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
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'unlocked_exclusive_badges')) {
                $table->json('unlocked_exclusive_badges')
                    ->nullable()
                    ->after('has_exclusive_badge');
            }

            if (! Schema::hasColumn('users', 'badge_milestone_notified')) {
                $table->json('badge_milestone_notified')
                    ->nullable()
                    ->after('unlocked_exclusive_badges');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('users', 'unlocked_exclusive_badges') ? 'unlocked_exclusive_badges' : null,
                Schema::hasColumn('users', 'badge_milestone_notified') ? 'badge_milestone_notified' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
