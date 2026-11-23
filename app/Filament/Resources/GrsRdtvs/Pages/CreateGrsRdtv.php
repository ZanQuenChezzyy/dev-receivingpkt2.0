<?php

namespace App\Filament\Resources\GrsRdtvs\Pages;

use App\Filament\Resources\GrsRdtvs\GrsRdtvResource;
use App\Models\DeliveryOrderReceipt;
use App\Models\GrsRdtvItem;
use App\Services\GrsRdtvProcessingService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class CreateGrsRdtv extends CreateRecord
{
    protected static string $resource = GrsRdtvResource::class;

    /** @var array<int, UploadedFile> */
    protected array $uploadedFiles = [];

    /** @var array<int, array{file: UploadedFile, reason: ?string}> */
    protected array $uploadedItems = [];

    protected function getProcessingService(): GrsRdtvProcessingService
    {
        return app(GrsRdtvProcessingService::class);
    }

    protected function handleRecordCreation(array $data): Model
    {
        return $this->getProcessingService()->findOrCreateBatch(
            transactionDate: (string) $data['transaction_date'],
            category: (string) $data['category'],
            createdBy: $data['created_by'] ?? Auth::id(),
        );
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $category = (string) ($data['category'] ?? 'GRS');
        $files = is_array($data['files'] ?? null) ? $data['files'] : [];
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];

        unset($data['files'], $data['items']);

        $result = $this->getProcessingService()->validateUploads(
            category: $category,
            files: $files,
            items: $items,
        );

        if ($result['has_errors']) {
            $this->uploadedFiles = [];
            $this->uploadedItems = [];

            Notification::make()
                ->title('Gagal Disimpan')
                ->body((string) $result['error_body'])
                ->danger()
                ->send();

            $this->halt();
        }

        $this->uploadedFiles = $result['uploaded_files'];
        $this->uploadedItems = $result['uploaded_items'];

        return $data;
    }

    protected function afterCreate(): void
    {
        $grsRdtv = $this->record;
        $category = (string) $grsRdtv->category;

        $summary = $this->getProcessingService()->processUploads(
            grsRdtv: $grsRdtv,
            uploadedFiles: $this->uploadedFiles,
            uploadedItems: $this->uploadedItems,
        );

        $this->uploadedFiles = [];
        $this->uploadedItems = [];

        $body = "Dokumen {$category} selesai diproses.<br>";

        if (! empty($summary['matched'])) {
            $body .= '<br><b>Berhasil (Matched - '.count($summary['matched']).'):</b><br>&bull; '.implode('<br>&bull; ', $summary['matched']);
        }

        if (! empty($summary['not_found'])) {
            $body .= '<br><br><b>Tidak Ditemukan (Not Found - '.count($summary['not_found']).'):</b><br>&bull; '.implode('<br>&bull; ', $summary['not_found']);
        }

        Notification::make()
            ->title('Proses Selesai')
            ->body($body)
            ->success()
            ->send();
    }

    protected function createInvoicingReceiptFor(GrsRdtvItem $grsRdtvItem, ?DeliveryOrderReceipt $do, string $category, mixed $transactionDate): void
    {
        $this->getProcessingService()->createInvoicingReceiptFor($grsRdtvItem, $do, $category, $transactionDate);
    }
}
