<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualMir extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'delivery_order_receipt_id',
        'image_path',
        'created_by',
    ];

    public function deliveryOrderReceipt()
    {
        return $this->belongsTo(DeliveryOrderReceipt::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
