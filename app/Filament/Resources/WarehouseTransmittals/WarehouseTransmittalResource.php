<?php

namespace App\Filament\Resources\WarehouseTransmittals;

use App\Filament\Clusters\PengirimanGudang\PengirimanGudangCluster;
use App\Filament\Resources\WarehouseTransmittals\Pages\CreateWarehouseTransmittal;
use App\Filament\Resources\WarehouseTransmittals\Pages\EditWarehouseTransmittal;
use App\Filament\Resources\WarehouseTransmittals\Pages\ListWarehouseTransmittals;
use App\Filament\Resources\WarehouseTransmittals\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\WarehouseTransmittals\Schemas\WarehouseTransmittalForm;
use App\Filament\Resources\WarehouseTransmittals\Tables\WarehouseTransmittalsTable;
use App\Models\WarehouseTransmittal;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WarehouseTransmittalResource extends Resource
{
    protected static ?string $model = WarehouseTransmittal::class;

    protected static ?string $cluster = PengirimanGudangCluster::class;

    protected static ?int $navigationSort = 2;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\BackedEnum|null $activeNavigationIcon = 'heroicon-s-document-text';

    public static function getNavigationLabel(): string
    {
        return 'Transmittal Gudang';
    }

    public static function getModelLabel(): string
    {
        return 'Transmittal Gudang';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Transmittal Gudang';
    }

    protected static ?string $recordTitleAttribute = 'transmittal_no';

    public static function form(Schema $schema): Schema
    {
        return WarehouseTransmittalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehouseTransmittalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarehouseTransmittals::route('/'),
            'create' => CreateWarehouseTransmittal::route('/create'),
            'edit' => EditWarehouseTransmittal::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
