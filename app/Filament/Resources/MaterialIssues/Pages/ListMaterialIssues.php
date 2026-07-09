<?php

namespace App\Filament\Resources\MaterialIssues\Pages;

use App\Filament\Exports\MaterialIssueExporter;
use App\Filament\Resources\MaterialIssues\MaterialIssueResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListMaterialIssues extends ListRecords
{
    protected static string $resource = MaterialIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(MaterialIssueExporter::class)
                ->icon(Heroicon::ArrowUpTray)
                ->color('gray'),
            CreateAction::make()
                ->label('Tambah Material Issue')
                ->icon(Heroicon::PlusCircle),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua')
                ->icon(Heroicon::OutlinedListBullet),
            'digital' => Tab::make('Digital')
                ->icon(Heroicon::OutlinedComputerDesktop)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('jenis_mir', 'digital')),
            'manual' => Tab::make('Manual')
                ->icon(Heroicon::OutlinedDocumentArrowUp)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('jenis_mir', 'manual')),
        ];
    }
}
