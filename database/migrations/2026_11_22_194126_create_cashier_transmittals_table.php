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
        Schema::create('cashier_transmittals', function (Blueprint $table) {
            $table->id();
            $table->string('transmittal_no')->unique();
            $table->date('sent_date')->index();
            $table->string('destination')->default('Department Akuntansi / Dept. Akuntansi');
            $table->string('status', 30)->default('Terbuka')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_transmittals');
    }
};
