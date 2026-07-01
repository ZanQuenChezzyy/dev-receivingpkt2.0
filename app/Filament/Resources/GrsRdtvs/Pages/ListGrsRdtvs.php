<?php

namespace App\Filament\Resources\GrsRdtvs\Pages;

use App\Filament\Resources\GrsRdtvs\GrsRdtvResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ListGrsRdtvs extends ListRecords
{
    protected static string $resource = GrsRdtvResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah GRS/RDTV')
                ->icon(Heroicon::PlusCircle),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua')
                ->icon(Heroicon::OutlinedListBullet)
                ->badge($this->getModel()::count()),
            'grs' => Tab::make('GRS')
                ->icon(Heroicon::OutlinedDocumentCheck)
                ->modifyQueryUsing(fn($query) => $query->where('category', 'GRS'))
                ->badge($this->getModel()::where('category', 'GRS')->count()),
            'rdtv' => Tab::make('RDTV')
                ->icon(Heroicon::OutlinedArrowPath)
                ->modifyQueryUsing(fn($query) => $query->where('category', 'RDTV'))
                ->badge($this->getModel()::where('category', 'RDTV')->count()),
        ];
    }
}
