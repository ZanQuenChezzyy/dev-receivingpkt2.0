<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringChemical extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_category',
        'qc_by',
        'do_number',
        'document_path',
        'received_by',
        'received_date',
        'doc_status',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_date' => 'date',
        ];
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function monitoringChemicalDetails(): HasMany
    {
        return $this->hasMany(MonitoringChemicalDetail::class);
    }

    public function isDone(): bool
    {
        if (
            empty($this->do_number) ||
            empty($this->received_date) ||
            empty($this->material_category)
        ) {
            return false;
        }

        if ($this->monitoringChemicalDetails()->count() === 0) {
            return false;
        }

        foreach ($this->monitoringChemicalDetails as $detail) {
            if (empty($detail->location_id)) {
                return false;
            }

            if ($this->material_category === 'Karung') {
                if (empty($detail->chemical_qc_tuv_id)) {
                    return false;
                }
            } elseif ($this->material_category === 'Chemical') {
                if (empty($detail->chemical_qc_tuv_id) || $detail->is_qty_tolerance === null || $detail->has_update_progress === null) {
                    return false;
                }
                
                if ($detail->has_update_progress) {
                    if (
                        empty($detail->tanggal_pengajuan_simala) ||
                        empty($detail->tanggal_pengambilan_sample) ||
                        empty($detail->tanggal_terbit_coa)
                    ) {
                        return false;
                    }
                }
            } elseif ($this->material_category === 'Lainnya') {
                if (empty($detail->chemical_qc_tuv_id) || $detail->is_qty_tolerance === null) {
                    return false;
                }
                
                if ($this->qc_by === 'PPE') {
                    if ($detail->has_update_progress === null) {
                        return false;
                    }
                    if ($detail->has_update_progress) {
                        if (
                            empty($detail->tanggal_pengajuan_simala) ||
                            empty($detail->tanggal_pengambilan_sample) ||
                            empty($detail->tanggal_terbit_coa)
                        ) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }
}
