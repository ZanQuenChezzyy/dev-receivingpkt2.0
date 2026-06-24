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
        Schema::create('warehouse_transmittal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_transmittal_id')->constrained('warehouse_transmittals', 'id', 'wt_items_wt_id_fk')->onDelete('cascade');
            $table->foreignId('delivery_order_receipt_detail_id')->constrained('delivery_order_receipt_details', 'id', 'wt_items_dord_id_fk')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_transmittal_items');
    }
};
