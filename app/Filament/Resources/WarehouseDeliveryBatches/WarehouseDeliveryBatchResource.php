<?php

namespace App\Filament\Resources\WarehouseDeliveryBatches;

use App\Filament\Clusters\PengirimanGudang\PengirimanGudangCluster;
use App\Filament\Resources\WarehouseDeliveryBatches\Pages\ListWarehouseDeliveryBatches;
use App\Filament\Resources\WarehouseDeliveryBatches\Tables\WarehouseDeliveryBatchesTable;
use App\Models\WarehouseDeliveryBatch;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WarehouseDeliveryBatchResource extends Resource
{
    protected static ?string $model = WarehouseDeliveryBatch::class;

    protected static ?string $cluster = PengirimanGudangCluster::class;

    protected static ?int $navigationSort = 1;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\BackedEnum|null $activeNavigationIcon = 'heroicon-s-clipboard-document-list';

    public static function getNavigationLabel(): string
    {
        return 'Master MB51';
    }

    public static function getModelLabel(): string
    {
        return 'Master MB51';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Master Pengiriman MB51';
    }

    protected static ?string $recordTitleAttribute = 'batch_title';

    public static function table(Table $table): Table
    {
        return WarehouseDeliveryBatchesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('uploader')
            ->withCount([
                'items as delivered_count' => fn ($query) => $query->where('status', 'delivered'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarehouseDeliveryBatches::route('/'),
        ];
    }
}
