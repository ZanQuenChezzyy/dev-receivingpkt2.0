<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicingReceiptItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoicing_receipt_id',
        'purchase_order_issued_id',
        'delivery_order_receipt_detail_id',
        'item_no',
        'material_code',
        'no_grs',
        'no_do',
        'nama_barang',
        'qty_penagihan',
        'qty_po',
        'uoi',
        'subtotal_exclude_ppn',
        'subtotal_include_ppn',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item_no' => 'integer',
            'qty_penagihan' => 'float',
            'qty_po' => 'float',
            'subtotal_exclude_ppn' => 'float',
            'subtotal_include_ppn' => 'float',
        ];
    }

    public function invoicingReceipt(): BelongsTo
    {
        return $this->belongsTo(InvoicingReceipt::class);
    }

    public function purchaseOrderIssued(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderIssued::class);
    }

    public function deliveryOrderReceiptDetail(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrderReceiptDetail::class);
    }
}
