<?php

namespace App\Filament\Actions;

use App\Http\Controllers\Admin\ChunkedImportUploadController;
use App\Services\NonStockAgingImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ImportNonStockAgingAction
{
    public static function make(string $name = 'importNonStockMonitoring'): Action
    {
        return Action::make($name)
            ->label('Impor Excel Monitoring / GRS')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->modalHeading('Impor Data Monitoring Aging Non-Stock & PD')
            ->modalDescription('Unggah file Excel penuh (.xlsx) atau CSV (.csv). Sistem memproses file secara bertahap (chunked upload + streaming) sehingga aman untuk file berukuran besar hingga 50 MB.')
            ->modalSubmitActionLabel('Mulai Impor')
            ->modalWidth('xl')
            ->schema([
                ViewField::make('monitoring_file')
                    ->label('File Excel (.xlsx) atau CSV')
                    ->view('filament.forms.components.chunked-import-upload')
                    ->helperText('Mendukung workbook penuh dengan tab MONITORING, GRS, dan R02 maupun file CSV/MB51 tunggal. Maksimal 50 MB.')
                    ->required(),
                Radio::make('import_scope')
                    ->label('Cakupan Data yang Diimpor')
                    ->options([
                        'active_only' => 'Hanya status aktif (Belum Diambil, On Check, Scheduled Hold) — lebih cepat untuk baseline awal',
                        'all' => 'Semua status (termasuk Diambil & Dipindah) — untuk menyimpan seluruh riwayat & kontak PIC',
                    ])
                    ->default('active_only')
                    ->required(),
                Toggle::make('sync_web_do_after')
                    ->label('Sinkronkan transaksi DO & MIR Web 2.0 setelah impor')
                    ->helperText('Otomatis menautkan data penerimaan DO terbaru dan menutup item yang sudah terbit MIR penuh.')
                    ->default(true),
            ])
            ->action(function (array $data, NonStockAgingImportService $importService): void {
                $userId = Auth::id();
                $relativePath = ChunkedImportUploadController::normalizeOwnedRelativePath(
                    is_string($data['monitoring_file'] ?? null) ? $data['monitoring_file'] : null,
                    $userId !== null ? (int) $userId : null,
                );

                if ($relativePath === null) {
                    Notification::make()
                        ->title('File unggahan tidak valid')
                        ->body('Silakan unggah ulang file Excel atau CSV sebelum memulai impor.')
                        ->danger()
                        ->send();

                    return;
                }

                $disk = Storage::disk('local');
                if (! $disk->exists($relativePath)) {
                    Notification::make()
                        ->title('File sementara tidak ditemukan')
                        ->body('File mungkin sudah kedaluwarsa. Silakan unggah ulang file Anda.')
                        ->danger()
                        ->send();

                    return;
                }

                $absolutePath = $disk->path($relativePath);

                try {
                    $result = $importService->import($absolutePath, [
                        'only_active_statuses' => ($data['import_scope'] ?? 'active_only') === 'active_only',
                        'dry_run' => false,
                        'imported_by' => $userId !== null ? (int) $userId : null,
                        'sync_web_do_after' => (bool) ($data['sync_web_do_after'] ?? true),
                    ]);

                    $bodyLines = [
                        "Dipindai: {$result['rows_scanned']} baris | Baru: {$result['created']} | Diperbarui: {$result['updated']} | Dilewati: {$result['skipped']}.",
                        "Cocok PO DB: {$result['matched_db_po']} | Cocok DO Web: {$result['matched_db_do']} | Unit Terpetakan: {$result['matched_unit']} | Kontak PIC: {$result['contacts_extracted']}.",
                    ];

                    if ($result['foreign_currency_unconverted_count'] > 0) {
                        $bodyLines[] = "Catatan: {$result['foreign_currency_unconverted_count']} item bermata uang asing tanpa nilai LC IDR tidak dijumlahkan mentah.";
                    }

                    Notification::make()
                        ->title('Impor Monitoring Aging selesai')
                        ->body(implode(' ', $bodyLines))
                        ->success()
                        ->persistent()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Impor gagal diproses')
                        ->body($e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();
                } finally {
                    $disk->delete($relativePath);
                }
            });
    }
}
