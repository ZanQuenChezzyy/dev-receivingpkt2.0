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
            'city' => fake()->city(),
            'street' => fake()->streetAddress(),
            'street2' => null,
            'street3' => null,
            'street4' => null,
            'telephone_1' => fake()->phoneNumber(),
            'telephone_2' => fake()->phoneNumber(),
            'fax_number' => null,
            'salesperson' => fake()->name(),
            'telp_salesperson' => fake()->phoneNumber(),
            'email' => [fake()->unique()->companyEmail(), fake()->unique()->companyEmail()],
        ];
    }
}
