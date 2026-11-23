<?php

namespace Database\Factories;

use App\Enums\AgingFollowupStage;
use App\Enums\AgingOperationalStatus;
use App\Models\NonStockAgingControl;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NonStockAgingControl>
 */
class NonStockAgingControlFactory extends Factory
{
    protected $model = NonStockAgingControl::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $poNo = '53000'.$this->faker->numerify('#####');
        $itemNo = $this->faker->randomElement([10, 20, 30, 40]);

        return [
            'delivery_order_receipt_detail_id' => null,
            'purchase_order_issued_id' => null,
            'po_by_item' => $poNo.'-'.$itemNo,
            'data_source' => 'import_monitoring',
            'mrp_type_snapshot' => 'NONSTOCK',
            'material_type_snapshot' => 'ZSP',
            'material_code_snapshot' => $this->faker->numerify('60#####'),
            'description_snapshot' => strtoupper($this->faker->words(4, true)),
            'vendor_name_snapshot' => $this->faker->company(),
            'uoi_snapshot' => 'EA',
            'requisitioner_raw' => 'RENDAL-TAP5',
            'grs_posting_date' => now()->subDays(10)->toDateString(),
            'initial_qty_gr' => 2,
            'manual_qty_issued' => 0,
            'initial_value_lc' => 50_000_000,
            'location_text' => 'FLOOR-A',
            'requesting_unit_id' => null,
            'operational_status' => AgingOperationalStatus::BelumDiambil,
            'followup_stage' => AgingFollowupStage::BelumDitindaklanjuti,
            'physical_checked' => true,
            'reminder_count' => 0,
        ];
    }

    public function onCheck(): static
    {
        return $this->state(fn (): array => [
            'operational_status' => AgingOperationalStatus::OnCheck,
            'physical_checked' => false,
        ]);
    }

    public function scheduledHold(string $reason = 'Menunggu jadwal Turn Around Pabrik'): static
    {
        return $this->state(fn (): array => [
            'operational_status' => AgingOperationalStatus::ScheduledHold,
            'followup_stage' => AgingFollowupStage::TerkonfirmasiJadwalAmbil,
            'planned_pickup_date' => now()->addDays(14)->toDateString(),
            'scheduled_hold_reason' => $reason,
            'delay_reason_category' => 'Menunggu Jadwal TA/Maintenance',
        ]);
    }

    public function agingDays(int $days): static
    {
        return $this->state(fn (): array => [
            'grs_posting_date' => now()->subDays($days)->toDateString(),
        ]);
    }
}
