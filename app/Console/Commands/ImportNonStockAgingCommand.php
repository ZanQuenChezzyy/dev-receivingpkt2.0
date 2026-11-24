<?php

namespace App\Console\Commands;

use App\Services\NonStockAgingImportService;
use Illuminate\Console\Command;

class ImportNonStockAgingCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'nonstock:import-monitoring
                            {path : Path lengkap file Excel (.xlsx) atau CSV (.csv) Monitoring PD & Non-Stock}
                            {--active-only : Hanya mengimpor item berstatus aktif (Belum Diambil, On Check, Scheduled Hold)}
                            {--dry-run : Simulasi impor tanpa menulis perubahan ke database}
                            {--sync-web : Jalankan sinkronisasi transaksi DO Web 2.0 setelah impor selesai}
                            {--replace-baseline : Hapus data lama di luar file ini yang belum memiliki tindak lanjut manual}';

    /**
     * @var string
     */
    protected $description = 'Mengimpor data Monitoring & GRS Material Non-Stock/PD (XLSX penuh atau CSV) secara streaming hemat memori.';

    public function handle(NonStockAgingImportService $importService): int
    {
        $path = (string) $this->argument('path');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $activeOnly = (bool) $this->option('active-only');
        $syncWeb = (bool) $this->option('sync-web');
        $replaceBaseline = (bool) $this->option('replace-baseline');

        $this->info(($dryRun ? '[DRY RUN] ' : '')."Memproses file: {$path}");

        try {
            $result = $importService->import($path, [
                'only_active_statuses' => $activeOnly,
                'dry_run' => $dryRun,
                'sync_web_do_after' => $syncWeb,
                'replace_baseline' => $replaceBaseline,
            ]);
        } catch (\Throwable $e) {
            $this->error('Impor gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Mode Impor', $result['mode']],
                ['Worksheet Terdeteksi', empty($result['sheets_detected']) ? '-' : implode(', ', $result['sheets_detected'])],
                ['Baris PO-Item Dipindai', (string) $result['rows_scanned']],
                ['Data Baru Dibuat', (string) $result['created']],
                ['Data Diperbarui', (string) $result['updated']],
                ['Data Usang Dihapus', (string) ($result['deleted_stale'] ?? 0)],
                ['Dilewati (Filter Status)', (string) $result['skipped']],
                ['Cocok PO Terbit (DB)', (string) $result['matched_db_po']],
                ['Cocok DO Web 2.0 (DB)', (string) $result['matched_db_do']],
                ['Terpetakan Unit Peminta', (string) $result['matched_unit']],
                ['Kontak PIC Terekstrak', (string) $result['contacts_extracted']],
                ['Tanpa Tanggal GRS', (string) $result['missing_grs_date_count']],
                ['Mata Uang Asing Belum Konversi', (string) $result['foreign_currency_unconverted_count']],
            ]
        );

        if (! empty($result['status_counts'])) {
            $statusRows = [];
            foreach ($result['status_counts'] as $status => $count) {
                $statusRows[] = [$status, (string) $count];
            }
            $this->table(['Status Operasional', 'Jumlah Item'], $statusRows);
        }

        $this->info($dryRun ? 'Simulasi impor selesai tanpa mengubah database.' : 'Impor data Aging Material Non-Stock & PD selesai.');

        return self::SUCCESS;
    }
}
