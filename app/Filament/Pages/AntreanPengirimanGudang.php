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

    protected static ?string $cluster = PengirimanGudangCluster::class;

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|BackedEnum|null $activeNavigationIcon = 'heroicon-s-truck';

    public static function getNavigationLabel(): string
    {
        return 'Daftar Pengiriman Gudang';
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Daftar Pengiriman Gudang';
    }

    protected string $view = 'filament.pages.antrean-pengiriman-gudang';

    public function table(Table $table): Table
    {
        $table
            ->query(function () {
                $user = Auth::user();
                return DeliveryOrderReceiptDetail::query()
                    ->whereHas('deliveryOrderReceipt', function ($query) {
                        $query->whereHas('grsRdtvItems');
                    })
                    ->whereNotIn('id', WarehouseTransmittalItem::select('delivery_order_receipt_detail_id'))
                    ->where(function (Builder $q) use ($user) {
                        if ($user->hasAnyRole(['Developer', 'AVP Receiving'])) {
                            $q->whereIn('mrp_type', ['V1', 'NONSTOCK']);
                            return;
                        }

                        $hasMatchedRole = false;

                        if ($user->hasRole('Admin Sparepart')) {
                            $hasMatchedRole = true;
                            $q->orWhere(function ($sub) {
                                $sub->where('mrp_type', 'V1')->where('material_type', 'ZSP');
                            });
                        }

                        if ($user->hasRole('Admin Chemical')) {
                            $hasMatchedRole = true;
                            $q->orWhere(function ($sub) {
                                $sub->where('mrp_type', 'V1')->where('material_type', 'ZSM');
                            });
                        }

                        if ($user->hasRole('Admin Bahan Baku')) {
                            $hasMatchedRole = true;
                            $q->orWhere(function ($sub) {
                                $sub->where('mrp_type', 'V1')->where('material_type', 'ZRM');
                            })->orWhere(function ($sub) {
                                $sub->whereIn('mrp_type', ['V1', 'NONSTOCK'])
                                    ->whereIn('material_type', ['ZSP', 'ZSM'])
                                    ->where(function ($desc) {
                                        $desc->where('description', 'like', '%HELIUM%')
                                            ->orWhere('description', 'like', '%ARGON%')
                                            ->orWhere('description', 'like', '%METHANOL%')
                                            ->orWhere('description', 'like', '%DIESEL%');
                                    });
                            });
                        }

                        if (!$hasMatchedRole) {
                            $q->whereRaw('1 = 0');
                        }
                    });
            })
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
                        ->getStateUsing(fn($record) => $record->purchaseOrderIssued?->purchase_order_no ?? 'Tanpa PO')
                        ->description(function ($record) {
                            $doNumber = $record->deliveryOrderReceipt?->delivery_order_no ?? '-';
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
                                    $q->where('delivery_order_no', 'like', "%{$search}%");
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
                        ->copyMessage('Material No disalin!')
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('description')
                        ->label('Deskripsi')
                        ->limit(40)
                        ->tooltip(fn($record) => $record->description)
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),

                // 📊 GRUP 3: KUANTITAS & PENGIRIMAN
                ColumnGroup::make('Kuantitas & Pengiriman', [
                    TextColumn::make('quantity')
                        ->label('Diterima')
                        ->numeric()
                        ->badge()
                        ->color('gray')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('qty_mir')
                        ->label('Diambil (MIR)')
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
                        ->color(fn($state) => $state > 0 ? 'warning' : 'success')
                        ->icon(fn($state) => $state > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-circle'),
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

                        return $grsItem ? asset('storage/' . $grsItem->file_path) : '#';
                    })
                    ->openUrlInNewTab()
                    ->visible(fn(DeliveryOrderReceiptDetail $record) => $record->deliveryOrderReceipt->grsRdtvItems->isNotEmpty()),
            ])
            ->toolbarActions([
                BulkAction::make('buat_transmittal')
                    ->label('Buat Transmittal Gudang')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->button()
                    ->outlined()
                    ->requiresConfirmation()
                    ->modalHeading('Buat Transmittal Gudang')
                    ->modalDescription('Pastikan Anda telah memilih Gudang Tujuan pada baris tabel sebelum men-Buat transmittal.')
                    ->modalSubmitActionLabel('Ya, Buat')
                    ->action(function (Collection $records) {
                        $groupedRecords = $records->groupBy('warehouse_destination_id');

                        $count = 0;
                        foreach ($groupedRecords as $destinationId => $items) {
                            if (!$destinationId) {
                                continue;
                            }

                            $destination = WarehouseDestination::find($destinationId);

                            if (!$destination) {
                                continue;
                            }

                            // Check if there is an existing transmittal for this destination today
                            $transmittal = WarehouseTransmittal::where('warehouse_destination_id', $destination->id)
                                ->whereDate('created_at', now()->toDateString())
                                ->first();

                            if (!$transmittal) {
                                // Buat Transmittal No
                                $date = now()->format('Ymd');
                                $lastTransmittal = WarehouseTransmittal::whereDate('created_at', now()->toDateString())->latest()->first();
                                $sequence = $lastTransmittal ? (intval(substr($lastTransmittal->transmittal_no, -4)) + 1) : 1;
                                $transmittalNo = 'TRG-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);

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
                                ->title('Pilih Tujuan Gudang terlebih dahulu pada baris tabel sebelum Buat.')
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
