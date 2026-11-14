<?php

namespace App\Console\Commands;

use App\Services\MonitoringKarungImportService;
use Illuminate\Console\Command;

class ImportMonitoringKarungCsvCommand extends Command
{
    protected $signature = 'karung:import {file : Path file CSV data monitoring karung}';

    protected $description = 'Import data monitoring karung dari file CSV ke database';

    public function handle(MonitoringKarungImportService $service): int
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");

            return self::FAILURE;
        }

        $this->info("Memulai import data monitoring karung dari: {$filePath}");

        try {
            $result = $service->import($filePath, true);

            $this->info('Import selesai dengan sukses!');
            $this->line("- Total PO Terproses: {$result['imported_pos']}");
            $this->line("- Total Item QC: {$result['imported_qc_items']}");
            $this->line("- Total Item Pengiriman: {$result['imported_delivery_items']}");
            $this->line('- Total DO Receipt Tersinkron: '.($result['synced_do_count'] ?? 0));
            $this->line('- Total DO Receipt Tersinkron: '.($result['synced_do_count'] ?? 0));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Gagal melakukan import: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
