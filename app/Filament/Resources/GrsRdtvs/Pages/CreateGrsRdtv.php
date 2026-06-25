<?php

namespace App\Filament\Resources\GrsRdtvs\Pages;

use App\Filament\Resources\GrsRdtvs\GrsRdtvResource;
use App\Models\DeliveryOrderReceipt;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CreateGrsRdtv extends CreateRecord
{
    protected static string $resource = GrsRdtvResource::class;

    protected array $uploadedFiles = [];
    protected array $uploadedItems = [];

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
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
                        if ($do->status === 'GRS') {
                            $alreadyProcessed[] = $documentCode;
                        }

                        $latestQc = $do->qcHistories()->latest()->first();
                        if (!$latestQc || $latestQc->status !== 'Kembali') {
                            $invalidDocuments[] = $documentCode;
                        }
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
                        if ($do->status === 'GRS') {
                            $alreadyProcessed[] = $documentCode;
                        }

                        $latestQc = $do->qcHistories()->latest()->first();
                        if (!$latestQc || $latestQc->status !== 'Kembali') {
                            $invalidDocuments[] = $documentCode;
                        }
                    }
                    $uniqueItems[] = $item;
                }
            }
            $this->uploadedItems = $uniqueItems;
            unset($data['items']);
        }

        $errors = [];
        if (!empty($invalidDocuments)) {
            $errors[] = 'Belum kembali dari QC: ' . implode(', ', array_unique($invalidDocuments));
        }
        if (!empty($alreadyProcessed)) {
            $errors[] = 'Sudah sukses diupload sebagai GRS sebelumnya: ' . implode(', ', array_unique($alreadyProcessed));
        }
        if (!empty($duplicateDocuments)) {
            $errors[] = 'Terdeteksi duplikat file yang sama: ' . implode(', ', array_unique($duplicateDocuments));
        }

        if (!empty($errors)) {
            Notification::make()
                ->title('Gagal Disimpan')
                ->body(implode('<br>', $errors))
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

        $matchedCount = 0;
        $notFoundCount = 0;

        // --- Proses Dokumen GRS (Multiupload) ---
        if ($category === 'GRS' && !empty($this->uploadedFiles)) {
            foreach ($this->uploadedFiles as $file) {
                if ($file instanceof TemporaryUploadedFile) {
                    $originalName = $file->getClientOriginalName();
                    $documentCode = pathinfo($originalName, PATHINFO_FILENAME);

                    $path = $file->storeAs('grs-rdtv-docs', $originalName, 'public');
                    $do = DeliveryOrderReceipt::where('document_code', $documentCode)->first();

                    if ($do) {
                        $do->update(['status' => $category]);
                        $matchedCount++;
                    } else {
                        $notFoundCount++;
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
        if ($category === 'RDTV' && !empty($this->uploadedItems)) {
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
                            'delay_notes' => $reason
                        ]);
                        $matchedCount++;
                    } else {
                        $notFoundCount++;
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

        Notification::make()
            ->title('Proses Selesai')
            ->body("Berhasil memproses dokumen {$category}. Matched: {$matchedCount}, Not Found: {$notFoundCount}")
            ->success()
            ->send();
    }
}
