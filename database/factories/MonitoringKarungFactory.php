<?php

namespace Database\Factories;

use App\Models\MonitoringKarung;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitoringKarung>
 */
class MonitoringKarungFactory extends Factory
{
    protected $model = MonitoringKarung::class;

    public function definition(): array
    {
        return [
            'no_po' => (string) fake()->numerify('500000####'),
            'vendor_name' => fake()->company(),
            'qty_po' => fake()->randomElement([500000, 1000000, 1500000, 2000000]),
            'kategori_karung' => fake()->randomElement(['Prill', 'Granul', 'NPK', 'Retail', null]),
            'lokasi_pabrik' => fake()->randomElement(['Surabaya', 'Banyuwangi', null]),
            'status' => 'Aktif',
            'catatan' => null,
        ];
    }
}
