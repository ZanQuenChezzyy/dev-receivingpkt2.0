<?php

namespace App\Filament\Resources\CashierTransmittals;

use App\Filament\Concerns\TranslatableResource;
use App\Filament\Resources\CashierTransmittals\Pages\ListCashierTransmittals;
use App\Filament\Resources\CashierTransmittals\Pages\ViewCashierTransmittal;
use App\Filament\Resources\CashierTransmittals\RelationManagers\InvoicingReceiptsRelationManager;
use App\Filament\Resources\CashierTransmittals\Schemas\CashierTransmittalInfolist;
use App\Filament\Resources\CashierTransmittals\Tables\CashierTransmittalsTable;
use App\Models\CashierTransmittal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CashierTransmittalResource extends Resource
{
    use TranslatableResource;

    protected static ?string $model = CashierTransmittal::class;

    protected static string|UnitEnum|null $navigationGroup = 'Penerimaan Jasa / Invoice';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::DocumentText;

    protected static ?string $recordTitleAttribute = 'transmittal_no';

    public static function getNavigationLabel(): string
    {
        return __('Transmittal Kasir');
    }

    public static function getModelLabel(): string
    {
        return __('Transmittal Kasir');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Transmittal Kasir');
    }

    public static function getNavigationBadge(): ?string
    {
        $openCount = CashierTransmittal::query()
            ->where('status', CashierTransmittal::STATUS_OPEN)
            ->count();

        return $openCount > 0 ? (string) $openCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function infolist(Schema $schema): Schema
    {
        return CashierTransmittalInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashierTransmittalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            InvoicingReceiptsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashierTransmittals::route('/'),
            'view' => ViewCashierTransmittal::route('/{record}'),
        ];
    }
}
