<?php

namespace App\Console\Commands;

use App\Models\InvoicingReceipt;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('invoicing:sync-descriptions')]
#[Description('Sinkronisasi format deskripsi bernomor untuk semua dokumen invoice yang memiliki banyak item')]
class SyncInvoicingDescriptionsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai sinkronisasi deskripsi invoice...');

        $updatedCount = 0;
        InvoicingReceipt::query()->with(['items', 'purchaseOrderIssued'])->chunk(100, function ($receipts) use (&$updatedCount): void {
            foreach ($receipts as $receipt) {
                $oldDesc = $receipt->deskripsi;
                $receipt->syncSummaryFromItems();
                if ($receipt->deskripsi !== $oldDesc) {
                    $receipt->saveQuietly();
                    $updatedCount++;
                }
            }
        });

        $this->info("Sinkronisasi selesai! {$updatedCount} invoice berhasil diperbarui format deskripsinya.");

        return self::SUCCESS;
    }
}
