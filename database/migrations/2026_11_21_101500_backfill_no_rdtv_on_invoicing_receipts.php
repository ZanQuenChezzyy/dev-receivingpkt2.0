<?php

use App\Services\GrsPdfParserService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('invoicing_receipts')) {
            return;
        }

        $rdtvReceipts = DB::table('invoicing_receipts')
            ->where('jenis', 'RDTV')
            ->where(function ($query) {
                $query->whereNull('no_rdtv')
                    ->orWhere('no_rdtv', '')
                    ->orWhere('no_rdtv', '-');
            })
            ->get();

        $parser = app(GrsPdfParserService::class);

        foreach ($rdtvReceipts as $receipt) {
            $filePath = $receipt->dokumen_verifikasi_grs;

            // Jika kosong di dokumen_verifikasi_grs, coba ambil dari grs_rdtv_items jika ada
            if (empty($filePath) && ! empty($receipt->grs_rdtv_item_id) && Schema::hasTable('grs_rdtv_items')) {
                $filePath = DB::table('grs_rdtv_items')
                    ->where('id', $receipt->grs_rdtv_item_id)
                    ->value('file_path');
            }

            if (! empty($filePath) && Storage::disk('public')->exists($filePath)) {
                try {
                    $fullPath = Storage::disk('public')->path($filePath);
                    $parsed = $parser->parse($fullPath);
                    if (! empty($parsed['no_rdtv'])) {
                        DB::table('invoicing_receipts')
                            ->where('id', $receipt->id)
                            ->update([
                                'no_rdtv' => $parsed['no_rdtv'],
                                'updated_at' => now(),
                            ]);
                    }
                } catch (Throwable $e) {
                    // Abaikan berkas rusak
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration needed for data backfill.
    }
};
