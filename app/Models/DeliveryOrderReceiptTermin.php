<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'delivery_order_receipt_id',
    'stage',
    'percentage',
    'post_103',
    'qr_103_code',
])]
class DeliveryOrderReceiptTermin extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'percentage' => 'float',
            'post_103' => 'datetime',
        ];
    }

    public function deliveryOrderReceipt(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrderReceipt::class);
    }
}
