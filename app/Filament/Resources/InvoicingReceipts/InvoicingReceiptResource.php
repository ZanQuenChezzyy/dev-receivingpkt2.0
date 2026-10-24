<?php

namespace App\Filament\Resources\InvoicingReceipts;

use App\Filament\Resources\InvoicingReceipts\Pages\CreateInvoicingReceipt;
use App\Filament\Resources\InvoicingReceipts\Pages\EditInvoicingReceipt;
use App\Filament\Resources\InvoicingReceipts\Pages\ListInvoicingReceipts;
use App\Filament\Resources\InvoicingReceipts\Pages\ViewInvoicingReceipt;
use App\Filament\Resources\InvoicingReceipts\Schemas\InvoicingReceiptForm;
use App\Filament\Resources\InvoicingReceipts\Schemas\InvoicingReceiptInfolist;
use App\Filament\Resources\InvoicingReceipts\Tables\InvoicingReceiptsTable;
use App\Models\InvoicingReceipt;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class InvoicingReceiptResource extends Resource
{
    protected static ?string $model = InvoicingReceipt::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Penerimaan Jasa / Invoice';

    protected static ?int $navigationSort = 1;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static string|\BackedEnum|null $activeNavigationIcon = 'heroicon-s-receipt-percent';

    public static function getNavigationLabel(): string
    {
        return 'Invoice Barang';
    }

    public static function getModelLabel(): string
    {
        return 'Invoice Barang';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Invoice Barang';
    }

    public static function form(Schema $schema): Schema
    {
        return InvoicingReceiptForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InvoicingReceiptInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoicingReceiptsTable::configure($table);
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
            'index' => ListInvoicingReceipts::route('/'),
            'create' => CreateInvoicingReceipt::route('/create'),
            'view' => ViewInvoicingReceipt::route('/{record}'),
            'edit' => EditInvoicingReceipt::route('/{record}/edit'),
        ];
    }
}
