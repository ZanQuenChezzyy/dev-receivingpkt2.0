<?php

namespace App\Console\Commands;

use App\Models\InvoicingReceipt;
use App\Services\GrsPdfParserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackfillInvoicingGrsDeliveryDateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoicing:backfill-grs-delivery-date
                            {--dry-run : Simulasi tanpa menyimpan perubahan ke database}
                            {--force : Perbarui ulang delivery_date meski sudah terisi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ekstrak ulang delivery_date dari file PDF GRS/RDTV yang sudah terunggah ke Invoice Barang.';

    public function __construct(public GrsPdfParserService $parser)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $query = InvoicingReceipt::query()
            ->with('grsRdtvItem')
            ->whereNotNull('grs_rdtv_item_id')
            ->when(! $force, fn ($q) => $q->whereNull('delivery_date'));

        $total = $query->count();
        $this->info("Memeriksa {$total} data Invoice Barang yang terhubung ke berkas GRS/RDTV...");

        $updated = 0;
        $skippedMissingFile = 0;
        $skippedNoDate = 0;
        $processed = 0;

        $query->chunkById(100, function ($receipts) use ($dryRun, $force, &$updated, &$skippedMissingFile, &$skippedNoDate, &$processed): void {
            foreach ($receipts as $receipt) {
                $processed++;
                $path = $receipt->grsRdtvItem?->file_path;
                if (blank($path) || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'pdf' || ! Storage::disk('public')->exists($path)) {
                    $skippedMissingFile++;

                    continue;
                }

                $fullPath = Storage::disk('public')->path($path);
                if (! is_file($fullPath) || filesize($fullPath) > 5 * 1024 * 1024) {
                    $skippedNoDate++;

                    continue;
                }

                try {
                    $parsed = $this->parser->parse($fullPath);
                } catch (\Throwable $e) {
                    report($e);
                    $skippedNoDate++;

                    continue;
                }

                $deliveryDate = $parsed['delivery_date'] ?? null;
                if (blank($deliveryDate)) {
                    $skippedNoDate++;

                    continue;
                }

                $updates = [];
                if ($force || blank($receipt->delivery_date)) {
                    $updates['delivery_date'] = $deliveryDate;
                }
                if (blank($receipt->tanggal_penerimaan) && filled($parsed['tanggal_penerimaan'] ?? null)) {
                    $updates['tanggal_penerimaan'] = $parsed['tanggal_penerimaan'];
                }
                if (blank($receipt->tanggal_po) && filled($parsed['tanggal_po'] ?? null)) {
                    $updates['tanggal_po'] = $parsed['tanggal_po'];
                }

                if ($updates !== []) {
                    if (! $dryRun) {
                        $receipt->updateQuietly($updates);
                    }
                    $updated++;
                }
            }

            $this->line("  Progres: {$processed} diproses (diperbarui: {$updated}, tanpa tanggal: {$skippedNoDate}, hilang: {$skippedMissingFile})");
        });

        $modeLabel = $dryRun ? '[DRY-RUN] ' : '';
        $this->info("{$modeLabel}Selesai. Diperbarui: {$updated}, File tidak ditemukan: {$skippedMissingFile}, PDF tanpa Delivery Date: {$skippedNoDate}.");

        return self::SUCCESS;
    }
}
