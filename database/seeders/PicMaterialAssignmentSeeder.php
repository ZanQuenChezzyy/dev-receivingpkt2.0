<?php

namespace Database\Seeders;

use App\Models\PicMaterialAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;

class PicMaterialAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultUser = User::first();

        PicMaterialAssignment::firstOrCreate(
            ['material_type' => 'ZSP'],
            [
                'assignment_mode' => 'free_pool',
                'user_id' => null,
                'notes' => 'Bebas ke siapapun (Open pool)',
            ]
        );

        $zrm = PicMaterialAssignment::firstOrCreate(
            ['material_type' => 'ZRM'],
            [
                'assignment_mode' => $defaultUser ? 'user' : 'free_pool',
                'user_id' => $defaultUser?->id,
                'notes' => 'Khusus Raw Material',
            ]
        );

        if ($defaultUser && $zrm->assignment_mode === 'user') {
            $zrm->users()->syncWithoutDetaching([$defaultUser->id]);
        }

        PicMaterialAssignment::firstOrCreate(
            ['material_type' => 'ZPM'],
            [
                'assignment_mode' => 'free_pool',
                'user_id' => null,
                'notes' => 'Packaging Material',
            ]
        );

        PicMaterialAssignment::firstOrCreate(
            ['material_type' => 'ZFP'],
            [
                'assignment_mode' => 'free_pool',
                'user_id' => null,
                'notes' => 'Finished Product',
            ]
        );

        PicMaterialAssignment::firstOrCreate(
            ['material_type' => 'ZSM'],
            [
                'assignment_mode' => 'free_pool',
                'user_id' => null,
                'notes' => 'Semi-Finished Material',
            ]
        );
    }
}
