<?php

namespace App\Filament\Resources\PicMaterialAssignments\Pages;

use App\Filament\Resources\PicMaterialAssignments\PicMaterialAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPicMaterialAssignments extends ListRecords
{
    protected static string $resource = PicMaterialAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
