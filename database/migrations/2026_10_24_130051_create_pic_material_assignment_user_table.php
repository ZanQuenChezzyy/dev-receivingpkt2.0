<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pic_material_assignment_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pic_material_assignment_id')
                ->constrained('pic_material_assignments')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['pic_material_assignment_id', 'user_id'], 'pma_user_unique');
        });

        // Migrasikan user_id yang sudah ada di tabel pic_material_assignments ke pivot table
        $existing = DB::table('pic_material_assignments')
            ->whereNotNull('user_id')
            ->get();

        foreach ($existing as $row) {
            DB::table('pic_material_assignment_user')->insertOrIgnore([
                'pic_material_assignment_id' => $row->id,
                'user_id' => $row->user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pic_material_assignment_user');
    }
};
