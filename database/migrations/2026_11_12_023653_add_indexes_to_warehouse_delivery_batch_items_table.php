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
        Schema::table('warehouse_delivery_batch_items', function (Blueprint $table) {
            $table->index('po_number');
            $table->index('status');
            $table->index(['warehouse_delivery_batch_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouse_delivery_batch_items', function (Blueprint $table) {
            $table->dropIndex(['po_number']);
            $table->dropIndex(['status']);
            $table->dropIndex(['warehouse_delivery_batch_id', 'status']);
        });
    }
};
