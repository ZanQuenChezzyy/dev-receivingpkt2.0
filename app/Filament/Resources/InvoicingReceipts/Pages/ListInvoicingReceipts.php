<?php

namespace App\Filament\Resources\InvoicingReceipts\Pages;

use App\Filament\Resources\InvoicingReceipts\InvoicingReceiptResource;
use App\Filament\Resources\InvoicingReceipts\Widgets\InvoicingOverviewWidget;
use App\Http\Controllers\Admin\ChunkedImportUploadController;
use App\Models\InvoicingReceipt;
use App\Services\GrsPdfParserService;
use App\Services\InvoicingCsvImportService;
use App\Services\MonitoringBarangImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListInvoicingReceipts extends ListRecords
{
    protected static string $resource = InvoicingReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importInvoiceBarang')
                ->label('Impor Invoice Barang')
                ->icon(Heroicon::ArrowDownTray)
                ->color('success')
                ->modalHeading('Impor Data Invoice / Monitoring Barang (.xlsx / .csv)')
                ->modalDescription('Unggah file Excel (.xlsx) atau CSV Monitoring Barang maupun ekspor Mitraku. Sistem akan mendeteksi format file secara otomatis.')
                ->form([
                    ViewField::make('monitoring_file')
                        ->label('File Excel (.xlsx) atau CSV')
                        ->view('filament.forms.components.chunked-import-upload')
                        ->helperText('Maksimal 50 MB (.xlsx / .csv). Unggahan diproses bertahap (chunked) sehingga tetap berjalan meskipun batas upload PHP 2–8 MB.')
                        ->required(),

                    Toggle::make('only_po_barang')
                        ->label('Hanya impor transaksi "PO Barang" (khusus file Mitraku)')
                        ->helperText('Otomatis diterapkan apabila file yang diunggah berformat ekspor Mitraku.')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $relativePath = ChunkedImportUploadController::normalizeOwnedRelativePath(
                        is_string($data['monitoring_file'] ?? null) ? $data['monitoring_file'] : null,
                        Auth::id()
                    );

                    if ($relativePath === null || ! Storage::disk('local')->exists($relativePath)) {
                        Notification::make()
                            ->title('File Belum Siap Diimpor')
                            ->body('Silakan unggah file Excel (.xlsx) atau CSV hingga selesai sebelum menekan tombol impor.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $filePath = Storage::disk('local')->path($relativePath);

                    try {
                        $monitoringService = app(MonitoringBarangImportService::class);
                        $mitrakuService = app(InvoicingCsvImportService::class);

                        if ($monitoringService->canHandleFile($filePath)) {
                            $summary = $monitoringService->importFromFile($filePath);

                            Notification::make()
                                ->title('Impor Monitoring / Invoice Barang Selesai')
                                ->body(
                                    "Diproses {$summary['total_rows']} baris: "
                                    ."{$summary['created']} baru, "
                                    ."{$summary['updated']} diperbarui, "
                                    ."{$summary['unchanged']} sama, "
                                    ."{$summary['linked_do']} tertaut DO, "
                                    ."{$summary['fallback_kasir_count']} fallback tgl kasir."
                                )
                                ->success()
                                ->send();

                            if (! empty($summary['incomplete_invoiced_rows'])) {
                                $sampleLines = collect($summary['incomplete_invoiced_rows'])
                                    ->take(8)
                                    ->map(fn (array $row): string => "• PO {$row['no_po']} (Inv: {$row['no_invoice']}): "
                                        .implode(', ', $row['missing_fields']).' kosong')
                                    ->implode('<br>');
                                $remaining = count($summary['incomplete_invoiced_rows']) - 8;
                                $moreNote = $remaining > 0 ? "<br>...dan {$remaining} baris ber-invoice lainnya." : '';

                                Notification::make()
                                    ->title('Invoice Selesai dengan Data/Tanggal Kosong ('.count($summary['incomplete_invoiced_rows']).' baris)')
                                    ->body("{$sampleLines}{$moreNote}")
                                    ->warning()
                                    ->persistent()
                                    ->send();
                            }

                            if (! empty($summary['unmatched_pics'])) {
                                Notification::make()
                                    ->title('Sebagian PIC Belum Terpetakan ke User')
                                    ->body('Daftar PIC berikut belum memiliki akun user yang cocok:<br>&bull; '.implode('<br>&bull; ', $summary['unmatched_pics']))
                                    ->warning()
                                    ->persistent()
                                    ->send();
                            }

                            return;
                        }

                        if ($mitrakuService->canHandleFile($filePath)) {
                            $onlyPoBarang = (bool) ($data['only_po_barang'] ?? true);
                            $summary = $mitrakuService->importFromFile($filePath, onlyPoBarang: $onlyPoBarang);

                            Notification::make()
                                ->title('Impor Invoice Mitraku Selesai')
                                ->body(
                                    "Hasil impor: {$summary['created']} baru, "
                                    ."{$summary['updated']} diperbarui, "
                                    ."{$summary['linked_do']} tertaut DO, "
                                    ."{$summary['skipped']} dilewati."
                                )
                                ->success()
                                ->send();

                            if (! empty($summary['unlinked_po'])) {
                                Notification::make()
                                    ->title('Sebagian PO Belum Tertaut ke Penerimaan DO')
                                    ->body(implode('<br>&bull; ', array_slice($summary['unlinked_po'], 0, 10)))
                                    ->warning()
                                    ->persistent()
                                    ->send();
                            }

                            return;
                        }

                        Notification::make()
                            ->title('Format File Tidak Dikenali')
                            ->body('Pastikan file Excel (.xlsx) atau CSV menggunakan format Monitoring Barang atau ekspor Mitraku yang valid.')
                            ->danger()
                            ->send();
                    } catch (\Throwable $e) {
                        report($e);
                        $errorMessage = trim($e->getMessage()) !== '' ? $e->getMessage() : class_basename($e);

                        Notification::make()
                            ->title('Gagal Mengimpor Invoice Barang')
                            ->body('Terjadi kesalahan: '.$errorMessage)
                            ->danger()
                            ->send();
                    } finally {
                        if (is_file($filePath)) {
                            @unlink($filePath);
                        }
                    }
                }),

            Action::make('batchUploadVerifikasiGrs')
                ->label('Upload Verifikasi GRS')
                ->icon(Heroicon::ArrowUpTray)
                ->color('info')
                ->modalHeading('Upload Massal Dokumen Verifikasi GRS (105)')
                ->modalDescription('Unggah berkas PDF Surat Verifikasi Penerimaan Barang (ACC VP) secara massal (hingga 100 berkas sekaligus). Sistem akan membaca Nomor GRS (105) atau Nomor PO, mencocokkan ke tagihan invoice barang, menyimpan berkas, dan mengubah status menjadi "Terverifikasi".')
                ->modalSubmitActionLabel('Unggah & Verifikasi Otomatis')
                ->form([
                    FileUpload::make('verifikasi_files')
                        ->label('Pilih Berkas PDF Verifikasi GRS (Bisa Drag & Drop Puluhan File Sekaligus)')
                        ->multiple()
                        ->maxFiles(100)
                        ->acceptedFileTypes(['application/pdf'])
                        ->storeFiles(false)
                        ->required()
                        ->helperText('Pilih satu atau hingga 100 berkas PDF Surat Verifikasi Penerimaan Barang dari Digital Office. Nomor GRS (105) akan diekstrak dan dicocokkan otomatis.'),
                ])
                ->action(function (array $data): void {
                    $rawFiles = $data['verifikasi_files'] ?? [];
                    $files = is_array($rawFiles) ? $rawFiles : [$rawFiles];
                    $files = array_filter($files);

                    if (empty($files)) {
                        Notification::make()
                            ->title('Tidak Ada Berkas Dipilih')
                            ->body('Silakan pilih setidaknya satu berkas PDF verifikasi GRS.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $parser = app(GrsPdfParserService::class);
                    $matched = [];
                    $unmatched = [];

                    foreach ($files as $file) {
                        $originalName = $file instanceof TemporaryUploadedFile
                            ? $file->getClientOriginalName()
                            : basename((string) $file);

                        $filePath = $file instanceof TemporaryUploadedFile
                            ? $file->getRealPath()
                            : (string) $file;

                        try {
                            $parsed = $parser->parseVerifikasiGrs($filePath, $originalName);
                            $noGrs = $parsed['no_grs'] ?? null;
                            $noPo = $parsed['no_po'] ?? null;
                            $noDo = $parsed['no_do'] ?? null;

                            $receipt = null;

                            // 1. Pencocokan utama: Nomor GRS (105)
                            if (! empty($noGrs)) {
                                $receipt = InvoicingReceipt::where('no_grs', $noGrs)->first();
                            }

                            // 2. Fallback pencocokan: Nomor PO + DO atau PO saja
                            if (! $receipt && ! empty($noPo)) {
                                $query = InvoicingReceipt::where('no_po', $noPo);
                                if (! empty($noDo)) {
                                    $query->where('no_do', $noDo);
                                }
                                $receipt = (clone $query)->where('status', 'Menunggu Verifikasi')->first() ?? $query->first();
                            }

                            if ($receipt) {
                                // Simpan file ke storage disk public pada folder verifikasi-grs
                                $storedPath = $file instanceof TemporaryUploadedFile
                                    ? $file->store('verifikasi-grs', 'public')
                                    : (is_string($file) ? $file : null);

                                if ($storedPath) {
                                    $receipt->dokumen_verifikasi_grs = $storedPath;
                                }

                                // Ubah status menjadi Terverifikasi jika sebelumnya Menunggu Verifikasi
                                if ($receipt->status === 'Menunggu Verifikasi') {
                                    $receipt->status = 'Terverifikasi';
                                }

                                // Lengkapi data yang masih kosong jika berhasil diekstrak
                                if (empty($receipt->no_grs) && ! empty($noGrs)) {
                                    $receipt->no_grs = $noGrs;
                                }
                                if (empty($receipt->no_do) && ! empty($noDo)) {
                                    $receipt->no_do = $noDo;
                                }
                                if (empty($receipt->vendor_name) && ! empty($parsed['vendor_name'])) {
                                    $receipt->vendor_name = $parsed['vendor_name'];
                                }
                                if (empty($receipt->tanggal_terima_grs) && ! empty($parsed['tanggal_release_gr'])) {
                                    $receipt->tanggal_terima_grs = $parsed['tanggal_release_gr'];
                                }

                                $receipt->save();

                                $matched[] = [
                                    'file' => $originalName,
                                    'no_grs' => $receipt->no_grs ?: $noGrs,
                                    'no_po' => $receipt->no_po,
                                    'status' => $receipt->status,
                                    'source' => $parsed['detection_source'],
                                ];
                            } else {
                                $reason = empty($noGrs) && empty($noPo)
                                    ? 'Kode GRS (105) maupun PO tidak terdeteksi dari teks maupun nama file'
                                    : 'Tidak ditemukan Invoice Barang dengan GRS: '.($noGrs ?: '-').' / PO: '.($noPo ?: '-');

                                $unmatched[] = [
                                    'file' => $originalName,
                                    'no_grs' => $noGrs,
                                    'no_po' => $noPo,
                                    'reason' => $reason,
                                ];
                            }
                        } catch (\Throwable $e) {
                            report($e);
                            $unmatched[] = [
                                'file' => $originalName,
                                'no_grs' => null,
                                'no_po' => null,
                                'reason' => 'Error: '.$e->getMessage(),
                            ];
                        }
                    }

                    $totalFiles = count($files);
                    $totalMatched = count($matched);
                    $totalUnmatched = count($unmatched);

                    if ($totalMatched > 0 && $totalUnmatched === 0) {
                        Notification::make()
                            ->title("Semua Berkas Terverifikasi ({$totalMatched} Dokumen)")
                            ->body("Seluruh <b>{$totalMatched}</b> berkas verifikasi GRS berhasil dicocokkan dan status tagihan diubah menjadi <b>Terverifikasi</b>.")
                            ->success()
                            ->send();
                    } elseif ($totalMatched > 0 && $totalUnmatched > 0) {
                        $sampleUnmatched = collect($unmatched)
                            ->take(5)
                            ->map(fn ($u) => "• <b>{$u['file']}</b>: {$u['reason']}")
                            ->implode('<br>');
                        $rem = $totalUnmatched - 5;
                        if ($rem > 0) {
                            $sampleUnmatched .= "<br>...dan {$rem} berkas lainnya.";
                        }

                        Notification::make()
                            ->title("Verifikasi Batch Selesai ({$totalMatched} Berhasil, {$totalUnmatched} Tidak Cocok)")
                            ->body("<b>{$totalMatched} dari {$totalFiles}</b> dokumen berhasil diverifikasi.<br><br><b>Dokumen belum cocok:</b><br>{$sampleUnmatched}")
                            ->warning()
                            ->persistent()
                            ->send();
                    } else {
                        $sampleUnmatched = collect($unmatched)
                            ->take(5)
                            ->map(fn ($u) => "• <b>{$u['file']}</b>: {$u['reason']}")
                            ->implode('<br>');

                        Notification::make()
                            ->title("Tidak Ada Berkas yang Cocok ({$totalUnmatched} Dokumen Gagal)")
                            ->body("Pencocokan gagal untuk berkas berikut:<br>{$sampleUnmatched}")
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            CreateAction::make()
                ->label('Tambah Tagihan Baru')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InvoicingOverviewWidget::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Dokumen')
                ->icon('heroicon-m-document-text')
                ->badge(InvoicingReceipt::count()),

            'my_tasks' => Tab::make('Tugas Saya')
                ->icon('heroicon-m-user')
                ->modifyQueryUsing(fn (Builder $query): Builder => $this->applyMyTasksFilter($query))
                ->badge($this->applyMyTasksFilter(InvoicingReceipt::query())->count())
                ->badgeColor('primary'),

            'free_pool' => Tab::make('Bebas (Belum Ada PIC)')
                ->icon('heroicon-m-user-group')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('pic_id'))
                ->badge(InvoicingReceipt::whereNull('pic_id')->count())
                ->badgeColor('warning'),

            'waiting_verification' => Tab::make('Menunggu Verifikasi')
                ->icon('heroicon-m-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Menunggu Verifikasi'))
                ->badge(InvoicingReceipt::where('status', 'Menunggu Verifikasi')->count())
                ->badgeColor('danger'),

            'verified' => Tab::make('Terverifikasi')
                ->icon('heroicon-m-check-badge')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Terverifikasi'))
                ->badge(InvoicingReceipt::where('status', 'Terverifikasi')->count())
                ->badgeColor('info'),

            'sent_cashier' => Tab::make('Terkirim Kasir')
                ->icon('heroicon-m-paper-airplane')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Terkirim Kasir'))
                ->badge(InvoicingReceipt::where('status', 'Terkirim Kasir')->count())
                ->badgeColor('success'),

            'selesai' => Tab::make('Selesai')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Selesai'))
                ->badge(InvoicingReceipt::where('status', 'Selesai')->count())
                ->badgeColor('success'),
        ];
    }

    /**
     * Terapkan filter tugas aktif untuk tab "Tugas Saya" (tidak termasuk Terkirim Kasir & Selesai).
     */
    private function applyMyTasksFilter(Builder $query): Builder
    {
        $userId = Auth::id();
        if ($userId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('pic_id', $userId)
            ->whereIn('status', ['Menunggu Verifikasi', 'Terverifikasi']);
    }
}
