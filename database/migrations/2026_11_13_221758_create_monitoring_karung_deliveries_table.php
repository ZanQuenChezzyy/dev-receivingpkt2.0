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
        Schema::create('monitoring_karung_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitoring_karung_id')->constrained('monitoring_karungs')->cascadeOnDelete();
            $table->foreignId('invoicing_receipt_id')->nullable()->constrained('invoicing_receipts')->nullOnDelete();
            $table->string('tahap_kirim', 50);
            $table->unsignedBigInteger('qty_kirim')->default(0);
            $table->string('no_invoice', 100)->nullable();
            $table->string('no_do', 100)->nullable();
            $table->string('no_surat', 100)->nullable();
            $table->unsignedBigInteger('qty_supply')->nullable();
            $table->unsignedBigInteger('qty_surat')->nullable();
            $table->date('tanggal_penerimaan')->nullable();
            $table->text('keterangan')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('no_invoice');
            $table->index('no_do');
            $table->index('tanggal_penerimaan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_karung_deliveries');
    }
};
