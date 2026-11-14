<?php

namespace App\Filament\Resources\MonitoringKarungs\Pages;

use App\Filament\Resources\MonitoringKarungs\MonitoringKarungResource;
use App\Models\MonitoringKarung;
use App\Services\MonitoringKarungImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ListMonitoringKarungs extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = MonitoringKarungResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('+ Buat Monitoring PO Karung'),

            Action::make('importSpreadsheet')
                ->label('Import Excel / CSV Monitoring')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Import Data Monitoring Karung dari Excel (.xlsx) / CSV')
                ->modalDescription('Unggah file Excel (.xlsx) atau CSV dari spreadsheet rekap pengujian QC dan pengiriman karung untuk memproses dan memperbarui data secara otomatis.')
                ->form([
                    FileUpload::make('spreadsheet_file')
                        ->label('File Excel (.xlsx) atau CSV Rekap Karung')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                            'text/plain',
                            'text/comma-separated-values',
                        ])
                        ->disk('local')
                        ->directory('temp-karung-imports')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $filePath = Storage::disk('local')->path($data['spreadsheet_file']);

                    try {
                        $service = app(MonitoringKarungImportService::class);
                        $result = $service->import($filePath, true);

                        $syncedText = isset($result['synced_do_count']) && $result['synced_do_count'] > 0
                            ? ", serta otomatis menghubungkan {$result['synced_do_count']} DO Receipt!"
                            : '.';

                        Notification::make()
                            ->title('Import Spreadsheet Karung Berhasil!')
                            ->body("Berhasil memproses {$result['imported_pos']} PO, {$result['imported_qc_items']} tahapan QC, {$result['imported_delivery_items']} tahapan pengiriman{$syncedText}")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Gagal Mengimpor Data')
                            ->body('Terjadi kesalahan saat memproses data: '.$e->getMessage())
                            ->danger()
                            ->send();
                    } finally {
                        if (file_exists($filePath)) {
                            @unlink($filePath);
                        }
                    }
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua PO')
                ->badge(MonitoringKarung::count()),

            'aktif' => Tab::make('Sedang Berjalan')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Aktif'))
                ->badge(MonitoringKarung::where('status', 'Aktif')->count())
                ->badgeColor('primary'),

            'belum_sa' => Tab::make('Ada Belum SA SAP')
                ->modifyQueryUsing(function (Builder $query) {
                    $query->whereHas('qcItems', function (Builder $q) {
                        $q->whereNull('no_sa')->orWhere('no_sa', '')->orWhere('status_qc', 'BLM SA');
                    });
                })
                ->badge(function () {
                    return MonitoringKarung::whereHas('qcItems', function (Builder $q) {
                        $q->whereNull('no_sa')->orWhere('no_sa', '')->orWhere('status_qc', 'BLM SA');
                    })->count();
                })
                ->badgeColor('danger'),

            'selesai' => Tab::make('Selesai')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Selesai'))
                ->badge(MonitoringKarung::where('status', 'Selesai')->count())
                ->badgeColor('success'),
        ];
    }
}
