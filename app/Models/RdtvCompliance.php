<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RdtvCompliance extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_order_receipt_id',
        'purchase_order_issued_id',
        'vendor_id',
        'document_type',
        'file_path',
        'original_filename',
        'file_size',
        'vendor_notes',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function deliveryOrderReceipt(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrderReceipt::class);
    }

    public function purchaseOrderIssued(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderIssued::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'Menunggu Review';
    }

    public function isApproved(): bool
    {
        return $this->status === 'Disetujui';
    }

    public function isRejected(): bool
    {
        return $this->status === 'Ditolak';
    }
}
