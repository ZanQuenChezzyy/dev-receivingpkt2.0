<?php

namespace App\Models;

use Database\Factories\MonitoringKarungDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringKarungDelivery extends Model
{
    /** @use HasFactory<MonitoringKarungDeliveryFactory> */
    use HasFactory;

    protected $fillable = [
        'monitoring_karung_id',
        'delivery_order_receipt_id',
        'invoicing_receipt_id',
        'tahap_kirim',
        'qty_kirim',
        'no_invoice',
        'no_do',
        'no_surat',
        'qty_supply',
        'qty_surat',
        'tanggal_penerimaan',
        'keterangan',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'qty_kirim' => 'integer',
            'qty_supply' => 'integer',
            'qty_surat' => 'integer',
            'tanggal_penerimaan' => 'date',
            'sort_order' => 'integer',
        ];
    }

    public function monitoringKarung(): BelongsTo
    {
        return $this->belongsTo(MonitoringKarung::class);
    }

    protected static function booted(): void
    {
        static::saved(function (MonitoringKarungDelivery $delivery): void {
            $delivery->monitoringKarung?->recalculateStatus();
        });

        static::deleted(function (MonitoringKarungDelivery $delivery): void {
            $delivery->monitoringKarung?->recalculateStatus();
        });
    }

    public function deliveryOrderReceipt(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrderReceipt::class);
    }

    public function invoicingReceipt(): BelongsTo
    {
        return $this->belongsTo(InvoicingReceipt::class);
    }
}
