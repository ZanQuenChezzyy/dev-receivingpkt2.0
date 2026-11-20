<?php

namespace App\Filament\Resources\InvoicingReceipts\Tables;

use App\Models\InvoicingReceipt;
use App\Models\PurchaseOrderIssued;
use App\Models\User;
use App\Services\GrsPdfParserService;
use App\Services\InvoicingAssignmentService;
use App\Services\UserBadgeRenderer;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class InvoicingReceiptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ColumnGroup::make('Dokumen Utama & PIC', [
                    TextColumn::make('tanggal_terima_grs')
                        ->label('Tanggal Terima GRS')
                        ->date('d/m/Y')
                        ->icon('heroicon-m-calendar')
                        ->iconColor('primary')
                        ->sortable(),

                    TextColumn::make('no_po')
                        ->label('No. PO')
                        ->weight(FontWeight::Bold)
                        ->color('primary')
                        ->icon('heroicon-m-document-duplicate')
                        ->copyable()
                        ->copyMessage('Nomor PO disalin')
                        ->searchable()
                        ->sortable(),

                    TextColumn::make('no_grs')
                        ->label('No. GRS/RDTV')
                        ->state(fn (InvoicingReceipt $record): string => match ($record->jenis) {
                            'RDTV' => $record->no_rdtv ?: ($record->no_grs ?: '-'),
                            default => $record->no_grs ?: ($record->no_rdtv ?: '-'),
                        })
                        ->description(fn (InvoicingReceipt $record): ?string => $record->jenis)
                        ->copyable()
                        ->copyableState(fn (InvoicingReceipt $record): string => ($record->jenis === 'RDTV' ? ($record->no_rdtv ?: $record->no_grs) : ($record->no_grs ?: $record->no_rdtv)) ?: '')
                        ->copyMessage('Nomor GRS/RDTV disalin')
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query->where(function ($q) use ($search) {
                                $q->where('no_grs', 'like', "%{$search}%")
                                    ->orWhere('no_rdtv', 'like', "%{$search}%");
                            });
                        })
                        ->sortable(query: function (Builder $query, string $direction): Builder {
                            return $query->orderBy(DB::raw('COALESCE(no_grs, no_rdtv)'), $direction);
                        }),

                    TextColumn::make('pic.name')
                        ->label('PIC')
                        ->html()
                        ->formatStateUsing(fn ($state, ?InvoicingReceipt $record) => UserBadgeRenderer::render($record?->pic, 'Tentukan PIC'))
                        ->tooltip(fn ($record) => $record->pic_id ? "PIC: {$record->pic?->name} (Klik untuk ubah)" : 'Klik untuk menentukan PIC dokumen ini')
                        ->searchable(query: fn (Builder $query, string $search): Builder => UserBadgeRenderer::searchQuery($query, 'pic', $search))
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
                                            modifyQueryUsing: fn ($query) => $query->where('is_active', true)->whereHas('roles', fn ($q) => $q->where('name', 'Staff Invoice'))
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
                        ),

                    TextColumn::make('vendor_name')
                        ->label('Nama Vendor')
                        ->weight(FontWeight::Bold)
                        ->icon('heroicon-m-building-office')
                        ->searchable()
                        ->limit(25)
                        ->tooltip(fn ($record) => $record->vendor_name),
                ]),

                ColumnGroup::make('Referensi DO', [
                    TextColumn::make('no_do')
                        ->label('No. DO')
                        ->icon('heroicon-m-truck')
                        ->copyable()
                        ->copyMessage('Nomor DO disalin')
                        ->searchable()
                        ->sortable(),

                    TextColumn::make('kategori_barang')
                        ->label('Kategori')
                        ->badge()
                        ->color('gray')
                        ->placeholder('-')
                        ->searchable()
                        ->toggleable(),

                    TextColumn::make('material_type')
                        ->label('Tipe Mat.')
                        ->badge()
                        ->default(InvoicingReceipt::DEFAULT_MATERIAL_TYPE)
                        ->formatStateUsing(fn (?string $state): string => InvoicingReceipt::normalizeMaterialType($state))
                        ->color(fn (?string $state): string => InvoicingReceipt::normalizeMaterialType($state) === InvoicingReceipt::DEFAULT_MATERIAL_TYPE ? 'gray' : 'info')
                        ->searchable(),
                ]),

                ColumnGroup::make('Data Penagihan & Nilai', [
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
                        ->limit(24)
                        ->tooltip(fn ($record) => $record->nama_barang)
                        ->description(function (InvoicingReceipt $record): ?string {
                            $count = $record->relationLoaded('items') ? $record->items->count() : $record->items()->count();

                            return $count > 1 ? "({$count} item barang)" : null;
                        })
                        ->searchable(),

                    TextColumn::make('qty_penagihan')
                        ->label('Qty Tagihan')
                        ->formatStateUsing(fn ($record) => ($record->qty_penagihan ?? '-').' '.($record->uoi ?? ''))
                        ->sortable(),

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

                ColumnGroup::make('Status & Timeline', [
                    TextColumn::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'Menunggu Verifikasi' => 'warning',
                            'Terverifikasi' => 'info',
                            'Terkirim Kasir', 'Selesai' => 'success',
                            default => 'gray',
                        })
                        ->icon(fn (string $state): string => match ($state) {
                            'Menunggu Verifikasi' => 'heroicon-m-clock',
                            'Terverifikasi' => 'heroicon-m-check-badge',
                            'Terkirim Kasir' => 'heroicon-m-paper-airplane',
                            'Selesai' => 'heroicon-m-check-circle',
                            default => 'heroicon-m-document',
                        }),

                    TextColumn::make('tanggal_kirim_kasir')
                        ->label('Tgl Kirim Kasir')
                        ->date('d/m/Y')
                        ->placeholder(fn (InvoicingReceipt $record): string => match (true) {
                            $record->jenis === 'RDTV' => 'Tidak Perlu (RDTV)',
                            $record->status === 'Selesai' => 'Tanggal Kosong',
                            default => 'Belum Dikirim',
                        })
                        ->color(fn ($state, InvoicingReceipt $record): ?string => ($record->jenis !== 'RDTV' && $record->status === 'Selesai' && blank($state)) ? 'warning' : null)
                        ->tooltip(fn ($state, InvoicingReceipt $record): ?string => ($record->jenis !== 'RDTV' && $record->status === 'Selesai' && blank($state)) ? 'Invoice sudah ada (Selesai), namun tanggal kirim kasir masih kosong.' : null)
                        ->icon('heroicon-m-arrow-right-circle')
                        ->sortable(),

                    TextColumn::make('lama_pekerjaan')
                        ->label('Lama Pekerjaan')
                        ->badge()
                        ->suffix(' Hari')
                        ->color(fn ($state) => $state > 3 ? 'danger' : 'info')
                        ->icon('heroicon-m-clock')
                        ->sortable(),

                    TextColumn::make('dokumen_grs')
                        ->label('Dokumen GRS')
                        ->state(fn (InvoicingReceipt $record) => $record->dokumen_grs ?: $record->grsRdtvItem?->file_path)
                        ->formatStateUsing(fn ($state) => $state ? 'Ada Dokumen GRS' : 'Belum Ada')
                        ->badge()
                        ->color(fn ($state) => $state ? 'info' : 'gray')
                        ->icon(fn ($state) => $state ? 'heroicon-m-document-text' : null)
                        ->url(fn ($record) => ($record->dokumen_grs ?: $record->grsRdtvItem?->file_path) ? Storage::url($record->dokumen_grs ?: $record->grsRdtvItem?->file_path) : null)
                        ->openUrlInNewTab()
                        ->tooltip('Berkas Goods Receipt Slip (GRS / RDTV) fisik penerimaan gudang.'),

                    TextColumn::make('dokumen_verifikasi_grs')
                        ->label('Verifikasi (ACC VP)')
                        ->formatStateUsing(function ($state, InvoicingReceipt $record) {
                            if (! $state) {
                                return 'Belum Ada';
                            }
                            // Jika terisi file slip GRS gudang, bukan surat verifikasi ACC VP
                            $grsPath = $record->dokumen_grs ?: $record->grsRdtvItem?->file_path;
                            if ($grsPath && $grsPath === $state) {
                                return 'Belum Ada';
                            }

                            return 'Ada (ACC VP)';
                        })
                        ->badge()
                        ->color(function ($state, InvoicingReceipt $record) {
                            $grsPath = $record->dokumen_grs ?: $record->grsRdtvItem?->file_path;
                            if (! $state || ($grsPath && $grsPath === $state)) {
                                return 'gray';
                            }

                            return 'success';
                        })
                        ->icon(function ($state, InvoicingReceipt $record) {
                            $grsPath = $record->dokumen_grs ?: $record->grsRdtvItem?->file_path;
                            if (! $state || ($grsPath && $grsPath === $state)) {
                                return null;
                            }

                            return 'heroicon-m-check-badge';
                        })
                        ->url(function (InvoicingReceipt $record) {
                            $grsPath = $record->dokumen_grs ?: $record->grsRdtvItem?->file_path;
                            if (! $record->dokumen_verifikasi_grs || ($grsPath && $grsPath === $record->dokumen_verifikasi_grs)) {
                                return null;
                            }

                            return Storage::url($record->dokumen_verifikasi_grs);
                        })
                        ->openUrlInNewTab()
                        ->tooltip('Surat Verifikasi Penerimaan Barang resmi yang telah disetujui / ditandatangani VP.'),

                    TextColumn::make('dokumen_invoice')
                        ->label('Berkas Inv.')
                        ->formatStateUsing(fn ($state) => $state ? 'Ada Berkas' : 'Belum Ada')
                        ->badge()
                        ->color(fn ($state) => $state ? 'primary' : 'gray')
                        ->icon(fn ($state) => $state ? 'heroicon-m-arrow-top-right-on-square' : null)
                        ->url(fn ($record) => $record->dokumen_invoice ? Storage::url($record->dokumen_invoice) : null)
                        ->openUrlInNewTab()
                        ->tooltip('Berkas invoice fisik/resmi dari vendor.'),
                ]),

                // Kolom pelengkap (toggleable)
                TextColumn::make('tanggal_terima_koreksi_invoice')
                    ->label('Tanggal Terima Invoice')
                    ->date('d/m/Y')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'GRS' => 'success',
                        'RDTV' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tanggal_info_grs')
                    ->label('Tgl Info GRS')
                    ->date('d/m/Y')
                    ->placeholder('Belum Diinfokan')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('no_rdtv')
                    ->label('No. RDTV Khusus')
                    ->placeholder('-')
                    ->copyable()
                    ->copyMessage('No. RDTV disalin')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('email_vendor')
                    ->label('Email Vendor')
                    ->icon('heroicon-m-envelope')
                    ->iconColor('info')
                    ->placeholder('-')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('qty_po')
                    ->label('Qty di PO')
                    ->formatStateUsing(fn ($record) => ($record->qty_po ?? '-').' '.($record->uoi ?? ''))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(25)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                DateRangeFilter::make('tanggal_terima_grs')
                    ->label('Rentang Tgl Terima GRS')
                    ->placeholder('Pilih rentang tanggal')
                    ->modifyQueryUsing(function (Builder $query, ?Carbon $startDate, ?Carbon $endDate): Builder {
                        return $query
                            ->when($startDate && $endDate, fn ($q) => $q->whereBetween('tanggal_terima_grs', [
                                $startDate->toDateString(),
                                $endDate->toDateString(),
                            ]))
                            ->when($startDate && ! $endDate, fn ($q) => $q->where('tanggal_terima_grs', '>=', $startDate->toDateString()))
                            ->when(! $startDate && $endDate, fn ($q) => $q->where('tanggal_terima_grs', '<=', $endDate->toDateString()));
                    }),

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
                        'Selesai' => 'Selesai',
                    ]),

                SelectFilter::make('kategori_barang')
                    ->label('Filter Jenis Barang')
                    ->options(fn (): array => InvoicingReceipt::query()
                        ->whereNotNull('kategori_barang')
                        ->where('kategori_barang', '!=', '')
                        ->distinct()
                        ->orderBy('kategori_barang')
                        ->pluck('kategori_barang', 'kategori_barang')
                        ->all()),

                SelectFilter::make('pic_id')
                    ->label('Filter PIC (Staff Invoice)')
                    ->relationship('pic', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true)->whereHas('roles', fn ($q) => $q->where('name', 'Staff Invoice')))
                    ->hidden(fn ($livewire) => ($livewire?->activeTab ?? null) === 'my_tasks'),

                SelectFilter::make('material_type')
                    ->label('Filter Tipe Material')
                    ->options(InvoicingReceipt::materialTypeOptions())
                    ->hidden(fn ($livewire) => ($livewire?->activeTab ?? null) === 'my_tasks'),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->filtersFormColumns(['default' => 1, 'sm' => 2, 'lg' => 3])
            ->defaultSort('tanggal_terima_grs', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('id', 'desc'))
            ->recordActions([
                ActionGroup::make([
                    // ACTION KHUSUS RDTV: INFO GRS / INFO RDTV KE VENDOR -> SELESAI
                    Action::make('infoRdtv')
                        ->label(fn (?InvoicingReceipt $record): string => $record?->status === 'Selesai' ? 'Ubah Info RDTV' : 'Info RDTV (Selesai)')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (?InvoicingReceipt $record): bool => $record?->jenis === 'RDTV')
                        ->modalHeading(fn (?InvoicingReceipt $record): string => 'Info RDTV ke Vendor - PO: '.($record?->no_po ?? '-'))
                        ->modalDescription('Isi Tanggal Info GRS sebagai tanda RDTV sudah diinfokan ke vendor. Dokumen RDTV otomatis berstatus Selesai tanpa tahap Invoice maupun Kasir.')
                        ->modalSubmitActionLabel('Simpan & Selesaikan')
                        ->schema([
                            TextInput::make('no_rdtv')
                                ->label('No. RDTV (Opsional)')
                                ->placeholder('Nomor dokumen RDTV resmi')
                                ->prefixIcon('heroicon-m-hashtag')
                                ->default(fn (?InvoicingReceipt $record): ?string => $record?->no_rdtv),

                            DatePicker::make('tanggal_info_grs')
                                ->label('Tanggal Info GRS (Info RDTV ke Vendor)')
                                ->native(false)
                                ->required()
                                ->default(fn (?InvoicingReceipt $record) => $record?->tanggal_info_grs ?? now()->toDateString()),
                        ])
                        ->action(function (InvoicingReceipt $record, array $data): void {
                            $record->update([
                                'no_rdtv' => filled($data['no_rdtv'] ?? null) ? $data['no_rdtv'] : $record->no_rdtv,
                                'tanggal_info_grs' => $data['tanggal_info_grs'],
                                'status' => 'Selesai',
                            ]);

                            Notification::make()
                                ->title('Info RDTV Disimpan')
                                ->body("Dokumen RDTV PO {$record->no_po} telah ditandai Selesai.")
                                ->success()
                                ->send();
                        }),

                    // ACTION UTAMA 1: INPUT DATA INVOICE & NO. GRS
                    Action::make('inputInvoiceData')
                        ->label('Input Invoice & GRS')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->visible(fn (?InvoicingReceipt $record): bool => $record?->jenis !== 'RDTV')
                        ->modalWidth('5xl')
                        ->modalIcon('heroicon-o-document-currency-dollar')
                        ->modalHeading(fn (?InvoicingReceipt $record) => 'Input Data Invoice & No. GRS - PO: '.($record?->no_po ?? '-'))
                        ->modalDescription('Lengkapi Nomor GRS resmi SAP, No. DO, rincian barang yang ditagihkan, serta Nomor Invoice dan Faktur Pajak.')
                        ->modalSubmitActionLabel('Simpan Data Invoice')
                        ->modalCancelActionLabel('Tutup')
                        ->modalContent(fn (?InvoicingReceipt $record) => $record ? view('filament.actions.input-invoice-modal-header', ['record' => $record]) : null)
                        ->steps([
                            Step::make('Dokumen Penagihan')
                                ->description('Nomor dokumen resmi SAP & DO')
                                ->icon('heroicon-o-clipboard-document-check')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('no_grs')
                                        ->label('No. GRS Utama (Resmi SAP)')
                                        ->placeholder('Contoh: 5000xxxxxx')
                                        ->prefixIcon('heroicon-m-hashtag')
                                        ->default(fn (?InvoicingReceipt $record) => $record?->no_grs)
                                        ->required(fn (?InvoicingReceipt $record) => $record?->jenis === 'GRS')
                                        ->visible(fn (?InvoicingReceipt $record) => $record?->jenis !== 'RDTV')
                                        ->hint('Wajib dari SAP')
                                        ->hintColor('danger')
                                        ->hintIcon('heroicon-m-exclamation-circle')
                                        ->helperText('Nomor dokumen GRS resmi dari SAP (akan otomatis diterapkan ke seluruh rincian barang).'),

                                    TextInput::make('no_rdtv')
                                        ->label('No. RDTV (Resmi)')
                                        ->placeholder('Nomor dokumen RDTV')
                                        ->prefixIcon('heroicon-m-hashtag')
                                        ->default(fn (?InvoicingReceipt $record) => $record?->no_rdtv)
                                        ->required(fn (?InvoicingReceipt $record) => $record?->jenis === 'RDTV')
                                        ->visible(fn (?InvoicingReceipt $record) => $record?->jenis === 'RDTV')
                                        ->hint('Wajib RDTV')
                                        ->hintColor('danger')
                                        ->helperText('Nomor RDTV resmi dari slip fisik.'),

                                    TextInput::make('no_do')
                                        ->label('Nomor DO Utama (Delivery Order)')
                                        ->placeholder('Nomor Delivery Order')
                                        ->prefixIcon('heroicon-m-truck')
                                        ->default(fn (?InvoicingReceipt $record) => $record?->no_do ?: ($record ? InvoicingReceipt::findDeliveryOrderNoForPo($record->no_po) : null))
                                        ->required()
                                        ->hint('Wajib')
                                        ->hintColor('danger')
                                        ->helperText('Nomor surat jalan / Delivery Order yang ditagihkan.'),

                                    TextInput::make('no_invoice')
                                        ->label('Nomor Invoice')
                                        ->placeholder('Contoh: INV-2026/001 (Opsional)')
                                        ->prefixIcon('heroicon-m-document-text')
                                        ->default(fn (?InvoicingReceipt $record) => $record?->no_invoice)
                                        ->helperText('Nomor invoice penagihan resmi vendor (opsional, dapat diisi menyusul saat berkas fisik diterima).'),

                                    TextInput::make('faktur_pajak')
                                        ->label('Nomor Faktur Pajak')
                                        ->placeholder('Contoh: 010.001-26.12345678 (Opsional)')
                                        ->prefixIcon('heroicon-m-receipt-percent')
                                        ->default(fn (?InvoicingReceipt $record) => $record?->faktur_pajak)
                                        ->helperText('Nomor seri Faktur Pajak elektronik / e-Faktur (opsional, dapat diisi menyusul).'),

                                    DatePicker::make('tanggal_invoice')
                                        ->label('Tanggal Invoice')
                                        ->placeholder('Pilih tanggal invoice')
                                        ->prefixIcon('heroicon-m-calendar')
                                        ->native(false)
                                        ->default(fn (?InvoicingReceipt $record) => $record?->tanggal_invoice)
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            if (filled($state)) {
                                                $set('tanggal_faktur_pajak', $state);
                                            }
                                        })
                                        ->helperText('Tanggal terbit invoice resmi dari vendor.'),

                                    DatePicker::make('tanggal_faktur_pajak')
                                        ->label('Tanggal Faktur Pajak')
                                        ->placeholder('Pilih tanggal faktur pajak')
                                        ->prefixIcon('heroicon-m-calendar')
                                        ->native(false)
                                        ->default(fn (?InvoicingReceipt $record) => $record?->tanggal_faktur_pajak)
                                        ->helperText('Otomatis mengikuti tanggal invoice, namun dapat diubah jika berbeda.'),
                                ]),

                            Step::make('Rincian Barang & Ringkasan Total')
                                ->description('Daftar item barang yang ditagihkan serta ringkasan total nilai tagihan')
                                ->icon('heroicon-o-queue-list')
                                ->columns(2)
                                ->schema([
                                    Repeater::make('items')
                                        ->label('Item Barang yang Ditagihkan')
                                        ->collapsible()
                                        ->cloneable()
                                        ->addActionLabel('+ Tambah Item Barang')
                                        ->itemLabel(fn (array $state): ?string => ($state['nama_barang'] ?? null)
                                            ? ((isset($state['item_no']) ? '['.$state['item_no'].'] ' : '').$state['nama_barang'].(isset($state['qty_penagihan']) ? ' ('.$state['qty_penagihan'].' '.($state['uoi'] ?? '').')' : ''))
                                            : 'Item Barang')
                                        ->default(function (InvoicingReceipt $record): array {
                                            $poRecords = (filled($record->no_po) && $record->no_po !== '-')
                                                ? PurchaseOrderIssued::where('purchase_order_no', trim($record->no_po))->orderBy('item_no')->get()
                                                : collect();

                                            if ($record->items()->exists()) {
                                                return $record->items->sortBy('item_no')->values()->map(function ($it, $seq) use ($poRecords, $record) {
                                                    $matchedPo = PurchaseOrderIssued::matchByDescription($it->nama_barang, $poRecords)
                                                        ?? ($poRecords[$seq] ?? null);

                                                    $qtyPo = $it->qty_po !== null
                                                        ? (float) $it->qty_po
                                                        : ($matchedPo?->qty_po ? (float) $matchedPo->qty_po : null);

                                                    $qtyPenagihan = $it->qty_penagihan !== null
                                                        ? (float) $it->qty_penagihan
                                                        : $qtyPo;

                                                    return [
                                                        'id' => $it->id,
                                                        'item_no' => $it->item_no ?? $matchedPo?->item_no ?? (($seq + 1) * 10),
                                                        'material_code' => $it->material_code ?? $matchedPo?->material_code,
                                                        'nama_barang' => $it->nama_barang,
                                                        'qty_penagihan' => $qtyPenagihan,
                                                        'qty_po' => $qtyPo,
                                                        'uoi' => $it->uoi ?? $matchedPo?->uoi ?? $record->uoi,
                                                    ];
                                                })->values()->toArray();
                                            }

                                            // Tarik dari Master PO jika ada
                                            if ($poRecords->isNotEmpty()) {
                                                return $poRecords->map(fn ($p) => [
                                                    'item_no' => $p->item_no,
                                                    'material_code' => $p->material_code,
                                                    'nama_barang' => $p->description,
                                                    'qty_penagihan' => (float) $p->qty_po,
                                                    'qty_po' => (float) $p->qty_po,
                                                    'uoi' => $p->uoi,
                                                ])->values()->toArray();
                                            }

                                            // Tarik dari Delivery Order jika ada
                                            if ($record->deliveryOrderReceipt?->deliveryOrderReceiptDetails?->isNotEmpty()) {
                                                return $record->deliveryOrderReceipt->deliveryOrderReceiptDetails->map(fn ($d) => [
                                                    'item_no' => $d->item_no,
                                                    'material_code' => $d->material_code,
                                                    'nama_barang' => $d->description,
                                                    'qty_penagihan' => (float) $d->quantity,
                                                    'qty_po' => (float) ($d->purchaseOrderIssued?->qty_po ?? $d->quantity),
                                                    'uoi' => $d->uoi,
                                                ])->values()->toArray();
                                            }

                                            // Fallback single item
                                            if (filled($record->nama_barang) || filled($record->qty_penagihan) || filled($record->subtotal_exclude_ppn)) {
                                                return [
                                                    [
                                                        'item_no' => 10,
                                                        'material_code' => null,
                                                        'nama_barang' => $record->nama_barang,
                                                        'qty_penagihan' => $record->qty_penagihan !== null ? (float) $record->qty_penagihan : ($record->qty_po !== null ? (float) $record->qty_po : null),
                                                        'qty_po' => $record->qty_po !== null ? (float) $record->qty_po : null,
                                                        'uoi' => $record->uoi,
                                                    ],
                                                ];
                                            }

                                            return [];
                                        })
                                        ->columnSpanFull()
                                        ->columns(['default' => 1, 'sm' => 2, 'md' => 12])
                                        ->schema([
                                            Hidden::make('item_no'),
                                            Hidden::make('uoi'),

                                            TextInput::make('nama_barang')
                                                ->label('Deskripsi')
                                                ->placeholder('Nama atau deskripsi spesifikasi barang')
                                                ->required()
                                                ->columnSpan(['default' => 1, 'sm' => 2, 'md' => 6]),

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
                                                ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 3]),

                                            TextInput::make('qty_po')
                                                ->label('Qty PO')
                                                ->numeric()
                                                ->placeholder('0.00')
                                                ->suffix(fn (Get $get, ?Model $record) => $get('uoi') ?: ($record?->uoi ?? ''))
                                                ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 3]),
                                        ]),

                                    TextInput::make('subtotal_exclude_ppn')
                                        ->label('Total Sub Total (Exclude PPN)')
                                        ->prefix('Rp')
                                        ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                        ->formatStateUsing(fn ($state) => InvoicingReceipt::formatRupiah($state))
                                        ->dehydrateStateUsing(fn ($state) => InvoicingReceipt::parseRupiah($state))
                                        ->default(function (InvoicingReceipt $record): ?string {
                                            if ($record->subtotal_exclude_ppn !== null && (float) $record->subtotal_exclude_ppn > 0) {
                                                return InvoicingReceipt::formatRupiah($record->subtotal_exclude_ppn);
                                            }
                                            if (filled($record->no_po) && $record->no_po !== '-') {
                                                $sumPo = (float) PurchaseOrderIssued::where('purchase_order_no', trim($record->no_po))->sum('total_amount_in_lc');
                                                if ($sumPo > 0) {
                                                    return InvoicingReceipt::formatRupiah($sumPo);
                                                }
                                            }

                                            return null;
                                        })
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            $subtotal = InvoicingReceipt::parseRupiah($state);
                                            if ($subtotal !== null && $subtotal > 0) {
                                                $incPpn = (int) round($subtotal * 1.11);
                                                $set('subtotal_include_ppn', InvoicingReceipt::formatRupiah($incPpn));
                                            } else {
                                                $set('subtotal_include_ppn', null);
                                            }
                                        })
                                        ->columnSpan(1),

                                    TextInput::make('subtotal_include_ppn')
                                        ->label('Total Sub Total (Include PPN 11%)')
                                        ->prefix('Rp')
                                        ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                        ->formatStateUsing(fn ($state) => InvoicingReceipt::formatRupiah($state))
                                        ->dehydrateStateUsing(fn ($state) => InvoicingReceipt::parseRupiah($state))
                                        ->default(function (InvoicingReceipt $record): ?string {
                                            if ($record->subtotal_include_ppn !== null && (float) $record->subtotal_include_ppn > 0) {
                                                return InvoicingReceipt::formatRupiah($record->subtotal_include_ppn);
                                            }
                                            if ($record->subtotal_exclude_ppn !== null && (float) $record->subtotal_exclude_ppn > 0) {
                                                return InvoicingReceipt::formatRupiah(round((float) $record->subtotal_exclude_ppn * 1.11));
                                            }
                                            if (filled($record->no_po) && $record->no_po !== '-') {
                                                $sumPo = (float) PurchaseOrderIssued::where('purchase_order_no', trim($record->no_po))->sum('total_amount_in_lc');
                                                if ($sumPo > 0) {
                                                    return InvoicingReceipt::formatRupiah(round($sumPo * 1.11));
                                                }
                                            }

                                            return null;
                                        })
                                        ->columnSpan(1),
                                ]),
                        ])
                        ->skippableSteps()
                        ->action(function (InvoicingReceipt $record, array $data) {
                            $record->update([
                                'no_grs' => $data['no_grs'] ?? $record->no_grs,
                                'no_rdtv' => $data['no_rdtv'] ?? $record->no_rdtv,
                                'no_invoice' => $data['no_invoice'] ?? null,
                                'tanggal_invoice' => $data['tanggal_invoice'] ?? null,
                                'no_do' => $data['no_do'] ?? $record->no_do,
                                'faktur_pajak' => $data['faktur_pajak'] ?? null,
                                'tanggal_faktur_pajak' => $data['tanggal_faktur_pajak'] ?? null,
                            ]);

                            $submittedItems = $data['items'] ?? [];
                            if (! empty($submittedItems)) {
                                $record->items()->delete();

                                $poRecords = PurchaseOrderIssued::where('purchase_order_no', trim((string) $record->no_po))
                                    ->orderBy('item_no')
                                    ->get();
                                $poMap = $poRecords->keyBy('item_no');

                                $headerNoGrs = $data['no_grs'] ?? $record->no_grs;
                                $headerNoDo = $data['no_do'] ?? $record->no_do;

                                foreach ($submittedItems as $seq => $itemData) {
                                    $itemNo = isset($itemData['item_no']) && is_numeric($itemData['item_no']) ? (int) $itemData['item_no'] : null;

                                    // Match against PO terbit by description first!
                                    $matchedPo = null;
                                    if (filled($itemData['nama_barang'] ?? null)) {
                                        $matchedPo = PurchaseOrderIssued::matchByDescription($itemData['nama_barang'], $poRecords);
                                    }
                                    if (! $matchedPo && $itemNo && isset($poMap[$itemNo])) {
                                        $matchedPo = $poMap[$itemNo];
                                    }
                                    if (! $matchedPo && isset($poRecords[$seq])) {
                                        $matchedPo = $poRecords[$seq];
                                    }
                                    if (! $matchedPo) {
                                        $matchedPo = $poRecords->first();
                                    }

                                    $qtyPo = isset($itemData['qty_po']) && is_numeric($itemData['qty_po'])
                                        ? (float) $itemData['qty_po']
                                        : ($matchedPo?->qty_po ? (float) $matchedPo->qty_po : null);

                                    $qtyPenagihan = isset($itemData['qty_penagihan']) && is_numeric($itemData['qty_penagihan'])
                                        ? (float) $itemData['qty_penagihan']
                                        : $qtyPo;

                                    $record->items()->create([
                                        'purchase_order_issued_id' => $matchedPo?->id ?? $record->purchase_order_issued_id,
                                        'item_no' => $matchedPo?->item_no ?? $itemNo ?? (($seq + 1) * 10),
                                        'material_code' => $itemData['material_code'] ?? $matchedPo?->material_code,
                                        'nama_barang' => $itemData['nama_barang'] ?? $matchedPo?->description,
                                        'no_grs' => $itemData['no_grs'] ?? $headerNoGrs,
                                        'no_do' => $itemData['no_do'] ?? $headerNoDo,
                                        'qty_penagihan' => $qtyPenagihan,
                                        'qty_po' => $qtyPo,
                                        'uoi' => $itemData['uoi'] ?? $matchedPo?->uoi ?? $record->uoi,
                                    ]);
                                }

                                $record->syncSummaryFromItems();
                                if (array_key_exists('subtotal_exclude_ppn', $data)) {
                                    $cleanEx = InvoicingReceipt::parseRupiah($data['subtotal_exclude_ppn'] ?? null);
                                    $record->subtotal_exclude_ppn = $cleanEx;

                                    if (filled($data['subtotal_include_ppn'] ?? null)) {
                                        $cleanIn = InvoicingReceipt::parseRupiah($data['subtotal_include_ppn']);
                                        $record->subtotal_include_ppn = $cleanIn !== null ? (float) round($cleanIn) : null;
                                    } else {
                                        $record->subtotal_include_ppn = $cleanEx !== null ? (float) round($cleanEx * 1.11) : null;
                                    }
                                }
                                $record->save();
                            } else {
                                $cleanEx = array_key_exists('subtotal_exclude_ppn', $data)
                                    ? InvoicingReceipt::parseRupiah($data['subtotal_exclude_ppn'] ?? null)
                                    : $record->subtotal_exclude_ppn;

                                $cleanIn = array_key_exists('subtotal_include_ppn', $data)
                                    ? (filled($data['subtotal_include_ppn'] ?? null)
                                        ? (InvoicingReceipt::parseRupiah($data['subtotal_include_ppn']) !== null ? (float) round(InvoicingReceipt::parseRupiah($data['subtotal_include_ppn'])) : null)
                                        : ($cleanEx !== null ? (float) round($cleanEx * 1.11) : null))
                                    : ($cleanEx !== null ? (float) round((float) $cleanEx * 1.11) : $record->subtotal_include_ppn);

                                $record->update([
                                    'qty_penagihan' => $data['qty_penagihan'] ?? $record->qty_penagihan,
                                    'qty_po' => $data['qty_po'] ?? $record->qty_po,
                                    'uoi' => $data['uoi'] ?? $record->uoi,
                                    'subtotal_exclude_ppn' => $cleanEx,
                                    'subtotal_include_ppn' => $cleanIn,
                                ]);
                            }

                            Notification::make()
                                ->title('Data Invoice & Rincian Barang Berhasil Disimpan')
                                ->body(
                                    $record->wasChanged('status') && $record->status === 'Selesai'
                                    ? "Data penagihan untuk PO {$record->no_po} telah diperbarui dan status otomatis menjadi Selesai."
                                    : "Data penagihan dan rincian barang untuk PO {$record->no_po} telah diperbarui."
                                )
                                ->success()
                                ->send();
                        }),

                    // ACTION UTAMA 2: SALIN KE MITRAKU (MODAL INTERAKTIF)
                    Action::make('copyMitraku')
                        ->label('Salin ke Mitraku')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->color('primary')
                        ->visible(fn (?InvoicingReceipt $record): bool => $record?->jenis !== 'RDTV')
                        ->modalWidth('4xl')
                        ->modalHeading(fn (?InvoicingReceipt $record) => 'Format Salin Mitraku - PO: '.($record?->no_po ?? '-'))
                        ->modalDescription('Salin data secara instan untuk di-paste ke sistem Mitraku Pupuk Kaltim')
                        ->modalContent(fn (?InvoicingReceipt $record) => $record ? view('filament.actions.copy-to-mitraku-modal', ['record' => $record]) : null)
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup'),

                    // ACTION UTAMA 3: UPLOAD BERKAS (GRS, VERIFIKASI GRS ACC VP & INVOICE VENDOR)
                    Action::make('uploadBerkas')
                        ->label('Upload Berkas')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('info')
                        ->visible(fn (?InvoicingReceipt $record): bool => $record?->jenis !== 'RDTV')
                        ->modalHeading(fn (?InvoicingReceipt $record) => 'Upload Berkas - PO: '.($record?->no_po ?? '-'))
                        ->modalDescription('Unggah dokumen GRS gudang, dokumen verifikasi GRS (ACC VP), dan berkas invoice resmi vendor.')
                        ->schema([
                            FileUpload::make('dokumen_grs')
                                ->label('Dokumen GRS (Goods Receipt Slip)')
                                ->disk('public')
                                ->directory('berkas-grs')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->default(fn (?InvoicingReceipt $record) => $record?->dokumen_grs ?: $record?->grsRdtvItem?->file_path)
                                ->helperText('Unggah berkas slip GRS fisik penerimaan gudang (Goods Receipt Slip).'),

                            FileUpload::make('dokumen_verifikasi_grs')
                                ->label('Surat / Dokumen Verifikasi GRS (ACC VP)')
                                ->disk('public')
                                ->directory('verifikasi-grs')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->default(fn (?InvoicingReceipt $record) => ($record?->dokumen_verifikasi_grs && $record?->dokumen_verifikasi_grs !== ($record?->dokumen_grs ?: $record?->grsRdtvItem?->file_path)) ? $record->dokumen_verifikasi_grs : null)
                                ->helperText('Unggah surat verifikasi penerimaan barang resmi yang telah disetujui / ditandatangani VP.'),

                            FileUpload::make('dokumen_invoice')
                                ->label('Berkas Invoice Vendor')
                                ->disk('public')
                                ->directory('berkas-invoice')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->default(fn (?InvoicingReceipt $record) => $record?->dokumen_invoice)
                                ->helperText('Unggah berkas invoice resmi setelah vendor mengirimkan berkas tagihan fisik ke PKT.'),
                        ])
                        ->action(function (InvoicingReceipt $record, array $data) {
                            $grsFile = $data['dokumen_grs'] ?? null;
                            $verifikasiFile = $data['dokumen_verifikasi_grs'] ?? null;
                            $invoiceFile = $data['dokumen_invoice'] ?? null;

                            if (empty($grsFile) && empty($verifikasiFile) && empty($invoiceFile) && empty($record->dokumen_grs) && empty($record->dokumen_verifikasi_grs) && empty($record->dokumen_invoice)) {
                                Notification::make()
                                    ->title('Tidak Ada Berkas Dipilih')
                                    ->body('Silakan pilih setidaknya satu berkas (Dokumen GRS, Verifikasi GRS ACC VP, atau Invoice Vendor).')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $updateData = [];

                            if (array_key_exists('dokumen_grs', $data)) {
                                $updateData['dokumen_grs'] = $grsFile;
                            }

                            if (array_key_exists('dokumen_verifikasi_grs', $data)) {
                                $updateData['dokumen_verifikasi_grs'] = $verifikasiFile;
                                if (! empty($verifikasiFile) && $record->status === 'Menunggu Verifikasi') {
                                    $updateData['status'] = 'Terverifikasi';
                                }

                                if (! empty($verifikasiFile) && strtolower(pathinfo($verifikasiFile, PATHINFO_EXTENSION)) === 'pdf') {
                                    $fullPath = Storage::disk('public')->path($verifikasiFile);
                                    if (file_exists($fullPath)) {
                                        try {
                                            $parsed = app(GrsPdfParserService::class)->parseVerifikasiGrs($fullPath, basename($verifikasiFile));
                                            if (! empty($parsed['no_grs']) && empty($record->no_grs)) {
                                                $updateData['no_grs'] = $parsed['no_grs'];
                                            }
                                            if (! empty($parsed['tanggal_release_gr'])) {
                                                $updateData['tanggal_terima_grs'] = $parsed['tanggal_release_gr'];
                                            }
                                            if (! empty($parsed['tanggal_penerimaan'])) {
                                                $updateData['tanggal_penerimaan'] = $parsed['tanggal_penerimaan'];
                                            }
                                            if (! empty($parsed['tanggal_po']) && empty($record->tanggal_po)) {
                                                $updateData['tanggal_po'] = $parsed['tanggal_po'];
                                            }
                                            if (! empty($parsed['no_do']) && empty($record->no_do)) {
                                                $updateData['no_do'] = $parsed['no_do'];
                                            }
                                            if (! empty($parsed['vendor_name']) && empty($record->vendor_name)) {
                                                $updateData['vendor_name'] = $parsed['vendor_name'];
                                            }
                                        } catch (\Throwable $e) {
                                            report($e);
                                        }
                                    }
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
                                ->body("Dokumen GRS / verifikasi / invoice untuk PO {$record->no_po} telah diperbarui.")
                                ->success()
                                ->send();
                        }),

                    // ACTION TENTUKAN PIC (MUNCUL JIKA BELUM ADA PIC)
                    Action::make('setPic')
                        ->label('Tentukan PIC')
                        ->icon('heroicon-o-user-plus')
                        ->color('warning')
                        ->visible(fn (InvoicingReceipt $record) => is_null($record->pic_id))
                        ->modalHeading(fn (InvoicingReceipt $record) => "Tentukan PIC - PO: {$record->no_po}")
                        ->modalDescription('Pilih staf invoice yang akan bertanggung jawab menangani dokumen ini.')
                        ->schema([
                            Select::make('pic_id')
                                ->label('Pilih Staff Invoice')
                                ->relationship(
                                    name: 'pic',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn ($query) => $query->where('is_active', true)->whereHas('roles', fn ($q) => $q->where('name', 'Staff Invoice'))
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
                                    modifyQueryUsing: fn ($query) => $query->where('is_active', true)->whereHas('roles', fn ($q) => $q->where('name', 'Staff Invoice'))
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
                        ->visible(fn (?InvoicingReceipt $record): bool => $record?->jenis !== 'RDTV' && ! in_array($record?->status, ['Terkirim Kasir', 'Selesai'], true))
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
                    ->button()
                    ->dropdownPlacement('bottom-start')
                    ->dropdownFlip(false)
                    ->dropdownOffset(6)
                    ->dropdownMaxHeight('20rem'),
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
                                ->options(fn () => User::where('is_active', true)->role('Staff Invoice')->pluck('name', 'id'))
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
                            $staffCount = User::where('is_active', true)->role('Staff Invoice')->count();

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
