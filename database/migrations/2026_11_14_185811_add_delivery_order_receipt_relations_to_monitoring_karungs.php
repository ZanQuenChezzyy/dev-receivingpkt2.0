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
        if (Schema::hasTable('delivery_order_receipts')) {
            Schema::table('delivery_order_receipts', function (Blueprint $table) {
                if (! Schema::hasColumn('delivery_order_receipts', 'monitoring_karung_id')) {
                    $table->foreignId('monitoring_karung_id')
                        ->nullable()
                        ->after('monitoring_chemical_id')
                        ->constrained('monitoring_karungs')
                        ->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('monitoring_karung_deliveries')) {
            Schema::table('monitoring_karung_deliveries', function (Blueprint $table) {
                if (! Schema::hasColumn('monitoring_karung_deliveries', 'delivery_order_receipt_id')) {
                    $table->foreignId('delivery_order_receipt_id')
                        ->nullable()
                        ->after('monitoring_karung_id')
                        ->constrained('delivery_order_receipts')
                        ->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('monitoring_karung_deliveries')) {
            Schema::table('monitoring_karung_deliveries', function (Blueprint $table) {
                if (Schema::hasColumn('monitoring_karung_deliveries', 'delivery_order_receipt_id')) {
                    $table->dropConstrainedForeignId('delivery_order_receipt_id');
                }
            });
        }

        if (Schema::hasTable('delivery_order_receipts')) {
            Schema::table('delivery_order_receipts', function (Blueprint $table) {
                if (Schema::hasColumn('delivery_order_receipts', 'monitoring_karung_id')) {
                    $table->dropConstrainedForeignId('monitoring_karung_id');
                }
            });
        }
    }
};
