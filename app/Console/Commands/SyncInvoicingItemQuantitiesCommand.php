<?php

namespace App\Console\Commands;

use App\Models\InvoicingReceipt;
use App\Models\PurchaseOrderIssued;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('invoicing:sync-quantities {--force : Perbarui semua item tanpa memeriksa apakah kuantitas duplikat}')]
#[Description('Sinkronisasi quantity item invoice dengan PO terbit berdasarkan kecocokan deskripsi barang')]
class SyncInvoicingItemQuantitiesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai sinkronisasi quantity item invoice berdasarkan PO terbit...');
        $force = (bool) $this->option('force');

        $totalInvoices = 0;
        $updatedInvoices = 0;
        $updatedItems = 0;

        $query = InvoicingReceipt::query()
            ->whereNotNull('no_po')
            ->where('no_po', '!=', '-')
            ->where('no_po', '!=', '')
            ->has('items')
            ->with(['items']);

        $count = $query->count();
        $this->info("Ditemukan {$count} invoice yang memiliki rincian item barang.");

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $query->chunk(100, function ($invoices) use ($force, &$totalInvoices, &$updatedInvoices, &$updatedItems, $progressBar): void {
            foreach ($invoices as $invoice) {
                $totalInvoices++;
                $pos = PurchaseOrderIssued::query()
                    ->where('purchase_order_no', trim((string) $invoice->no_po))
                    ->orderBy('item_no')
                    ->get();

                if ($pos->isEmpty()) {
                    $progressBar->advance();

                    continue;
                }

                $items = $invoice->items->sortBy('item_no')->values();
                $itemQtys = $items->pluck('qty_penagihan')->filter(fn ($q) => $q !== null)->unique();
                $poQtys = $pos->pluck('qty_po')->unique();

                // Deteksi apakah invoice mengalami masalah kuantitas seragam (duplikat item pertama)
                $hasUniformDuplicateQty = ($items->count() > 1 && $itemQtys->count() === 1 && $poQtys->count() > 1);

                $invoiceModified = false;

                foreach ($items as $seq => $item) {
                    $matchedPo = PurchaseOrderIssued::matchByDescription($item->nama_barang, $pos)
                        ?? ($pos[$seq] ?? null);

                    if (! $matchedPo) {
                        continue;
                    }

                    $itemDirty = false;

                    // 1. Sinkronisasi Qty PO
                    if ($item->qty_po === null || (float) $item->qty_po !== (float) $matchedPo->qty_po) {
                        $item->qty_po = (float) $matchedPo->qty_po;
                        $itemDirty = true;
                    }

                    // 2. Sinkronisasi Qty Penagihan jika seragam / force / null
                    if ($force || $hasUniformDuplicateQty || $item->qty_penagihan === null || (float) $item->qty_penagihan == 0.0) {
                        if ((float) $item->qty_penagihan !== (float) $matchedPo->qty_po) {
                            $item->qty_penagihan = (float) $matchedPo->qty_po;
                            $itemDirty = true;
                        }
                    }

                    // 3. Sinkronisasi relasi PO & item_no
                    if ($item->purchase_order_issued_id !== $matchedPo->id) {
                        $item->purchase_order_issued_id = $matchedPo->id;
                        $itemDirty = true;
                    }

                    if ($item->item_no === null || $item->item_no <= 0 || ($matchedPo->item_no && $item->item_no !== (int) $matchedPo->item_no && $items->count() === $pos->count())) {
                        $item->item_no = (int) $matchedPo->item_no;
                        $itemDirty = true;
                    }

                    // 4. Sinkronisasi UOI & Material Code jika kosong
                    if (blank($item->uoi) && filled($matchedPo->uoi)) {
                        $item->uoi = $matchedPo->uoi;
                        $itemDirty = true;
                    }

                    if (blank($item->material_code) && filled($matchedPo->material_code)) {
                        $item->material_code = $matchedPo->material_code;
                        $itemDirty = true;
                    }

                    if ($itemDirty) {
                        $item->saveQuietly();
                        $updatedItems++;
                        $invoiceModified = true;
                    }
                }

                if ($invoiceModified) {
                    $invoice->syncSummaryFromItems();
                    $invoice->saveQuietly();
                    $updatedInvoices++;
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        $this->info('Sinkronisasi kuantitas selesai!');
        $this->info("- Total invoice diproses: {$totalInvoices}");
        $this->info("- Total invoice diperbarui: {$updatedInvoices}");
        $this->info("- Total item barang disesuaikan: {$updatedItems}");

        return self::SUCCESS;
    }
}
