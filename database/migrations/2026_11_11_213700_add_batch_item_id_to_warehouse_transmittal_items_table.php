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
        Schema::table('warehouse_transmittal_items', function (Blueprint $table) {
            $table->foreignId('warehouse_delivery_batch_item_id')
                ->nullable()
                ->after('warehouse_transmittal_id')
                ->constrained('warehouse_delivery_batch_items', 'id', 'wti_wdbi_fk')
                ->nullOnDelete();

            if (DB::getDriverName() !== 'sqlite') {
                $table->unsignedBigInteger('delivery_order_receipt_detail_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouse_transmittal_items', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign('wti_wdbi_fk');
            }
            $table->dropColumn('warehouse_delivery_batch_item_id');
        });
    }
};
