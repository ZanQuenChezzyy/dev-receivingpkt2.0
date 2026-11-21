<?php

namespace App\Filament\Resources\DeliveryOrderReceipts;

use App\Filament\Concerns\TranslatableResource;
use App\Filament\Resources\DeliveryOrderReceipts\Pages\CreateDeliveryOrderReceipt;
use App\Filament\Resources\DeliveryOrderReceipts\Pages\EditDeliveryOrderReceipt;
use App\Filament\Resources\DeliveryOrderReceipts\Pages\ListDeliveryOrderReceipts;
use App\Filament\Resources\DeliveryOrderReceipts\Pages\ViewDeliveryOrderReceipt;
use App\Filament\Resources\DeliveryOrderReceipts\RelationManagers\DelayLogsRelationManager;
use App\Filament\Resources\DeliveryOrderReceipts\RelationManagers\QcHistoriesRelationManager;
use App\Filament\Resources\DeliveryOrderReceipts\RelationManagers\TransmittalsRelationManager;
use App\Filament\Resources\DeliveryOrderReceipts\Schemas\DeliveryOrderReceiptForm;
use App\Filament\Resources\DeliveryOrderReceipts\Schemas\DeliveryOrderReceiptInfolist;
use App\Filament\Resources\DeliveryOrderReceipts\Tables\DeliveryOrderReceiptsTable;
use App\Models\DeliveryOrderReceipt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as DbSchema;
use UnitEnum;

class DeliveryOrderReceiptResource extends Resource
{
    use TranslatableResource;

    protected static ?string $model = DeliveryOrderReceipt::class;

    protected static string|UnitEnum|null $navigationGroup = 'Penerimaan Receiving';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::ClipboardDocumentCheck;

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getPendingSoftcopyCount();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $pendingCount = static::getPendingSoftcopyCount();

        if ($pendingCount <= 0) {
            return null;
        }

        return "{$pendingCount} DO memiliki softcopy sertifikat dari vendor yang perlu segera diajukan ulang";
    }

    public static function getPendingSoftcopyCount(): int
    {
        if (! DbSchema::hasTable('rdtv_compliances')) {
            return 0;
        }

        return DeliveryOrderReceipt::query()
            ->whereHas('rdtvCompliances', fn (Builder $query) => $query->where('status', 'Menunggu Review'))
            ->count();
    }

    public static function getNavigationLabel(): string
    {
        return __('Penerimaan DO');
    }

    public static function getModelLabel(): string
    {
        return __('Penerimaan DO');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Penerimaan DO');
    }

    public static function form(Schema $schema): Schema
    {
        return DeliveryOrderReceiptForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DeliveryOrderReceiptInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliveryOrderReceiptsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            QcHistoriesRelationManager::class,
            TransmittalsRelationManager::class,
            DelayLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveryOrderReceipts::route('/'),
            'create' => CreateDeliveryOrderReceipt::route('/create'),
            'view' => ViewDeliveryOrderReceipt::route('/{record}'),
            'edit' => EditDeliveryOrderReceipt::route('/{record}/edit'),
        ];
    }
}
