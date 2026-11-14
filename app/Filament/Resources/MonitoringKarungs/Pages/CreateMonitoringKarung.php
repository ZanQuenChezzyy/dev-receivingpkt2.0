<?php

namespace App\Filament\Resources\MonitoringKarungs\Pages;

use App\Filament\Resources\MonitoringKarungs\MonitoringKarungResource;
use App\Services\SyncMonitoringKarungWithDeliveryOrderService;
use Filament\Resources\Pages\CreateRecord;

class CreateMonitoringKarung extends CreateRecord
{
    protected static string $resource = MonitoringKarungResource::class;

    protected function afterCreate(): void
    {
        app(SyncMonitoringKarungWithDeliveryOrderService::class)->sync($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
