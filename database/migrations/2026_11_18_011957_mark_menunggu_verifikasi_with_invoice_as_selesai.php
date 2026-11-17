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
            ->where(function ($query): void {
                $query->whereNull('status')
                    ->orWhereRaw("TRIM(status) = ''")
                    ->orWhere('status', 'Menunggu Verifikasi');
            })
            ->whereNotNull('no_invoice')
            ->whereRaw("TRIM(no_invoice) != ''")
            ->whereRaw("LOWER(TRIM(no_invoice)) NOT IN ('-', 'null', 'none', 'kosong')")
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
        // Perubahan status ke Selesai untuk data ber-invoice tidak di-rollback agar data tetap konsisten.
    }
};
