<?php

namespace App\Models;

use Database\Factories\MonitoringKarungFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringKarung extends Model
{
    /** @use HasFactory<MonitoringKarungFactory> */
    use HasFactory;

    protected $fillable = [
        'purchase_order_issued_id',
        'vendor_id',
        'no_po',
        'vendor_name',
        'qty_po',
        'kategori_karung',
        'lokasi_pabrik',
        'status',
        'catatan',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'qty_po' => 'integer',
        ];
    }

    public function purchaseOrderIssued(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderIssued::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function qcItems(): HasMany
    {
        return $this->hasMany(MonitoringKarungQc::class)->orderBy('sort_order')->orderBy('id');
    }

    public function deliveryItems(): HasMany
    {
        return $this->hasMany(MonitoringKarungDelivery::class)->orderBy('sort_order')->orderBy('id');
    }

    public function deliveryOrderReceipts(): HasMany
    {
        return $this->hasMany(DeliveryOrderReceipt::class);
    }

    public function getTotalQtyQcAttribute(): int
    {
        if ($this->relationLoaded('qcItems')) {
            return (int) $this->qcItems->sum('qty_qc');
        }

        return (int) $this->qcItems()->sum('qty_qc');
    }

    public function getTotalQtyKirimAttribute(): int
    {
        if ($this->relationLoaded('deliveryItems')) {
            return (int) $this->deliveryItems->sum('qty_kirim');
        }

        return (int) $this->deliveryItems()->sum('qty_kirim');
    }

    public function getSisaQtyPoAttribute(): int
    {
        return max(0, (int) $this->qty_po - $this->total_qty_kirim);
    }

    public function getPersentaseQcAttribute(): float
    {
        if ($this->qty_po <= 0) {
            return 0.0;
        }

        return min(100.0, round(($this->total_qty_qc / $this->qty_po) * 100, 1));
    }

    public function getPersentaseKirimAttribute(): float
    {
        if ($this->qty_po <= 0) {
            return 0.0;
        }

        return min(100.0, round(($this->total_qty_kirim / $this->qty_po) * 100, 1));
    }

    public function getHasPendingSaAttribute(): bool
    {
        return $this->pending_sa_count > 0;
    }

    public function getPendingSaCountAttribute(): int
    {
        $items = $this->relationLoaded('qcItems') ? $this->qcItems : $this->qcItems()->get();

        return $items->filter(function (MonitoringKarungQc $qc) {
            return empty($qc->no_sa) || strtolower($qc->status_qc ?? '') === 'blm sa';
        })->count();
    }

    public function getCompletedSaCountAttribute(): int
    {
        $items = $this->relationLoaded('qcItems') ? $this->qcItems : $this->qcItems()->get();

        return $items->filter(function (MonitoringKarungQc $qc) {
            return ! empty($qc->no_sa) && strtolower($qc->status_qc ?? '') !== 'blm sa';
        })->count();
    }

    public function getSelisihQcKirimAttribute(): int
    {
        return (int) $this->total_qty_qc - (int) $this->total_qty_kirim;
    }

    /**
     * Recalculate and update the monitoring status dynamically.
     *
     * Rules:
     * - 'Selesai': Jika total pengiriman telah memenuhi alokasi PO (total_qty_kirim >= qty_po > 0),
     *              DAN seluruh tahapan QC telah memiliki nomor SA SAP (tidak ada pending SA, completed_sa_count > 0).
     * - 'Terkendala': Jika user menandai terkendala secara spesifik atau ada QC berstatus Ditolak.
     * - 'Aktif': Jika masih dalam proses pengiriman atau masih menunggu penerbitan SA SAP.
     */
    public function recalculateStatus(bool $save = true): string
    {
        if ($this->status === 'Terkendala') {
            $hasRejected = $this->qcItems()->where('status_qc', 'like', '%tolak%')->exists();
            if ($hasRejected) {
                return 'Terkendala';
            }
        }

        $isDeliveryComplete = $this->qty_po > 0 && $this->total_qty_kirim >= $this->qty_po;
        $isSaComplete = ! $this->has_pending_sa && $this->completed_sa_count > 0;

        $newStatus = ($isDeliveryComplete && $isSaComplete) ? 'Selesai' : 'Aktif';

        if ($save && $this->status !== $newStatus) {
            $this->status = $newStatus;
            $this->saveQuietly();
        }

        return $newStatus;
    }
}
