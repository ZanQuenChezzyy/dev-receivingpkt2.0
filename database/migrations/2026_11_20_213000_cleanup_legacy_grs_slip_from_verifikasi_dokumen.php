<?php

use Illuminate\Database\Migrations\Migration;
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
            // Bersihkan data legacy di mana dokumen_verifikasi_grs terisi path berkas slip GRS gudang (bukan surat verifikasi resmi)
            DB::table('invoicing_receipts')
                ->whereNotNull('dokumen_verifikasi_grs')
                ->where(function ($q) {
                    $q->where('dokumen_verifikasi_grs', 'like', 'grs-rdtv%')
                        ->orWhere('dokumen_verifikasi_grs', 'like', '%grs-rdtv/%')
                        ->orWhere('dokumen_verifikasi_grs', 'like', '%grs-rdtv-items%');
                })
                ->update(['dokumen_verifikasi_grs' => null]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu reverse
    }
};
