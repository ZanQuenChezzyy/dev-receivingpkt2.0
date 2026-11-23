<?php

namespace Database\Factories;

use App\Models\NonStockAgingSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NonStockAgingSnapshot>
 */
class NonStockAgingSnapshotFactory extends Factory
{
    protected $model = NonStockAgingSnapshot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => 'Baseline W1',
            'snapshot_date' => now()->toDateString(),
            'scope' => 'all',
            'mrp_scope' => 'NONSTOCK',
            'total_outstanding_items' => 96,
            'total_outstanding_po' => 96,
            'total_outstanding_value' => 12_520_000_000,
            'on_check_items' => 46,
            'on_check_value' => 4_730_000_000,
            'normal_count' => 32,
            'normal_value' => 2_150_000_000,
            'attention_count' => 24,
            'attention_value' => 2_840_000_000,
            'warning_count' => 22,
            'warning_value' => 3_480_000_000,
            'critical_count' => 18,
            'critical_value' => 4_050_000_000,
            'scheduled_hold_count' => 0,
            'scheduled_hold_value' => 0,
            'unattended_critical_count' => 18,
            'unattended_critical_value' => 4_050_000_000,
            'pic_mapped_percentage' => 100.0,
            'warning_critical_followed_up_percentage' => 0.0,
            'unit_breakdown' => [],
            'created_by' => null,
        ];
    }
}
