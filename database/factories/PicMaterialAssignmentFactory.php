<?php

namespace Database\Factories;

use App\Models\PicMaterialAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PicMaterialAssignment>
 */
class PicMaterialAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'material_type' => fake()->unique()->randomElement(['ZSP', 'ZRM', 'ZPM', 'ZSR', 'ZNB', 'ZRAW', 'ZPKG']),
            'assignment_mode' => 'free_pool',
            'user_id' => null,
            'notes' => fake()->sentence(),
        ];
    }
}
