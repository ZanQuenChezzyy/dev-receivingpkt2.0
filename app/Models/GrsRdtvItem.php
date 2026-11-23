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

    /**
     * Menghasilkan URL publik dokumen dengan query versi untuk menghindari cache browser/CDN.
     */
    public function getDocumentUrlAttribute(): ?string
    {
        if (blank($this->file_path)) {
            return null;
        }

        $url = Storage::disk('public')->url($this->file_path);
        $version = $this->updated_at?->timestamp;

        return $version ? "{$url}?v={$version}" : $url;
    }

    /**
     * Menghapus berkas fisik dari storage hanya jika tidak dipakai oleh item GRS/RDTV lain.
     */
    public static function deletePhysicalFileSafely(?string $filePath, ?int $ignoreItemId = null): void
    {
        if (blank($filePath)) {
            return;
        }

        $stillReferenced = static::query()
            ->when($ignoreItemId !== null, fn ($q) => $q->where('id', '!=', $ignoreItemId))
            ->where('file_path', $filePath)
            ->exists();

        if ($stillReferenced) {
            return;
        }

        if (Storage::disk('public')->exists($filePath)) {
            try {
                Storage::disk('public')->delete($filePath);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * Menyelaraskan status dan catatan kendala DeliveryOrderReceipt sesuai item GRS/RDTV yang masih aktif.
     */
    public static function syncDeliveryOrderStatus(DeliveryOrderReceipt $do, ?int $ignoreItemId = null): void
    {
        $remainingGrs = $do->grsRdtvItems()
            ->when($ignoreItemId !== null, fn ($q) => $q->where('id', '!=', $ignoreItemId))
            ->whereHas('grsRdtv', fn ($q) => $q->where('category', 'GRS'))
            ->count();

        $remainingRdtv = $do->grsRdtvItems()
            ->when($ignoreItemId !== null, fn ($q) => $q->where('id', '!=', $ignoreItemId))
            ->whereHas('grsRdtv', fn ($q) => $q->where('category', 'RDTV'))
            ->count();

        if ($remainingGrs > 0) {
            $do->status = 'GRS';
            if ($do->delay_reason === 'RDTV') {
                $do->delay_reason = null;
                $do->delay_notes = null;
            }
        } elseif ($remainingRdtv > 0) {
            $do->status = 'RDTV';
        } else {
            $latestQc = $do->qcHistories()->latest('id')->first();
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

            if (in_array($do->delay_reason, ['RDTV', 'GRS'], true)) {
                $do->delay_reason = null;
                $do->delay_notes = null;
            }
        }

        $do->save();
    }

    protected static function booted(): void
    {
        static::deleting(function (GrsRdtvItem $item) {
            // 1. Delete physical file from storage safely (only if no other item shares the same path)
            static::deletePhysicalFileSafely($item->file_path, $item->id);

            // 2. Delete linked InvoicingReceipt(s) created for this GRS/RDTV item
            InvoicingReceipt::where('grs_rdtv_item_id', $item->id)->each(function (InvoicingReceipt $inv) {
                $inv->grs_rdtv_item_id = null;
                $inv->delete();
            });

            // 3. Reset DeliveryOrderReceipt status and delay reason deterministically
            $do = $item->deliveryOrderReceipt;
            if ($do) {
                static::syncDeliveryOrderStatus($do, $item->id);
            }
        });
    }
}
