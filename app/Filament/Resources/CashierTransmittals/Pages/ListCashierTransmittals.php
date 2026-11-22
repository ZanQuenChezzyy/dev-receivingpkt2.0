<?php

namespace App\Filament\Resources\CashierTransmittals\Pages;

use App\Filament\Resources\CashierTransmittals\CashierTransmittalResource;
use App\Models\CashierTransmittal;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ListCashierTransmittals extends ListRecords
{
    protected static string $resource = CashierTransmittalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncByDate')
                ->label('Tarik Invoice per Tanggal')
                ->icon(Heroicon::ArrowDownTray)
                ->color('primary')
                ->modalHeading('Tarik Invoice Belum Masuk Transmittal')
                ->modalDescription('Menggabungkan seluruh invoice GRS yang sudah memiliki Tanggal Kirim Kasir pada tanggal pilihan (namun belum masuk riwayat transmittal) ke dalam satu Transmittal Kasir.')
                ->modalSubmitActionLabel('Tarik & Gabungkan')
                ->schema([
                    DatePicker::make('sent_date')
                        ->label('Tanggal Kirim Kasir')
                        ->native(false)
                        ->required()
                        ->default(now()->toDateString()),

                    Toggle::make('force_new_batch')
                        ->label('Buat nomor batch baru (jangan gabung ke batch Terbuka)')
                        ->default(false),
                ])
                ->action(function (array $data): void {
                    $sentDate = (string) ($data['sent_date'] ?? now()->toDateString());
                    $forceNewBatch = (bool) ($data['force_new_batch'] ?? false);

                    $result = CashierTransmittal::syncUnassignedReceiptsForDate(
                        sentDate: $sentDate,
                        userId: Auth::id(),
                        forceNewBatch: $forceNewBatch,
                    );

                    $formattedDate = Carbon::parse($sentDate)->locale('id')->translatedFormat('d F Y');

                    if ($result['transmittal'] === null || $result['attached_count'] === 0) {
                        Notification::make()
                            ->title('Tidak Ada Invoice Baru untuk Ditarik')
                            ->body("Seluruh invoice GRS pada tanggal {$formattedDate} sudah masuk transmittal atau belum ada data kirim kasir.")
                            ->warning()
                            ->send();

                        return;
                    }

                    $transmittal = $result['transmittal'];
                    $totalInBatch = $transmittal->invoicingReceipts()->count();
                    $printUrl = route('filament.admin.resources.cashier-transmittals.print', [
                        'cashierTransmittal' => $transmittal->transmittal_no,
                    ]);

                    Notification::make()
                        ->title("Transmittal {$transmittal->transmittal_no} Diperbarui")
                        ->body("{$result['attached_count']} invoice berhasil ditarik untuk tanggal {$formattedDate}. Total saat ini: {$totalInBatch} invoice dalam 1 transmittal.")
                        ->actions([
                            Action::make('printSyncedTransmittal')
                                ->label('Cetak Transmittal PDF')
                                ->button()
                                ->url($printUrl)
                                ->openUrlInNewTab(),
                        ])
                        ->success()
                        ->send();
                }),
        ];
    }
}
