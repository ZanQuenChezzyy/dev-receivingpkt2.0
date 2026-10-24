<?php

namespace Database\Factories;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'vendor_number' => (string) fake()->unique()->numerify('1000####'),
            'email' => fake()->unique()->companyEmail(),
            'name_3' => fake()->companySuffix(),
            'city' => fake()->city(),
            'street' => fake()->streetAddress(),
            'telephone_1' => fake()->phoneNumber(),
            'salesperson' => fake()->name(),
            'telp_salesperson' => fake()->phoneNumber(),
        ];
    }
}
