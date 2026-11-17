<?php

use App\Models\InvoicingReceipt;
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
        if (! Schema::hasTable('invoicing_receipts')) {
            return;
        }

        DB::table('invoicing_receipts')
            ->where(function ($query): void {
                $query->whereNull('material_type')
                    ->orWhereRaw("TRIM(material_type) = ''")
                    ->orWhereRaw("LOWER(TRIM(material_type)) IN ('none', 'null', '-', 'kosong', 'nonstock', 'non-stock', 'non stock')");
            })
            ->where(function ($query): void {
                $query->whereNull('material_type')
                    ->orWhere('material_type', '!=', InvoicingReceipt::DEFAULT_MATERIAL_TYPE);
            })
            ->update([
                'material_type' => InvoicingReceipt::DEFAULT_MATERIAL_TYPE,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Normalisasi tipe material ke "Non Stock" tidak di-rollback agar data tetap konsisten.
    }
};
