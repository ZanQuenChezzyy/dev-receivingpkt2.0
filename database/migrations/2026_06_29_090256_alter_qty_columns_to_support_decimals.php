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
        Schema::table('monitoring_chemical_details', function (Blueprint $table) {
            $table->decimal('quantity', 15, 4)->nullable()->change();
        });

        Schema::table('delivery_order_receipt_details', function (Blueprint $table) {
            $table->decimal('quantity', 15, 4)->change();
            $table->decimal('total_amount_snapshot', 20, 4)->change();
        });

        Schema::table('monitoring_npk_details', function (Blueprint $table) {
            $table->decimal('quantity', 15, 4)->change();
        });

        Schema::table('chemical_qc_tuvs', function (Blueprint $table) {
            $table->decimal('qty_qc_tuv', 15, 4)->change();
        });

        Schema::table('purchase_order_issueds', function (Blueprint $table) {
            $table->decimal('qty_po', 15, 4)->default(0)->change();
            $table->decimal('net_price', 20, 4)->default(0)->change();
            $table->decimal('total_amount_in_lc', 20, 4)->default(0)->change();
        });
        
        Schema::table('material_issue_details', function (Blueprint $table) {
            $table->decimal('diminta', 15, 4)->change();
            $table->decimal('diserahkan', 15, 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_chemical_details', function (Blueprint $table) {
            $table->decimal('quantity', 15, 0)->nullable()->change();
        });

        Schema::table('delivery_order_receipt_details', function (Blueprint $table) {
            $table->decimal('quantity', 15, 0)->change();
            $table->decimal('total_amount_snapshot', 20, 0)->change();
        });

        Schema::table('monitoring_npk_details', function (Blueprint $table) {
            $table->decimal('quantity', 15, 0)->change();
        });

        Schema::table('chemical_qc_tuvs', function (Blueprint $table) {
            $table->decimal('qty_qc_tuv', 15, 0)->change();
        });

        Schema::table('purchase_order_issueds', function (Blueprint $table) {
            $table->decimal('qty_po', 12, 0)->default(0)->change();
            $table->decimal('net_price', 20, 0)->default(0)->change();
            $table->decimal('total_amount_in_lc', 20, 0)->default(0)->change();
        });
        
        Schema::table('material_issue_details', function (Blueprint $table) {
            $table->decimal('diminta', 15, 2)->change();
            $table->decimal('diserahkan', 15, 2)->change();
        });
    }
};
