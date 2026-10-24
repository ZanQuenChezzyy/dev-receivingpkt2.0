<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Filament\Imports\VendorImporter;
use App\Filament\Resources\Vendors\VendorResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListVendors extends ListRecords
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(VendorImporter::class)
                ->label('Import Data Vendor (Excel/CSV)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info'),
            CreateAction::make()
                ->label('Tambah Vendor Baru')
                ->icon('heroicon-o-plus'),
        ];
    }
}
