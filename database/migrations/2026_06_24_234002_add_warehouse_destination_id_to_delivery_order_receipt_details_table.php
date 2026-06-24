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
        Schema::table('delivery_order_receipt_details', function (Blueprint $table) {
            $table->foreignId('warehouse_destination_id')->nullable()->constrained('warehouse_destinations', 'id', 'dord_wd_id_fk')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_order_receipt_details', function (Blueprint $table) {
            $table->dropForeign('dord_wd_id_fk');
            $table->dropColumn('warehouse_destination_id');
        });
    }
};
