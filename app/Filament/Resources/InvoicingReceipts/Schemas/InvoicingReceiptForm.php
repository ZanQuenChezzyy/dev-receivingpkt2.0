<?php

namespace App\Filament\Resources\InvoicingReceipts\Schemas;

use App\Models\DeliveryOrderReceipt;
use App\Models\InvoicingReceipt;
use App\Models\PurchaseOrderIssued;
use App\Models\Vendor;
use App\Services\GrsPdfParserService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
                            ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                if ($state === 'RDTV' && filled($get('tanggal_info_grs'))) {
                                    $set('status', 'Selesai');
                                }
                            })
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
                            ->visible(fn (Get $get) => $get('jenis') === 'RDTV')
                            ->helperText('Nomor dokumen RDTV resmi (opsional jika belum tersedia).'),

                        DatePicker::make('tanggal_info_grs')
                            ->label('Tanggal Info GRS ke Vendor')
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                if ($get('jenis') === 'RDTV' && filled($state)) {
                                    $set('status', 'Selesai');
                                }
                            })
                            ->helperText(fn (Get $get): ?string => $get('jenis') === 'RDTV'
                                ? 'Untuk dokumen RDTV, mengisi tanggal ini menandakan info RDTV sudah disampaikan ke vendor dan status otomatis Selesai.'
                                : null),

                        TextInput::make('kategori_barang')
                            ->label('Kategori / Jenis Barang')
                            ->placeholder('Contoh: Karung, Sparepart, Chemical/Bahan Baku'),

                        DatePicker::make('tanggal_penerimaan')
                            ->label('Tanggal Penerimaan (GRS)')
                            ->native(false)
                            ->helperText('Tanggal penerimaan barang di gudang (sesuai dokumen GRS).'),

                        DatePicker::make('delivery_date')
                            ->label('Delivery Date (GRS/RDTV)')
                            ->native(false)
                            ->helperText('Diambil otomatis dari berkas GRS/RDTV yang diunggah.'),
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

                                $poRecords = PurchaseOrderIssued::where('purchase_order_no', trim($state))->orderBy('item_no')->get();
                                $po = $poRecords->first();
                                if ($po) {
                                    if (! $get('no_do')) {
                                        $foundDo = InvoicingReceipt::findDeliveryOrderNoForPo($state);
                                        if ($foundDo) {
                                            $set('no_do', $foundDo);
                                        }
                                    }

                                    $currentItems = $get('items');
                                    if ($poRecords->isNotEmpty()) {
                                        if (empty($currentItems)) {
                                            $set('items', $poRecords->map(fn ($p) => [
                                                'item_no' => $p->item_no,
                                                'material_code' => $p->material_code,
                                                'nama_barang' => $p->description,
                                                'qty_penagihan' => (float) $p->qty_po,
                                                'qty_po' => (float) $p->qty_po,
                                                'uoi' => $p->uoi,
                                            ])->toArray());
                                        } else {
                                            $updatedItems = array_map(function ($it) use ($poRecords) {
                                                $matchedPo = PurchaseOrderIssued::matchByDescription($it['nama_barang'] ?? null, $poRecords);
                                                if ($matchedPo) {
                                                    $it['item_no'] ??= $matchedPo->item_no;
                                                    $it['material_code'] ??= $matchedPo->material_code;
                                                    $it['qty_po'] = (float) $matchedPo->qty_po;
                                                    $it['uoi'] ??= $matchedPo->uoi;
                                                    if (! isset($it['qty_penagihan']) || (float) $it['qty_penagihan'] == 0.0) {
                                                        $it['qty_penagihan'] = (float) $matchedPo->qty_po;
                                                    }
                                                }

                                                return $it;
                                            }, $currentItems);
                                            $set('items', $updatedItems);
                                        }
                                    }

                                    $totalQtyPo = (float) $poRecords->sum('qty_po');
                                    $totalAmount = (float) $poRecords->sum('total_amount_in_lc');

                                    if (! $get('qty_po') || (float) $get('qty_po') == 0.0) {
                                        $set('qty_po', $totalQtyPo > 0 ? $totalQtyPo : (float) $po->qty_po);
                                    }
                                    if (! $get('qty_penagihan') || (float) $get('qty_penagihan') == 0.0) {
                                        $set('qty_penagihan', $totalQtyPo > 0 ? $totalQtyPo : (float) $po->qty_po);
                                    }
                                    if (! $get('uoi') && $po->uoi) {
                                        $set('uoi', $po->uoi);
                                    }
                                    if (! $get('material_type') || $get('material_type') === InvoicingReceipt::DEFAULT_MATERIAL_TYPE) {
                                        $set('material_type', InvoicingReceipt::normalizeMaterialType($po->material_type));
                                    }
                                    if (! $get('nama_barang')) {
                                        $names = $poRecords->pluck('description')->filter()->unique()->implode(', ');
                                        $set('nama_barang', $names ?: $po->description);
                                    }
                                    if (! $get('deskripsi') && $po->description) {
                                        $descList = $poRecords->pluck('description')->filter()->values();
                                        if ($descList->count() > 1) {
                                            $lines = $descList->map(fn ($d, $i) => ($i + 1).'. '.trim($d))->implode("\n");
                                            $set('deskripsi', $lines);
                                        } else {
                                            $set('deskripsi', $po->description);
                                        }
                                    }
                                    if (! $get('vendor_name') && $po->vendor_name) {
                                        $set('vendor_name', $po->vendor_name);
                                    }
                                    if (! $get('subtotal_exclude_ppn') && $totalAmount > 0) {
                                        $set('subtotal_exclude_ppn', $totalAmount);
                                        $set('subtotal_include_ppn', round($totalAmount + ($totalAmount * 0.11), 2));
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

                                        $currentItems = $get('items');
                                        if (empty($currentItems)) {
                                            $set('items', $details->map(fn ($d) => [
                                                'item_no' => $d->item_no,
                                                'material_code' => $d->material_code,
                                                'nama_barang' => $d->description,
                                                'qty_penagihan' => (float) $d->quantity,
                                                'qty_po' => (float) ($d->purchaseOrderIssued?->qty_po ?? $d->quantity),
                                                'uoi' => $d->uoi,
                                                'no_do' => $do->delivery_order_no,
                                            ])->values()->toArray());
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
                            ->options(InvoicingReceipt::materialTypeOptions())
                            ->default(InvoicingReceipt::DEFAULT_MATERIAL_TYPE)
                            ->formatStateUsing(fn (?string $state): string => InvoicingReceipt::normalizeMaterialType($state))
                            ->dehydrateStateUsing(fn (?string $state): string => InvoicingReceipt::normalizeMaterialType($state))
                            ->placeholder('Pilih Tipe Material'),

                        Select::make('pic_id')
                            ->label('PIC Ditugaskan (Staff Invoice)')
                            ->relationship(
                                name: 'pic',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query, ?InvoicingReceipt $record) => $query->whereHas('roles', fn ($q) => $q->where('name', 'Staff Invoice'))->where(fn ($q) => $q->where('is_active', true)->when($record?->pic_id, fn ($sub, $id) => $sub->orWhere('id', $id)))
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
                            ->label('Deskripsi')
                            ->rows(5)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Rincian Penagihan & Mitraku')
                    ->description('Nilai tagihan, nomor faktur pajak, dan kuantitas barang')
                    ->icon('heroicon-o-receipt-percent')
                    ->schema([
                        TextInput::make('no_invoice')
                            ->label('Nomor Invoice')
                            ->placeholder('Nomor tagihan vendor (opsional)')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                $currentStatus = (string) ($get('status') ?? '');
                                if (
                                    InvoicingReceipt::isMeaningfulText(is_string($state) ? $state : null)
                                    && ($currentStatus === '' || $currentStatus === 'Menunggu Verifikasi')
                                ) {
                                    $set('status', 'Selesai');
                                }
                            })
                            ->helperText('Jika status masih Menunggu Verifikasi, pengisian nomor invoice otomatis menandai status sebagai Selesai.'),

                        TextInput::make('faktur_pajak')
                            ->label('Nomor Faktur Pajak')
                            ->placeholder('Contoh: 010.001-26.12345678 (opsional)'),

                        DatePicker::make('tanggal_invoice')
                            ->label('Tanggal Invoice')
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (filled($state)) {
                                    $set('tanggal_faktur_pajak', $state);
                                }
                            })
                            ->helperText('Tanggal terbit invoice resmi vendor.'),

                        DatePicker::make('tanggal_faktur_pajak')
                            ->label('Tanggal Faktur Pajak')
                            ->native(false)
                            ->helperText('Tanggal faktur pajak (otomatis mengikuti tanggal invoice, dapat diubah jika berbeda).'),

                        Repeater::make('items')
                            ->label('Rincian Barang yang Ditagihkan')
                            ->relationship('items')
                            ->itemLabel(fn (array $state): ?string => ($state['nama_barang'] ?? null)
                                ? ((isset($state['item_no']) ? '['.$state['item_no'].'] ' : '').$state['nama_barang'].(isset($state['qty_penagihan']) ? ' ('.$state['qty_penagihan'].' '.($state['uoi'] ?? '').')' : ''))
                                : 'Item Barang')
                            ->collapsible()
                            ->cloneable()
                            ->defaultItems(0)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('item_no')
                                    ->label('Item No.')
                                    ->placeholder('10, 20...')
                                    ->numeric()
                                    ->columnSpan(['default' => 12, 'sm' => 2, 'md' => 2]),

                                TextInput::make('nama_barang')
                                    ->label('Nama / Deskripsi Barang')
                                    ->placeholder('Nama atau spesifikasi barang')
                                    ->required()
                                    ->columnSpan(['default' => 12, 'sm' => 10, 'md' => 4]),

                                TextInput::make('qty_penagihan')
                                    ->label('Qty Ditagihkan')
                                    ->numeric()
                                    ->required()
                                    ->placeholder('0.00')
                                    ->suffix(fn (Get $get, ?Model $record) => $get('uoi') ?: ($record?->uoi ?? ''))
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $items = $get('../../items') ?? [];
                                        $totalQty = 0;
                                        foreach ($items as $item) {
                                            $totalQty += (float) ($item['qty_penagihan'] ?? 0);
                                        }
                                        $set('../../qty_penagihan', $totalQty > 0 ? $totalQty : null);
                                    })
                                    ->columnSpan(['default' => 6, 'sm' => 6, 'md' => 3]),

                                TextInput::make('qty_po')
                                    ->label('Qty PO')
                                    ->numeric()
                                    ->placeholder('0.00')
                                    ->suffix(fn (Get $get, ?Model $record) => $get('uoi') ?: ($record?->uoi ?? ''))
                                    ->columnSpan(['default' => 6, 'sm' => 6, 'md' => 3]),

                                Hidden::make('uoi'),
                            ])
                            ->columns(12),

                        TextInput::make('nama_barang')
                            ->label('Ringkasan Nama Barang')
                            ->placeholder('Deskripsi umum material / barang (otomatis dari rincian)')
                            ->columnSpanFull(),

                        TextInput::make('subtotal_exclude_ppn')
                            ->label('Total Sub Total (Exclude PPN)')
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->formatStateUsing(fn ($state) => InvoicingReceipt::formatRupiah($state))
                            ->dehydrateStateUsing(fn ($state) => InvoicingReceipt::parseRupiah($state))
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set) {
                                $subtotal = InvoicingReceipt::parseRupiah($state);
                                if ($subtotal !== null && $subtotal > 0) {
                                    $incPpn = (int) round($subtotal * 1.11);
                                    $set('subtotal_include_ppn', InvoicingReceipt::formatRupiah($incPpn));
                                } else {
                                    $set('subtotal_include_ppn', null);
                                }
                            }),

                        TextInput::make('subtotal_include_ppn')
                            ->label('Total Sub Total (Include PPN 11%)')
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->formatStateUsing(fn ($state) => InvoicingReceipt::formatRupiah($state))
                            ->dehydrateStateUsing(fn ($state) => InvoicingReceipt::parseRupiah($state))
                            ->helperText('Rumus otomatis: Sub Total (Exclude PPN) + 11% PPN (Dibulatkan)'),
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
                                'Selesai' => 'Selesai',
                            ])
                            ->default('Menunggu Verifikasi')
                            ->required(),

                        FileUpload::make('dokumen_verifikasi_grs')
                            ->label('Surat / Dokumen Verifikasi GRS')
                            ->disk('public')
                            ->directory('verifikasi-grs')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                if (! $state) {
                                    return;
                                }
                                $file = is_array($state) ? array_values($state)[0] ?? null : $state;
                                $path = null;
                                if ($file instanceof TemporaryUploadedFile) {
                                    $path = $file->getRealPath();
                                } elseif (is_string($file)) {
                                    $storagePath = Storage::disk('public')->path($file);
                                    if (file_exists($storagePath)) {
                                        $path = $storagePath;
                                    }
                                }

                                if ($path && file_exists($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf') {
                                    try {
                                        $parsed = app(GrsPdfParserService::class)->parse($path);
                                        if (! empty($parsed['no_grs'])) {
                                            $set('no_grs', $parsed['no_grs']);
                                        }
                                        if (! empty($parsed['no_rdtv'])) {
                                            $set('no_rdtv', $parsed['no_rdtv']);
                                        }
                                        if ($get('jenis') === 'RDTV' && ! empty($parsed['tanggal_penolakan'])) {
                                            $set('tanggal_terima_grs', $parsed['tanggal_penolakan']);
                                        } elseif (! empty($parsed['tanggal_release_gr'])) {
                                            $set('tanggal_terima_grs', $parsed['tanggal_release_gr']);
                                        }
                                        if (! empty($parsed['delivery_date'])) {
                                            $set('delivery_date', $parsed['delivery_date']);
                                        }
                                        if (! empty($parsed['tanggal_penerimaan'])) {
                                            $set('tanggal_penerimaan', $parsed['tanggal_penerimaan']);
                                        }
                                        if (! empty($parsed['no_po']) && (! $get('no_po') || $get('no_po') === '-')) {
                                            $set('no_po', $parsed['no_po']);
                                        }
                                        if (! empty($parsed['no_do']) && ! $get('no_do')) {
                                            $set('no_do', $parsed['no_do']);
                                        }
                                        if (! empty($parsed['vendor_name']) && ! $get('vendor_name')) {
                                            $set('vendor_name', $parsed['vendor_name']);
                                        }
                                    } catch (\Throwable $e) {
                                        report($e);
                                    }
                                }
                            })
                            ->helperText('Unggah surat verifikasi GRS. Jika PDF GRS diunggah, data Nomor GRS, Tanggal Release, Delivery Date, dan Tanggal Penerimaan akan otomatis terisi.'),

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
