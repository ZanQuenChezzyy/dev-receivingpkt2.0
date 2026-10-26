<?php

namespace App\Filament\Resources\InvoicingReceipts\Tables;

use App\Models\InvoicingReceipt;
use App\Models\User;
use App\Services\InvoicingAssignmentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class InvoicingReceiptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ColumnGroup::make('Dokumen GRS / RDTV', [
                    TextColumn::make('tanggal_terima_grs')
                        ->label('Tgl Terima GRS')
                        ->date('d/m/Y')
                        ->icon('heroicon-m-calendar')
                        ->iconColor('primary')
                        ->sortable(),

                    TextColumn::make('jenis')
                        ->label('Jenis')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'GRS' => 'success',
                            'RDTV' => 'warning',
                            default => 'gray',
                        })
                        ->icon(fn (string $state): string => match ($state) {
                            'GRS' => 'heroicon-m-document-check',
                            'RDTV' => 'heroicon-m-arrow-path',
                            default => 'heroicon-m-document',
                        }),

                    TextColumn::make('no_grs')
                        ->label('No. GRS')
                        ->placeholder('-')
                        ->copyable()
                        ->copyMessage('No. GRS disalin')
                        ->searchable(),

                    TextColumn::make('no_rdtv')
                        ->label('No. RDTV')
                        ->placeholder('-')
                        ->copyable()
                        ->copyMessage('No. RDTV disalin')
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->searchable(),

                    TextColumn::make('tanggal_info_grs')
                        ->label('Tgl Info GRS')
                        ->date('d/m/Y')
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),

                ColumnGroup::make('Referensi PO & DO', [
                    TextColumn::make('no_po')
                        ->label('No. PO')
                        ->weight(FontWeight::Bold)
                        ->color('primary')
                        ->icon('heroicon-m-document-duplicate')
                        ->copyable()
                        ->copyMessage('Nomor PO disalin')
                        ->searchable()
                        ->sortable(),

                    TextColumn::make('no_do')
                        ->label('No. DO')
                        ->icon('heroicon-m-truck')
                        ->copyable()
                        ->copyMessage('Nomor DO disalin')
                        ->searchable()
                        ->sortable(),

                    TextColumn::make('material_type')
                        ->label('Tipe Mat.')
                        ->badge()
                        ->color('info')
                        ->searchable(),
                ]),

                ColumnGroup::make('Vendor & Kontak', [
                    TextColumn::make('vendor_name')
                        ->label('Nama Vendor')
                        ->weight(FontWeight::Bold)
                        ->icon('heroicon-m-building-office')
                        ->searchable()
                        ->limit(25)
                        ->tooltip(fn ($record) => $record->vendor_name),

                    TextColumn::make('email_vendor')
                        ->label('Email Vendor')
                        ->icon('heroicon-m-envelope')
                        ->iconColor('info')
                        ->placeholder('-')
                        ->copyable()
                        ->searchable(),
                ]),

                ColumnGroup::make('PIC Penugasan', [
                    TextColumn::make('pic.name')
                        ->label('PIC')
                        ->placeholder('Belum Ada (Tentukan PIC)')
                        ->badge()
                        ->color(fn ($record) => $record->pic_id ? 'primary' : 'warning')
                        ->icon(fn ($record) => $record->pic_id ? 'heroicon-m-user' : 'heroicon-m-user-plus')
                        ->tooltip(fn ($record) => $record->pic_id ? "PIC: {$record->pic?->name}" : 'Klik untuk menentukan PIC dokumen ini')
                        ->action(
                            Action::make('setPicFromColumn')
                                ->modalHeading(fn (InvoicingReceipt $record) => "Tentukan PIC - PO: {$record->no_po}")
                                ->modalDescription('Pilih staf invoice yang akan bertanggung jawab menangani dokumen ini.')
                                ->schema([
                                    Select::make('pic_id')
                                        ->label('Pilih Staff Invoice')
                                        ->relationship(
                                            name: 'pic',
                                            titleAttribute: 'name',
                                            modifyQueryUsing: fn ($query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'Staff Invoice'))
                                        )
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->helperText('Hanya pengguna dengan role "Staff Invoice" yang dapat dipilih.')
                                        ->default(function (InvoicingReceipt $record) {
                                            if ($record->pic_id) {
                                                return $record->pic_id;
                                            }
                                            $user = Auth::user();

                                            return ($user && $user->hasRole('Staff Invoice')) ? $user->id : null;
                                        }),
                                ])
                                ->action(function (InvoicingReceipt $record, array $data) {
                                    $record->update(['pic_id' => $data['pic_id']]);

                                    $assignedUser = User::find($data['pic_id']);
                                    $assignedName = $assignedUser?->name ?? 'Staff Invoice';

                                    Notification::make()
                                        ->title('PIC Berhasil Ditentukan')
                                        ->body("Dokumen PO {$record->no_po} kini ditugaskan kepada {$assignedName}.")
                                        ->success()
                                        ->send();
                                })
                        )
                        ->searchable(),
                ]),

                ColumnGroup::make('Data Penagihan & Mitraku', [
                    TextColumn::make('no_invoice')
                        ->label('No. Invoice')
                        ->placeholder('Belum Ada')
                        ->copyable()
                        ->copyMessage('No. Invoice disalin')
                        ->searchable(),

                    TextColumn::make('faktur_pajak')
                        ->label('Faktur Pajak')
                        ->placeholder('Belum Ada')
                        ->copyable()
                        ->copyMessage('Faktur Pajak disalin')
                        ->searchable(),

                    TextColumn::make('nama_barang')
                        ->label('Nama Barang')
                        ->limit(20)
                        ->tooltip(fn ($record) => $record->nama_barang)
                        ->searchable(),

                    TextColumn::make('qty_penagihan')
                        ->label('Qty Tagihan')
                        ->formatStateUsing(fn ($record) => ($record->qty_penagihan ?? '-').' '.($record->uoi ?? ''))
                        ->sortable(),

                    TextColumn::make('qty_po')
                        ->label('Qty di PO')
                        ->formatStateUsing(fn ($record) => ($record->qty_po ?? '-').' '.($record->uoi ?? ''))
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('subtotal_exclude_ppn')
                        ->label('Sub Total (Excl. PPN)')
                        ->money('IDR', locale: 'id')
                        ->copyable()
                        ->copyableState(fn ($state) => $state !== null ? (floor((float) $state) == (float) $state ? number_format((float) $state, 0, '', '') : rtrim(rtrim(number_format((float) $state, 2, '.', ''), '0'), '.')) : null)
                        ->sortable(),

                    TextColumn::make('subtotal_include_ppn')
                        ->label('Sub Total (Incl. PPN)')
                        ->money('IDR', locale: 'id')
                        ->weight(FontWeight::Bold)
                        ->color('success')
                        ->copyable()
                        ->copyableState(fn ($state) => $state !== null ? (floor((float) $state) == (float) $state ? number_format((float) $state, 0, '', '') : rtrim(rtrim(number_format((float) $state, 2, '.', ''), '0'), '.')) : null)
                        ->sortable(),
                ]),

                ColumnGroup::make('Timeline & Kasir', [
                    TextColumn::make('tanggal_terima_koreksi_invoice')
                        ->label('Tgl Terima/Koreksi')
                        ->date('d/m/Y')
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('tanggal_kirim_kasir')
                        ->label('Tgl Kirim Kasir')
                        ->date('d/m/Y')
                        ->placeholder('Belum Dikirim')
                        ->icon('heroicon-m-arrow-right-circle')
                        ->sortable(),

                    TextColumn::make('lama_pekerjaan')
                        ->label('Lama Pekerjaan')
                        ->badge()
                        ->suffix(' Hari')
                        ->color(fn ($state) => $state > 3 ? 'danger' : 'info')
                        ->icon('heroicon-m-clock')
                        ->sortable(),

                    TextColumn::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'Menunggu Verifikasi' => 'warning',
                            'Terverifikasi' => 'info',
                            'Terkirim Kasir' => 'success',
                            default => 'gray',
                        })
                        ->icon(fn (string $state): string => match ($state) {
                            'Menunggu Verifikasi' => 'heroicon-m-clock',
                            'Terverifikasi' => 'heroicon-m-check-badge',
                            'Terkirim Kasir' => 'heroicon-m-paper-airplane',
                            default => 'heroicon-m-document',
                        }),

                    TextColumn::make('dokumen_invoice')
                        ->label('Berkas Inv.')
                        ->formatStateUsing(fn ($state) => $state ? 'Ada Berkas' : 'Belum Ada')
                        ->badge()
                        ->color(fn ($state) => $state ? 'info' : 'gray')
                        ->icon(fn ($state) => $state ? 'heroicon-m-arrow-top-right-on-square' : null)
                        ->url(fn ($record) => $record->dokumen_invoice ? Storage::url($record->dokumen_invoice) : null)
                        ->openUrlInNewTab(),

                    TextColumn::make('dokumen_verifikasi_grs')
                        ->label('Dok. Verifikasi')
                        ->formatStateUsing(fn ($state) => $state ? 'Ada Berkas' : 'Belum Ada')
                        ->badge()
                        ->color(fn ($state) => $state !== 'Belum Ada' ? 'success' : 'gray')
                        ->icon(fn ($state) => $state !== 'Belum Ada' ? 'heroicon-m-arrow-top-right-on-square' : null)
                        ->url(fn ($record) => $record->dokumen_verifikasi_grs ? Storage::url($record->dokumen_verifikasi_grs) : null)
                        ->openUrlInNewTab(),
                ]),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(25)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jenis')
                    ->label('Filter Kategori')
                    ->options([
                        'GRS' => 'GRS',
                        'RDTV' => 'RDTV',
                    ]),

                SelectFilter::make('status')
                    ->label('Status Verifikasi')
                    ->options([
                        'Menunggu Verifikasi' => 'Menunggu Verifikasi',
                        'Terverifikasi' => 'Terverifikasi',
                        'Terkirim Kasir' => 'Terkirim Kasir',
                    ]),

                SelectFilter::make('material_type')
                    ->label('Filter Tipe Material')
                    ->options([
                        'ZSP' => 'ZSP',
                        'ZFP' => 'ZFP',
                        'ZPM' => 'ZPM',
                        'ZRM' => 'ZRM',
                        'ZSM' => 'ZSM',
                    ]),

                SelectFilter::make('pic_id')
                    ->label('Filter PIC (Staff Invoice)')
                    ->relationship('pic', 'name', modifyQueryUsing: fn ($query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'Staff Invoice'))),
            ])
            ->recordActions([
                // ACTION TENTUKAN PIC (MUNCUL JIKA BELUM ADA PIC)
                Action::make('setPic')
                    ->label('Tentukan PIC')
                    ->icon('heroicon-o-user-plus')
                    ->color('warning')
                    ->button()
                    ->outlined()
                    ->size(Size::Small)
                    ->visible(fn (InvoicingReceipt $record) => is_null($record->pic_id))
                    ->modalHeading(fn (InvoicingReceipt $record) => "Tentukan PIC - PO: {$record->no_po}")
                    ->modalDescription('Pilih staf invoice yang akan bertanggung jawab menangani dokumen ini.')
                    ->schema([
                        Select::make('pic_id')
                            ->label('Pilih Staff Invoice')
                            ->relationship(
                                name: 'pic',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'Staff Invoice'))
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Hanya pengguna dengan role "Staff Invoice" yang dapat dipilih.')
                            ->default(function () {
                                $user = Auth::user();

                                return ($user && $user->hasRole('Staff Invoice')) ? $user->id : null;
                            }),
                    ])
                    ->action(function (InvoicingReceipt $record, array $data) {
                        $record->update(['pic_id' => $data['pic_id']]);

                        $assignedUser = User::find($data['pic_id']);
                        $assignedName = $assignedUser?->name ?? 'Staff Invoice';

                        Notification::make()
                            ->title('PIC Berhasil Ditentukan')
                            ->body("Dokumen PO {$record->no_po} kini ditugaskan kepada {$assignedName}.")
                            ->success()
                            ->send();
                    }),

                // ACTION UTAMA 1: INPUT DATA INVOICE & NO. GRS
                Action::make('inputInvoiceData')
                    ->label('Input Invoice & GRS')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->button()
                    ->outlined()
                    ->size(Size::Small)
                    ->modalWidth('4xl')
                    ->modalIcon('heroicon-o-document-currency-dollar')
                    ->modalHeading(fn (InvoicingReceipt $record) => "Input Data Invoice & No. GRS - PO: {$record->no_po}")
                    ->modalDescription('Lengkapi No. Invoice, No. DO, Faktur Pajak, dan Nomor GRS resmi SAP.')
                    ->modalSubmitActionLabel('Simpan Data Invoice')
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (InvoicingReceipt $record) => view('filament.actions.input-invoice-modal-header', ['record' => $record]))
                    ->schema([
                        Section::make('Dokumen Penagihan Wajib')
                            ->description('Nomor dokumen resmi penagihan vendor dan slip resmi GRS/RDTV dari SAP.')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->schema([
                                TextInput::make('no_grs')
                                    ->label('No. GRS (Resmi SAP)')
                                    ->placeholder('Contoh: 5000xxxxxx')
                                    ->prefixIcon('heroicon-m-hashtag')
                                    ->default(fn (InvoicingReceipt $record) => $record->no_grs)
                                    ->required(fn (InvoicingReceipt $record) => $record->jenis === 'GRS')
                                    ->visible(fn (InvoicingReceipt $record) => $record->jenis !== 'RDTV')
                                    ->hint('Wajib dari SAP')
                                    ->hintColor('danger')
                                    ->hintIcon('heroicon-m-exclamation-circle')
                                    ->helperText('Nomor GRS/105.'),

                                TextInput::make('no_rdtv')
                                    ->label('No. RDTV (Resmi)')
                                    ->placeholder('Nomor dokumen RDTV')
                                    ->prefixIcon('heroicon-m-hashtag')
                                    ->default(fn (InvoicingReceipt $record) => $record->no_rdtv)
                                    ->required(fn (InvoicingReceipt $record) => $record->jenis === 'RDTV')
                                    ->visible(fn (InvoicingReceipt $record) => $record->jenis === 'RDTV')
                                    ->hint('Wajib RDTV')
                                    ->hintColor('danger')
                                    ->helperText('Nomor RDTV resmi dari slip fisik.'),

                                TextInput::make('no_invoice')
                                    ->label('Nomor Invoice')
                                    ->placeholder('Contoh: INV-2026/001')
                                    ->prefixIcon('heroicon-m-document-text')
                                    ->default(fn (InvoicingReceipt $record) => $record->no_invoice)
                                    ->required()
                                    ->hint('Wajib dari Vendor')
                                    ->hintColor('danger')
                                    ->helperText('Nomor invoice penagihan resmi yang tertera pada berkas tagihan vendor.'),

                                TextInput::make('no_do')
                                    ->label('Nomor DO (Delivery Order)')
                                    ->placeholder('Nomor Delivery Order')
                                    ->prefixIcon('heroicon-m-truck')
                                    ->default(fn (InvoicingReceipt $record) => $record->no_do)
                                    ->required()
                                    ->hint('Wajib')
                                    ->hintColor('danger')
                                    ->helperText('Nomor surat jalan / Delivery Order yang ditagihkan.'),

                                TextInput::make('faktur_pajak')
                                    ->label('Nomor Faktur Pajak')
                                    ->placeholder('Contoh: 010.001-26.12345678')
                                    ->prefixIcon('heroicon-m-receipt-percent')
                                    ->default(fn (InvoicingReceipt $record) => $record->faktur_pajak)
                                    ->required()
                                    ->hint('Wajib')
                                    ->hintColor('danger')
                                    ->helperText('Nomor seri Faktur Pajak elektronik (e-Faktur) dari vendor.'),
                            ])->columns(2),

                        Section::make('Rincian Kuantitas & Nilai Penagihan')
                            ->description('Kuantitas barang dan perhitungan nilai tagihan. Nilai Include PPN terhitung otomatis (+11%).')
                            ->icon('heroicon-o-calculator')
                            ->schema([
                                TextInput::make('qty_penagihan')
                                    ->label('Quantity Penagihan')
                                    ->numeric()
                                    ->prefixIcon('heroicon-m-archive-box')
                                    ->default(fn (InvoicingReceipt $record) => $record->qty_penagihan)
                                    ->suffix(fn (InvoicingReceipt $record) => $record->uoi ?: 'UOI')
                                    ->hint('Ditagihkan Vendor')
                                    ->helperText('Jumlah kuantitas barang yang ditagihkan oleh vendor.'),

                                TextInput::make('qty_po')
                                    ->label('Quantity di PO')
                                    ->numeric()
                                    ->prefixIcon('heroicon-m-shopping-cart')
                                    ->default(fn (InvoicingReceipt $record) => $record->qty_po)
                                    ->suffix(fn (InvoicingReceipt $record) => $record->uoi ?: 'UOI')
                                    ->hint('Referensi PO')
                                    ->helperText('Total kuantitas barang yang tertera pada PO terbit.'),

                                TextInput::make('uoi')
                                    ->label('Satuan Barang (UOI)')
                                    ->prefixIcon('heroicon-m-tag')
                                    ->placeholder('PCS, TON, SET, dll')
                                    ->default(fn (InvoicingReceipt $record) => $record->uoi)
                                    ->helperText('Satuan Unit of Issue barang.'),

                                TextInput::make('subtotal_exclude_ppn')
                                    ->label('Sub Total (Exclude PPN)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(fn (InvoicingReceipt $record) => $record->subtotal_exclude_ppn)
                                    ->live(onBlur: true)
                                    ->hint('DPP Sebelum PPN')
                                    ->hintColor('primary')
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state !== null && is_numeric($state)) {
                                            $subtotal = (float) $state;
                                            $set('subtotal_include_ppn', round($subtotal + ($subtotal * 0.11), 2));
                                        } else {
                                            $set('subtotal_include_ppn', null);
                                        }
                                    })
                                    ->helperText('Dasar Pengenaan Pajak (DPP) sebelum dikenakan PPN.'),

                                TextInput::make('subtotal_include_ppn')
                                    ->label('Sub Total (Include PPN 11%)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(fn (InvoicingReceipt $record) => $record->subtotal_include_ppn)
                                    ->hint('Otomatis +11% PPN')
                                    ->hintColor('success')
                                    ->hintIcon('heroicon-m-sparkles')
                                    ->helperText('Rumus otomatis: Exclude PPN + (Exclude PPN × 11%).'),
                            ])->columns(2),
                    ])
                    ->action(function (InvoicingReceipt $record, array $data) {
                        $record->update([
                            'no_grs' => $data['no_grs'] ?? $record->no_grs,
                            'no_rdtv' => $data['no_rdtv'] ?? $record->no_rdtv,
                            'no_invoice' => $data['no_invoice'],
                            'no_do' => $data['no_do'],
                            'faktur_pajak' => $data['faktur_pajak'],
                            'qty_penagihan' => $data['qty_penagihan'] ?? $record->qty_penagihan,
                            'qty_po' => $data['qty_po'] ?? $record->qty_po,
                            'uoi' => $data['uoi'] ?? $record->uoi,
                            'subtotal_exclude_ppn' => $data['subtotal_exclude_ppn'] ?? $record->subtotal_exclude_ppn,
                            'subtotal_include_ppn' => $data['subtotal_include_ppn'] ?? $record->subtotal_include_ppn,
                        ]);

                        Notification::make()
                            ->title('Data Invoice Berhasil Disimpan')
                            ->body("Data penagihan untuk PO {$record->no_po} telah diperbarui.")
                            ->success()
                            ->send();
                    }),

                // ACTION UTAMA 2: SALIN KE MITRAKU (MODAL INTERAKTIF)
                Action::make('copyMitraku')
                    ->label('Salin ke Mitraku')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('primary')
                    ->button()
                    ->outlined()
                    ->size(Size::Small)
                    ->modalHeading(fn (InvoicingReceipt $record) => "Format Salin Mitraku - PO: {$record->no_po}")
                    ->modalDescription('Salin data secara instan untuk di-paste ke sistem Mitraku Pupuk Kaltim')
                    ->modalContent(fn (InvoicingReceipt $record) => view('filament.actions.copy-to-mitraku-modal', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                // ACTION UTAMA 3: UPLOAD BERKAS (VERIFIKASI GRS ACC VP & INVOICE VENDOR)
                Action::make('uploadBerkas')
                    ->label('Upload Berkas')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->button()
                    ->outlined()
                    ->size(Size::Small)
                    ->modalHeading(fn (InvoicingReceipt $record) => "Upload Berkas - PO: {$record->no_po}")
                    ->modalDescription('Unggah dokumen verifikasi GRS (setelah ACC VP) dan berkas invoice resmi (setelah vendor mengirim ke PKT).')
                    ->schema([
                        FileUpload::make('dokumen_verifikasi_grs')
                            ->label('Surat / Dokumen Verifikasi GRS (ACC VP)')
                            ->disk('public')
                            ->directory('verifikasi-grs')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->default(fn (InvoicingReceipt $record) => $record->dokumen_verifikasi_grs)
                            ->helperText('Unggah surat verifikasi GRS setelah mendapat persetujuan (ACC) dari VP.'),

                        FileUpload::make('dokumen_invoice')
                            ->label('Berkas Invoice Vendor')
                            ->disk('public')
                            ->directory('berkas-invoice')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->default(fn (InvoicingReceipt $record) => $record->dokumen_invoice)
                            ->helperText('Unggah berkas invoice resmi setelah vendor mengirimkan berkas tagihan fisik ke PKT.'),
                    ])
                    ->action(function (InvoicingReceipt $record, array $data) {
                        $verifikasiFile = $data['dokumen_verifikasi_grs'] ?? null;
                        $invoiceFile = $data['dokumen_invoice'] ?? null;

                        if (empty($verifikasiFile) && empty($invoiceFile) && empty($record->dokumen_verifikasi_grs) && empty($record->dokumen_invoice)) {
                            Notification::make()
                                ->title('Tidak Ada Berkas Dipilih')
                                ->body('Silakan pilih setidaknya satu berkas (Verifikasi GRS ACC VP atau Invoice Vendor).')
                                ->warning()
                                ->send();

                            return;
                        }

                        $updateData = [];

                        if (array_key_exists('dokumen_verifikasi_grs', $data)) {
                            $updateData['dokumen_verifikasi_grs'] = $verifikasiFile;
                            if (! empty($verifikasiFile) && $record->status === 'Menunggu Verifikasi') {
                                $updateData['status'] = 'Terverifikasi';
                            }
                        }

                        if (array_key_exists('dokumen_invoice', $data)) {
                            $updateData['dokumen_invoice'] = $invoiceFile;
                        }

                        if (! empty($updateData)) {
                            $record->update($updateData);
                        }

                        Notification::make()
                            ->title('Berkas Berhasil Disimpan')
                            ->body("Dokumen verifikasi / invoice untuk PO {$record->no_po} telah diperbarui.")
                            ->success()
                            ->send();
                    }),

                // ACTION GROUP UNTUK AKSI LAINNYA
                ActionGroup::make([
                    // Klaim PIC jika belum ada PIC
                    Action::make('claimPic')
                        ->label('Ambil Sebagai PIC')
                        ->icon('heroicon-o-hand-raised')
                        ->color('warning')
                        ->visible(fn (InvoicingReceipt $record) => is_null($record->pic_id))
                        ->requiresConfirmation()
                        ->modalHeading('Klaim Penugasan PIC')
                        ->modalDescription('Anda akan ditetapkan sebagai penanggung jawab (PIC) untuk dokumen ini.')
                        ->action(function (InvoicingReceipt $record) {
                            $user = Auth::user();
                            if (! $user || (! $user->hasRole('Staff Invoice') && ! $user->hasRole('Developer') && ! $user->hasRole('Administrator'))) {
                                Notification::make()
                                    ->title('Akses Ditolak')
                                    ->body('Hanya pengguna dengan role "Staff Invoice" yang dapat mengambil tugas ini.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $record->update(['pic_id' => $user->id]);

                            Notification::make()
                                ->title('Penugasan Diambil')
                                ->body("Anda sekarang menjadi PIC untuk dokumen PO {$record->no_po}.")
                                ->success()
                                ->send();
                        }),

                    // Tugaskan PIC (Staff Invoice)
                    Action::make('assignPic')
                        ->label('Tugaskan PIC')
                        ->icon('heroicon-o-user-plus')
                        ->color('primary')
                        ->modalHeading('Tugaskan PIC Staff Invoice')
                        ->modalDescription('Pilih staf invoice yang bertanggung jawab atau kosongkan untuk mengembalikan ke status Open Pool.')
                        ->schema([
                            Select::make('pic_id')
                                ->label('Pilih PIC (Staff Invoice)')
                                ->placeholder('Bebas ke Siapapun (Open Pool)')
                                ->relationship(
                                    name: 'pic',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn ($query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'Staff Invoice'))
                                )
                                ->searchable()
                                ->preload()
                                ->helperText('Hanya pengguna dengan role "Staff Invoice" yang dapat dipilih.')
                                ->default(fn (InvoicingReceipt $record) => $record->pic_id),
                        ])
                        ->action(function (InvoicingReceipt $record, array $data) {
                            $record->update(['pic_id' => $data['pic_id'] ?? null]);

                            Notification::make()
                                ->title('PIC Diperbarui')
                                ->body("PIC untuk PO {$record->no_po} berhasil diperbarui.")
                                ->success()
                                ->send();
                        }),

                    // Kirim ke Kasir
                    Action::make('sendToCashier')
                        ->label('Kirim ke Kasir')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->visible(fn (InvoicingReceipt $record) => $record->status !== 'Terkirim Kasir')
                        ->requiresConfirmation()
                        ->modalHeading('Kirim Berkas ke Kasir')
                        ->modalDescription('Sistem akan mengisi Tanggal Kirim Kasir hari ini dan menghitung lama pekerjaan.')
                        ->action(function (InvoicingReceipt $record) {
                            $record->update([
                                'tanggal_kirim_kasir' => now()->toDateString(),
                                'status' => 'Terkirim Kasir',
                            ]);

                            Notification::make()
                                ->title('Terkirim ke Kasir')
                                ->body("Dokumen PO {$record->no_po} telah diserahkan ke bagian Kasir.")
                                ->success()
                                ->send();
                        }),

                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->label('')
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->size(Size::Small)
                    ->color('info')
                    ->outlined()
                    ->button(),
            ], position: RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('assignPicBulk')
                        ->label('Tugaskan PIC ke Terpilih')
                        ->icon('heroicon-o-user-plus')
                        ->color('primary')
                        ->modalHeading('Tugaskan PIC ke Dokumen Terpilih')
                        ->schema([
                            Select::make('pic_id')
                                ->label('Pilih PIC (Staff Invoice)')
                                ->placeholder('Bebas ke Siapapun (Open Pool)')
                                ->options(fn () => User::role('Staff Invoice')->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->helperText('Hanya pengguna dengan role "Staff Invoice" yang dapat dipilih.'),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each(fn (InvoicingReceipt $record) => $record->update(['pic_id' => $data['pic_id'] ?? null]));

                            Notification::make()
                                ->title('PIC Diperbarui')
                                ->body(count($records).' dokumen penagihan berhasil diperbarui PIC-nya.')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('distributeEvenlyBulk')
                        ->label('Bagi Rata ke Staff Invoice')
                        ->icon('heroicon-o-arrows-pointing-out')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Bagi Rata Dokumen ke Staff Invoice')
                        ->modalDescription('Sistem akan membagikan dokumen yang dipilih secara seimbang dan merata ke seluruh pengguna dengan role "Staff Invoice".')
                        ->action(function (Collection $records) {
                            $service = app(InvoicingAssignmentService::class);
                            $assignedCount = $service->distributeEvenly($records);
                            $staffCount = User::role('Staff Invoice')->count();

                            if ($staffCount === 0) {
                                Notification::make()
                                    ->title('Penugasan Gagal')
                                    ->body('Tidak ditemukan pengguna dengan role "Staff Invoice".')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->title('Berhasil Dibagi Rata')
                                ->body("Sebanyak {$assignedCount} dokumen berhasil dibagikan secara merata ke {$staffCount} Staff Invoice.")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
