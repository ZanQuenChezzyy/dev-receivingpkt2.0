<?php

namespace App\Filament\Resources\WarehouseTransmittals\Pages;

use App\Filament\Resources\WarehouseTransmittals\WarehouseTransmittalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarehouseTransmittals extends ListRecords
{
    protected static string $resource = WarehouseTransmittalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
