<?php

namespace App\Filament\Resources\CashierTransmittals\Pages;

use App\Filament\Resources\CashierTransmittals\CashierTransmittalResource;
use App\Models\CashierTransmittal;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCashierTransmittal extends ViewRecord
{
    protected static string $resource = CashierTransmittalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Cetak Transmittal PDF')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->visible(fn (): bool => $this->record instanceof CashierTransmittal && $this->record->invoicingReceipts()->exists())
                ->url(fn (): string => route('filament.admin.resources.cashier-transmittals.print', [
                    'cashierTransmittal' => $this->record->transmittal_no,
                ]))
                ->openUrlInNewTab(),

            Action::make('toggleLock')
                ->label(fn (): string => $this->record->isLocked() ? 'Buka Kembali Batch' : 'Kunci Batch')
                ->icon(fn (): string => $this->record->isLocked() ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                ->color(fn (): string => $this->record->isLocked() ? 'warning' : 'gray')
                ->requiresConfirmation()
                ->modalHeading(fn (): string => $this->record->isLocked() ? 'Buka Kembali Batch Transmittal?' : 'Kunci Batch Transmittal?')
                ->modalDescription(fn (): string => $this->record->isLocked()
                    ? 'Batch ini akan kembali berstatus Terbuka dan dapat menampung invoice tambahan dari PIC lain pada tanggal yang sama.'
                    : 'Batch yang dikunci tidak akan menerima tambahan invoice otomatis. Pengiriman baru di tanggal yang sama akan otomatis membuat nomor batch berikutnya.')
                ->action(function (): void {
                    $newStatus = $this->record->isLocked()
                        ? CashierTransmittal::STATUS_OPEN
                        : CashierTransmittal::STATUS_LOCKED;

                    $this->record->update(['status' => $newStatus]);
                    $this->refreshFormData(['status']);

                    Notification::make()
                        ->title($newStatus === CashierTransmittal::STATUS_LOCKED ? 'Batch Dikunci' : 'Batch Dibuka Kembali')
                        ->body("Status {$this->record->transmittal_no} diperbarui menjadi {$newStatus}.")
                        ->success()
                        ->send();
                }),

            DeleteAction::make()
                ->label('Hapus Transmittal')
                ->modalDescription('Menghapus riwayat transmittal tidak akan menghapus data invoice barang — invoice hanya dilepas dari nomor transmittal ini.'),
        ];
    }
}
