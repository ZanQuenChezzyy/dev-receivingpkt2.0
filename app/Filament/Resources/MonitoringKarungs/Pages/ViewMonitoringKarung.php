<?php

namespace App\Filament\Resources\MonitoringKarungs\Pages;

use App\Filament\Resources\MonitoringKarungs\MonitoringKarungResource;
use App\Models\MonitoringKarung;
use App\Services\SyncMonitoringKarungWithDeliveryOrderService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewMonitoringKarung extends ViewRecord
{
    protected static string $resource = MonitoringKarungResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncDoReceipt')
                ->label('Sinkronkan DO Receipt')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function (MonitoringKarung $record): void {
                    $service = app(SyncMonitoringKarungWithDeliveryOrderService::class);
                    $result = $service->sync($record);

                    Notification::make()
                        ->title('Sinkronisasi DO Receipt Berhasil!')
                        ->body("Terhubung ke {$result['linked_do_count']} DO Receipt. ({$result['new_deliveries']} tahap baru, {$result['updated_deliveries']} tahap diperbarui)")
                        ->success()
                        ->send();
                }),
            EditAction::make(),
        ];
    }
}
