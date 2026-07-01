<?php

namespace App\Filament\Resources\GrsRdtvs\Pages;

use App\Filament\Resources\GrsRdtvs\GrsRdtvResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditGrsRdtv extends EditRecord
{
    protected static string $resource = GrsRdtvResource::class;

    protected array $uploadedFiles = [];
    protected array $uploadedItems = [];

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $category = $this->record->category;
        $invalidDocuments = [];
        $alreadyProcessed = [];
        $duplicateDocuments = [];
        $seen = [];

        if (isset($data['files'])) {
            $uniqueFiles = [];
            foreach ($data['files'] as $file) {
                if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                    $originalName = $file->getClientOriginalName();
                    $documentCode = pathinfo($originalName, PATHINFO_FILENAME);

                    if (in_array($documentCode, $seen)) {
                        $duplicateDocuments[] = $documentCode;
                        continue;
                    }
                    $seen[] = $documentCode;

                    $do = \App\Models\DeliveryOrderReceipt::where('document_code', $documentCode)->first();
                    if ($do) {
                        if ($do->status === 'GRS') {
                            $alreadyProcessed[] = $documentCode;
                        }

                        $latestQc = $do->qcHistories()->latest()->first();
                        if (! $latestQc || $latestQc->status !== 'Kembali') {
                            $invalidDocuments[] = $documentCode;
                        }
                    }
                    $uniqueFiles[] = $file;
                }
            }
            $this->uploadedFiles = $uniqueFiles;
            unset($data['files']);
        }

        if (isset($data['items'])) {
            $uniqueItems = [];
            foreach ($data['items'] as $item) {
                $file = is_array($item['file']) ? array_values($item['file'])[0] ?? null : $item['file'];
                if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                    $originalName = $file->getClientOriginalName();
                    $documentCode = pathinfo($originalName, PATHINFO_FILENAME);

                    if (in_array($documentCode, $seen)) {
                        $duplicateDocuments[] = $documentCode;
                        continue;
                    }
                    $seen[] = $documentCode;

                    $do = \App\Models\DeliveryOrderReceipt::where('document_code', $documentCode)->first();
                    if ($do) {
                        if ($do->status === 'GRS') {
                            $alreadyProcessed[] = $documentCode;
                        }

                        $latestQc = $do->qcHistories()->latest()->first();
                        if (! $latestQc || $latestQc->status !== 'Kembali') {
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
        if (! empty($invalidDocuments)) {
            $errors[] = 'Belum kembali dari QC: '.implode(', ', array_unique($invalidDocuments));
        }
        if (! empty($alreadyProcessed)) {
            $errors[] = 'Sudah sukses diupload sebagai GRS sebelumnya: '.implode(', ', array_unique($alreadyProcessed));
        }
        if (! empty($duplicateDocuments)) {
            $errors[] = 'Terdeteksi duplikat file yang sama: '.implode(', ', array_unique($duplicateDocuments));
        }

        if (! empty($errors)) {
            \Filament\Notifications\Notification::make()
                ->title('Gagal Disimpan')
                ->body(implode('<br>', $errors))
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $grsRdtv = $this->record;
        $category = $grsRdtv->category;

        $matchedCount = 0;
        $notFoundCount = 0;

        if ($category === 'GRS' && ! empty($this->uploadedFiles)) {
            foreach ($this->uploadedFiles as $file) {
                if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                    $originalName = $file->getClientOriginalName();
                    $documentCode = pathinfo($originalName, PATHINFO_FILENAME);

                    $path = $file->storeAs('grs-rdtv-docs', $originalName, 'public');
                    $do = \App\Models\DeliveryOrderReceipt::where('document_code', $documentCode)->first();

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

        if ($category === 'RDTV' && ! empty($this->uploadedItems)) {
            foreach ($this->uploadedItems as $item) {
                $file = is_array($item['file']) ? array_values($item['file'])[0] ?? null : $item['file'];
                $reason = $item['reason'] ?? null;

                if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                    $originalName = $file->getClientOriginalName();
                    $documentCode = pathinfo($originalName, PATHINFO_FILENAME);

                    $path = $file->storeAs('grs-rdtv-docs', $originalName, 'public');
                    $do = \App\Models\DeliveryOrderReceipt::where('document_code', $documentCode)->first();

                    if ($do) {
                        $do->update([
                            'status' => $category,
                            'delay_reason' => 'RDTV',
                            'delay_notes' => $reason,
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

        if ($matchedCount > 0 || $notFoundCount > 0) {
            \Filament\Notifications\Notification::make()
                ->title('Proses Lanjutan Selesai')
                ->body("Berhasil memproses tambahan dokumen {$category}. Matched: {$matchedCount}, Not Found: {$notFoundCount}")
                ->success()
                ->send();
        }
    }
}
