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
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('vendor_number')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('email')
                ->requiredMapping()
                ->rules(['required', 'email', 'max:255']),
            ImportColumn::make('name_3')
                ->rules(['max:255']),
            ImportColumn::make('city')
                ->rules(['max:255']),
            ImportColumn::make('street')
                ->rules(['max:255']),
            ImportColumn::make('street2')
                ->rules(['max:255']),
            ImportColumn::make('street3')
                ->rules(['max:255']),
            ImportColumn::make('street4')
                ->rules(['max:255']),
            ImportColumn::make('telephone_1')
                ->rules(['max:255']),
            ImportColumn::make('telephone_2')
                ->rules(['max:255']),
            ImportColumn::make('fax_number')
                ->rules(['max:255']),
            ImportColumn::make('salesperson')
                ->rules(['max:255']),
            ImportColumn::make('telp_salesperson')
                ->rules(['max:255']),
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
