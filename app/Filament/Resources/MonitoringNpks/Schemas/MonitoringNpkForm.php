<?php

namespace App\Filament\Resources\MonitoringNpks\Schemas;

use App\Filament\Resources\MonitoringNpks\MonitoringNpkResource;
use App\Models\MonitoringNpk;
use App\Models\PurchaseOrderIssued;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\EmptyState;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

class MonitoringNpkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)->schema([
                    // === KOLOM KIRI: PO & Dokumen + Timeline ===
                    Group::make()->schema([
                        Section::make('Data Dokumen Monitoring')
                            ->description('Pilih Nomor PO untuk memuat item otomatis. Lengkapi Nomor DO, Tahapan proses, dan Lokasi penerimaan.')
                            ->icon('heroicon-m-bars-3-bottom-left')
                            ->columns(12)
                            ->schema([
                                Select::make('purchase_order_terbit_id')
                                    ->label('Nomor Purchase Order')
                                    ->placeholder('Pilih Nomor PO')
                                    ->relationship(
                                        name: 'purchaseOrderIssued',
                                        titleAttribute: 'purchase_order_no',
                                        modifyQueryUsing: fn ($query) => $query
                                            ->selectRaw('MIN(id) as id, purchase_order_no')
                                            ->groupBy('purchase_order_no')
                                            ->orderBy('purchase_order_no')
                                    )
                                    ->searchable()
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(function (mixed $state, Set $set, Get $get): void {
                                        if (! $state) {
                                            $set('details', []);

                                            return;
                                        }

                                        $anchor = PurchaseOrderIssued::find($state);
                                        if (! $anchor) {
                                            $set('details', []);

                                            return;
                                        }

                                        $items = PurchaseOrderIssued::where('purchase_order_no', $anchor->purchase_order_no)
                                            ->orderBy('item_no')
                                            ->get(['id', 'item_no', 'material_code', 'description', 'qty_po', 'uoi']);

                                        $currentMonitoringId = (int) ($get('id') ?? 0);

                                        $filteredItems = $items->map(function ($it) use ($currentMonitoringId) {
                                            $h = MonitoringNpkResource::hitungSisaDbByItem((int) $it->id, $currentMonitoringId > 0 ? $currentMonitoringId : null);
                                            $poQty = (float) ($h['po'] ?? 0);
                                            $usedDb = (float) ($h['used_db'] ?? 0);
                                            $sisa = $poQty - $usedDb;

                                            if ($sisa <= 0) {
                                                return null;
                                            }

                                            return [
                                                'purchase_order_issued_id' => $it->id,
                                                'item_no' => $it->item_no,
                                                'material_code' => $it->material_code,
                                                'description' => $it->description,
                                                'quantity' => $sisa,
                                                'uoi' => $it->uoi,
                                                'is_qty_tolerance' => false,
                                            ];
                                        })->filter()->values()->toArray();

                                        $set('details', ! empty($filteredItems) ? $filteredItems : []);
                                    })
                                    ->columnSpan(6),

                                TextInput::make('delivery_oder_number')
                                    ->label('Nomor DO')
                                    ->placeholder('Masukkan Nomor DO')
                                    ->maxLength(50)
                                    ->required()
                                    ->rule(
                                        fn (Get $get, ?MonitoringNpk $record) => Rule::unique('monitoring_npks', 'delivery_oder_number')
                                            ->where(fn ($q) => $q->where('purchase_order_terbit_id', (int) $get('purchase_order_terbit_id')))
                                            ->ignore($record?->getKey())
                                    )
                                    ->dehydrateStateUsing(function (?string $state) {
                                        $s = trim((string) $state);

                                        return $s === '' || $s === '-' ? null : $s;
                                    })
                                    ->helperText('Unik per Nomor PO.')
                                    ->columnSpan(6),

                                TextInput::make('stage')
                                    ->label('Tahapan')
                                    ->placeholder('Masukkan Tahapan')
                                    ->maxLength(100)
                                    ->columnSpan(6),

                                Select::make('location_id')
                                    ->label('Lokasi')
                                    ->placeholder('Pilih Lokasi')
                                    ->relationship('location', 'name')
                                    ->default(125)
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->columnSpan(6),
                            ]),

                        Section::make('Timeline Proses')
                            ->description('Catat urutan tanggal penerimaan material dan dokumen secara berurutan.')
                            ->icon('heroicon-m-calendar-days')
                            ->columns(12)
                            ->schema([
                                DatePicker::make('sample_receivied_date')
                                    ->label('Terima Sample')
                                    ->placeholder('Pilih tanggal')
                                    ->native(false)
                                    ->helperText('Tanggal sample pertama kali diterima oleh tim.')
                                    ->prefixIcon('heroicon-m-beaker')
                                    ->columnSpan(6),

                                DatePicker::make('delivery_oder_delivery_date')
                                    ->label('DO Dikirim')
                                    ->placeholder('Pilih tanggal')
                                    ->live()
                                    ->native(false)
                                    ->helperText('Tanggal dokumen Delivery Order mulai dikirim.')
                                    ->prefixIcon('heroicon-m-truck')
                                    ->columnSpan(6),

                                DatePicker::make('received_date')
                                    ->label('Penerimaan (Actual)')
                                    ->placeholder('Pilih tanggal')
                                    ->visible(fn (Get $get) => filled($get('delivery_oder_delivery_date')))
                                    ->required(fn (Get $get) => filled($get('delivery_oder_delivery_date')))
                                    ->native(false)
                                    ->helperText('Tanggal material fisik tiba di lokasi.')
                                    ->prefixIcon('heroicon-m-inbox-arrow-down')
                                    ->columnSpan(6),

                                DatePicker::make('purchase_order_103_date')
                                    ->label('Proses 103')
                                    ->placeholder('Pilih tanggal')
                                    ->native(false)
                                    ->helperText('Tanggal verifikasi proses 103 (Goods Receipt).')
                                    ->prefixIcon('heroicon-m-document-check')
                                    ->columnSpan(6),

                                DatePicker::make('laprima_date')
                                    ->label('Terbit LAPRIMA')
                                    ->placeholder('Pilih tanggal')
                                    ->native(false)
                                    ->helperText('Tanggal dokumen Laporan Penerimaan Material terbit.')
                                    ->prefixIcon('heroicon-m-clipboard-document')
                                    ->columnSpan(6),

                                DatePicker::make('coa_date')
                                    ->label('Terima COA')
                                    ->placeholder('Pilih tanggal')
                                    ->live()
                                    ->native(false)
                                    ->helperText('Tanggal Certificate of Analysis (COA) diterima.')
                                    ->prefixIcon('heroicon-m-shield-check')
                                    ->columnSpan(6),

                                FileUpload::make('coa_files')
                                    ->label('Dokumen COA (PDF)')
                                    ->multiple()
                                    ->appendFiles()
                                    ->directory('monitoring-npk-docs')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->downloadable()
                                    ->openable()
                                    ->helperText('Unggah salinan dokumen COA sebagai bukti.')
                                    ->visible(fn (Get $get) => filled($get('coa_date')))
                                    ->required(fn (Get $get) => filled($get('coa_date')))
                                    ->columnSpan(6),
                            ]),
                    ])->columnSpan(['lg' => 7]),

                    // === KOLOM KANAN: DETAIL ITEM ===
                    Group::make()->schema([
                        Section::make('Detail Item')
                            ->description('Wajib pilih nomor PO terlebih dahulu. Atur kuantitas sesuai DO aktual.')
                            ->icon('heroicon-m-clipboard-document-list')
                            ->schema([
                                Repeater::make('details')
                                    ->hiddenLabel()
                                    ->relationship('details')
                                    ->addActionLabel('Tambah Item')
                                    ->defaultItems(0)
                                    ->minItems(1)
                                    ->disabled(fn (Get $get) => blank($get('purchase_order_terbit_id')))
                                    ->addable(fn (Get $get) => filled($get('purchase_order_terbit_id')))
                                    ->deletable(fn (Get $get) => filled($get('purchase_order_terbit_id')))
                                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                        $data['quantity'] = (float) str_replace(',', '.', (string) ($data['quantity'] ?? 0));
                                        unset($data['id']);

                                        return $data;
                                    })
                                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data, $record): array {
                                        $data['quantity'] = (float) str_replace(',', '.', (string) ($data['quantity'] ?? 0));

                                        return $data;
                                    })
                                    ->schema([
                                        Grid::make(12)->schema([
                                            Select::make('purchase_order_issued_id')
                                                ->label('Item No')
                                                ->placeholder('Pilih Item')
                                                ->options(function (Get $get) {
                                                    $anchorId = $get('../../purchase_order_terbit_id');
                                                    if (! $anchorId) {
                                                        return [];
                                                    }
                                                    $anchor = PurchaseOrderIssued::find($anchorId);
                                                    if (! $anchor) {
                                                        return [];
                                                    }

                                                    return PurchaseOrderIssued::where('purchase_order_no', $anchor->purchase_order_no)
                                                        ->orderBy('item_no')
                                                        ->get()
                                                        ->mapWithKeys(fn ($r) => [
                                                            $r->id => str_pad((string) $r->item_no, 2, '0', STR_PAD_LEFT),
                                                        ])->all();
                                                })
                                                ->afterStateHydrated(function (Select $component, $state, Get $get, $record) {
                                                    if (! $state && $record && $record->item_no) {
                                                        $anchorId = $get('../../purchase_order_terbit_id');
                                                        if ($anchorId) {
                                                            $anchor = PurchaseOrderIssued::find($anchorId);
                                                            if ($anchor) {
                                                                $poItem = PurchaseOrderIssued::where('purchase_order_no', $anchor->purchase_order_no)
                                                                    ->where('item_no', $record->item_no)
                                                                    ->first();
                                                                if ($poItem) {
                                                                    $component->state($poItem->id);
                                                                }
                                                            }
                                                        }
                                                    }
                                                })
                                                ->searchable()
                                                ->live()
                                                ->columnSpan(4)
                                                ->afterStateUpdated(function (Set $set, Get $get, $state, $record) {
                                                    if (! $state) {
                                                        $set('item_no', null);
                                                        $set('material_code', null);
                                                        $set('description', null);
                                                        $set('uoi', null);
                                                        $set('quantity', null);
                                                        $set('is_qty_tolerance', false);

                                                        return;
                                                    }

                                                    $po = PurchaseOrderIssued::find($state);
                                                    if (! $po) {
                                                        return;
                                                    }

                                                    $currentMonitoringId = $get('../../id');
                                                    $h = MonitoringNpkResource::hitungSisaDbByItem((int) $po->id, $currentMonitoringId);
                                                    $poQty = (float) ($h['po'] ?? 0);
                                                    $usedDb = (float) ($h['used_db'] ?? 0);
                                                    $sisa = max(0, $poQty - $usedDb);

                                                    $set('item_no', $po->item_no);
                                                    $set('material_code', $po->material_code);
                                                    $set('description', $po->description);
                                                    $set('uoi', $po->uoi);
                                                    $set('quantity', $sisa);
                                                    $set('is_qty_tolerance', false);
                                                })
                                                ->rule(function (Get $get) {
                                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                        $rows = $get('../*') ?? [];
                                                        $ids = collect($rows)->pluck('purchase_order_issued_id')->filter()->all();
                                                        if (count($ids) !== count(array_unique($ids))) {
                                                            $fail('Item yang sama tidak boleh dipilih dua kali.');
                                                        }
                                                    };
                                                }),

                                            Hidden::make('item_no')->dehydrated(true),
                                            Hidden::make('uoi')->dehydrated(true),

                                            TextInput::make('material_code')
                                                ->label('Mat. Code')
                                                ->disabled()
                                                ->dehydrated(true)
                                                ->columnSpan(8),

                                            TextInput::make('description')
                                                ->label('Deskripsi')
                                                ->disabled()
                                                ->dehydrated(true)
                                                ->columnSpan(12),

                                            TextInput::make('quantity')
                                                ->label('Quantity Aktual')
                                                ->required()
                                                ->dehydrateStateUsing(fn ($state): ?float => $state !== null && $state !== '' ? (float) str_replace(',', '.', (string) $state) : null)
                                                ->suffix(fn (Get $get): ?string => $get('uoi') ?: null)
                                                ->columnSpan(8)
                                                ->validationAttribute('Quantity')
                                                ->live(onBlur: true)
                                                ->rules([
                                                    fn (Get $get, $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                                        $valString = str_replace(',', '.', (string) $value);

                                                        if (! is_numeric($valString) || (float) $valString <= 0) {
                                                            $fail('Format kuantitas harus berupa angka positif (gunakan titik atau koma untuk desimal).');

                                                            return;
                                                        }

                                                        $isToleranceActive = (bool) ($get('is_qty_tolerance') ?? false);
                                                        $rowPoTerbitId = (int) ($get('purchase_order_issued_id') ?? 0);
                                                        $itemNo = $get('item_no');

                                                        if (! $rowPoTerbitId || ! $itemNo) {
                                                            return;
                                                        }

                                                        $currentMonitoringId = $get('../../id');
                                                        $h = MonitoringNpkResource::hitungSisaDbByItem($rowPoTerbitId, $currentMonitoringId);
                                                        $qtyPo = (float) ($h['po'] ?? 0);
                                                        $netSaved = (float) ($h['used_db'] ?? 0);
                                                        $uoi = (string) ($h['uoi'] ?? '');

                                                        $currentInput = (float) $valString;
                                                        $totalAkanDiterima = $netSaved + $currentInput;

                                                        $maxAllowedWithTolerance = $qtyPo * 1.10; // 10% tolerance

                                                        if (! $isToleranceActive && $totalAkanDiterima > $qtyPo) {
                                                            $selisih = $totalAkanDiterima - $qtyPo;
                                                            $fmtSelisih = rtrim(rtrim(number_format($selisih, 4, ',', '.'), '0'), ',');
                                                            $fail("Input tidak valid! Kelebihan {$fmtSelisih} {$uoi}. Aktifkan 'Toleransi Qty' atau kurangi angka.");
                                                        } elseif ($isToleranceActive && $totalAkanDiterima > $maxAllowedWithTolerance) {
                                                            $fail('Kuantitas melebihi batas maksimal toleransi 10% dari PO ('.number_format($maxAllowedWithTolerance, 2, ',', '.')." {$uoi}).");
                                                        }
                                                    },
                                                ])
                                                ->helperText(function (Get $get, $record) {
                                                    $rowPoTerbitId = (int) ($get('purchase_order_issued_id') ?? 0);
                                                    $itemNo = $get('item_no');

                                                    if (! $rowPoTerbitId || ! $itemNo) {
                                                        return null;
                                                    }

                                                    $currentMonitoringId = $get('../../id');
                                                    $h = MonitoringNpkResource::hitungSisaDbByItem($rowPoTerbitId, $currentMonitoringId);
                                                    $qtyPo = (float) ($h['po'] ?? 0);
                                                    $netSaved = (float) ($h['used_db'] ?? 0);
                                                    $qtyDitolak = (float) ($h['qty_ditolak'] ?? 0);
                                                    $uoi = (string) ($h['uoi'] ?? 'EA');

                                                    $currentInput = (float) str_replace(',', '.', (string) ($get('quantity') ?? 0));

                                                    $fmtNetSaved = number_format($netSaved, 3, ',', '.');
                                                    $totalAkanDiterima = $netSaved + $currentInput;
                                                    $sisaSetelahInput = $qtyPo - $totalAkanDiterima;

                                                    $fmtQtyPo = rtrim(rtrim(number_format($qtyPo, 4, ',', '.'), '0'), ',');
                                                    $fmtTotalAkanDiterima = rtrim(rtrim(number_format($totalAkanDiterima, 4, ',', '.'), '0'), ',');
                                                    $fmtSisaAbsolut = rtrim(rtrim(number_format(abs($sisaSetelahInput), 4, ',', '.'), '0'), ',');

                                                    if ($get('is_qty_tolerance') && $sisaSetelahInput < 0) {
                                                        $statusInfo = "<span style='color: #d97706; font-weight: bold;'>Toleransi Aktif: {$fmtSisaAbsolut} {$uoi}</span>";
                                                    } else {
                                                        $colorSisa = $sisaSetelahInput < 0 ? '#dc2626' : ($sisaSetelahInput == 0 ? '#6b7280' : '#f59e0b');
                                                        $statusLabel = $sisaSetelahInput < 0 ? 'OVER LIMIT' : 'Quantity Tersisa';
                                                        $statusInfo = "<span style='color: {$colorSisa}; font-weight: bold;'>{$statusLabel}: {$fmtSisaAbsolut} {$uoi}</span>";
                                                    }

                                                    $colorAkanDiterima = ($totalAkanDiterima >= $qtyPo) ? '#16a34a' : ($totalAkanDiterima > 0 ? '#16a34a' : '#6b7280');
                                                    $colorRiwayat = ($netSaved > 0) ? '#4090ff' : '#4b5563';
                                                    $liRdtv = $qtyDitolak > 0 ? "<li style='color: #d97706;'>Qty Ditolak (RDTV Dikembalikan): <b>".rtrim(rtrim(number_format($qtyDitolak, 4, ',', '.'), '0'), ',')." {$uoi}</b></li>" : '';

                                                    return new HtmlString("
                                                        <ul class='list-disc pl-5 space-y-1 text-xs text-gray-500'>
                                                            <li>PO Terbit: <b class='text-gray-700'>{$fmtQtyPo} {$uoi}</b></li>
                                                            <li style='color: {$colorRiwayat};'>Riwayat Terima: <b>{$fmtNetSaved} {$uoi}</b></li>
                                                            {$liRdtv}
                                                            <li style='color: {$colorAkanDiterima}; font-weight: 600;'>Riwayat + Input Saat Ini: <b>{$fmtTotalAkanDiterima} {$uoi}</b></li>
                                                            <li>{$statusInfo}</li>
                                                        </ul>
                                                    ");
                                                }),

                                            Toggle::make('is_qty_tolerance')
                                                ->label('Toleransi Qty?')
                                                ->inline(false)
                                                ->onColor('danger')
                                                ->live()
                                                ->afterStateUpdated(function (Set $set, Get $get) {
                                                    $set('quantity', $get('quantity'));
                                                })
                                                ->default(false)
                                                ->dehydrated()
                                                ->columnSpan(4),
                                        ]),
                                    ])
                                    ->columnSpanFull(),

                                EmptyState::make('Belum ada Nomor PO yang dipilih')
                                    ->description('Silakan cari dan pilih Nomor PO terlebih dahulu untuk menampilkan detail item.')
                                    ->icon('heroicon-o-cursor-arrow-rays')
                                    ->contained(true)
                                    ->visible(fn (Get $get, $record): bool => filled($get('purchase_order_terbit_id')) === false && $record === null),

                                EmptyState::make('Semua item dalam PO ini sudah diterima sepenuhnya.')
                                    ->description('Tidak ada sisa kuota material yang tersedia untuk diproses pada nomor PO ini.')
                                    ->icon('heroicon-o-check-circle')
                                    ->contained(true)
                                    ->visible(fn (Get $get): bool => filled($get('purchase_order_terbit_id')) && empty($get('details'))),
                            ]),

                        Section::make('Status Purchase Order')
                            ->icon('heroicon-m-clipboard-document-check')
                            ->description('A (Sesuai PO) / B (Selesai).')
                            ->columns(12)
                            ->schema([
                                ToggleButtons::make('purchase_order_status')
                                    ->label('Status PO (terkini)')
                                    ->options(['A' => 'A / Kosong', 'B' => 'B / Selesai'])
                                    ->icons(['A' => 'heroicon-m-x-circle', 'B' => 'heroicon-m-check-circle'])
                                    ->colors(['A' => 'danger', 'B' => 'success'])
                                    ->grouped()
                                    ->inline()
                                    ->live()
                                    ->extraAttributes(['class' => 'mx-auto flex justify-center w-full'])
                                    ->afterStateUpdated(function (string $state, Set $set, Get $get) {
                                        if ($state === 'B' && blank($get('purchase_order_status_b_date'))) {
                                            $set('purchase_order_status_b_date', now()->toDateString());
                                        }
                                        if ($state === 'A' && blank($get('purchase_order_status_a_date'))) {
                                            $set('purchase_order_status_a_date', now()->toDateString());
                                        }
                                    })
                                    ->columnSpan(12),

                                Fieldset::make('Status A')
                                    ->columns(12)
                                    ->schema([
                                        DatePicker::make('purchase_order_status_a_date')
                                            ->label('Tanggal Status A')
                                            ->native(false)
                                            ->disabled(fn (Get $get) => blank($get('purchase_order_status')) || $get('purchase_order_status') === 'B')
                                            ->required(fn (Get $get) => $get('purchase_order_status') === 'A')
                                            ->columnSpan(6),

                                        DatePicker::make('purchase_order_status_b_date')
                                            ->label('Tanggal Status B')
                                            ->native(false)
                                            ->disabled(fn (Get $get) => blank($get('purchase_order_status')) || $get('purchase_order_status') === 'A')
                                            ->required(fn (Get $get) => $get('purchase_order_status') === 'B')
                                            ->columnSpan(6),

                                        FileUpload::make('purchase_order_status_a_files')
                                            ->label('Evidence Status A')
                                            ->multiple()
                                            ->appendFiles()
                                            ->directory('monitoring-npk-docs')
                                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                            ->disabled(fn (Get $get) => blank($get('purchase_order_status')) || $get('purchase_order_status') === 'B')
                                            ->required(fn (Get $get) => $get('purchase_order_status') === 'A')
                                            ->columnSpan(12),
                                    ])
                                    ->columnSpan(12),
                            ]),

                        Hidden::make('created_by')
                            ->default(fn () => Auth::id() ?? 1)
                            ->required(),

                        Hidden::make('id')->dehydrated(false),
                    ])->columnSpan(['lg' => 5]),
                ])->columnSpanFull(),
            ]);
    }
}
