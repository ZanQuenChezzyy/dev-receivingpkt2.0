<?php

namespace App\Filament\Imports;

use App\Models\Vendor;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class VendorImporter extends Importer
{
    protected static ?string $model = Vendor::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('NAMA VENDOR')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('vendor_number')
                ->label('Vendor Number')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('city')
                ->label('City')
                ->rules(['max:255']),
            ImportColumn::make('street')
                ->label('Street')
                ->rules(['max:255']),
            ImportColumn::make('street2')
                ->label('Street2')
                ->rules(['max:255']),
            ImportColumn::make('street3')
                ->label('Street3')
                ->rules(['max:255']),
            ImportColumn::make('street4')
                ->label('Street4')
                ->rules(['max:255']),
            ImportColumn::make('telephone_1')
                ->label('Telephone 1')
                ->rules(['max:255']),
            ImportColumn::make('telephone_2')
                ->label('Telephone 2')
                ->rules(['max:255']),
            ImportColumn::make('fax_number')
                ->label('Fax Number')
                ->rules(['max:255']),
            ImportColumn::make('salesperson')
                ->label('Salesperson')
                ->rules(['max:255']),
            ImportColumn::make('telp_salesperson')
                ->label('Telp. Salesperson')
                ->rules(['max:255']),
            ImportColumn::make('email')
                ->label('Email')
                ->rules(['nullable']),
        ];
    }

    public function resolveRecord(): Vendor
    {
        return Vendor::firstOrNew([
            'vendor_number' => $this->data['vendor_number'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import data Vendor selesai. '.Number::format($import->successful_rows).' vendor berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' data gagal diimpor.';
        }

        return $body;
    }
}
