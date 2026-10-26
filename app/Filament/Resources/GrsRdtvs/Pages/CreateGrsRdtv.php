<?php

namespace App\Filament\Resources\GrsRdtvs\Pages;

use App\Filament\Resources\GrsRdtvs\GrsRdtvResource;
use App\Models\DeliveryOrderReceipt;
use App\Models\GrsRdtvItem;
use App\Models\InvoicingReceipt;
use App\Models\Vendor;
use App\Services\InvoicingAssignmentService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CreateGrsRdtv extends CreateRecord
{
    protected static string $resource = GrsRdtvResource::class;

    protected array $uploadedFiles = [];

    protected array $uploadedItems = [];

    protected ?InvoicingAssignmentService $assignmentService = null;

    protected function getAssignmentService(): InvoicingAssignmentService
    {
        if (! $this->assignmentService) {
            $this->assignmentService = app(InvoicingAssignmentService::class);
        }

        return $this->assignmentService;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return static::getModel()::firstOrCreate([
            'transaction_date' => $data['transaction_date'],
            'category' => $data['category'],
        ], [
            'created_by' => $data['created_by'] ?? Auth::id(),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $category = $data['category'] ?? 'GRS';
        $invalidDocuments = [];
        $alreadyProcessed = [];
        $duplicateDocuments = [];
        $unposted103Documents = [];
        $readyDocuments = [];
        $notFoundDocuments = [];
        $seen = [];

        // Pindahkan files (GRS) ke property sementara
        if (isset($data['files'])) {
            $uniqueFiles = [];
            foreach ($data['files'] as $file) {
                if ($file instanceof TemporaryUploadedFile) {
                    $originalName = $file->getClientOriginalName();
                    $documentCode = pathinfo($originalName, PATHINFO_FILENAME);

                    if (in_array($documentCode, $seen)) {
                        $duplicateDocuments[] = $documentCode;

                        continue;
                    }
                    $seen[] = $documentCode;

                    $do = DeliveryOrderReceipt::where('document_code', $documentCode)->first();
                    if ($do) {
                        $hasError = false;
                        if ($do->receipt_mode === 'Termin') {
                            $totalTermins = $do->termins()->count();
                            $postedTermins = $do->termins()->whereNotNull('post_103')->count();
                            $grsCount = $do->grsRdtvItems()->whereHas('grsRdtv', fn ($q) => $q->where('category', 'GRS'))->count();

                            if ($grsCount >= $totalTermins) {
                                $alreadyProcessed[] = $documentCode;
                                $hasError = true;
                            } else {
                                if ($postedTermins <= $grsCount) {
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
                            }
                        } else {
                            $grsCount = $do->grsRdtvItems()->whereHas('grsRdtv', fn ($q) => $q->where('category', 'GRS'))->count();

                            if ($grsCount >= 1) {
                                $alreadyProcessed[] = $documentCode;
                                $hasError = true;
                            }

                            if (is_null($do->post_103)) {
                                $unposted103Documents[] = $documentCode;
                                $hasError = true;
                            } else {
                                $isZrmZsmOrZpm = $do->deliveryOrderReceiptDetails()->whereIn('material_type', ['ZRM', 'ZSM', 'ZPM'])->exists();
                                if (! $isZrmZsmOrZpm) {
                                    $latestQc = $do->qcHistories()->latest()->first();
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
                    } else {
                        $notFoundDocuments[] = $documentCode;
                    }
                    $uniqueFiles[] = $file;
                }
            }
            $this->uploadedFiles = $uniqueFiles;
            unset($data['files']);
        }

        // Pindahkan items (RDTV) ke property sementara
        if (isset($data['items'])) {
            $uniqueItems = [];
            foreach ($data['items'] as $item) {
                $file = is_array($item['file']) ? array_values($item['file'])[0] ?? null : $item['file'];
                if ($file instanceof TemporaryUploadedFile) {
                    $originalName = $file->getClientOriginalName();
                    $documentCode = pathinfo($originalName, PATHINFO_FILENAME);

                    if (in_array($documentCode, $seen)) {
                        $duplicateDocuments[] = $documentCode;

                        continue;
                    }
                    $seen[] = $documentCode;

                    $do = DeliveryOrderReceipt::where('document_code', $documentCode)->first();
                    if ($do) {
                        $hasError = false;
                        if ($do->receipt_mode === 'Termin') {
                            $totalTermins = $do->termins()->count();
                            $postedTermins = $do->termins()->whereNotNull('post_103')->count();
                            $grsCount = $do->grsRdtvItems()->whereHas('grsRdtv', fn ($q) => $q->where('category', 'GRS'))->count();

                            if ($grsCount >= $totalTermins) {
                                $alreadyProcessed[] = $documentCode;
                                $hasError = true;
                            } else {
                                if ($postedTermins <= $grsCount) {
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
                            }
                        } else {
                            $grsCount = $do->grsRdtvItems()->whereHas('grsRdtv', fn ($q) => $q->where('category', 'GRS'))->count();

                            if ($grsCount >= 1) {
                                $alreadyProcessed[] = $documentCode;
                                $hasError = true;
                            }

                            if (is_null($do->post_103)) {
                                $unposted103Documents[] = $documentCode;
                                $hasError = true;
                            } else {
                                $isZrmZsmOrZpm = $do->deliveryOrderReceiptDetails()->whereIn('material_type', ['ZRM', 'ZSM', 'ZPM'])->exists();
                                if (! $isZrmZsmOrZpm) {
                                    $latestQc = $do->qcHistories()->latest()->first();
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
                    } else {
                        $notFoundDocuments[] = $documentCode;
                    }
                    $uniqueItems[] = $item;
                }
            }
            $this->uploadedItems = $uniqueItems;
            unset($data['items']);
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

            $allMessages = array_merge($errors, $infoMessages);

            Notification::make()
                ->title('Gagal Disimpan')
                ->body(implode('<br><br>', $allMessages))
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $grsRdtv = $this->record;
        $category = $grsRdtv->category;

        $matchedDocs = [];
        $notFoundDocs = [];

        // --- Proses Dokumen GRS (Multiupload) ---
        if ($category === 'GRS' && ! empty($this->uploadedFiles)) {
            foreach ($this->uploadedFiles as $file) {
                if ($file instanceof TemporaryUploadedFile) {
                    $originalName = $file->getClientOriginalName();
                    $documentCode = pathinfo($originalName, PATHINFO_FILENAME);

                    $path = $file->storeAs('grs-rdtv-docs', $originalName, 'public');
                    $do = DeliveryOrderReceipt::where('document_code', $documentCode)->first();

                    if ($do) {
                        $do->update(['status' => $category]);
                        $matchedDocs[] = $documentCode;
                    } else {
                        $notFoundDocs[] = $documentCode;
                    }

                    $grsRdtvItem = $grsRdtv->grsRdtvItems()->create([
                        'delivery_order_receipt_id' => $do ? $do->id : null,
                        'document_code' => $documentCode,
                        'file_path' => $path,
                        'status' => $do ? 'Matched' : 'Not Found',
                        'reason' => null,
                    ]);

                    $this->createInvoicingReceiptFor($grsRdtvItem, $do, $category, $grsRdtv->transaction_date);
                }
            }
        }

        // --- Proses Dokumen RDTV (Repeater dengan alasan) ---
        if ($category === 'RDTV' && ! empty($this->uploadedItems)) {
            foreach ($this->uploadedItems as $item) {
                // Ekstrak file dari Repeater
                $file = is_array($item['file']) ? array_values($item['file'])[0] ?? null : $item['file'];
                $reason = $item['reason'] ?? null;

                if ($file instanceof TemporaryUploadedFile) {
                    $originalName = $file->getClientOriginalName();
                    $documentCode = pathinfo($originalName, PATHINFO_FILENAME);

                    $path = $file->storeAs('grs-rdtv-docs', $originalName, 'public');
                    $do = DeliveryOrderReceipt::where('document_code', $documentCode)->first();

                    if ($do) {
                        // Karena RDTV, statusnya menjadi RDTV dan kita masukkan delay_reason
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
                        'delivery_order_receipt_id' => $do ? $do->id : null,
                        'document_code' => $documentCode,
                        'file_path' => $path,
                        'status' => $do ? 'Matched' : 'Not Found',
                        'reason' => $reason,
                    ]);

                    $this->createInvoicingReceiptFor($grsRdtvItem, $do, $category, $grsRdtv->transaction_date);
                }
            }
        }

        $body = "Dokumen {$category} selesai diproses.<br>";

        if (! empty($matchedDocs)) {
            $body .= '<br><b>Berhasil (Matched - '.count($matchedDocs).'):</b><br>&bull; '.implode('<br>&bull; ', $matchedDocs);
        }

        if (! empty($notFoundDocs)) {
            $body .= '<br><br><b>Tidak Ditemukan (Not Found - '.count($notFoundDocs).'):</b><br>&bull; '.implode('<br>&bull; ', $notFoundDocs);
        }

        Notification::make()
            ->title('Proses Selesai')
            ->body($body)
            ->success()
            ->send();
    }

    protected function createInvoicingReceiptFor(GrsRdtvItem $grsRdtvItem, ?DeliveryOrderReceipt $do, string $category, $transactionDate): void
    {
        if (! $do) {
            return;
        }

        $do->loadMissing(['deliveryOrderReceiptDetails.purchaseOrderIssued']);
        $firstDetail = $do->deliveryOrderReceiptDetails->first();
        $po = $firstDetail?->purchaseOrderIssued;

        $rawMaterialType = $firstDetail?->material_type ?? $po?->material_type;
        $cleanType = trim((string) $rawMaterialType);
        $isNoneOrEmpty = empty($cleanType) || in_array(strtolower($cleanType), ['none', 'null', '-', 'kosong']);
        $materialType = $isNoneOrEmpty ? null : $cleanType;

        // Tentukan PIC: jika tipe material none atau kosong, bagi rata ke Staff Invoice
        $picId = $this->getAssignmentService()->determinePicId($materialType);

        // Lookup data vendor di master Vendor
        $vendor = null;
        if ($po?->vendor_id || $po?->vendor_name) {
            $vendor = Vendor::where('vendor_number', $po->vendor_id)
                ->orWhere('name', $po->vendor_name)
                ->first();
        }

        $details = $do->deliveryOrderReceiptDetails;
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

        // Rumus Subtotal Include PPN: = Exclude + (Exclude * 11%)
        $subtotalInclude = $subtotalExclude !== null
            ? round((float) $subtotalExclude + ((float) $subtotalExclude * 0.11), 2)
            : null;

        InvoicingReceipt::create([
            'grs_rdtv_item_id' => $grsRdtvItem->id,
            'delivery_order_receipt_id' => $do->id,
            'purchase_order_issued_id' => $po?->id,
            'vendor_id' => $vendor?->id,
            'pic_id' => $picId,
            'tanggal_terima_grs' => $transactionDate ?? now(),
            'jenis' => $category,
            'no_grs' => null, // Wajib diisi nomor GRS resmi (bukan kode dokumen) oleh Staff Invoice
            'no_rdtv' => null,
            'no_po' => $po?->purchase_order_no ?? '-',
            'no_do' => $do->delivery_order_no,
            'material_type' => $materialType,
            'vendor_name' => $po?->vendor_name ?? $vendor?->name,
            'deskripsi' => $firstDetail?->description ?? $po?->description,
            'email_vendor' => is_array($vendor?->email) ? implode(', ', $vendor->email) : $vendor?->email,
            'tanggal_info_grs' => $transactionDate ?? now(),
            'nama_barang' => $firstDetail?->description ?? $po?->description,
            'qty_penagihan' => $qtyPenagihan,
            'qty_po' => $qtyPo,
            'uoi' => $uoi,
            'subtotal_exclude_ppn' => $subtotalExclude,
            'subtotal_include_ppn' => $subtotalInclude,
            'tanggal_terima_koreksi_invoice' => $transactionDate ?? now(),
            'keterangan' => "Auto-generate dari {$category}: {$grsRdtvItem->document_code} (DO: {$do->delivery_order_no})",
            'status' => 'Menunggu Verifikasi',
        ]);
    }
}
