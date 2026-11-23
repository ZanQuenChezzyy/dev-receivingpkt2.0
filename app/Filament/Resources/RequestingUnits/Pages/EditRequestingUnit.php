<?php

namespace App\Filament\Resources\RequestingUnits\Pages;

use App\Filament\Resources\RequestingUnits\RequestingUnitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRequestingUnit extends EditRecord
{
    protected static string $resource = RequestingUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
