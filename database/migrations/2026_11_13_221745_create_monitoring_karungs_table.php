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
        Schema::create('monitoring_karungs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_issued_id')->nullable()->constrained('purchase_order_issueds')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('no_po', 50)->index();
            $table->string('vendor_name', 255);
            $table->unsignedBigInteger('qty_po')->default(0);
            $table->string('kategori_karung', 100)->nullable();
            $table->string('lokasi_pabrik', 100)->nullable();
            $table->string('status', 50)->default('Aktif');
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('vendor_name');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_karungs');
    }
};
