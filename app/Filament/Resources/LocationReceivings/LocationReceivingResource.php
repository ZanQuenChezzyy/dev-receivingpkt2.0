<?php

namespace App\Filament\Resources\LocationReceivings;

use App\Filament\Resources\LocationReceivings\Pages\ManageLocationReceivings;
use App\Models\LocationReceiving;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class LocationReceivingResource extends Resource
{
    protected static ?string $model = LocationReceiving::class;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static string|BackedEnum|null $activeNavigationIcon = 'heroicon-s-map-pin';

    public static function getNavigationLabel(): string
    {
        return 'Lokasi Receiving';
    }

    public static function getModelLabel(): string
    {
        return 'Lokasi Receiving';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Lokasi Receiving';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Lokasi Receiving')
                    ->description('Data master lokasi untuk proses receiving.')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lokasi')
                            ->placeholder('Masukkan nama lokasi')
                            ->required()
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                InfolistSection::make('Informasi Lokasi')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Lokasi')
                            ->weight(FontWeight::Bold)
                            ->color('primary')
                            ->icon('heroicon-m-map-pin'),
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Diperbarui Pada')
                            ->dateTime()
                            ->placeholder('-'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColumnGroup::make('Informasi Lokasi', [
                    TextColumn::make('name')
                        ->label('Nama Lokasi')
                        ->searchable()
                        ->sortable()
                        ->weight(FontWeight::Bold)
                        ->icon('heroicon-m-map-pin')
                        ->color('primary'),
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
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLocationReceivings::route('/'),
        ];
    }
}
