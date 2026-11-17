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
        Schema::table('invoicing_receipts', function (Blueprint $table) {
            $table->string('kategori_barang')->nullable()->index()->after('jenis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoicing_receipts', function (Blueprint $table) {
            $table->dropIndex(['kategori_barang']);
            $table->dropColumn('kategori_barang');
        });
    }
};
