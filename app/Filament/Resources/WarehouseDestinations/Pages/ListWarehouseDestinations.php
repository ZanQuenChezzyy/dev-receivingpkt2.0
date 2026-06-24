<?php

namespace App\Filament\Resources\WarehouseDestinations\Pages;

use App\Filament\Resources\WarehouseDestinations\WarehouseDestinationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarehouseDestinations extends ListRecords
{
    protected static string $resource = WarehouseDestinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
