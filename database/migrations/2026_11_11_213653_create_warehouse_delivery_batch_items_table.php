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
        Schema::create('warehouse_delivery_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_delivery_batch_id')
                ->constrained('warehouse_delivery_batches', 'id', 'wdbi_batch_fk')
                ->cascadeOnDelete();
            $table->date('posting_date');
            $table->string('material_code')->nullable();
            $table->text('description');
            $table->string('po_number', 50);
            $table->string('item_no', 20);
            $table->string('ref_doc_103', 50)->nullable();
            $table->string('mat_doc_105', 50)->index();
            $table->string('doc_year', 4)->nullable();
            $table->decimal('quantity', 15, 3);
            $table->string('uom', 20);
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('storage_location', 50)->nullable();
            $table->string('plant', 20)->nullable();
            $table->string('vendor_code', 50)->nullable();
            $table->string('reference_do_no', 100)->nullable();
            $table->string('storage_text', 100)->nullable();

            $table->string('status')->default('pending'); // pending, delivered
            $table->foreignId('delivery_order_receipt_id')
                ->nullable()
                ->constrained('delivery_order_receipts', 'id', 'wdbi_dor_fk')
                ->nullOnDelete();
            $table->foreignId('delivery_order_receipt_detail_id')
                ->nullable()
                ->constrained('delivery_order_receipt_details', 'id', 'wdbi_dord_fk')
                ->nullOnDelete();
            $table->foreignId('warehouse_transmittal_id')
                ->nullable()
                ->constrained('warehouse_transmittals', 'id', 'wdbi_wt_fk')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_delivery_batch_items');
    }
};
