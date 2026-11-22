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
            $table->foreignId('cashier_transmittal_id')
                ->nullable()
                ->after('grs_rdtv_item_id')
                ->constrained('cashier_transmittals')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoicing_receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cashier_transmittal_id');
        });
    }
};
