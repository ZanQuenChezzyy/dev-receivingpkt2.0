<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'left_rail_shortcuts')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->json('left_rail_shortcuts')->nullable()->after('avatar_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'left_rail_shortcuts')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('left_rail_shortcuts');
            });
        }
    }
};
