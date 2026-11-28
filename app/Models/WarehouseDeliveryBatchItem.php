<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseDeliveryBatchItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_delivery_batch_id',
        'posting_date',
        'material_code',
        'description',
        'po_number',
        'item_no',
        'ref_doc_103',
        'mat_doc_105',
        'doc_year',
        'quantity',
        'uom',
        'amount',
        'storage_location',
        'plant',
        'vendor_code',
        'reference_do_no',
        'storage_text',
        'status',
        'delivery_order_receipt_id',
        'delivery_order_receipt_detail_id',
        'warehouse_transmittal_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'posting_date' => 'date',
            'quantity' => 'decimal:3',
            'amount' => 'decimal:2',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(WarehouseDeliveryBatch::class, 'warehouse_delivery_batch_id');
    }

    public function deliveryOrderReceipt(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrderReceipt::class);
    }

    public function deliveryOrderReceiptDetail(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrderReceiptDetail::class);
    }

    public function warehouseTransmittal(): BelongsTo
    {
        return $this->belongsTo(WarehouseTransmittal::class);
    }

    /**
     * Membuat kunci logis unik satu baris transaksi SAP MB51 (MatDoc 105 + Tahun + PO + Item + Kode Material).
     */
    public static function makeLogicalKey(
        ?string $matDoc105,
        ?string $poNumber,
        string|int|null $itemNo,
        ?string $materialCode,
        ?string $docYear = null,
    ): string {
        $doc = strtoupper(trim((string) $matDoc105));
        $year = trim((string) $docYear);
        $po = strtoupper(trim((string) $poNumber));
        $rawItem = trim((string) $itemNo);
        $item = $rawItem !== '' ? (ltrim($rawItem, '0') ?: '0') : '';
        $mat = strtoupper(trim((string) $materialCode));

        return "{$doc}|{$year}|{$po}|{$item}|{$mat}";
    }

    /**
     * Mengembalikan kunci logis unik untuk record item MB51 ini.
     */
    public function logicalKey(): string
    {
        $year = trim((string) $this->doc_year);
        if ($year === '' && $this->posting_date) {
            $year = $this->posting_date->format('Y');
        }

        return static::makeLogicalKey(
            matDoc105: $this->mat_doc_105,
            poNumber: $this->po_number,
            itemNo: $this->item_no,
            materialCode: $this->material_code,
            docYear: $year,
        );
    }

    /**
     * Skor kelengkapan data SAP untuk memilih 1 record kanonik saat terdapat duplikat lintas batch.
     */
    public function richnessScore(): int
    {
        $score = 0;

        if (filled($this->ref_doc_103)) {
            $score += 4;
        }
        if (filled($this->delivery_order_receipt_detail_id)) {
            $score += 3;
        }
        if (filled($this->storage_location)) {
            $score += 2;
        }
        if (filled($this->delivery_order_receipt_id)) {
            $score += 2;
        }
        if (filled($this->reference_do_no)) {
            $score += 1;
        }

        return $score;
    }
}
