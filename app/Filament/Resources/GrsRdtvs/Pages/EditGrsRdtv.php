<?php

namespace App\Filament\Resources\GrsRdtvs\Pages;

use App\Filament\Resources\GrsRdtvs\GrsRdtvResource;
use App\Models\GrsRdtv;
use App\Services\GrsRdtvProcessingService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class EditGrsRdtv extends EditRecord
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

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $category = (string) ($data['category'] ?? $this->record->category);
        $files = is_array($data['files'] ?? null) ? $data['files'] : [];
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];

        unset($data['files'], $data['items']);

        // Cegah duplikasi header transaksi untuk tanggal & kategori yang sama
        if (! empty($data['transaction_date'])) {
            $targetDate = Carbon::parse($data['transaction_date'])->toDateString();
            $duplicateBatch = GrsRdtv::query()
                ->where('id', '!=', $this->record->id)
                ->whereDate('transaction_date', $targetDate)
                ->where('category', $category)
                ->first();

            if ($duplicateBatch) {
                $this->uploadedFiles = [];
                $this->uploadedItems = [];

                Notification::make()
                    ->title('Tanggal Transaksi Sudah Ada')
                    ->body('Sudah ada data '.$category.' pada tanggal '.Carbon::parse($targetDate)->format('d/m/Y').'. Silakan gunakan data transaksi pada tanggal tersebut.')
                    ->danger()
                    ->send();

                $this->halt();
            }
        }

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

    protected function afterSave(): void
    {
        $grsRdtv = $this->record;
        $category = (string) $grsRdtv->category;

        $summary = $this->getProcessingService()->processUploads(
            grsRdtv: $grsRdtv,
            uploadedFiles: $this->uploadedFiles,
            uploadedItems: $this->uploadedItems,
        );

        // Bersihkan seluruh state upload agar penyimpanan berikutnya tidak mengirim ulang file yang sama
        $this->uploadedFiles = [];
        $this->uploadedItems = [];
        $this->data['files'] = [];
        $this->data['items'] = [];

        // Segarkan tabel daftar dokumen di bawah form secara langsung
        $this->dispatch('grs-rdtv-items-updated');

        if ($summary['processed_count'] === 0) {
            return;
        }

        $body = "Dokumen {$category} lanjutan selesai diproses.<br>";

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
}
