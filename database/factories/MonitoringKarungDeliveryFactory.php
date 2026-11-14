<?php

namespace Database\Factories;

use App\Models\MonitoringKarung;
use App\Models\MonitoringKarungDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitoringKarungDelivery>
 */
class MonitoringKarungDeliveryFactory extends Factory
{
    protected $model = MonitoringKarungDelivery::class;

    public function definition(): array
    {
        return [
            'monitoring_karung_id' => MonitoringKarung::factory(),
            'tahap_kirim' => (string) fake()->numberBetween(1, 10),
            'qty_kirim' => fake()->randomElement([150000, 180000, 200000, 300000]),
            'no_invoice' => fake()->bothify('###/???/25'),
            'no_do' => fake()->bothify('##/???/25'),
            'no_surat' => fake()->numerify('#####'),
            'qty_supply' => fake()->randomElement([150000, 180000, 200000]),
            'qty_surat' => 300000,
            'tanggal_penerimaan' => fake()->date(),
            'sort_order' => 0,
        ];
    }
}
