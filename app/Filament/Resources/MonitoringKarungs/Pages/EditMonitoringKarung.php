<?php

namespace App\Filament\Resources\MonitoringKarungs\Pages;

use App\Filament\Resources\MonitoringKarungs\MonitoringKarungResource;
use App\Services\SyncMonitoringKarungWithDeliveryOrderService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMonitoringKarung extends EditRecord
{
    protected static string $resource = MonitoringKarungResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        app(SyncMonitoringKarungWithDeliveryOrderService::class)->sync($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
