<?php

namespace App\Filament\Resources\PicMaterialAssignments\Pages;

use App\Filament\Resources\PicMaterialAssignments\PicMaterialAssignmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPicMaterialAssignment extends ViewRecord
{
    protected static string $resource = PicMaterialAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
