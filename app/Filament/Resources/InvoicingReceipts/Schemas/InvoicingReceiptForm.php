<?php

namespace App\Filament\Resources\InvoicingReceipts\Schemas;

use App\Models\DeliveryOrderReceipt;
use App\Models\PurchaseOrderIssued;
use App\Models\Vendor;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class InvoicingReceiptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Dokumen GRS / RDTV')
                    ->description('Detail referensi penerimaan slip GRS atau RDTV')
                    ->icon('heroicon-o-document-currency-dollar')
                    ->schema([
                        DatePicker::make('tanggal_terima_grs')
                            ->label('Tanggal Terima GRS')
                            ->native(false)
                            ->default(now())
                            ->required(),

                        ToggleButtons::make('jenis')
                            ->label('Jenis Dokumen')
                            ->options([
                                'GRS' => 'GRS',
                                'RDTV' => 'RDTV',
                            ])
                            ->colors([
                                'GRS' => 'success',
                                'RDTV' => 'warning',
                            ])
                            ->default('GRS')
                            ->inline()
                            ->live()
                            ->required(),

                        TextInput::make('no_grs')
                            ->label('No. GRS')
                            ->placeholder('Nomor dokumen GRS resmi (SAP)')
                            ->required(fn (Get $get) => $get('jenis') === 'GRS' || empty($get('jenis')))
                            ->visible(fn (Get $get) => $get('jenis') !== 'RDTV')
                            ->helperText('Wajib diisi dengan Nomor GRS resmi SAP/Fisik oleh Staff Invoice.'),

                        TextInput::make('no_rdtv')
                            ->label('No. RDTV')
                            ->placeholder('Nomor dokumen RDTV')
                            ->required(fn (Get $get) => $get('jenis') === 'RDTV')
                            ->visible(fn (Get $get) => $get('jenis') === 'RDTV')
                            ->helperText('Wajib diisi dengan Nomor RDTV resmi.'),

                        DatePicker::make('tanggal_info_grs')
                            ->label('Tanggal Info GRS ke Vendor')
                            ->native(false),
                    ])->columns(2),

                Section::make('Referensi PO & DO')
                    ->description('Data kaitan PO, DO, dan penugasan PIC')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        TextInput::make('no_po')
                            ->label('Nomor PO')
                            ->placeholder('Nomor PO Terbit')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (! $state) {
                                    return;
                                }

                                $po = PurchaseOrderIssued::where('purchase_order_no', trim($state))->first();
                                if ($po) {
                                    if (! $get('qty_po')) {
                                        $set('qty_po', (float) $po->qty_po);
                                    }
                                    if (! $get('uoi') && $po->uoi) {
                                        $set('uoi', $po->uoi);
                                    }
                                    if (! $get('material_type') && $po->material_type) {
                                        $set('material_type', $po->material_type);
                                    }
                                    if (! $get('nama_barang') && $po->description) {
                                        $set('nama_barang', $po->description);
                                    }
                                    if (! $get('deskripsi') && $po->description) {
                                        $set('deskripsi', $po->description);
                                    }
                                    if (! $get('vendor_name') && $po->vendor_name) {
                                        $set('vendor_name', $po->vendor_name);
                                    }
                                    if (! $get('subtotal_exclude_ppn') && $po->total_amount_in_lc) {
                                        $subtotal = (float) $po->total_amount_in_lc;
                                        $set('subtotal_exclude_ppn', $subtotal);
                                        $set('subtotal_include_ppn', round($subtotal + ($subtotal * 0.11), 2));
                                    }
                                    if (! $get('vendor_id') && ($po->vendor_id || $po->vendor_name)) {
                                        $vendor = Vendor::where('vendor_number', $po->vendor_id)
                                            ->orWhere('name', $po->vendor_name)
                                            ->first();
                                        if ($vendor) {
                                            $set('vendor_id', $vendor->id);
                                            $set('email_vendor', is_array($vendor->email) ? implode(', ', $vendor->email) : $vendor->email);
                                        }
                                    }
                                }
                            }),

                        TextInput::make('no_do')
                            ->label('Nomor DO')
                            ->placeholder('Nomor Delivery Order')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (! $state) {
                                    return;
                                }

                                $do = DeliveryOrderReceipt::with('deliveryOrderReceiptDetails.purchaseOrderIssued')
                                    ->where('delivery_order_no', trim($state))
                                    ->first();

                                if ($do) {
                                    $details = $do->deliveryOrderReceiptDetails;
                                    $firstDetail = $details->first();
                                    $po = $firstDetail?->purchaseOrderIssued;

                                    if ($details->isNotEmpty()) {
                                        $sumQty = (float) $details->sum('quantity');
                                        $sumAmount = (float) $details->sum('total_amount_snapshot');

                                        if (! $get('qty_penagihan') || $get('qty_penagihan') == 0) {
                                            $set('qty_penagihan', $sumQty);
                                        }
                                        if ($sumAmount > 0 && (! $get('subtotal_exclude_ppn') || $get('subtotal_exclude_ppn') == 0)) {
                                            $set('subtotal_exclude_ppn', $sumAmount);
                                            $set('subtotal_include_ppn', round($sumAmount + ($sumAmount * 0.11), 2));
                                        }
                                    }

                                    if (! $get('uoi') && $firstDetail?->uoi) {
                                        $set('uoi', $firstDetail->uoi);
                                    }
                                    if (! $get('no_po') && $po?->purchase_order_no) {
                                        $set('no_po', $po->purchase_order_no);
                                    }
                                    if (! $get('qty_po') && $po?->qty_po) {
                                        $set('qty_po', (float) $po->qty_po);
                                    }
                                    if (! $get('nama_barang') && ($firstDetail?->description ?? $po?->description)) {
                                        $set('nama_barang', $firstDetail?->description ?? $po?->description);
                                    }
                                }
                            }),

                        Select::make('material_type')
                            ->label('Tipe Material')
                            ->options([
                                'ZSP' => 'ZSP',
                                'ZFP' => 'ZFP',
                                'ZPM' => 'ZPM',
                                'ZRM' => 'ZRM',
                                'ZSM' => 'ZSM',
                            ])
                            ->placeholder('Pilih Tipe Material'),

                        Select::make('pic_id')
                            ->label('PIC Ditugaskan (Staff Invoice)')
                            ->relationship(
                                name: 'pic',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'Staff Invoice'))
                            )
                            ->placeholder('Bebas ke Siapapun (Open Pool)')
                            ->searchable()
                            ->preload()
                            ->helperText('Hanya pengguna dengan role "Staff Invoice" yang dapat dipilih.'),
                    ])->columns(2),

                Section::make('Informasi Vendor')
                    ->description('Data identitas dan korespondensi vendor')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        Select::make('vendor_id')
                            ->label('Pilih Vendor (Master)')
                            ->relationship('vendor', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $vendor = Vendor::find($state);
                                    if ($vendor) {
                                        $set('vendor_name', $vendor->name);
                                        $set('email_vendor', is_array($vendor->email) ? implode(', ', $vendor->email) : $vendor->email);
                                    }
                                }
                            }),

                        TextInput::make('vendor_name')
                            ->label('Nama Vendor')
                            ->placeholder('Nama perusahaan vendor'),

                        TextInput::make('email_vendor')
                            ->label('Email Vendor')
                            ->placeholder('vendor@email.com (bisa lebih dari 1 email)'),

                        Textarea::make('deskripsi')
                            ->label('Deskripsi Pekerjaan / Catatan Pengadaan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Rincian Penagihan & Mitraku')
                    ->description('Nilai tagihan, nomor faktur pajak, dan kuantitas barang')
                    ->icon('heroicon-o-receipt-percent')
                    ->schema([
                        TextInput::make('no_invoice')
                            ->label('Nomor Invoice')
                            ->placeholder('Nomor tagihan vendor (opsional)'),

                        TextInput::make('faktur_pajak')
                            ->label('Nomor Faktur Pajak')
                            ->placeholder('Contoh: 010.001-26.12345678 (opsional)'),

                        TextInput::make('nama_barang')
                            ->label('Nama Barang')
                            ->placeholder('Deskripsi material / barang')
                            ->columnSpanFull(),

                        TextInput::make('qty_penagihan')
                            ->label('Quantity Penagihan')
                            ->numeric()
                            ->placeholder('0.00'),

                        TextInput::make('qty_po')
                            ->label('Quantity di PO')
                            ->numeric()
                            ->placeholder('0.00'),

                        TextInput::make('uoi')
                            ->label('Satuan (UOI)')
                            ->placeholder('PCS, TON, SET, dll'),

                        TextInput::make('subtotal_exclude_ppn')
                            ->label('Sub Total (Exclude PPN)')
                            ->numeric()
                            ->prefix('Rp')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state !== null && is_numeric($state)) {
                                    $subtotal = (float) $state;
                                    $set('subtotal_include_ppn', round($subtotal + ($subtotal * 0.11), 2));
                                } else {
                                    $set('subtotal_include_ppn', null);
                                }
                            }),

                        TextInput::make('subtotal_include_ppn')
                            ->label('Sub Total (Include PPN 11%)')
                            ->numeric()
                            ->prefix('Rp')
                            ->helperText('Rumus otomatis: Sub Total (Exclude PPN) + 11% PPN'),
                    ])->columns(2),

                Section::make('Timeline & Verifikasi Dokumen')
                    ->description('Waktu penyelesaian invoice, upload dokumen verifikasi, dan status kasir')
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        DatePicker::make('tanggal_terima_koreksi_invoice')
                            ->label('Tanggal Terima & Koreksi Invoice')
                            ->native(false)
                            ->default(now()),

                        DatePicker::make('tanggal_kirim_kasir')
                            ->label('Tanggal Kirim Kasir')
                            ->native(false),

                        TextInput::make('lama_pekerjaan')
                            ->label('Lama Pekerjaan (Hari)')
                            ->numeric()
                            ->suffix('Hari')
                            ->readOnly()
                            ->helperText('Dihitung otomatis dari selisih tanggal terima dan kirim kasir'),

                        Select::make('status')
                            ->label('Status Alur Verifikasi')
                            ->options([
                                'Menunggu Verifikasi' => 'Menunggu Verifikasi',
                                'Terverifikasi' => 'Terverifikasi',
                                'Terkirim Kasir' => 'Terkirim Kasir',
                            ])
                            ->default('Menunggu Verifikasi')
                            ->required(),

                        FileUpload::make('dokumen_verifikasi_grs')
                            ->label('Surat / Dokumen Verifikasi GRS (ACC VP)')
                            ->disk('public')
                            ->directory('verifikasi-grs')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->helperText('Unggah surat verifikasi GRS setelah mendapat persetujuan (ACC) dari VP.'),

                        FileUpload::make('dokumen_invoice')
                            ->label('Berkas Invoice Vendor')
                            ->disk('public')
                            ->directory('berkas-invoice')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->helperText('Unggah berkas invoice resmi setelah vendor mengirimkan berkas fisik/tagihan ke PKT.'),

                        Textarea::make('keterangan')
                            ->label('Keterangan Tambahan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
