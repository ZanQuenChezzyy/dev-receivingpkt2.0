<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseTransmittalItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_transmittal_id',
        'warehouse_delivery_batch_item_id',
        'delivery_order_receipt_detail_id',
    ];

    public function transmittal()
    {
        return $this->belongsTo(WarehouseTransmittal::class, 'warehouse_transmittal_id');
    }

    public function batchItem()
    {
        return $this->belongsTo(WarehouseDeliveryBatchItem::class, 'warehouse_delivery_batch_item_id');
    }

    public function detail()
    {
        return $this->belongsTo(DeliveryOrderReceiptDetail::class, 'delivery_order_receipt_detail_id');
    }
}
