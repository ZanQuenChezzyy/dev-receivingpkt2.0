<?php

namespace App\Filament\Resources\MaterialIssues\Pages;

use App\Filament\Resources\MaterialIssues\MaterialIssueResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListMaterialIssues extends ListRecords
{
    protected static string $resource = MaterialIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_manual_mirs')
                ->label('Lihat Daftar MIR Manual')
                ->icon(Heroicon::ClipboardDocumentList)
                ->color('gray')
                ->url(fn() => \App\Filament\Resources\ManualMirs\ManualMirResource::getUrl('index')),
            CreateAction::make()
                ->label('Tambah Material Issue')
                ->icon(Heroicon::PlusCircle),
        ];
    }
}
