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
use Filament\Notifications\Notification;
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
                        ->sortable(),

                    TextColumn::make('subtotal_include_ppn')
                        ->label('Sub Total (Incl. PPN)')
                        ->money('IDR', locale: 'id')
                        ->weight(FontWeight::Bold)
                        ->color('success')
                        ->copyable()
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

                // ACTION UTAMA 1: SALIN KE MITRAKU (MODAL INTERAKTIF)
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

                // ACTION UTAMA 2: UPLOAD DOKUMEN VERIFIKASI GRS
                Action::make('uploadVerifikasi')
                    ->label('Upload Verifikasi')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->button()
                    ->outlined()
                    ->size(Size::Small)
                    ->modalHeading('Upload Dokumen Verifikasi GRS')
                    ->modalDescription('Unggah berkas verifikasi GRS dan berkas invoice resmi jika ada.')
                    ->schema([
                        FileUpload::make('dokumen_verifikasi_grs')
                            ->label('Pilih File Verifikasi GRS')
                            ->disk('public')
                            ->directory('verifikasi-grs')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->default(fn (InvoicingReceipt $record) => $record->dokumen_verifikasi_grs)
                            ->required(),

                        FileUpload::make('dokumen_invoice')
                            ->label('Pilih Berkas Invoice (Opsional)')
                            ->disk('public')
                            ->directory('berkas-invoice')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->default(fn (InvoicingReceipt $record) => $record->dokumen_invoice)
                            ->helperText('Unggah berkas invoice resmi dari vendor (PDF/Gambar).'),
                    ])
                    ->action(function (InvoicingReceipt $record, array $data) {
                        $updateData = [
                            'dokumen_verifikasi_grs' => $data['dokumen_verifikasi_grs'],
                            'status' => 'Terverifikasi',
                        ];

                        if (! empty($data['dokumen_invoice'])) {
                            $updateData['dokumen_invoice'] = $data['dokumen_invoice'];
                        }

                        $record->update($updateData);

                        Notification::make()
                            ->title('Verifikasi Berhasil')
                            ->body("Dokumen verifikasi untuk PO {$record->no_po} telah diunggah.")
                            ->success()
                            ->send();
                    }),

                // ACTION UTAMA 3: UPLOAD BERKAS INVOICE
                Action::make('uploadInvoice')
                    ->label('Upload Invoice')
                    ->icon('heroicon-o-document-arrow-up')
                    ->color('info')
                    ->button()
                    ->outlined()
                    ->size(Size::Small)
                    ->modalHeading('Upload Berkas Invoice')
                    ->modalDescription('Unggah berkas invoice resmi dari vendor (PDF/Gambar).')
                    ->schema([
                        FileUpload::make('dokumen_invoice')
                            ->label('Pilih Berkas Invoice')
                            ->disk('public')
                            ->directory('berkas-invoice')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->default(fn (InvoicingReceipt $record) => $record->dokumen_invoice)
                            ->required(),
                    ])
                    ->action(function (InvoicingReceipt $record, array $data) {
                        $record->update([
                            'dokumen_invoice' => $data['dokumen_invoice'],
                        ]);

                        Notification::make()
                            ->title('Invoice Berhasil Diunggah')
                            ->body("Berkas invoice untuk PO {$record->no_po} telah disimpan.")
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
