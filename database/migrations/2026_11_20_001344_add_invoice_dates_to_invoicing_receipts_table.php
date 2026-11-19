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
            $table->date('tanggal_invoice')->nullable()->after('no_invoice');
            $table->date('tanggal_faktur_pajak')->nullable()->after('faktur_pajak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoicing_receipts', function (Blueprint $table) {
            $table->dropColumn(['tanggal_invoice', 'tanggal_faktur_pajak']);
        });
    }
};
