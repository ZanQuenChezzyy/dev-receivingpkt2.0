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
        Schema::table('material_issues', function (Blueprint $table) {
            $table->enum('jenis_mir', ['digital', 'manual'])->default('digital')->after('id');
            $table->string('image_path')->nullable()->after('jenis_mir');
            $table->string('po_number')->nullable()->after('purchase_order_issued_id');
            $table->foreignId('delivery_order_receipt_id')->nullable()->constrained()->nullOnDelete();

            $table->string('mir_number')->nullable()->change();
            $table->date('tanggal')->nullable()->change();
            $table->foreignId('purchase_order_issued_id')->nullable()->change();
            $table->string('no_hp')->nullable()->change();
            $table->string('departemen')->nullable()->change();
            $table->string('bagian')->nullable()->change();
            $table->text('digunakan_untuk')->nullable()->change();
        });

        $manualMirs = \Illuminate\Support\Facades\DB::table('manual_mirs')->get();
        foreach ($manualMirs as $mir) {
            \Illuminate\Support\Facades\DB::table('material_issues')->insert([
                'jenis_mir' => 'manual',
                'po_number' => $mir->po_number,
                'delivery_order_receipt_id' => $mir->delivery_order_receipt_id,
                'image_path' => $mir->image_path,
                'created_by' => $mir->created_by,
                'created_at' => $mir->created_at,
                'updated_at' => $mir->updated_at,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('material_issues')->where('jenis_mir', 'manual')->delete();

        Schema::table('material_issues', function (Blueprint $table) {
            $table->dropForeign(['delivery_order_receipt_id']);
            $table->dropColumn(['jenis_mir', 'image_path', 'po_number', 'delivery_order_receipt_id']);
            
            $table->string('mir_number')->nullable(false)->change();
            $table->date('tanggal')->nullable(false)->change();
            $table->foreignId('purchase_order_issued_id')->nullable(false)->change();
            $table->string('no_hp')->nullable(false)->change();
            $table->string('departemen')->nullable(false)->change();
            $table->string('bagian')->nullable(false)->change();
            $table->text('digunakan_untuk')->nullable(false)->change();
        });
    }
};
