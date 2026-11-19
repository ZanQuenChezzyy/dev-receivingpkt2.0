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
        if (Schema::hasTable('invoicing_receipt_items')) {
            Schema::table('invoicing_receipt_items', function (Blueprint $table) {
                if (Schema::hasColumn('invoicing_receipt_items', 'subtotal_exclude_ppn')) {
                    $table->dropColumn('subtotal_exclude_ppn');
                }
                if (Schema::hasColumn('invoicing_receipt_items', 'subtotal_include_ppn')) {
                    $table->dropColumn('subtotal_include_ppn');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('invoicing_receipt_items')) {
            Schema::table('invoicing_receipt_items', function (Blueprint $table) {
                if (! Schema::hasColumn('invoicing_receipt_items', 'subtotal_exclude_ppn')) {
                    $table->decimal('subtotal_exclude_ppn', 18, 2)->nullable();
                }
                if (! Schema::hasColumn('invoicing_receipt_items', 'subtotal_include_ppn')) {
                    $table->decimal('subtotal_include_ppn', 18, 2)->nullable();
                }
            });
        }
    }
};
