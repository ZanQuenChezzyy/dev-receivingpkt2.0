<?php

namespace App\Models;

use Database\Factories\MonitoringKarungQcFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringKarungQc extends Model
{
    /** @use HasFactory<MonitoringKarungQcFactory> */
    use HasFactory;

    protected $fillable = [
        'monitoring_karung_id',
        'tahap_qc',
        'qty_qc',
        'no_dof_daan',
        'tgl_permintaan',
        'no_sik',
        'tgl_sik',
        'no_surat_qc',
        'status_qc',
        'file_istek',
        'no_bapp',
        'no_sa',
        'drafter',
        'keterangan',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'qty_qc' => 'integer',
            'tgl_permintaan' => 'date',
            'tgl_sik' => 'date',
            'sort_order' => 'integer',
        ];
    }

    public function monitoringKarung(): BelongsTo
    {
        return $this->belongsTo(MonitoringKarung::class);
    }

    protected static function booted(): void
    {
        static::saved(function (MonitoringKarungQc $qc): void {
            $qc->monitoringKarung?->recalculateStatus();
        });

        static::deleted(function (MonitoringKarungQc $qc): void {
            $qc->monitoringKarung?->recalculateStatus();
        });
    }
}
