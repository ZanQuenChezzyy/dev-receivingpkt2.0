<?php

namespace App\Filament\Resources\WarehouseDestinations;

use App\Filament\Resources\WarehouseDestinations\Pages\CreateWarehouseDestination;
use App\Filament\Resources\WarehouseDestinations\Pages\EditWarehouseDestination;
use App\Filament\Resources\WarehouseDestinations\Pages\ListWarehouseDestinations;
use App\Filament\Resources\WarehouseDestinations\Schemas\WarehouseDestinationForm;
use App\Filament\Resources\WarehouseDestinations\Tables\WarehouseDestinationsTable;
use App\Models\WarehouseDestination;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class WarehouseDestinationResource extends Resource
{
    protected static ?string $model = WarehouseDestination::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $modelLabel = 'Gudang Tujuan';

    protected static ?string $pluralModelLabel = 'Gudang Tujuan';

    public static function getNavigationGroup(): ?string
    {
        return 'Data Master';
    }

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return WarehouseDestinationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehouseDestinationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarehouseDestinations::route('/'),
            'create' => CreateWarehouseDestination::route('/create'),
            'edit' => EditWarehouseDestination::route('/{record}/edit'),
        ];
    }
}
