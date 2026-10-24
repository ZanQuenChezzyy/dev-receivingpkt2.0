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
        Schema::create('invoicing_receipts', function (Blueprint $table) {
            $table->id();

            // Relasi dokumen
            $table->foreignId('grs_rdtv_item_id')->nullable()->constrained('grs_rdtv_items')->nullOnDelete();
            $table->foreignId('delivery_order_receipt_id')->nullable()->constrained('delivery_order_receipts')->nullOnDelete();
            $table->foreignId('purchase_order_issued_id')->nullable()->constrained('purchase_order_issueds')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('pic_id')->nullable()->constrained('users')->nullOnDelete();

            // Kolom dokumen GRS / RDTV
            $table->date('tanggal_terima_grs')->nullable();
            $table->string('jenis')->default('GRS'); // GRS atau RDTV
            $table->string('no_grs')->nullable()->index();
            $table->string('no_rdtv')->nullable()->index();
            $table->string('no_po')->index();
            $table->string('no_do')->nullable()->index();
            $table->string('padi')->nullable();
            $table->string('material_type')->nullable();

            // Informasi Vendor
            $table->string('vendor_name')->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('email_vendor')->nullable();
            $table->date('tanggal_info_grs')->nullable();

            // Tagihan & Faktur
            $table->string('no_invoice')->nullable()->index();
            $table->string('faktur_pajak')->nullable()->index();
            $table->text('nama_barang')->nullable();
            $table->decimal('qty_penagihan', 15, 4)->nullable();
            $table->decimal('qty_po', 15, 4)->nullable();
            $table->string('uoi', 20)->nullable();
            $table->decimal('subtotal_exclude_ppn', 18, 2)->nullable();
            $table->decimal('subtotal_include_ppn', 18, 2)->nullable();

            // Verifikasi & Timeline
            $table->date('tanggal_terima_koreksi_invoice')->nullable();
            $table->date('tanggal_kirim_kasir')->nullable();
            $table->integer('lama_pekerjaan')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('dokumen_verifikasi_grs')->nullable();
            $table->string('status')->default('Menunggu Verifikasi'); // Menunggu Verifikasi, Terverifikasi, Terkirim Kasir

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoicing_receipts');
    }
};
