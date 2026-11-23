<?php

namespace App\Models;

use Database\Factories\NonStockAgingSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NonStockAgingSnapshot extends Model
{
    /** @use HasFactory<NonStockAgingSnapshotFactory> */
    use HasFactory;

    protected $table = 'nonstock_aging_snapshots';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'label',
        'snapshot_date',
        'scope',
        'mrp_scope',
        'total_outstanding_items',
        'total_outstanding_po',
        'total_outstanding_value',
        'on_check_items',
        'on_check_value',
        'normal_count',
        'normal_value',
        'attention_count',
        'attention_value',
        'warning_count',
        'warning_value',
        'critical_count',
        'critical_value',
        'scheduled_hold_count',
        'scheduled_hold_value',
        'unattended_critical_count',
        'unattended_critical_value',
        'pic_mapped_percentage',
        'warning_critical_followed_up_percentage',
        'unit_breakdown',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'total_outstanding_items' => 'integer',
            'total_outstanding_po' => 'integer',
            'total_outstanding_value' => 'float',
            'on_check_items' => 'integer',
            'on_check_value' => 'float',
            'normal_count' => 'integer',
            'normal_value' => 'float',
            'attention_count' => 'integer',
            'attention_value' => 'float',
            'warning_count' => 'integer',
            'warning_value' => 'float',
            'critical_count' => 'integer',
            'critical_value' => 'float',
            'scheduled_hold_count' => 'integer',
            'scheduled_hold_value' => 'float',
            'unattended_critical_count' => 'integer',
            'unattended_critical_value' => 'float',
            'pic_mapped_percentage' => 'float',
            'warning_critical_followed_up_percentage' => 'float',
            'unit_breakdown' => 'array',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
