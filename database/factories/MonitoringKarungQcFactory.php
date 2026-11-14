<?php

namespace Database\Factories;

use App\Models\MonitoringKarung;
use App\Models\MonitoringKarungQc;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitoringKarungQc>
 */
class MonitoringKarungQcFactory extends Factory
{
    protected $model = MonitoringKarungQc::class;

    public function definition(): array
    {
        return [
            'monitoring_karung_id' => MonitoringKarung::factory(),
            'tahap_qc' => fake()->randomElement(['Awal', '1', '2', '3']),
            'qty_qc' => fake()->randomElement([22, 150000, 300000]),
            'no_dof_daan' => fake()->numerify('#####'),
            'tgl_permintaan' => fake()->date(),
            'no_sik' => fake()->numerify('####'),
            'tgl_sik' => fake()->date(),
            'no_surat_qc' => fake()->numerify('#####'),
            'status_qc' => fake()->randomElement(['Diterima', 'Ditolak', 'Istek', 'TUV']),
            'file_istek' => fake()->numerify('#####'),
            'no_bapp' => fake()->numerify('0####'),
            'no_sa' => fake()->numerify('1000######'),
            'drafter' => fake()->randomElement(['CAMEL', 'PUGUH', 'RIZKI']),
            'sort_order' => 0,
        ];
    }
}
