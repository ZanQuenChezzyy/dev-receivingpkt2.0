<?php

namespace App\Console\Commands;

use App\Services\MonitoringBarangImportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('invoicing:import-monitoring {file : Path file Excel (.xlsx) atau CSV Monitoring Barang}')]
#[Description('Import data Monitoring Barang (.xlsx / .csv) ke tabel invoicing_receipts dengan pemetaan PIC dan aturan status otomatis')]
class ImportMonitoringBarangCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(MonitoringBarangImportService $service): int
    {
        $filePath = (string) $this->argument('file');

        if (! is_file($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");

            return self::FAILURE;
        }

        $this->info("Memulai impor Monitoring Barang dari: {$filePath}");

        try {
            $summary = $service->importFromFile($filePath);

            $this->info('Impor Monitoring Barang selesai!');
            $this->line("- Total Baris Diproses : {$summary['total_rows']}");
            $this->line("- Data Baru Dibuat     : {$summary['created']}");
            $this->line("- Data Diperbarui      : {$summary['updated']}");
            $this->line("- Data Tidak Berubah   : {$summary['unchanged']}");
            $this->line("- Baris Kosong/Dilewati: {$summary['skipped']}");
            $this->line("- Tertaut ke DO        : {$summary['linked_do']}");
            $this->line("- Fallback Tgl Kasir   : {$summary['fallback_kasir_count']}");

            if (! empty($summary['incomplete_invoiced_rows'])) {
                $this->newLine();
                $this->warn('Peringatan: Terdapat '.count($summary['incomplete_invoiced_rows']).' data ber-invoice (Status: Selesai) dengan tanggal/kolom yang masih kosong:');
                foreach (array_slice($summary['incomplete_invoiced_rows'], 0, 20) as $row) {
                    $missingText = implode(', ', $row['missing_fields']);
                    $this->line("  * Baris {$row['row_number']} | PO: {$row['no_po']} | Inv: {$row['no_invoice']} -> {$missingText}");
                }
                if (count($summary['incomplete_invoiced_rows']) > 20) {
                    $remaining = count($summary['incomplete_invoiced_rows']) - 20;
                    $this->line("  ...dan {$remaining} baris ber-invoice lainnya.");
                }
            }

            if (! empty($summary['unmatched_pics'])) {
                $this->newLine();
                $this->warn('PIC berikut belum terpetakan ke akun user di database:');
                foreach ($summary['unmatched_pics'] as $picInfo) {
                    $this->line("  * {$picInfo}");
                }
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Gagal melakukan impor: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
