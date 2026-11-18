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
        Schema::create('invoicing_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoicing_receipt_id')->index();
            $table->unsignedBigInteger('purchase_order_issued_id')->nullable()->index();
            $table->unsignedBigInteger('delivery_order_receipt_detail_id')->nullable()->index();
            $table->integer('item_no')->nullable()->index();
            $table->string('material_code')->nullable()->index();
            $table->string('no_grs')->nullable()->index();
            $table->string('no_do')->nullable()->index();
            $table->text('nama_barang')->nullable();
            $table->decimal('qty_penagihan', 15, 4)->nullable();
            $table->decimal('qty_po', 15, 4)->nullable();
            $table->string('uoi', 20)->nullable();
            $table->decimal('subtotal_exclude_ppn', 18, 2)->nullable();
            $table->decimal('subtotal_include_ppn', 18, 2)->nullable();
            $table->timestamps();

            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('invoicing_receipt_id')->references('id')->on('invoicing_receipts')->cascadeOnDelete();
                $table->foreign('purchase_order_issued_id')->references('id')->on('purchase_order_issueds')->nullOnDelete();
                $table->foreign('delivery_order_receipt_detail_id')->references('id')->on('delivery_order_receipt_details')->nullOnDelete();
            }
        });

        // Backfill data historis dari invoicing_receipts jika ada
        if (Schema::hasTable('invoicing_receipts')) {
            $existingReceipts = DB::table('invoicing_receipts')->get();
            $now = now();

            foreach ($existingReceipts as $receipt) {
                $hasContent = (isset($receipt->nama_barang) && trim((string) $receipt->nama_barang) !== '')
                    || (isset($receipt->qty_penagihan) && (float) $receipt->qty_penagihan > 0)
                    || (isset($receipt->subtotal_exclude_ppn) && (float) $receipt->subtotal_exclude_ppn > 0);

                if ($hasContent) {
                    DB::table('invoicing_receipt_items')->insert([
                        'invoicing_receipt_id' => $receipt->id,
                        'purchase_order_issued_id' => $receipt->purchase_order_issued_id ?? null,
                        'no_grs' => $receipt->no_grs ?? null,
                        'no_do' => $receipt->no_do ?? null,
                        'nama_barang' => $receipt->nama_barang ?? null,
                        'qty_penagihan' => $receipt->qty_penagihan ?? null,
                        'qty_po' => $receipt->qty_po ?? null,
                        'uoi' => $receipt->uoi ?? null,
                        'subtotal_exclude_ppn' => $receipt->subtotal_exclude_ppn ?? null,
                        'subtotal_include_ppn' => $receipt->subtotal_include_ppn ?? null,
                        'created_at' => $receipt->created_at ?? $now,
                        'updated_at' => $receipt->updated_at ?? $now,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoicing_receipt_items');
    }
};
