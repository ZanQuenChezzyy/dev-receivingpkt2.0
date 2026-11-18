<?php

namespace App\Filament\Resources\InvoicingReceipts\Pages;

use App\Filament\Resources\InvoicingReceipts\InvoicingReceiptResource;
use App\Filament\Resources\InvoicingReceipts\Widgets\InvoicingOverviewWidget;
use App\Http\Controllers\Admin\ChunkedImportUploadController;
use App\Models\InvoicingReceipt;
use App\Services\InvoicingCsvImportService;
use App\Services\MonitoringBarangImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
