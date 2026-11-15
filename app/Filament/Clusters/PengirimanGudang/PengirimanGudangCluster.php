<?php

namespace App\Filament\Clusters\PengirimanGudang;

use BackedEnum;
use Filament\Clusters\Cluster;
use UnitEnum;

class PengirimanGudangCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|UnitEnum|null $navigationGroup = 'Pengeluaran Material';

    protected static ?string $navigationLabel = 'Pengiriman Gudang';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return static::$navigationGroup ? __(static::$navigationGroup) : null;
    }

    public static function getNavigationLabel(): string
    {
        return __('Pengiriman Gudang');
    }
}
