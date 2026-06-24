<?php

namespace App\Filament\Resources\WarehouseTransmittals\Pages;

use App\Filament\Resources\WarehouseTransmittals\WarehouseTransmittalResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditWarehouseTransmittal extends EditRecord
{
    protected static string $resource = WarehouseTransmittalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
