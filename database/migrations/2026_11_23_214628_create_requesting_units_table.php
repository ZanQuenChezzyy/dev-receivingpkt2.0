<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requesting_units', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->json('requisitioner_aliases')->nullable();
            $table->string('pic_name')->nullable();
            $table->string('pic_npk', 30)->nullable();
            $table->string('pic_email')->nullable();
            $table->string('pic_phone', 40)->nullable();
            $table->string('avp_name')->nullable();
            $table->string('avp_title')->nullable();
            $table->string('avp_email')->nullable();
            $table->string('avp_phone', 40)->nullable();
            $table->string('vp_name')->nullable();
            $table->string('vp_title')->nullable();
            $table->string('vp_email')->nullable();
            $table->string('vp_phone', 40)->nullable();
            $table->boolean('is_pilot')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requesting_units');
    }
};
