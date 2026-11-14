<?php

namespace App\Filament\Resources\MonitoringKarungs\Schemas;

use App\Models\DeliveryOrderReceipt;
use App\Models\MonitoringKarung;
use App\Models\PurchaseOrderIssued;
use App\Models\Vendor;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema as DbSchema;

class MonitoringKarungForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('purchase_order_issued_id'),
                Hidden::make('vendor_id'),
                Hidden::make('created_by')->default(fn () => Auth::id()),
                Hidden::make('updated_by')->default(fn () => Auth::id()),

                // 1. Informasi Master Purchase Order & Vendor (Format 2 Kolom Luas & Otomatis Sambung PO Terbit & DO Receipt)
                Section::make('Informasi Kontrak & Purchase Order Karung')
                    ->description('Lengkapi nomor PO karung. Sistem otomatis menghubungkan vendor, alokasi total, dan riwayat Surat Jalan DO.')
                    ->icon('heroicon-m-document-text')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        Select::make('no_po')
                            ->label('Nomor Purchase Order (PO)')
                            ->placeholder('Pilih / Cari Nomor PO')
                            ->searchable()
                            ->preload(false)
                            ->required()
                            ->helperText('Cari nomor PO: vendor, kuantitas kontrak & riwayat DO Receipt terisi otomatis.')
                            ->noSearchResultsMessage('Purchase Order tidak ditemukan.')
                            ->getSearchResultsUsing(
                                fn (string $search): array => DbSchema::hasTable('purchase_order_issueds')
                                    ? PurchaseOrderIssued::where('purchase_order_no', 'like', "%{$search}%")
                                        ->orWhere('vendor_name', 'like', "%{$search}%")
                                        ->limit(10)
                                        ->pluck('purchase_order_no', 'purchase_order_no')
                                        ->toArray()
                                    : []
                            )
                            ->getOptionLabelUsing(fn ($value): ?string => (string) $value)
                            ->live()
                            ->afterStateUpdated(function (mixed $state, Set $set, Get $get): void {
                                $poNo = trim((string) $state);
                                if (empty($poNo) || ! DbSchema::hasTable('purchase_order_issueds')) {
                                    return;
                                }

                                $hasDetails = DbSchema::hasTable('delivery_order_receipt_details');

                                $query = PurchaseOrderIssued::where('purchase_order_no', $poNo);
                                if ($hasDetails) {
                                    $query->with(['deliveryOrderReceiptDetails.deliveryOrderReceipt']);
                                }

                                $po = $query->first();

                                if ($po) {
                                    $set('purchase_order_issued_id', $po->id);

                                    if (! empty($po->vendor_name)) {
                                        $set('vendor_name', $po->vendor_name);

                                        if (DbSchema::hasTable('vendors')) {
                                            $vendor = Vendor::where('name', $po->vendor_name)->first();
                                            if ($vendor) {
                                                $set('vendor_id', $vendor->id);
                                            }
                                        }
                                    }

                                    if ($po->qty_po > 0) {
                                        $set('qty_po', (int) $po->qty_po);
                                    }

                                    // Deteksi Kategori Karung dari deskripsi barang di PO SAP
                                    if (! empty($po->description)) {
                                        $desc = strtoupper($po->description);
                                        if (str_contains($desc, 'PRILL')) {
                                            $set('kategori_karung', 'Prill');
                                        } elseif (str_contains($desc, 'GRANUL')) {
                                            $set('kategori_karung', 'Granul');
                                        } elseif (str_contains($desc, 'NPK') || str_contains($desc, 'PHONSKA')) {
                                            $set('kategori_karung', 'NPK');
                                        } elseif (str_contains($desc, 'RETAIL')) {
                                            $set('kategori_karung', 'Retail');
                                        }
                                    }

                                    // Sambung otomatis data DO Receipt jika tabel pengiriman masih kosong
                                    if ($hasDetails) {
                                        $currentDeliveries = $get('deliveryItems') ?? [];
                                        if (empty($currentDeliveries) && $po->deliveryOrderReceiptDetails->isNotEmpty()) {
                                            $groupedByReceipt = $po->deliveryOrderReceiptDetails->groupBy('delivery_order_receipt_id');
                                            $populatedDeliveries = [];
                                            $stageIndex = 1;

                                            foreach ($groupedByReceipt as $receiptDetails) {
                                                $firstDetail = $receiptDetails->first();
                                                $receipt = $firstDetail->deliveryOrderReceipt;
                                                $totalQty = (int) $receiptDetails->sum('quantity');

                                                $populatedDeliveries[] = [
                                                    'delivery_order_receipt_id' => $receipt?->id,
                                                    'tahap_kirim' => (string) $stageIndex,
                                                    'qty_kirim' => $totalQty > 0 ? $totalQty : (int) ($firstDetail->quantity ?: 0),
                                                    'tanggal_penerimaan' => $receipt?->physical_received_date?->format('Y-m-d')
                                                        ?? $receipt?->received_date?->format('Y-m-d')
                                                        ?? null,
                                                    'no_do' => $receipt?->delivery_order_no ?? '',
                                                    'no_invoice' => '',
                                                    'no_surat' => $receipt?->dof_number ?? '',
                                                    'qty_supply' => $totalQty > 0 ? $totalQty : (int) ($firstDetail->quantity ?: 0),
                                                    'qty_surat' => $totalQty > 0 ? $totalQty : (int) ($firstDetail->quantity ?: 0),
                                                    'keterangan' => $receipt ? "Terhubung dari DO Receipt #{$receipt->delivery_order_no}" : 'Penerimaan DO',
                                                ];
                                                $stageIndex++;
                                            }

                                            if (! empty($populatedDeliveries)) {
                                                $set('deliveryItems', $populatedDeliveries);
                                            }
                                        }
                                    }
                                }
                            }),

                        Select::make('vendor_name')
                            ->label('Nama Vendor Pembuat Karung')
                            ->placeholder('Pilih / Cari Vendor')
                            ->searchable()
                            ->preload(false)
                            ->required()
                            ->helperText('Pilih vendor terdaftar atau cari dari master vendor / PO.')
                            ->noSearchResultsMessage('Vendor tidak ditemukan.')
                            ->options(function (): array {
                                if (! DbSchema::hasTable('monitoring_karungs')) {
                                    return [];
                                }

                                return MonitoringKarung::whereNotNull('vendor_name')
                                    ->where('vendor_name', '!=', '')
                                    ->distinct()
                                    ->orderBy('vendor_name')
                                    ->limit(25)
                                    ->pluck('vendor_name', 'vendor_name')
                                    ->toArray();
                            })
                            ->getSearchResultsUsing(
                                function (string $search): array {
                                    $search = trim($search);
                                    if ($search === '') {
                                        return [];
                                    }

                                    $results = [];

                                    if (DbSchema::hasTable('monitoring_karungs')) {
                                        $results = array_merge(
                                            $results,
                                            MonitoringKarung::where('vendor_name', 'like', "%{$search}%")
                                                ->distinct()
                                                ->limit(10)
                                                ->pluck('vendor_name', 'vendor_name')
                                                ->toArray()
                                        );
                                    }

                                    if (DbSchema::hasTable('vendors')) {
                                        $results = array_merge(
                                            $results,
                                            Vendor::where('name', 'like', "%{$search}%")
                                                ->limit(10)
                                                ->pluck('name', 'name')
                                                ->toArray()
                                        );
                                    }

                                    if (count($results) < 10 && DbSchema::hasTable('purchase_order_issueds')) {
                                        $results = array_merge(
                                            $results,
                                            PurchaseOrderIssued::whereNotNull('vendor_name')
                                                ->where('vendor_name', 'like', "%{$search}%")
                                                ->distinct()
                                                ->limit(10)
                                                ->pluck('vendor_name', 'vendor_name')
                                                ->toArray()
                                        );
                                    }

                                    return array_unique($results);
                                }
                            )
                            ->getOptionLabelUsing(fn ($value): ?string => (string) $value)
                            ->createOptionModalHeading('Tambah Vendor Baru')
                            ->createOptionModalHeading('Tambah Vendor Baru')
                            ->createOptionForm([
                                TextInput::make('vendor_number')
                                    ->label('ID Vendor / Vendor Number (SAP)')
                                    ->placeholder('cth: 10005432')
                                    ->required()
                                    ->maxLength(50)
                                    ->prefixIcon('heroicon-m-identification')
                                    ->helperText('Nomor identitas / kode akun vendor di SAP.'),

                                TextInput::make('name')
                                    ->label('Nama Vendor')
                                    ->placeholder('cth: PT Dasaplast Nusantara')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-building-office-2'),

                                TextInput::make('email')
                                    ->label('Email Vendor')
                                    ->email()
                                    ->placeholder('cth: vendor@email.com')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-envelope')
                                    ->helperText('Alamat email aktif untuk korespondensi vendor.'),
                            ])
                            ->createOptionUsing(function (array $data, ?Set $set = null): string {
                                $name = trim((string) ($data['name'] ?? ''));
                                $vendorNumber = trim((string) ($data['vendor_number'] ?? ''));
                                $email = trim((string) ($data['email'] ?? ''));

                                if (DbSchema::hasTable('vendors')) {
                                    $vendor = Vendor::firstOrCreate(
                                        ['vendor_number' => $vendorNumber],
                                        [
                                            'name' => $name,
                                            'email' => $email,
                                        ]
                                    );

                                    if ($vendor->name !== $name || empty($vendor->email)) {
                                        $vendor->update([
                                            'name' => $name,
                                            'email' => $email,
                                        ]);
                                    }

                                    if ($set) {
                                        $set('vendor_id', $vendor->id);
                                    }
                                }

                                return $name;
                            })
                            ->live()
                            ->afterStateUpdated(function (mixed $state, Set $set): void {
                                if ($state && DbSchema::hasTable('vendors')) {
                                    $vendor = Vendor::where('name', $state)->first();
                                    if ($vendor) {
                                        $set('vendor_id', $vendor->id);
                                    }
                                }
                            }),

                        TextInput::make('qty_po')
                            ->label('Total Kuantitas Lembar PO')
                            ->placeholder('cth: 1500000')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->prefixIcon('heroicon-m-archive-box')
                            ->suffix('Lembar')
                            ->helperText('Total lembar karung yang dipesan pada kontrak/PO.'),

                        Select::make('kategori_karung')
                            ->label('Kategori Karung')
                            ->options([
                                'Prill' => 'Karung Urea Prill',
                                'Granul' => 'Karung Urea Granul',
                                'NPK' => 'Karung NPK',
                                'Retail' => 'Karung Retail',
                                'Standard' => 'Karung Standar',
                            ])
                            ->placeholder('Pilih Kategori')
                            ->searchable(),

                        Select::make('lokasi_pabrik')
                            ->label('Lokasi Pabrik / Gudang')
                            ->options([
                                'Surabaya' => 'Surabaya',
                                'Banyuwangi' => 'Banyuwangi',
                                'Bontang' => 'Bontang',
                            ])
                            ->native(false)
                            ->placeholder('Pilih Lokasi Pabrik'),

                        Select::make('status')
                            ->label('Status Monitoring')
                            ->options([
                                'Aktif' => 'Aktif',
                                'Selesai' => 'Selesai',
                                'Terkendala' => 'Terkendala',
                            ])
                            ->native(false)
                            ->default('Aktif')
                            ->required(),

                        Textarea::make('catatan')
                            ->label('Catatan Tambahan PO')
                            ->placeholder('Spesifikasi khusus, jadwal pengiriman, atau catatan rekanan.')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                // 2. Log Tahapan QC & Pengiriman Fisik (Format 2 Kolom Luas & Bebas Scroll Fatigue)
                Section::make('Rincian Log Tahapan QC & Pengiriman Fisik')
                    ->description('Kelola log tahapan pengujian laboratorium QC dan tahapan pengiriman fisik karung dalam tata letak 2 kolom yang lega.')
                    ->icon('heroicon-m-clipboard-document-list')
                    ->schema([
                        Tabs::make('Tabs Tahapan')
                            ->tabs([
                                // Tab 1: Pengujian Mutu QC & SA SAP (Format 2 Kolom)
                                Tab::make('Pengujian QC & SA SAP')
                                    ->icon('heroicon-m-shield-check')
                                    ->badge(fn (Get $get): int => count($get('qcItems') ?? []))
                                    ->schema([
                                        Repeater::make('qcItems')
                                            ->relationship('qcItems')
                                            ->label('Daftar Tahapan Pengujian Mutu QC')
                                            ->addActionLabel('+ Tambah Tahap Pengujian QC')
                                            ->collapsible()
                                            ->collapsed(true) // Terciutkan otomatis agar tidak lelah scroll jika ada 10-20 tahapan
                                            ->collapseAllAction(fn ($action) => $action->label('Ciutkan Semua'))
                                            ->expandAllAction(fn ($action) => $action->label('Buka Semua'))
                                            ->cloneable()
                                            ->defaultItems(0)
                                            ->itemLabel(function (array $state): ?string {
                                                if (empty($state['tahap_qc']) && empty($state['qty_qc'])) {
                                                    return '➕ Tahap QC Baru (Sedang Diisi)';
                                                }

                                                $tahap = ! empty($state['tahap_qc']) ? "Tahap {$state['tahap_qc']}" : 'Tahap -';
                                                $qty = ! empty($state['qty_qc']) ? number_format((int) $state['qty_qc'], 0, ',', '.').' Lbr' : '0 Lbr';
                                                $sik = ! empty($state['no_sik']) ? "SIK: {$state['no_sik']}" : 'Tanpa SIK';
                                                $sa = ! empty($state['no_sa']) ? "SA: {$state['no_sa']}" : '⚠️ Belum SA';
                                                $status = $state['status_qc'] ?? 'Diterima';

                                                return "{$tahap}  •  {$qty}  •  {$sik}  •  {$sa}  [{$status}]";
                                            })
                                            ->columns(['default' => 1, 'md' => 2])
                                            ->schema([
                                                // Baris 1: Identitas Tahap & Kuantitas Uji
                                                TextInput::make('tahap_qc')
                                                    ->label('Tahap Pengujian')
                                                    ->placeholder('cth: Awal, 1, 2, Uji Ulang')
                                                    ->required(),

                                                TextInput::make('qty_qc')
                                                    ->label('Kuantitas Diuji (Lembar)')
                                                    ->placeholder('cth: 300000')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required()
                                                    ->suffix('Lembar'),

                                                // Baris 2: Hasil Pengujian QC & Nomor Dokumen SIK
                                                Select::make('status_qc')
                                                    ->label('Hasil Pengujian QC')
                                                    ->options([
                                                        'Diterima' => 'Diterima',
                                                        'Ditolak' => 'Ditolak',
                                                        'Istek' => 'Istek',
                                                        'TUV' => 'TUV',
                                                        'Diterima dgn Penalti' => 'Diterima dgn Penalti',
                                                        'BLM SA' => 'BLM SA',
                                                        'BATAL' => 'BATAL',
                                                    ])
                                                    ->default('Diterima')
                                                    ->required(),

                                                TextInput::make('no_sik')
                                                    ->label('Nomor SIK')
                                                    ->placeholder('cth: 194/SIK/2026'),

                                                // Baris 3: Tanggal SIK & Nomor SA SAP
                                                DatePicker::make('tgl_sik')
                                                    ->label('Tanggal SIK')
                                                    ->native(false)
                                                    ->displayFormat('d/m/Y'),

                                                TextInput::make('no_sa')
                                                    ->label('Nomor SA (SAP)')
                                                    ->placeholder('cth: 1000690323')
                                                    ->helperText('Kosongkan jika belum terbit di SAP.'),

                                                // Baris 4: Berita Acara & Surat Hasil QC
                                                TextInput::make('no_bapp')
                                                    ->label('Nomor BAPP')
                                                    ->placeholder('cth: 01886'),

                                                TextInput::make('no_surat_qc')
                                                    ->label('No. Surat Hasil QC')
                                                    ->placeholder('cth: 30166'),

                                                // Baris 5: Drafter & Dokumen DOF/DAAN
                                                TextInput::make('drafter')
                                                    ->label('Nama Drafter')
                                                    ->placeholder('cth: CAMEL, PUGUH, RIZKI')
                                                    ->datalist(['CAMEL', 'PUGUH', 'RIZKI', 'PUGUH / CAMEL']),

                                                TextInput::make('no_dof_daan')
                                                    ->label('Nomor DOF / DAAN')
                                                    ->placeholder('cth: 29204')
                                                    ->datalist(function (Get $get): array {
                                                        if (! DbSchema::hasTable('delivery_order_receipt_details') || ! DbSchema::hasTable('purchase_order_issueds')) {
                                                            return [];
                                                        }

                                                        $noPo = $get('../../no_po');
                                                        if (! empty($noPo)) {
                                                            $po = PurchaseOrderIssued::where('purchase_order_no', trim($noPo))->first();
                                                            if ($po) {
                                                                return DeliveryOrderReceipt::whereHas('deliveryOrderReceiptDetails', function ($q) use ($po) {
                                                                    $q->where('purchase_order_issued_id', $po->id);
                                                                })
                                                                    ->whereNotNull('dof_number')
                                                                    ->pluck('dof_number')
                                                                    ->filter()
                                                                    ->unique()
                                                                    ->values()
                                                                    ->toArray();
                                                            }
                                                        }

                                                        return [];
                                                    }),

                                                // Baris 6: Tanggal Permintaan & File ISTEK
                                                DatePicker::make('tgl_permintaan')
                                                    ->label('Tanggal Permintaan')
                                                    ->native(false)
                                                    ->displayFormat('d/m/Y'),

                                                TextInput::make('file_istek')
                                                    ->label('Referensi File ISTEK')
                                                    ->placeholder('cth: 30410'),

                                                // Baris 7: Catatan Khusus Tahap QC (Full Lebar 2 Kolom)
                                                Textarea::make('keterangan')
                                                    ->label('Catatan Khusus Tahap QC')
                                                    ->placeholder('Catatan spesifikasi pengujian laboratorium atau pengecualian.')
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),

                                // Tab 2: Pengiriman Fisik & Surat Jalan DO (Format 2 Kolom)
                                Tab::make('Pengiriman & DO')
                                    ->icon('heroicon-m-truck')
                                    ->badge(fn (Get $get): int => count($get('deliveryItems') ?? []))
                                    ->schema([
                                        Repeater::make('deliveryItems')
                                            ->relationship('deliveryItems')
                                            ->label('Daftar Tahapan Pengiriman Fisik & DO')
                                            ->addActionLabel('+ Tambah Tahap Pengiriman Fisik')
                                            ->collapsible()
                                            ->collapsed(true) // Terciutkan otomatis agar rapi
                                            ->collapseAllAction(fn ($action) => $action->label('Ciutkan Semua'))
                                            ->expandAllAction(fn ($action) => $action->label('Buka Semua'))
                                            ->cloneable()
                                            ->defaultItems(0)
                                            ->itemLabel(function (array $state): ?string {
                                                if (empty($state['tahap_kirim']) && empty($state['qty_kirim'])) {
                                                    return '➕ Tahap Kirim Baru (Sedang Diisi)';
                                                }

                                                $tahap = ! empty($state['tahap_kirim']) ? "Kirim #{$state['tahap_kirim']}" : 'Kirim #-';
                                                $qty = ! empty($state['qty_kirim']) ? number_format((int) $state['qty_kirim'], 0, ',', '.').' Lbr' : '0 Lbr';
                                                $do = ! empty($state['no_do']) ? "DO: {$state['no_do']}" : 'Tanpa DO';
                                                $inv = ! empty($state['no_invoice']) ? "Inv: {$state['no_invoice']}" : 'Belum Inv';
                                                $tgl = ! empty($state['tanggal_penerimaan']) ? date('d/m/Y', strtotime($state['tanggal_penerimaan'])) : '';

                                                return "{$tahap}  •  {$qty}  •  {$do}  •  {$inv}".($tgl ? "  •  Tgl: {$tgl}" : '');
                                            })
                                            ->columns(['default' => 1, 'md' => 2])
                                            ->schema([
                                                Hidden::make('delivery_order_receipt_id'),

                                                // Baris 1: Tahap Kirim & Kuantitas Kirim Fisik
                                                TextInput::make('tahap_kirim')
                                                    ->label('Tahap Pengiriman')
                                                    ->placeholder('cth: 1, 2, 3')
                                                    ->required(),

                                                TextInput::make('qty_kirim')
                                                    ->label('Kuantitas Kirim Fisik')
                                                    ->placeholder('cth: 150000')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required()
                                                    ->suffix('Lembar'),

                                                // Baris 2: Tanggal Terima & Dokumen Surat Jalan DO (Tersambung ke DO Receipt)
                                                DatePicker::make('tanggal_penerimaan')
                                                    ->label('Tgl Terima Fisik')
                                                    ->native(false)
                                                    ->displayFormat('d/m/Y'),

                                                TextInput::make('no_do')
                                                    ->label('No. Surat Jalan (DO)')
                                                    ->placeholder('cth: 019/DO/2026')
                                                    ->datalist(function (Get $get): array {
                                                        if (! DbSchema::hasTable('delivery_order_receipts')) {
                                                            return [];
                                                        }

                                                        $noPo = $get('../../no_po');
                                                        if (! empty($noPo) && DbSchema::hasTable('delivery_order_receipt_details') && DbSchema::hasTable('purchase_order_issueds')) {
                                                            $po = PurchaseOrderIssued::where('purchase_order_no', trim($noPo))->first();
                                                            if ($po) {
                                                                return DeliveryOrderReceipt::whereHas('deliveryOrderReceiptDetails', function ($q) use ($po) {
                                                                    $q->where('purchase_order_issued_id', $po->id);
                                                                })
                                                                    ->pluck('delivery_order_no')
                                                                    ->filter()
                                                                    ->unique()
                                                                    ->values()
                                                                    ->toArray();
                                                            }
                                                        }

                                                        return DeliveryOrderReceipt::latest('id')
                                                            ->limit(30)
                                                            ->pluck('delivery_order_no')
                                                            ->filter()
                                                            ->unique()
                                                            ->values()
                                                            ->toArray();
                                                    })
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                                                        if (empty($state) || ! DbSchema::hasTable('delivery_order_receipts')) {
                                                            return;
                                                        }

                                                        $query = DeliveryOrderReceipt::where('delivery_order_no', trim($state));
                                                        if (DbSchema::hasTable('delivery_order_receipt_details')) {
                                                            $query->with('deliveryOrderReceiptDetails');
                                                        }

                                                        $receipt = $query->first();

                                                        if ($receipt) {
                                                            $set('delivery_order_receipt_id', $receipt->id);
                                                            $date = $receipt->physical_received_date?->format('Y-m-d')
                                                                ?? $receipt->received_date?->format('Y-m-d');
                                                            if ($date) {
                                                                $set('tanggal_penerimaan', $date);
                                                            }
                                                            if ($receipt->dof_number) {
                                                                $set('no_surat', $receipt->dof_number);
                                                            }

                                                            if (DbSchema::hasTable('delivery_order_receipt_details') && $receipt->relationLoaded('deliveryOrderReceiptDetails')) {
                                                                $sumQty = (int) $receipt->deliveryOrderReceiptDetails->sum('quantity');
                                                                if ($sumQty > 0) {
                                                                    $set('qty_kirim', $sumQty);
                                                                    $set('qty_supply', $sumQty);
                                                                    $set('qty_surat', $sumQty);
                                                                }
                                                            }
                                                        }
                                                    }),

                                                // Baris 3: Dokumen Invoice & Surat Pengantar
                                                TextInput::make('no_invoice')
                                                    ->label('No. Invoice (Tagihan)')
                                                    ->placeholder('cth: INV-2026-001'),

                                                TextInput::make('no_surat')
                                                    ->label('No. Surat Pengantar / DOF')
                                                    ->placeholder('cth: 035/SP/2026'),

                                                // Baris 4: Realisasi Supply & Qty Surat
                                                TextInput::make('qty_supply')
                                                    ->label('Qty Realisasi Supply')
                                                    ->placeholder('cth: 150000')
                                                    ->numeric()
                                                    ->suffix('Lembar'),

                                                TextInput::make('qty_surat')
                                                    ->label('Qty Pada Surat')
                                                    ->placeholder('cth: 150000')
                                                    ->numeric()
                                                    ->suffix('Lembar'),

                                                // Baris 5: Catatan Pengiriman (Full Lebar 2 Kolom)
                                                Textarea::make('keterangan')
                                                    ->label('Catatan Pengiriman')
                                                    ->placeholder('Catatan gudang / penerimaan fisik atau catatan ekspedisi.')
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
