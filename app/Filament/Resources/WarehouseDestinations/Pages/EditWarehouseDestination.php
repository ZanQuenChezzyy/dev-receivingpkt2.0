<?php

namespace App\Filament\Resources\WarehouseDestinations\Pages;

use App\Filament\Resources\WarehouseDestinations\WarehouseDestinationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWarehouseDestination extends EditRecord
{
    protected static string $resource = WarehouseDestinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
