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
            $table->string('diserahkan_npk')->nullable()->after('diserahkan_oleh');
            $table->longText('diserahkan_signature')->nullable()->after('diserahkan_npk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_issues', function (Blueprint $table) {
            $table->dropColumn(['diserahkan_npk', 'diserahkan_signature']);
        });
    }
};
