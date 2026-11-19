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
        Schema::table('invoicing_receipts', function (Blueprint $table) {
            $table->date('delivery_date')->nullable()->after('tanggal_terima_grs');
            $table->date('tanggal_penerimaan')->nullable()->after('delivery_date');
            $table->date('tanggal_po')->nullable()->after('no_po');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoicing_receipts', function (Blueprint $table) {
            $table->dropColumn(['delivery_date', 'tanggal_penerimaan', 'tanggal_po']);
        });
    }
};
