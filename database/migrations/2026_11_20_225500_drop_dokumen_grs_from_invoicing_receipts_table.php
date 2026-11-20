<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('invoicing_receipts') && Schema::hasColumn('invoicing_receipts', 'dokumen_grs')) {
            Schema::table('invoicing_receipts', function (Blueprint $table) {
                $table->dropColumn('dokumen_grs');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('invoicing_receipts') && ! Schema::hasColumn('invoicing_receipts', 'dokumen_grs')) {
            Schema::table('invoicing_receipts', function (Blueprint $table) {
                $table->string('dokumen_grs')->nullable()->after('dokumen_verifikasi_grs');
            });
        }
    }
};
