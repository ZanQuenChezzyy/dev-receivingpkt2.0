<?php

namespace App\Filament\Resources\WarehouseTransmittals\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WarehouseTransmittalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('transmittal_no')
                    ->label('Transmittal No')
                    ->required()
                    ->disabled() // Because it's auto-generated usually
                    ->maxLength(255),
                Select::make('warehouse_destination_id')
                    ->relationship('destination', 'name')
                    ->label('Tujuan Gudang')
                    ->required()
                    ->disabled(),
                DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required()
                    ->disabled(),
                Select::make('created_by')
                    ->relationship('createdBy', 'name')
                    ->label('Dibuat Oleh')
                    ->required()
                    ->disabled(),
            ]);
    }
}
