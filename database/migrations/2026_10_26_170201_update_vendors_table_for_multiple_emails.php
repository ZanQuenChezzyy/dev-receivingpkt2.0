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
        // 1. Drop index pada email jika ada (MySQL memerlukan drop index sebelum diubah ke TEXT)
        try {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropIndex(['email']);
            });
        } catch (Throwable $e) {
            // Abaikan jika index tidak ditemukan
        }

        // 2. Drop kolom name_3 jika ada
        if (Schema::hasColumn('vendors', 'name_3')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropColumn('name_3');
            });
        }

        // 3. Ubah kolom email menjadi text nullable untuk mendukung multi-email
        Schema::table('vendors', function (Blueprint $table) {
            $table->text('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('name_3')->nullable();
            $table->string('email')->nullable()->change();
        });
    }
};
