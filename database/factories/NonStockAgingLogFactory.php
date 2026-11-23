<?php

namespace Database\Factories;

use App\Models\NonStockAgingControl;
use App\Models\NonStockAgingLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NonStockAgingLog>
 */
class NonStockAgingLogFactory extends Factory
{
    protected $model = NonStockAgingLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nonstock_aging_control_id' => NonStockAgingControl::factory(),
            'action_type' => 'reminder_stage_1',
            'aging_days_snapshot' => 20,
            'aging_zone_snapshot' => 'Attention',
            'outstanding_value_snapshot' => 25_000_000,
            'channel' => 'WhatsApp',
            'recipient_level' => 'PIC',
            'recipient_name' => $this->faker->name(),
            'recipient_contact' => '08'.$this->faker->numerify('##########'),
            'email_sent' => false,
            'notes' => 'Reminder pengambilan material Non-Stock.',
            'created_by' => null,
            'created_at' => now(),
        ];
    }
}
