<?php

namespace App\Services;

use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\GrsRdtvItem;
use App\Models\PurchaseOrderIssued;
use App\Models\WarehouseDeliveryBatch;
use App\Models\WarehouseDeliveryBatchItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SapMb51ImportService
{
    /**
     * Memproses file upload MB51 SAP (CSV atau Excel .xlsx)
     * dan menyimpannya sebagai satu batch master pengiriman.
     *
     * @param array{
     *     file_path: string,
     *     file_name: string,
     *     start_date: string,
     *     end_date: string,
     *     batch_title?: string|null,
     *     uploaded_by: int
     * } $params
     */
    public function import(array $params): WarehouseDeliveryBatch
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $filePath = $params['file_path'];
        $fileName = $params['file_name'];
        $startDate = Carbon::parse($params['start_date'])->toDateString();
        $endDate = Carbon::parse($params['end_date'])->toDateString();
        $uploadedBy = $params['uploaded_by'];

        $defaultTitle = 'Daftar Pengiriman Material Diterima '.Carbon::parse($startDate)->format('d M Y').' - '.Carbon::parse($endDate)->format('d M Y');
        $batchTitle = ! empty($params['batch_title']) ? $params['batch_title'] : $defaultTitle;

        // Baca seluruh baris data dari file (CSV / XLSX)
        $rawRows = $this->extractRowsFromFile($filePath);

        if (empty($rawRows)) {
            throw new \RuntimeException('File kosong atau format data tidak dapat dibaca.');
        }

        // Cari header kolom
        $headerIndex = -1;
        $headerMap = [];

        foreach ($rawRows as $idx => $row) {
            $mapped = $this->identifyHeaderColumns($row);
            if (isset($mapped['mat_doc_105']) && isset($mapped['po_number'])) {
                $headerIndex = $idx;
                $headerMap = $mapped;
                break;
            }
        }

        if ($headerIndex === -1) {
            throw new \RuntimeException('Format kolom SAP MB51 tidak valid. Pastikan kolom Purchase Order dan Material Document tersedia.');
        }

        // Parse baris data dan cegah duplikasi baris identik di dalam file yang sama
        $parsedItems = [];
        $seenInFile = [];

        for ($i = $headerIndex + 1; $i < count($rawRows); $i++) {
            $row = $rawRows[$i];

            $item = $this->parseRow($row, $headerMap);
            if ($item !== null) {
                $logicalKey = WarehouseDeliveryBatchItem::makeLogicalKey(
                    matDoc105: $item['mat_doc_105'] ?? null,
                    poNumber: $item['po_number'] ?? null,
                    itemNo: $item['item_no'] ?? null,
                    materialCode: $item['material_code'] ?? null,
                    docYear: $item['doc_year'] ?? null,
                );

                if (isset($seenInFile[$logicalKey])) {
                    continue;
                }

                $seenInFile[$logicalKey] = true;
                $item['_logical_key'] = $logicalKey;
                $parsedItems[] = $item;
            }
        }

        if (empty($parsedItems)) {
            throw new \RuntimeException('Tidak ada data material valid yang dapat diimpor dari file tersebut.');
        }

        // Preload item MB51 yang sudah ada di database agar unggahan rentang tanggal tumpang tindih tidak menduplikasi data
        $existingByKey = [];
        $uploadedMatDocs = array_values(array_unique(array_filter(array_column($parsedItems, 'mat_doc_105'))));

        foreach (array_chunk($uploadedMatDocs, 500) as $docChunk) {
            $existingRows = WarehouseDeliveryBatchItem::query()
                ->whereIn('mat_doc_105', $docChunk)
                ->orderByDesc('warehouse_delivery_batch_id')
                ->orderByDesc('id')
                ->get();

            foreach ($existingRows as $existingRow) {
                $key = $existingRow->logicalKey();
                if (! isset($existingByKey[$key])) {
                    $existingByKey[$key] = $existingRow;
                }
            }
        }

        // Preload relasi Delivery Order secara bulk untuk performa tinggi & pencegahan N+1 query
        $lookups = $this->buildDeliveryOrderLookup($parsedItems);

        $newRows = [];
        $newTotalValue = 0.0;
        $skippedDuplicates = 0;
        $toEnrich = [];

        foreach ($parsedItems as $itemData) {
            $logicalKey = $itemData['_logical_key'];
            unset($itemData['_logical_key']);

            $match = $this->matchFast($itemData, $lookups);
            $itemData['delivery_order_receipt_id'] = $match['delivery_order_receipt_id'] ?? null;
            $itemData['delivery_order_receipt_detail_id'] = $match['delivery_order_receipt_detail_id'] ?? null;

            if (isset($existingByKey[$logicalKey])) {
                $toEnrich[] = [$existingByKey[$logicalKey], $itemData];
                $skippedDuplicates++;

                continue;
            }

            $newRows[] = $itemData;
            $newTotalValue += (float) ($itemData['amount'] ?? 0);
        }

        if ($toEnrich !== []) {
            DB::transaction(function () use ($toEnrich): void {
                foreach ($toEnrich as [$existing, $incoming]) {
                    $this->enrichExistingBatchItemIfIncomplete($existing, $incoming);
                }
            });
        }

        if ($newRows === []) {
            throw new \RuntimeException("Seluruh {$skippedDuplicates} baris data pada file ini sudah terdaftar di Master MB51 sebelumnya (tidak ada data baru untuk diimpor).");
        }

        return DB::transaction(function () use ($batchTitle, $startDate, $endDate, $fileName, $filePath, $uploadedBy, $newRows, $newTotalValue, $skippedDuplicates, $params) {
            $notes = trim((string) ($params['notes'] ?? ''));
            if ($skippedDuplicates > 0) {
                $dupNote = "{$skippedDuplicates} baris duplikat dari batch sebelumnya dilewati otomatis.";
                $notes = $notes !== '' ? "{$notes}\n{$dupNote}" : $dupNote;
            }

            $batch = WarehouseDeliveryBatch::create([
                'batch_title' => $batchTitle,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_items' => count($newRows),
                'total_value' => $newTotalValue,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'status' => 'active',
                'uploaded_by' => $uploadedBy,
                'notes' => $notes !== '' ? $notes : null,
            ]);

            $template = [
                'warehouse_delivery_batch_id' => $batch->id,
                'posting_date' => null,
                'material_code' => null,
                'description' => null,
                'po_number' => null,
                'item_no' => null,
                'ref_doc_103' => null,
                'mat_doc_105' => null,
                'doc_year' => null,
                'quantity' => 0,
                'uom' => 'EA',
                'amount' => 0,
                'storage_location' => null,
                'plant' => null,
                'vendor_code' => null,
                'reference_do_no' => null,
                'storage_text' => null,
                'status' => 'pending',
                'delivery_order_receipt_id' => null,
                'delivery_order_receipt_detail_id' => null,
                'warehouse_transmittal_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $cleanChunk = [];
            foreach ($newRows as $itemData) {
                $itemData['warehouse_delivery_batch_id'] = $batch->id;
                $itemData['warehouse_transmittal_id'] = null;
                $itemData['created_at'] = now();
                $itemData['updated_at'] = now();

                // Pastikan urutan dan jumlah kolom persis sama untuk setiap baris MySQL bulk insert
                $cleanRow = [];
                foreach ($template as $key => $defaultVal) {
                    $cleanRow[$key] = array_key_exists($key, $itemData) ? $itemData[$key] : $defaultVal;
                }
                $cleanChunk[] = $cleanRow;
            }

            // Simpan items per batch 250 baris untuk kecepatan I/O optimal
            foreach (array_chunk($cleanChunk, 250) as $chunk) {
                WarehouseDeliveryBatchItem::insert($chunk);
            }

            return $batch;
        });
    }

    /**
     * Melengkapi metadata SAP pada item MB51 lama yang belum lengkap tanpa membuat baris duplikat
     * dan tanpa mengubah status pengiriman/transmittal yang sudah ada.
     *
     * @param  array<string, mixed>  $incoming
     */
    protected function enrichExistingBatchItemIfIncomplete(WarehouseDeliveryBatchItem $existing, array $incoming): void
    {
        $updates = [];

        if (blank($existing->ref_doc_103) && filled($incoming['ref_doc_103'] ?? null)) {
            $updates['ref_doc_103'] = $incoming['ref_doc_103'];
            // Batch lama tanpa ref_doc_103 sering memakai qty default 1; perbarui qty & nilai jika data SAP baru lebih lengkap
            if ((float) ($incoming['quantity'] ?? 0) > 0 && (float) $existing->quantity !== (float) $incoming['quantity']) {
                $updates['quantity'] = $incoming['quantity'];
            }
            if ((float) ($incoming['amount'] ?? 0) > 0 && (float) $existing->amount === 0.0) {
                $updates['amount'] = $incoming['amount'];
            }
        }

        if (blank($existing->storage_location) && filled($incoming['storage_location'] ?? null)) {
            $updates['storage_location'] = $incoming['storage_location'];
        }

        if (blank($existing->reference_do_no) && filled($incoming['reference_do_no'] ?? null)) {
            $updates['reference_do_no'] = $incoming['reference_do_no'];
        }

        if (blank($existing->delivery_order_receipt_id) && filled($incoming['delivery_order_receipt_id'] ?? null)) {
            $updates['delivery_order_receipt_id'] = $incoming['delivery_order_receipt_id'];
        }

        if (blank($existing->delivery_order_receipt_detail_id) && filled($incoming['delivery_order_receipt_detail_id'] ?? null)) {
            $updates['delivery_order_receipt_detail_id'] = $incoming['delivery_order_receipt_detail_id'];
        }

        if ($updates !== []) {
            $existing->update($updates);
        }
    }

    /**
     * Melakukan batch preloading dan indexing untuk mencocokkan ribuan data MB51
     * ke Delivery Order hanya dalam 3-5 query efisien (mencegah bottleneck N+1 query),
     * termasuk disambiguasi pengiriman parsial (multi-DO dalam satu PO & item yang sama).
     *
     * @param  array<int, array<string, mixed>>  $parsedItems
     * @return array{
     *     grs_105: array<string, array{delivery_order_receipt_id: int, delivery_order_receipt_detail_id: ?int, details_by_item: array<int, int>}>,
     *     poi_exact: array<string, list<array{delivery_order_receipt_id: int, delivery_order_receipt_detail_id: int, delivery_order_no: string, quantity: ?float}>>,
     *     poi_po: array<string, array{delivery_order_receipt_id: int, delivery_order_receipt_detail_id: ?int}>,
     *     ref_103: array<string, array{delivery_order_receipt_id: int, delivery_order_receipt_detail_id: ?int, details_by_item: array<int, int>}>,
     *     ref_do: array<string, array{delivery_order_receipt_id: int, delivery_order_receipt_detail_id: ?int, details_by_item: array<int, int>}>
     * }
     */
    public function buildDeliveryOrderLookup(array $parsedItems): array
    {
        $poNumbers = [];
        $refDocs103 = [];
        $referenceDoNos = [];
        $matDocs105 = [];

        foreach ($parsedItems as $item) {
            if (! empty($item['po_number'])) {
                $poNumbers[] = (string) $item['po_number'];
            }
            if (! empty($item['ref_doc_103'])) {
                $refDocs103[] = (string) $item['ref_doc_103'];
            }
            if (! empty($item['reference_do_no'])) {
                $referenceDoNos[] = (string) $item['reference_do_no'];
            }
            if (! empty($item['mat_doc_105'])) {
                $matDocs105[] = (string) $item['mat_doc_105'];
            }
        }

        $poNumbers = array_values(array_unique($poNumbers));
        $refDocs103 = array_values(array_unique($refDocs103));
        $referenceDoNos = array_values(array_unique($referenceDoNos));
        $matDocs105 = array_values(array_unique($matDocs105));

        // 0. Preload kecocokan langsung lewat nomor dokumen GRS (MatDoc 105 -> GrsRdtvItem -> DeliveryOrderReceipt)
        $grs105Map = [];
        if (! empty($matDocs105) && Schema::hasTable('grs_rdtv_items')) {
            $grsItems = GrsRdtvItem::query()
                ->whereIn('document_code', $matDocs105)
                ->whereNotNull('delivery_order_receipt_id')
                ->with(['deliveryOrderReceipt.deliveryOrderReceiptDetails'])
                ->get();

            foreach ($grsItems as $grsItem) {
                $do = $grsItem->deliveryOrderReceipt;
                if (! $do) {
                    continue;
                }

                $detailsByItem = [];
                foreach ($do->deliveryOrderReceiptDetails as $d) {
                    $dItemNo = (int) ($d->item_no ?? 0);
                    if ($dItemNo > 0 && ! isset($detailsByItem[$dItemNo])) {
                        $detailsByItem[$dItemNo] = $d->id;
                    }
                }

                $grsKey = strtoupper(trim((string) $grsItem->document_code));
                if ($grsKey !== '' && ! isset($grs105Map[$grsKey])) {
                    $grs105Map[$grsKey] = [
                        'delivery_order_receipt_id' => $do->id,
                        'delivery_order_receipt_detail_id' => null,
                        'details_by_item' => $detailsByItem,
                    ];
                }
            }
        }

        // 1. Preload PurchaseOrderIssued & SEMUA kandidat DeliveryOrderReceiptDetail per PO & Item No (mendukung pengiriman parsial)
        $poiExactMap = [];
        $poiPoMap = [];

        if (! empty($poNumbers)) {
            $pois = PurchaseOrderIssued::whereIn('purchase_order_no', $poNumbers)
                ->select('id', 'purchase_order_no', 'item_no')
                ->get();

            if ($pois->isNotEmpty()) {
                $poiIds = $pois->pluck('id')->toArray();
                $detailsByPoi = DeliveryOrderReceiptDetail::whereIn('purchase_order_issued_id', $poiIds)
                    ->with('deliveryOrderReceipt:id,delivery_order_no')
                    ->get()
                    ->groupBy('purchase_order_issued_id');

                foreach ($pois as $poi) {
                    $matchedDetails = $detailsByPoi->get($poi->id);
                    if (! $matchedDetails || $matchedDetails->isEmpty()) {
                        continue;
                    }

                    foreach ($matchedDetails as $detail) {
                        $candidate = [
                            'delivery_order_receipt_id' => $detail->delivery_order_receipt_id,
                            'delivery_order_receipt_detail_id' => $detail->id,
                            'delivery_order_no' => strtoupper(trim((string) ($detail->deliveryOrderReceipt?->delivery_order_no ?? ''))),
                            'quantity' => $detail->quantity !== null ? (float) $detail->quantity : null,
                        ];

                        $detailItemNo = (int) (($detail->item_no ?? null) ?: $poi->item_no);
                        if ($detailItemNo > 0) {
                            $itemKey = $poi->purchase_order_no.'_'.$detailItemNo;
                            $poiExactMap[$itemKey][] = $candidate;
                        }

                        if (! isset($poiPoMap[$poi->purchase_order_no])) {
                            $poiPoMap[$poi->purchase_order_no] = [
                                'delivery_order_receipt_id' => $detail->delivery_order_receipt_id,
                                'delivery_order_receipt_detail_id' => null,
                            ];
                        }
                    }
                }
            }
        }

        // 2. Preload DeliveryOrderReceipt berdasarkan post_103
        $refDoc103Map = [];
        if (! empty($refDocs103)) {
            $doReceipts103 = DeliveryOrderReceipt::whereIn('post_103', $refDocs103)
                ->with('deliveryOrderReceiptDetails')
                ->get();

            foreach ($doReceipts103 as $do) {
                $itemDetails = [];
                foreach ($do->deliveryOrderReceiptDetails as $d) {
                    $dItemNo = (int) ($d->item_no ?? 0);
                    if ($dItemNo > 0 && ! isset($itemDetails[$dItemNo])) {
                        $itemDetails[$dItemNo] = $d->id;
                    }
                }

                $refDoc103Map[$do->post_103] = [
                    'delivery_order_receipt_id' => $do->id,
                    'delivery_order_receipt_detail_id' => null,
                    'details_by_item' => $itemDetails,
                ];
            }
        }

        // 3. Preload DeliveryOrderReceipt berdasarkan delivery_order_no (case-insensitive)
        $referenceDoMap = [];
        if (! empty($referenceDoNos)) {
            $doReceiptsRef = DeliveryOrderReceipt::whereIn('delivery_order_no', $referenceDoNos)
                ->with('deliveryOrderReceiptDetails')
                ->get();

            foreach ($doReceiptsRef as $do) {
                $itemDetails = [];
                foreach ($do->deliveryOrderReceiptDetails as $d) {
                    $dItemNo = (int) ($d->item_no ?? 0);
                    if ($dItemNo > 0 && ! isset($itemDetails[$dItemNo])) {
                        $itemDetails[$dItemNo] = $d->id;
                    }
                }

                $normDo = strtoupper(trim((string) $do->delivery_order_no));
                if ($normDo !== '' && ! isset($referenceDoMap[$normDo])) {
                    $referenceDoMap[$normDo] = [
                        'delivery_order_receipt_id' => $do->id,
                        'delivery_order_receipt_detail_id' => null,
                        'details_by_item' => $itemDetails,
                    ];
                }
            }
        }

        return [
            'grs_105' => $grs105Map,
            'poi_exact' => $poiExactMap,
            'poi_po' => $poiPoMap,
            'ref_103' => $refDoc103Map,
            'ref_do' => $referenceDoMap,
        ];
    }

    /**
     * Pencocokan in-memory super cepat menggunakan indexed map hasil preloading,
     * dengan prioritas disambiguasi pengiriman parsial (GRS 105 -> Ref DO -> Qty -> PO+Item).
     *
     * @param  array<string, mixed>  $itemData
     * @param  array{
     *     grs_105?: array<string, array{delivery_order_receipt_id: int, delivery_order_receipt_detail_id: ?int, details_by_item: array<int, int>}>,
     *     poi_exact: array<string, list<array{delivery_order_receipt_id: int, delivery_order_receipt_detail_id: int, delivery_order_no: string, quantity: ?float}>>,
     *     poi_po: array<string, array{delivery_order_receipt_id: int, delivery_order_receipt_detail_id: ?int}>,
     *     ref_103: array<string, array{delivery_order_receipt_id: int, delivery_order_receipt_detail_id: ?int, details_by_item: array<int, int>}>,
     *     ref_do: array<string, array{delivery_order_receipt_id: int, delivery_order_receipt_detail_id: ?int, details_by_item: array<int, int>}>
     * }  $lookups
     * @return array{delivery_order_receipt_id: ?int, delivery_order_receipt_detail_id: ?int}|null
     */
    public function matchFast(array $itemData, array $lookups): ?array
    {
        $poNumber = trim((string) ($itemData['po_number'] ?? ''));
        $itemNo = (int) ($itemData['item_no'] ?? 0);
        $refDoc103 = trim((string) ($itemData['ref_doc_103'] ?? ''));
        $referenceDoNo = strtoupper(trim((string) ($itemData['reference_do_no'] ?? '')));
        $matDoc105 = strtoupper(trim((string) ($itemData['mat_doc_105'] ?? '')));
        $incomingQty = isset($itemData['quantity']) ? (float) $itemData['quantity'] : null;

        // Prioritas 0: Cocokkan langsung lewat nomor dokumen GRS 105 (paling akurat untuk pengiriman parsial)
        if ($matDoc105 !== '' && isset($lookups['grs_105'][$matDoc105])) {
            $entry = $lookups['grs_105'][$matDoc105];

            return [
                'delivery_order_receipt_id' => $entry['delivery_order_receipt_id'],
                'delivery_order_receipt_detail_id' => $itemNo > 0 ? ($entry['details_by_item'][$itemNo] ?? null) : null,
            ];
        }

        // Prioritas 1: Cocokkan PO Number & Item No, disambiguasi bila ada beberapa DO parsial
        if ($poNumber !== '' && $itemNo > 0 && ! empty($lookups['poi_exact'][$poNumber.'_'.$itemNo])) {
            $candidates = $lookups['poi_exact'][$poNumber.'_'.$itemNo];

            // 1a. Cocokkan Nomor DO referensi (mis. DO 63515 vs DO 52136602 pada PO 5300063515 item 20)
            if ($referenceDoNo !== '') {
                foreach ($candidates as $cand) {
                    if ($cand['delivery_order_no'] !== '' && $cand['delivery_order_no'] === $referenceDoNo) {
                        return [
                            'delivery_order_receipt_id' => $cand['delivery_order_receipt_id'],
                            'delivery_order_receipt_detail_id' => $cand['delivery_order_receipt_detail_id'],
                        ];
                    }
                }
            }

            // 1b. Cocokkan kuantitas tepat jika pengiriman parsial berbeda jumlah (mis. 26 vs 39)
            if ($incomingQty !== null && $incomingQty > 0 && count($candidates) > 1) {
                foreach ($candidates as $cand) {
                    if ($cand['quantity'] !== null && abs($cand['quantity'] - $incomingQty) < 0.001) {
                        return [
                            'delivery_order_receipt_id' => $cand['delivery_order_receipt_id'],
                            'delivery_order_receipt_detail_id' => $cand['delivery_order_receipt_detail_id'],
                        ];
                    }
                }
            }

            // 1c. Fallback ke kandidat pertama yang cocok PO + Item No
            $first = $candidates[0];

            return [
                'delivery_order_receipt_id' => $first['delivery_order_receipt_id'],
                'delivery_order_receipt_detail_id' => $first['delivery_order_receipt_detail_id'],
            ];
        }

        // Prioritas 2: Cocokkan Reference DO ke DeliveryOrderReceipt.delivery_order_no
        if ($referenceDoNo !== '' && isset($lookups['ref_do'][$referenceDoNo])) {
            $entry = $lookups['ref_do'][$referenceDoNo];

            return [
                'delivery_order_receipt_id' => $entry['delivery_order_receipt_id'],
                'delivery_order_receipt_detail_id' => $itemNo > 0 ? ($entry['details_by_item'][$itemNo] ?? null) : null,
            ];
        }

        // Prioritas 3: Cocokkan Kode 103 ke DeliveryOrderReceipt.post_103
        if ($refDoc103 !== '' && isset($lookups['ref_103'][$refDoc103])) {
            $entry = $lookups['ref_103'][$refDoc103];

            return [
                'delivery_order_receipt_id' => $entry['delivery_order_receipt_id'],
                'delivery_order_receipt_detail_id' => $itemNo > 0 ? ($entry['details_by_item'][$itemNo] ?? null) : null,
            ];
        }

        // Prioritas 4: Fallback level PO saja (tanpa detail item)
        if ($poNumber !== '' && isset($lookups['poi_po'][$poNumber])) {
            return $lookups['poi_po'][$poNumber];
        }

        return null;
    }

    /**
     * Mengekstrak baris data dari file CSV atau XLSX.
     *
     * @return array<int, array<int, string>>
     */
    public function extractRowsFromFile(string $filePath): array
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            return [];
        }

        $content = file_get_contents($filePath);
        if ($content === false || trim($content) === '') {
            return [];
        }

        // Cek apakah file adalah XLSX (ZIP binary header PK\x03\x04)
        if (str_starts_with($content, "PK\x03\x04")) {
            return $this->extractRowsFromXlsx($filePath);
        }

        return $this->extractRowsFromCsv($content);
    }

    /**
     * Membaca file CSV dengan auto-detection delimiter (, atau ;).
     *
     * @return array<int, array<int, string>>
     */
    protected function extractRowsFromCsv(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if (empty($lines)) {
            return [];
        }

        // Deteksi delimiter dari baris pertama
        $firstLine = $lines[0];
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        $rows = [];
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        while (($data = fgetcsv($stream, 0, $delimiter)) !== false) {
            $rows[] = array_map('trim', $data);
        }

        fclose($stream);

        return $rows;
    }

    /**
     * Membaca file XLSX menggunakan ZipArchive native PHP tanpa dependensi berat.
     *
     * @return array<int, array<int, string>>
     */
    protected function extractRowsFromXlsx(string $filePath): array
    {
        if (! class_exists(\ZipArchive::class)) {
            return [];
        }

        $zip = new \ZipArchive;
        if ($zip->open($filePath) !== true) {
            return [];
        }

        // Shared strings
        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if (is_string($sharedXml) && $sharedXml !== '') {
            $xml = @simplexml_load_string($sharedXml);
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

        // Ambil worksheet pertama (sheet1.xml)
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (! is_string($sheetXml) || $sheetXml === '') {
            $zip->close();

            return [];
        }

        $xml = @simplexml_load_string($sheetXml);
        $zip->close();

        if ($xml === false || ! isset($xml->sheetData->row)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $rowElem) {
            $currentRow = [];
            $colIndex = 0;

            foreach ($rowElem->c as $cell) {
                $ref = (string) $cell['r'];
                preg_match('/([A-Z]+)(\d+)/', $ref, $matches);
                $colLetters = $matches[1] ?? '';
                $targetCol = $this->columnLettersToNumber($colLetters);

                // Isi sel kosong jika ada gap kolom
                while ($colIndex < $targetCol) {
                    $currentRow[$colIndex] = '';
                    $colIndex++;
                }

                $val = '';
                $type = (string) $cell['t'];

                if ($type === 's') {
                    $idx = (int) $cell->v;
                    $val = $sharedStrings[$idx] ?? '';
                } elseif (isset($cell->v)) {
                    $val = (string) $cell->v;
                }

                $currentRow[$colIndex] = trim($val);
                $colIndex++;
            }

            $rows[] = $currentRow;
        }

        return $rows;
    }

    protected function columnLettersToNumber(string $letters): int
    {
        $num = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $num = $num * 26 + (ord($letters[$i]) - 64);
        }

        return max(0, $num - 1);
    }

    /**
     * Memetakan nama kolom SAP MB51 ke index array.
     *
     * @param  array<int, string>  $row
     * @return array<string, int>
     */
    protected function identifyHeaderColumns(array $row): array
    {
        $map = [];

        foreach ($row as $idx => $colName) {
            $normalized = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $colName)));

            if (in_array($normalized, ['postingdate', 'postingdt', 'tglposting', 'tglpenerimaan'])) {
                $map['posting_date'] = $idx;
            } elseif (in_array($normalized, ['material', 'materialno', 'kodematerial', 'materialcode'])) {
                $map['material_code'] = $idx;
            } elseif (in_array($normalized, ['materialdescription', 'matdesc', 'deskripsi', 'description'])) {
                $map['description'] = $idx;
            } elseif (in_array($normalized, ['item', 'itemno', 'noitem', 'poitem'])) {
                $map['item_no'] = $idx;
            } elseif (in_array($normalized, ['purchaseorder', 'pono', 'nomorpo', 'purchaseorderno'])) {
                $map['po_number'] = $idx;
            } elseif (in_array($normalized, ['referencedocument', 'refdoc', 'kode103', 'refdocument'])) {
                $map['ref_doc_103'] = $idx;
            } elseif (in_array($normalized, ['materialdocument', 'matdoc', 'kode105', 'matdocument'])) {
                $map['mat_doc_105'] = $idx;
            } elseif (in_array($normalized, ['materialdocyear', 'matdocyear', 'year', 'tahun'])) {
                $map['doc_year'] = $idx;
            } elseif (in_array($normalized, ['qtyinunofentry', 'qty', 'quantity', 'jumlah'])) {
                $map['quantity'] = $idx;
            } elseif (in_array($normalized, ['unitofentry', 'uom', 'satuan', 'baseunitofmeasure'])) {
                $map['uom'] = $idx;
            } elseif (in_array($normalized, ['amountinlc', 'amount', 'totalamount', 'nilai'])) {
                $map['amount'] = $idx;
            } elseif (in_array($normalized, ['storagelocation', 'sloc', 'storloc', 'lokasi'])) {
                $map['storage_location'] = $idx;
            } elseif (in_array($normalized, ['plant'])) {
                $map['plant'] = $idx;
            } elseif (in_array($normalized, ['vendor', 'supplier', 'vendorcode'])) {
                $map['vendor_code'] = $idx;
            } elseif (in_array($normalized, ['reference', 'suratjalan', 'dono', 'referensi'])) {
                $map['reference_do_no'] = $idx;
            } elseif (in_array($normalized, ['documentheadertext', 'text', 'storage'])) {
                $map['storage_text'] = $idx;
            }
        }

        return $map;
    }

    /**
     * Mem-parsing satu baris data MB51 menjadi array terstruktur.
     * Mengembalikan null jika baris adalah ringkasan / kosong.
     *
     * @param  array<int, string>  $row
     * @param  array<string, int>  $headerMap
     * @return array<string, mixed>|null
     */
    protected function parseRow(array $row, array $headerMap): ?array
    {
        $poNumber = isset($headerMap['po_number']) ? trim($row[$headerMap['po_number']] ?? '') : '';
        $matDoc105 = isset($headerMap['mat_doc_105']) ? trim($row[$headerMap['mat_doc_105']] ?? '') : '';
        $postingDateStr = isset($headerMap['posting_date']) ? trim($row[$headerMap['posting_date']] ?? '') : '';

        // Abaikan baris ringkasan (footer totals) atau baris kosong
        if ($poNumber === '' || $matDoc105 === '' || $postingDateStr === '') {
            return null;
        }

        // Parsing tanggal (mendukung format tanggal standar dan nomor serial Excel)
        if (is_numeric($postingDateStr) && (float) $postingDateStr > 20000 && (float) $postingDateStr < 70000) {
            $postingDate = Carbon::createFromTimestampUTC(((int) $postingDateStr - 25569) * 86400)->toDateString();
        } else {
            try {
                $postingDate = Carbon::parse(str_replace(['.', '/'], '-', $postingDateStr))->toDateString();
            } catch (\Throwable $e) {
                return null;
            }
        }

        $itemNo = isset($headerMap['item_no']) ? trim($row[$headerMap['item_no']] ?? '10') : '10';
        $materialCode = isset($headerMap['material_code']) ? trim($row[$headerMap['material_code']] ?? '') : '';
        $description = isset($headerMap['description']) ? trim($row[$headerMap['description']] ?? '') : '';
        $refDoc103 = isset($headerMap['ref_doc_103']) ? trim($row[$headerMap['ref_doc_103']] ?? '') : '';
        $docYear = isset($headerMap['doc_year']) ? trim($row[$headerMap['doc_year']] ?? '') : Carbon::parse($postingDate)->format('Y');

        // Kuantitas & Nilai
        $qtyStr = isset($headerMap['quantity']) ? str_replace(',', '', trim($row[$headerMap['quantity']] ?? '1')) : '1';
        $quantity = (float) $qtyStr;

        $uom = isset($headerMap['uom']) ? trim($row[$headerMap['uom']] ?? 'EA') : 'EA';

        $amountStr = isset($headerMap['amount']) ? str_replace(',', '', trim($row[$headerMap['amount']] ?? '0')) : '0';
        $amount = (float) $amountStr;

        $storageLocation = isset($headerMap['storage_location']) ? trim($row[$headerMap['storage_location']] ?? '') : null;
        $plant = isset($headerMap['plant']) ? trim($row[$headerMap['plant']] ?? '') : null;
        $vendorCode = isset($headerMap['vendor_code']) ? trim($row[$headerMap['vendor_code']] ?? '') : null;
        $referenceDoNo = isset($headerMap['reference_do_no']) ? trim($row[$headerMap['reference_do_no']] ?? '') : null;
        $storageText = isset($headerMap['storage_text']) ? trim($row[$headerMap['storage_text']] ?? '') : null;

        return [
            'posting_date' => $postingDate,
            'material_code' => $materialCode !== '' ? $materialCode : null,
            'description' => $description !== '' ? $description : 'Material Tanpa Deskripsi',
            'po_number' => $poNumber,
            'item_no' => $itemNo,
            'ref_doc_103' => $refDoc103 !== '' ? $refDoc103 : null,
            'mat_doc_105' => $matDoc105,
            'doc_year' => $docYear,
            'quantity' => $quantity,
            'uom' => $uom !== '' ? $uom : 'EA',
            'amount' => $amount,
            'storage_location' => $storageLocation,
            'plant' => $plant,
            'vendor_code' => $vendorCode,
            'reference_do_no' => $referenceDoNo,
            'storage_text' => $storageText,
            'status' => 'pending',
        ];
    }

    /**
     * Mencocokkan data MB51 ke data Penerimaan Delivery Order di database.
     *
     * @param  array<string, mixed>  $itemData
     * @return array{delivery_order_receipt_id: ?int, delivery_order_receipt_detail_id: ?int}|null
     */
    public function matchToDeliveryOrder(array $itemData): ?array
    {
        $poNumber = $itemData['po_number'] ?? '';
        $itemNo = (int) ($itemData['item_no'] ?? 0);
        $refDoc103 = $itemData['ref_doc_103'] ?? '';
        $referenceDoNo = $itemData['reference_do_no'] ?? '';

        // Prioritas 1: Cocokkan PO Number & Item No via PurchaseOrderIssued
        if ($poNumber !== '') {
            $poi = PurchaseOrderIssued::where('purchase_order_no', $poNumber)
                ->when($itemNo > 0, fn ($q) => $q->where('item_no', $itemNo))
                ->first();

            if ($poi) {
                $detail = DeliveryOrderReceiptDetail::where('purchase_order_issued_id', $poi->id)->first();
                if ($detail) {
                    return [
                        'delivery_order_receipt_id' => $detail->delivery_order_receipt_id,
                        'delivery_order_receipt_detail_id' => $detail->id,
                    ];
                }
            }
        }

        // Prioritas 2: Cocokkan Kode 103 ke DeliveryOrderReceipt.post_103
        if ($refDoc103 !== '') {
            $doReceipt = DeliveryOrderReceipt::where('post_103', $refDoc103)->first();
            if ($doReceipt) {
                $firstDetail = $doReceipt->deliveryOrderReceiptDetails()->first();

                return [
                    'delivery_order_receipt_id' => $doReceipt->id,
                    'delivery_order_receipt_detail_id' => $firstDetail?->id,
                ];
            }
        }

        // Prioritas 3: Cocokkan Reference DO ke DeliveryOrderReceipt.delivery_order_no
        if ($referenceDoNo !== '') {
            $doReceipt = DeliveryOrderReceipt::where('delivery_order_no', $referenceDoNo)->first();
            if ($doReceipt) {
                $firstDetail = $doReceipt->deliveryOrderReceiptDetails()->first();

                return [
                    'delivery_order_receipt_id' => $doReceipt->id,
                    'delivery_order_receipt_detail_id' => $firstDetail?->id,
                ];
            }
        }

        return null;
    }
}
