<?php

namespace App\Models;

use Database\Factories\InvoicingReceiptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'grs_rdtv_item_id',
    'delivery_order_receipt_id',
    'purchase_order_issued_id',
    'vendor_id',
    'pic_id',
    'tanggal_terima_grs',
    'jenis',
    'no_grs',
    'no_rdtv',
    'no_po',
    'no_do',
    'padi',
    'material_type',
    'vendor_name',
    'deskripsi',
    'email_vendor',
    'tanggal_info_grs',
    'no_invoice',
    'faktur_pajak',
    'nama_barang',
    'qty_penagihan',
    'qty_po',
    'uoi',
    'subtotal_exclude_ppn',
    'subtotal_include_ppn',
    'tanggal_terima_koreksi_invoice',
    'tanggal_kirim_kasir',
    'lama_pekerjaan',
    'keterangan',
    'dokumen_verifikasi_grs',
    'dokumen_invoice',
    'dokumen_kelengkapan',
    'status',
])]
class InvoicingReceipt extends Model
{
    /** @use HasFactory<InvoicingReceiptFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tanggal_terima_grs' => 'date',
            'tanggal_info_grs' => 'date',
            'tanggal_terima_koreksi_invoice' => 'date',
            'tanggal_kirim_kasir' => 'date',
            'qty_penagihan' => 'float',
            'qty_po' => 'float',
            'subtotal_exclude_ppn' => 'float',
            'subtotal_include_ppn' => 'float',
            'lama_pekerjaan' => 'integer',
            'dokumen_kelengkapan' => 'array',
        ];
    }

    public function grsRdtvItem(): BelongsTo
    {
        return $this->belongsTo(GrsRdtvItem::class);
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

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    protected function deliveryDatePo(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->relationLoaded('purchaseOrderIssued') && $this->purchaseOrderIssued) {
                    return $this->purchaseOrderIssued->delivery_date_po;
                }

                if ($this->purchase_order_issued_id) {
                    return $this->purchaseOrderIssued?->delivery_date_po;
                }

                if ($this->no_po && $this->no_po !== '-') {
                    return PurchaseOrderIssued::where('purchase_order_no', $this->no_po)->value('delivery_date_po');
                }

                return null;
            }
        );
    }

    protected static function booted(): void
    {
        static::saving(function (InvoicingReceipt $record) {
            // Auto hitung Subtotal Include PPN: rumus = Exclude + (Exclude * 11%)
            if ($record->subtotal_exclude_ppn !== null) {
                if ($record->subtotal_include_ppn === null || $record->isDirty('subtotal_exclude_ppn')) {
                    $exclude = (float) $record->subtotal_exclude_ppn;
                    $record->subtotal_include_ppn = round($exclude + ($exclude * 0.11), 2);
                }
            }

            // Auto hitung Lama Pekerjaan
            if ($record->tanggal_terima_koreksi_invoice && $record->tanggal_kirim_kasir) {
                $start = Carbon::parse($record->tanggal_terima_koreksi_invoice);
                $end = Carbon::parse($record->tanggal_kirim_kasir);
                $record->lama_pekerjaan = (int) $start->diffInDays($end);
            } elseif ($record->tanggal_terima_koreksi_invoice && ! $record->tanggal_kirim_kasir) {
                $start = Carbon::parse($record->tanggal_terima_koreksi_invoice);
                $record->lama_pekerjaan = (int) $start->diffInDays(now());
            }
        });
    }
}
