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
        if (! Schema::hasTable('invoicing_receipts')) {
            return;
        }

        DB::table('invoicing_receipts')
            ->where('jenis', 'RDTV')
            ->whereNotNull('tanggal_info_grs')
            ->where(function ($query): void {
                $query->whereNull('status')
                    ->orWhere('status', '!=', 'Selesai');
            })
            ->update([
                'status' => 'Selesai',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Normalisasi status RDTV selesai tidak di-rollback agar data tetap konsisten.
    }
};
