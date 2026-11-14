<?php

namespace App\Filament\Resources\MonitoringKarungs;

use App\Filament\Resources\MonitoringKarungs\Pages\CreateMonitoringKarung;
use App\Filament\Resources\MonitoringKarungs\Pages\EditMonitoringKarung;
use App\Filament\Resources\MonitoringKarungs\Pages\ListMonitoringKarungs;
use App\Filament\Resources\MonitoringKarungs\Pages\ViewMonitoringKarung;
use App\Filament\Resources\MonitoringKarungs\Schemas\MonitoringKarungForm;
use App\Filament\Resources\MonitoringKarungs\Schemas\MonitoringKarungInfolist;
use App\Filament\Resources\MonitoringKarungs\Tables\MonitoringKarungsTable;
use App\Models\MonitoringKarung;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class MonitoringKarungResource extends Resource
{
    protected static ?string $model = MonitoringKarung::class;

    protected static string|UnitEnum|null $navigationGroup = 'Penerimaan Jasa / Invoice';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|BackedEnum|null $activeNavigationIcon = 'heroicon-s-archive-box';

    public static function getNavigationLabel(): string
    {
        return 'Monitoring Karung';
    }

    public static function getModelLabel(): string
    {
        return 'Monitoring Karung';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Monitoring Karung';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = MonitoringKarung::whereHas('qcItems', function ($q) {
            $q->whereNull('no_sa')->orWhere('no_sa', '')->orWhere('status_qc', 'BLM SA');
        })->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'PO Karung yang masih memiliki dokumen belum SA SAP';
    }

    public static function form(Schema $schema): Schema
    {
        return MonitoringKarungForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MonitoringKarungInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MonitoringKarungsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // Dikelola langsung melalui Tabs Repeater terintegrasi pada MonitoringKarungForm
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMonitoringKarungs::route('/'),
            'create' => CreateMonitoringKarung::route('/create'),
            'view' => ViewMonitoringKarung::route('/{record}'),
            'edit' => EditMonitoringKarung::route('/{record}/edit'),
        ];
    }
}
