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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('vendor_number')->unique()->index();
            $table->string('email')->index();
            $table->string('name_3')->nullable();
            $table->string('city')->nullable();
            $table->string('street')->nullable();
            $table->string('street2')->nullable();
            $table->string('street3')->nullable();
            $table->string('street4')->nullable();
            $table->string('telephone_1')->nullable();
            $table->string('telephone_2')->nullable();
            $table->string('fax_number')->nullable();
            $table->string('salesperson')->nullable();
            $table->string('telp_salesperson')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
