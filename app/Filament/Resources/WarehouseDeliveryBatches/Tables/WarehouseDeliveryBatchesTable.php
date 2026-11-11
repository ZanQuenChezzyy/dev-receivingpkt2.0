<?php

namespace App\Filament\Resources\WarehouseDeliveryBatches\Tables;

use App\Models\WarehouseDeliveryBatch;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class WarehouseDeliveryBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('batch_title')
                    ->label('Daftar / Judul Batch')
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->color('primary')
                    ->description(function (WarehouseDeliveryBatch $record) {
                        $start = $record->start_date ? Carbon::parse($record->start_date)->format('d M Y') : '-';
                        $end = $record->end_date ? Carbon::parse($record->end_date)->format('d M Y') : '-';

                        return "Periode Penerimaan: {$start} s/d {$end}";
                    })
                    ->wrap(),

                TextColumn::make('total_items')
                    ->label('Jumlah Data')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.').' Material')
                    ->sortable(),

                TextColumn::make('delivered_progress')
                    ->label('Status Pengiriman')
                    ->badge()
                    ->getStateUsing(function (WarehouseDeliveryBatch $record) {
                        $total = (int) $record->total_items;
                        $delivered = $record->items()->where('status', 'delivered')->count();

                        if ($total === 0) {
                            return '0 Material';
                        }

                        return "{$delivered} / {$total} Terkirim";
                    })
                    ->color(function (WarehouseDeliveryBatch $record) {
                        $total = (int) $record->total_items;
                        $delivered = $record->items()->where('status', 'delivered')->count();

                        if ($total > 0 && $delivered === $total) {
                            return 'success';
                        }

                        if ($delivered > 0) {
                            return 'warning';
                        }

                        return 'gray';
                    }),

                TextColumn::make('total_value')
                    ->label('Total Nominal')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('uploader.name')
                    ->label('Diupload Oleh')
                    ->icon('heroicon-m-user')
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Waktu Upload')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->recordActions([
                Action::make('lihat_items')
                    ->label('Lihat Detail Material')
                    ->icon('heroicon-o-table-cells')
                    ->color('primary')
                    ->modalHeading(fn (WarehouseDeliveryBatch $record) => "Detail Material: {$record->batch_title}")
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function (WarehouseDeliveryBatch $record) {
                        $items = $record->items()
                            ->with(['deliveryOrderReceipt', 'warehouseTransmittal.destination'])
                            ->orderBy('posting_date', 'asc')
                            ->get();

                        return view('filament.components.warehouse-batch-items-modal', [
                            'batch' => $record,
                            'items' => $items,
                        ]);
                    }),

                DeleteAction::make()
                    ->modalHeading('Hapus Batch Pengiriman MB51')
                    ->modalDescription('Apakah Anda yakin ingin menghapus batch ini? Seluruh data item material di dalamnya akan ikut terhapus.')
                    ->successNotificationTitle('Batch pengiriman berhasil dihapus.'),
            ]);
    }
}
