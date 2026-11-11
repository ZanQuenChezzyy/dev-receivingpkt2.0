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
        Schema::create('warehouse_delivery_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_title');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_items')->default(0);
            $table->decimal('total_value', 18, 2)->default(0);
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->default('active'); // active, completed, archived
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_delivery_batches');
    }
};
