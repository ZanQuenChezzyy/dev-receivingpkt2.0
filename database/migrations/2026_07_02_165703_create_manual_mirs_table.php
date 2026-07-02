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
        Schema::create('manual_mirs', function (Blueprint $table) {
            $table->id();
            $table->string('po_number');
            $table->foreignId('delivery_order_receipt_id')->nullable()->constrained()->nullOnDelete();
            $table->string('image_path');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_mirs');
    }
};
