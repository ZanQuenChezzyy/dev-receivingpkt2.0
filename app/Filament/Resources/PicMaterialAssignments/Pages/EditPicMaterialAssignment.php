<?php

namespace App\Filament\Resources\PicMaterialAssignments\Pages;

use App\Filament\Resources\PicMaterialAssignments\PicMaterialAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPicMaterialAssignment extends EditRecord
{
    protected static string $resource = PicMaterialAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
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
