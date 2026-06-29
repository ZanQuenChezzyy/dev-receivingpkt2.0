<?php

namespace App\Filament\Resources\PurchaseOrderIssueds\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class PurchaseOrderIssuedsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->deferLoading()
            ->poll('10s')
            ->columns([
                \Filament\Tables\Columns\ColumnGroup::make('Informasi PO', [
                    TextColumn::make('purchase_order_and_item')
                        ->label('Purchase Order & Item')
                        ->searchable()
                        ->placeholder('None')
                        ->icon('heroicon-m-document-text')
                        ->weight(\Filament\Support\Enums\FontWeight::Bold)
                        ->color('primary')
                        ->copyable(),

                    TextColumn::make('purchase_order_no')
                        ->label('Purchase Order')
                        ->searchable()
                        ->sortable()
                        ->placeholder('None')
                        ->color('primary')
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('item_no')
                        ->label('Item')
                        ->sortable()
                        ->placeholder('None')
                        ->alignCenter()
                        ->color('gray')
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),

                \Filament\Tables\Columns\ColumnGroup::make('Detail Material', [
                    TextColumn::make('material_code')
                        ->label('Kode Material')
                        ->sortable()
                        ->placeholder('None')
                        ->icon('heroicon-m-cube')
                        ->color('gray'),

                    TextColumn::make('description')
                        ->label('Deskripsi')
                        ->limit(20)
                        ->wrap()
                        ->placeholder('None')
                        ->tooltip(fn ($record) => $record->description),

                    TextColumn::make('quantity_uoi')
                        ->label('Kuantitas')
                        ->getStateUsing(fn ($record) => $record->qty_po.' '.$record->uoi)
                        ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('qty_po', $direction))
                        ->badge()
                        ->placeholder('None')
                        ->color('info')
                        ->alignRight(),

                    TextColumn::make('uoi')
                        ->label('UoI')
                        ->placeholder('None')
                        ->toggleable(isToggledHiddenByDefault: true),
                        
                    TextColumn::make('material_type')
                        ->label('Tipe Material')
                        ->sortable()
                        ->placeholder('None')
                        ->color(fn ($state) => match ($state) {
                            'ZSP' => 'warning',
                            'ZFP', 'ZRM' => 'danger',
                            'ZSM', 'ZPM' => 'info',
                            default => 'warning',
                        })
                        ->badge()
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('mrp_type')
                        ->label('MRP Type')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'V1' => 'success',
                            'PD' => 'warning',
                            'INVESTASI' => 'info',
                            'NONSTOCK' => 'danger',
                            default => 'gray',
                        })
                        ->alignCenter()
                        ->placeholder('None')
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('aac')
                        ->label('AAC')
                        ->sortable()
                        ->placeholder('None')
                        ->color('info')
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('abc_indicator')
                        ->label('ABC')
                        ->sortable()
                        ->placeholder('None')
                        ->color('success')
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),

                \Filament\Tables\Columns\ColumnGroup::make('Komersial & Vendor', [
                    TextColumn::make('vendor_name')
                        ->label('Vendor')
                        ->searchable()
                        ->placeholder('None')
                        ->icon('heroicon-m-building-storefront')
                        ->tooltip(fn ($record) => $record->vendor_id_name)
                        ->limit(15),

                    TextColumn::make('currency')
                        ->label('Mata Uang')
                        ->badge()
                        ->sortable()
                        ->placeholder('None')
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('net_price')
                        ->label('Net Price')
                        ->money(fn($record) => $record->currency ?? 'IDR')
                        ->sortable()
                        ->placeholder('0')
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('total_amount_in_lc')
                        ->label('Total Amount in LC')
                        ->money(fn($record) => $record->currency ?? 'IDR')
                        ->sortable()
                        ->placeholder('0')
                        ->color('success')
                        ->toggleable(isToggledHiddenByDefault: true),
                        
                    TextColumn::make('requisitioner')
                        ->label('Requisitioner')
                        ->placeholder('none')
                        ->toggleable(isToggledHiddenByDefault: true),
                        
                    TextColumn::make('incoterm')
                        ->label('Incoterm')
                        ->tooltip(fn ($record) => $record->incoterm)
                        ->limit(15)
                        ->placeholder('None')
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),

                \Filament\Tables\Columns\ColumnGroup::make('Jadwal & Status', [
                    TextColumn::make('po_status')
                        ->label('Status')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'A' => 'success',
                            'B' => 'warning',
                            'C' => 'danger',
                            default => 'gray',
                        })
                        ->alignCenter()
                        ->placeholder('None'),

                    TextColumn::make('delivery_date_po')
                        ->label('Tgl Kirim PO')
                        ->date('d F Y')
                        ->icon('heroicon-m-truck')
                        ->sortable(),

                    TextColumn::make('date_create')
                        ->label('Tgl Rilis PO')
                        ->date('d F Y')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),

                \Filament\Tables\Columns\ColumnGroup::make('Sistem', [
                    TextColumn::make('created_at')
                        ->label('Dibuat')
                        ->dateTime('d M Y H:i')
                        ->sortable()
                        ->placeholder('None')
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('updated_at')
                        ->label('Update Terakhir')
                        ->dateTime('d M Y H:i')
                        ->sortable()
                        ->placeholder('None')
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),
            ])
            ->filters([
                SelectFilter::make('mrp_type')
                    ->label('MRP Type')
                    ->placeholder('Pilih MRP Type')
                    ->options([
                        'V1' => 'V1',
                        'PD' => 'PD',
                        'INVESTASI' => 'INVESTASI',
                        'NONSTOCK' => 'NONSTOCK',
                    ])
                    ->native(false),
                DateRangeFilter::make('date_create')
                    ->label('Tanggal PO Dibuat')
                    ->placeholder('Pilih rentang tanggal')
                    ->icon('heroicon-s-arrow-path'),
                DateRangeFilter::make('created_at')
                    ->label('Dibuat Saat')
                    ->placeholder('Pilih rentang tanggal')
                    ->icon('heroicon-s-arrow-path'),
            ], layout: FiltersLayout::Dropdown)
            ->filtersFormWidth(Width::FourExtraLarge)
            ->filtersFormColumns(3)
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make('Lihat')
                        ->color('gray')
                        ->slideOver(),
                    EditAction::make()
                        ->color('info')
                        ->slideOver(),
                    DeleteAction::make()
                        ->requiresConfirmation(),
                ])
                    ->label('')
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->size(Size::Small)
                    ->color('info')
                    ->outlined()
                    ->button(),
            ], position: RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
