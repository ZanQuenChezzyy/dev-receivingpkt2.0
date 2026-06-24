<?php

namespace App\Filament\Resources\WarehouseDestinations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WarehouseDestinationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Gudang'),
                Select::make('pic_id')
                    ->relationship('pic', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('PIC Gudang'),
            ]);
    }
}
