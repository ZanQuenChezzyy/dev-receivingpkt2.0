<?php

namespace App\Filament\Resources\ManualMirs\Pages;

use App\Filament\Resources\ManualMirs\ManualMirResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageManualMirs extends ManageRecords
{
    protected static string $resource = ManualMirResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah MIR Manual')
                ->icon(Heroicon::PlusCircle),
        ];
    }
}
