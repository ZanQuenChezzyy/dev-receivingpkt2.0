<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_order_receipt_termins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_receipt_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 50)->nullable();
            $table->decimal('percentage', 6, 2)->nullable(); // up to 100.00
            $table->dateTime('post_103')->nullable();
            $table->string('qr_103_code', 100)->nullable();
            $table->timestamps();
        });

        // Data Migration (Seeding)
        // Pindahkan data Termin lama ke tabel baru agar tidak rusak
        $oldTermins = DB::table('delivery_order_receipts')
            ->where('receipt_mode', 'Termin')
            ->get();

        foreach ($oldTermins as $termin) {
            DB::table('delivery_order_receipt_termins')->insert([
                'delivery_order_receipt_id' => $termin->id,
                'stage' => $termin->stage ?? 'TERMIN 1',
                'percentage' => $termin->termin_percentage ?? 0,
                'post_103' => $termin->post_103,
                'qr_103_code' => $termin->qr_103_code,
                'created_at' => $termin->created_at,
                'updated_at' => $termin->updated_at,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_order_receipt_termins');
    }
};
