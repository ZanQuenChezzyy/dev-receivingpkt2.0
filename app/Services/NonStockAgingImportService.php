<?php

namespace App\Services;

use App\Enums\AgingFollowupStage;
use App\Enums\AgingOperationalStatus;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\NonStockAgingControl;
use App\Models\NonStockAgingLog;
use App\Models\PurchaseOrderIssued;
use App\Models\RequestingUnit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use XMLReader;
use ZipArchive;

class NonStockAgingImportService
{
    public function __construct(
        protected NonStockAgingService $agingService,
    ) {}

    /**
     * Mengimpor data Monitoring / GRS dari file Excel (.xlsx multi-sheet atau single-sheet) maupun CSV (.csv).
     * Menggunakan streaming XMLReader agar hemat memori dan aman untuk file penuh berukuran besar.
     *
     * @param  array{
     *     only_active_statuses?: bool,
     *     dry_run?: bool,
     *     imported_by?: ?int,
     *     sync_web_do_after?: bool
     * }  $options
     * @return array{
     *     mode: string,
     *     sheets_detected: list<string>,
     *     rows_scanned: int,
     *     created: int,
     *     updated: int,
     *     skipped: int,
     *     matched_db_po: int,
     *     matched_db_do: int,
     *     matched_unit: int,
     *     contacts_extracted: int,
     *     missing_grs_date_count: int,
     *     foreign_currency_unconverted_count: int,
     *     status_counts: array<string, int>,
     *     mrp_counts: array<string, int>,
     *     web_sync_stats: ?array{created: int, updated: int, auto_closed: int, mapped: int}
     * }
     */
    public function import(string $filePath, array $options = []): array
    {
        @set_time_limit(300);

        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new RuntimeException('File impor tidak ditemukan atau tidak dapat dibaca.');
        }

        $onlyActiveStatuses = (bool) ($options['only_active_statuses'] ?? false);
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $importedBy = $options['imported_by'] ?? null;
        $syncWebDoAfter = (bool) ($options['sync_web_do_after'] ?? false);

        $header = file_get_contents($filePath, false, null, 0, 4);
        $isXlsx = is_string($header) && str_starts_with($header, "PK\x03\x04");

        $sheetsDetected = [];
        $grsLookup = [];
        $r02Lookup = [];
        $parsedRecords = [];
        $mode = 'csv_monitoring';

        if ($isXlsx) {
            $zip = new ZipArchive;
            if ($zip->open($filePath) !== true) {
                throw new RuntimeException('File Excel (.xlsx) tidak valid atau rusak.');
            }

            try {
                $sharedStrings = $this->readSharedStringsStreaming($zip);
                $sheetMap = $this->resolveWorkbookSheets($zip);
                $sheetsDetected = array_keys($sheetMap);

                $monitoringTarget = $this->findSheetTarget($sheetMap, ['MONITORING']);
                $grsTarget = $this->findSheetTarget($sheetMap, ['GRS', 'MB51', 'GRS 105']);
                $r02Target = $this->findSheetTarget($sheetMap, ['R02', 'PO TERBIT']);

                if ($grsTarget !== null) {
                    $grsLookup = $this->indexGrsSheet($zip, $sharedStrings, $grsTarget);
                }

                if ($monitoringTarget !== null) {
                    $mode = 'workbook_monitoring';
                    $parsedRecords = $this->parseMonitoringXlsxSheet($zip, $sharedStrings, $monitoringTarget);

                    if ($r02Target !== null && ! empty($parsedRecords)) {
                        $neededKeys = array_fill_keys(array_keys($parsedRecords), true);
                        $r02Lookup = $this->indexR02Sheet($zip, $sharedStrings, $r02Target, $neededKeys);
                    }
                } elseif ($grsTarget !== null) {
                    $mode = 'grs_only';
                    $parsedRecords = $this->convertGrsLookupToRecords($grsLookup);
                } else {
                    // Fallback: baca worksheet pertama secara generik (monitoring atau GRS)
                    $firstTarget = reset($sheetMap);
                    if (! is_string($firstTarget) || $firstTarget === '') {
                        throw new RuntimeException('Workbook Excel tidak memiliki worksheet yang dapat dibaca.');
                    }
                    $parsedRecords = $this->parseGenericXlsxSheet($zip, $sharedStrings, $firstTarget, $mode);
                }
            } finally {
                $zip->close();
            }
        } else {
            $parsedRecords = $this->parseCsvFile($filePath, $mode);
        }

        $result = $this->persistRecords(
            $parsedRecords,
            $grsLookup,
            $r02Lookup,
            $onlyActiveStatuses,
            $dryRun,
            $importedBy,
            $mode,
            $sheetsDetected
        );

        if (! $dryRun && $syncWebDoAfter) {
            $result['web_sync_stats'] = $this->agingService->syncFromDeliveryOrderDetails();
        }

        return $result;
    }

    /**
     * Membaca xl/sharedStrings.xml secara streaming tanpa memuat DOM besar ke memori.
     *
     * @return list<string>
     */
    protected function readSharedStringsStreaming(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if (! is_string($xml) || $xml === '') {
            return [];
        }

        $strings = [];
        $reader = new XMLReader;
        $reader->XML($xml);

        $inSi = false;
        $inT = false;
        $current = '';

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT) {
                if ($reader->name === 'si') {
                    $inSi = true;
                    $current = '';
                } elseif ($inSi && $reader->name === 't') {
                    $inT = true;
                }
            } elseif ($reader->nodeType === XMLReader::TEXT || $reader->nodeType === XMLReader::CDATA || $reader->nodeType === XMLReader::SIGNIFICANT_WHITESPACE) {
                if ($inSi && $inT) {
                    $current .= $reader->value;
                }
            } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
                if ($reader->name === 't') {
                    $inT = false;
                } elseif ($reader->name === 'si') {
                    $strings[] = $current;
                    $inSi = false;
                }
            }
        }

        $reader->close();

        return $strings;
    }

    /**
     * Memetakan nama worksheet => path XML internal di dalam arsip XLSX.
     *
     * @return array<string, string>
     */
    protected function resolveWorkbookSheets(ZipArchive $zip): array
    {
        $wbContent = $zip->getFromName('xl/workbook.xml');
        $relsContent = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if (! is_string($wbContent) || ! is_string($relsContent)) {
            return [];
        }

        $wbXml = @simplexml_load_string($wbContent);
        $relsXml = @simplexml_load_string($relsContent);

        if ($wbXml === false || $relsXml === false) {
            return [];
        }

        $rels = [];
        foreach ($relsXml->Relationship as $rel) {
            $rels[(string) $rel['Id']] = (string) $rel['Target'];
        }

        $map = [];
        if (isset($wbXml->sheets->sheet)) {
            foreach ($wbXml->sheets->sheet as $sheet) {
                $name = trim((string) $sheet['name']);
                $rAttrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                $rid = (string) ($rAttrs['id'] ?? '');
                $target = $rels[$rid] ?? '';
                if ($target !== '') {
                    $normalizedTarget = str_starts_with($target, 'xl/') ? $target : 'xl/'.ltrim($target, '/');
                    $map[$name] = $normalizedTarget;
                }
            }
        }

        return $map;
    }

    /**
     * @param  array<string, string>  $sheetMap
     * @param  list<string>  $candidates
     */
    protected function findSheetTarget(array $sheetMap, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            foreach ($sheetMap as $name => $target) {
                if (strcasecmp(trim($name), $candidate) === 0) {
                    return $target;
                }
            }
        }

        return null;
    }

    /**
     * Streaming setiap baris worksheet XLSX ke callback.
     *
     * @param  list<string>  $sharedStrings
     * @param  callable(int, array<string, string>): void  $onRow
     */
    protected function streamWorksheetRows(ZipArchive $zip, array $sharedStrings, string $target, callable $onRow): void
    {
        $content = $zip->getFromName($target);
        if (! is_string($content) || $content === '') {
            return;
        }

        $reader = new XMLReader;
        $reader->XML($content);

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'row') {
                $rowNum = (int) $reader->getAttribute('r');
                $rowXml = $reader->readOuterXML();
                $rowEl = @simplexml_load_string($rowXml);

                if ($rowEl === false) {
                    continue;
                }

                $cells = [];
                foreach ($rowEl->c as $c) {
                    $ref = (string) $c['r'];
                    $col = preg_replace('/\d+/', '', $ref) ?? '';
                    if ($col === '') {
                        continue;
                    }

                    $type = (string) $c['t'];
                    $rawVal = isset($c->v) ? (string) $c->v : '';

                    if ($type === 's' && $rawVal !== '') {
                        $val = $sharedStrings[(int) $rawVal] ?? '';
                    } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                        $val = (string) $c->is->t;
                    } elseif ($type === 'e') {
                        // Abaikan error formula Excel (#REF!, #N/A, #VALUE!)
                        $val = '';
                    } else {
                        $val = $rawVal;
                    }

                    $cells[$col] = trim($val);
                }

                if (array_filter($cells, fn (string $v): bool => $v !== '') !== []) {
                    $onRow($rowNum, $cells);
                }
            }
        }

        $reader->close();
    }

    /**
     * Mengindeks tab GRS (SAP MB51 105) berdasarkan PO-Item.
     *
     * @param  list<string>  $sharedStrings
     * @return array<string, array{
     *     po_by_item: string,
     *     posting_date: ?string,
     *     material_code: ?string,
     *     description: ?string,
     *     location_text: ?string,
     *     mrp_type: ?string,
     *     material_type: ?string,
     *     qty_gr: float,
     *     amount_lc: float
     * }>
     */
    protected function indexGrsSheet(ZipArchive $zip, array $sharedStrings, string $target): array
    {
        $headerMap = [];
        $headerRowNum = null;
        $lookup = [];

        $this->streamWorksheetRows($zip, $sharedStrings, $target, function (int $rowNum, array $cells) use (&$headerMap, &$headerRowNum, &$lookup): void {
            if ($headerRowNum === null) {
                $mapped = $this->mapGrsHeaders($cells);
                if (isset($mapped['po_by_item']) || (isset($mapped['po_number']) && isset($mapped['item_no']))) {
                    $headerRowNum = $rowNum;
                    $headerMap = $mapped;
                }

                return;
            }

            $key = $this->agingService->normalizePoByItem(
                $cells[$headerMap['po_number'] ?? ''] ?? null,
                $cells[$headerMap['item_no'] ?? ''] ?? null,
                $cells[$headerMap['po_by_item'] ?? ''] ?? null,
            );

            if ($key === null) {
                return;
            }

            $postingDate = $this->parseDate($cells[$headerMap['posting_date'] ?? ''] ?? null)
                ?? $this->parseDate($cells[$headerMap['entry_date'] ?? ''] ?? null);
            $qty = $this->parseNumber($cells[$headerMap['qty_gr'] ?? ''] ?? null);
            $amountLc = $this->parseNumber($cells[$headerMap['amount_lc'] ?? ''] ?? null);
            $mrp = strtoupper(trim($cells[$headerMap['mrp_type'] ?? ''] ?? ''));
            $matType = strtoupper(trim($cells[$headerMap['material_type'] ?? ''] ?? ''));
            $matCode = $this->cleanCode($cells[$headerMap['material_code'] ?? ''] ?? null);
            $desc = trim($cells[$headerMap['description'] ?? ''] ?? '');
            $loc = trim($cells[$headerMap['location_text'] ?? ''] ?? '');

            if (! isset($lookup[$key])) {
                $lookup[$key] = [
                    'po_by_item' => $key,
                    'posting_date' => $postingDate,
                    'material_code' => $matCode,
                    'description' => $desc !== '' ? $desc : null,
                    'location_text' => $loc !== '' ? $loc : null,
                    'mrp_type' => $mrp !== '' ? $mrp : null,
                    'material_type' => $matType !== '' ? $matType : null,
                    'qty_gr' => $qty,
                    'amount_lc' => $amountLc,
                ];
            } else {
                $lookup[$key]['qty_gr'] += $qty;
                $lookup[$key]['amount_lc'] += $amountLc;
                if ($postingDate !== null && ($lookup[$key]['posting_date'] === null || $postingDate < $lookup[$key]['posting_date'])) {
                    $lookup[$key]['posting_date'] = $postingDate;
                }
                if ($lookup[$key]['mrp_type'] === null && $mrp !== '') {
                    $lookup[$key]['mrp_type'] = $mrp;
                }
                if ($lookup[$key]['location_text'] === null && $loc !== '') {
                    $lookup[$key]['location_text'] = $loc;
                }
            }
        });

        return $lookup;
    }

    /**
     * Mengindeks tab R02 secara selektif hanya untuk PO-Item yang dibutuhkan.
     *
     * @param  list<string>  $sharedStrings
     * @param  array<string, bool>  $neededKeys
     * @return array<string, array{vendor_name: ?string, uoi: ?string, qty_po: float, amount_lc: float, requisitioner: ?string, description: ?string, material_code: ?string}>
     */
    protected function indexR02Sheet(ZipArchive $zip, array $sharedStrings, string $target, array $neededKeys): array
    {
        $headerMap = [];
        $headerRowNum = null;
        $lookup = [];

        $this->streamWorksheetRows($zip, $sharedStrings, $target, function (int $rowNum, array $cells) use (&$headerMap, &$headerRowNum, &$lookup, $neededKeys): void {
            if ($headerRowNum === null) {
                $mapped = $this->mapR02Headers($cells);
                if (isset($mapped['po_number']) || isset($mapped['po_by_item'])) {
                    $headerRowNum = $rowNum;
                    $headerMap = $mapped;
                }

                return;
            }

            $key = $this->agingService->normalizePoByItem(
                $cells[$headerMap['po_number'] ?? ''] ?? null,
                $cells[$headerMap['item_no'] ?? ''] ?? null,
                $cells[$headerMap['po_by_item'] ?? ''] ?? null,
            );

            if ($key === null || ! isset($neededKeys[$key]) || isset($lookup[$key])) {
                return;
            }

            $vendorClean = PurchaseOrderIssued::cleanVendorName($cells[$headerMap['vendor_name'] ?? ''] ?? null);

            $lookup[$key] = [
                'vendor_name' => filled($vendorClean) ? $vendorClean : null,
                'uoi' => ($uoi = trim($cells[$headerMap['uoi'] ?? ''] ?? '')) !== '' ? $uoi : null,
                'qty_po' => $this->parseNumber($cells[$headerMap['qty_po'] ?? ''] ?? null),
                'amount_lc' => $this->parseNumber($cells[$headerMap['amount_lc'] ?? ''] ?? null),
                'requisitioner' => ($req = trim($cells[$headerMap['requisitioner'] ?? ''] ?? '')) !== '' ? $req : null,
                'description' => ($desc = trim($cells[$headerMap['description'] ?? ''] ?? '')) !== '' ? $desc : null,
                'material_code' => $this->cleanCode($cells[$headerMap['material_code'] ?? ''] ?? null),
            ];
        });

        return $lookup;
    }

    /**
     * Mem-parsing tab MONITORING menjadi daftar record terstruktur (diindeks berdasarkan PO-Item).
     *
     * @param  list<string>  $sharedStrings
     * @return array<string, array<string, mixed>>
     */
    protected function parseMonitoringXlsxSheet(ZipArchive $zip, array $sharedStrings, string $target): array
    {
        $headerMap = [];
        $headerRowNum = null;
        $records = [];

        $this->streamWorksheetRows($zip, $sharedStrings, $target, function (int $rowNum, array $cells) use (&$headerMap, &$headerRowNum, &$records): void {
            if ($headerRowNum === null) {
                $mapped = $this->mapMonitoringHeaders($cells);
                if (isset($mapped['po_by_item']) || isset($mapped['po_number'])) {
                    $headerRowNum = $rowNum;
                    $headerMap = $mapped;
                }

                return;
            }

            $record = $this->buildMonitoringRecord($cells, $headerMap);
            if ($record !== null) {
                $records[$record['po_by_item']] = $record;
            }
        });

        return $records;
    }

    /**
     * @param  list<string>  $sharedStrings
     * @return array<string, array<string, mixed>>
     */
    protected function parseGenericXlsxSheet(ZipArchive $zip, array $sharedStrings, string $target, string &$mode): array
    {
        $records = $this->parseMonitoringXlsxSheet($zip, $sharedStrings, $target);
        if (! empty($records)) {
            $mode = 'workbook_monitoring';

            return $records;
        }

        $grsLookup = $this->indexGrsSheet($zip, $sharedStrings, $target);
        if (! empty($grsLookup)) {
            $mode = 'grs_only';

            return $this->convertGrsLookupToRecords($grsLookup);
        }

        throw new RuntimeException('Header kolom PO by Item / Purchase Order tidak ditemukan pada worksheet.');
    }

    /**
     * Mem-parsing file CSV (MONITORING atau GRS/MB51) secara streaming dengan auto-deteksi delimiter.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function parseCsvFile(string $filePath, string &$mode): array
    {
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Gagal membuka file CSV.');
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                return [];
            }

            $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine) ?? $firstLine;
            $delimiter = ',';
            $counts = [
                ';' => substr_count($firstLine, ';'),
                ',' => substr_count($firstLine, ','),
                "\t" => substr_count($firstLine, "\t"),
            ];
            arsort($counts);
            $topDelim = array_key_first($counts);
            if (is_string($topDelim) && $counts[$topDelim] > 0) {
                $delimiter = $topDelim;
            }

            rewind($handle);

            $headerMap = [];
            $isGrsFormat = false;
            $records = [];

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $cells = [];
                foreach ($row as $idx => $val) {
                    $clean = trim((string) $val);
                    if ($idx === 0) {
                        $clean = preg_replace('/^\xEF\xBB\xBF/', '', $clean) ?? $clean;
                    }
                    $cells[(string) $idx] = $clean;
                }

                if (empty($headerMap)) {
                    $monMap = $this->mapMonitoringHeaders($cells);
                    $grsMap = $this->mapGrsHeaders($cells);

                    if (isset($grsMap['posting_date']) && ! isset($monMap['status_pengambilan'])) {
                        $headerMap = $grsMap;
                        $isGrsFormat = true;
                        $mode = 'grs_only';
                    } elseif (isset($monMap['po_by_item']) || isset($monMap['po_number'])) {
                        $headerMap = $monMap;
                        $mode = 'csv_monitoring';
                    }

                    continue;
                }

                if ($isGrsFormat) {
                    $key = $this->agingService->normalizePoByItem(
                        $cells[$headerMap['po_number'] ?? ''] ?? null,
                        $cells[$headerMap['item_no'] ?? ''] ?? null,
                        $cells[$headerMap['po_by_item'] ?? ''] ?? null,
                    );
                    if ($key === null) {
                        continue;
                    }
                    $records[$key] = [
                        'po_by_item' => $key,
                        'data_source' => 'import_mb51',
                        'material_code' => $this->cleanCode($cells[$headerMap['material_code'] ?? ''] ?? null),
                        'description' => ($d = trim($cells[$headerMap['description'] ?? ''] ?? '')) !== '' ? $d : null,
                        'location_text' => ($l = trim($cells[$headerMap['location_text'] ?? ''] ?? '')) !== '' ? $l : null,
                        'mrp_type' => ($m = strtoupper(trim($cells[$headerMap['mrp_type'] ?? ''] ?? ''))) !== '' ? $m : null,
                        'material_type' => ($mt = strtoupper(trim($cells[$headerMap['material_type'] ?? ''] ?? ''))) !== '' ? $mt : null,
                        'grs_posting_date' => $this->parseDate($cells[$headerMap['posting_date'] ?? ''] ?? null)
                            ?? $this->parseDate($cells[$headerMap['entry_date'] ?? ''] ?? null),
                        'qty_gr' => $this->parseNumber($cells[$headerMap['qty_gr'] ?? ''] ?? null),
                        'qty_issued' => 0.0,
                        'currency' => 'IDR',
                        'amount_lc_direct' => $this->parseNumber($cells[$headerMap['amount_lc'] ?? ''] ?? null),
                        'raw_total_value' => 0.0,
                        'raw_avail_value' => 0.0,
                        'requisitioner' => null,
                        'contact_person' => null,
                        'vendor_name' => null,
                        'uoi' => null,
                        'physical_checked' => true,
                        'status_raw' => 'BELUM DIAMBIL',
                        'updated_date' => null,
                        'catatan' => null,
                    ];
                } else {
                    $record = $this->buildMonitoringRecord($cells, $headerMap);
                    if ($record !== null) {
                        $records[$record['po_by_item']] = $record;
                    }
                }
            }

            if (empty($headerMap)) {
                throw new RuntimeException('Header kolom PO by Item / Purchase Order tidak ditemukan pada file CSV.');
            }

            return $records;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $grsLookup
     * @return array<string, array<string, mixed>>
     */
    protected function convertGrsLookupToRecords(array $grsLookup): array
    {
        $records = [];

        foreach ($grsLookup as $key => $grs) {
            $records[$key] = [
                'po_by_item' => $key,
                'data_source' => 'import_mb51',
                'material_code' => $grs['material_code'],
                'description' => $grs['description'],
                'location_text' => $grs['location_text'],
                'mrp_type' => $grs['mrp_type'],
                'material_type' => $grs['material_type'],
                'grs_posting_date' => $grs['posting_date'],
                'qty_gr' => (float) $grs['qty_gr'],
                'qty_issued' => 0.0,
                'currency' => 'IDR',
                'amount_lc_direct' => (float) $grs['amount_lc'],
                'raw_total_value' => 0.0,
                'raw_avail_value' => 0.0,
                'requisitioner' => null,
                'contact_person' => null,
                'vendor_name' => null,
                'uoi' => null,
                'physical_checked' => true,
                'status_raw' => 'BELUM DIAMBIL',
                'updated_date' => null,
                'catatan' => null,
            ];
        }

        return $records;
    }

    /**
     * @param  array<string, string>  $cells
     * @param  array<string, string>  $headerMap
     * @return array<string, mixed>|null
     */
    protected function buildMonitoringRecord(array $cells, array $headerMap): ?array
    {
        $key = $this->agingService->normalizePoByItem(
            $cells[$headerMap['po_number'] ?? ''] ?? null,
            $cells[$headerMap['item_no'] ?? ''] ?? null,
            $cells[$headerMap['po_by_item'] ?? ''] ?? null,
        );

        if ($key === null) {
            return null;
        }

        $cekRaw = strtoupper(trim($cells[$headerMap['physical_checked'] ?? ''] ?? ''));
        $physicalChecked = in_array($cekRaw, ['1', 'TRUE', 'YA', 'YES', 'OK'], true);

        $vendorClean = PurchaseOrderIssued::cleanVendorName($cells[$headerMap['vendor_name'] ?? ''] ?? null);

        return [
            'po_by_item' => $key,
            'data_source' => 'import_monitoring',
            'material_code' => $this->cleanCode($cells[$headerMap['material_code'] ?? ''] ?? null),
            'description' => ($d = trim($cells[$headerMap['description'] ?? ''] ?? '')) !== '' ? $d : null,
            'location_text' => ($l = trim($cells[$headerMap['location_text'] ?? ''] ?? '')) !== '' ? $l : null,
            'requisitioner' => ($r = trim($cells[$headerMap['requisitioner'] ?? ''] ?? '')) !== '' ? $r : null,
            'contact_person' => ($cp = trim($cells[$headerMap['contact_person'] ?? ''] ?? '')) !== '' ? $cp : null,
            'vendor_name' => filled($vendorClean) ? $vendorClean : null,
            'mrp_type' => ($mrp = strtoupper(trim($cells[$headerMap['mrp_type'] ?? ''] ?? ''))) !== '' ? $mrp : null,
            'material_type' => ($mt = strtoupper(trim($cells[$headerMap['material_type'] ?? ''] ?? ''))) !== '' ? $mt : null,
            'uoi' => ($u = trim($cells[$headerMap['uoi'] ?? ''] ?? '')) !== '' ? $u : null,
            'grs_posting_date' => $this->parseDate($cells[$headerMap['posting_date'] ?? ''] ?? null),
            'qty_gr' => $this->parseNumber($cells[$headerMap['qty_gr'] ?? ''] ?? null),
            'qty_issued' => $this->parseNumber($cells[$headerMap['qty_issued'] ?? ''] ?? null),
            'currency' => strtoupper(trim($cells[$headerMap['currency'] ?? ''] ?? '')),
            'amount_lc_direct' => $this->parseNumber($cells[$headerMap['amount_lc'] ?? ''] ?? null),
            'raw_total_value' => $this->parseNumber($cells[$headerMap['total_value'] ?? ''] ?? null),
            'raw_avail_value' => $this->parseNumber($cells[$headerMap['avail_value'] ?? ''] ?? null),
            'physical_checked' => $physicalChecked,
            'status_raw' => strtoupper(trim($cells[$headerMap['status_pengambilan'] ?? ''] ?? '')),
            'updated_date' => $this->parseDate($cells[$headerMap['updated_date'] ?? ''] ?? null),
            'catatan' => ($cat = trim($cells[$headerMap['catatan'] ?? ''] ?? '')) !== '' ? $cat : null,
        ];
    }

    /**
     * Menyimpan hasil parsing ke nonstock_aging_controls secara bulk-preloaded dan idempotent.
     *
     * @param  array<string, array<string, mixed>>  $parsedRecords
     * @param  array<string, array<string, mixed>>  $grsLookup
     * @param  array<string, array<string, mixed>>  $r02Lookup
     * @param  list<string>  $sheetsDetected
     * @return array<string, mixed>
     */
    protected function persistRecords(
        array $parsedRecords,
        array $grsLookup,
        array $r02Lookup,
        bool $onlyActiveStatuses,
        bool $dryRun,
        ?int $importedBy,
        string $mode,
        array $sheetsDetected,
    ): array {
        $summary = [
            'mode' => $mode,
            'sheets_detected' => $sheetsDetected,
            'rows_scanned' => count($parsedRecords),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'matched_db_po' => 0,
            'matched_db_do' => 0,
            'matched_unit' => 0,
            'contacts_extracted' => 0,
            'missing_grs_date_count' => 0,
            'foreign_currency_unconverted_count' => 0,
            'status_counts' => [],
            'mrp_counts' => [],
            'web_sync_stats' => null,
        ];

        if (empty($parsedRecords)) {
            return $summary;
        }

        $allKeys = array_keys($parsedRecords);
        $poNumbers = array_values(array_unique(array_map(fn (string $k): string => explode('-', $k, 2)[0], $allKeys)));

        // Bulk preload PurchaseOrderIssued (cegah N+1)
        $dbPoMap = [];
        foreach (array_chunk($poNumbers, 500) as $poChunk) {
            $pois = PurchaseOrderIssued::query()
                ->whereIn('purchase_order_no', $poChunk)
                ->get();

            foreach ($pois as $poi) {
                $key = $this->agingService->normalizePoByItem($poi->purchase_order_no, $poi->item_no, $poi->purchase_order_and_item);
                if ($key !== null) {
                    $dbPoMap[$key] = $poi;
                }
            }
        }

        // Bulk preload DeliveryOrderReceiptDetail berdasarkan purchase_order_issued_id
        $dbDoDetailByPoId = [];
        $poiIds = array_values(array_map(fn (PurchaseOrderIssued $poi): int => $poi->id, $dbPoMap));
        foreach (array_chunk($poiIds, 500) as $idChunk) {
            $details = DeliveryOrderReceiptDetail::query()
                ->with(['deliveryOrderReceipt.grsRdtvItems.grsRdtv', 'locationReceiving'])
                ->whereIn('purchase_order_issued_id', $idChunk)
                ->get();

            foreach ($details as $detail) {
                if ($detail->purchase_order_issued_id !== null && ! isset($dbDoDetailByPoId[$detail->purchase_order_issued_id])) {
                    $dbDoDetailByPoId[$detail->purchase_order_issued_id] = $detail;
                }
            }
        }

        // Bulk preload existing NonStockAgingControl
        $existingByKey = [];
        foreach (array_chunk($allKeys, 500) as $keyChunk) {
            $existing = NonStockAgingControl::query()
                ->whereIn('po_by_item', $keyChunk)
                ->get();

            foreach ($existing as $ctrl) {
                if (filled($ctrl->po_by_item)) {
                    $existingByKey[(string) $ctrl->po_by_item] = $ctrl;
                }
            }
        }

        $units = RequestingUnit::query()->where('is_active', true)->get();

        $process = function () use (
            $parsedRecords,
            $grsLookup,
            $r02Lookup,
            $dbPoMap,
            $dbDoDetailByPoId,
            $existingByKey,
            $units,
            $onlyActiveStatuses,
            $dryRun,
            $importedBy,
            &$summary
        ): void {
            foreach ($parsedRecords as $key => $row) {
                $status = $this->mapOperationalStatus((string) ($row['status_raw'] ?? ''), (bool) ($row['physical_checked'] ?? false));

                if ($onlyActiveStatuses && ! $status->isActiveAtReceiving()) {
                    $summary['skipped']++;

                    continue;
                }

                $grs = $grsLookup[$key] ?? null;
                $r02 = $r02Lookup[$key] ?? null;
                $dbPo = $dbPoMap[$key] ?? null;
                $dbDoDetail = $dbPo !== null ? ($dbDoDetailByPoId[$dbPo->id] ?? null) : null;

                if ($dbPo !== null) {
                    $summary['matched_db_po']++;
                }
                if ($dbDoDetail !== null) {
                    $summary['matched_db_do']++;
                }

                // Tentukan MRP Type (prioritas: DO Detail -> DB PO -> GRS -> MONITORING -> default NONSTOCK)
                $mrpType = strtoupper((string) (
                    $dbDoDetail?->mrp_type
                    ?? $dbPo?->mrp_type
                    ?? $grs['mrp_type']
                    ?? $row['mrp_type']
                    ?? 'NONSTOCK'
                ));
                if ($mrpType === '') {
                    $mrpType = 'NONSTOCK';
                }

                // Tentukan Tanggal GRS (T0)
                $grsDate = $this->resolveGrsDateFromSources($dbDoDetail, $grs['posting_date'] ?? null, $row['grs_posting_date'] ?? null);
                if ($grsDate === null) {
                    $summary['missing_grs_date_count']++;
                }

                // Tentukan Qty GR & Nilai IDR (Local Currency)
                $qtyGr = max(
                    (float) ($row['qty_gr'] ?? 0),
                    (float) ($grs['qty_gr'] ?? 0),
                    (float) ($dbDoDetail?->quantity ?? 0),
                );
                if ($qtyGr <= 0 && $dbPo !== null && (float) $dbPo->qty_po > 0) {
                    $qtyGr = (float) $dbPo->qty_po;
                }

                $valueLc = $this->resolveIdrLocalValue(
                    $dbDoDetail,
                    $dbPo,
                    $grs,
                    $r02,
                    $row,
                    $qtyGr,
                    $summary['foreign_currency_unconverted_count']
                );

                // Requisitioner & Pemetaan Unit Peminta
                $requisitionerRaw = $row['requisitioner']
                    ?? $dbDoDetail?->requisitioner
                    ?? $dbPo?->requisitioner
                    ?? ($r02['requisitioner'] ?? null);

                $matchedUnit = $this->agingService->matchRequestingUnit($requisitionerRaw, $units);
                if ($matchedUnit !== null) {
                    $summary['matched_unit']++;
                }

                // Ekstraksi Contact Person (Nama + No HP)
                $parsedContact = $this->agingService->extractNameAndPhone($row['contact_person'] ?? null);
                if ($parsedContact['name'] !== null || $parsedContact['phone'] !== null) {
                    $summary['contacts_extracted']++;
                }

                $locationText = $row['location_text']
                    ?? ($grs['location_text'] ?? null)
                    ?? $dbDoDetail?->locationReceiving?->name;

                $summary['status_counts'][$status->value] = ($summary['status_counts'][$status->value] ?? 0) + 1;
                $summary['mrp_counts'][$mrpType] = ($summary['mrp_counts'][$mrpType] ?? 0) + 1;

                /** @var NonStockAgingControl|null $control */
                $control = $existingByKey[$key] ?? null;
                if ($control === null && $dbDoDetail !== null) {
                    $control = NonStockAgingControl::query()
                        ->where('delivery_order_receipt_detail_id', $dbDoDetail->id)
                        ->first();
                }

                $catatan = $row['catatan'] ?? null;
                $inferredStage = $this->inferFollowupStage($status, $catatan);
                $movedLocation = $status === AgingOperationalStatus::Dipindah ? ($catatan ?? 'Dipindah dari area Receiving') : null;

                if ($control === null) {
                    $summary['created']++;

                    if ($dryRun) {
                        continue;
                    }

                    $created = NonStockAgingControl::create([
                        'delivery_order_receipt_detail_id' => $dbDoDetail?->id,
                        'purchase_order_issued_id' => $dbPo?->id,
                        'po_by_item' => $key,
                        'data_source' => $dbDoDetail !== null ? 'web_do' : (string) ($row['data_source'] ?? 'import_monitoring'),
                        'mrp_type_snapshot' => $mrpType,
                        'material_type_snapshot' => $dbDoDetail?->material_type ?? $dbPo?->material_type ?? ($grs['material_type'] ?? null) ?? ($row['material_type'] ?? null),
                        'material_code_snapshot' => $dbDoDetail?->material_code ?? $dbPo?->material_code ?? ($grs['material_code'] ?? null) ?? ($row['material_code'] ?? null) ?? ($r02['material_code'] ?? null),
                        'description_snapshot' => $dbDoDetail?->description ?? $dbPo?->description ?? ($grs['description'] ?? null) ?? ($row['description'] ?? null) ?? ($r02['description'] ?? null),
                        'vendor_name_snapshot' => $dbPo?->vendor_name ?? ($row['vendor_name'] ?? null) ?? ($r02['vendor_name'] ?? null),
                        'uoi_snapshot' => $dbDoDetail?->uoi ?? $dbPo?->uoi ?? ($row['uoi'] ?? null) ?? ($r02['uoi'] ?? null),
                        'requisitioner_raw' => $requisitionerRaw,
                        'grs_posting_date' => $grsDate,
                        'initial_qty_gr' => $qtyGr > 0 ? $qtyGr : null,
                        'manual_qty_issued' => (float) ($row['qty_issued'] ?? 0),
                        'initial_value_lc' => $valueLc > 0 ? $valueLc : null,
                        'location_text' => $locationText,
                        'requesting_unit_id' => $matchedUnit?->id,
                        'pic_name_override' => $parsedContact['name'],
                        'pic_phone_override' => $parsedContact['phone'],
                        'operational_status' => $status,
                        'followup_stage' => $inferredStage,
                        'physical_checked' => $status === AgingOperationalStatus::OnCheck ? false : (bool) ($row['physical_checked'] ?? false),
                        'warehouse_validated_at' => ($row['physical_checked'] ?? false) ? ($row['updated_date'] ?? now()) : null,
                        'moved_to_location' => $movedLocation,
                        'moved_at' => $status === AgingOperationalStatus::Dipindah ? ($row['updated_date'] ?? now()) : null,
                        'last_followup_note' => $catatan,
                    ]);

                    NonStockAgingLog::create([
                        'nonstock_aging_control_id' => $created->id,
                        'action_type' => 'import_baseline',
                        'aging_days_snapshot' => $created->resolvedAgingDays(),
                        'aging_zone_snapshot' => $created->resolvedAgingZone()->value,
                        'outstanding_value_snapshot' => $created->resolvedOutstandingValue(),
                        'channel' => 'Sistem',
                        'recipient_level' => 'Internal',
                        'notes' => 'Impor data awal dari spreadsheet monitoring.'.($catatan !== null ? ' Catatan: '.$catatan : ''),
                        'created_by' => $importedBy,
                    ]);

                    continue;
                }

                $summary['updated']++;

                if ($dryRun) {
                    continue;
                }

                // Upsert aman: lengkapi data yang kosong/terbaru tanpa menimpa kontrol manual web (Scheduled Hold / Eskalasi AVP & VP)
                if ($control->purchase_order_issued_id === null && $dbPo !== null) {
                    $control->purchase_order_issued_id = $dbPo->id;
                }
                if ($control->delivery_order_receipt_detail_id === null && $dbDoDetail !== null) {
                    $control->delivery_order_receipt_detail_id = $dbDoDetail->id;
                }
                if (blank($control->mrp_type_snapshot) || $control->mrp_type_snapshot === 'NONSTOCK') {
                    $control->mrp_type_snapshot = $mrpType;
                }
                if (blank($control->description_snapshot)) {
                    $control->description_snapshot = $dbDoDetail?->description ?? $dbPo?->description ?? ($grs['description'] ?? null) ?? ($row['description'] ?? null);
                }
                if (blank($control->material_code_snapshot)) {
                    $control->material_code_snapshot = $dbDoDetail?->material_code ?? $dbPo?->material_code ?? ($grs['material_code'] ?? null) ?? ($row['material_code'] ?? null);
                }
                if (blank($control->vendor_name_snapshot)) {
                    $control->vendor_name_snapshot = $dbPo?->vendor_name ?? ($row['vendor_name'] ?? null) ?? ($r02['vendor_name'] ?? null);
                }
                if (blank($control->requisitioner_raw) && filled($requisitionerRaw)) {
                    $control->requisitioner_raw = $requisitionerRaw;
                }
                if ($control->grs_posting_date === null && $grsDate !== null) {
                    $control->grs_posting_date = $grsDate;
                }
                if (($control->initial_qty_gr === null || $control->initial_qty_gr <= 0) && $qtyGr > 0) {
                    $control->initial_qty_gr = $qtyGr;
                }
                if ((float) ($row['qty_issued'] ?? 0) > (float) $control->manual_qty_issued) {
                    $control->manual_qty_issued = (float) $row['qty_issued'];
                }
                if (($control->initial_value_lc === null || $control->initial_value_lc <= 0) && $valueLc > 0) {
                    $control->initial_value_lc = $valueLc;
                }
                if (blank($control->location_text) && filled($locationText)) {
                    $control->location_text = $locationText;
                }
                if ($control->requesting_unit_id === null && $matchedUnit !== null) {
                    $control->requesting_unit_id = $matchedUnit->id;
                }
                if (blank($control->pic_name_override) && filled($parsedContact['name'])) {
                    $control->pic_name_override = $parsedContact['name'];
                }
                if (blank($control->pic_phone_override) && filled($parsedContact['phone'])) {
                    $control->pic_phone_override = $parsedContact['phone'];
                }
                if ($control->operational_status !== AgingOperationalStatus::ScheduledHold) {
                    $control->operational_status = $status;
                }
                if (! $control->physical_checked && (bool) ($row['physical_checked'] ?? false)) {
                    $control->physical_checked = true;
                    $control->warehouse_validated_at ??= ($row['updated_date'] ?? now());
                }
                if ($status === AgingOperationalStatus::Dipindah && blank($control->moved_to_location)) {
                    $control->moved_to_location = $movedLocation;
                    $control->moved_at ??= ($row['updated_date'] ?? now());
                }
                if (blank($control->last_followup_note) && filled($catatan)) {
                    $control->last_followup_note = $catatan;
                }
                if ($control->followup_stage === AgingFollowupStage::BelumDitindaklanjuti && $inferredStage !== AgingFollowupStage::BelumDitindaklanjuti) {
                    $control->followup_stage = $inferredStage;
                }

                $control->save();
            }
        };

        if ($dryRun) {
            $process();
        } else {
            DB::transaction($process);
        }

        return $summary;
    }

    protected function resolveGrsDateFromSources(?DeliveryOrderReceiptDetail $dbDoDetail, ?string $grsSheetDate, ?string $monitoringDate): ?string
    {
        if ($dbDoDetail?->deliveryOrderReceipt !== null) {
            $webGrsDate = $dbDoDetail->deliveryOrderReceipt->grsRdtvItems
                ->filter(fn ($item): bool => strtoupper((string) $item->grsRdtv?->category) === 'GRS' && $item->grsRdtv?->transaction_date !== null)
                ->map(fn ($item): Carbon => $item->grsRdtv->transaction_date)
                ->sort()
                ->first();

            if ($webGrsDate instanceof Carbon) {
                return $webGrsDate->toDateString();
            }
        }

        if ($grsSheetDate !== null) {
            return $grsSheetDate;
        }

        if ($monitoringDate !== null) {
            return $monitoringDate;
        }

        if ($dbDoDetail?->deliveryOrderReceipt?->physical_received_date instanceof Carbon) {
            return $dbDoDetail->deliveryOrderReceipt->physical_received_date->toDateString();
        }

        if ($dbDoDetail?->deliveryOrderReceipt?->received_date instanceof Carbon) {
            return $dbDoDetail->deliveryOrderReceipt->received_date->toDateString();
        }

        return null;
    }

    /**
     * Resolusi nilai Rupiah (IDR / Local Currency) dengan pencegahan campur mata uang asing (USD/EUR/JPY).
     *
     * @param  array<string, mixed>|null  $grs
     * @param  array<string, mixed>|null  $r02
     * @param  array<string, mixed>  $row
     */
    protected function resolveIdrLocalValue(
        ?DeliveryOrderReceiptDetail $dbDoDetail,
        ?PurchaseOrderIssued $dbPo,
        ?array $grs,
        ?array $r02,
        array $row,
        float $qtyGr,
        int &$foreignCurrencyUnconvertedCount,
    ): float {
        if ($dbDoDetail !== null && (float) $dbDoDetail->total_amount_snapshot > 0) {
            return round((float) $dbDoDetail->total_amount_snapshot, 2);
        }

        if ($dbPo !== null && (float) $dbPo->total_amount_in_lc > 0) {
            $unitLc = (float) $dbPo->qty_po > 0
                ? ((float) $dbPo->total_amount_in_lc / (float) $dbPo->qty_po)
                : (float) $dbPo->total_amount_in_lc;
            $effectiveQty = $qtyGr > 0 ? $qtyGr : max(1.0, (float) $dbPo->qty_po);

            return round($unitLc * $effectiveQty, 2);
        }

        if ($grs !== null && (float) ($grs['amount_lc'] ?? 0) > 0) {
            return round((float) $grs['amount_lc'], 2);
        }

        if ($r02 !== null && (float) ($r02['amount_lc'] ?? 0) > 0) {
            $r02QtyPo = (float) ($r02['qty_po'] ?? 0);
            if ($r02QtyPo > 0 && $qtyGr > 0) {
                return round(((float) $r02['amount_lc'] / $r02QtyPo) * $qtyGr, 2);
            }

            return round((float) $r02['amount_lc'], 2);
        }

        if ((float) ($row['amount_lc_direct'] ?? 0) > 0) {
            return round((float) $row['amount_lc_direct'], 2);
        }

        $currency = strtoupper(trim((string) ($row['currency'] ?? '')));
        $rawVal = (float) ($row['raw_total_value'] ?? 0);
        if ($rawVal <= 0) {
            $rawVal = (float) ($row['raw_avail_value'] ?? 0);
        }

        if ($rawVal > 0) {
            if ($currency === '' || $currency === 'IDR' || $currency === 'RP') {
                return round($rawVal, 2);
            }

            // Mata uang asing tanpa konversi Local Currency tidak boleh dijumlahkan mentah sebagai Rupiah
            $foreignCurrencyUnconvertedCount++;
        }

        return 0.0;
    }

    protected function mapOperationalStatus(string $statusRaw, bool $physicalChecked): AgingOperationalStatus
    {
        $normalized = strtoupper(trim($statusRaw));

        return match (true) {
            str_contains($normalized, 'ON CHECK') || str_contains($normalized, 'ONCHECK') => AgingOperationalStatus::OnCheck,
            str_contains($normalized, 'DIPINDAH') || str_contains($normalized, 'PINDAH') => AgingOperationalStatus::Dipindah,
            str_contains($normalized, 'BELUM') => AgingOperationalStatus::BelumDiambil,
            str_contains($normalized, 'HOLD') => AgingOperationalStatus::ScheduledHold,
            str_contains($normalized, 'DIAMBIL') || str_contains($normalized, 'SELESAI') => AgingOperationalStatus::SelesaiDiambil,
            ! $physicalChecked => AgingOperationalStatus::OnCheck,
            default => AgingOperationalStatus::BelumDiambil,
        };
    }

    protected function inferFollowupStage(AgingOperationalStatus $status, ?string $catatan): AgingFollowupStage
    {
        if ($status === AgingOperationalStatus::SelesaiDiambil || $status === AgingOperationalStatus::Dipindah) {
            return AgingFollowupStage::Selesai;
        }

        if ($status === AgingOperationalStatus::ScheduledHold) {
            return AgingFollowupStage::TerkonfirmasiJadwalAmbil;
        }

        $note = strtoupper((string) $catatan);
        if (str_contains($note, 'SUDAH DIHUBUNGI') || str_contains($note, 'DIHUBUNGI')) {
            return AgingFollowupStage::ReminderTahap1;
        }

        return AgingFollowupStage::BelumDitindaklanjuti;
    }

    /**
     * @param  array<string, string>  $cells
     * @return array<string, string>
     */
    protected function mapMonitoringHeaders(array $cells): array
    {
        $map = [];

        foreach ($cells as $col => $rawHeader) {
            $norm = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $rawHeader) ?? '');

            if (in_array($norm, ['pobyitem', 'poitem', 'purchaseorderanditem'], true)) {
                $map['po_by_item'] = $col;
            } elseif (in_array($norm, ['purchaseorder', 'nomorpo', 'pono', 'purchaseorderno'], true)) {
                $map['po_number'] = $col;
            } elseif (in_array($norm, ['item', 'itemno', 'noitem'], true)) {
                $map['item_no'] = $col;
            } elseif (in_array($norm, ['material', 'materialcode', 'kodematerial', 'materialno'], true)) {
                $map['material_code'] = $col;
            } elseif (in_array($norm, ['materialdescription', 'deskripsi', 'description'], true)) {
                $map['description'] = $col;
            } elseif (in_array($norm, ['keterangan', 'lokasi', 'location', 'storage'], true)) {
                $map['location_text'] = $col;
            } elseif (in_array($norm, ['requisitioner', 'requistioner', 'unitpeminta'], true)) {
                $map['requisitioner'] = $col;
            } elseif (in_array($norm, ['contactperson', 'pic', 'kontakpic'], true)) {
                $map['contact_person'] = $col;
            } elseif (in_array($norm, ['vendorname', 'vendor', 'namavendor'], true)) {
                $map['vendor_name'] = $col;
            } elseif (in_array($norm, ['mrp', 'mrptype'], true)) {
                $map['mrp_type'] = $col;
            } elseif (in_array($norm, ['materialtype', 'mattype'], true)) {
                $map['material_type'] = $col;
            } elseif (in_array($norm, ['totalqtygr', 'qtygr', 'qtyditerima'], true)) {
                $map['qty_gr'] = $col;
            } elseif (in_array($norm, ['satuanpo', 'uoi', 'uom', 'satuan'], true)) {
                $map['uoi'] = $col;
            } elseif (in_array($norm, ['currencypo', 'currency', 'matauang'], true)) {
                $map['currency'] = $col;
            } elseif (in_array($norm, ['totalvalue'], true)) {
                $map['total_value'] = $col;
            } elseif (in_array($norm, ['amountinlc', 'amountinlcsap', 'totalamountinlocalcurr', 'totalamountinlc', 'convertvalue'], true)) {
                $map['amount_lc'] = $col;
            } elseif (in_array($norm, ['totalqtyissued', 'qtyissued', 'issued'], true)) {
                $map['qty_issued'] = $col;
            } elseif (in_array($norm, ['avalaiblevalue', 'availablevalue', 'nilaioutstanding'], true)) {
                $map['avail_value'] = $col;
            } elseif (in_array($norm, ['cekfisik', 'physicalcheck'], true)) {
                $map['physical_checked'] = $col;
            } elseif (in_array($norm, ['statuspengambilan', 'status'], true)) {
                $map['status_pengambilan'] = $col;
            } elseif (in_array($norm, ['tanggalupdate', 'tglupdate'], true)) {
                $map['updated_date'] = $col;
            } elseif (in_array($norm, ['postingdate', 'entrygrs', 'tanggalgrs', 'tglgrs'], true)) {
                $map['posting_date'] = $col;
            } elseif (in_array($norm, ['catatan', 'notes', 'remarks'], true)) {
                $map['catatan'] = $col;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, string>  $cells
     * @return array<string, string>
     */
    protected function mapGrsHeaders(array $cells): array
    {
        $map = [];

        foreach ($cells as $col => $rawHeader) {
            $norm = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $rawHeader) ?? '');

            if (in_array($norm, ['pobyitem', 'poitem'], true)) {
                $map['po_by_item'] = $col;
            } elseif (in_array($norm, ['entrydate', 'entrygrs'], true)) {
                $map['entry_date'] = $col;
            } elseif (in_array($norm, ['postingdate', 'postingdt', 'tglposting'], true)) {
                $map['posting_date'] = $col;
            } elseif (in_array($norm, ['purchaseorder', 'pono', 'nomorpo'], true)) {
                $map['po_number'] = $col;
            } elseif (in_array($norm, ['item', 'itemno'], true)) {
                $map['item_no'] = $col;
            } elseif (in_array($norm, ['material', 'materialno', 'kodematerial'], true)) {
                $map['material_code'] = $col;
            } elseif (in_array($norm, ['materialdescription', 'deskripsi'], true)) {
                $map['description'] = $col;
            } elseif (in_array($norm, ['text', 'documentheadertext', 'keterangan'], true)) {
                $map['location_text'] = $col;
            } elseif (in_array($norm, ['amountinlc', 'amount'], true)) {
                $map['amount_lc'] = $col;
            } elseif (in_array($norm, ['qtygr', 'qtyinopun', 'qtyinunofentry', 'quantity'], true)) {
                if (! isset($map['qty_gr']) || $norm === 'qtygr') {
                    $map['qty_gr'] = $col;
                }
            } elseif ($norm === 'mrptype') {
                // Sheet GRS asli memiliki 2 kolom berlabel "MRP TYPE" (S = NONSTOCK/PD, T = ZSP/Material Type)
                if (! isset($map['mrp_type'])) {
                    $map['mrp_type'] = $col;
                } else {
                    $map['material_type'] = $col;
                }
            } elseif ($norm === 'materialtype') {
                $map['material_type'] = $col;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, string>  $cells
     * @return array<string, string>
     */
    protected function mapR02Headers(array $cells): array
    {
        $map = [];

        foreach ($cells as $col => $rawHeader) {
            $norm = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $rawHeader) ?? '');

            if (in_array($norm, ['pobyitem', 'poitem'], true)) {
                $map['po_by_item'] = $col;
            } elseif (in_array($norm, ['nomorpo', 'purchaseorder'], true)) {
                $map['po_number'] = $col;
            } elseif (in_array($norm, ['itempo', 'item'], true)) {
                $map['item_no'] = $col;
            } elseif (in_array($norm, ['vendorname'], true)) {
                $map['vendor_name'] = $col;
            } elseif (in_array($norm, ['qtypo'], true)) {
                $map['qty_po'] = $col;
            } elseif (in_array($norm, ['satuanpo', 'uoi'], true)) {
                $map['uoi'] = $col;
            } elseif (in_array($norm, ['departementrequisitioner', 'requisitioner'], true)) {
                $map['requisitioner'] = $col;
            } elseif (in_array($norm, ['description', 'materialdescription'], true)) {
                $map['description'] = $col;
            } elseif (in_array($norm, ['materialno', 'material'], true)) {
                $map['material_code'] = $col;
            } elseif (in_array($norm, ['totalamountinlocalcurr', 'totalamountinlc'], true)) {
                $map['amount_lc'] = $col;
            }
        }

        return $map;
    }

    protected function cleanCode(?string $raw): ?string
    {
        $clean = trim((string) $raw);
        if ($clean === '' || $clean === '-') {
            return null;
        }

        if (is_numeric($clean) && str_contains(strtoupper($clean), 'E')) {
            $clean = sprintf('%.0f', (float) $clean);
        } elseif (preg_match('/^\d+\.0+$/', $clean) === 1) {
            $clean = preg_replace('/\.0+$/', '', $clean) ?? $clean;
        }

        return $clean !== '' ? $clean : null;
    }

    protected function parseNumber(?string $raw): float
    {
        $clean = trim((string) $raw);
        if ($clean === '' || $clean === '-' || str_starts_with($clean, '#')) {
            return 0.0;
        }

        if (is_numeric($clean)) {
            return (float) $clean;
        }

        $clean = trim((string) preg_replace('/^(Rp\.?|IDR)\s*/i', '', $clean));
        $clean = str_replace(' ', '', $clean);

        if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d+)?$/', $clean) === 1) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);

            return (float) $clean;
        }

        if (preg_match('/^-?\d{1,3}(,\d{3})+(\.\d+)?$/', $clean) === 1) {
            $clean = str_replace(',', '', $clean);

            return (float) $clean;
        }

        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    protected function parseDate(?string $raw): ?string
    {
        $clean = trim((string) $raw);
        if ($clean === '' || $clean === '-' || str_starts_with($clean, '#')) {
            return null;
        }

        if (is_numeric($clean)) {
            $num = (float) $clean;
            if ($num >= 30000 && $num <= 65000) {
                $timestamp = (int) round(($num - 25569) * 86400);

                return gmdate('Y-m-d', $timestamp);
            }

            return null;
        }

        // Terjemahkan nama bulan Indonesia bila ada
        $indoMonths = [
            'Januari' => 'January',
            'Februari' => 'February',
            'Maret' => 'March',
            'Mei' => 'May',
            'Juni' => 'June',
            'Juli' => 'July',
            'Agustus' => 'August',
            'Oktober' => 'October',
            'Desember' => 'December',
        ];
        $translated = str_ireplace(array_keys($indoMonths), array_values($indoMonths), $clean);

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd-F-Y', 'd F Y', 'd M Y', 'm/d/Y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $translated);
                if ($parsed !== false && $parsed->year >= 2000 && $parsed->year <= 2100) {
                    return $parsed->toDateString();
                }
            } catch (\Throwable) {
                // Coba format berikutnya
            }
        }

        try {
            $parsed = Carbon::parse($translated);
            if ($parsed->year >= 2000 && $parsed->year <= 2100) {
                return $parsed->toDateString();
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
