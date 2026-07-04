<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\PengirimanGudang\PengirimanGudangCluster;
use App\Models\DeliveryOrderReceipt;
use App\Models\PurchaseOrderIssued;
use App\Models\WarehouseDestination;
use App\Models\WarehouseTransmittal;
use App\Models\WarehouseTransmittalItem;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
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

    public function getTitle(): string|Htmlable
    {
        return 'Daftar Pengiriman Gudang';
    }

    protected string $view = 'filament.pages.antrean-pengiriman-gudang';

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $user = Auth::user();

                return DeliveryOrderReceipt::query()
                    ->whereHas('grsRdtvItems')
                    ->whereHas('deliveryOrderReceiptDetails', function ($query) use ($user) {
                        $query->whereNotIn('id', WarehouseTransmittalItem::select('delivery_order_receipt_detail_id'))
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

                                if (! $hasMatchedRole) {
                                    $q->whereRaw('1 = 0');
                                }
                            });
                    });
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                ColumnGroup::make('Informasi Pengiriman', [
                    TextColumn::make('po_and_do')
                        ->label('Nomor PO & DO')
                        ->icon('heroicon-m-document-duplicate')
                        ->iconColor('primary')
                        ->color('primary')
                        ->weight(FontWeight::Bold)
                        ->getStateUsing(fn (DeliveryOrderReceipt $record) => $record->deliveryOrderReceiptDetails->first()?->purchaseOrderIssued?->purchase_order_no ?? 'Tanpa PO')
                        ->description(function (DeliveryOrderReceipt $record) {
                            $doNumber = $record->delivery_order_no ?? '-';
                            $js = 'event.stopPropagation(); event.preventDefault(); ';
                            $js .= "if(navigator.clipboard) { navigator.clipboard.writeText('{$doNumber}'); } else { let t = document.createElement('textarea'); t.value = '{$doNumber}'; document.body.appendChild(t); t.select(); document.execCommand('copy'); document.body.removeChild(t); } ";
                            $js .= "new FilamentNotification().title('Nomor DO disalin!').success().send();";

                            return new HtmlString("<span onclick=\"{$js}\" class='text-gray-500 font-medium cursor-pointer hover:text-primary-600 hover:underline transition' title='Klik untuk menyalin DO'>DO: {$doNumber}</span>");
                        })
                        ->searchable(query: function (Builder $query, string $search) {
                            $query->whereHas('deliveryOrderReceiptDetails.purchaseOrderIssued', function ($q) use ($search) {
                                $q->where('purchase_order_no', 'like', "%{$search}%");
                            })
                                ->orWhere('delivery_order_no', 'like', "%{$search}%");
                        })
                        ->copyable()
                        ->copyMessage('Nomor PO disalin!')
                        ->sortable(query: function (Builder $query, string $direction) {
                            return $query->orderBy(
                                PurchaseOrderIssued::select('purchase_order_no')
                                    ->join('delivery_order_receipt_details', 'delivery_order_receipt_details.purchase_order_issued_id', '=', 'purchase_order_issueds.id')
                                    ->whereColumn('delivery_order_receipt_details.delivery_order_receipt_id', 'delivery_order_receipts.id')
                                    ->limit(1),
                                $direction
                            );
                        }),

                    TextColumn::make('total_items')
                        ->label('Total Item')
                        ->getStateUsing(fn (DeliveryOrderReceipt $record) => $record->deliveryOrderReceiptDetails()
                            ->whereNotIn('id', WarehouseTransmittalItem::select('delivery_order_receipt_detail_id'))
                            ->count().' Item')
                        ->badge()
                        ->color('info')
                        ->icon('heroicon-m-cube'),

                    TextColumn::make('material_code_item')
                        ->label('Material Code')
                        ->iconColor('gray')
                        ->getStateUsing(function (DeliveryOrderReceipt $record) {
                            $details = $record->deliveryOrderReceiptDetails()
                                ->whereNotIn('id', WarehouseTransmittalItem::select('delivery_order_receipt_detail_id'))
                                ->get();

                            if ($details->isEmpty()) {
                                return ['-'];
                            }

                            return $details->map(function ($detail) {
                                return $detail->material_code ?? '-';
                            })->toArray();
                        })
                        ->listWithLineBreaks()
                        ->bulleted()
                        ->limitList(2)
                        ->expandableLimitedList()
                        ->searchable(query: function (Builder $query, string $search) {
                            $query->whereHas('deliveryOrderReceiptDetails', function ($q) use ($search) {
                                $q->where('material_code', 'like', "%{$search}%");
                            });
                        })
                        ->tooltip(function (DeliveryOrderReceipt $record) {
                            $details = $record->deliveryOrderReceiptDetails()
                                ->whereNotIn('id', WarehouseTransmittalItem::select('delivery_order_receipt_detail_id'))
                                ->get();

                            $htmlList = '';
                            foreach ($details->pluck('material_code') as $index => $code) {
                                if (! $code) {
                                    continue;
                                }
                                $number = $index + 1;
                                $htmlList .= "{$number}. {$code}<br>";
                            }

                            return new HtmlString($htmlList ?: '-');
                        })
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('deskripsi_item')
                        ->label('Daftar Material')
                        ->icon(Heroicon::Cube)
                        ->iconColor('gray')
                        ->getStateUsing(function (DeliveryOrderReceipt $record) {
                            // Ambil detail yang belum ditransmittal agar akurat dengan antrean
                            $details = $record->deliveryOrderReceiptDetails()
                                ->whereNotIn('id', WarehouseTransmittalItem::select('delivery_order_receipt_detail_id'))
                                ->get();

                            if ($details->isEmpty()) {
                                return ['Tidak ada item'];
                            }

                            return $details->map(function ($detail) {
                                return $detail->description;
                            })->toArray();
                        })
                        ->listWithLineBreaks() // Menampilkan data array berbaris ke bawah
                        ->bulleted() // Menambahkan titik (bullet)
                        ->limitList(2) // Batasi tampilan awal misal 2 baris
                        ->limit(20)
                        ->expandableLimitedList() // Bisa diklik "View more"
                        ->searchable(query: function (Builder $query, string $search) {
                            $query->whereHas('deliveryOrderReceiptDetails', function ($q) use ($search) {
                                $q->where('description', 'like', "%{$search}%");
                            });
                        })
                        ->tooltip(function (DeliveryOrderReceipt $record) {
                            $details = $record->deliveryOrderReceiptDetails()
                                ->whereNotIn('id', WarehouseTransmittalItem::select('delivery_order_receipt_detail_id'))
                                ->get();

                            $htmlList = '';
                            foreach ($details->pluck('description') as $index => $desc) {
                                $number = $index + 1;
                                $htmlList .= "{$number}. {$desc}<br>"; // Gunakan <br> sebagai enter HTML
                            }

                            return new HtmlString($htmlList);
                        })
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('buat_transmittal_row')
                    ->label('Buat Transmittal')
                    ->icon(Heroicon::Truck)
                    ->color('success')
                    ->button()
                    ->outlined()
                    ->schema([
                        Select::make('warehouse_destination_id')
                            ->label('Pilih Gudang Tujuan')
                            ->options(WarehouseDestination::pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->helperText('Gudang tujuan ini akan berlaku untuk semua item dalam PO ini.'),
                    ])
                    ->modalHeading('Buat Transmittal Gudang')
                    ->modalDescription('Semua item dari PO ini akan dikirimkan ke gudang yang sama.')
                    ->modalSubmitActionLabel('Ya, Buat Transmittal')
                    ->modalSubmitAction(fn ($action) => $action->color('primary'))
                    ->action(function (DeliveryOrderReceipt $record, array $data) {
                        $destinationId = $data['warehouse_destination_id'];
                        $destination = WarehouseDestination::find($destinationId);

                        if (! $destination) {
                            return;
                        }

                        $transmittal = WarehouseTransmittal::where('warehouse_destination_id', $destination->id)
                            ->whereDate('created_at', now()->toDateString())
                            ->first();

                        if (! $transmittal) {
                            $date = now()->format('Ymd');
                            $lastTransmittal = WarehouseTransmittal::whereDate('created_at', now()->toDateString())->latest()->first();
                            $sequence = $lastTransmittal ? (intval(substr($lastTransmittal->transmittal_no, -4)) + 1) : 1;
                            $transmittalNo = 'TRG-'.$date.'-'.str_pad($sequence, 4, '0', STR_PAD_LEFT);

                            $transmittal = WarehouseTransmittal::create([
                                'transmittal_no' => $transmittalNo,
                                'warehouse_destination_id' => $destination->id,
                                'tanggal' => now(),
                                'created_by' => Auth::id(),
                            ]);
                        }

                        $countItems = 0;
                        $user = Auth::user();

                        $items = $record->deliveryOrderReceiptDetails()
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

                                if (! $hasMatchedRole) {
                                    $q->whereRaw('1 = 0');
                                }
                            })
                            ->get();

                        foreach ($items as $item) {
                            WarehouseTransmittalItem::firstOrCreate([
                                'warehouse_transmittal_id' => $transmittal->id,
                                'delivery_order_receipt_detail_id' => $item->id,
                            ]);
                            $countItems++;
                        }

                        if ($countItems > 0) {
                            Notification::make()
                                ->title("Berhasil diproses! $countItems item dimasukkan ke transmittal tujuan ".$destination->name.'.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Tidak ada item yang valid untuk diproses berdasarkan hak akses Anda.')
                                ->warning()
                                ->send();
                        }
                    }),

                ActionGroup::make([
                    Action::make('lihat_item')
                        ->label('Lihat Item')
                        ->icon('heroicon-o-list-bullet')
                        ->color('gray')
                        ->schema([
                            RepeatableEntry::make('deliveryOrderReceiptDetails')
                                ->label('')
                                ->schema([
                                    Grid::make(4)->schema([
                                        TextEntry::make('material_code')
                                            ->label('Material No')
                                            ->weight(FontWeight::Bold)
                                            ->icon('heroicon-m-cube')
                                            ->color('primary')
                                            ->copyable(),

                                        TextEntry::make('description')
                                            ->label('Deskripsi'),

                                        TextEntry::make('quantity')
                                            ->label('Qty Diterima')
                                            ->badge()
                                            ->color('success'),

                                        TextEntry::make('qty_mir')
                                            ->label('Diambil (MIR)')
                                            ->state(fn ($record) => $record->materialIssueDetails()->sum('diserahkan'))
                                            ->badge()
                                            ->color('warning'),
                                    ]),
                                ])
                                ->columnSpanFull(),
                        ])
                        ->modalHeading(fn (DeliveryOrderReceipt $record) => 'Daftar Item: '.$record->delivery_order_no)
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup'),

                    Action::make('lihat_grs')
                        ->label('Lihat GRS')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(function (DeliveryOrderReceipt $record) {
                            $grsItem = $record->grsRdtvItems->first();

                            return $grsItem ? asset('storage/'.$grsItem->file_path) : '#';
                        })
                        ->openUrlInNewTab()
                        ->visible(fn (DeliveryOrderReceipt $record) => $record->grsRdtvItems->isNotEmpty()),
                ])
                    ->label('')
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->button()
                    ->color('info')
                    ->outlined(),
            ])
            ->toolbarActions([
                BulkAction::make('buat_transmittal')
                    ->label('Buat Transmittal Gudang')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->button()
                    ->outlined()
                    ->schema([
                        Select::make('warehouse_destination_id')
                            ->label('Pilih Gudang Tujuan')
                            ->options(WarehouseDestination::pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->helperText('Gudang tujuan ini akan berlaku untuk semua item dalam PO yang Anda pilih.'),
                    ])
                    ->modalHeading('Buat Transmittal Gudang')
                    ->modalDescription('Semua item dari PO yang terpilih akan dikirimkan ke gudang yang sama.')
                    ->modalSubmitActionLabel('Ya, Buat Transmittal')
                    ->action(function (Collection $records, array $data) {
                        $destinationId = $data['warehouse_destination_id'];
                        $destination = WarehouseDestination::find($destinationId);

                        if (! $destination) {
                            return;
                        }

                        // Check if there is an existing transmittal for this destination today
                        $transmittal = WarehouseTransmittal::where('warehouse_destination_id', $destination->id)
                            ->whereDate('created_at', now()->toDateString())
                            ->first();

                        if (! $transmittal) {
                            // Buat Transmittal No
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

                        $countItems = 0;
                        $user = Auth::user();

                        foreach ($records as $doRecord) {
                            // Hanya ambil item yang belum ada di transmittal dan sesuai role
                            $items = $doRecord->deliveryOrderReceiptDetails()
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

                                    if (! $hasMatchedRole) {
                                        $q->whereRaw('1 = 0');
                                    }
                                })
                                ->get();

                            foreach ($items as $item) {
                                WarehouseTransmittalItem::firstOrCreate([
                                    'warehouse_transmittal_id' => $transmittal->id,
                                    'delivery_order_receipt_detail_id' => $item->id,
                                ]);
                                $countItems++;
                            }
                        }

                        if ($countItems > 0) {
                            Notification::make()
                                ->title("Berhasil diproses! $countItems item dimasukkan ke transmittal tujuan ".$destination->name.'.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Tidak ada item yang valid untuk diproses berdasarkan hak akses Anda.')
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
