<?php

namespace App\Filament\Resources\InvoicingReceipts\Pages;

use App\Filament\Resources\InvoicingReceipts\InvoicingReceiptResource;
use App\Services\InvoicingGrsEmailDispatcher;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditInvoicingReceipt extends EditRecord
{
    protected static string $resource = InvoicingReceiptResource::class;

    protected ?string $previousVerifikasiPath = null;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->previousVerifikasiPath = $this->record->getOriginal('dokumen_verifikasi_grs');
    }

    protected function afterSave(): void
    {
        $this->record->syncSummaryFromItems();
        $this->record->saveQuietly();

        $currentPath = trim((string) ($this->record->dokumen_verifikasi_grs ?? ''));
        $previousPath = trim((string) ($this->previousVerifikasiPath ?? ''));
        $slipPath = trim((string) ($this->record->grsRdtvItem?->file_path ?? ''));

        if ($currentPath !== '' && $currentPath !== $previousPath && $currentPath !== $slipPath) {
            $emailResult = app(InvoicingGrsEmailDispatcher::class)->dispatchIfEligible(
                receipt: $this->record->fresh(['grsRdtvItem', 'vendor', 'pic']),
                triggerSource: 'form_edit',
                userId: Auth::id(),
            );

            if ($emailResult['status'] === 'sent') {
                Notification::make()
                    ->title('Email Vendor Terkirim Otomatis')
                    ->body($emailResult['message'])
                    ->success()
                    ->send();
            } elseif ($emailResult['status'] === 'skipped') {
                Notification::make()
                    ->title('Email Otomatis Ditahan')
                    ->body($emailResult['message'])
                    ->warning()
                    ->send();
            } elseif ($emailResult['status'] === 'failed') {
                Notification::make()
                    ->title('Gagal Mengirim Email ke Vendor')
                    ->body($emailResult['message'])
                    ->danger()
                    ->send();
            }
        }
    }
}
