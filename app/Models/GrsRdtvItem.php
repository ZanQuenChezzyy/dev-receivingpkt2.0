<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GrsRdtvItem extends Model
{
    protected $fillable = [
        'grs_rdtv_id',
        'delivery_order_receipt_id',
        'document_code',
        'file_path',
        'status',
        'reason',
    ];

    public function grsRdtv()
    {
        return $this->belongsTo(GrsRdtv::class);
    }

    public function deliveryOrderReceipt()
    {
        return $this->belongsTo(DeliveryOrderReceipt::class);
    }

    public function invoicingReceipt()
    {
        return $this->hasOne(InvoicingReceipt::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (GrsRdtvItem $item) {
            // 1. Delete physical file from storage if exists
            if (! empty($item->file_path) && Storage::disk('public')->exists($item->file_path)) {
                try {
                    Storage::disk('public')->delete($item->file_path);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            // 2. Delete linked InvoicingReceipt(s) created for this GRS/RDTV item
            InvoicingReceipt::where('grs_rdtv_item_id', $item->id)->each(function (InvoicingReceipt $inv) {
                $inv->grs_rdtv_item_id = null;
                $inv->delete();
            });

            // 3. Reset DeliveryOrderReceipt status and delay reason
            $do = $item->deliveryOrderReceipt;
            if ($do) {
                // Check if DO has any other remaining GRS or RDTV items (excluding this item)
                $remainingGrs = $do->grsRdtvItems()
                    ->where('id', '!=', $item->id)
                    ->whereHas('grsRdtv', fn ($q) => $q->where('category', 'GRS'))
                    ->count();

                $remainingRdtv = $do->grsRdtvItems()
                    ->where('id', '!=', $item->id)
                    ->whereHas('grsRdtv', fn ($q) => $q->where('category', 'RDTV'))
                    ->count();

                if ($remainingGrs > 0) {
                    $do->status = 'GRS';
                } elseif ($remainingRdtv > 0) {
                    $do->status = 'RDTV';
                } else {
                    $latestQc = $do->qcHistories()->latest()->first();
                    if ($latestQc) {
                        $do->status = match ($latestQc->status) {
                            'Kembali' => 'Kembali dari ISTEK',
                            'Kirim' => 'Pengajuan QC',
                            'Revisi' => 'Pending (Menunggu Pengajuan Ulang)',
                            default => 'Diterima',
                        };
                    } else {
                        $do->status = 'Diterima';
                    }

                    if (in_array($do->delay_reason, ['RDTV', 'GRS'])) {
                        $do->delay_reason = null;
                        $do->delay_notes = null;
                    }
                }

                $do->save();
            }
        });
    }
}
