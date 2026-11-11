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
                TextColumn::make('do_no')
                    ->label('DO No')
                    ->getStateUsing(fn ($record) => $record->detail?->deliveryOrderReceipt?->delivery_order_no
                        ?? $record->batchItem?->deliveryOrderReceipt?->delivery_order_no
                        ?? ($record->batchItem?->reference_do_no ?: '-')
                    )
                    ->searchable(),

                TextColumn::make('po_no')
                    ->label('PO No')
                    ->getStateUsing(fn ($record) => $record->detail?->purchaseOrderIssued?->purchase_order_no
                        ?? $record->batchItem?->po_number
                        ?? '-'
                    )
                    ->searchable(),

                TextColumn::make('mat_doc_105')
                    ->label('MatDoc 105')
                    ->getStateUsing(fn ($record) => $record->batchItem?->mat_doc_105 ?? '-')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                TextColumn::make('material_code')
                    ->label('Material No')
                    ->getStateUsing(fn ($record) => $record->detail?->material_code ?? $record->batchItem?->material_code ?? '-')
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->getStateUsing(fn ($record) => $record->detail?->description ?? $record->batchItem?->description ?? '-')
                    ->limit(35),

                TextColumn::make('quantity')
                    ->label('Qty')
                    ->getStateUsing(fn ($record) => $record->detail?->quantity ?? $record->batchItem?->quantity ?? 0)
                    ->numeric(decimalPlaces: 3),

                TextColumn::make('qty_mir')
                    ->label('Qty MIR')
                    ->getStateUsing(function ($record) {
                        if (! $record->detail) {
                            return 0;
                        }

                        return $record->detail->materialIssueDetails()->sum('diserahkan');
                    })
                    ->numeric(decimalPlaces: 3),

                TextColumn::make('qty_sisa')
                    ->label('Sisa Dikirim')
                    ->getStateUsing(function ($record) {
                        if (! $record->detail) {
                            return 0;
                        }

                        $mirQty = $record->detail->materialIssueDetails()->sum('diserahkan');

                        return max(0, $record->detail->quantity - $mirQty);
                    })
                    ->numeric(decimalPlaces: 3)
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
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
