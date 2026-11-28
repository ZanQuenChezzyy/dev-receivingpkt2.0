<?php

namespace App\Filament\Resources\WarehouseTransmittals\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'detail.deliveryOrderReceipt',
                'detail.purchaseOrderIssued',
                'batchItem.deliveryOrderReceipt',
            ]))
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('do_no')
                    ->label('DO No')
                    ->getStateUsing(fn ($record) => $record->batchItem?->deliveryOrderReceipt?->delivery_order_no
                        ?? ($record->batchItem?->reference_do_no ?: null)
                        ?? $record->detail?->deliveryOrderReceipt?->delivery_order_no
                        ?? '-'
                    )
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search): void {
                            $q->whereHas('detail.deliveryOrderReceipt', fn (Builder $sub) => $sub->where('delivery_order_no', 'like', "%{$search}%"))
                                ->orWhereHas('batchItem', function (Builder $sub) use ($search): void {
                                    $sub->where('reference_do_no', 'like', "%{$search}%")
                                        ->orWhereHas('deliveryOrderReceipt', fn (Builder $doSub) => $doSub->where('delivery_order_no', 'like', "%{$search}%"));
                                });
                        });
                    }),

                TextColumn::make('po_no')
                    ->label('PO No')
                    ->getStateUsing(fn ($record) => $record->batchItem?->po_number
                        ?: ($record->detail?->purchaseOrderIssued?->purchase_order_no ?? '-')
                    )
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search): void {
                            $q->whereHas('detail.purchaseOrderIssued', fn (Builder $sub) => $sub->where('purchase_order_no', 'like', "%{$search}%"))
                                ->orWhereHas('batchItem', fn (Builder $sub) => $sub->where('po_number', 'like', "%{$search}%"));
                        });
                    }),

                TextColumn::make('mat_doc_105')
                    ->label('MatDoc 105')
                    ->getStateUsing(fn ($record) => $record->batchItem?->mat_doc_105 ?? '-')
                    ->badge()
                    ->color('info')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'batchItem',
                        fn (Builder $sub) => $sub->where('mat_doc_105', 'like', "%{$search}%"),
                    )),

                TextColumn::make('material_code')
                    ->label('Material No')
                    ->getStateUsing(fn ($record) => $record->batchItem?->material_code ?: ($record->detail?->material_code ?? '-'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search): void {
                            $q->whereHas('detail', fn (Builder $sub) => $sub->where('material_code', 'like', "%{$search}%"))
                                ->orWhereHas('batchItem', fn (Builder $sub) => $sub->where('material_code', 'like', "%{$search}%"));
                        });
                    }),

                TextColumn::make('description')
                    ->label('Description')
                    ->getStateUsing(fn ($record) => $record->batchItem?->description ?: ($record->detail?->description ?? '-'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search): void {
                            $q->whereHas('detail', fn (Builder $sub) => $sub->where('description', 'like', "%{$search}%"))
                                ->orWhereHas('batchItem', fn (Builder $sub) => $sub->where('description', 'like', "%{$search}%"));
                        });
                    })
                    ->limit(35),

                TextColumn::make('quantity')
                    ->label('Qty')
                    ->getStateUsing(fn ($record) => $record->batchItem?->quantity ?? $record->detail?->quantity ?? 0)
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
                        $baseQty = (float) ($record->batchItem?->quantity ?? $record->detail?->quantity ?? 0);
                        if (! $record->detail) {
                            return $baseQty;
                        }

                        $mirQty = (float) $record->detail->materialIssueDetails()->sum('diserahkan');

                        return max(0, $baseQty - $mirQty);
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
