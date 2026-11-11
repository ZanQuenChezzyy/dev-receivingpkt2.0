<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseDeliveryBatchItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_delivery_batch_id',
        'posting_date',
        'material_code',
        'description',
        'po_number',
        'item_no',
        'ref_doc_103',
        'mat_doc_105',
        'doc_year',
        'quantity',
        'uom',
        'amount',
        'storage_location',
        'plant',
        'vendor_code',
        'reference_do_no',
        'storage_text',
        'status',
        'delivery_order_receipt_id',
        'delivery_order_receipt_detail_id',
        'warehouse_transmittal_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'posting_date' => 'date',
            'quantity' => 'decimal:3',
            'amount' => 'decimal:2',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(WarehouseDeliveryBatch::class, 'warehouse_delivery_batch_id');
    }

    public function deliveryOrderReceipt(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrderReceipt::class);
    }

    public function deliveryOrderReceiptDetail(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrderReceiptDetail::class);
    }

    public function warehouseTransmittal(): BelongsTo
    {
        return $this->belongsTo(WarehouseTransmittal::class);
    }
}
