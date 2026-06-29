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
        Schema::table('delivery_order_receipts', function (Blueprint $table) {
            $table->decimal('termin_percentage', 5, 2)->nullable()->comment('Menyimpan persentase termin (contoh: 15.50)')->after('receipt_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_order_receipts', function (Blueprint $table) {
            $table->dropColumn('termin_percentage');
        });
    }
};
