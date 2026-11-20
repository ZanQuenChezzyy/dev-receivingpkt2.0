<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('invoicing_receipts')) {
            if (! Schema::hasColumn('invoicing_receipts', 'dokumen_grs')) {
                Schema::table('invoicing_receipts', function (Blueprint $table) {
                    $table->string('dokumen_grs')->nullable()->after('dokumen_verifikasi_grs');
                });
            }

            // 1. Backfill dokumen_grs dari grs_rdtv_items jika ada
            if (Schema::hasTable('grs_rdtv_items')) {
                DB::table('invoicing_receipts')
                    ->join('grs_rdtv_items', 'invoicing_receipts.grs_rdtv_item_id', '=', 'grs_rdtv_items.id')
                    ->whereNull('invoicing_receipts.dokumen_grs')
                    ->whereNotNull('grs_rdtv_items.file_path')
                    ->update([
                        'invoicing_receipts.dokumen_grs' => DB::raw('grs_rdtv_items.file_path'),
                    ]);
            }

            // 2. Pindahkan berkas slip GRS yang sebelumnya sempat tersimpan di dokumen_verifikasi_grs ke dokumen_grs
            DB::table('invoicing_receipts')
                ->where(function ($q) {
                    $q->where('dokumen_verifikasi_grs', 'like', 'grs-rdtv%')
                        ->orWhere('dokumen_verifikasi_grs', 'like', '%grs-rdtv/%')
                        ->orWhere('dokumen_verifikasi_grs', 'like', '%grs-rdtv-items%');
                })
                ->update([
                    'dokumen_grs' => DB::raw('COALESCE(dokumen_grs, dokumen_verifikasi_grs)'),
                    'dokumen_verifikasi_grs' => null,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('invoicing_receipts') && Schema::hasColumn('invoicing_receipts', 'dokumen_grs')) {
            Schema::table('invoicing_receipts', function (Blueprint $table) {
                $table->dropColumn('dokumen_grs');
            });
        }
    }
};
