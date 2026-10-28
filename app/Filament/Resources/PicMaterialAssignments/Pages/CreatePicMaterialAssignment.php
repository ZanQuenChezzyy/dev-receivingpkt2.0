<?php

namespace App\Filament\Resources\PicMaterialAssignments\Pages;

use App\Filament\Resources\PicMaterialAssignments\PicMaterialAssignmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePicMaterialAssignment extends CreateRecord
{
    protected static string $resource = PicMaterialAssignmentResource::class;

    protected function afterCreate(): void
    {
        if ($this->record->assignment_mode !== 'user') {
            $this->record->users()->detach();
            $this->record->updateQuietly([
                'user_id' => null,
                'conditional_rules' => null,
            ]);
        } else {
            $firstId = $this->record->users()->first()?->id;
            $this->record->updateQuietly(['user_id' => $firstId]);
        }
    }
}
