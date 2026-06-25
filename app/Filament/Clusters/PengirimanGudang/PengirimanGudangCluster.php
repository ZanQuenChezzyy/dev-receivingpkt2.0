<?php

namespace App\Filament\Clusters\PengirimanGudang;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class PengirimanGudangCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';
    protected static string|\UnitEnum|null $navigationGroup = 'Pengeluaran Material';
    protected static ?string $navigationLabel = 'Pengiriman Gudang';
}
