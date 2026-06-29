<?php

namespace App\Filament\Resources\WarehouseDestinations\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WarehouseDestinationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Gudang')
                    ->description('Data master untuk gudang tujuan pengiriman.')
                    ->icon('heroicon-o-home-modern')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->label('Nama Gudang')
                                ->placeholder('Contoh: Gudang Bahan Baku'),
                            Select::make('pic_id')
                                ->relationship('pic', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->label('PIC Gudang'),
                        ]),
                    ]),
            ]);
    }
}
