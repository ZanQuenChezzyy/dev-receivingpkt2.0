<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('warehouse_delivery_batch_items', function (Blueprint $table) {
            if (DB::getDriverName() === 'mysql') {
                $existing = collect(DB::select('SHOW INDEX FROM warehouse_delivery_batch_items'))->pluck('Key_name')->unique()->all();
                if (! in_array('warehouse_delivery_batch_items_po_number_index', $existing)) {
                    $table->index('po_number');
                }
                if (! in_array('warehouse_delivery_batch_items_status_index', $existing)) {
                    $table->index('status');
                }
                if (! in_array('wdbi_batch_status_idx', $existing)) {
                    $table->index(['warehouse_delivery_batch_id', 'status'], 'wdbi_batch_status_idx');
                }
            } else {
                $table->index('po_number');
                $table->index('status');
                $table->index(['warehouse_delivery_batch_id', 'status'], 'wdbi_batch_status_idx');
            }
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
            $table->dropIndex('wdbi_batch_status_idx');
        });
    }
};
