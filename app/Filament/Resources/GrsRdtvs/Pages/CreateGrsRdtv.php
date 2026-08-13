<?php

namespace App\Filament\Resources\GrsRdtvs\Pages;

use App\Filament\Resources\GrsRdtvs\GrsRdtvResource;
use App\Models\DeliveryOrderReceipt;
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

                    $grsRdtv->grsRdtvItems()->create([
                        'delivery_order_receipt_id' => $do ? $do->id : null,
                        'document_code' => $documentCode,
                        'file_path' => $path,
                        'status' => $do ? 'Matched' : 'Not Found',
                        'reason' => null,
                    ]);
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

                    $grsRdtv->grsRdtvItems()->create([
                        'delivery_order_receipt_id' => $do ? $do->id : null,
                        'document_code' => $documentCode,
                        'file_path' => $path,
                        'status' => $do ? 'Matched' : 'Not Found',
                        'reason' => $reason,
                    ]);
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
}
