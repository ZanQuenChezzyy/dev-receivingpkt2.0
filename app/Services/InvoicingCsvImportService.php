<?php

namespace App\Services;

use App\Models\DeliveryOrderReceipt;
use App\Models\InvoicingReceipt;
use App\Models\PurchaseOrderIssued;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class InvoicingCsvImportService
{
    public function __construct(
        protected InvoicingAssignmentService $assignmentService
    ) {}

    /**
     * Menormalisasi file CSV atau Excel (.xlsx) Multi-Section / Multi-Sheet Mitraku secara in-place
     * menjadi CSV 1 tabel rata (flat) agar kompatibel penuh dengan Filament ImportAction & League\Csv.
     */
    public function normalizeMitrakuCsvFileInPlace(string $filePath): void
    {
        if (! is_file($filePath) || ! is_readable($filePath) || ! is_writable($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        if ($content === false || trim($content) === '') {
            return;
        }

        // Jika file adalah workbook Excel (.xlsx / ZIP archive PK\x03\x04)
        if ($this->isXlsxContent($content)) {
            $csvFromXlsx = $this->convertXlsxFileToRawCsvString($filePath);
            if ($csvFromXlsx === '') {
                return;
            }

            $flattened = $this->flattenMultiSectionCsvToString($csvFromXlsx);
            file_put_contents($filePath, $flattened !== '' ? $flattened : $csvFromXlsx);

            return;
        }

        if (! $this->isMultiSectionMitrakuCsv($content)) {
            return;
        }

        $flattened = $this->flattenMultiSectionCsvToString($content);
        if ($flattened !== '') {
            file_put_contents($filePath, $flattened);
        }
    }

    /**
     * Mengecek apakah konten biner merupakan file .xlsx (ZIP archive).
     */
    public function isXlsxContent(string $content): bool
    {
        return str_starts_with($content, "PK\x03\x04");
    }

    /**
     * Mengekstrak seluruh baris dari semua worksheet di dalam file .xlsx
     * lalu menggabungkannya menjadi string CSV mentah (sehingga baik .xlsx 1 sheet
     * maupun .xlsx multi-sheet dapat diproses oleh parser Multi-Section).
     */
    public function convertXlsxFileToRawCsvString(string $filePath): string
    {
        if (! class_exists(\ZipArchive::class)) {
            return '';
        }

        $zip = new \ZipArchive;
        if ($zip->open($filePath) !== true) {
            return '';
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if (is_string($sharedStringsXml) && $sharedStringsXml !== '') {
            $xml = @simplexml_load_string($sharedStringsXml);
            if ($xml !== false && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $run) {
                            $text .= (string) ($run->t ?? '');
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        $sheetNames = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (is_string($name) && preg_match('#^xl/worksheets/sheet\d+\.xml$#i', $name)) {
                $sheetNames[] = $name;
            }
        }
        natsort($sheetNames);

        $outStream = fopen('php://temp', 'r+');

        foreach ($sheetNames as $sheetName) {
            $sheetXml = $zip->getFromName($sheetName);
            if (! is_string($sheetXml) || $sheetXml === '') {
                continue;
            }

            $xml = @simplexml_load_string($sheetXml);
            if ($xml === false || ! isset($xml->sheetData->row)) {
                continue;
            }

            foreach ($xml->sheetData->row as $row) {
                $rowCells = [];
                $maxColIndex = -1;

                foreach ($row->c as $c) {
                    $cellRef = (string) ($c['r'] ?? '');
                    $colIndex = $this->excelCellRefToColumnIndex($cellRef, $maxColIndex + 1);
                    $maxColIndex = max($maxColIndex, $colIndex);

                    $cellType = (string) ($c['t'] ?? '');
                    $val = '';

                    if ($cellType === 's') {
                        $ssIndex = (int) ($c->v ?? 0);
                        $val = $sharedStrings[$ssIndex] ?? '';
                    } elseif ($cellType === 'inlineStr') {
                        if (isset($c->is->t)) {
                            $val = (string) $c->is->t;
                        } elseif (isset($c->is->r)) {
                            foreach ($c->is->r as $run) {
                                $val .= (string) ($run->t ?? '');
                            }
                        }
                    } else {
                        $val = (string) ($c->v ?? '');
                    }

                    $rowCells[$colIndex] = trim($val);
                }

                if ($maxColIndex >= 0) {
                    $denseRow = [];
                    for ($col = 0; $col <= $maxColIndex; $col++) {
                        $denseRow[$col] = $rowCells[$col] ?? '';
                    }

                    if (! empty(array_filter($denseRow, fn (string $v): bool => $v !== ''))) {
                        fputcsv($outStream, $denseRow);
                    }
                }
            }
        }

        $zip->close();

        rewind($outStream);
        $rawCsv = stream_get_contents($outStream);
        fclose($outStream);

        return $rawCsv ?: '';
    }

    /**
     * Mengonversi referensi sel Excel (misal: "A1" -> 0, "B1" -> 1, "AA1" -> 26) menjadi indeks kolom 0-based.
     */
    protected function excelCellRefToColumnIndex(string $cellRef, int $fallbackIndex): int
    {
        if (! preg_match('/^([A-Z]+)/i', $cellRef, $matches)) {
            return $fallbackIndex;
        }

        $letters = strtoupper($matches[1]);
        $index = 0;
        $length = strlen($letters);

        for ($i = 0; $i < $length; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - ord('A') + 1);
        }

        return $index - 1;
    }

    /**
     * Mengecek apakah konten CSV merupakan format Multi-Section Mitraku
     * (memiliki lebih dari 1 baris header "Invoice Number").
     */
    public function isMultiSectionMitrakuCsv(string $csvContent): bool
    {
        $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', $csvContent) ?? $csvContent;
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csvContent);
        rewind($stream);

        $headerSectionCount = 0;
        while (($row = fgetcsv($stream)) !== false) {
            $firstCol = trim((string) ($row[0] ?? ''));
            if (strcasecmp($firstCol, 'Invoice Number') === 0) {
                $headerSectionCount++;
                if ($headerSectionCount > 1) {
                    fclose($stream);

                    return true;
                }
            }
        }

        fclose($stream);

        return false;
    }

    /**
     * Menggabungkan Tabel 1 (Header Invoice) dan Tabel 3 (Detail PO/GRS/DO/Arrival Date)
     * dari CSV Multi-Section Mitraku menjadi string CSV 1 tabel yang rata (flat),
     * serta membuang Tabel 2 (Log Aksi).
     */
    public function flattenMultiSectionCsvToString(string $csvContent): string
    {
        $parsed = $this->parseMultiSectionCsv($csvContent);
        $headersByInvoice = $parsed['headers'];
        $detailsByInvoice = $parsed['details'];

        $allInvoiceNumbers = array_unique(array_merge(
            array_keys($headersByInvoice),
            array_keys($detailsByInvoice)
        ));

        if (empty($allInvoiceNumbers)) {
            return '';
        }

        $unifiedColumns = [
            'Invoice Number',
            'Transaction Type',
            'PO Number',
            'Vendor Code',
            'Vendor Name',
            'Tax Number',
            'Invoice Amount',
            'PO Amount',
            'Description',
            'GR / SA / BA-BL Number',
            'DO / BAPP / Timesheet Number',
            'Receipt-DO / BAPP / Arrival Deadline Date',
            'Completion / Actual / Arrival Date',
            'Created By',
        ];

        $outStream = fopen('php://temp', 'r+');
        fputcsv($outStream, $unifiedColumns);

        foreach ($allInvoiceNumbers as $invoiceNo) {
            $headerRow = $headersByInvoice[$invoiceNo] ?? [];
            $detailRows = $detailsByInvoice[$invoiceNo] ?? [[]];

            foreach ($detailRows as $detailRow) {
                $poNumber = trim((string) ($detailRow['PO Number'] ?? ($headerRow['PO Numbers'] ?? '')));
                if (str_contains($poNumber, ',')) {
                    $poNumber = trim(explode(',', $poNumber)[0]);
                }

                $invoiceAmount = trim((string) ($headerRow['Invoice Amount'] ?? ($detailRow['PO Amount'] ?? '')));
                $poAmount = trim((string) ($detailRow['PO Amount'] ?? $invoiceAmount));
                $description = trim((string) ($headerRow['Description'] ?? ($detailRow['PO Notes'] ?? '')));

                $flatRow = [
                    $invoiceNo,
                    trim((string) ($detailRow['Transaction Type'] ?? ($headerRow['Transaction Type'] ?? ''))),
                    $poNumber,
                    trim((string) ($headerRow['Vendor Code'] ?? ($detailRow['Vendor Code'] ?? ''))),
                    trim((string) ($headerRow['Vendor Name'] ?? ($detailRow['Vendor Name'] ?? ''))),
                    trim((string) ($headerRow['Tax Number'] ?? '')),
                    $invoiceAmount,
                    $poAmount,
                    $description,
                    trim((string) ($detailRow['GR / SA / BA-BL Number'] ?? '')),
                    trim((string) ($detailRow['DO / BAPP / Timesheet Number'] ?? '')),
                    trim((string) ($detailRow['Receipt-DO / BAPP / Arrival Deadline Date'] ?? '')),
                    trim((string) ($detailRow['Completion / Actual / Arrival Date'] ?? '')),
                    trim((string) ($headerRow['Created By'] ?? '')),
                ];

                fputcsv($outStream, $flatRow);
            }
        }

        rewind($outStream);
        $flattenedCsv = stream_get_contents($outStream);
        fclose($outStream);

        return $flattenedCsv ?: '';
    }

    /**
     * Digunakan oleh Filament InvoicingReceiptImporter::resolveRecord() untuk memproses
     * 1 baris data dari Importer (mendukung pencocokan 2 arah PO + Tanggal Kedatangan / Nomor DO).
     *
     * @param  array<string, mixed>  $mappedData
     * @param  array<string, mixed>  $originalData
     */
    public function resolveRecordForImporter(array $mappedData, array $originalData = []): ?InvoicingReceipt
    {
        $invoiceNo = trim((string) ($mappedData['no_invoice'] ?? $originalData['Invoice Number'] ?? ''));

        $headerRow = [
            'Invoice Number' => $invoiceNo,
            'Transaction Type' => (string) ($mappedData['transaction_type'] ?? $originalData['Transaction Type'] ?? ''),
            'PO Numbers' => (string) ($mappedData['no_po'] ?? $originalData['PO Number'] ?? $originalData['PO Numbers'] ?? ''),
            'Vendor Code' => (string) ($mappedData['vendor_code'] ?? $originalData['Vendor Code'] ?? ''),
            'Vendor Name' => (string) ($mappedData['vendor_name'] ?? $originalData['Vendor Name'] ?? ''),
            'Tax Number' => (string) ($mappedData['faktur_pajak'] ?? $originalData['Tax Number'] ?? ''),
            'Invoice Amount' => (string) ($mappedData['subtotal_exclude_ppn'] ?? $originalData['Invoice Amount'] ?? ''),
            'Description' => (string) ($mappedData['deskripsi'] ?? $originalData['Description'] ?? ''),
            'Created By' => (string) ($mappedData['created_by'] ?? $originalData['Created By'] ?? ''),
        ];

        $detailRow = [
            'PO Number' => (string) ($mappedData['no_po'] ?? $originalData['PO Number'] ?? $originalData['PO Numbers'] ?? ''),
            'PO Amount' => (string) ($mappedData['subtotal_exclude_ppn'] ?? $originalData['PO Amount'] ?? $originalData['Invoice Amount'] ?? ''),
            'GR / SA / BA-BL Number' => (string) ($mappedData['no_grs'] ?? $originalData['GR / SA / BA-BL Number'] ?? ''),
            'DO / BAPP / Timesheet Number' => (string) ($mappedData['no_do'] ?? $originalData['DO / BAPP / Timesheet Number'] ?? ''),
            'Receipt-DO / BAPP / Arrival Deadline Date' => (string) ($mappedData['arrival_deadline_date'] ?? $originalData['Receipt-DO / BAPP / Arrival Deadline Date'] ?? ''),
            'Completion / Actual / Arrival Date' => (string) ($mappedData['actual_arrival_date'] ?? $originalData['Completion / Actual / Arrival Date'] ?? ''),
        ];

        return $this->buildFilledInvoicingReceipt($invoiceNo, $headerRow, $detailRow);
    }

    /**
     * Impor file CSV Mitraku (mendukung format Multi-Section 3 tabel maupun 1 tabel)
     * dan otomatis menautkan ke data Penerimaan Barang (DeliveryOrderReceipt).
     *
     * @return array{
     *     created: int,
     *     updated: int,
     *     linked_do: int,
     *     unlinked_po: array<int, string>,
     *     skipped: int,
     *     processed_invoices: array<int, string>
     * }
     */
    public function importFromFile(string $filePath, bool $onlyPoBarang = true): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return [
                'created' => 0,
                'updated' => 0,
                'linked_do' => 0,
                'unlinked_po' => [],
                'skipped' => 0,
                'processed_invoices' => [],
            ];
        }

        if ($this->isXlsxContent($content)) {
            $content = $this->convertXlsxFileToRawCsvString($filePath);
        }

        return $this->importFromString($content, $onlyPoBarang);
    }

    /**
     * Impor dari konten string CSV Mitraku.
     *
     * @return array{
     *     created: int,
     *     updated: int,
     *     linked_do: int,
     *     unlinked_po: array<int, string>,
     *     skipped: int,
     *     processed_invoices: array<int, string>
     * }
     */
    public function importFromString(string $csvContent, bool $onlyPoBarang = true): array
    {
        $parsed = $this->parseMultiSectionCsv($csvContent);
        $headersByInvoice = $parsed['headers'];
        $detailsByInvoice = $parsed['details'];

        $allInvoiceNumbers = array_unique(array_merge(
            array_keys($headersByInvoice),
            array_keys($detailsByInvoice)
        ));

        $createdCount = 0;
        $updatedCount = 0;
        $linkedDoCount = 0;
        $unlinkedPoList = [];
        $skippedCount = 0;
        $processedInvoices = [];

        foreach ($allInvoiceNumbers as $invoiceNo) {
            $headerRow = $headersByInvoice[$invoiceNo] ?? [];
            $detailRows = $detailsByInvoice[$invoiceNo] ?? [[]];

            $transactionType = trim((string) ($headerRow['Transaction Type'] ?? ($detailRows[0]['Transaction Type'] ?? '')));

            if ($onlyPoBarang && $transactionType !== '' && strcasecmp($transactionType, 'PO Barang') !== 0) {
                $skippedCount++;

                continue;
            }

            foreach ($detailRows as $detailRow) {
                $detailTxType = trim((string) ($detailRow['Transaction Type'] ?? $transactionType));
                if ($onlyPoBarang && $detailTxType !== '' && strcasecmp($detailTxType, 'PO Barang') !== 0) {
                    $skippedCount++;

                    continue;
                }

                $result = $this->processMergedInvoiceRow($invoiceNo, $headerRow, $detailRow);

                if ($result['status'] === 'skipped') {
                    $skippedCount++;

                    continue;
                }

                if ($result['status'] === 'created') {
                    $createdCount++;
                } elseif ($result['status'] === 'updated') {
                    $updatedCount++;
                }

                if ($result['linked_do']) {
                    $linkedDoCount++;
                } elseif (! empty($result['no_po']) && $result['no_po'] !== '-') {
                    $unlinkedPoList[] = "{$invoiceNo} (PO: {$result['no_po']})";
                }

                $processedInvoices[] = $invoiceNo;
            }
        }

        return [
            'created' => $createdCount,
            'updated' => $updatedCount,
            'linked_do' => $linkedDoCount,
            'unlinked_po' => array_values(array_unique($unlinkedPoList)),
            'skipped' => $skippedCount,
            'processed_invoices' => array_values(array_unique($processedInvoices)),
        ];
    }

    /**
     * Mem-parsing CSV Multi-Section Mitraku:
     * - Section 1 (Tabel 1): Header Invoice (SAP Document Number, Vendor Code, Invoice Amount, Tax Number, dll.)
     * - Section 2 (Tabel 2): Riwayat / Action Log -> DIABAIKAN SEPENUHNYA
     * - Section 3 (Tabel 3): Detail PO, GR Number, DO Number, Arrival Date
     *
     * @return array{
     *     headers: array<string, array<string, string>>,
     *     details: array<string, array<int, array<string, string>>>
     * }
     */
    public function parseMultiSectionCsv(string $csvContent): array
    {
        // Bersihkan UTF-8 BOM jika ada
        $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', $csvContent) ?? $csvContent;

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csvContent);
        rewind($stream);

        $currentSection = null;
        $currentHeaders = [];
        $headersByInvoice = [];
        $detailsByInvoice = [];

        while (($row = fgetcsv($stream)) !== false) {
            // Lewati baris kosong
            if ($row === [null] || empty(array_filter($row, fn ($val) => trim((string) $val) !== ''))) {
                continue;
            }

            $trimmedRow = array_map(fn ($val) => trim((string) $val), $row);
            $firstCol = $trimmedRow[0] ?? '';

            // Deteksi pergantian Header Section
            if (strcasecmp($firstCol, 'Invoice Number') === 0) {
                $headerNames = $trimmedRow;

                if (in_array('SAP Document Number', $headerNames, true) || in_array('Tax Number', $headerNames, true) || in_array('Invoice Amount', $headerNames, true)) {
                    $currentSection = 'header';
                    $currentHeaders = $headerNames;
                } elseif (in_array('Date & Time', $headerNames, true) && in_array('Action', $headerNames, true)) {
                    // Tabel 2: Riwayat Status / Log Aksi -> Diabaikan sesuai review
                    $currentSection = 'history_ignored';
                    $currentHeaders = $headerNames;
                } elseif (in_array('GR / SA / BA-BL Number', $headerNames, true) || in_array('DO / BAPP / Timesheet Number', $headerNames, true) || in_array('PO Number', $headerNames, true)) {
                    $currentSection = 'detail';
                    $currentHeaders = $headerNames;
                } else {
                    $currentSection = 'unknown';
                    $currentHeaders = $headerNames;
                }

                continue;
            }

            if ($currentSection === 'history_ignored' || $currentSection === null || empty($currentHeaders)) {
                continue;
            }

            // Pasangkan nilai baris dengan nama kolom header aktif
            $assoc = [];
            foreach ($currentHeaders as $idx => $colName) {
                if ($colName !== '') {
                    $assoc[$colName] = $trimmedRow[$idx] ?? '';
                }
            }

            $invoiceNo = trim($assoc['Invoice Number'] ?? '');
            if ($invoiceNo === '') {
                continue;
            }

            if ($currentSection === 'header') {
                $headersByInvoice[$invoiceNo] = $assoc;
            } elseif ($currentSection === 'detail') {
                $detailsByInvoice[$invoiceNo][] = $assoc;
            }
        }

        fclose($stream);

        return [
            'headers' => $headersByInvoice,
            'details' => $detailsByInvoice,
        ];
    }

    /**
     * Memproses 1 baris gabungan (Tabel 1 + Tabel 3), mencocokkan ke DeliveryOrderReceipt,
     * dan melakukan Smart Upsert ke InvoicingReceipt.
     *
     * @param  array<string, string>  $headerRow
     * @param  array<string, string>  $detailRow
     * @return array{status: string, linked_do: bool, no_po: string, record: ?InvoicingReceipt}
     */
    protected function processMergedInvoiceRow(string $invoiceNo, array $headerRow, array $detailRow): array
    {
        $receipt = $this->buildFilledInvoicingReceipt($invoiceNo, $headerRow, $detailRow);
        if (! $receipt) {
            return ['status' => 'skipped', 'linked_do' => false, 'no_po' => '-', 'record' => null];
        }

        $isUpdating = $receipt->exists;
        $receipt->save();

        return [
            'status' => $isUpdating ? 'updated' : 'created',
            'linked_do' => $receipt->delivery_order_receipt_id !== null,
            'no_po' => (string) $receipt->no_po,
            'record' => $receipt,
        ];
    }

    /**
     * Membangun atau memperbarui atribut InvoicingReceipt dari pasangan baris Header + Detail
     * tanpa langsung memanggil save() (sehingga dapat digunakan langsung oleh Filament Importer).
     *
     * @param  array<string, string>  $headerRow
     * @param  array<string, string>  $detailRow
     */
    public function buildFilledInvoicingReceipt(string $invoiceNo, array $headerRow, array $detailRow): ?InvoicingReceipt
    {
        // 1. Ekstrak Nomor PO (prioritas dari Tabel 3 PO Number, fallback ke Tabel 1 PO Number / PO Numbers)
        $rawPo = trim($detailRow['PO Number'] ?? ($headerRow['PO Number'] ?? ($headerRow['PO Numbers'] ?? '')));
        $noPo = $rawPo !== '' ? trim(explode(',', $rawPo)[0]) : '-';

        // 2. Ekstrak Nomor GRS & Nomor DO dari Tabel 3 (atau dari baris gabungan)
        $noGrs = $this->nullIfEmpty($detailRow['GR / SA / BA-BL Number'] ?? ($headerRow['GR / SA / BA-BL Number'] ?? null));
        $csvNoDo = $this->nullIfEmpty($detailRow['DO / BAPP / Timesheet Number'] ?? ($headerRow['DO / BAPP / Timesheet Number'] ?? null));

        // 3. Ekstrak Tanggal Kedatangan Barang dari Tabel 3 (Acuan pencocokan ke DeliveryOrderReceipt)
        // Catatan: GR / SA / BA-BL Date TIDAK digunakan sebagai tanggal_terima_grs sesuai review
        $arrivalDateCandidates = array_values(array_filter([
            $this->parseDate($detailRow['Receipt-DO / BAPP / Arrival Deadline Date'] ?? ($headerRow['Receipt-DO / BAPP / Arrival Deadline Date'] ?? null)),
            $this->parseDate($detailRow['Completion / Actual / Arrival Date'] ?? ($headerRow['Completion / Actual / Arrival Date'] ?? null)),
        ]));

        // 4. Ekstrak Informasi Tagihan & Pajak dari Tabel 1
        // Catatan: Created On & Transmittal Cashier to Accounting Date TIDAK digunakan sesuai review
        $fakturPajak = $this->nullIfEmpty($headerRow['Tax Number'] ?? null);
        $rawAmount = $detailRow['PO Amount'] ?? ($headerRow['Invoice Amount'] ?? null);
        if ($rawAmount === null || trim((string) $rawAmount) === '') {
            $rawAmount = $headerRow['Invoice Amount'] ?? null;
        }
        $subtotalExclude = $this->parseNumeric($rawAmount);

        $vendorCode = $this->nullIfEmpty($headerRow['Vendor Code'] ?? null);
        $rawVendorName = $this->nullIfEmpty($headerRow['Vendor Name'] ?? null);
        $cleanVendorName = PurchaseOrderIssued::cleanVendorName($rawVendorName);
        $csvDescription = $this->nullIfEmpty($headerRow['Description'] ?? ($detailRow['PO Notes'] ?? null));
        $createdByName = $this->nullIfEmpty($headerRow['Created By'] ?? null);

        if ($noPo === '-' && $invoiceNo === '') {
            return null;
        }

        // 5. Cari Data Penerimaan Barang (DeliveryOrderReceipt) menggunakan Kombinasi 2 Arah:
        //    Tahap 1: Nomor PO + Tanggal Kedatangan (received_date / physical_received_date)
        //    Tahap 2: Jika Tanggal tidak sesuai -> Nomor PO + Nomor DO (delivery_order_no)
        //    Tahap 3: Fallback jika PO hanya memiliki 1 DeliveryOrderReceipt yang belum ber-invoice
        $matchedDo = $this->findMatchingDeliveryOrderReceipt(
            noPo: $noPo,
            arrivalDates: $arrivalDateCandidates,
            csvNoDo: $csvNoDo,
            invoiceNo: $invoiceNo
        );

        // 6. Resolusi Data Master PO Terbit (PurchaseOrderIssued)
        $poRecord = null;
        $doDetails = collect();

        if ($matchedDo) {
            $matchedDo->loadMissing(['deliveryOrderReceiptDetails.purchaseOrderIssued', 'grsRdtvItems.grsRdtv']);
            $doDetails = $matchedDo->deliveryOrderReceiptDetails;
            $poRecord = $doDetails->first()?->purchaseOrderIssued;
        }

        if (! $poRecord && $noPo !== '-') {
            $poRecord = PurchaseOrderIssued::where('purchase_order_no', $noPo)->first();
        }

        // 7. Resolusi Data Master Vendor
        $vendor = $this->resolveVendor($vendorCode, $cleanVendorName, $poRecord);

        // 8. Resolusi Item GRS/RDTV jika DO ditemukan
        $grsRdtvItem = $matchedDo?->grsRdtvItems?->sortByDesc('id')->first();

        // 9. Cari apakah sudah ada record InvoicingReceipt (Smart Upsert)
        $existingReceipt = $this->findExistingInvoicingReceipt(
            invoiceNo: $invoiceNo,
            matchedDo: $matchedDo,
            noPo: $noPo,
            csvNoDo: $csvNoDo
        );

        $receipt = $existingReceipt ?? new InvoicingReceipt;

        // 10. Siapkan atribut dari Penerimaan Barang (DO) & PO Terbit
        $firstDetail = $doDetails->first();
        $rawMaterialType = $receipt->material_type
            ?? $firstDetail?->material_type
            ?? $poRecord?->material_type;
        $cleanMaterialType = $this->cleanMaterialType($rawMaterialType);

        $qtyPenagihan = $receipt->qty_penagihan;
        if (($qtyPenagihan === null || (float) $qtyPenagihan === 0.0) && $doDetails->isNotEmpty()) {
            $qtyPenagihan = (float) $doDetails->sum('quantity');
        } elseif ($qtyPenagihan === null && $poRecord?->qty_po) {
            $qtyPenagihan = (float) $poRecord->qty_po;
        }

        $qtyPo = $receipt->qty_po;
        if ($qtyPo === null || (float) $qtyPo === 0.0) {
            $sumQtyPo = $doDetails->map(fn ($d) => $d->purchaseOrderIssued)->filter()->unique('id')->sum('qty_po');
            $qtyPo = $sumQtyPo > 0 ? (float) $sumQtyPo : ($poRecord?->qty_po ? (float) $poRecord->qty_po : null);
        }

        $uoi = $receipt->uoi ?? $firstDetail?->uoi ?? $poRecord?->uoi ?? 'PCS';
        $namaBarang = $receipt->nama_barang ?? $firstDetail?->description ?? $poRecord?->description ?? $csvDescription;
        $deskripsi = $csvDescription ?? $receipt->deskripsi ?? $firstDetail?->description ?? $poRecord?->description;

        // Nilai tagihan (Exclude PPN & Include PPN 11%)
        if ($subtotalExclude === null && $receipt->subtotal_exclude_ppn !== null) {
            $subtotalExclude = (float) $receipt->subtotal_exclude_ppn;
        } elseif ($subtotalExclude === null && $doDetails->isNotEmpty()) {
            $sumSnapshot = (float) $doDetails->sum('total_amount_snapshot');
            $subtotalExclude = $sumSnapshot > 0 ? $sumSnapshot : ($poRecord?->total_amount_in_lc ? (float) $poRecord->total_amount_in_lc : null);
        }

        $subtotalInclude = $subtotalExclude !== null
            ? round($subtotalExclude + ($subtotalExclude * 0.11), 2)
            : $receipt->subtotal_include_ppn;

        // Tentukan PIC jika belum ada
        $picId = $receipt->pic_id;
        if (! $picId) {
            $picId = $this->resolvePicId($createdByName, $cleanMaterialType, $doDetails, $poRecord, $deskripsi);
        }

        // Nomor DO: gunakan yang tercatat dari CSV atau dari DO yang ter-link
        $finalNoDo = $csvNoDo ?? $matchedDo?->delivery_order_no ?? $receipt->no_do;

        // Tanggal Terima GRS: HANYA diambil dari transaksi GRS internal jika belum ada (bukan dari GR Date CSV)
        $tanggalTerimaGrs = $receipt->tanggal_terima_grs
            ?? $grsRdtvItem?->grsRdtv?->transaction_date;

        $receipt->fill([
            'grs_rdtv_item_id' => $receipt->grs_rdtv_item_id ?? $grsRdtvItem?->id,
            'delivery_order_receipt_id' => $matchedDo?->id ?? $receipt->delivery_order_receipt_id,
            'purchase_order_issued_id' => $receipt->purchase_order_issued_id ?? $poRecord?->id,
            'vendor_id' => $vendor?->id ?? $receipt->vendor_id,
            'pic_id' => $picId,
            'tanggal_terima_grs' => $tanggalTerimaGrs,
            'jenis' => $receipt->jenis ?: ($grsRdtvItem?->grsRdtv?->category ?? 'GRS'),
            'no_grs' => $noGrs ?? $receipt->no_grs,
            'no_po' => $noPo !== '-' ? $noPo : ($receipt->no_po ?? $poRecord?->purchase_order_no ?? '-'),
            'no_do' => $finalNoDo,
            'material_type' => $cleanMaterialType,
            'vendor_name' => $cleanVendorName ?? $receipt->vendor_name ?? $poRecord?->vendor_name ?? $vendor?->name,
            'deskripsi' => $deskripsi,
            'email_vendor' => $receipt->email_vendor ?? (is_array($vendor?->email) ? implode(', ', $vendor->email) : $vendor?->email),
            'no_invoice' => $invoiceNo !== '' ? $invoiceNo : $receipt->no_invoice,
            'faktur_pajak' => $fakturPajak ?? $receipt->faktur_pajak,
            'nama_barang' => $namaBarang,
            'qty_penagihan' => $qtyPenagihan,
            'qty_po' => $qtyPo,
            'uoi' => $uoi,
            'subtotal_exclude_ppn' => $subtotalExclude,
            'subtotal_include_ppn' => $subtotalInclude,
            'status' => $receipt->status ?: 'Menunggu Verifikasi',
        ]);

        return $receipt;
    }

    /**
     * Algoritma Pencocokan 2 Arah ke DeliveryOrderReceipt di dalam Nomor PO yang sama:
     * 1. Cek Nomor PO + Tanggal Kedatangan (received_date / physical_received_date)
     * 2. Jika Tanggal tidak sesuai -> Cek Nomor PO + Nomor DO (delivery_order_no)
     * 3. Fallback: Jika PO tersebut hanya punya 1 DeliveryOrderReceipt yang belum dipakai invoice lain.
     *
     * @param  array<int, string>  $arrivalDates
     */
    protected function findMatchingDeliveryOrderReceipt(
        string $noPo,
        array $arrivalDates,
        ?string $csvNoDo,
        string $invoiceNo
    ): ?DeliveryOrderReceipt {
        if ($noPo === '' || $noPo === '-') {
            return null;
        }

        // Ambil seluruh DeliveryOrderReceipt untuk Nomor PO ini
        $candidates = DeliveryOrderReceipt::with(['deliveryOrderReceiptDetails.purchaseOrderIssued', 'invoicingReceipts'])
            ->whereHas('deliveryOrderReceiptDetails.purchaseOrderIssued', function ($query) use ($noPo) {
                $query->where('purchase_order_no', $noPo);
            })
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        // Helper untuk memprioritaskan DO yang belum dipakai oleh invoice berbeda
        $preferAvailable = function (Collection $matches) use ($invoiceNo): ?DeliveryOrderReceipt {
            if ($matches->isEmpty()) {
                return null;
            }

            // 1. Jika sudah ada yang terhubung ke nomor invoice yang sama
            $sameInvoice = $matches->first(fn (DeliveryOrderReceipt $do) => $do->invoicingReceipts->contains('no_invoice', $invoiceNo));
            if ($sameInvoice) {
                return $sameInvoice;
            }

            // 2. Prioritaskan DO yang belum memiliki nomor invoice (atau belum punya InvoicingReceipt ber-no_invoice)
            $unclaimed = $matches->first(function (DeliveryOrderReceipt $do) {
                return $do->invoicingReceipts->isEmpty()
                    || $do->invoicingReceipts->every(fn (InvoicingReceipt $ir) => empty($ir->no_invoice));
            });

            return $unclaimed ?? $matches->first();
        };

        // TAHAP 1: Cocokkan berdasarkan Tanggal Kedatangan (received_date atau physical_received_date)
        if (! empty($arrivalDates)) {
            $dateMatches = $candidates->filter(function (DeliveryOrderReceipt $do) use ($arrivalDates) {
                $recDate = $do->received_date ? Carbon::parse($do->received_date)->format('Y-m-d') : null;
                $physDate = $do->physical_received_date ? Carbon::parse($do->physical_received_date)->format('Y-m-d') : null;

                return ($recDate && in_array($recDate, $arrivalDates, true))
                    || ($physDate && in_array($physDate, $arrivalDates, true));
            });

            if ($dateMatches->count() === 1) {
                return $dateMatches->first();
            }

            if ($dateMatches->count() > 1) {
                // Jika ada >1 kedatangan di tanggal yang sama, saring lagi dengan Nomor DO jika tersedia
                if ($csvNoDo) {
                    $doSubMatch = $this->filterByDoNumber($dateMatches, $csvNoDo);
                    if ($doSubMatch->isNotEmpty()) {
                        return $preferAvailable($doSubMatch);
                    }
                }

                return $preferAvailable($dateMatches);
            }
        }

        // TAHAP 2: Jika Tanggal Kedatangan tidak sesuai -> Cocokkan berdasarkan Nomor DO (delivery_order_no)
        if ($csvNoDo !== null && $csvNoDo !== '') {
            $doMatches = $this->filterByDoNumber($candidates, $csvNoDo);
            if ($doMatches->isNotEmpty()) {
                return $preferAvailable($doMatches);
            }
        }

        // TAHAP 3: Fallback jika PO tersebut hanya memiliki 1 DeliveryOrderReceipt yang belum dipakai invoice lain
        if ($candidates->count() === 1) {
            $onlyDo = $candidates->first();
            $hasDifferentInvoice = $onlyDo->invoicingReceipts->contains(function (InvoicingReceipt $ir) use ($invoiceNo) {
                return ! empty($ir->no_invoice) && strcasecmp($ir->no_invoice, $invoiceNo) !== 0;
            });

            if (! $hasDifferentInvoice) {
                return $onlyDo;
            }
        }

        return null;
    }

    /**
     * Saring koleksi DeliveryOrderReceipt berdasarkan Nomor DO (exact match maupun normalisasi alfanumerik).
     *
     * @param  Collection<int, DeliveryOrderReceipt>  $candidates
     * @return Collection<int, DeliveryOrderReceipt>
     */
    protected function filterByDoNumber(Collection $candidates, string $csvNoDo): Collection
    {
        $exactMatches = $candidates->filter(
            fn (DeliveryOrderReceipt $do) => strcasecmp(trim((string) $do->delivery_order_no), trim($csvNoDo)) === 0
        );

        if ($exactMatches->isNotEmpty()) {
            return $exactMatches;
        }

        $normCsvDo = $this->normalizeDoNumber($csvNoDo);
        if ($normCsvDo === '') {
            return collect();
        }

        return $candidates->filter(function (DeliveryOrderReceipt $do) use ($normCsvDo) {
            $normDbDo = $this->normalizeDoNumber((string) $do->delivery_order_no);

            return $normDbDo !== '' && $normDbDo === $normCsvDo;
        });
    }

    /**
     * Cari record InvoicingReceipt yang sudah ada untuk mencegah duplikasi data.
     */
    protected function findExistingInvoicingReceipt(
        string $invoiceNo,
        ?DeliveryOrderReceipt $matchedDo,
        string $noPo,
        ?string $csvNoDo
    ): ?InvoicingReceipt {
        // 1. Cek berdasarkan Nomor Invoice jika sudah pernah diimpor
        if ($invoiceNo !== '') {
            $byInvoice = InvoicingReceipt::where('no_invoice', $invoiceNo)->first();
            if ($byInvoice) {
                return $byInvoice;
            }
        }

        // 2. Cek berdasarkan delivery_order_receipt_id (misal yang sudah ter-generate otomatis dari GRS)
        if ($matchedDo) {
            $byDo = InvoicingReceipt::where('delivery_order_receipt_id', $matchedDo->id)
                ->where(function ($q) use ($invoiceNo) {
                    $q->whereNull('no_invoice')
                        ->orWhere('no_invoice', '')
                        ->orWhere('no_invoice', $invoiceNo);
                })
                ->latest('id')
                ->first();

            if ($byDo) {
                return $byDo;
            }
        }

        // 3. Cek berdasarkan kombinasi Nomor PO + Nomor DO yang belum memiliki no_invoice
        if ($noPo !== '-' && $csvNoDo) {
            $byPoDo = InvoicingReceipt::where('no_po', $noPo)
                ->where('no_do', $csvNoDo)
                ->where(function ($q) use ($invoiceNo) {
                    $q->whereNull('no_invoice')
                        ->orWhere('no_invoice', '')
                        ->orWhere('no_invoice', $invoiceNo);
                })
                ->latest('id')
                ->first();

            if ($byPoDo) {
                return $byPoDo;
            }
        }

        // 4. Cek jika hanya ada 1 InvoicingReceipt untuk PO ini yang belum memiliki no_invoice
        if ($noPo !== '-') {
            $unbilledForPo = InvoicingReceipt::where('no_po', $noPo)
                ->where(function ($q) {
                    $q->whereNull('no_invoice')->orWhere('no_invoice', '');
                })
                ->get();

            if ($unbilledForPo->count() === 1) {
                return $unbilledForPo->first();
            }
        }

        return null;
    }

    protected function resolveVendor(?string $vendorCode, ?string $cleanVendorName, ?PurchaseOrderIssued $poRecord): ?Vendor
    {
        $codeToSearch = $vendorCode ?: $poRecord?->vendor_id;
        $nameToSearch = $cleanVendorName ?: $poRecord?->vendor_name;

        if (! $codeToSearch && ! $nameToSearch) {
            return null;
        }

        return Vendor::query()
            ->when($codeToSearch, fn ($q) => $q->where('vendor_number', $codeToSearch))
            ->when($nameToSearch, function ($q) use ($codeToSearch, $nameToSearch) {
                if ($codeToSearch) {
                    $q->orWhere('name', $nameToSearch);
                } else {
                    $q->where('name', $nameToSearch);
                }
            })
            ->first();
    }

    /**
     * @param  Collection<int, mixed>  $doDetails
     */
    protected function resolvePicId(
        ?string $createdByName,
        ?string $materialType,
        Collection $doDetails,
        ?PurchaseOrderIssued $poRecord,
        ?string $description
    ): ?int {
        if ($createdByName) {
            $staffUser = User::where('is_active', true)
                ->role('Staff Invoice')
                ->where('name', 'like', "%{$createdByName}%")
                ->first();

            if ($staffUser) {
                return $staffUser->id;
            }
        }

        $materialCodes = $doDetails->isNotEmpty()
            ? $doDetails->pluck('material_code')->filter()->values()->all()
            : array_filter([$poRecord?->material_code]);

        $descriptions = $doDetails->isNotEmpty()
            ? $doDetails->pluck('description')->filter()->values()->all()
            : array_filter([$poRecord?->description, $description]);

        return $this->assignmentService->determinePicId($materialType, $materialCodes, $descriptions);
    }

    protected function normalizeDoNumber(string $doNo): string
    {
        $alnum = preg_replace('/[^a-zA-Z0-9]/', '', strtoupper(trim($doNo))) ?? '';

        return ltrim($alnum, '0') ?: $alnum;
    }

    protected function cleanMaterialType(?string $rawType): ?string
    {
        $clean = trim((string) $rawType);
        if ($clean === '' || in_array(strtolower($clean), ['none', 'null', '-', 'kosong'], true)) {
            return null;
        }

        return $clean;
    }

    protected function parseDate(?string $value): ?string
    {
        $clean = trim((string) $value);
        if ($clean === '' || $clean === '-') {
            return null;
        }

        // Dukungan untuk Excel serial date (misal: 46342)
        if (preg_match('/^\d{5}(\.\d+)?$/', $clean)) {
            $serialDays = (int) $clean;
            if ($serialDays >= 35000 && $serialDays <= 65000) {
                return Carbon::create(1899, 12, 30)->addDays($serialDays)->format('Y-m-d');
            }
        }

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d'] as $format) {
            try {
                return Carbon::createFromFormat($format, substr($clean, 0, 10))->format('Y-m-d');
            } catch (\Throwable $e) {
                // Lanjut ke format berikutnya
            }
        }

        try {
            return Carbon::parse($clean)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function parseNumeric(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $clean = trim((string) $value);
        if ($clean === '' || $clean === '-') {
            return null;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', $clean));
        if ($normalized === null || $normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    protected function nullIfEmpty(?string $value): ?string
    {
        $clean = trim((string) $value);

        return ($clean === '' || $clean === '-') ? null : $clean;
    }
}
