<?php

namespace App\Filament\Resources\WarehouseTransmittals\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Transmittal Items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Read-only, so no form schema needed
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('detail.deliveryOrderReceipt.delivery_order_no')
                    ->label('DO No'),
                TextColumn::make('detail.purchaseOrderIssued.purchase_order_no')
                    ->label('PO No'),
                TextColumn::make('detail.material_code')
                    ->label('Material No'),
                TextColumn::make('detail.description')
                    ->label('Description')
                    ->limit(30),
                TextColumn::make('detail.quantity')
                    ->label('Qty DO')
                    ->numeric(),
                TextColumn::make('detail.qty_mir')
                    ->label('Qty MIR')
                    ->state(function ($record) {
                        return $record->detail->materialIssueDetails()->sum('diserahkan');
                    })
                    ->numeric(),
                TextColumn::make('detail.qty_sisa')
                    ->label('Sisa Dikirim')
                    ->state(function ($record) {
                        $mirQty = $record->detail->materialIssueDetails()->sum('diserahkan');

                        return $record->detail->quantity - $mirQty;
                    })
                    ->numeric()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }
}
