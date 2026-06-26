<?php

namespace App\Filament\Resources\MaterialIssues\Schemas;

use App\Models\DeliveryOrderReceiptDetail;
use App\Models\PurchaseOrderIssued;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\EmptyState;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class MaterialIssueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make([
                    self::getInformasiUtamaSection(),
                ]),
                self::getDetailMaterialSection(),
                self::getTandaTanganSection()
                    ->columnSpanFull(),
            ]);
    }

    protected static function getInformasiUtamaSection(): Section
    {
        return Section::make('Informasi Utama MIR')
            ->icon(Heroicon::OutlinedDocumentText)
            ->description('Lengkapi data utama formulir Material Issued Request (MIR) di bawah ini.')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('mir_number')
                        ->label('No. MIR')
                        ->disabled()
                        ->placeholder('Auto Generated')
                        ->prefixIcon('heroicon-m-hashtag')
                        ->dehydrated(false)
                        ->visibleOn('create'),

                    TextInput::make('mir_number')
                        ->label('No. MIR')
                        ->disabled()
                        ->prefixIcon('heroicon-m-hashtag')
                        ->visibleOn(['edit', 'view']),

                    DatePicker::make('tanggal')
                        ->label('Tanggal MIR')
                        ->placeholder('Pilih Tanggal')
                        ->prefixIcon('heroicon-m-calendar')
                        ->required()
                        ->native(false)
                        ->default(now()),

                    Select::make('purchase_order_issued_id')
                        ->label('Nomor PO')
                        ->placeholder('Cari dan Pilih Nomor PO')
                        ->prefixIcon('heroicon-m-document-magnifying-glass')
                        ->searchable()
                        ->preload(false)
                        ->required()
                        ->getSearchResultsUsing(function (string $search) {
                            return PurchaseOrderIssued::whereHas('deliveryOrderReceiptDetails')
                                ->where('purchase_order_no', 'like', "%{$search}%")
                                ->limit(50)
                                ->get()
                                ->unique('purchase_order_no')
                                ->pluck('purchase_order_no', 'id')
                                ->toArray();
                        })
                        ->columnSpanFull()
                        ->getOptionLabelUsing(fn ($value): ?string => PurchaseOrderIssued::find($value)?->purchase_order_no)
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('materialIssueDetails', [])),
                ]),

                Section::make('Detail Kebutuhan (Cost Center & Lokasi)')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('no_hp')
                                ->label('No. Hp')
                                ->placeholder('08123456789')
                                ->prefixIcon('heroicon-m-phone')
                                ->tel()
                                ->required(),
                            TextInput::make('departemen')
                                ->label('Departemen')
                                ->placeholder('Produksi / Maintenance')
                                ->prefixIcon('heroicon-m-building-office')
                                ->required(),
                            TextInput::make('bagian')
                                ->label('Bagian')
                                ->placeholder('Elektrikal')
                                ->prefixIcon('heroicon-m-users')
                                ->required(),
                            Textarea::make('digunakan_untuk')
                                ->label('Tujuan Penggunaan')
                                ->placeholder('Jelaskan secara singkat material akan digunakan untuk apa...')
                                ->required()
                                ->autosize()
                                ->rows(3),
                            TextInput::make('no_reservasi')
                                ->label('No. Reservasi')
                                ->placeholder('No. Reservasi SAP')
                                ->prefixIcon('heroicon-m-bookmark'),
                            TextInput::make('no_jor_wo')
                                ->label('No. JOR / WO')
                                ->placeholder('Job Order / Work Order')
                                ->prefixIcon('heroicon-m-wrench-screwdriver'),
                            TextInput::make('no_alat')
                                ->label('No. Alat')
                                ->placeholder('Equipment / Asset No')
                                ->prefixIcon('heroicon-m-cog'),
                            TextInput::make('kode_biaya')
                                ->label('Kode Biaya')
                                ->placeholder('Cost Center')
                                ->prefixIcon('heroicon-m-currency-dollar'),
                        ]),
                    ])
                    ->collapsible()
                    ->compact(),
            ]);
    }

    protected static function getDetailMaterialSection(): Section
    {
        return Section::make('Daftar Barang yang Diambil')
            ->icon(Heroicon::OutlinedCube)
            ->description(function (Get $get) {
                $poId = $get('purchase_order_issued_id');
                if ($poId) {
                    $po = PurchaseOrderIssued::find($poId);

                    return $po ? "Daftar Material untuk PO: {$po->purchase_order_no}" : 'Pilih barang dari PO beserta kuantitas yang diambil.';
                }

                return 'Silakan pilih Nomor PO terlebih dahulu untuk mulai mengambil material.';
            })
            ->schema([
                Repeater::make('materialIssueDetails')
                    ->relationship()
                    ->label('')
                    ->addActionLabel('Tambah Material ke Daftar')
                    ->collapsible()
                    ->defaultItems(1)
                    ->itemLabel(fn (array $state): ?string => $state['description'] ?? 'Item Baru')
                    ->hidden(fn (Get $get): bool => empty($get('purchase_order_issued_id')))
                    ->schema([
                        Grid::make(12)->schema([
                            Select::make('delivery_order_receipt_detail_id')
                                ->label('Pilih Material (Item No.)')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->columnSpan(5)
                                ->prefixIcon('heroicon-m-hashtag')
                                ->placeholder('Pilih Item dari PO')
                                ->options(function (Get $get) {
                                    $poId = $get('../../purchase_order_issued_id');
                                    if (! $poId) {
                                        return [];
                                    }
                                    $poItem = PurchaseOrderIssued::find($poId);
                                    if (! $poItem) {
                                        return [];
                                    }
                                    $allPoItemIds = PurchaseOrderIssued::where('purchase_order_no', $poItem->purchase_order_no)->pluck('id');

                                    return DeliveryOrderReceiptDetail::whereIn('purchase_order_issued_id', $allPoItemIds)
                                        ->get()
                                        ->mapWithKeys(function ($detail) {
                                            return [$detail->id => "Item {$detail->item_no} - {$detail->description}"];
                                        });
                                })
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if ($state) {
                                        $detail = DeliveryOrderReceiptDetail::with('locationReceiving')->find($state);
                                        if ($detail) {
                                            $set('description', $detail->description);
                                            $set('stock_no', $detail->material_code);
                                            $set('location', $detail->locationReceiving?->name);
                                            $set('uoi', $detail->uoi);

                                            // BOH = Qty Datang - Qty yang sudah diambil sebelumnya
                                            $qtyReceived = (float) $detail->quantity;
                                            $qtyIssued = (float) $detail->issued_quantity;
                                            $set('boh', max(0, $qtyReceived - $qtyIssued));
                                        }
                                    } else {
                                        $set('description', null);
                                        $set('stock_no', null);
                                        $set('location', null);
                                        $set('uoi', null);
                                        $set('boh', null);
                                    }
                                }),

                            TextInput::make('stock_no')
                                ->label('Stock / Material No')
                                ->placeholder('Otomatis')
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpan(3),

                            TextInput::make('location')
                                ->label('Lokasi Penyimpanan')
                                ->placeholder('Lokasi Gudang')
                                ->disabled()
                                ->dehydrated(false)
                                ->prefixIcon('heroicon-m-map-pin')
                                ->columnSpan(4),

                            TextInput::make('description')
                                ->label('Deskripsi Material')
                                ->placeholder('Nama Material')
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpanFull(),

                            // Hidden UOI untuk kebutuhan suffix
                            Hidden::make('uoi')->dehydrated(false),

                            TextInput::make('diminta')
                                ->label('Qty Diminta')
                                ->placeholder('Jumlah Diminta')
                                ->numeric()
                                ->required()
                                ->prefixIcon('heroicon-m-shopping-cart')
                                ->suffix(fn (Get $get) => $get('uoi') ?? '')
                                ->rule(function (Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $boh = (float) $get('boh');
                                        if ((float) $value > $boh) {
                                            $fail("Kuantitas tidak boleh melebihi sisa stok (BOH: {$boh}).");
                                        }
                                    };
                                })
                                ->columnSpan(4),

                            TextInput::make('diserahkan')
                                ->label('Qty Diserahkan')
                                ->placeholder('Jumlah Aktual')
                                ->numeric()
                                ->required()
                                ->prefixIcon('heroicon-m-check-badge')
                                ->suffix(fn (Get $get) => $get('uoi') ?? '')
                                ->rule(function (Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $boh = (float) $get('boh');
                                        if ((float) $value > $boh) {
                                            $fail("Kuantitas tidak boleh melebihi sisa stok (BOH: {$boh}).");
                                        }
                                    };
                                })
                                ->columnSpan(4),

                            TextInput::make('boh')
                                ->label('Sisa Stok (BOH)')
                                ->placeholder('Sisa Tersedia')
                                ->numeric()
                                ->readOnly()
                                ->prefixIcon('heroicon-m-cube')
                                ->suffix(fn (Get $get) => $get('uoi') ?? '')
                                ->columnSpan(4),
                        ]),
                    ])
                    ->columnSpanFull(),

                EmptyState::make('Belum ada Nomor PO yang dipilih')
                    ->description('Silakan cari dan pilih Nomor PO pada bagian Informasi Utama untuk menampilkan daftar material.')
                    ->icon(Heroicon::OutlinedDocumentMagnifyingGlass)
                    ->visible(fn (Get $get): bool => empty($get('purchase_order_issued_id'))),
            ]);
    }

    protected static function getTandaTanganSection(): Section
    {
        return Section::make('Tanda Tangan (Fisik & Digital)')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->description('Catat nama pihak yang bertanda tangan di formulir fisik atau lihat tanda tangan digital MIR.')
            ->schema([
                Grid::make(4)->schema([
                    Group::make([
                        TextInput::make('diminta_oleh')
                            ->label('Diminta Oleh')
                            ->placeholder('Nama Peminta')
                            ->prefixIcon('heroicon-m-user'),
                        TextInput::make('npk')
                            ->label('NPK Peminta')
                            ->placeholder('NPK Peminta')
                            ->prefixIcon('heroicon-m-identification'),
                    ]),
                    Group::make([
                        TextInput::make('disetujui_oleh')
                            ->label('Disetujui Oleh (ISTEK)')
                            ->placeholder('Nama ISTEK')
                            ->prefixIcon('heroicon-m-user'),
                        TextInput::make('disetujui_npk')
                            ->label('NPK ISTEK')
                            ->placeholder('NPK ISTEK')
                            ->prefixIcon('heroicon-m-identification'),
                    ]),
                    Group::make([
                        TextInput::make('diserahkan_oleh')
                            ->label('Diserahkan (Receiving)')
                            ->placeholder('Nama Petugas')
                            ->prefixIcon('heroicon-m-truck'),
                        TextInput::make('diserahkan_npk')
                            ->label('NPK Receiving')
                            ->placeholder('NPK Petugas')
                            ->prefixIcon('heroicon-m-identification'),
                    ]),
                    Group::make([
                        TextInput::make('diterima_oleh')
                            ->label('Diterima Oleh')
                            ->placeholder('Nama Penerima')
                            ->prefixIcon('heroicon-m-user-plus'),
                        TextInput::make('diketahui_oleh')
                            ->label('Diketahui Oleh')
                            ->placeholder('Nama Pengetahui')
                            ->prefixIcon('heroicon-m-eye'),
                    ]),
                ]),

                Section::make('Tanda Tangan Digital Terlampir')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('diminta_signature')
                                ->label('Tanda Tangan Peminta')
                                ->state(fn ($record) => $record?->diminta_signature ? new HtmlString('<div class="flex flex-col items-center"><img src="'.$record->diminta_signature.'" style="max-height: 100px; border: 1px solid #e5e7eb; border-radius: 8px; padding: 4px; background: white;"><span class="text-xs text-gray-500 mt-2">Digital Signature</span></div>') : new HtmlString('<span class="text-sm text-gray-500 italic">Tidak Ada Tanda Tangan Digital</span>')),

                            TextEntry::make('disetujui_signature')
                                ->label('Tanda Tangan ISTEK')
                                ->state(fn ($record) => $record?->disetujui_signature ? new HtmlString('<div class="flex flex-col items-center"><img src="'.$record->disetujui_signature.'" style="max-height: 100px; border: 1px solid #e5e7eb; border-radius: 8px; padding: 4px; background: white;"><span class="text-xs text-gray-500 mt-2">Digital Signature</span></div>') : new HtmlString('<span class="text-sm text-gray-500 italic">Tidak Ada Tanda Tangan Digital</span>')),

                            TextEntry::make('diserahkan_signature')
                                ->label('Tanda Tangan Receiving')
                                ->state(fn ($record) => $record?->diserahkan_signature ? new HtmlString('<div class="flex flex-col items-center"><img src="'.$record->diserahkan_signature.'" style="max-height: 100px; border: 1px solid #e5e7eb; border-radius: 8px; padding: 4px; background: white;"><span class="text-xs text-gray-500 mt-2">Digital Signature</span></div>') : new HtmlString('<span class="text-sm text-gray-500 italic">Tidak Ada Tanda Tangan Digital</span>')),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($record) => ! $record?->diminta_signature && ! $record?->disetujui_signature && ! $record?->diserahkan_signature),
            ])->collapsible();
    }
}
