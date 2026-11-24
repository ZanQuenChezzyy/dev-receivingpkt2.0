<?php

namespace App\Services;

use App\Models\DeliveryOrderReceipt;
use App\Models\GrsRdtv;
use App\Models\GrsRdtvItem;
use App\Models\InvoicingReceipt;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GrsRdtvProcessingService
{
    public function __construct(
        public InvoicingAssignmentService $assignmentService,
        public GrsPdfParserService $pdfParser,
    ) {}

    /**
     * Validasi daftar file GRS atau item RDTV sebelum disimpan.
     *
     * @param  array<int, mixed>  $files
     * @param  array<int, array<string, mixed>>  $items
     * @return array{
     *     has_errors: bool,
     *     error_body: ?string,
     *     uploaded_files: array<int, UploadedFile>,
     *     uploaded_items: array<int, array<string, mixed>>
     * }
     */
    public function validateUploads(string $category, array $files = [], array $items = []): array
    {
        $invalidDocuments = [];
        $alreadyProcessed = [];
        $duplicateDocuments = [];
        $unposted103Documents = [];
        $readyDocuments = [];
        $notFoundDocuments = [];
        $seen = [];

        $uniqueFiles = [];
        $uniqueItems = [];

        if ($category === 'GRS') {
            foreach ($files as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $documentCode = $this->extractDocumentCode($file);
                if (in_array($documentCode, $seen, true)) {
                    $duplicateDocuments[] = $documentCode;

                    continue;
                }
                $seen[] = $documentCode;

                $this->validateSingleDocument(
                    $documentCode,
                    $category,
                    $file,
                    $alreadyProcessed,
                    $unposted103Documents,
                    $invalidDocuments,
                    $readyDocuments,
                    $notFoundDocuments,
                );

                $uniqueFiles[] = $file;
            }
        } elseif ($category === 'RDTV') {
            foreach ($items as $item) {
                $rawFile = $item['file'] ?? null;
                $file = is_array($rawFile) ? (array_values($rawFile)[0] ?? null) : $rawFile;

                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $documentCode = $this->extractDocumentCode($file);
                if (in_array($documentCode, $seen, true)) {
                    $duplicateDocuments[] = $documentCode;

                    continue;
                }
                $seen[] = $documentCode;

                $this->validateSingleDocument(
                    $documentCode,
                    $category,
                    $file,
                    $alreadyProcessed,
                    $unposted103Documents,
                    $invalidDocuments,
                    $readyDocuments,
                    $notFoundDocuments,
                );

                $uniqueItems[] = [
                    'file' => $file,
                    'reason' => $item['reason'] ?? null,
                ];
            }
        }

        $errors = [];
        if (! empty($unposted103Documents)) {
            $unposted103Unique = array_unique($unposted103Documents);
            $errors[] = '<b>Belum Post 103 ('.count($unposted103Unique).'):</b><br>&bull; '.implode('<br>&bull; ', $unposted103Unique);
        }
        if (! empty($invalidDocuments)) {
            $invalidUnique = array_unique($invalidDocuments);
            $errors[] = '<b>Belum kembali dari QC ('.count($invalidUnique).'):</b><br>&bull; '.implode('<br>&bull; ', $invalidUnique);
        }
        if (! empty($alreadyProcessed)) {
            $alreadyProcessedUnique = array_unique($alreadyProcessed);
            $errors[] = '<b>Sudah sukses diupload sebagai GRS sebelumnya ('.count($alreadyProcessedUnique).'):</b><br>&bull; '.implode('<br>&bull; ', $alreadyProcessedUnique);
        }
        if (! empty($duplicateDocuments)) {
            $duplicateUnique = array_unique($duplicateDocuments);
            $errors[] = '<b>Terdeteksi duplikat file yang sama ('.count($duplicateUnique).'):</b><br>&bull; '.implode('<br>&bull; ', $duplicateUnique);
        }

        if (! empty($errors)) {
            $infoMessages = [];
            if (! empty($readyDocuments)) {
                $readyUnique = array_unique($readyDocuments);
                $infoMessages[] = '<b>Siap Diproses / Belum Terupload di GRS dan RDTV ('.count($readyUnique).'):</b><br>&bull; '.implode('<br>&bull; ', $readyUnique);
            }
            if (! empty($notFoundDocuments)) {
                $notFoundUnique = array_unique($notFoundDocuments);
                $infoMessages[] = '<b>Tidak Ditemukan di Sistem ('.count($notFoundUnique).'):</b><br>&bull; '.implode('<br>&bull; ', $notFoundUnique);
            }

            return [
                'has_errors' => true,
                'error_body' => implode('<br><br>', array_merge($errors, $infoMessages)),
                'uploaded_files' => [],
                'uploaded_items' => [],
            ];
        }

        return [
            'has_errors' => false,
            'error_body' => null,
            'uploaded_files' => $uniqueFiles,
            'uploaded_items' => $uniqueItems,
        ];
    }

    /**
     * Proses penyimpanan file fisik, pembuatan GrsRdtvItem, pembaruan status DO, dan pembuatan InvoicingReceipt.
     *
     * @param  array<int, UploadedFile>  $uploadedFiles
     * @param  array<int, array{file: UploadedFile, reason: ?string}>  $uploadedItems
     * @return array{matched: array<int, string>, not_found: array<int, string>, processed_count: int}
     */
    public function processUploads(GrsRdtv $grsRdtv, array $uploadedFiles = [], array $uploadedItems = []): array
    {
        $category = $grsRdtv->category;
        $matchedDocs = [];
        $notFoundDocs = [];

        if ($category === 'GRS' && ! empty($uploadedFiles)) {
            foreach ($uploadedFiles as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $documentCode = $this->extractDocumentCode($file);
                $path = $this->storeDocumentFile($file, $documentCode);
                $do = DeliveryOrderReceipt::where('document_code', $documentCode)->first();

                if ($do) {
                    $updateDo = ['status' => $category];
                    if ($do->delay_reason === 'RDTV') {
                        $updateDo['delay_reason'] = null;
                        $updateDo['delay_notes'] = null;
                    }
                    $do->update($updateDo);
                    $matchedDocs[] = $documentCode;
                } else {
                    $notFoundDocs[] = $documentCode;
                }

                $grsRdtvItem = $grsRdtv->grsRdtvItems()->create([
                    'delivery_order_receipt_id' => $do?->id,
                    'document_code' => $documentCode,
                    'file_path' => $path,
                    'status' => $do ? 'Matched' : 'Not Found',
                    'reason' => null,
                ]);

                $this->createInvoicingReceiptFor($grsRdtvItem, $do, $category, $grsRdtv->transaction_date);
            }
        }

        if ($category === 'RDTV' && ! empty($uploadedItems)) {
            foreach ($uploadedItems as $item) {
                $rawFile = $item['file'] ?? null;
                $file = is_array($rawFile) ? (array_values($rawFile)[0] ?? null) : $rawFile;
                $reason = $item['reason'] ?? null;

                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $documentCode = $this->extractDocumentCode($file);
                $path = $this->storeDocumentFile($file, $documentCode);
                $do = DeliveryOrderReceipt::where('document_code', $documentCode)->first();

                if ($do) {
                    $do->update([
                        'status' => $category,
                        'delay_reason' => 'RDTV',
                        'delay_notes' => $reason,
                    ]);
                    $matchedDocs[] = $documentCode;
                } else {
                    $notFoundDocs[] = $documentCode;
                }

                $grsRdtvItem = $grsRdtv->grsRdtvItems()->create([
                    'delivery_order_receipt_id' => $do?->id,
                    'document_code' => $documentCode,
                    'file_path' => $path,
                    'status' => $do ? 'Matched' : 'Not Found',
                    'reason' => $reason,
                ]);

                $this->createInvoicingReceiptFor($grsRdtvItem, $do, $category, $grsRdtv->transaction_date);
            }
        }

        return [
            'matched' => $matchedDocs,
            'not_found' => $notFoundDocs,
            'processed_count' => count($matchedDocs) + count($notFoundDocs),
        ];
    }

    /**
     * Mengganti file PDF pada GrsRdtvItem yang sudah ada tanpa menghapus data InvoicingReceipt terkait.
     */
    public function replaceItemFile(GrsRdtvItem $item, UploadedFile $file, ?string $reason = null): GrsRdtvItem
    {
        $oldPath = $item->file_path;
        $newPath = $this->storeDocumentFile($file, $item->document_code);

        $updateItem = ['file_path' => $newPath];
        if ($reason !== null) {
            $updateItem['reason'] = $reason;
        }

        // Jika sebelumnya Not Found / belum tertaut ke DO, cek ulang apakah DO kini sudah tersedia
        $linkedDo = $item->deliveryOrderReceipt;
        if (! $linkedDo) {
            $linkedDo = DeliveryOrderReceipt::where('document_code', $item->document_code)->first();
            if ($linkedDo) {
                $updateItem['delivery_order_receipt_id'] = $linkedDo->id;
                $updateItem['status'] = 'Matched';
            }
        }

        $item->update($updateItem);

        // Hapus file fisik lama secara aman (hanya jika tidak dipakai item lain)
        if (! empty($oldPath) && $oldPath !== $newPath) {
            GrsRdtvItem::deletePhysicalFileSafely($oldPath, $item->id);
        }

        // Parsing ulang PDF baru dan sinkronkan metadata ke InvoicingReceipt terkait
        $item->refresh()->loadMissing(['grsRdtv', 'deliveryOrderReceipt', 'invoicingReceipt']);
        $category = $item->grsRdtv?->category ?? 'GRS';
        $isRdtv = $category === 'RDTV';
        $parsedPdf = $this->parsePdfIfAvailable($newPath);

        if ($isRdtv && $reason !== null && $item->deliveryOrderReceipt) {
            $item->deliveryOrderReceipt->update([
                'delay_reason' => 'RDTV',
                'delay_notes' => $reason,
            ]);
        }

        $invoicingReceipt = $item->invoicingReceipt;
        if (! $invoicingReceipt && $item->deliveryOrderReceipt) {
            $this->createInvoicingReceiptFor(
                $item,
                $item->deliveryOrderReceipt,
                $category,
                $item->grsRdtv?->transaction_date,
            );

            return $item->fresh();
        }

        if ($invoicingReceipt) {
            $invUpdates = [];

            // Jika dokumen_verifikasi_grs masih mengarah ke slip lama (belum diunggah dokumen verifikasi terpisah), perbarui ke path baru
            if (empty($invoicingReceipt->dokumen_verifikasi_grs) || $invoicingReceipt->dokumen_verifikasi_grs === $oldPath) {
                $invUpdates['dokumen_verifikasi_grs'] = $newPath;
            }

            if ($parsedPdf) {
                if (! $isRdtv && ! empty($parsedPdf['no_grs'])) {
                    $invUpdates['no_grs'] = $parsedPdf['no_grs'];
                }
                if ($isRdtv && ! empty($parsedPdf['no_rdtv'])) {
                    $invUpdates['no_rdtv'] = $parsedPdf['no_rdtv'];
                }
                if (! $isRdtv && ! empty($parsedPdf['tanggal_release_gr'])) {
                    $invUpdates['tanggal_terima_grs'] = $parsedPdf['tanggal_release_gr'];
                }
                if ($isRdtv && ! empty($parsedPdf['tanggal_penolakan'])) {
                    $invUpdates['tanggal_terima_grs'] = $parsedPdf['tanggal_penolakan'];
                }
                if (! empty($parsedPdf['delivery_date'])) {
                    $invUpdates['delivery_date'] = $parsedPdf['delivery_date'];
                }
                if (! empty($parsedPdf['tanggal_penerimaan'])) {
                    $invUpdates['tanggal_penerimaan'] = $parsedPdf['tanggal_penerimaan'];
                }
                if (! empty($parsedPdf['tanggal_po'])) {
                    $invUpdates['tanggal_po'] = $parsedPdf['tanggal_po'];
                }
                if (empty($invoicingReceipt->no_do) && ! empty($parsedPdf['no_do'])) {
                    $invUpdates['no_do'] = $parsedPdf['no_do'];
                }
            }

            if ($invUpdates !== []) {
                $invoicingReceipt->update($invUpdates);
            }
        }

        return $item->fresh();
    }

    /**
     * Menyimpan berkas dengan nama unik berversi untuk mencegah tabrakan file fisik maupun cache CDN/browser.
     */
    public function storeDocumentFile(UploadedFile $file, string $documentCode): string
    {
        $safeCode = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim($documentCode)) ?: 'document';
        $safeCode = trim($safeCode, '-');
        if ($safeCode === '') {
            $safeCode = 'document';
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'pdf');
        $uniqueName = sprintf(
            '%s__%s_%s.%s',
            $safeCode,
            now()->format('YmdHis'),
            Str::lower(Str::random(6)),
            $extension
        );

        return $file->storeAs('grs-rdtv-docs', $uniqueName, 'public');
    }

    /**
     * Ekstrak kode dokumen dari nama file asli yang diunggah.
     */
    public function extractDocumentCode(UploadedFile $file): string
    {
        return trim((string) pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
    }

    /**
     * Cari atau buat header transaksi GrsRdtv secara aman berdasarkan tanggal dan kategori.
     */
    public function findOrCreateBatch(string $transactionDate, string $category, ?int $createdBy = null): GrsRdtv
    {
        $normalizedDate = Carbon::parse($transactionDate)->toDateString();

        $existing = GrsRdtv::query()
            ->whereDate('transaction_date', $normalizedDate)
            ->where('category', $category)
            ->first();

        if ($existing) {
            return $existing;
        }

        return GrsRdtv::create([
            'transaction_date' => $normalizedDate,
            'category' => $category,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Validasi satu dokumen terhadap data DeliveryOrderReceipt, QC, Post 103, dan GRS yang aktif.
     *
     * @param  array<int, string>  $alreadyProcessed
     * @param  array<int, string>  $unposted103Documents
     * @param  array<int, string>  $invalidDocuments
     * @param  array<int, string>  $readyDocuments
     * @param  array<int, string>  $notFoundDocuments
     */
    protected function validateSingleDocument(
        string $documentCode,
        string $category,
        UploadedFile $file,
        array &$alreadyProcessed,
        array &$unposted103Documents,
        array &$invalidDocuments,
        array &$readyDocuments,
        array &$notFoundDocuments,
    ): void {
        $do = DeliveryOrderReceipt::where('document_code', $documentCode)->first();
        if (! $do) {
            $notFoundDocuments[] = $documentCode;

            return;
        }

        $hasError = false;

        // 1. Bersihkan item yatim (tanpa parent GrsRdtv) atau item yang berkas fisiknya sudah tidak ada di storage
        $allDoItems = $do->grsRdtvItems()->with('grsRdtv')->get();
        foreach ($allDoItems as $existingItem) {
            $missingParent = $existingItem->grsRdtv === null;
            $missingFile = empty($existingItem->file_path) || ! Storage::disk('public')->exists($existingItem->file_path);

            if ($missingParent || $missingFile) {
                $existingItem->delete();
            }
        }

        // 2. Hitung ulang item GRS aktif yang benar-benar valid beserta nomor GRS masing-masing
        $activeGrsItems = $do->grsRdtvItems()
            ->with(['grsRdtv', 'invoicingReceipt'])
            ->whereHas('grsRdtv', fn ($q) => $q->where('category', 'GRS'))
            ->get();

        $grsCount = $activeGrsItems->count();

        // 3. Sinkronkan status DO jika status masih GRS/RDTV padahal tidak ada item aktif
        $activeRdtvCount = $do->grsRdtvItems()
            ->whereHas('grsRdtv', fn ($q) => $q->where('category', 'RDTV'))
            ->count();

        if ($grsCount === 0 && $activeRdtvCount === 0 && in_array($do->status, ['GRS', 'RDTV'], true)) {
            GrsRdtvItem::syncDeliveryOrderStatus($do);
            $do->refresh();
        }

        $existingDatesInfo = $activeGrsItems
            ->map(fn (GrsRdtvItem $it) => $it->grsRdtv?->transaction_date ? Carbon::parse($it->grsRdtv->transaction_date)->format('d/m/Y') : null)
            ->filter()
            ->unique()
            ->implode(', ');

        $alreadyLabel = $existingDatesInfo !== ''
            ? "{$documentCode} (Tgl: {$existingDatesInfo})"
            : $documentCode;

        if ($do->receipt_mode === 'Termin') {
            $totalTermins = $do->termins()->count();
            $postedTermins = $do->termins()->whereNotNull('post_103')->count();

            if ($totalTermins > 0 && $grsCount >= $totalTermins) {
                $alreadyProcessed[] = $alreadyLabel;
                $hasError = true;
            } elseif ($postedTermins <= $grsCount) {
                $unposted103Documents[] = $documentCode;
                $hasError = true;
            } else {
                $isZrmZsmOrZpm = $do->deliveryOrderReceiptDetails()->whereIn('material_type', ['ZRM', 'ZSM', 'ZPM'])->exists();
                if (! $isZrmZsmOrZpm) {
                    $qcKembaliCount = $do->qcHistories()->where('status', 'Kembali')->count();
                    if ($qcKembaliCount <= $grsCount) {
                        $invalidDocuments[] = $documentCode;
                        $hasError = true;
                    }
                }
            }
        } else {
            if ($grsCount >= 1) {
                // Izinkan unggah GRS parsial pada DO yang sudah GRS bila PDF memiliki No. GRS yang berbeda dari seluruh GRS sebelumnya
                $isDistinctPartialGrs = false;
                if ($category === 'GRS') {
                    $uploadedNoGrs = $this->extractNoGrsFromUploadedFile($file);
                    $existingGrsNumbers = $activeGrsItems
                        ->flatMap(function (GrsRdtvItem $it): array {
                            $raw = trim((string) ($it->invoicingReceipt?->no_grs ?? ''));
                            if ($raw === '') {
                                return [];
                            }

                            return array_values(array_filter(array_map('trim', explode(',', $raw))));
                        })
                        ->unique()
                        ->all();

                    if ($uploadedNoGrs !== null && $existingGrsNumbers !== [] && ! in_array($uploadedNoGrs, $existingGrsNumbers, true)) {
                        $isDistinctPartialGrs = true;
                    }
                }

                if (! $isDistinctPartialGrs) {
                    $alreadyProcessed[] = $alreadyLabel;
                    $hasError = true;
                }
            }

            if (is_null($do->post_103)) {
                $unposted103Documents[] = $documentCode;
                $hasError = true;
            } else {
                $isZrmZsmOrZpm = $do->deliveryOrderReceiptDetails()->whereIn('material_type', ['ZRM', 'ZSM', 'ZPM'])->exists();
                if (! $isZrmZsmOrZpm) {
                    $latestQc = $do->qcHistories()->latest('id')->first();
                    if (! $latestQc || $latestQc->status !== 'Kembali') {
                        $invalidDocuments[] = $documentCode;
                        $hasError = true;
                    }
                }
            }
        }

        if (! $hasError) {
            $readyDocuments[] = $documentCode;
        }
    }

    /**
     * Mengekstrak No. GRS dari file PDF yang baru diunggah (untuk mendeteksi GRS parsial berbeda pada DO/PO yang sama).
     */
    protected function extractNoGrsFromUploadedFile(UploadedFile $file): ?string
    {
        $realPath = $file->getRealPath();
        if (! $realPath || ! is_file($realPath)) {
            return null;
        }

        try {
            $parsed = $this->pdfParser->parse($realPath);
            $noGrs = trim((string) ($parsed['no_grs'] ?? ''));

            return $noGrs !== '' ? $noGrs : null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Memeriksa kelayakan 1 kode dokumen terhadap DeliveryOrderReceipt (Post 103, QC Kembali, duplikat GRS vs parsial berbeda)
     * tanpa mengubah status database, untuk kebutuhan Pre-Check upload gabungan.
     *
     * @return array{
     *     status: 'ready'|'warning'|'blocked',
     *     reason_code: ?string,
     *     message: string,
     *     do: ?DeliveryOrderReceipt,
     *     is_distinct_partial: bool
     * }
     */
    public function inspectDocumentEligibility(
        string $documentCode,
        string $category,
        ?string $uploadedNoGrs = null,
    ): array {
        $do = DeliveryOrderReceipt::with([
            'deliveryOrderReceiptDetails.purchaseOrderIssued',
            'grsRdtvItems.grsRdtv',
            'grsRdtvItems.invoicingReceipt',
            'qcHistories',
            'termins',
        ])->where('document_code', trim($documentCode))->first();

        if (! $do) {
            return [
                'status' => 'warning',
                'reason_code' => 'not_found',
                'message' => "Kode DO {$documentCode} tidak ditemukan di Penerimaan Barang (tetap bisa tersimpan sebagai Not Found, tanpa Invoice Barang otomatis).",
                'do' => null,
                'is_distinct_partial' => false,
            ];
        }

        $activeGrsItems = $do->grsRdtvItems->filter(function (GrsRdtvItem $it): bool {
            if ($it->grsRdtv === null || $it->grsRdtv->category !== 'GRS') {
                return false;
            }

            return ! empty($it->file_path) && Storage::disk('public')->exists($it->file_path);
        });

        $grsCount = $activeGrsItems->count();
        $existingDatesInfo = $activeGrsItems
            ->map(fn (GrsRdtvItem $it) => $it->grsRdtv?->transaction_date ? Carbon::parse($it->grsRdtv->transaction_date)->format('d/m/Y') : null)
            ->filter()
            ->unique()
            ->implode(', ');
        $alreadyLabel = $existingDatesInfo !== ''
            ? "Sudah pernah diunggah sebagai GRS (Tgl: {$existingDatesInfo})."
            : 'Sudah pernah diunggah sebagai GRS sebelumnya.';

        $isDistinctPartial = false;

        if ($do->receipt_mode === 'Termin') {
            $totalTermins = $do->termins->count();
            $postedTermins = $do->termins->whereNotNull('post_103')->count();

            if ($totalTermins > 0 && $grsCount >= $totalTermins) {
                return [
                    'status' => 'blocked',
                    'reason_code' => 'already_processed',
                    'message' => $alreadyLabel,
                    'do' => $do,
                    'is_distinct_partial' => false,
                ];
            }

            if ($postedTermins <= $grsCount) {
                return [
                    'status' => 'blocked',
                    'reason_code' => 'unposted_103',
                    'message' => 'Belum Post 103 untuk termin berikutnya.',
                    'do' => $do,
                    'is_distinct_partial' => false,
                ];
            }

            $isZrmZsmOrZpm = $do->deliveryOrderReceiptDetails->whereIn('material_type', ['ZRM', 'ZSM', 'ZPM'])->isNotEmpty();
            if (! $isZrmZsmOrZpm) {
                $qcKembaliCount = $do->qcHistories->where('status', 'Kembali')->count();
                if ($qcKembaliCount <= $grsCount) {
                    return [
                        'status' => 'blocked',
                        'reason_code' => 'qc_not_returned',
                        'message' => 'Belum kembali dari QC untuk termin berikutnya.',
                        'do' => $do,
                        'is_distinct_partial' => false,
                    ];
                }
            }
        } else {
            if ($grsCount >= 1) {
                if ($category === 'GRS' && filled($uploadedNoGrs)) {
                    $existingGrsNumbers = $activeGrsItems
                        ->flatMap(function (GrsRdtvItem $it): array {
                            $raw = trim((string) ($it->invoicingReceipt?->no_grs ?? ''));
                            if ($raw === '') {
                                return [];
                            }

                            return array_values(array_filter(array_map('trim', explode(',', $raw))));
                        })
                        ->unique()
                        ->all();

                    if ($existingGrsNumbers !== [] && ! in_array(trim((string) $uploadedNoGrs), $existingGrsNumbers, true)) {
                        $isDistinctPartial = true;
                    }
                }

                if (! $isDistinctPartial) {
                    return [
                        'status' => 'blocked',
                        'reason_code' => 'already_processed',
                        'message' => $alreadyLabel,
                        'do' => $do,
                        'is_distinct_partial' => false,
                    ];
                }
            }

            if (is_null($do->post_103)) {
                return [
                    'status' => 'blocked',
                    'reason_code' => 'unposted_103',
                    'message' => 'Belum Post 103.',
                    'do' => $do,
                    'is_distinct_partial' => false,
                ];
            }

            $isZrmZsmOrZpm = $do->deliveryOrderReceiptDetails->whereIn('material_type', ['ZRM', 'ZSM', 'ZPM'])->isNotEmpty();
            if (! $isZrmZsmOrZpm) {
                $latestQc = $do->qcHistories->sortByDesc('id')->first();
                if (! $latestQc || $latestQc->status !== 'Kembali') {
                    return [
                        'status' => 'blocked',
                        'reason_code' => 'qc_not_returned',
                        'message' => 'Belum kembali dari QC.',
                        'do' => $do,
                        'is_distinct_partial' => false,
                    ];
                }
            }
        }

        return [
            'status' => 'ready',
            'reason_code' => null,
            'message' => $isDistinctPartial
                ? 'Siap diproses (GRS parsial baru dengan No. GRS berbeda).'
                : 'Siap diproses.',
            'do' => $do,
            'is_distinct_partial' => $isDistinctPartial,
        ];
    }

    /**
     * Membuat catatan InvoicingReceipt otomatis dari GrsRdtvItem yang berhasil diproses.
     */
    public function createInvoicingReceiptFor(
        GrsRdtvItem $grsRdtvItem,
        ?DeliveryOrderReceipt $do,
        string $category,
        mixed $transactionDate,
        bool $attachSlipAsFallbackVerif = true,
    ): ?InvoicingReceipt {
        if (! $do) {
            return null;
        }

        $do->loadMissing(['deliveryOrderReceiptDetails.purchaseOrderIssued']);
        $details = $do->deliveryOrderReceiptDetails;
        $firstDetail = $details->first();
        $po = $firstDetail?->purchaseOrderIssued;

        $parsedPdf = $this->parsePdfIfAvailable($grsRdtvItem->file_path);

        $resolvedNoPo = $po?->purchase_order_no ?? ($parsedPdf['no_po'] ?? null);

        $rawMaterialType = $firstDetail?->material_type ?? $po?->material_type;
        $materialType = InvoicingReceipt::normalizeMaterialType($rawMaterialType);

        $materialCodes = $details->isNotEmpty()
            ? $details->pluck('material_code')->filter()->values()->all()
            : array_filter([$po?->material_code]);

        $descriptions = $details->isNotEmpty()
            ? $details->pluck('description')->filter()->values()->all()
            : array_filter([$po?->description, $parsedPdf['nama_barang'] ?? null]);

        $picId = $this->assignmentService->determinePicId(
            $materialType,
            $materialCodes,
            $descriptions,
            $resolvedNoPo,
            $po?->id
        );

        $vendor = null;
        if ($po?->vendor_id || $po?->vendor_name) {
            $vendor = Vendor::where('vendor_number', $po->vendor_id)
                ->orWhere('name', $po->vendor_name)
                ->first();
        }

        $qtyPenagihan = $details->isNotEmpty() ? (float) $details->sum('quantity') : (float) ($po?->qty_po ?? 0);
        $qtyPo = $details->map(fn ($d) => $d->purchaseOrderIssued)->filter()->unique('id')->sum('qty_po');
        if (! $qtyPo && $po) {
            $qtyPo = (float) $po->qty_po;
        }
        $uoi = $firstDetail?->uoi ?? $po?->uoi ?? 'PCS';

        $sumSnapshot = $details->sum('total_amount_snapshot');
        $subtotalExclude = ($details->isNotEmpty() && $sumSnapshot > 0)
            ? (float) $sumSnapshot
            : (float) ($po?->total_amount_in_lc ?? 0);

        if ($subtotalExclude <= 0 && ! empty($parsedPdf['subtotal_exclude_ppn'])) {
            $subtotalExclude = (float) $parsedPdf['subtotal_exclude_ppn'];
        }

        $subtotalInclude = round((float) $subtotalExclude + ((float) $subtotalExclude * 0.11), 2);

        $isRdtv = $category === 'RDTV';

        $noGrs = $category === 'GRS' ? ($parsedPdf['no_grs'] ?? null) : null;
        $noRdtv = $category === 'RDTV' ? ($parsedPdf['no_rdtv'] ?? null) : null;

        if ($category === 'GRS') {
            $tanggalTerimaGrs = ($parsedPdf && ! empty($parsedPdf['tanggal_release_gr']))
                ? $parsedPdf['tanggal_release_gr']
                : ($transactionDate ?? now());
        } else {
            $tanggalTerimaGrs = ($parsedPdf && ! empty($parsedPdf['tanggal_penolakan']))
                ? $parsedPdf['tanggal_penolakan']
                : ($transactionDate ?? now());
        }

        $deliveryDate = $parsedPdf['delivery_date'] ?? null;
        $tanggalPenerimaan = $parsedPdf['tanggal_penerimaan'] ?? null;
        $tanggalPo = $parsedPdf['tanggal_po'] ?? null;

        if ($isRdtv && empty($grsRdtvItem->reason) && ! empty($parsedPdf['alasan_penolakan'])) {
            $grsRdtvItem->update(['reason' => $parsedPdf['alasan_penolakan']]);
            if (empty($do->delay_notes)) {
                $do->update(['delay_notes' => $parsedPdf['alasan_penolakan']]);
            }
        }

        return InvoicingReceipt::create([
            'grs_rdtv_item_id' => $grsRdtvItem->id,
            'delivery_order_receipt_id' => $do->id,
            'purchase_order_issued_id' => $po?->id,
            'vendor_id' => $vendor?->id,
            'pic_id' => $picId,
            'tanggal_terima_grs' => $tanggalTerimaGrs,
            'delivery_date' => $deliveryDate,
            'tanggal_penerimaan' => $tanggalPenerimaan,
            'tanggal_po' => $tanggalPo,
            'jenis' => $category,
            'no_grs' => $noGrs,
            'no_rdtv' => $noRdtv,
            'no_po' => $po?->purchase_order_no ?? ($parsedPdf['no_po'] ?? '-'),
            'no_do' => $do->delivery_order_no ?: ($parsedPdf['no_do'] ?? null),
            'material_type' => $materialType,
            'vendor_name' => $po?->vendor_name ?? ($vendor?->name ?? ($parsedPdf['vendor_name'] ?? null)),
            'deskripsi' => $firstDetail?->description ?? ($po?->description ?? ($parsedPdf['nama_barang'] ?? null)),
            'email_vendor' => is_array($vendor?->email) ? implode(', ', $vendor->email) : $vendor?->email,
            'tanggal_info_grs' => $isRdtv ? null : $tanggalTerimaGrs,
            'nama_barang' => $firstDetail?->description ?? ($po?->description ?? ($parsedPdf['nama_barang'] ?? null)),
            'qty_penagihan' => $qtyPenagihan > 0 ? $qtyPenagihan : ($parsedPdf['qty_penagihan'] ?? 0),
            'qty_po' => $qtyPo > 0 ? $qtyPo : ($parsedPdf['qty_penagihan'] ?? 0),
            'uoi' => $uoi ?: ($parsedPdf['uoi'] ?? 'PCS'),
            'subtotal_exclude_ppn' => $subtotalExclude,
            'subtotal_include_ppn' => $subtotalInclude,
            'tanggal_terima_koreksi_invoice' => $isRdtv ? null : $tanggalTerimaGrs,
            'dokumen_verifikasi_grs' => $attachSlipAsFallbackVerif ? $grsRdtvItem->file_path : null,
            'keterangan' => $isRdtv && ! empty($grsRdtvItem->reason)
                ? "Auto-generate dari RDTV: {$grsRdtvItem->document_code} (Alasan: {$grsRdtvItem->reason})"
                : "Auto-generate dari {$category}: {$grsRdtvItem->document_code} (DO: {$do->delivery_order_no})",
            'status' => 'Menunggu Verifikasi',
        ]);
    }

    /**
     * Ekstrak metadata dari file PDF di disk public jika file tersedia.
     *
     * @return ?array<string, mixed>
     */
    protected function parsePdfIfAvailable(?string $relativePath): ?array
    {
        if (blank($relativePath) || strtolower(pathinfo($relativePath, PATHINFO_EXTENSION)) !== 'pdf') {
            return null;
        }

        $fullPath = Storage::disk('public')->path($relativePath);
        if (! file_exists($fullPath)) {
            return null;
        }

        try {
            return $this->pdfParser->parse($fullPath);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
