<?php

namespace App\Filament\Resources\WarehouseDestinations\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WarehouseDestinationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ColumnGroup::make('Detail Gudang', [
                    TextColumn::make('name')
                        ->searchable()
                        ->sortable()
                        ->label('Nama Gudang')
                        ->weight(FontWeight::Bold)
                        ->icon('heroicon-m-home-modern')
                        ->color('primary'),
                    TextColumn::make('pic.name')
                        ->searchable()
                        ->sortable()
                        ->label('PIC Gudang')
                        ->icon('heroicon-m-user')
                        ->color('gray'),
                ]),
                ColumnGroup::make('Sistem', [
                    TextColumn::make('created_at')
                        ->label('Dibuat')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('updated_at')
                        ->label('Diperbarui')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
