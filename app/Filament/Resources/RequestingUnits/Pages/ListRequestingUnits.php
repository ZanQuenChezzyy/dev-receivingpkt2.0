<?php

namespace App\Filament\Resources\RequestingUnits\Pages;

use App\Filament\Resources\RequestingUnits\RequestingUnitResource;
use App\Services\NonStockAgingService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListRequestingUnits extends ListRecords
{
    protected static string $resource = RequestingUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncMappings')
                ->label('Sinkronkan Pemetaan Requisitioner')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Sinkronkan Pemetaan Requisitioner')
                ->modalDescription('Sistem akan mencocokkan seluruh item Non-Stock & PD yang belum memiliki Unit Peminta berdasarkan daftar alias pada master ini.')
                ->action(function (NonStockAgingService $service): void {
                    $mapped = $service->syncRequestingUnitMappings(onlyUnmapped: true);

                    Notification::make()
                        ->title('Pemetaan selesai')
                        ->body("{$mapped} item berhasil ditautkan ke Unit Peminta yang sesuai.")
                        ->success()
                        ->send();
                }),
            Action::make('mirContactCandidates')
                ->label('Referensi Kontak dari MIR')
                ->icon('heroicon-o-phone')
                ->color('gray')
                ->modalHeading('Referensi Kontak PIC dari Riwayat MIR')
                ->modalDescription('Daftar departemen, peminta, dan nomor HP yang pernah tercatat pada transaksi MIR sebagai bahan pengisian kontak PIC.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalContent(fn (NonStockAgingService $service): View => view('filament.resources.requesting-units.mir-contact-candidates', [
                    'candidates' => $service->extractContactsFromMirHistory(40),
                ])),
            CreateAction::make()
                ->label('Tambah Unit Peminta'),
        ];
    }
}
