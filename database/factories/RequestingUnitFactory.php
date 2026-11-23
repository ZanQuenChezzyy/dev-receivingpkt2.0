<?php

namespace Database\Factories;

use App\Models\RequestingUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestingUnit>
 */
class RequestingUnitFactory extends Factory
{
    protected $model = RequestingUnit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = strtoupper($this->faker->unique()->bothify('UNIT-##??'));

        return [
            'name' => 'Unit '.$code,
            'requisitioner_aliases' => [$code],
            'pic_name' => $this->faker->name(),
            'pic_npk' => $this->faker->numerify('#######'),
            'pic_email' => $this->faker->unique()->safeEmail(),
            'pic_phone' => '08'.$this->faker->numerify('##########'),
            'avp_name' => $this->faker->name(),
            'avp_title' => 'AVP '.$code,
            'avp_email' => $this->faker->unique()->safeEmail(),
            'avp_phone' => '08'.$this->faker->numerify('##########'),
            'vp_name' => $this->faker->name(),
            'vp_title' => 'VP '.$code,
            'vp_email' => $this->faker->unique()->safeEmail(),
            'vp_phone' => '08'.$this->faker->numerify('##########'),
            'is_pilot' => false,
            'is_active' => true,
        ];
    }

    public function pilot(): static
    {
        return $this->state(fn (): array => [
            'is_pilot' => true,
        ]);
    }
}
