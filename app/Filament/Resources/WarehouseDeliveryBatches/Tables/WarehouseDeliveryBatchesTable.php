<?php

namespace App\Filament\Resources\WarehouseDeliveryBatches\Tables;

use App\Models\WarehouseDeliveryBatch;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
            ->defaultSort('start_date', 'desc')
            ->columns([
                TextColumn::make('batch_title')
                    ->label('Judul Batch')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('date_range')
                    ->label('Rentang Tanggal')
                    ->icon('heroicon-m-calendar-days')
                    ->iconColor('primary')
                    ->weight(FontWeight::Bold)
                    ->color('primary')
                    ->getStateUsing(function (WarehouseDeliveryBatch $record) {
                        $start = $record->start_date ? Carbon::parse($record->start_date)->locale('id') : null;
                        $end = $record->end_date ? Carbon::parse($record->end_date)->locale('id') : null;

                        if (! $start && ! $end) {
                            return '-';
                        }

                        if ($start && ! $end) {
                            return $start->translatedFormat('d F Y');
                        }

                        if (! $start && $end) {
                            return $end->translatedFormat('d F Y');
                        }

                        if ($start->isSameDay($end)) {
                            return $start->translatedFormat('d F Y');
                        }

                        // Bulan dan tahun sama (contoh: 16 - 30 Juli 2026)
                        if ($start->format('Y-m') === $end->format('Y-m')) {
                            return $start->format('d').' - '.$end->translatedFormat('d F Y');
                        }

                        // Tahun sama, beda bulan (contoh: 16 Juli - 18 Agustus 2026)
                        if ($start->format('Y') === $end->format('Y')) {
                            return $start->translatedFormat('d F').' - '.$end->translatedFormat('d F Y');
                        }

                        // Beda tahun (contoh: 28 Desember 2025 - 5 Januari 2026)
                        return $start->translatedFormat('d F Y').' - '.$end->translatedFormat('d F Y');
                    })
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('start_date', $direction);
                    }),

                TextColumn::make('total_items')
                    ->label('Jumlah Data')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.').' Data')
                    ->sortable(),

                TextColumn::make('delivered_progress')
                    ->label('Status Pengiriman')
                    ->badge()
                    ->getStateUsing(function (WarehouseDeliveryBatch $record) {
                        $total = (int) $record->total_items;
                        $delivered = $record->items()->where('status', 'delivered')->count();

                        if ($total === 0) {
                            return '0 / 0 Terkirim';
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

                TextColumn::make('uploader.name')
                    ->label('Diupload Oleh')
                    ->icon('heroicon-m-user')
                    ->description(fn (WarehouseDeliveryBatch $record) => $record->created_at ? Carbon::parse($record->created_at)->locale('id')->translatedFormat('d M Y, H:i') : null)
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('total_value')
                    ->label('Total Nominal')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('lihat_items')
                        ->label('Lihat Detail Material')
                        ->icon('heroicon-o-table-cells')
                        ->color('primary')
                        ->modalHeading(function (WarehouseDeliveryBatch $record) {
                            $start = $record->start_date ? Carbon::parse($record->start_date)->locale('id') : null;
                            $end = $record->end_date ? Carbon::parse($record->end_date)->locale('id') : null;
                            $range = ($start && $end && $start->format('Y-m') === $end->format('Y-m'))
                                ? $start->format('d').' - '.$end->translatedFormat('d F Y')
                                : ($start?->translatedFormat('d M Y').' - '.$end?->translatedFormat('d M Y'));

                            return "Detail Material Periode: {$range}";
                        })
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
                ]),
            ]);
    }
}
