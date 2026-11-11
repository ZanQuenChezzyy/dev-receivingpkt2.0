<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sqlExpression = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(vendor_name, 'Pusat', ''), 'PUSAT', ''), 'pusat', ''), ' ,', ','))";

        if (Schema::hasTable('purchase_order_issueds')) {
            DB::table('purchase_order_issueds')
                ->where('vendor_name', 'like', '%pusat%')
                ->update([
                    'vendor_name' => DB::raw($sqlExpression),
                ]);
        }

        if (Schema::hasTable('invoicing_receipts')) {
            DB::table('invoicing_receipts')
                ->where('vendor_name', 'like', '%pusat%')
                ->update([
                    'vendor_name' => DB::raw($sqlExpression),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data normalization is irreversible.
    }
};
