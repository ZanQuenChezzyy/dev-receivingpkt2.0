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
        if (! Schema::hasColumn('users', 'has_exclusive_badge')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('has_exclusive_badge')
                    ->nullable()
                    ->default(null)
                    ->after('developer_badge_style');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'has_exclusive_badge')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('has_exclusive_badge');
            });
        }
    }
};
