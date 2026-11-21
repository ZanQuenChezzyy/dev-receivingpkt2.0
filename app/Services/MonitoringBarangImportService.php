<?php

namespace App\Services;

use App\Models\DeliveryOrderReceipt;
use App\Models\InvoicingReceipt;
use App\Models\PurchaseOrderIssued;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use XMLReader;
use ZipArchive;

class MonitoringBarangImportService
{
    /**
     * Pemetaan nama PIC pada spreadsheet Monitoring Barang ke email user sistem.
     *
     * @var array<string, string>
     */
    public const PIC_EMAIL_MAP = [
        'ALEX' => 'alex@receiving.com',
        'CAMEL' => 'camel@receiving.com',
        'ELLA' => 'husdayanti@receiving.com',
        'EVA' => 'eva@receiving.com',
        'HENGKY' => 'alex@receiving.com',
        'ICAN' => 'nafisa@receiving.com',
        'MICHAEL' => 'alex@receiving.com',
        'NAFISA' => 'nafisa@receiving.com',
        'PUGUH' => 'husdayanti@receiving.com',
        'RIZKI' => 'rizki@receiving.com',
        'WAHYU' => 'wahyu@receiving.com',
    ];

    /**
     * Mengecek apakah file (.xlsx atau .csv) berformat Monitoring Barang.
     */
    public function canHandleFile(string $filePath): bool
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            return false;
        }

        if ($this->isXlsxFile($filePath)) {
            try {
                $workbook = $this->openXlsxWorkbookMetadata($filePath);
                $sharedStrings = $this->loadXlsxSharedStrings($filePath);

                foreach ($workbook['sheets'] as $sheet) {
                    $previewRows = $this->readXlsxWorksheetRows(
                        $filePath,
                        $sheet['path'],
                        $sharedStrings,
                        $workbook['date_styles'],
                        $workbook['use_1904_dates'],
                        maxRows: 30
                    );

                    if ($this->detectHeader($previewRows) !== null) {
                        return true;
                    }
                }
            } catch (\Throwable) {
                return false;
            }

            return false;
        }

        $rows = $this->extractRowsFromCsvFile($filePath, maxRows: 30);
        if (empty($rows)) {
            return false;
        }

        return $this->detectHeader($rows) !== null;
    }

    /**
     * Impor data Monitoring Barang dari file Excel (.xlsx) atau CSV (.csv).
     *
     * @return array{
     *     created: int,
     *     updated: int,
     *     unchanged: int,
     *     skipped: int,
     *     linked_do: int,
     *     fallback_kasir_count: int,
     *     total_rows: int,
     *     incomplete_invoiced_rows: array<int, array{row_number: int, no_po: string, no_invoice: string, no_grs: ?string, missing_fields: array<int, string>}>,
     *     unmatched_pics: array<int, string>
     * }
     */
    public function importFromFile(string $filePath): array
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new RuntimeException("File tidak dapat dibaca: {$filePath}");
        }

        if ($this->isXlsxFile($filePath)) {
            $parsed = $this->extractRowsAndHeaderFromXlsx($filePath);
            $extracted = $this->extractParsedItems($parsed['rows'], $parsed['header_row_index'], $parsed['map']);
            unset($parsed);

            return $this->processParsedItems($extracted['items'], $extracted['skipped']);
        }

        $rows = $this->extractRowsFromCsvFile($filePath);
        if (empty($rows)) {
            throw new RuntimeException('File CSV kosong atau tidak dapat dibaca.');
        }

        $headerInfo = $this->detectHeader($rows);
        if ($headerInfo === null) {
            throw new RuntimeException('Header kolom Monitoring Barang tidak ditemukan. Pastikan kolom No. PO, Tanggal Terima GRS / No. GRS, dan No. Invoice tersedia.');
        }

        $extracted = $this->extractParsedItems($rows, $headerInfo['header_row_index'], $headerInfo['map']);
        unset($rows, $headerInfo);

        return $this->processParsedItems($extracted['items'], $extracted['skipped']);
    }

    protected function isXlsxFile(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $handle = fopen($filePath, 'rb');
        $magic = $handle ? fread($handle, 4) : '';
        if ($handle) {
            fclose($handle);
        }

        return $extension === 'xlsx' || $magic === "PK\x03\x04";
    }

    /**
     * Impor data Monitoring Barang dari konten string CSV.
     *
     * @return array{
     *     created: int,
     *     updated: int,
     *     unchanged: int,
     *     skipped: int,
     *     linked_do: int,
     *     fallback_kasir_count: int,
     *     total_rows: int,
     *     incomplete_invoiced_rows: array<int, array{row_number: int, no_po: string, no_invoice: string, no_grs: ?string, missing_fields: array<int, string>}>,
     *     unmatched_pics: array<int, string>
     * }
     */
    public function importFromString(string $csvContent): array
    {
        $rows = $this->extractRowsFromCsv($csvContent);
        unset($csvContent);

        if (empty($rows)) {
            throw new RuntimeException('Data CSV kosong atau tidak valid.');
        }

        $headerInfo = $this->detectHeader($rows);
        if ($headerInfo === null) {
            throw new RuntimeException('Header kolom Monitoring Barang tidak ditemukan. Pastikan kolom No. PO, Tanggal Terima GRS / No. GRS, dan No. Invoice tersedia.');
        }

        $extracted = $this->extractParsedItems($rows, $headerInfo['header_row_index'], $headerInfo['map']);
        unset($rows, $headerInfo);

        return $this->processParsedItems($extracted['items'], $extracted['skipped']);
    }

    /**
     * Memproses baris hasil ekstraksi (.xlsx atau .csv) ke tabel invoicing_receipts.
     *
     * @param  array<int, array<int, string>>  $rows
     * @param  array<string, mixed>  $map
     * @return array{
     *     created: int,
     *     updated: int,
     *     unchanged: int,
     *     skipped: int,
     *     linked_do: int,
     *     fallback_kasir_count: int,
     *     total_rows: int,
     *     incomplete_invoiced_rows: array<int, array{row_number: int, no_po: string, no_invoice: string, no_grs: ?string, missing_fields: array<int, string>}>,
     *     unmatched_pics: array<int, string>
     * }
     */
    protected function processParsedRows(array $rows, int $headerRowIndex, array $map): array
    {
        $extracted = $this->extractParsedItems($rows, $headerRowIndex, $map);
        unset($rows);

        return $this->processParsedItems($extracted['items'], $extracted['skipped']);
    }

    /**
     * Mengubah seluruh baris mentah menjadi array item terstruktur.
     *
     * @param  array<int, array<int, string>>  $rows
     * @param  array<string, mixed>  $map
     * @return array{items: array<int, array<string, mixed>>, skipped: int}
     */
    protected function extractParsedItems(array $rows, int $headerRowIndex, array $map): array
    {
        $parsedItems = [];
        $skippedCount = 0;

        for ($i = $headerRowIndex + 1, $total = count($rows); $i < $total; $i++) {
            $parsed = $this->parseDataRow($rows[$i], $map, $i + 1);
            if ($parsed === null) {
                $skippedCount++;

                continue;
            }

            $parsedItems[] = $parsed;
        }

        return [
            'items' => $parsedItems,
            'skipped' => $skippedCount,
        ];
    }

    /**
     * Memproses item terstruktur per chunk berbasis PO agar penggunaan RAM tetap rendah pada file berukuran besar.
     *
     * @param  array<int, array<string, mixed>>  $parsedItems
     * @return array{
     *     created: int,
     *     updated: int,
     *     unchanged: int,
     *     skipped: int,
     *     linked_do: int,
     *     fallback_kasir_count: int,
     *     total_rows: int,
     *     incomplete_invoiced_rows: array<int, array{row_number: int, no_po: string, no_invoice: string, no_grs: ?string, missing_fields: array<int, string>}>,
     *     unmatched_pics: array<int, string>
     * }
     */
    protected function processParsedItems(array $parsedItems, int $skippedCount): array
    {
        $totalRows = count($parsedItems);
        $chunks = $this->chunkParsedItemsByPo($parsedItems, 100);
        unset($parsedItems);

        $connection = DB::connection();
        $wasLoggingQueries = $connection->logging();
        if ($wasLoggingQueries) {
            $connection->disableQueryLog();
        }

        $createdCount = 0;
        $updatedCount = 0;
        $unchangedCount = 0;
        $linkedDoCount = 0;
        $fallbackKasirCount = 0;
        $incompleteInvoicedRows = [];
        $unmatchedPics = [];
        $claimedDoMap = [];

        try {
            $usersByEmail = $this->preloadUsersByEmail();
            [$vendorsByName, $vendorsByNumber] = $this->preloadVendorLookups();

            foreach ($chunks as $chunkIndex => $chunkItems) {
                $lookups = $this->preloadChunkLookups($chunkItems, $usersByEmail, $vendorsByName, $vendorsByNumber);
                $reservedDoOwners = $this->resolveStrictBatchDoClaims($chunkItems, $lookups);

                DB::transaction(function () use (
                    $chunkItems,
                    &$lookups,
                    $reservedDoOwners,
                    &$createdCount,
                    &$updatedCount,
                    &$unchangedCount,
                    &$linkedDoCount,
                    &$fallbackKasirCount,
                    &$incompleteInvoicedRows,
                    &$unmatchedPics,
                    &$claimedDoMap
                ): void {
                    $poPicSyncCandidates = [];
                    $touchedReceiptIdsByPo = [];

                    foreach ($chunkItems as $item) {
                        $result = $this->upsertItem($item, $lookups, $claimedDoMap, $reservedDoOwners);

                        if ($result['status'] === 'created') {
                            $createdCount++;
                        } elseif ($result['status'] === 'updated') {
                            $updatedCount++;
                        } else {
                            $unchangedCount++;
                        }

                        if ($result['linked_do']) {
                            $linkedDoCount++;
                        }

                        if ($item['used_kasir_fallback']) {
                            $fallbackKasirCount++;
                        }

                        if ($result['unmatched_pic'] !== null) {
                            $unmatchedPics[] = $result['unmatched_pic'];
                        }

                        if ($result['incomplete_info'] !== null) {
                            $incompleteInvoicedRows[] = $result['incomplete_info'];
                        }

                        $poNo = (string) ($item['no_po'] ?? '-');
                        if (filled($poNo) && $poNo !== '-') {
                            $poKey = 'po:'.$poNo;
                            if ($result['receipt_id'] !== null) {
                                $touchedReceiptIdsByPo[$poKey][] = $result['receipt_id'];
                            }

                            if ($result['resolved_pic_id'] !== null) {
                                $poPicSyncCandidates[$poKey]['po'] = $poNo;
                                $poPicSyncCandidates[$poKey]['pics'][$result['resolved_pic_id']] = true;
                            }
                        }
                    }

                    // Sinkronkan PIC untuk seluruh record eksisting lain pada PO yang sama agar mengikuti data impor terbaru
                    foreach ($poPicSyncCandidates as $poKey => $candidate) {
                        $picIdsMap = $candidate['pics'];
                        if (count($picIdsMap) !== 1) {
                            continue;
                        }

                        $poNoString = (string) $candidate['po'];
                        $latestPicId = (int) array_key_first($picIdsMap);
                        $siblingQuery = InvoicingReceipt::query()
                            ->where('no_po', $poNoString)
                            ->where(function ($q) use ($latestPicId): void {
                                $q->whereNull('pic_id')
                                    ->orWhere('pic_id', '!=', $latestPicId);
                            });

                        $touchedForPo = $touchedReceiptIdsByPo[$poKey] ?? [];
                        if (! empty($touchedForPo)) {
                            $siblingQuery->whereNotIn('id', $touchedForPo);
                        }

                        $siblingUpdated = $siblingQuery->update(['pic_id' => $latestPicId]);
                        $updatedCount += $siblingUpdated;
                    }
                });

                unset($lookups, $reservedDoOwners, $chunks[$chunkIndex]);
                gc_collect_cycles();
            }
        } finally {
            if ($wasLoggingQueries) {
                $connection->enableQueryLog();
            }
        }

        usort($incompleteInvoicedRows, fn (array $a, array $b): int => $a['row_number'] <=> $b['row_number']);

        return [
            'created' => $createdCount,
            'updated' => $updatedCount,
            'unchanged' => $unchangedCount,
            'skipped' => $skippedCount,
            'linked_do' => $linkedDoCount,
            'fallback_kasir_count' => $fallbackKasirCount,
            'total_rows' => $totalRows,
            'incomplete_invoiced_rows' => $incompleteInvoicedRows,
            'unmatched_pics' => array_values(array_unique($unmatchedPics)),
        ];
    }

    /**
     * Mengelompokkan item ke dalam chunk berbasis PO agar seluruh baris milik PO yang sama tetap diproses bersama.
     *
     * @param  array<int, array<string, mixed>>  $parsedItems
     * @return array<int, array<int, array<string, mixed>>>
     */
    protected function chunkParsedItemsByPo(array $parsedItems, int $targetChunkSize = 100): array
    {
        $groups = [];

        foreach ($parsedItems as $item) {
            $noPo = (string) ($item['no_po'] ?? '-');
            if ($noPo !== '' && $noPo !== '-') {
                $groupKey = 'po:'.$noPo;
            } elseif (filled($item['no_do'] ?? null)) {
                $groupKey = 'do:'.strtolower(trim((string) $item['no_do']));
            } else {
                $groupKey = 'row:'.($item['row_number'] ?? count($groups));
            }

            $groups[$groupKey][] = $item;
        }

        $chunks = [];
        $currentChunk = [];

        foreach ($groups as $groupItems) {
            if (! empty($currentChunk) && (count($currentChunk) + count($groupItems)) > $targetChunkSize) {
                $chunks[] = $currentChunk;
                $currentChunk = [];
            }

            foreach ($groupItems as $groupItem) {
                $currentChunk[] = $groupItem;
            }
        }

        if (! empty($currentChunk)) {
            $chunks[] = $currentChunk;
        }

        return $chunks;
    }

    /**
     * Ekstrak seluruh baris dari sheet Monitoring Barang pada file Excel (.xlsx) menggunakan streaming XMLReader
     * agar tahan terhadap sel bernilai numerik yang keliru berformat tanggal atau error formula Excel.
     *
     * @return array{rows: array<int, array<int, string>>, header_row_index: int, map: array<string, mixed>}
     */
    protected function extractRowsAndHeaderFromXlsx(string $filePath): array
    {
        $workbook = $this->openXlsxWorkbookMetadata($filePath);
        $sharedStrings = $this->loadXlsxSharedStrings($filePath);

        $selectedSheet = null;
        $bestScore = PHP_INT_MIN;

        foreach ($workbook['sheets'] as $sheet) {
            $previewRows = $this->readXlsxWorksheetRows(
                $filePath,
                $sheet['path'],
                $sharedStrings,
                $workbook['date_styles'],
                $workbook['use_1904_dates'],
                maxRows: 30
            );

            $headerInfo = $this->detectHeader($previewRows);
            if ($headerInfo === null) {
                continue;
            }

            $score = $this->scoreMonitoringSheetCandidate($sheet['name']);
            if ($score > $bestScore) {
                $bestScore = $score;
                $selectedSheet = [
                    'path' => $sheet['path'],
                    'header_row_index' => $headerInfo['header_row_index'],
                    'map' => $headerInfo['map'],
                ];
            }
        }

        if ($selectedSheet === null) {
            throw new RuntimeException('Header kolom Monitoring Barang tidak ditemukan pada workbook Excel (.xlsx).');
        }

        $rows = $this->readXlsxWorksheetRows(
            $filePath,
            $selectedSheet['path'],
            $sharedStrings,
            $workbook['date_styles'],
            $workbook['use_1904_dates']
        );

        return [
            'rows' => $rows,
            'header_row_index' => $selectedSheet['header_row_index'],
            'map' => $selectedSheet['map'],
        ];
    }

    /**
     * Membaca daftar sheet beserta style format tanggal dari metadata workbook .xlsx.
     *
     * @return array{
     *     sheets: array<int, array{name: string, path: string}>,
     *     date_styles: array<int, bool>,
     *     use_1904_dates: bool
     * }
     */
    protected function openXlsxWorkbookMetadata(string $filePath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('File Excel (.xlsx) tidak valid atau korup.');
        }

        try {
            $workbookXml = $zip->getFromName('xl/workbook.xml');
            $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

            if ($workbookXml === false || $relsXml === false) {
                throw new RuntimeException('Struktur metadata workbook Excel (.xlsx) tidak lengkap.');
            }

            $relTargets = [];
            $relsDom = new DOMDocument;
            $relsDom->loadXML($relsXml);
            foreach ($relsDom->getElementsByTagName('*') as $node) {
                if ($node->localName === 'Relationship') {
                    $id = $node->getAttribute('Id');
                    $target = ltrim(str_replace('\\', '/', $node->getAttribute('Target')), '/');
                    if ($id !== '' && $target !== '') {
                        $relTargets[$id] = str_starts_with($target, 'xl/') ? $target : "xl/{$target}";
                    }
                }
            }

            $wbDom = new DOMDocument;
            $wbDom->loadXML($workbookXml);

            $use1904Dates = false;
            foreach ($wbDom->getElementsByTagName('*') as $node) {
                if ($node->localName === 'workbookPr') {
                    $flag = strtolower(trim($node->getAttribute('date1904')));
                    $use1904Dates = in_array($flag, ['1', 'true'], true);

                    break;
                }
            }

            $sheets = [];
            foreach ($wbDom->getElementsByTagName('*') as $node) {
                if ($node->localName !== 'sheet') {
                    continue;
                }

                $name = $node->getAttribute('name');
                $rId = $node->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
                if ($rId === '') {
                    $rId = $node->getAttribute('r:id');
                }

                if ($rId !== '' && isset($relTargets[$rId])) {
                    $sheets[] = [
                        'name' => $name,
                        'path' => $relTargets[$rId],
                    ];
                }
            }

            return [
                'sheets' => $sheets,
                'date_styles' => $this->loadXlsxDateStyleMap($zip),
                'use_1904_dates' => $use1904Dates,
            ];
        } finally {
            $zip->close();
        }
    }

    /**
     * Memetakan indeks style xf Excel yang bertipe format tanggal.
     *
     * @return array<int, bool>
     */
    protected function loadXlsxDateStyleMap(ZipArchive $zip): array
    {
        $stylesXml = $zip->getFromName('xl/styles.xml');
        if ($stylesXml === false) {
            return [];
        }

        $dom = new DOMDocument;
        if (! @$dom->loadXML($stylesXml)) {
            return [];
        }

        $customFormats = [];
        foreach ($dom->getElementsByTagName('*') as $node) {
            if ($node->localName === 'numFmt') {
                $fmtId = (int) $node->getAttribute('numFmtId');
                $customFormats[$fmtId] = $node->getAttribute('formatCode');
            }
        }

        $dateStyles = [];
        foreach ($dom->getElementsByTagName('*') as $node) {
            if ($node->localName !== 'cellXfs') {
                continue;
            }

            $xfIdx = 0;
            foreach ($node->childNodes as $child) {
                if (! $child instanceof DOMElement || $child->localName !== 'xf') {
                    continue;
                }

                $numFmtId = (int) $child->getAttribute('numFmtId');
                $code = $customFormats[$numFmtId] ?? '';
                $isBuiltinDate = ($numFmtId >= 14 && $numFmtId <= 22) || ($numFmtId >= 45 && $numFmtId <= 47);

                if ($isBuiltinDate || ($code !== '' && $this->isDateFormatCode($code))) {
                    $dateStyles[$xfIdx] = true;
                }

                $xfIdx++;
            }

            break;
        }

        return $dateStyles;
    }

    protected function isDateFormatCode(string $formatCode): bool
    {
        $stripped = preg_replace(['/\[[^\]]*\]/', '/"[^"]*"/', '/\\\\./', '/_./'], '', $formatCode) ?? '';

        if (str_contains($stripped, '#') || preg_match('/0(?:\.0+)?/', $stripped) === 1) {
            return false;
        }

        return preg_match('/(?:y{2,4}|d{1,4}|m{2,4}|(?<![a-z])m(?![a-z]))/i', $stripped) === 1;
    }

    /**
     * Memuat tabel sharedStrings.xml secara streaming tanpa memakan memori besar.
     *
     * @return array<int, string>
     */
    protected function loadXlsxSharedStrings(string $filePath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            return [];
        }

        $hasSharedStrings = $zip->locateName('xl/sharedStrings.xml') !== false;
        $zip->close();

        if (! $hasSharedStrings) {
            return [];
        }

        $realPath = realpath($filePath) ?: $filePath;
        $reader = new XMLReader;
        if (! @$reader->open("zip://{$realPath}#xl/sharedStrings.xml")) {
            return [];
        }

        $strings = [];
        $inSi = false;
        $inPhonetic = false;
        $buffer = '';

        try {
            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::ELEMENT) {
                    if ($reader->localName === 'si') {
                        $inSi = true;
                        $inPhonetic = false;
                        $buffer = '';
                    } elseif ($inSi && $reader->localName === 'rPh') {
                        $inPhonetic = true;
                    }
                } elseif ($inSi && ! $inPhonetic && ($reader->nodeType === XMLReader::TEXT || $reader->nodeType === XMLReader::CDATA)) {
                    $buffer .= $reader->value;
                } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
                    if ($reader->localName === 'rPh') {
                        $inPhonetic = false;
                    } elseif ($reader->localName === 'si') {
                        $strings[] = trim($buffer);
                        $inSi = false;
                    }
                }
            }
        } finally {
            $reader->close();
        }

        return $strings;
    }

    /**
     * Membaca baris worksheet Excel (.xlsx) secara streaming menggunakan XMLReader.
     *
     * @param  array<int, string>  $sharedStrings
     * @param  array<int, bool>  $dateStyles
     * @return array<int, array<int, string>>
     */
    protected function readXlsxWorksheetRows(
        string $filePath,
        string $zipSheetPath,
        array $sharedStrings,
        array $dateStyles,
        bool $use1904Dates,
        ?int $maxRows = null
    ): array {
        $realPath = realpath($filePath) ?: $filePath;
        $reader = new XMLReader;
        if (! @$reader->open("zip://{$realPath}#{$zipSheetPath}")) {
            return [];
        }

        $rows = [];
        $dom = new DOMDocument;
        $expandedCount = 0;

        try {
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                    continue;
                }

                $rowNode = $reader->expand($dom);
                if (! $rowNode instanceof DOMElement) {
                    continue;
                }

                $expandedCount++;
                if ($expandedCount % 250 === 0) {
                    $dom = new DOMDocument;
                }

                $cells = [];
                $nextColIndex = 0;

                foreach ($rowNode->childNodes as $cellNode) {
                    if (! $cellNode instanceof DOMElement || $cellNode->localName !== 'c') {
                        continue;
                    }

                    $ref = $cellNode->getAttribute('r');
                    $colIdx = $ref !== '' ? $this->columnReferenceToIndex($ref) : $nextColIndex;
                    $nextColIndex = $colIdx + 1;

                    if ($colIdx < 0 || $colIdx > 60) {
                        continue;
                    }

                    while (count($cells) < $colIdx) {
                        $cells[] = '';
                    }

                    $cells[$colIdx] = $this->extractXlsxCellValue(
                        $cellNode,
                        $sharedStrings,
                        $dateStyles,
                        $use1904Dates
                    );
                }

                $rows[] = $cells;

                if ($maxRows !== null && count($rows) >= $maxRows) {
                    break;
                }
            }
        } finally {
            $reader->close();
        }

        return $rows;
    }

    protected function extractXlsxCellValue(
        DOMElement $cellNode,
        array $sharedStrings,
        array $dateStyles,
        bool $use1904Dates
    ): string {
        $cellType = $cellNode->getAttribute('t');
        if ($cellType === 'e') {
            return '';
        }

        if ($cellType === 'inlineStr') {
            foreach ($cellNode->childNodes as $child) {
                if ($child instanceof DOMElement && $child->localName === 'is') {
                    return $this->extractDomTextOutsidePhonetic($child);
                }
            }

            return '';
        }

        $vValue = '';
        foreach ($cellNode->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === 'v') {
                $vValue = $child->nodeValue ?? '';

                break;
            }
        }

        if ($vValue === '') {
            return '';
        }

        if ($cellType === 's') {
            return trim((string) ($sharedStrings[(int) $vValue] ?? ''));
        }

        if ($cellType === 'b') {
            return ($vValue === '1' || strtolower(trim($vValue)) === 'true') ? '1' : '0';
        }

        if ($cellType === 'str') {
            $trimmed = trim($vValue);

            return in_array(strtoupper($trimmed), ['#N/A', '#VALUE!', '#REF!', '#DIV/0!', '#NAME?', '#NUM!', '#NULL!'], true)
                ? ''
                : $trimmed;
        }

        $trimmed = trim($vValue);
        if (! is_numeric($trimmed)) {
            return $trimmed;
        }

        $numericVal = (float) $trimmed;
        $styleIdx = (int) $cellNode->getAttribute('s');

        if (isset($dateStyles[$styleIdx])) {
            $maxExcelDays = $use1904Dates ? 2957003.9999884 : 2958465.9999884;
            if ($numericVal >= 1 && $numericVal <= $maxExcelDays) {
                $baseDate = $use1904Dates ? Carbon::create(1904, 1, 1) : Carbon::create(1899, 12, 30);

                return $baseDate->addDays((int) floor($numericVal))->format('Y-m-d');
            }
        }

        if (preg_match('/^-?\d+$/', $trimmed) === 1) {
            return $trimmed;
        }

        if (fmod($numericVal, 1.0) === 0.0) {
            return number_format($numericVal, 0, '', '');
        }

        return (string) $numericVal;
    }

    protected function extractDomTextOutsidePhonetic(DOMElement $element): string
    {
        $text = '';
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                if ($child->localName === 'rPh') {
                    continue;
                }
                $text .= $this->extractDomTextOutsidePhonetic($child);
            } elseif ($child->nodeType === XML_TEXT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                $text .= $child->nodeValue;
            }
        }

        return trim($text);
    }

    protected function columnReferenceToIndex(string $cellRef): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($cellRef)) ?? '';
        $index = 0;

        for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - ord('A') + 1);
        }

        return $index - 1;
    }

    protected function scoreMonitoringSheetCandidate(string $sheetName): int
    {
        $lower = strtolower(trim($sheetName));
        $score = 0;

        if (in_array($lower, ['barang_', 'barang', 'monitoring barang', 'monitoring'], true)) {
            $score += 100;
        } elseif (str_contains($lower, 'monitoring') || str_contains($lower, 'barang')) {
            $score += 50;
        }

        foreach (['backup', 'error', 'laporan', 'draft', 'copy', 'old', 'transit', 'jasa'] as $penaltyKeyword) {
            if (str_contains($lower, $penaltyKeyword)) {
                $score -= 40;
            }
        }

        return $score;
    }

    /**
     * Ekstrak baris CSV langsung dari file secara streaming agar hemat memori untuk file besar.
     *
     * @return array<int, array<int, string>>
     */
    protected function extractRowsFromCsvFile(string $filePath, ?int $maxRows = null): array
    {
        $stream = fopen($filePath, 'rb');
        if ($stream === false) {
            return [];
        }

        try {
            $firstLine = fgets($stream);
            if ($firstLine === false) {
                return [];
            }

            $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine) ?? $firstLine;
            $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

            rewind($stream);

            $rows = [];
            $isFirstRow = true;

            while (($data = fgetcsv($stream, 0, $delimiter, '"', '\\')) !== false) {
                if ($isFirstRow) {
                    if (isset($data[0])) {
                        $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $data[0]) ?? (string) $data[0];
                    }
                    $isFirstRow = false;
                }

                $rows[] = array_map(fn (?string $val): string => trim((string) $val), $data);
                if ($maxRows !== null && count($rows) >= $maxRows) {
                    break;
                }
            }

            return $rows;
        } finally {
            fclose($stream);
        }
    }

    /**
     * Ekstrak seluruh baris dari string CSV dengan dukungan header & sel multiline serta deteksi delimiter.
     *
     * @return array<int, array<int, string>>
     */
    protected function extractRowsFromCsv(string $csvContent, ?int $maxRows = null): array
    {
        $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', $csvContent) ?? $csvContent;
        $firstLine = strtok($csvContent, "\r\n") ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return [];
        }

        fwrite($stream, $csvContent);
        rewind($stream);

        $rows = [];
        while (($data = fgetcsv($stream, 0, $delimiter, '"', '\\')) !== false) {
            $rows[] = array_map(fn (?string $val): string => trim((string) $val), $data);
            if ($maxRows !== null && count($rows) >= $maxRows) {
                break;
            }
        }

        fclose($stream);

        return $rows;
    }

    /**
     * Deteksi baris header Monitoring Barang dan petakan posisi indeks kolomnya.
     *
     * @param  array<int, array<int, string>>  $rows
     * @return array{header_row_index: int, map: array<string, mixed>}|null
     */
    protected function detectHeader(array $rows): ?array
    {
        $limit = min(count($rows), 30);

        for ($rowIdx = 0; $rowIdx < $limit; $rowIdx++) {
            $map = $this->buildColumnIndexMap($rows[$rowIdx]);
            $hasPo = isset($map['no_po']) || isset($map['po_fallback']);
            $hasGrsOrDate = isset($map['tanggal_terima_grs']) || isset($map['no_grs']);
            $hasInvoice = isset($map['no_invoice']);

            if ($hasPo && $hasGrsOrDate && $hasInvoice) {
                return [
                    'header_row_index' => $rowIdx,
                    'map' => $map,
                ];
            }
        }

        return null;
    }

    /**
     * Memetakan nama header ke indeks kolom numerik agar header duplikat/kosong tidak bertabrakan.
     *
     * @param  array<int, string>  $headerRow
     * @return array<string, mixed>
     */
    protected function buildColumnIndexMap(array $headerRow): array
    {
        $map = [
            'vendor_indices' => [],
        ];

        foreach ($headerRow as $idx => $colName) {
            $slug = preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $colName))) ?? '';

            if ($slug === '' || in_array($slug, ['no', 'nomor'], true)) {
                continue;
            }

            if (in_array($slug, ['tanggalterimagrs', 'tglterimagrs'], true)) {
                $map['tanggal_terima_grs'] ??= $idx;
            } elseif (in_array($slug, ['jenis', 'kategoribarang'], true)) {
                $map['kategori_barang'] ??= $idx;
            } elseif (in_array($slug, ['nogrs', 'nomorgrs'], true)) {
                $map['no_grs'] ??= $idx;
            } elseif (in_array($slug, ['nordtv', 'nomorrdtv'], true)) {
                $map['no_rdtv'] ??= $idx;
            } elseif (in_array($slug, ['nopo', 'nomorpo'], true)) {
                $map['no_po'] ??= $idx;
            } elseif ($slug === 'po') {
                $map['po_fallback'] ??= $idx;
            } elseif ($slug === 'pic') {
                $map['pic'] ??= $idx;
            } elseif ($slug === 'padi') {
                $map['padi'] ??= $idx;
            } elseif (in_array($slug, ['vendor', 'namavendor'], true)) {
                $map['vendor_indices'][] = $idx;
            } elseif (in_array($slug, ['deskripsi', 'description'], true)) {
                $map['deskripsi'] ??= $idx;
            } elseif (in_array($slug, ['email', 'emailvendor'], true)) {
                $map['email_vendor'] ??= $idx;
            } elseif (in_array($slug, ['tanggalinfogrs', 'tglinfogrs'], true)) {
                $map['tanggal_info_grs'] ??= $idx;
            } elseif (in_array($slug, ['noinvoice', 'nomorinvoice'], true)) {
                $map['no_invoice'] ??= $idx;
            } elseif (in_array($slug, ['nodo', 'nomordo'], true)) {
                $map['no_do'] ??= $idx;
            } elseif (in_array($slug, ['fakturpajak', 'nofakturpajak'], true)) {
                $map['faktur_pajak'] ??= $idx;
            } elseif ($slug === 'namabarang') {
                $map['nama_barang'] ??= $idx;
            } elseif (in_array($slug, ['quantitypenagihan', 'qtypenagihan'], true)) {
                $map['qty_penagihan'] ??= $idx;
            } elseif (in_array($slug, ['quantitydipo', 'qtydipo', 'qtypo'], true)) {
                $map['qty_po'] ??= $idx;
            } elseif (in_array($slug, ['uoi', 'uom', 'satuan'], true)) {
                $map['uoi'] ??= $idx;
            } elseif ($slug === 'subtotalexcludeppn') {
                $map['subtotal_exclude_ppn'] ??= $idx;
            } elseif ($slug === 'subtotalincludeppn') {
                $map['subtotal_include_ppn'] ??= $idx;
            } elseif (in_array($slug, ['tanggalterimakoreksiinvoice', 'tglterimakoreksiinvoice', 'tanggalterimadankoreksiinvoice', 'tglterimadankoreksiinvoice'], true)) {
                $map['tanggal_terima_koreksi_invoice'] ??= $idx;
            } elseif (in_array($slug, ['tanggalkirimkasir', 'tglkirimkasir'], true)) {
                $map['tanggal_kirim_kasir'] ??= $idx;
            } elseif ($slug === 'lamapekerjaan') {
                $map['lama_pekerjaan'] ??= $idx;
            } elseif ($slug === 'keterangan') {
                $map['keterangan'] ??= $idx;
            }
        }

        return $map;
    }

    /**
     * Mem-parsing satu baris data Monitoring Barang dan menerapkan aturan bisnis status & fallback tanggal.
     *
     * @param  array<int, string>  $row
     * @param  array<string, mixed>  $map
     * @return array<string, mixed>|null
     */
    protected function parseDataRow(array $row, array $map, int $rowNumber): ?array
    {
        if (empty(array_filter($row, fn (string $v): bool => trim($v) !== ''))) {
            return null;
        }

        $rawPo = $this->nullIfEmpty($this->cellByKey($row, $map, 'no_po'))
            ?? $this->nullIfEmpty($this->cellByKey($row, $map, 'po_fallback'));
        $noPo = $this->limitString($this->cleanPoNumber($rawPo), 255);
        $noGrs = $this->limitString($this->nullIfEmpty($this->cellByKey($row, $map, 'no_grs')), 255);
        $noRdtv = $this->limitString($this->nullIfEmpty($this->cellByKey($row, $map, 'no_rdtv')), 255);
        $noInvoice = $this->limitString($this->nullIfEmpty($this->cellByKey($row, $map, 'no_invoice')), 255);
        $vendorName = $this->limitString($this->resolveVendorNameFromRow($row, $map['vendor_indices'] ?? []), 255);

        if ($noPo === null && $noGrs === null && $noRdtv === null && $noInvoice === null && $vendorName === null) {
            return null;
        }

        $finalNoPo = $noPo ?? '-';
        $isRdtvInGrsCol = $noGrs !== null && preg_match('/^rdtv\b/i', $noGrs) === 1;
        $jenis = ($noGrs !== null && ! $isRdtvInGrsCol)
            ? 'GRS'
            : (($noRdtv !== null || $isRdtvInGrsCol) ? 'RDTV' : 'GRS');
        $kategoriBarang = $this->limitString($this->nullIfEmpty($this->cellByKey($row, $map, 'kategori_barang')), 255);
        $padi = $this->limitString($this->nullIfEmpty($this->cellByKey($row, $map, 'padi')), 255);
        $picName = $this->nullIfEmpty($this->cellByKey($row, $map, 'pic'));

        $tanggalTerimaGrs = $this->parseDate($this->cellByKey($row, $map, 'tanggal_terima_grs'));
        $tanggalInfoGrs = $this->parseDate($this->cellByKey($row, $map, 'tanggal_info_grs'));
        $tanggalTerimaKoreksi = $this->parseDate($this->cellByKey($row, $map, 'tanggal_terima_koreksi_invoice'));
        $rawTanggalKirimKasir = $this->parseDate($this->cellByKey($row, $map, 'tanggal_kirim_kasir'));

        $hasInvoice = $noInvoice !== null;
        $isRdtvInformed = $jenis === 'RDTV' && $tanggalInfoGrs !== null;
        $usedKasirFallback = false;

        if ($hasInvoice) {
            $status = 'Selesai';

            if ($rawTanggalKirimKasir !== null) {
                $finalTanggalKirimKasir = $rawTanggalKirimKasir;
            } elseif ($tanggalTerimaKoreksi !== null) {
                $finalTanggalKirimKasir = $tanggalTerimaKoreksi;
                $usedKasirFallback = true;
            } elseif ($tanggalInfoGrs !== null) {
                $finalTanggalKirimKasir = Carbon::parse($tanggalInfoGrs)->addDays(3)->format('Y-m-d');
                $usedKasirFallback = true;
            } else {
                $finalTanggalKirimKasir = null;
            }
        } elseif ($isRdtvInformed) {
            // Dokumen RDTV selesai di tahap Info GRS (menginfokan RDTV ke vendor) tanpa tahap kasir
            $status = 'Selesai';
            $finalTanggalKirimKasir = $rawTanggalKirimKasir;
        } else {
            $status = 'Menunggu Verifikasi';
            $finalTanggalKirimKasir = $rawTanggalKirimKasir;
        }

        $rawLamaPekerjaan = $this->cellByKey($row, $map, 'lama_pekerjaan');
        $parsedLama = ($rawLamaPekerjaan !== null && $rawLamaPekerjaan !== '' && is_numeric($rawLamaPekerjaan))
            ? (int) $rawLamaPekerjaan
            : null;
        $lamaPekerjaan = ($parsedLama !== null && $parsedLama >= 0 && $parsedLama <= 36500)
            ? $parsedLama
            : null;

        $rawNamaBarang = $this->nullIfEmpty($this->cellByKey($row, $map, 'nama_barang'));
        $rawDeskripsi = $this->nullIfEmpty($this->cellByKey($row, $map, 'deskripsi'));
        $rawQtyPenagihan = $this->cellByKey($row, $map, 'qty_penagihan');
        $rawQtyPo = $this->cellByKey($row, $map, 'qty_po');
        $rawUoi = $this->cellByKey($row, $map, 'uoi');
        $rawSubtotalExclude = $this->cellByKey($row, $map, 'subtotal_exclude_ppn');
        $rawSubtotalInclude = $this->cellByKey($row, $map, 'subtotal_include_ppn');

        $itemDetails = $this->parseMultilineItems(
            $rawNamaBarang,
            $rawQtyPenagihan,
            $rawQtyPo,
            $rawUoi,
            $rawSubtotalExclude,
            $rawSubtotalInclude,
            $rawDeskripsi
        );

        $parsedNamaBarang = $rawNamaBarang;
        if (! empty($itemDetails) && count($itemDetails) > 1) {
            $distinctNames = array_values(array_unique(array_filter(array_column($itemDetails, 'nama_barang'))));
            if (! empty($distinctNames)) {
                $parsedNamaBarang = implode(', ', $distinctNames);
            }
        }

        return [
            'row_number' => $rowNumber,
            'tanggal_terima_grs' => $tanggalTerimaGrs,
            'jenis' => $jenis,
            'kategori_barang' => $kategoriBarang,
            'padi' => $padi,
            'no_grs' => $noGrs,
            'no_rdtv' => $noRdtv,
            'no_po' => $finalNoPo,
            'pic_name' => $picName,
            'vendor_name' => $vendorName,
            'deskripsi' => $rawDeskripsi,
            'email_vendor' => $this->limitString($this->normalizeEmailList($this->cellByKey($row, $map, 'email_vendor')), 255),
            'tanggal_info_grs' => $tanggalInfoGrs,
            'no_invoice' => $noInvoice,
            'no_do' => $this->limitString($this->nullIfEmpty($this->cellByKey($row, $map, 'no_do')), 255),
            'faktur_pajak' => $this->limitString($this->nullIfEmpty($this->cellByKey($row, $map, 'faktur_pajak')), 255),
            'nama_barang' => $parsedNamaBarang,
            'qty_penagihan' => $this->clampDecimal($this->parseNumeric($rawQtyPenagihan), 99_999_999_999.9999),
            'qty_po' => $this->clampDecimal($this->parseNumeric($rawQtyPo), 99_999_999_999.9999),
            'uoi' => $this->normalizeUoi($rawUoi),
            'subtotal_exclude_ppn' => $this->clampDecimal($this->parseNumeric($rawSubtotalExclude), 9_999_999_999_999_999.99),
            'subtotal_include_ppn' => $this->clampDecimal($this->parseNumeric($rawSubtotalInclude), 9_999_999_999_999_999.99),
            'item_details' => $itemDetails,
            'tanggal_terima_koreksi_invoice' => $tanggalTerimaKoreksi,
            'tanggal_kirim_kasir' => $finalTanggalKirimKasir,
            'lama_pekerjaan' => $lamaPekerjaan,
            'keterangan' => $this->nullIfEmpty($this->cellByKey($row, $map, 'keterangan')),
            'status' => $status,
            'has_invoice' => $hasInvoice,
            'used_kasir_fallback' => $usedKasirFallback,
        ];
    }

    /**
     * Memecah sel multiline (nama barang, qty penagihan, qty po, uoi, subtotal)
     * menjadi array rincian item berpasangan.
     *
     * @return array<int, array{
     *     nama_barang: ?string,
     *     qty_penagihan: ?float,
     *     qty_po: ?float,
     *     uoi: ?string,
     *     subtotal_exclude_ppn: ?float,
     *     subtotal_include_ppn: ?float
     * }>
     */
    protected function parseMultilineItems(
        ?string $rawNama,
        ?string $rawQtyPenagihan,
        ?string $rawQtyPo,
        ?string $rawUoi,
        ?string $rawSubtotalExclude,
        ?string $rawSubtotalInclude,
        ?string $fallbackDescription = null
    ): array {
        $namaLines = ($rawNama !== null && trim($rawNama) !== '')
            ? (preg_split('/[\r\n]+/', trim($rawNama)) ?: [])
            : [];

        if (empty($namaLines) && $fallbackDescription !== null && trim($fallbackDescription) !== '') {
            $namaLines = preg_split('/[\r\n]+/', trim($fallbackDescription)) ?: [];
        }

        $namaLines = array_values(array_map('trim', $namaLines));

        $qtyPenagihanLines = ($rawQtyPenagihan !== null && trim($rawQtyPenagihan) !== '')
            ? array_values(array_map('trim', preg_split('/[\r\n]+/', trim($rawQtyPenagihan)) ?: []))
            : [];

        $qtyPoLines = ($rawQtyPo !== null && trim($rawQtyPo) !== '')
            ? array_values(array_map('trim', preg_split('/[\r\n]+/', trim($rawQtyPo)) ?: []))
            : [];

        $uoiLines = ($rawUoi !== null && trim($rawUoi) !== '')
            ? array_values(array_map('trim', preg_split('/[\r\n]+/', trim($rawUoi)) ?: []))
            : [];

        $subtotalExLines = ($rawSubtotalExclude !== null && trim($rawSubtotalExclude) !== '')
            ? array_values(array_map('trim', preg_split('/[\r\n]+/', trim($rawSubtotalExclude)) ?: []))
            : [];

        $subtotalInLines = ($rawSubtotalInclude !== null && trim($rawSubtotalInclude) !== '')
            ? array_values(array_map('trim', preg_split('/[\r\n]+/', trim($rawSubtotalInclude)) ?: []))
            : [];

        $count = max(count($namaLines), count($qtyPenagihanLines), count($qtyPoLines));

        if ($count === 0) {
            if (! empty($subtotalExLines) || ! empty($subtotalInLines)) {
                $count = 1;
            } else {
                return [];
            }
        }

        $items = [];
        for ($i = 0; $i < $count; $i++) {
            $itemName = $namaLines[$i] ?? ($count === 1 ? ($namaLines[0] ?? null) : null);
            if ($itemName !== null) {
                $itemName = $this->nullIfEmpty($itemName);
            }

            $qtyPenagihan = isset($qtyPenagihanLines[$i])
                ? $this->clampDecimal($this->parseSingleNumeric($qtyPenagihanLines[$i]), 99_999_999_999.9999)
                : null;

            $qtyPo = isset($qtyPoLines[$i])
                ? $this->clampDecimal($this->parseSingleNumeric($qtyPoLines[$i]), 99_999_999_999.9999)
                : null;

            // Jika UOI hanya ditulis 1 baris di spreadsheet, terapkan untuk semua item
            if (count($uoiLines) === 1) {
                $uoi = $this->normalizeUoi($uoiLines[0]);
            } else {
                $uoi = isset($uoiLines[$i]) ? $this->normalizeUoi($uoiLines[$i]) : null;
            }

            // Jika subtotal memiliki jumlah baris yang sama dengan jumlah item, gunakan nilai masing-masing.
            // Jika hanya 1 nilai (lump-sum satu invoice), subtotal dicatat di header, bukan diduplikasi per item.
            if (count($subtotalExLines) === $count && isset($subtotalExLines[$i])) {
                $subtotalEx = $this->clampDecimal($this->parseSingleNumeric($subtotalExLines[$i]), 9_999_999_999_999_999.99);
            } elseif ($count === 1 && isset($subtotalExLines[0])) {
                $subtotalEx = $this->clampDecimal($this->parseSingleNumeric($subtotalExLines[0]), 9_999_999_999_999_999.99);
            } else {
                $subtotalEx = null;
            }

            if (count($subtotalInLines) === $count && isset($subtotalInLines[$i])) {
                $subtotalIn = $this->clampDecimal($this->parseSingleNumeric($subtotalInLines[$i]), 9_999_999_999_999_999.99);
            } elseif ($count === 1 && isset($subtotalInLines[0])) {
                $subtotalIn = $this->clampDecimal($this->parseSingleNumeric($subtotalInLines[0]), 9_999_999_999_999_999.99);
            } else {
                $subtotalIn = ($subtotalEx !== null) ? round($subtotalEx * 1.11, 2) : null;
            }

            // Abaikan item jika seluruh kolomnya kosong
            if ($itemName === null && $qtyPenagihan === null && $qtyPo === null && $subtotalEx === null) {
                continue;
            }

            $items[] = [
                'nama_barang' => $itemName,
                'qty_penagihan' => $qtyPenagihan,
                'qty_po' => $qtyPo,
                'uoi' => $uoi,
                'subtotal_exclude_ppn' => $subtotalEx,
                'subtotal_include_ppn' => $subtotalIn,
            ];
        }

        return $items;
    }

    /**
     * Preload daftar email user -> ID sekali per sesi impor dengan footprint memori minimal.
     *
     * @return array<string, int>
     */
    protected function preloadUsersByEmail(): array
    {
        $usersByEmail = [];

        foreach (DB::table('users')->select(['id', 'email'])->get() as $user) {
            $email = strtolower(trim((string) $user->email));
            if ($email !== '') {
                $usersByEmail[$email] = (int) $user->id;
            }
        }

        return $usersByEmail;
    }

    /**
     * Preload daftar vendor dalam bentuk objek ringan (tanpa hidrasi model Eloquent penuh).
     *
     * @return array{
     *     0: array<string, object{id: int, name: ?string, email_string: ?string}>,
     *     1: array<string, object{id: int, name: ?string, email_string: ?string}>
     * }
     */
    protected function preloadVendorLookups(): array
    {
        $vendorsByName = [];
        $vendorsByNumber = [];

        foreach (DB::table('vendors')->select(['id', 'name', 'vendor_number', 'email'])->cursor() as $row) {
            $vendorEntry = (object) [
                'id' => (int) $row->id,
                'name' => $row->name !== null ? (string) $row->name : null,
                'email_string' => $this->parseVendorEmailString($row->email),
            ];

            $clean = strtolower((string) PurchaseOrderIssued::cleanVendorName($vendorEntry->name));
            if ($clean !== '' && ! isset($vendorsByName[$clean])) {
                $vendorsByName[$clean] = $vendorEntry;
            }

            $vendorNo = trim((string) ($row->vendor_number ?? ''));
            if ($vendorNo !== '' && ! isset($vendorsByNumber[$vendorNo])) {
                $vendorsByNumber[$vendorNo] = $vendorEntry;
            }
        }

        return [$vendorsByName, $vendorsByNumber];
    }

    protected function parseVendorEmailString(mixed $rawEmail): ?string
    {
        if (blank($rawEmail)) {
            return null;
        }

        if (is_array($rawEmail)) {
            $emails = array_values(array_filter(array_map('trim', $rawEmail)));

            return ! empty($emails) ? implode(', ', $emails) : null;
        }

        $decoded = json_decode((string) $rawEmail, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $emails = array_values(array_filter(array_map('trim', $decoded)));

            return ! empty($emails) ? implode(', ', $emails) : null;
        }

        $parts = preg_split('/[,;]+/', (string) $rawEmail);
        $emails = array_values(array_filter(array_map('trim', (array) $parts)));

        return ! empty($emails) ? implode(', ', $emails) : null;
    }

    /**
     * Preload master User, PurchaseOrderIssued, Vendor, dan DeliveryOrderReceipt untuk satu kumpulan baris.
     *
     * @param  array<int, array<string, mixed>>  $parsedItems
     * @return array{
     *     users_by_email: array<string, int>,
     *     pos_by_no: array<string, PurchaseOrderIssued>,
     *     all_pos_by_no: array<string, array<int, PurchaseOrderIssued>>,
     *     vendors_by_name: array<string, object>,
     *     vendors_by_number: array<string, object>,
     *     dos_by_po: array<string, Collection<int, DeliveryOrderReceipt>>,
     *     dos_by_do: array<string, Collection<int, DeliveryOrderReceipt>>,
     *     existing_by_grs: array<string, Collection<int, InvoicingReceipt>>,
     *     existing_by_po: array<string, Collection<int, InvoicingReceipt>>
     * }
     */
    protected function preloadLookups(array $parsedItems): array
    {
        $usersByEmail = $this->preloadUsersByEmail();
        [$vendorsByName, $vendorsByNumber] = $this->preloadVendorLookups();

        return $this->preloadChunkLookups($parsedItems, $usersByEmail, $vendorsByName, $vendorsByNumber);
    }

    /**
     * Preload lookup terkompresi khusus untuk satu chunk item (dengan seleksi kolom minimal & deduplikasi model).
     *
     * @param  array<int, array<string, mixed>>  $chunkItems
     * @param  array<string, int>  $usersByEmail
     * @param  array<string, object>  $vendorsByName
     * @param  array<string, object>  $vendorsByNumber
     * @return array{
     *     users_by_email: array<string, int>,
     *     pos_by_no: array<string, PurchaseOrderIssued>,
     *     all_pos_by_no: array<string, array<int, PurchaseOrderIssued>>,
     *     vendors_by_name: array<string, object>,
     *     vendors_by_number: array<string, object>,
     *     dos_by_po: array<string, Collection<int, DeliveryOrderReceipt>>,
     *     dos_by_do: array<string, Collection<int, DeliveryOrderReceipt>>,
     *     existing_by_grs: array<string, Collection<int, InvoicingReceipt>>,
     *     existing_by_po: array<string, Collection<int, InvoicingReceipt>>
     * }
     */
    protected function preloadChunkLookups(
        array $chunkItems,
        array $usersByEmail,
        array $vendorsByName,
        array $vendorsByNumber
    ): array {
        $poNumbers = array_values(array_unique(array_filter(
            array_column($chunkItems, 'no_po'),
            fn (string $po): bool => $po !== '' && $po !== '-'
        )));

        $grsNumbers = array_values(array_unique(array_filter(
            array_column($chunkItems, 'no_grs'),
            fn (mixed $grs): bool => is_string($grs) && $grs !== ''
        )));

        $posByNo = [];
        $allPosByNo = [];
        $dosByPo = [];
        $existingByGrs = [];
        $existingByPo = [];
        $loadedReceiptIds = [];

        $doRelationSelects = [
            'deliveryOrderReceiptDetails:id,delivery_order_receipt_id,purchase_order_issued_id,material_type,description,uoi,quantity',
            'deliveryOrderReceiptDetails.purchaseOrderIssued:id,purchase_order_no',
            'grsRdtvItems:id,delivery_order_receipt_id',
            'invoicingReceipts:id,delivery_order_receipt_id,no_grs,no_invoice',
        ];

        foreach (array_chunk($poNumbers, 250) as $poChunk) {
            $poIds = [];

            $poRecords = PurchaseOrderIssued::query()
                ->select([
                    'id',
                    'purchase_order_no',
                    'item_no',
                    'material_type',
                    'description',
                    'qty_po',
                    'uoi',
                    'vendor_id',
                    'vendor_name',
                ])
                ->whereIn('purchase_order_no', $poChunk)
                ->orderBy('item_no')
                ->get();

            foreach ($poRecords as $po) {
                $poIds[] = $po->id;
                $poKey = trim((string) $po->purchase_order_no);
                if ($poKey !== '') {
                    $allPosByNo[$poKey] ??= [];
                    $allPosByNo[$poKey][] = $po;

                    if (! isset($posByNo[$poKey])) {
                        $posByNo[$poKey] = $po;
                    }
                }
            }

            if (! empty($poIds)) {
                $doIds = [];
                foreach (array_chunk($poIds, 500) as $poIdChunk) {
                    $matchedIds = DB::table('delivery_order_receipt_details')
                        ->whereIn('purchase_order_issued_id', $poIdChunk)
                        ->whereNotNull('delivery_order_receipt_id')
                        ->distinct()
                        ->pluck('delivery_order_receipt_id')
                        ->all();

                    foreach ($matchedIds as $doId) {
                        $doIds[(int) $doId] = true;
                    }
                }

                foreach (array_chunk(array_keys($doIds), 250) as $doIdChunk) {
                    $doCandidates = DeliveryOrderReceipt::query()
                        ->select(['id', 'delivery_order_no', 'status', 'received_date', 'physical_received_date'])
                        ->with($doRelationSelects)
                        ->whereIn('id', $doIdChunk)
                        ->get();

                    foreach ($doCandidates as $do) {
                        $linkedPos = $do->deliveryOrderReceiptDetails
                            ->map(fn ($detail): ?string => $detail->purchaseOrderIssued?->purchase_order_no)
                            ->filter()
                            ->unique();

                        foreach ($linkedPos as $linkedPo) {
                            $dosByPo[$linkedPo] ??= collect();
                            $dosByPo[$linkedPo]->push($do);
                        }
                    }
                }
            }

            foreach (InvoicingReceipt::query()->whereIn('no_po', $poChunk)->get() as $existing) {
                $loadedReceiptIds[$existing->id] = true;
                $existingByPo[$existing->no_po] ??= collect();
                $existingByPo[$existing->no_po]->push($existing);

                if (filled($existing->no_grs)) {
                    $existingByGrs[$existing->no_grs] ??= collect();
                    $existingByGrs[$existing->no_grs]->push($existing);
                }
            }
        }

        $loadedReceiptIdList = array_keys($loadedReceiptIds);
        foreach (array_chunk($grsNumbers, 250) as $grsChunk) {
            $grsQuery = InvoicingReceipt::query()->whereIn('no_grs', $grsChunk);
            if (! empty($loadedReceiptIdList)) {
                $grsQuery->whereNotIn('id', $loadedReceiptIdList);
            }

            foreach ($grsQuery->get() as $existing) {
                if (filled($existing->no_grs)) {
                    $existingByGrs[$existing->no_grs] ??= collect();
                    $existingByGrs[$existing->no_grs]->push($existing);
                }
            }
        }

        $dosByDo = [];
        $doTokensNeedingDirectLookup = [];

        foreach ($chunkItems as $item) {
            $itemPo = (string) ($item['no_po'] ?? '-');
            $itemDo = $item['no_do'] ?? null;

            if ($itemDo !== null && ($itemPo === '-' || ! isset($dosByPo[$itemPo]))) {
                $doTokensNeedingDirectLookup[] = trim((string) $itemDo);
                foreach (preg_split('/[\r\n,;&]+/', (string) $itemDo) ?: [] as $part) {
                    $trimmedPart = trim($part);
                    if ($trimmedPart !== '' && $trimmedPart !== '-') {
                        $doTokensNeedingDirectLookup[] = $trimmedPart;
                    }
                }
            }
        }

        $doTokensNeedingDirectLookup = array_values(array_unique(array_filter($doTokensNeedingDirectLookup)));
        foreach (array_chunk($doTokensNeedingDirectLookup, 250) as $doChunk) {
            $directDos = DeliveryOrderReceipt::query()
                ->select(['id', 'delivery_order_no', 'status', 'received_date', 'physical_received_date'])
                ->with($doRelationSelects)
                ->whereIn('delivery_order_no', $doChunk)
                ->get();

            foreach ($directDos as $do) {
                $doKey = strtolower(trim((string) $do->delivery_order_no));
                if ($doKey !== '') {
                    $dosByDo[$doKey] ??= collect();
                    $dosByDo[$doKey]->push($do);
                }
            }
        }

        return [
            'users_by_email' => $usersByEmail,
            'pos_by_no' => $posByNo,
            'all_pos_by_no' => $allPosByNo,
            'vendors_by_name' => $vendorsByName,
            'vendors_by_number' => $vendorsByNumber,
            'dos_by_po' => $dosByPo,
            'dos_by_do' => $dosByDo,
            'existing_by_grs' => $existingByGrs,
            'existing_by_po' => $existingByPo,
        ];
    }

    /**
     * Melakukan upsert satu baris ke tabel invoicing_receipts beserta relasinya.
     *
     * @param  array<string, mixed>  $item
     * @param  array{
     *     users_by_email: array<string, int>,
     *     pos_by_no: array<string, PurchaseOrderIssued>,
     *     vendors_by_name: array<string, object>,
     *     vendors_by_number: array<string, object>,
     *     dos_by_po: array<string, Collection<int, DeliveryOrderReceipt>>,
     *     dos_by_do: array<string, Collection<int, DeliveryOrderReceipt>>,
     *     existing_by_grs: array<string, Collection<int, InvoicingReceipt>>,
     *     existing_by_po: array<string, Collection<int, InvoicingReceipt>>
     * }  $lookups
     * @param  array<int, string>  $claimedDoMap
     * @param  array<int, string>  $reservedDoOwners
     * @return array{
     *     status: 'created'|'updated'|'unchanged',
     *     linked_do: bool,
     *     unmatched_pic: ?string,
     *     incomplete_info: ?array{row_number: int, no_po: string, no_invoice: string, no_grs: ?string, missing_fields: array<int, string>}
     * }
     */
    protected function upsertItem(array $item, array &$lookups, array &$claimedDoMap, array $reservedDoOwners = []): array
    {
        $poRecord = $lookups['pos_by_no'][$item['no_po']] ?? null;
        $vendor = $this->resolveVendor($item['vendor_name'], $poRecord, $lookups['vendors_by_name'], $lookups['vendors_by_number']);
        $matchedDo = $this->matchDeliveryOrderReceipt(
            $item,
            $lookups['dos_by_po'][$item['no_po']] ?? collect(),
            $claimedDoMap,
            $reservedDoOwners,
            $lookups['dos_by_do'] ?? []
        );
        $grsRdtvItem = $matchedDo?->grsRdtvItems?->sortByDesc('id')->first();

        [$picId, $unmatchedPic] = $this->resolvePicId($item['pic_name'], $lookups['users_by_email']);

        $existing = $this->findExistingReceipt(
            noPo: $item['no_po'],
            jenis: $item['jenis'],
            noGrs: $item['no_grs'],
            noRdtv: $item['no_rdtv'],
            noInvoice: $item['no_invoice'],
            noDo: $item['no_do'],
            namaBarang: $item['nama_barang'],
            lookups: $lookups
        );

        $receipt = $existing ?? new InvoicingReceipt;
        $isExisting = $receipt->exists;

        $doDetails = $matchedDo?->deliveryOrderReceiptDetails ?? collect();
        $firstDetail = $doDetails->first();
        $existingMaterialType = ($receipt->material_type !== null && InvoicingReceipt::normalizeMaterialType($receipt->material_type) !== InvoicingReceipt::DEFAULT_MATERIAL_TYPE)
            ? $receipt->material_type
            : null;
        $materialType = $this->cleanMaterialType($existingMaterialType ?? $firstDetail?->material_type ?? $poRecord?->material_type);

        $subtotalInclude = $item['subtotal_include_ppn'] ?? $receipt->subtotal_include_ppn;
        $receipt->preserveExplicitIncludePpn = $subtotalInclude !== null;

        $resolvedStatus = $item['status'];
        if (
            $isExisting
            && $resolvedStatus === 'Menunggu Verifikasi'
            && in_array($receipt->status, ['Terverifikasi', 'Terkirim Kasir', 'Selesai'], true)
        ) {
            $resolvedStatus = $receipt->status;
        }

        $receipt->fill([
            'grs_rdtv_item_id' => $receipt->grs_rdtv_item_id ?? $grsRdtvItem?->id,
            'delivery_order_receipt_id' => $matchedDo?->id ?? $receipt->delivery_order_receipt_id,
            'purchase_order_issued_id' => $receipt->purchase_order_issued_id ?? $poRecord?->id,
            'vendor_id' => $vendor?->id ?? $receipt->vendor_id,
            'pic_id' => $picId ?? $receipt->pic_id,
            'tanggal_terima_grs' => $item['tanggal_terima_grs'] ?? $receipt->tanggal_terima_grs,
            'jenis' => $item['jenis'],
            'kategori_barang' => $item['kategori_barang'] ?? $receipt->kategori_barang,
            'padi' => $item['padi'] ?? $receipt->padi,
            'no_grs' => $item['no_grs'] ?? $receipt->no_grs,
            'no_rdtv' => $item['no_rdtv'] ?? $receipt->no_rdtv,
            'no_po' => $item['no_po'],
            'no_do' => $item['no_do'] ?? $matchedDo?->delivery_order_no ?? $receipt->no_do,
            'material_type' => $materialType,
            'vendor_name' => $item['vendor_name'] ?? $receipt->vendor_name ?? $poRecord?->vendor_name ?? $vendor?->name,
            'deskripsi' => $item['deskripsi'] ?? $receipt->deskripsi ?? $firstDetail?->description ?? $poRecord?->description,
            'email_vendor' => $item['email_vendor'] ?? $receipt->email_vendor ?? $vendor?->email_string,
            'tanggal_info_grs' => $item['tanggal_info_grs'] ?? $receipt->tanggal_info_grs,
            'no_invoice' => $item['no_invoice'] ?? $receipt->no_invoice,
            'faktur_pajak' => $item['faktur_pajak'] ?? $receipt->faktur_pajak,
            'nama_barang' => $item['nama_barang'] ?? $receipt->nama_barang ?? $firstDetail?->description ?? $poRecord?->description,
            'qty_penagihan' => $item['qty_penagihan'] ?? $receipt->qty_penagihan,
            'qty_po' => $item['qty_po'] ?? $receipt->qty_po ?? ($poRecord?->qty_po ? (float) $poRecord->qty_po : null),
            'uoi' => $item['uoi'] ?? $receipt->uoi ?? $firstDetail?->uoi ?? $poRecord?->uoi,
            'subtotal_exclude_ppn' => $item['subtotal_exclude_ppn'] ?? $receipt->subtotal_exclude_ppn,
            'subtotal_include_ppn' => $subtotalInclude,
            'tanggal_terima_koreksi_invoice' => $item['tanggal_terima_koreksi_invoice'] ?? $receipt->tanggal_terima_koreksi_invoice,
            'tanggal_kirim_kasir' => $item['tanggal_kirim_kasir'] ?? $receipt->tanggal_kirim_kasir,
            'lama_pekerjaan' => $item['lama_pekerjaan'] ?? $receipt->lama_pekerjaan,
            'keterangan' => $item['keterangan'] ?? $receipt->keterangan,
            'status' => $resolvedStatus,
        ]);

        if ($isExisting && ! $receipt->isDirty()) {
            $saveStatus = 'unchanged';
        } else {
            $receipt->save();
            $saveStatus = $isExisting ? 'updated' : 'created';

            if (filled($receipt->no_grs)) {
                $lookups['existing_by_grs'][$receipt->no_grs] ??= collect();
                if (! $lookups['existing_by_grs'][$receipt->no_grs]->contains('id', $receipt->id)) {
                    $lookups['existing_by_grs'][$receipt->no_grs]->push($receipt);
                }
            }
            if (! $isExisting && filled($receipt->no_po) && $receipt->no_po !== '-') {
                $lookups['existing_by_po'][$receipt->no_po] ??= collect();
                $lookups['existing_by_po'][$receipt->no_po]->push($receipt);
            }
        }

        // Sinkronisasi rincian item barang ke tabel invoicing_receipt_items
        $itemDetails = $item['item_details'] ?? [];
        if (empty($itemDetails)) {
            $fallbackNama = $item['nama_barang'] ?? $firstDetail?->description ?? $poRecord?->description;
            if (filled($fallbackNama) || filled($item['qty_penagihan']) || filled($item['subtotal_exclude_ppn'])) {
                $itemDetails = [
                    [
                        'nama_barang' => $fallbackNama,
                        'qty_penagihan' => $item['qty_penagihan'],
                        'qty_po' => $item['qty_po'] ?? ($poRecord?->qty_po ? (float) $poRecord->qty_po : null),
                        'uoi' => $item['uoi'] ?? $firstDetail?->uoi ?? $poRecord?->uoi,
                    ],
                ];
            }
        }

        if (! empty($itemDetails)) {
            $existingItems = $receipt->items()->get();

            // Jika sebelumnya hanya ada 1 item yang berisi multiline nama barang,
            // dan sekarang dipecah menjadi multiple items yang rapi, bersihkan item lama tersebut.
            if ($existingItems->count() === 1 && count($itemDetails) > 1 && str_contains((string) $existingItems->first()->nama_barang, "\n")) {
                $receipt->items()->delete();
                $existingItems = collect();
            }

            $poList = collect($lookups['all_pos_by_no'][$item['no_po']] ?? []);

            foreach ($itemDetails as $seq => $detail) {
                $detailNama = $detail['nama_barang'] ?? $firstDetail?->description ?? $poRecord?->description;

                // Cocokkan item barang secara akurat ke PO terbit berdasarkan deskripsi
                $matchedItemPo = PurchaseOrderIssued::matchByDescription($detailNama, $poList)
                    ?? ($poList[$seq] ?? $poRecord);

                $resolvedQtyPo = $detail['qty_po']
                    ?? ($matchedItemPo?->qty_po ? (float) $matchedItemPo->qty_po : null);

                $resolvedQtyPenagihan = $detail['qty_penagihan']
                    ?? $resolvedQtyPo;

                // Cari existing item berdasarkan nama_barang atau urutan indeks
                $existingItem = null;
                if (filled($detailNama)) {
                    $existingItem = $existingItems->first(fn ($it) => $it->nama_barang === $detailNama);
                }
                if (! $existingItem && isset($existingItems[$seq])) {
                    $existingItem = $existingItems[$seq];
                }

                if ($existingItem) {
                    $existingItem->update([
                        'purchase_order_issued_id' => $existingItem->purchase_order_issued_id ?? $matchedItemPo?->id ?? $poRecord?->id,
                        'delivery_order_receipt_detail_id' => $existingItem->delivery_order_receipt_detail_id ?? $firstDetail?->id,
                        'item_no' => $existingItem->item_no ?? $matchedItemPo?->item_no ?? ($seq + 1),
                        'no_grs' => $item['no_grs'] ?? $existingItem->no_grs ?? $receipt->no_grs,
                        'no_do' => $item['no_do'] ?? $existingItem->no_do ?? $receipt->no_do,
                        'nama_barang' => $detailNama,
                        'qty_penagihan' => $detail['qty_penagihan'] ?? $existingItem->qty_penagihan ?? $resolvedQtyPenagihan,
                        'qty_po' => $detail['qty_po'] ?? $existingItem->qty_po ?? $resolvedQtyPo,
                        'uoi' => $detail['uoi'] ?? $existingItem->uoi ?? $matchedItemPo?->uoi ?? $firstDetail?->uoi ?? $poRecord?->uoi,
                    ]);
                } else {
                    $receipt->items()->create([
                        'purchase_order_issued_id' => $matchedItemPo?->id ?? $poRecord?->id,
                        'delivery_order_receipt_detail_id' => $firstDetail?->id,
                        'item_no' => $matchedItemPo?->item_no ?? ($seq + 1),
                        'no_grs' => $item['no_grs'] ?? $receipt->no_grs,
                        'no_do' => $item['no_do'] ?? $receipt->no_do,
                        'nama_barang' => $detailNama,
                        'qty_penagihan' => $resolvedQtyPenagihan,
                        'qty_po' => $resolvedQtyPo,
                        'uoi' => $detail['uoi'] ?? $matchedItemPo?->uoi ?? $firstDetail?->uoi ?? $poRecord?->uoi,
                    ]);
                }
            }

            if ($receipt->items()->count() > 1) {
                $receipt->syncSummaryFromItems();
                $receipt->saveQuietly();
            }
        }

        if ($receipt->delivery_order_receipt_id !== null) {
            $claimedDoMap[$receipt->delivery_order_receipt_id] = $this->resolveItemClaimKey($item);
        }

        $incompleteInfo = null;
        if ($item['has_invoice']) {
            $missingFields = $this->collectMissingInvoicedFields($receipt);
            if (! empty($missingFields)) {
                $incompleteInfo = [
                    'row_number' => $item['row_number'],
                    'no_po' => (string) $receipt->no_po,
                    'no_invoice' => (string) $receipt->no_invoice,
                    'no_grs' => $receipt->no_grs,
                    'missing_fields' => $missingFields,
                ];
            }
        }

        return [
            'status' => $saveStatus,
            'linked_do' => $receipt->delivery_order_receipt_id !== null,
            'unmatched_pic' => $unmatchedPic,
            'incomplete_info' => $incompleteInfo,
            'receipt_id' => $receipt->id,
            'resolved_pic_id' => $picId,
        ];
    }

    /**
     * Mengumpulkan daftar kolom/tanggal penting yang masih kosong pada record berstatus 'Selesai' (ber-invoice).
     *
     * @return array<int, string>
     */
    protected function collectMissingInvoicedFields(InvoicingReceipt $receipt): array
    {
        $missing = [];

        if ($receipt->tanggal_kirim_kasir === null) {
            $missing[] = 'Tanggal Kirim Kasir';
        }

        if ($receipt->tanggal_terima_koreksi_invoice === null) {
            $missing[] = 'Tanggal Terima & Koreksi Invoice';
        }

        if ($receipt->tanggal_info_grs === null) {
            $missing[] = 'Tanggal Info GRS';
        }

        if ($this->nullIfEmpty($receipt->no_do) === null) {
            $missing[] = 'No. DO';
        }

        if ($this->nullIfEmpty($receipt->faktur_pajak) === null) {
            $missing[] = 'Faktur Pajak';
        }

        return $missing;
    }

    /**
     * Cari record InvoicingReceipt eksisting agar setiap tahap GRS (No. GRS unik)
     * maupun penolakan (RDTV) tetap terpisah dan idempotent saat diimpor ulang.
     *
     * @param  array<string, mixed>  $lookups
     */
    protected function findExistingReceipt(
        string $noPo,
        string $jenis,
        ?string $noGrs,
        ?string $noRdtv,
        ?string $noInvoice,
        ?string $noDo,
        ?string $namaBarang,
        array $lookups
    ): ?InvoicingReceipt {
        $poCandidates = $lookups['existing_by_po'][$noPo] ?? collect();

        // 0. Prioritaskan jika No. Invoice sudah ada di PO yang sama (1 invoice menagihkan beberapa barang / beda GRS)
        if ($noInvoice !== null && $poCandidates->isNotEmpty()) {
            $sameInvoice = $poCandidates->first(
                fn (InvoicingReceipt $r): bool => $r->no_invoice !== null && strcasecmp($r->no_invoice, $noInvoice) === 0
            );
            if ($sameInvoice) {
                return $sameInvoice;
            }
        }

        // 1. Jika memiliki No. GRS: No. GRS selalu unik setiap terbit (Tahap 1 vs Tahap 2 berbeda)
        if ($noGrs !== null) {
            $candidates = $lookups['existing_by_grs'][$noGrs] ?? collect();

            if ($candidates->isNotEmpty()) {
                if ($noInvoice !== null) {
                    $sameInvoice = $candidates->first(
                        fn (InvoicingReceipt $r): bool => $r->no_invoice !== null && strcasecmp($r->no_invoice, $noInvoice) === 0
                    );
                    if ($sameInvoice) {
                        return $sameInvoice;
                    }

                    $unbilled = $candidates->first(fn (InvoicingReceipt $r): bool => blank($r->no_invoice));
                    if ($unbilled) {
                        return $unbilled;
                    }

                    return null;
                }

                $unbilled = $candidates->first(fn (InvoicingReceipt $r): bool => blank($r->no_invoice));
                if ($unbilled) {
                    return $unbilled;
                }

                // Jika No. GRS sudah ada dan sudah terisi No. Invoice di sistem sementara CSV belum mengisi No. Invoice,
                // tetap perbarui record eksisting (terutama PIC) agar tidak membuat duplikat
                $samePoCandidate = $candidates->first(fn (InvoicingReceipt $r): bool => $r->no_po === $noPo);

                return $samePoCandidate ?? $candidates->first();
            }

            // Fallback ke record auto-generate dari GRS/DO yang belum memiliki no_grs & no_invoice
            if ($poCandidates->isNotEmpty()) {
                if ($noDo !== null) {
                    $matchedByDo = $poCandidates->first(
                        fn (InvoicingReceipt $r): bool => $r->no_do === $noDo && blank($r->no_grs) && blank($r->no_invoice)
                    );
                    if ($matchedByDo) {
                        return $matchedByDo;
                    }
                }

                // Jika CSV tidak mencantumkan No. DO, cocokkan ke record auto-generate pada PO yang sama
                // yang belum memiliki No. GRS dan belum ditagihkan agar data & PIC terbarui tanpa duplikasi
                $unmatchedAutoGrs = $poCandidates->first(
                    fn (InvoicingReceipt $r): bool => $r->jenis === $jenis && blank($r->no_grs) && blank($r->no_invoice)
                );
                if ($unmatchedAutoGrs) {
                    return $unmatchedAutoGrs;
                }
            }

            return null;
        }

        // 2. Jika dokumen RDTV (No. GRS kosong & No. RDTV terisi)
        if ($jenis === 'RDTV' && $noRdtv !== null) {
            $candidates = $poCandidates->filter(
                fn (InvoicingReceipt $r): bool => $r->no_rdtv === $noRdtv && $r->jenis === 'RDTV' && blank($r->no_grs)
            );

            if ($candidates->isEmpty()) {
                return $poCandidates->first(
                    fn (InvoicingReceipt $r): bool => $r->jenis === 'RDTV'
                        && blank($r->no_grs)
                        && blank($r->no_rdtv)
                        && blank($r->no_invoice)
                        && ($noDo === null || $r->no_do === $noDo)
                );
            }

            if ($noInvoice !== null) {
                $sameInvoice = $candidates->first(
                    fn (InvoicingReceipt $r): bool => $r->no_invoice !== null && strcasecmp($r->no_invoice, $noInvoice) === 0
                );

                return $sameInvoice ?? $candidates->first(fn (InvoicingReceipt $r): bool => blank($r->no_invoice));
            }

            return $candidates->first(fn (InvoicingReceipt $r): bool => blank($r->no_invoice))
                ?? $candidates->first();
        }

        // 3. Fallback jika keduanya kosong
        if ($noInvoice !== null) {
            return $poCandidates->first(
                fn (InvoicingReceipt $r): bool => $r->no_invoice === $noInvoice && blank($r->no_grs)
            );
        }

        if ($noDo !== null) {
            return $poCandidates->first(function (InvoicingReceipt $r) use ($noDo, $namaBarang): bool {
                $matchesBase = $r->no_do === $noDo && blank($r->no_grs) && blank($r->no_rdtv) && blank($r->no_invoice);

                return $matchesBase && ($namaBarang === null || $r->nama_barang === $namaBarang);
            });
        }

        // Jika No. GRS, No. RDTV, No. Invoice, dan No. DO sama-sama kosong (contoh: PO baru tanpa nomor GRS),
        // cocokkan ke record PO yang sama yang belum memiliki nomor GRS/RDTV/Invoice agar tidak duplikat saat re-import
        if ($poCandidates->isNotEmpty()) {
            $sameName = $namaBarang !== null
                ? $poCandidates->first(
                    fn (InvoicingReceipt $r): bool => $r->jenis === $jenis
                        && blank($r->no_grs)
                        && blank($r->no_rdtv)
                        && blank($r->no_invoice)
                        && $r->nama_barang === $namaBarang
                )
                : null;

            return $sameName ?? $poCandidates->first(
                fn (InvoicingReceipt $r): bool => $r->jenis === $jenis
                    && blank($r->no_grs)
                    && blank($r->no_rdtv)
                    && blank($r->no_invoice)
            );
        }

        return null;
    }

    /**
     * Reservasi DO yang cocok persis / normalisasi No. DO dalam batch agar fallback PO-level tidak merebut DO milik baris lain.
     *
     * @param  array<int, array<string, mixed>>  $parsedItems
     * @param  array<string, mixed>  $lookups
     * @return array<int, string>
     */
    protected function resolveStrictBatchDoClaims(array $parsedItems, array $lookups): array
    {
        $reserved = [];

        foreach ($parsedItems as $item) {
            $noDo = $item['no_do'] ?? null;
            if ($noDo === null) {
                continue;
            }

            $noPo = (string) ($item['no_po'] ?? '-');
            $claimKey = $this->resolveItemClaimKey($item);
            $poCandidates = $lookups['dos_by_po'][$noPo] ?? collect();

            if ($noPo !== '' && $noPo !== '-' && $poCandidates->isNotEmpty()) {
                $strictMatches = $this->findDoMatchesWithinCandidates($noDo, $poCandidates);
                if ($strictMatches->isNotEmpty()) {
                    $picked = $this->selectPreferredMatchingDo($strictMatches, $reserved, $item['no_invoice'] ?? null);
                    if ($picked !== null) {
                        $reserved[$picked->id] ??= $claimKey;
                    }
                }
            } elseif (! empty($lookups['dos_by_do'])) {
                $direct = $this->matchDirectByDoNumber($noPo, $noDo, $lookups['dos_by_do']);
                if ($direct !== null) {
                    $reserved[$direct->id] ??= $claimKey;
                }
            }
        }

        return $reserved;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function resolveItemClaimKey(array $item): string
    {
        return (string) ($item['no_grs'] ?? $item['no_rdtv'] ?? $item['no_invoice'] ?? "row-{$item['row_number']}");
    }

    /**
     * Cocokkan ke DeliveryOrderReceipt melalui 4 lapis pencocokan:
     * 1. Kecocokan No. DO (penuh, alfanumerik, multi-DO, slash) dalam PO yang sama
     * 2. Fallback PO sama saat No. DO salah ketik / diisi sembarangan (typo, DO tunggal tersisa, atau qty & tanggal)
     * 3. Fallback langsung via No. DO unik bila PO tidak tertaut
     *
     * @param  array<string, mixed>  $item
     * @param  Collection<int, DeliveryOrderReceipt>  $candidates
     * @param  array<int, string>  $claimedDoMap
     * @param  array<int, string>  $reservedDoOwners
     * @param  array<string, Collection<int, DeliveryOrderReceipt>>  $dosByDo
     */
    protected function matchDeliveryOrderReceipt(
        array $item,
        Collection $candidates,
        array $claimedDoMap,
        array $reservedDoOwners = [],
        array $dosByDo = []
    ): ?DeliveryOrderReceipt {
        $noPo = (string) ($item['no_po'] ?? '-');
        $noDo = $item['no_do'] ?? null;
        $noGrs = $item['no_grs'] ?? null;
        $noInvoice = $item['no_invoice'] ?? null;
        $jenis = (string) ($item['jenis'] ?? 'GRS');
        $claimKey = $this->resolveItemClaimKey($item);

        if ($noPo !== '' && $noPo !== '-' && $candidates->isNotEmpty()) {
            if ($noDo !== null) {
                $matched = $this->findDoMatchesWithinCandidates($noDo, $candidates);
                if ($matched->isNotEmpty()) {
                    return $this->selectPreferredMatchingDo($matched, $claimedDoMap, $noInvoice);
                }
            }

            $eligible = $candidates->filter(
                fn (DeliveryOrderReceipt $do): bool => $this->isEligibleFallbackDo(
                    $do,
                    $noGrs,
                    $noInvoice,
                    $jenis,
                    $claimedDoMap,
                    $reservedDoOwners,
                    $claimKey
                )
            )->values();

            if ($eligible->isEmpty()) {
                return null;
            }

            if ($noDo !== null) {
                $typoMatch = $this->findBestTypoMatchInCandidates($noDo, $eligible);
                if ($typoMatch !== null) {
                    return $typoMatch;
                }
            }

            if ($eligible->count() === 1) {
                return $eligible->first();
            }

            if ($noDo !== null) {
                return $this->disambiguateEligibleDosByQtyAndDate(
                    $eligible,
                    $item['qty_penagihan'] ?? null,
                    $item['tanggal_terima_grs'] ?? null
                );
            }

            return null;
        }

        if ($noDo !== null && ! empty($dosByDo)) {
            return $this->matchDirectByDoNumber($noPo, $noDo, $dosByDo);
        }

        return null;
    }

    /**
     * @param  array<int, string>  $claimedDoMap
     * @param  array<int, string>  $reservedDoOwners
     */
    protected function isEligibleFallbackDo(
        DeliveryOrderReceipt $do,
        ?string $noGrs,
        ?string $noInvoice,
        string $jenis,
        array $claimedDoMap,
        array $reservedDoOwners,
        string $claimKey
    ): bool {
        if (isset($reservedDoOwners[$do->id]) && strcasecmp($reservedDoOwners[$do->id], $claimKey) !== 0) {
            return false;
        }

        if (isset($claimedDoMap[$do->id]) && strcasecmp($claimedDoMap[$do->id], $claimKey) !== 0) {
            return false;
        }

        if ($jenis === 'RDTV' && $do->status === 'GRS') {
            return false;
        }

        if ($do->status === 'GRS' && $noGrs === null && $noInvoice === null) {
            return false;
        }

        return ! $do->invoicingReceipts->contains(function (InvoicingReceipt $ir) use ($noGrs, $noInvoice): bool {
            if (filled($ir->no_grs) && ($noGrs === null || strcasecmp($ir->no_grs, $noGrs) !== 0)) {
                return true;
            }

            if (filled($ir->no_invoice) && ($noInvoice === null || strcasecmp($ir->no_invoice, $noInvoice) !== 0)) {
                return true;
            }

            return false;
        });
    }

    /**
     * Mencari kecocokan typo ringan (edit distance <= 2) pada No. DO di dalam PO yang sama.
     *
     * @param  Collection<int, DeliveryOrderReceipt>  $eligible
     */
    protected function findBestTypoMatchInCandidates(string $noDo, Collection $eligible): ?DeliveryOrderReceipt
    {
        $normTarget = $this->normalizeAlnum($noDo);
        if (strlen($normTarget) < 6 || strlen($normTarget) > 250) {
            return null;
        }

        $scored = [];
        foreach ($eligible as $do) {
            $normDb = $this->normalizeAlnum((string) $do->delivery_order_no);
            if (strlen($normDb) < 6 || strlen($normDb) > 250) {
                continue;
            }

            $distance = levenshtein($normTarget, $normDb);
            if ($distance <= 2) {
                $scored[] = ['do' => $do, 'distance' => $distance];
            }
        }

        if (empty($scored)) {
            return null;
        }

        usort($scored, fn (array $a, array $b): int => $a['distance'] <=> $b['distance']);

        if (count($scored) === 1 || $scored[0]['distance'] < $scored[1]['distance']) {
            return $scored[0]['do'];
        }

        return null;
    }

    /**
     * Memilah kandidat DO pada PO yang sama menggunakan kecocokan kuantitas penagihan dan kedekatan tanggal terima.
     *
     * @param  Collection<int, DeliveryOrderReceipt>  $eligible
     */
    protected function disambiguateEligibleDosByQtyAndDate(
        Collection $eligible,
        ?float $qtyPenagihan,
        ?string $tanggalTerimaGrs
    ): ?DeliveryOrderReceipt {
        $pool = $eligible;

        if ($qtyPenagihan !== null && $qtyPenagihan > 0) {
            $qtyMatches = $pool->filter(function (DeliveryOrderReceipt $do) use ($qtyPenagihan): bool {
                $doQty = (float) $do->deliveryOrderReceiptDetails->sum('quantity');

                return $doQty > 0 && abs($doQty - $qtyPenagihan) <= 0.01;
            })->values();

            if ($qtyMatches->count() === 1) {
                return $qtyMatches->first();
            }

            if ($qtyMatches->isNotEmpty()) {
                $pool = $qtyMatches;
            }
        }

        if ($tanggalTerimaGrs !== null) {
            $grsDate = Carbon::parse($tanggalTerimaGrs)->startOfDay();
            $dateScored = [];

            foreach ($pool as $do) {
                $arrivalRaw = $do->physical_received_date ?? $do->received_date;
                if ($arrivalRaw === null) {
                    continue;
                }

                $arrivalDate = Carbon::parse($arrivalRaw)->startOfDay();
                $daysBeforeGrs = (int) $arrivalDate->diffInDays($grsDate, false);
                if ($daysBeforeGrs >= 0 && $daysBeforeGrs <= 90) {
                    $dateScored[] = ['do' => $do, 'days' => $daysBeforeGrs];
                }
            }

            if (count($dateScored) === 1) {
                return $dateScored[0]['do'];
            }

            if (count($dateScored) > 1) {
                usort($dateScored, fn (array $a, array $b): int => $a['days'] <=> $b['days']);
                if ($dateScored[0]['days'] < $dateScored[1]['days']) {
                    return $dateScored[0]['do'];
                }
            }
        }

        return null;
    }

    /**
     * Mencari DO di dalam kandidat PO yang sama: penuh, normalisasi alfanumerik, token multiline/koma, maupun segmen slash.
     *
     * @param  Collection<int, DeliveryOrderReceipt>  $candidates
     * @return Collection<int, DeliveryOrderReceipt>
     */
    protected function findDoMatchesWithinCandidates(string $noDo, Collection $candidates): Collection
    {
        $trimmedFull = trim($noDo);
        $directMatches = $this->filterCandidatesByDoString($trimmedFull, $candidates, minNormLength: 1);
        if ($directMatches->isNotEmpty()) {
            return $directMatches;
        }

        $delimiterTokens = array_values(array_unique(array_filter(
            array_map('trim', preg_split('/[\r\n,;&]+/', $trimmedFull) ?: []),
            fn (string $tok): bool => $tok !== '' && $tok !== '-'
        )));

        foreach ($delimiterTokens as $token) {
            $tokenMatches = $this->filterCandidatesByDoString($token, $candidates, minNormLength: 2);
            if ($tokenMatches->isNotEmpty()) {
                return $tokenMatches;
            }
        }

        if (str_contains($trimmedFull, '/')) {
            $slashCandidates = [];
            $firstSlashPos = strpos($trimmedFull, '/');
            if ($firstSlashPos !== false) {
                $afterFirstPrefix = trim(substr($trimmedFull, $firstSlashPos + 1));
                if ($afterFirstPrefix !== '') {
                    $slashCandidates[] = $afterFirstPrefix;
                }
            }

            foreach (explode('/', $trimmedFull) as $segment) {
                $trimmedSegment = trim($segment);
                if ($trimmedSegment !== '' && strlen($this->normalizeAlnum($trimmedSegment)) >= 3) {
                    $slashCandidates[] = $trimmedSegment;
                }
            }

            foreach (array_unique($slashCandidates) as $slashCandidate) {
                $slashMatches = $this->filterCandidatesByDoString($slashCandidate, $candidates, minNormLength: 3);
                if ($slashMatches->isNotEmpty()) {
                    return $slashMatches;
                }
            }
        }

        return collect();
    }

    /**
     * Memfilter kandidat DO berdasarkan kecocokan persis (case-insensitive) lalu fallback normalisasi alfanumerik.
     *
     * @param  Collection<int, DeliveryOrderReceipt>  $candidates
     * @return Collection<int, DeliveryOrderReceipt>
     */
    protected function filterCandidatesByDoString(string $target, Collection $candidates, int $minNormLength): Collection
    {
        $exactMatches = $candidates->filter(
            fn (DeliveryOrderReceipt $do): bool => strcasecmp(trim((string) $do->delivery_order_no), $target) === 0
        );
        if ($exactMatches->isNotEmpty()) {
            return $exactMatches;
        }

        $normTarget = $this->normalizeAlnum($target);
        if (strlen($normTarget) < $minNormLength) {
            return collect();
        }

        return $candidates->filter(function (DeliveryOrderReceipt $do) use ($normTarget): bool {
            $normDb = $this->normalizeAlnum((string) $do->delivery_order_no);

            return $normDb !== '' && $normDb === $normTarget;
        });
    }

    /**
     * @param  Collection<int, DeliveryOrderReceipt>  $matches
     * @param  array<int, string>  $claimedDoMap
     */
    protected function selectPreferredMatchingDo(Collection $matches, array $claimedDoMap, ?string $noInvoice): ?DeliveryOrderReceipt
    {
        if ($matches->count() === 1) {
            return $matches->first();
        }

        if ($noInvoice !== null) {
            $sameInvoice = $matches->first(
                fn (DeliveryOrderReceipt $do): bool => $do->invoicingReceipts->contains(
                    fn (InvoicingReceipt $ir): bool => filled($ir->no_invoice) && strcasecmp($ir->no_invoice, $noInvoice) === 0
                )
            );
            if ($sameInvoice !== null) {
                return $sameInvoice;
            }
        }

        $unclaimed = $matches->first(fn (DeliveryOrderReceipt $do): bool => ! isset($claimedDoMap[$do->id]));

        return $unclaimed ?? $matches->first();
    }

    /**
     * Fallback penautan langsung via No. DO ketika PO pada file kosong atau PO belum tertaut di DO.
     *
     * @param  array<string, Collection<int, DeliveryOrderReceipt>>  $dosByDo
     */
    protected function matchDirectByDoNumber(string $noPo, string $noDo, array $dosByDo): ?DeliveryOrderReceipt
    {
        $candidates = array_merge([trim($noDo)], array_map('trim', preg_split('/[\r\n,;&]+/', $noDo) ?: []));
        $hasEmptyExcelPo = $noPo === '' || $noPo === '-';

        foreach ($candidates as $candidate) {
            if ($candidate === '' || $candidate === '-') {
                continue;
            }

            $key = strtolower($candidate);
            $matches = $dosByDo[$key] ?? null;
            if (! $matches instanceof Collection || $matches->count() !== 1) {
                continue;
            }

            $matchedDo = $matches->first();
            $doPos = $matchedDo->deliveryOrderReceiptDetails
                ->map(fn ($detail): ?string => $detail->purchaseOrderIssued?->purchase_order_no)
                ->filter()
                ->unique()
                ->values();

            // Jika Excel tidak menyebut PO dan No. DO cukup spesifik (>= 4 alfanumerik), atau DO di DB belum terikat PO lain
            if (
                ($hasEmptyExcelPo && strlen($this->normalizeAlnum($candidate)) >= 4)
                || $doPos->isEmpty()
                || ($noPo !== '-' && $doPos->contains($noPo))
            ) {
                return $matchedDo;
            }
        }

        return null;
    }

    /**
     * Resolusi PIC berdasarkan pemetaan nama PIC spreadsheet ke email user sistem.
     *
     * @param  array<string, int>  $usersByEmail
     * @return array{0: ?int, 1: ?string}
     */
    protected function resolvePicId(?string $picName, array $usersByEmail): array
    {
        if ($picName === null) {
            return [null, null];
        }

        $picKey = strtoupper(preg_replace('/\s+/', ' ', trim($picName)) ?? trim($picName));

        if (! isset(self::PIC_EMAIL_MAP[$picKey])) {
            return [null, "{$picKey} (belum terdaftar di mapping PIC)"];
        }

        $targetEmail = strtolower(self::PIC_EMAIL_MAP[$picKey]);
        if (! isset($usersByEmail[$targetEmail])) {
            return [null, "{$picKey} ({$targetEmail} belum ada di tabel users)"];
        }

        return [$usersByEmail[$targetEmail], null];
    }

    /**
     * @param  array<string, object>  $vendorsByName
     * @param  array<string, object>  $vendorsByNumber
     */
    protected function resolveVendor(?string $vendorName, ?PurchaseOrderIssued $poRecord, array $vendorsByName, array $vendorsByNumber): ?object
    {
        if ($poRecord?->vendor_id && isset($vendorsByNumber[trim((string) $poRecord->vendor_id)])) {
            return $vendorsByNumber[trim((string) $poRecord->vendor_id)];
        }

        $candidates = array_filter([
            $vendorName,
            $poRecord?->vendor_name,
        ]);

        foreach ($candidates as $candidate) {
            $clean = strtolower((string) PurchaseOrderIssued::cleanVendorName($candidate));
            if ($clean !== '' && isset($vendorsByName[$clean])) {
                return $vendorsByName[$clean];
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $row
     * @param  array<string, mixed>  $map
     */
    protected function cellByKey(array $row, array $map, string $key): ?string
    {
        if (! isset($map[$key]) || ! is_int($map[$key])) {
            return null;
        }

        $val = $row[$map[$key]] ?? '';

        return trim((string) $val);
    }

    /**
     * @param  array<int, string>  $row
     * @param  array<int, int>  $vendorIndices
     */
    protected function resolveVendorNameFromRow(array $row, array $vendorIndices): ?string
    {
        foreach ($vendorIndices as $idx) {
            $raw = trim((string) ($row[$idx] ?? ''));
            if ($raw !== '' && $raw !== '-') {
                return PurchaseOrderIssued::cleanVendorName($raw);
            }
        }

        return null;
    }

    protected function cleanPoNumber(?string $raw): ?string
    {
        $clean = $this->nullIfEmpty($raw);
        if ($clean === null) {
            return null;
        }

        if (str_contains($clean, ',')) {
            $clean = trim(explode(',', $clean)[0]);
        }

        return $clean !== '' ? $clean : null;
    }

    protected function normalizeEmailList(?string $raw): ?string
    {
        $clean = $this->nullIfEmpty($raw);
        if ($clean === null) {
            return null;
        }

        $parts = preg_split('/[\s,;]+/', $clean) ?: [];
        $validParts = array_values(array_unique(array_filter(
            array_map('trim', $parts),
            fn (string $part): bool => $part !== '' && $part !== '-'
        )));

        return ! empty($validParts) ? implode(', ', $validParts) : null;
    }

    protected function cleanMaterialType(?string $rawType): string
    {
        return InvoicingReceipt::normalizeMaterialType($rawType);
    }

    protected function parseDate(?string $value): ?string
    {
        $clean = trim((string) $value);
        if ($clean === '' || $clean === '-' || str_starts_with($clean, '=')) {
            return null;
        }

        if (str_contains($clean, "\n") || str_contains($clean, "\r")) {
            $lines = preg_split('/[\r\n]+/', $clean) ?: [];
            $clean = trim((string) ($lines[0] ?? ''));
        }

        $clean = preg_replace('/\/{2,}/', '/', $clean) ?? $clean;

        if (preg_match('/^(\d{1,2}),(\d{1,2}),(\d{2,4})$/', $clean, $commaMatches) === 1) {
            $clean = "{$commaMatches[1]}/{$commaMatches[2]}/{$commaMatches[3]}";
        }

        if (preg_match('/^\d{5}(\.\d+)?$/', $clean) === 1) {
            $serialDays = (int) $clean;
            if ($serialDays >= 35000 && $serialDays <= 65000) {
                return Carbon::create(1899, 12, 30)->addDays($serialDays)->format('Y-m-d');
            }
        }

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d', 'j/n/Y', 'j-n-Y', 'd/m/y', 'd-m-y', 'j/n/y', 'j-n-y'] as $format) {
            try {
                $dt = Carbon::createFromFormat($format, trim($clean));
                if ($dt->year >= 2015 && $dt->year <= 2099) {
                    return $dt->format('Y-m-d');
                }
            } catch (\Throwable) {
                // Coba format tanggal berikutnya
            }
        }

        $translated = strtr(strtoupper($clean), [
            'JANUARI' => 'January',
            'FEBRUARI' => 'February',
            'PEBRUARI' => 'February',
            'MARET' => 'March',
            'MSRET' => 'March',
            'NARET' => 'March',
            'APRIL' => 'April',
            'MEI' => 'May',
            'JUNI' => 'June',
            'JULI' => 'July',
            'AGUSTUS' => 'August',
            'SEPTEMBER' => 'September',
            'OKTOBER' => 'October',
            'OKTBER' => 'October',
            'NOVEMBER' => 'November',
            'NOPEMBER' => 'November',
            'DESEMBER' => 'December',
        ]);

        if (preg_match('/^\d{1,2}\s+[A-Z]+\s+\d{2,4}$/i', $translated) === 1) {
            foreach (['j F Y', 'j F y', 'j M Y', 'j M y'] as $textFormat) {
                try {
                    $dt = Carbon::createFromFormat($textFormat, trim($translated));
                    if ($dt->year >= 2015 && $dt->year <= 2099) {
                        return $dt->format('Y-m-d');
                    }
                } catch (\Throwable) {
                    // Coba format teks berikutnya
                }
            }
        }

        return null;
    }

    protected function parseNumeric(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '' || $trimmed === '-' || str_starts_with($trimmed, '=')) {
            return null;
        }

        if (str_contains($trimmed, "\n") || str_contains($trimmed, "\r")) {
            $lines = preg_split('/[\r\n]+/', $trimmed) ?: [];
            $sum = 0.0;
            $hasAny = false;

            foreach ($lines as $line) {
                $parsed = $this->parseSingleNumeric($line);
                if ($parsed !== null) {
                    $sum += $parsed;
                    $hasAny = true;
                }
            }

            return $hasAny ? round($sum, 4) : null;
        }

        return $this->parseSingleNumeric($trimmed);
    }

    protected function parseSingleNumeric(string $value): ?float
    {
        $clean = trim(str_ireplace(['rp', 'idr'], '', $value));
        $clean = trim($clean);
        if ($clean === '' || $clean === '-' || str_starts_with($clean, '=')) {
            return null;
        }

        if (preg_match('/^-?\d+(?:\.\d+)?[eE][+-]?\d+$/', $clean) === 1) {
            return (float) $clean;
        }

        $hasComma = str_contains($clean, ',');
        $hasDot = str_contains($clean, '.');

        if ($hasComma && $hasDot) {
            $lastComma = strrpos($clean, ',');
            $lastDot = strrpos($clean, '.');

            if ($lastComma > $lastDot) {
                // Format Indonesia: 1.234.567,89
                $normalized = str_replace('.', '', $clean);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                // Format Internasional: 1,234,567.89
                $normalized = str_replace(',', '', $clean);
            }
        } elseif ($hasComma) {
            if (preg_match('/^-?\d{1,3}(,\d{3})+$/', $clean) === 1 || substr_count($clean, ',') > 1) {
                $normalized = str_replace(',', '', $clean);
            } else {
                $normalized = str_replace(',', '.', $clean);
            }
        } elseif ($hasDot) {
            if (substr_count($clean, '.') > 1) {
                $normalized = str_replace('.', '', $clean);
            } else {
                $normalized = $clean;
            }
        } else {
            $normalized = $clean;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized) ?? '';
        if ($normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    protected function normalizeUoi(?string $value): ?string
    {
        $clean = $this->nullIfEmpty($value);
        if ($clean === null) {
            return null;
        }

        $tokens = preg_split('/[\r\n]+/', $clean) ?: [];
        $unique = array_values(array_unique(array_filter(
            array_map(fn (string $token): string => trim($token), $tokens),
            fn (string $token): bool => $token !== '' && $token !== '-'
        )));

        if (empty($unique)) {
            return null;
        }

        $joined = count($unique) === 1 ? $unique[0] : implode('/', $unique);

        return $this->limitString($joined, 20);
    }

    protected function limitString(?string $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_strlen($value) > $maxLength ? mb_substr($value, 0, $maxLength) : $value;
    }

    protected function clampDecimal(?float $value, float $maxMagnitude): ?float
    {
        if ($value === null || ! is_finite($value)) {
            return null;
        }

        if (abs($value) > $maxMagnitude) {
            return null;
        }

        return $value;
    }

    protected function normalizeAlnum(string $value): string
    {
        $alnum = preg_replace('/[^a-zA-Z0-9]/', '', strtoupper(trim($value))) ?? '';

        return ltrim($alnum, '0') ?: $alnum;
    }

    protected function nullIfEmpty(?string $value): ?string
    {
        $clean = trim((string) $value);

        return ($clean === '' || $clean === '-') ? null : $clean;
    }
}
