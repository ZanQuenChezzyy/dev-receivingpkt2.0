<?php

namespace App\Models;

use App\Enums\AgingFollowupStage;
use App\Enums\AgingOperationalStatus;
use App\Enums\AgingZone;
use Database\Factories\NonStockAgingControlFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class NonStockAgingControl extends Model
{
    /** @use HasFactory<NonStockAgingControlFactory> */
    use HasFactory;

    protected $table = 'nonstock_aging_controls';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'delivery_order_receipt_detail_id',
        'purchase_order_issued_id',
        'po_by_item',
        'data_source',
        'mrp_type_snapshot',
        'material_type_snapshot',
        'material_code_snapshot',
        'description_snapshot',
        'vendor_name_snapshot',
        'uoi_snapshot',
        'requisitioner_raw',
        'grs_posting_date',
        'initial_qty_gr',
        'manual_qty_issued',
        'initial_value_lc',
        'location_text',
        'requesting_unit_id',
        'pic_name_override',
        'pic_email_override',
        'pic_phone_override',
        'operational_status',
        'followup_stage',
        'physical_checked',
        'warehouse_validated_at',
        'warehouse_validated_by',
        'warehouse_validation_notes',
        'planned_pickup_date',
        'scheduled_hold_reason',
        'delay_reason_category',
        'moved_to_location',
        'moved_at',
        'last_reminded_at',
        'reminder_count',
        'avp_escalated_at',
        'vp_escalated_at',
        'last_followup_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grs_posting_date' => 'date',
            'initial_qty_gr' => 'float',
            'manual_qty_issued' => 'float',
            'initial_value_lc' => 'float',
            'operational_status' => AgingOperationalStatus::class,
            'followup_stage' => AgingFollowupStage::class,
            'physical_checked' => 'boolean',
            'warehouse_validated_at' => 'datetime',
            'planned_pickup_date' => 'date',
            'moved_at' => 'datetime',
            'last_reminded_at' => 'datetime',
            'reminder_count' => 'integer',
            'avp_escalated_at' => 'datetime',
            'vp_escalated_at' => 'datetime',
        ];
    }

    public function deliveryOrderReceiptDetail(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrderReceiptDetail::class);
    }

    public function purchaseOrderIssued(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderIssued::class);
    }

    public function requestingUnit(): BelongsTo
    {
        return $this->belongsTo(RequestingUnit::class);
    }

    public function warehouseValidatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'warehouse_validated_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(NonStockAgingLog::class, 'nonstock_aging_control_id')->latest('created_at');
    }

    /**
     * Menentukan tanggal T0 (prioritas: Tanggal GRS Web -> Tanggal GRS Impor -> Tanggal Fisik DO -> Tanggal Terima DO).
     */
    public function resolvedGrsDate(): ?Carbon
    {
        if ($this->grs_posting_date instanceof Carbon) {
            return $this->grs_posting_date;
        }

        $do = $this->deliveryOrderReceiptDetail?->deliveryOrderReceipt;

        if ($do !== null) {
            $grsDate = $do->grsRdtvItems
                ->filter(fn (GrsRdtvItem $item): bool => strtoupper((string) $item->grsRdtv?->category) === 'GRS' && $item->grsRdtv?->transaction_date !== null)
                ->map(fn (GrsRdtvItem $item): Carbon => $item->grsRdtv->transaction_date)
                ->sort()
                ->first();

            if ($grsDate instanceof Carbon) {
                return $grsDate;
            }

            if ($do->physical_received_date instanceof Carbon) {
                return $do->physical_received_date;
            }

            if ($do->received_date instanceof Carbon) {
                return $do->received_date;
            }
        }

        return $this->created_at?->copy()->startOfDay();
    }

    /**
     * Menghitung umur tunggu dalam hari kalender sejak tanggal T0.
     */
    public function resolvedAgingDays(?Carbon $asOf = null): int
    {
        $t0 = $this->resolvedGrsDate();

        if ($t0 === null) {
            return 0;
        }

        $reference = ($asOf ?? now())->copy()->startOfDay();
        $days = (int) $t0->copy()->startOfDay()->diffInDays($reference, false);

        return max(0, $days);
    }

    public function resolvedAgingZone(?Carbon $asOf = null): AgingZone
    {
        return AgingZone::fromDays($this->resolvedAgingDays($asOf));
    }

    public function resolvedReceivedQuantity(): float
    {
        if ((float) ($this->initial_qty_gr ?? 0) > 0) {
            return max(0.0, (float) $this->initial_qty_gr);
        }

        if ($this->deliveryOrderReceiptDetail !== null) {
            return max(0.0, (float) $this->deliveryOrderReceiptDetail->quantity);
        }

        return 0.0;
    }

    public function resolvedIssuedQuantity(): float
    {
        if ($this->deliveryOrderReceiptDetail !== null) {
            if ($this->deliveryOrderReceiptDetail->getAttribute('mir_issued_sum') !== null) {
                $mirIssued = (float) $this->deliveryOrderReceiptDetail->getAttribute('mir_issued_sum');
            } else {
                $mirIssued = $this->deliveryOrderReceiptDetail->relationLoaded('materialIssueDetails')
                    ? (float) $this->deliveryOrderReceiptDetail->materialIssueDetails->sum('diserahkan')
                    : (float) $this->deliveryOrderReceiptDetail->issued_quantity;
            }

            return max($mirIssued, (float) ($this->manual_qty_issued ?? 0));
        }

        return max(0.0, (float) ($this->manual_qty_issued ?? 0));
    }

    public function resolvedRemainingQuantity(): float
    {
        return max(0.0, $this->resolvedReceivedQuantity() - $this->resolvedIssuedQuantity());
    }

    /**
     * Menghitung nilai Rupiah (IDR / Local Currency) tertahan secara proporsional terhadap sisa qty.
     */
    public function resolvedOutstandingValue(): float
    {
        $receivedQty = $this->resolvedReceivedQuantity();
        $remainingQty = $this->resolvedRemainingQuantity();

        if ($receivedQty <= 0 || $remainingQty <= 0) {
            return 0.0;
        }

        $baseValueLc = null;

        if ((float) ($this->initial_value_lc ?? 0) > 0) {
            $baseValueLc = (float) $this->initial_value_lc;
        } elseif ($this->deliveryOrderReceiptDetail !== null && (float) $this->deliveryOrderReceiptDetail->total_amount_snapshot > 0) {
            $baseValueLc = (float) $this->deliveryOrderReceiptDetail->total_amount_snapshot;
        } elseif ($this->purchaseOrderIssued !== null && (float) $this->purchaseOrderIssued->total_amount_in_lc > 0) {
            $po = $this->purchaseOrderIssued;
            $unitLc = (float) $po->qty_po > 0
                ? ((float) $po->total_amount_in_lc / (float) $po->qty_po)
                : (float) $po->total_amount_in_lc;
            $baseValueLc = $receivedQty * $unitLc;
        }

        if ($baseValueLc === null || $baseValueLc <= 0) {
            return 0.0;
        }

        return round(($remainingQty / $receivedQty) * $baseValueLc, 2);
    }

    public function resolvedPoNumber(): string
    {
        $poNo = $this->purchaseOrderIssued?->purchase_order_no
            ?? $this->deliveryOrderReceiptDetail?->purchaseOrderIssued?->purchase_order_no;

        if (filled($poNo)) {
            return (string) $poNo;
        }

        if (filled($this->po_by_item) && str_contains((string) $this->po_by_item, '-')) {
            return explode('-', (string) $this->po_by_item, 2)[0];
        }

        return (string) ($this->po_by_item ?? '-');
    }

    public function resolvedMrpType(): string
    {
        return strtoupper((string) (
            $this->mrp_type_snapshot
            ?? $this->deliveryOrderReceiptDetail?->mrp_type
            ?? $this->purchaseOrderIssued?->mrp_type
            ?? 'NONSTOCK'
        ));
    }

    public function resolvedDescription(): string
    {
        return (string) (
            $this->deliveryOrderReceiptDetail?->description
            ?? $this->purchaseOrderIssued?->description
            ?? $this->description_snapshot
            ?? '-'
        );
    }

    public function resolvedMaterialCode(): ?string
    {
        return $this->deliveryOrderReceiptDetail?->material_code
            ?? $this->purchaseOrderIssued?->material_code
            ?? $this->material_code_snapshot;
    }

    public function resolvedVendorName(): ?string
    {
        return $this->purchaseOrderIssued?->vendor_name
            ?? $this->deliveryOrderReceiptDetail?->purchaseOrderIssued?->vendor_name
            ?? $this->vendor_name_snapshot;
    }

    public function resolvedRequisitioner(): ?string
    {
        return $this->deliveryOrderReceiptDetail?->requisitioner
            ?? $this->purchaseOrderIssued?->requisitioner
            ?? $this->requisitioner_raw;
    }

    public function resolvedLocation(): ?string
    {
        if (filled($this->location_text)) {
            return $this->location_text;
        }

        return $this->deliveryOrderReceiptDetail?->locationReceiving?->name;
    }

    public function resolvedPicName(): ?string
    {
        return filled($this->pic_name_override)
            ? $this->pic_name_override
            : $this->requestingUnit?->pic_name;
    }

    public function resolvedPicEmail(): ?string
    {
        return filled($this->pic_email_override)
            ? $this->pic_email_override
            : $this->requestingUnit?->pic_email;
    }

    public function resolvedPicPhone(): ?string
    {
        return filled($this->pic_phone_override)
            ? $this->pic_phone_override
            : $this->requestingUnit?->pic_phone;
    }

    public function hasMappedPic(): bool
    {
        return $this->requesting_unit_id !== null && filled($this->resolvedPicName());
    }

    public function isOutstanding(): bool
    {
        $status = $this->operational_status;

        return $status instanceof AgingOperationalStatus && $status->isOutstanding();
    }

    public function isOverduePlannedPickup(?Carbon $asOf = null): bool
    {
        if (! $this->planned_pickup_date instanceof Carbon) {
            return false;
        }

        if (! $this->isOutstanding()) {
            return false;
        }

        return $this->planned_pickup_date->lt(($asOf ?? now())->copy()->startOfDay());
    }
}
