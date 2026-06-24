<?php

namespace App\Filament\Pages;

use App\Models\DeliveryOrderReceiptDetail;
use App\Models\WarehouseDestination;
use App\Models\WarehouseTransmittal;
use App\Models\WarehouseTransmittalItem;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class AntreanPengirimanGudang extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Daftar Pengiriman Gudang';

    protected static ?string $title = 'Daftar Pengiriman Gudang';

    protected static string|\UnitEnum|null $navigationGroup = 'Warehouse';

    protected string $view = 'filament.pages.antrean-pengiriman-gudang';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DeliveryOrderReceiptDetail::query()
                    ->whereHas('deliveryOrderReceipt', function ($query) {
                        $query->whereHas('grsRdtvItems');
                    })
                    ->where('mrp_type', 'V1')
                    ->whereNotIn('id', \App\Models\WarehouseTransmittalItem::select('delivery_order_receipt_detail_id'))
            )
            ->columns([
                TextColumn::make('deliveryOrderReceipt.delivery_oder_no')
                    ->label('DO No')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('purchaseOrderIssued.purchase_order_no')
                    ->label('PO No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('material_code')
                    ->label('Material No')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(30),
                TextColumn::make('quantity')
                    ->label('Qty DO')
                    ->numeric(),
                TextColumn::make('qty_mir')
                    ->label('Qty MIR')
                    ->state(function (DeliveryOrderReceiptDetail $record) {
                        return $record->materialIssueDetails()->sum('diserahkan');
                    })
                    ->numeric(),
                TextColumn::make('qty_sisa')
                    ->label('Sisa Dikirim')
                    ->state(function (DeliveryOrderReceiptDetail $record) {
                        $mirQty = $record->materialIssueDetails()->sum('diserahkan');

                        return $record->quantity - $mirQty;
                    })
                    ->numeric()
                    ->color(fn($state) => $state > 0 ? 'success' : 'danger'),
                \Filament\Tables\Columns\SelectColumn::make('warehouse_destination_id')
                    ->label('Tujuan Gudang')
                    ->options(WarehouseDestination::pluck('name', 'id'))
                    ->sortable()
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('lihat_grs')
                    ->label('Lihat GRS')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->button()
                    ->outlined()
                    ->url(function (DeliveryOrderReceiptDetail $record) {
                        $grsItem = $record->deliveryOrderReceipt->grsRdtvItems->first();
                        return $grsItem ? asset('storage/' . $grsItem->file_path) : '#';
                    })
                    ->openUrlInNewTab()
                    ->visible(fn(DeliveryOrderReceiptDetail $record) => $record->deliveryOrderReceipt->grsRdtvItems->isNotEmpty()),
            ])
            ->toolbarActions([
                BulkAction::make('buat_transmittal')
                    ->label('Generate Transmittal Gudang')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->button()
                    ->outlined()
                    ->action(function (Collection $records) {
                        $groupedRecords = $records->groupBy('warehouse_destination_id');

                        $count = 0;
                        foreach ($groupedRecords as $destinationId => $items) {
                            if (!$destinationId) {
                                continue;
                            }

                            $destination = WarehouseDestination::find($destinationId);

                            // Check if there is an existing transmittal for this destination today
                            $transmittal = WarehouseTransmittal::where('warehouse_destination_id', $destination->id)
                                ->whereDate('created_at', now()->toDateString())
                                ->first();

                            if (!$transmittal) {
                                // Generate Transmittal No
                                $date = now()->format('Ymd');
                                $lastTransmittal = WarehouseTransmittal::whereDate('created_at', now()->toDateString())->latest()->first();
                                $sequence = $lastTransmittal ? (intval(substr($lastTransmittal->transmittal_no, -4)) + 1) : 1;
                                $transmittalNo = 'TRG-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);

                                // Create Transmittal
                                $transmittal = WarehouseTransmittal::create([
                                    'transmittal_no' => $transmittalNo,
                                    'warehouse_destination_id' => $destination->id,
                                    'tanggal' => now(),
                                    'created_by' => auth()->id(),
                                ]);
                            }

                            foreach ($items as $record) {
                                WarehouseTransmittalItem::firstOrCreate([
                                    'warehouse_transmittal_id' => $transmittal->id,
                                    'delivery_order_receipt_detail_id' => $record->id,
                                ]);
                            }

                            $count++;
                        }

                        if ($count > 0) {
                            Notification::make()
                                ->title("Berhasil diproses! $count Gudang tujuan terupdate.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Pilih Tujuan Gudang terlebih dahulu pada baris tabel sebelum generate.')
                                ->warning()
                                ->send();
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
}
