<?php

namespace App\Services;

use App\Models\MonitoringKarung;
use App\Models\MonitoringKarungDelivery;
use App\Models\MonitoringKarungQc;
use App\Models\PurchaseOrderIssued;
use App\Models\Vendor;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class MonitoringKarungImportService
{
    /**
     * Import monitoring karung data from CSV string, CSV file path, or XLSX file path,
     * and automatically synchronize each imported record with PO Terbit and DO Receipts.
     *
     * @return array{imported_pos: int, imported_qc_items: int, imported_delivery_items: int, synced_do_count: int}
     */
    public function import(string $contentOrPath, bool $isFilePath = false): array
    {
        $rows = $this->loadRows($contentOrPath, $isFilePath);

        $currentVendor = null;
        $currentPo = null;
        $currentRecord = null;
        $currentCategory = null;
        $currentLocation = null;

        $importedPos = 0;
        $importedQcItems = 0;
        $importedDeliveryItems = 0;
        $processedRecords = [];

        DB::beginTransaction();

        try {
            $headerSkipped = false;

            foreach ($rows as $row) {
                if (empty($row) || count($row) < 2) {
                    continue;
                }

                $firstCol = trim((string) ($row[0] ?? ''));
                $rowString = implode(' ', array_map('strval', $row));

                // Check for stop condition: Monthly matrix section or summary footer
                if (
                    str_contains($firstCol, 'Januari') ||
                    str_contains($firstCol, 'SIK') ||
                    str_contains($rowString, 'Nilai kontrak') ||
                    str_contains($rowString, 'Total lembar karung') ||
                    str_contains($rowString, 'Asumsi perjalanan dinas')
                ) {
                    break;
                }

                // Skip header line
                if (! $headerSkipped && (str_contains($firstCol, 'NAMA VENDOR') || str_contains($rowString, 'NO PO'))) {
                    $headerSkipped = true;

                    continue;
                }

                $vendorCol = trim((string) ($row[0] ?? ''));
                $poCol = trim((string) ($row[1] ?? ''));

                // 1. Update current vendor if present
                if (! empty($vendorCol)) {
                    $currentVendor = $vendorCol;
                }

                // 2. Detect New PO or PO Continuation
                if (! empty($poCol)) {
                    $cleanPo = $this->cleanPoNumber($poCol);

                    if (! empty($cleanPo)) {
                        $currentPo = $cleanPo;

                        // Try to find PO quantity from column 14 (index 14: QTY PO)
                        $rawQtyPo = $this->parseNumber($row[14] ?? null);

                        // Also check notes for category or location
                        $rawDrafterOrNote = trim((string) ($row[13] ?? ''));
                        if (in_array(strtolower($rawDrafterOrNote), ['surabaya', 'banyuwangi'])) {
                            $currentLocation = ucfirst(strtolower($rawDrafterOrNote));
                        }
                        if (in_array(strtolower($rawDrafterOrNote), ['prill', 'granul', 'prill item 1', 'granul item 2'])) {
                            $currentCategory = ucfirst(strtolower($rawDrafterOrNote));
                        }

                        // Match against existing PurchaseOrderIssued or Vendor in database
                        $poIssued = PurchaseOrderIssued::where('purchase_order_no', $currentPo)->first();
                        $vendor = Vendor::where('name', 'like', "%{$currentVendor}%")->first();

                        $qtyPo = $rawQtyPo ?: ($poIssued ? (int) $poIssued->qty_po : 0);

                        $currentRecord = MonitoringKarung::updateOrCreate(
                            ['no_po' => $currentPo],
                            [
                                'purchase_order_issued_id' => $poIssued?->id,
                                'vendor_id' => $vendor?->id,
                                'vendor_name' => $currentVendor ?: ($poIssued?->vendor_name ?: 'Vendor Karung'),
                                'qty_po' => $qtyPo,
                                'kategori_karung' => $currentCategory,
                                'lokasi_pabrik' => $currentLocation,
                                'status' => 'Aktif',
                                'created_by' => Auth::id(),
                            ]
                        );

                        $importedPos++;
                    }
                }

                // If no current PO active, continue
                if (! $currentRecord) {
                    continue;
                }

                $processedRecords[$currentRecord->id] = $currentRecord;

                // Check notes column 13/14 for categories like "Prill", "Granul", "Surabaya", "Banyuwangi"
                $noteCol = trim((string) ($row[13] ?? ''));
                if (! empty($noteCol)) {
                    if (in_array(strtolower($noteCol), ['surabaya', 'banyuwangi'])) {
                        $currentRecord->update(['lokasi_pabrik' => ucfirst(strtolower($noteCol))]);
                    } elseif (str_contains(strtolower($noteCol), 'prill') || str_contains(strtolower($noteCol), 'granul')) {
                        $currentRecord->update(['kategori_karung' => $noteCol]);
                    }
                }

                // Update PO Qty if found on this row and record has 0
                $rowQtyPo = $this->parseNumber($row[14] ?? null);
                if ($rowQtyPo > 0 && $currentRecord->qty_po <= 0) {
                    $currentRecord->update(['qty_po' => $rowQtyPo]);
                }

                // 3. Process QC Column Data (Cols 2 - 13)
                $qtyQc = $this->parseNumber($row[2] ?? null);
                $tahapQc = $this->cleanString($row[3] ?? null, 50);
                $noDofDaan = $this->cleanString($row[4] ?? null, 100);
                $tglPermintaan = $this->parseDate($row[5] ?? null);
                $noSik = $this->cleanString($row[6] ?? null, 100);
                $tglSik = $this->parseDate($row[7] ?? null);
                $noSuratQc = $this->cleanString($row[8] ?? null, 100);
                $statusQc = $this->cleanString($row[9] ?? null, 50) ?: 'Diterima';
                $fileIstek = $this->cleanString($row[10] ?? null, 100);
                $noBapp = $this->cleanString($row[11] ?? null, 100);
                $noSa = $this->cleanSaNumber($row[12] ?? null);
                $drafter = $this->cleanString($row[13] ?? null, 100);

                $isQcTotalRow = strtoupper($tahapQc ?? '') === 'TOTAL' || strtoupper($statusQc ?? '') === 'TOTAL';

                if (! $isQcTotalRow && ($qtyQc > 0 || ! empty($tahapQc) || ! empty($noSik) || ! empty($noSuratQc))) {
                    MonitoringKarungQc::create([
                        'monitoring_karung_id' => $currentRecord->id,
                        'tahap_qc' => $tahapQc ?: 'Tahap QC',
                        'qty_qc' => $qtyQc,
                        'no_dof_daan' => $noDofDaan,
                        'tgl_permintaan' => $tglPermintaan,
                        'no_sik' => $noSik,
                        'tgl_sik' => $tglSik,
                        'no_surat_qc' => $noSuratQc,
                        'status_qc' => $statusQc,
                        'file_istek' => $fileIstek,
                        'no_bapp' => $noBapp,
                        'no_sa' => $noSa,
                        'drafter' => $drafter,
                        'sort_order' => $importedQcItems,
                    ]);

                    $importedQcItems++;
                }

                // 4. Process Delivery / Invoice Column Data (Cols 15 - 22)
                $qtyKirim = $this->parseNumber($row[15] ?? null);
                $tahapKirim = $this->cleanString($row[16] ?? null, 50);
                $noInvoice = $this->cleanString($row[17] ?? null, 100);
                $noDo = $this->cleanString($row[18] ?? null, 100);
                $noSurat = $this->cleanString($row[19] ?? null, 100);
                $qtySupply = $this->parseNumber($row[20] ?? null);
                $qtySurat = $this->parseNumber($row[21] ?? null);
                $tglPenerimaan = $this->parseDate($row[22] ?? null);

                $isDeliveryTotalRow = strtoupper($tahapKirim ?? '') === 'TOTAL' || strtoupper($noInvoice ?? '') === 'TOTAL';

                if (! $isDeliveryTotalRow && ($qtyKirim > 0 || ! empty($tahapKirim) || ! empty($noInvoice) || ! empty($noDo))) {
                    MonitoringKarungDelivery::create([
                        'monitoring_karung_id' => $currentRecord->id,
                        'tahap_kirim' => $tahapKirim ?: (string) ($currentRecord->deliveryItems()->count() + 1),
                        'qty_kirim' => $qtyKirim,
                        'no_invoice' => $noInvoice,
                        'no_do' => $noDo,
                        'no_surat' => $noSurat,
                        'qty_supply' => $qtySupply ?: null,
                        'qty_surat' => $qtySurat ?: null,
                        'tanggal_penerimaan' => $tglPenerimaan,
                        'sort_order' => $importedDeliveryItems,
                    ]);

                    $importedDeliveryItems++;
                }
            }

            // Otomatis sinkronisasi setiap PO hasil import dengan PO Terbit dan DO Receipt
            $syncService = app(SyncMonitoringKarungWithDeliveryOrderService::class);
            $syncedDoCount = 0;
            foreach ($processedRecords as $record) {
                $record->refresh();
                $syncRes = $syncService->sync($record);
                $syncedDoCount += $syncRes['linked_do_count'];
            }

            DB::commit();

            return [
                'imported_pos' => $importedPos,
                'imported_qc_items' => $importedQcItems,
                'imported_delivery_items' => $importedDeliveryItems,
                'synced_do_count' => $syncedDoCount,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Load rows from CSV string, CSV file, or XLSX file.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function loadRows(string $contentOrPath, bool $isFilePath): array
    {
        if ($isFilePath && file_exists($contentOrPath)) {
            $ext = strtolower(pathinfo($contentOrPath, PATHINFO_EXTENSION));
            $magic = @file_get_contents($contentOrPath, false, null, 0, 4);
            $isXlsx = ($ext === 'xlsx' || $magic === "PK\x03\x04");

            if ($isXlsx && class_exists(XlsxReader::class)) {
                return $this->loadRowsFromXlsx($contentOrPath);
            }

            $lines = file($contentOrPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        } else {
            $lines = explode("\n", $contentOrPath);
        }

        $rows = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (! empty($line)) {
                $rows[] = str_getcsv($line);
            }
        }

        return $rows;
    }

    /**
     * Read rows from an XLSX file using OpenSpout.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function loadRowsFromXlsx(string $filePath): array
    {
        $reader = new XlsxReader;
        $reader->open($filePath);

        $targetSheet = null;
        foreach ($reader->getSheetIterator() as $sheet) {
            if (str_contains(strtolower($sheet->getName()), 'monitoring')) {
                $targetSheet = $sheet;
                break;
            }
        }

        if ($targetSheet === null) {
            $reader->close();
            $reader = new XlsxReader;
            $reader->open($filePath);
            foreach ($reader->getSheetIterator() as $sheet) {
                $targetSheet = $sheet;
                break;
            }
        }

        $rows = [];
        if ($targetSheet !== null) {
            foreach ($targetSheet->getRowIterator() as $row) {
                $cells = [];
                foreach ($row->getCells() as $cell) {
                    $val = $cell->getValue();
                    if ($val instanceof DateTimeInterface) {
                        $val = $val->format('Y-m-d');
                    } elseif ($val === null) {
                        $val = '';
                    }
                    $cells[] = $val;
                }
                $rows[] = $cells;
            }
        }

        $reader->close();

        return $rows;
    }

    /**
     * Clean PO numbers (handles cases like "5000007049 / 2400034095").
     */
    protected function cleanPoNumber(mixed $raw): string
    {
        $raw = trim((string) $raw);
        if (empty($raw) || strtoupper($raw) === 'NO PO' || strtoupper($raw) === 'TOTAL' || str_starts_with($raw, '=')) {
            return '';
        }

        // If composite like "5000007049 / 2400034095", take the primary PO
        if (str_contains($raw, '/')) {
            $parts = explode('/', $raw);

            return trim($parts[0]);
        }

        return $raw;
    }

    /**
     * Clean and sanitize string values, rejecting Excel formulas and capping length.
     */
    protected function cleanString(mixed $val, int $maxLength = 100): ?string
    {
        if ($val === null || $val === '') {
            return null;
        }

        $str = trim((string) $val);

        if (str_starts_with($str, '=')) {
            return null;
        }

        return ! empty($str) ? substr($str, 0, $maxLength) : null;
    }

    /**
     * Clean SAP SA numbers, rejecting formulas and capping to 50 chars.
     */
    protected function cleanSaNumber(mixed $val): ?string
    {
        if (empty($val)) {
            return null;
        }

        $str = trim((string) $val);

        if (str_starts_with($str, '=')) {
            return null;
        }

        return ! empty($str) ? substr($str, 0, 50) : null;
    }

    /**
     * Clean string numbers with commas/dots or native numbers.
     */
    protected function parseNumber(mixed $val): int
    {
        if ($val === null || $val === '') {
            return 0;
        }

        if (is_int($val)) {
            return $val;
        }

        if (is_float($val)) {
            return (int) round($val);
        }

        $str = (string) $val;
        if (str_starts_with(trim($str), '=')) {
            return 0;
        }

        $clean = preg_replace('/[^0-9]/', '', $str);

        return (int) $clean;
    }

    /**
     * Parse date string safely into Y-m-d format.
     */
    protected function parseDate(mixed $val): ?string
    {
        if (empty($val)) {
            return null;
        }

        if ($val instanceof DateTimeInterface) {
            return $val->format('Y-m-d');
        }

        $str = trim((string) $val);

        if (str_starts_with($str, '=')) {
            return null;
        }

        // Check if multiple dates like "08/08/2024 & 24/10/2024"
        if (str_contains($str, '&')) {
            $parts = explode('&', $str);
            $str = trim($parts[0]);
        }

        // Handle YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
            return $str;
        }

        // Handle DD/MM/YYYY
        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $str)) {
            try {
                return Carbon::createFromFormat('d/m/Y', $str)->toDateString();
            } catch (\Exception $e) {
                // fallback
            }
        }

        // Handle Excel numeric date serials (e.g. 45692)
        if (is_numeric($str) && (int) $str > 30000 && (int) $str < 70000) {
            try {
                return Carbon::createFromTimestamp(((int) $str - 25569) * 86400)->toDateString();
            } catch (\Exception $e) {
                // fallback
            }
        }

        try {
            return Carbon::parse($str)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }
}
