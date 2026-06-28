<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
// use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

#[Fillable([
    'monitoring_npk_id',
    'monitoring_chemical_id',
    'delivery_oder_no',
    'received_date',
    'received_by',
    'created_by',
    'source_type',
    'stage',
    'document_code',
    'status',
    'post_103',
    'qr_103_code',
    'receipt_mode',
    'dof_number',
    'dof_date',
    'is_physically_received',
    'physical_received_date',
    'delay_reason',
    'delay_notes',
    'pending_date',
    'pending_resolved_date',
])]
class DeliveryOrderReceipt extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_date' => 'date',
            'post_103' => 'datetime',
            'dof_date' => 'date',
            'is_physically_received' => 'boolean',
            'physical_received_date' => 'date',
            'pending_date' => 'datetime',
            'pending_resolved_date' => 'datetime',
        ];
    }

    public function transmittalItems(): HasMany
    {
        return $this->hasMany(TransmittalItem::class);
    }

    public function transmittals(): BelongsToMany
    {
        return $this->belongsToMany(Transmittal::class, 'transmittal_items');
    }

    public function qcHistories(): HasMany
    {
        return $this->hasMany(QcHistory::class);
    }

    public function monitoringNpk(): BelongsTo
    {
        return $this->belongsTo(MonitoringNpk::class);
    }

    public function monitoringChemical(): BelongsTo
    {
        return $this->belongsTo(MonitoringChemical::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveryOrderReceiptDetails(): HasMany
    {
        return $this->hasMany(DeliveryOrderReceiptDetail::class);
    }

    public function grsRdtvItems(): HasMany
    {
        return $this->hasMany(GrsRdtvItem::class);
    }

    public function delayLogs(): HasMany
    {
        return $this->hasMany(DeliveryOrderReceiptDelayLog::class);
    }

    protected static function booted()
    {
        static::updating(function ($record) {
            // Auto-fill delay_notes if it's empty when delay_reason is set
            if ($record->isDirty('delay_reason') && !empty($record->delay_reason) && empty($record->delay_notes)) {
                $record->delay_notes = match ($record->delay_reason) {
                    'PO Belum Confirm' => 'Menunggu konfirmasi lebih lanjut dari pihak pengadaan terkait status PO.',
                    'Barang Diambil User Langsung (Tanpa Monitor)' => 'Barang telah diambil secara langsung oleh user ke lapangan sehingga proses monitoring terlewat.',
                    'Fisik Kelebihan Kirim (Over-delivery)' => 'Terdapat selisih di mana kuantitas fisik barang yang datang lebih banyak daripada yang tertera di dokumen.',
                    'Lainnya' => 'Penundaan karena alasan lain (catatan tidak disertakan oleh user).',
                    default => 'Proses tertunda.',
                };
            }
        });

        static::updated(function ($record) {
            // Check if delay_reason or delay_notes was changed
            if ($record->wasChanged('delay_reason') || $record->wasChanged('delay_notes')) {
                // If it's not simply clearing the reason, log it
                if (!empty($record->delay_reason)) {
                    $record->delayLogs()->create([
                        'delay_reason' => $record->delay_reason,
                        'delay_notes' => $record->delay_notes,
                        'created_by' => Auth::id() ?? $record->created_by,
                    ]);
                } else {
                    // If delay_reason is dirty and now empty, it means the pending status was cleared
                    $record->delayLogs()->create([
                        'delay_reason' => 'Penundaan Selesai (Clear)',
                        'delay_notes' => 'Status pending telah dihapus / diselesaikan. Proses penerimaan dilanjutkan.',
                        'created_by' => Auth::id() ?? $record->created_by,
                    ]);
                }
            }
        });
    }
}
