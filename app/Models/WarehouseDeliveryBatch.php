<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseDeliveryBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_title',
        'start_date',
        'end_date',
        'total_items',
        'total_value',
        'file_name',
        'file_path',
        'status',
        'uploaded_by',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_items' => 'integer',
            'total_value' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseDeliveryBatchItem::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getDeliveredItemsCountAttribute(): int
    {
        return $this->items()->where('status', 'delivered')->count();
    }

    public function updateProgressStatus(): void
    {
        $total = $this->total_items;
        $delivered = $this->delivered_items_count;

        if ($total > 0 && $delivered >= $total) {
            $this->update(['status' => 'completed']);
        } elseif ($delivered > 0) {
            $this->update(['status' => 'in_progress']);
        } else {
            $this->update(['status' => 'pending']);
        }
    }
}
