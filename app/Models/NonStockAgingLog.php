<?php

namespace App\Models;

use Database\Factories\NonStockAgingLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NonStockAgingLog extends Model
{
    /** @use HasFactory<NonStockAgingLogFactory> */
    use HasFactory;

    protected $table = 'nonstock_aging_logs';

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nonstock_aging_control_id',
        'action_type',
        'aging_days_snapshot',
        'aging_zone_snapshot',
        'outstanding_value_snapshot',
        'channel',
        'recipient_level',
        'recipient_name',
        'recipient_contact',
        'email_sent',
        'notes',
        'created_by',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aging_days_snapshot' => 'integer',
            'outstanding_value_snapshot' => 'float',
            'email_sent' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function control(): BelongsTo
    {
        return $this->belongsTo(NonStockAgingControl::class, 'nonstock_aging_control_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
