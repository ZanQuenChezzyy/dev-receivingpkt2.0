<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
// use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable([
    'purchase_order_and_item',
    'material_type',
    'mrp_type',
    'purchase_order_no',
    'item_no',
    'material_code',
    'aac',
    'abc_indicator',
    'description',
    'qty_po',
    'uoi',
    'vendor_id',
    'vendor_name',
    'date_create',
    'delivery_date_po',
    'po_status',
    'incoterm',
    'currency',
    'net_price',
    'total_amount_in_lc',
    'requisitioner',
])]
class PurchaseOrderIssued extends Model
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
            'date_create' => 'date',
            'delivery_date_po' => 'date',
            'item_no' => 'integer',
            'qty_po' => 'float',
            'net_price' => 'float',
            'total_amount_in_lc' => 'float',
        ];
    }

    /**
     * Clean the "Pusat" word from vendor name and normalize whitespace.
     */
    public static function cleanVendorName(?string $vendorName): ?string
    {
        if ($vendorName === null) {
            return null;
        }

        $cleaned = (string) preg_replace('/pusat/i', '', $vendorName);
        $cleaned = (string) preg_replace('/\s+,/', ',', $cleaned);
        $cleaned = (string) preg_replace('/\s+/', ' ', $cleaned);

        return trim($cleaned);
    }

    /**
     * Match a given item name/description against a collection or array of PurchaseOrderIssued records.
     *
     * @param  Collection<int, self>|array<int, self>  $poRecords
     */
    public static function matchByDescription(?string $itemName, $poRecords): ?self
    {
        if (blank($itemName)) {
            return null;
        }

        $records = $poRecords instanceof Collection ? $poRecords : collect($poRecords);
        if ($records->isEmpty()) {
            return null;
        }

        // Clean name by stripping leading numbering / tags e.g. "1. ", "1) ", "[10] "
        $cleaned = trim((string) preg_replace('/^\s*(\d+[\.\)]|\[\d+\])\s*/', '', trim($itemName)));
        if ($cleaned === '') {
            return null;
        }

        // 1. Exact match (case-insensitive)
        foreach ($records as $po) {
            if (strcasecmp(trim((string) $po->description), $cleaned) === 0) {
                return $po;
            }
        }

        // 2. Normalized alphanumeric match
        $normClean = strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $cleaned));
        if ($normClean !== '') {
            foreach ($records as $po) {
                $normPo = strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', (string) $po->description));
                if ($normClean === $normPo) {
                    return $po;
                }
            }
        }

        // 3. Token-based overlap match
        $itemTokens = array_values(array_filter(preg_split('/[^a-zA-Z0-9]+/', strtolower($cleaned)) ?: []));
        $bestPo = null;
        $bestScore = 0.0;

        if (! empty($itemTokens)) {
            foreach ($records as $po) {
                $poTokens = array_values(array_filter(preg_split('/[^a-zA-Z0-9]+/', strtolower((string) $po->description)) ?: []));
                if (! empty($poTokens)) {
                    $intersect = count(array_intersect($itemTokens, $poTokens));
                    $score = $intersect / max(count($itemTokens), count($poTokens));
                    if ($score > $bestScore && $score >= 0.5) {
                        $bestScore = $score;
                        $bestPo = $po;
                    }
                }
            }
            if ($bestPo) {
                return $bestPo;
            }
        }

        // 4. Substring match
        if (strlen($normClean) >= 5) {
            foreach ($records as $po) {
                $normPo = strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', (string) $po->description));
                if ($normPo !== '' && (str_contains($normClean, $normPo) || str_contains($normPo, $normClean))) {
                    return $po;
                }
            }
        }

        // 5. Similarity match (>= 70%)
        $bestSim = 0.0;
        foreach ($records as $po) {
            similar_text($cleaned, (string) $po->description, $sim);
            if ($sim > $bestSim && $sim >= 70.0) {
                $bestSim = $sim;
                $bestPo = $po;
            }
        }

        return $bestPo;
    }

    protected function vendorName(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => self::cleanVendorName($value),
        );
    }

    public function monitoringNpks(): HasMany
    {
        return $this->hasMany(MonitoringNpk::class);
    }

    public function monitoringChemicals(): HasMany
    {
        return $this->hasMany(MonitoringChemical::class);
    }

    public function monitoringKarungs(): HasMany
    {
        return $this->hasMany(MonitoringKarung::class);
    }

    public function chemicalQcTuvs(): HasMany
    {
        return $this->hasMany(ChemicalQcTuv::class);
    }

    public function deliveryOrderReceiptDetails(): HasMany
    {
        return $this->hasMany(DeliveryOrderReceiptDetail::class);
    }

    public function invoicingReceipts(): HasMany
    {
        return $this->hasMany(InvoicingReceipt::class);
    }
}
