<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\PengirimanGudang\PengirimanGudangCluster;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\PurchaseOrderIssued;
use App\Models\WarehouseDestination;
use App\Models\WarehouseTransmittal;
use App\Models\WarehouseTransmittalItem;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class AntreanPengirimanGudang extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Daftar Pengiriman Gudang';

    protected static ?string $title = 'Daftar Pengiriman Gudang';

    protected static ?string $cluster = PengirimanGudangCluster::class;

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.antrean-pengiriman-gudang';

    public function table(Table $table): Table
    {
        $table
            ->query(
                DeliveryOrderReceiptDetail::query()
                    ->whereHas('deliveryOrderReceipt', function ($query) {
                        $query->whereHas('grsRdtvItems');
                    })
                    ->where('mrp_type', 'V1')
                    ->whereNotIn('id', WarehouseTransmittalItem::select('delivery_order_receipt_detail_id'))
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                // 📄 GRUP 1: INFORMASI DOKUMEN
                ColumnGroup::make('Informasi Dokumen', [
                    TextColumn::make('po_and_do')
                        ->label('Nomor PO & DO')
                        ->icon('heroicon-m-document-duplicate')
                        ->iconColor('primary')
                        ->color('primary')
                        ->weight(FontWeight::Bold)
                        ->getStateUsing(fn ($record) => $record->purchaseOrderIssued?->purchase_order_no ?? 'Tanpa PO')
                        ->description(function ($record) {
                            $doNumber = $record->deliveryOrderReceipt?->delivery_oder_no ?? '-';
                            $js = 'event.stopPropagation(); event.preventDefault(); ';
                            $js .= "if(navigator.clipboard) { navigator.clipboard.writeText('{$doNumber}'); } else { let t = document.createElement('textarea'); t.value = '{$doNumber}'; document.body.appendChild(t); t.select(); document.execCommand('copy'); document.body.removeChild(t); } ";
                            $js .= "new FilamentNotification().title('Nomor DO disalin!').success().send();";

                            return new HtmlString("<span onclick=\"{$js}\" class='text-gray-500 font-medium cursor-pointer hover:text-primary-600 hover:underline transition' title='Klik untuk menyalin DO'>DO: {$doNumber}</span>");
                        })
                        ->searchable(query: function (Builder $query, string $search) {
                            $query->whereHas('purchaseOrderIssued', function ($q) use ($search) {
                                $q->where('purchase_order_no', 'like', "%{$search}%");
                            })
                                ->orWhereHas('deliveryOrderReceipt', function ($q) use ($search) {
                                    $q->where('delivery_oder_no', 'like', "%{$search}%");
                                });
                        })
                        ->copyable()
                        ->copyMessage('Nomor PO disalin!')
                        ->sortable(query: function (Builder $query, string $direction) {
                            return $query->orderBy(
                                PurchaseOrderIssued::select('purchase_order_no')
                                    ->whereColumn('purchase_order_issueds.id', 'delivery_order_receipt_details.purchase_order_issued_id'),
                                $direction
                            );
                        }),
                ]),

                // 📦 GRUP 2: DETAIL MATERIAL
                ColumnGroup::make('Detail Material', [
                    TextColumn::make('material_code')
                        ->label('Material No')
                        ->icon(Heroicon::Cube)
                        ->iconColor('gray')
                        ->weight(FontWeight::SemiBold)
                        ->searchable()
                        ->sortable()
                        ->copyable()
                        ->copyMessage('Material No disalin!'),

                    TextColumn::make('description')
                        ->label('Deskripsi')
                        ->limit(40)
                        ->tooltip(fn ($record) => $record->description)
                        ->searchable(),
                ]),

                // 📊 GRUP 3: KUANTITAS & PENGIRIMAN
                ColumnGroup::make('Kuantitas & Pengiriman', [
                    TextColumn::make('quantity')
                        ->label('Qty DO')
                        ->numeric()
                        ->badge()
                        ->color('gray')
                        ->sortable(),

                    TextColumn::make('qty_mir')
                        ->label('Qty Diambil (MIR)')
                        ->state(function (DeliveryOrderReceiptDetail $record) {
                            return $record->materialIssueDetails()->sum('diserahkan');
                        })
                        ->numeric()
                        ->badge()
                        ->color('info'),

                    TextColumn::make('qty_sisa')
                        ->label('Sisa Dikirim')
                        ->state(function (DeliveryOrderReceiptDetail $record) {
                            $mirQty = $record->materialIssueDetails()->sum('diserahkan');

                            return max(0, $record->quantity - $mirQty);
                        })
                        ->numeric()
                        ->badge()
                        ->color(fn ($state) => $state > 0 ? 'warning' : 'success')
                        ->icon(fn ($state) => $state > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-circle'),
                ]),

                // 🎯 GRUP 4: TUJUAN PENGIRIMAN
                ColumnGroup::make('Tujuan Pengiriman', [
                    SelectColumn::make('warehouse_destination_id')
                        ->label('Gudang Tujuan')
                        ->options(WarehouseDestination::pluck('name', 'id'))
                        ->sortable()
                        ->searchable(),
                ]),
            ]);

        return $table
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('lihat_grs')
                    ->label('Lihat GRS')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->button()
                    ->outlined()
                    ->url(function (DeliveryOrderReceiptDetail $record) {
                        $grsItem = $record->deliveryOrderReceipt->grsRdtvItems->first();

                        return $grsItem ? asset('storage/'.$grsItem->file_path) : '#';
                    })
                    ->openUrlInNewTab()
                    ->visible(fn (DeliveryOrderReceiptDetail $record) => $record->deliveryOrderReceipt->grsRdtvItems->isNotEmpty()),
            ])
            ->toolbarActions([
                BulkAction::make('buat_transmittal')
                    ->label('Generate Transmittal Gudang')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->button()
                    ->outlined()
                    ->requiresConfirmation()
                    ->modalHeading('Generate Transmittal Gudang')
                    ->modalDescription('Pastikan Anda telah memilih Gudang Tujuan pada baris tabel sebelum men-generate transmittal.')
                    ->modalSubmitActionLabel('Ya, Generate')
                    ->action(function (Collection $records) {
                        $groupedRecords = $records->groupBy('warehouse_destination_id');

                        $count = 0;
                        foreach ($groupedRecords as $destinationId => $items) {
                            if (! $destinationId) {
                                continue;
                            }

                            $destination = WarehouseDestination::find($destinationId);

                            if (! $destination) {
                                continue;
                            }

                            // Check if there is an existing transmittal for this destination today
                            $transmittal = WarehouseTransmittal::where('warehouse_destination_id', $destination->id)
                                ->whereDate('created_at', now()->toDateString())
                                ->first();

                            if (! $transmittal) {
                                // Generate Transmittal No
                                $date = now()->format('Ymd');
                                $lastTransmittal = WarehouseTransmittal::whereDate('created_at', now()->toDateString())->latest()->first();
                                $sequence = $lastTransmittal ? (intval(substr($lastTransmittal->transmittal_no, -4)) + 1) : 1;
                                $transmittalNo = 'TRG-'.$date.'-'.str_pad($sequence, 4, '0', STR_PAD_LEFT);

                                // Create Transmittal
                                $transmittal = WarehouseTransmittal::create([
                                    'transmittal_no' => $transmittalNo,
                                    'warehouse_destination_id' => $destination->id,
                                    'tanggal' => now(),
                                    'created_by' => Auth::id(),
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
            ])
            ->emptyStateHeading('Belum ada Antrean Pengiriman')
            ->emptyStateDescription('Daftar pengiriman gudang saat ini kosong.')
            ->emptyStateIcon('heroicon-o-truck');
    }
}
