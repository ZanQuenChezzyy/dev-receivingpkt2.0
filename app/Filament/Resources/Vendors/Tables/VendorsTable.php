<?php

namespace App\Filament\Resources\Vendors\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VendorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ColumnGroup::make('Identitas Vendor', [
                    TextColumn::make('name')
                        ->label('Nama Vendor')
                        ->weight(FontWeight::Bold)
                        ->icon('heroicon-m-building-office')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('vendor_number')
                        ->label('No. Vendor')
                        ->badge()
                        ->color('primary')
                        ->copyable()
                        ->copyMessage('Nomor vendor disalin')
                        ->searchable()
                        ->sortable(),
                ]),

                ColumnGroup::make('Kontak Resmi', [
                    TextColumn::make('email')
                        ->label('Email Vendor')
                        ->badge()
                        ->separator(',')
                        ->icon('heroicon-m-envelope')
                        ->iconColor('info')
                        ->copyable()
                        ->copyMessage('Email disalin')
                        ->searchable(),
                    TextColumn::make('telephone_1')
                        ->label('Telepon 1')
                        ->icon('heroicon-m-phone')
                        ->placeholder('-')
                        ->searchable(),
                    TextColumn::make('telephone_2')
                        ->label('Telepon 2')
                        ->placeholder('-')
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('fax_number')
                        ->label('No. Fax')
                        ->placeholder('-')
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),

                ColumnGroup::make('Salesperson', [
                    TextColumn::make('salesperson')
                        ->label('Nama Sales')
                        ->icon('heroicon-m-user')
                        ->placeholder('-')
                        ->searchable(),
                    TextColumn::make('telp_salesperson')
                        ->label('Telp. Sales')
                        ->icon('heroicon-m-phone-arrow-up-right')
                        ->placeholder('-')
                        ->copyable()
                        ->searchable(),
                ]),

                ColumnGroup::make('Alamat', [
                    TextColumn::make('city')
                        ->label('Kota')
                        ->icon('heroicon-m-map-pin')
                        ->placeholder('-')
                        ->sortable(),
                    TextColumn::make('street')
                        ->label('Jalan / Alamat')
                        ->placeholder('-')
                        ->limit(30)
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->label('')
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->size(Size::Small)
                    ->color('info')
                    ->outlined()
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
