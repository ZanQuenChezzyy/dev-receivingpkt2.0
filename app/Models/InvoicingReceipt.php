<?php

namespace App\Models;

use App\Services\BadgeMilestoneService;
use Database\Factories\InvoicingReceiptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

#[Fillable([
    'grs_rdtv_item_id',
    'cashier_transmittal_id',
    'delivery_order_receipt_id',
    'purchase_order_issued_id',
    'vendor_id',
    'pic_id',
    'tanggal_terima_grs',
    'delivery_date',
    'tanggal_penerimaan',
    'tanggal_po',
    'jenis',
    'kategori_barang',
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
    'tanggal_invoice',
    'faktur_pajak',
    'tanggal_faktur_pajak',
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

    public const DEFAULT_MATERIAL_TYPE = 'Non Stock';

    /**
     * @var list<string>
     */
    private const EMPTY_PLACEHOLDERS = ['none', 'null', '-', 'kosong'];

    /**
     * Daftar pilihan Tipe Material standar pada modul Invoicing & PIC Material.
     *
     * @return array<string, string>
     */
    public static function materialTypeOptions(): array
    {
        return [
            'ZSP' => 'ZSP',
            'ZFP' => 'ZFP',
            'ZPM' => 'ZPM',
            'ZRM' => 'ZRM',
            'ZSM' => 'ZSM',
            self::DEFAULT_MATERIAL_TYPE => self::DEFAULT_MATERIAL_TYPE,
        ];
    }

    /**
     * Memeriksa apakah nilai teks bukan kosong atau placeholder standar.
     */
    public static function isMeaningfulText(?string $value): bool
    {
        $clean = trim((string) $value);

        return $clean !== '' && ! in_array(strtolower($clean), self::EMPTY_PLACEHOLDERS, true);
    }

    /**
     * Memeriksa apakah material tergolong Packaging Material / Karung (ZPM) berdasarkan kode material,
     * deskripsi barang, atau keterdaftarannya pada Monitoring Karung.
     *
     * @param  string|array<int, string|null>|null  $materialCodes
     * @param  string|array<int, string|null>|null  $descriptions
     */
    public static function isKarungPackagingMaterial(
        ?string $rawType = null,
        string|array|null $materialCodes = null,
        string|array|null $descriptions = null,
        ?string $noPo = null,
    ): bool {
        if (strtoupper(trim((string) $rawType)) === 'ZPM') {
            return true;
        }

        // 1. Kode material SAP 7 digit berawalan 500 (500xxxx) — 100% eksklusif Packaging Material (ZPM)
        $codes = is_array($materialCodes) ? $materialCodes : [$materialCodes];
        foreach ($codes as $code) {
            $cleanCode = trim((string) $code);
            if ($cleanCode !== '' && preg_match('/^500\d{4}$/', $cleanCode) === 1) {
                return true;
            }
        }

        // 2. Deskripsi khas material Karung / Kantong / Sack / Benang Jahit Karung (hindari jasa inspeksi & geotextile)
        $descs = is_array($descriptions) ? $descriptions : [$descriptions];
        foreach ($descs as $desc) {
            $cleanDesc = trim((string) $desc);
            if ($cleanDesc === '') {
                continue;
            }

            if (preg_match('/^(?:PEMERIKSAAN|JASA|INSPEKSI|TESTING|KALIBRASI)\b|GEOTEXTILE/i', $cleanDesc) === 1) {
                continue;
            }

            if (
                preg_match('/^(?:SACK|KARUNG|KANTONG|JUMBO\s*BAG|INNER\s*BAG|BENANG\s*JAHIT(?:\s+[A-Z]+)?\s*:\s*KARUNG)\b/i', $cleanDesc) === 1
                || preg_match('/\b(?:SACK\s*:|KARUNG\s*(?:UREA|NPK|PHONSKA|NITREA|AMONIAK|50\s*KG|25\s*KG|1000\s*KG)|KANTONG\s*(?:UREA|NPK|PHONSKA|NITREA|JUMBO))\b/i', $cleanDesc) === 1
            ) {
                return true;
            }
        }

        // 3. Nomor PO terdaftar di tabel monitoring_karungs
        $cleanPo = trim((string) $noPo);
        if ($cleanPo !== '' && $cleanPo !== '-') {
            try {
                if (MonitoringKarung::query()->where('no_po', $cleanPo)->exists()) {
                    return true;
                }
            } catch (\Throwable) {
                // Abaikan jika tabel monitoring_karungs tidak tersedia (mis. sebagian unit test SQLite)
            }
        }

        return false;
    }

    /**
     * Menentukan tipe material akhir: mempertahankan tipe eksplisit (ZSP/ZRM/ZSM/ZPM/ZFP),
     * dan bila kosong/Non Stock namun teridentifikasi sebagai material Karung, otomatis diklasifikasikan ke "ZPM".
     *
     * @param  string|array<int, string|null>|null  $materialCodes
     * @param  string|array<int, string|null>|null  $descriptions
     */
    public static function resolveMaterialType(
        ?string $rawType,
        string|array|null $materialCodes = null,
        string|array|null $descriptions = null,
        ?string $noPo = null,
    ): string {
        $normalized = self::normalizeMaterialType($rawType);

        if ($normalized === self::DEFAULT_MATERIAL_TYPE && self::isKarungPackagingMaterial($rawType, $materialCodes, $descriptions, $noPo)) {
            return 'ZPM';
        }

        return $normalized;
    }

    /**
     * Menormalkan nilai Tipe Material; jika kosong, null, atau placeholder tidak valid maka dikembalikan sebagai "Non Stock".
     */
    public static function normalizeMaterialType(?string $rawType): string
    {
        $clean = trim((string) $rawType);

        if (
            ! self::isMeaningfulText($clean)
            || in_array(strtolower($clean), ['non stock', 'non-stock', 'nonstock'], true)
        ) {
            return self::DEFAULT_MATERIAL_TYPE;
        }

        return $clean;
    }

    /**
     * Parsing input mata uang Rupiah / angka dari masked string menjadi float integer bulat.
     * Menangani format bertitik ribuan Indonesia (misal "946.000", "1.050", "1.050.060", "946.000,50"),
     * string desimal database (misal "946000.00"), maupun tipe int/float tanpa salah membaca titik ribuan sebagai desimal.
     */
    public static function parseRupiah(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) round((float) $value);
        }

        $clean = trim((string) $value);
        $clean = preg_replace('/^\s*(?:rp\.?|idr)\s*/i', '', $clean) ?? $clean;
        $clean = trim($clean);

        if ($clean === '' || $clean === '-') {
            return null;
        }

        // Angka bulat murni tanpa pemisah (misal "946000", "0")
        if (preg_match('/^-?\d+$/', $clean) === 1) {
            return (float) $clean;
        }

        // Format ribuan Indonesia bertitik dengan opsional desimal koma (misal "946.000", "1.050", "1.050.060", "946.000,50")
        if (preg_match('/^-?[1-9]\d{0,2}(\.\d{3})+(,\d{1,2})?$/', $clean) === 1) {
            $normalized = str_replace('.', '', $clean);
            $normalized = str_replace(',', '.', $normalized);

            return (float) round((float) $normalized);
        }

        // Format nol dengan titik ribuan (misal "0.000")
        if (preg_match('/^-?0(\.000)+(,0{1,2})?$/', $clean) === 1) {
            return 0.0;
        }

        // Format string desimal database / standar 1-2 angka di belakang titik (misal "946000.00", "1050.50")
        if (preg_match('/^-?\d+\.\d{1,2}$/', $clean) === 1) {
            return (float) round((float) $clean);
        }

        // Format desimal koma Indonesia tanpa titik ribuan (misal "946000,50")
        if (preg_match('/^-?\d+,\d{1,2}$/', $clean) === 1) {
            return (float) round((float) str_replace(',', '.', $clean));
        }

        // Format ribuan internasional berkoma (misal "946,000", "1,050,060.00")
        if (preg_match('/^-?[1-9]\d{0,2}(,\d{3})+(\.\d{1,2})?$/', $clean) === 1) {
            return (float) round((float) str_replace(',', '', $clean));
        }

        $isNegative = str_starts_with($clean, '-');
        $digits = preg_replace('/[^\d]/', '', $clean) ?? '';

        if ($digits === '') {
            return null;
        }

        $number = (float) $digits;

        return $isNegative ? -$number : $number;
    }

    /**
     * Memformat angka Rupiah menjadi string bertitik tanpa desimal (misal 131950662 -> "131.950.662").
     */
    public static function formatRupiah(mixed $value): ?string
    {
        $num = self::parseRupiah($value);

        return $num !== null ? number_format($num, 0, ',', '.') : null;
    }

    /**
     * Menentukan apakah record sudah memiliki data nomor invoice yang valid.
     */
    public function hasInvoiceData(): bool
    {
        return self::isMeaningfulText($this->no_invoice);
    }

    /**
     * Menjaga nilai subtotal_include_ppn eksplisit dari file impor agar tidak dihitung ulang.
     */
    public bool $preserveExplicitIncludePpn = false;

    protected function casts(): array
    {
        return [
            'tanggal_terima_grs' => 'date',
            'delivery_date' => 'date',
            'tanggal_penerimaan' => 'date',
            'tanggal_po' => 'date',
            'tanggal_info_grs' => 'date',
            'tanggal_invoice' => 'date',
            'tanggal_faktur_pajak' => 'date',
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

    public function cashierTransmittal(): BelongsTo
    {
        return $this->belongsTo(CashierTransmittal::class);
    }

    /**
     * Mengambil path berkas Dokumen GRS fisik langsung dari relasi master GRS/RDTV tanpa duplikasi kolom.
     */
    public function getDokumenGrsAttribute(): ?string
    {
        return $this->grsRdtvItem?->file_path;
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

    public function items(): HasMany
    {
        return $this->hasMany(InvoicingReceiptItem::class);
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(InvoicingEmailLog::class)->latest();
    }

    public function latestEmailLog(): HasOne
    {
        return $this->hasOne(InvoicingEmailLog::class)->latestOfMany();
    }

    /**
     * Cari nomor Delivery Order dari relasi penerimaan DO berdasarkan nomor PO.
     */
    public static function findDeliveryOrderNoForPo(?string $poNo): ?string
    {
        if (blank($poNo) || $poNo === '-') {
            return null;
        }

        $cleanPo = trim((string) $poNo);

        return DeliveryOrderReceipt::whereHas('deliveryOrderReceiptDetails.purchaseOrderIssued', function ($q) use ($cleanPo) {
            $q->where('purchase_order_no', $cleanPo);
        })->latest()->value('delivery_order_no');
    }

    /**
     * Sinkronisasi ringkasan kuantitas dan nilai dari relasi items ke kolom summary header.
     */
    public function syncSummaryFromItems(): void
    {
        if (! $this->relationLoaded('items')) {
            $this->load('items');
        }

        if ($this->items->isNotEmpty()) {
            $sumQty = (float) $this->items->sum('qty_penagihan');
            if ($sumQty > 0 || $this->qty_penagihan === null) {
                $this->qty_penagihan = $sumQty;
            }

            $sumQtyPo = (float) $this->items->sum('qty_po');
            if ($sumQtyPo > 0 || $this->qty_po === null) {
                $this->qty_po = $sumQtyPo;
            }

            if (blank($this->uoi) && filled($this->items->first()?->uoi)) {
                $this->uoi = $this->items->first()->uoi;
            }

            $itemNames = $this->items->pluck('nama_barang')->filter(fn ($n) => filled($n))->values();
            if ($itemNames->isNotEmpty()) {
                $this->nama_barang = $itemNames->unique()->implode(', ');

                if ($itemNames->count() > 1) {
                    $lines = [];
                    foreach ($itemNames as $idx => $name) {
                        $lines[] = ($idx + 1).'. '.trim($name);
                    }
                    $this->deskripsi = implode("\n", $lines);
                } elseif (blank($this->deskripsi)) {
                    $this->deskripsi = trim($itemNames->first());
                }
            }

            $itemGrs = $this->items->pluck('no_grs')->filter(fn ($g) => filled($g))->unique()->values();
            if ($itemGrs->isNotEmpty() && blank($this->no_grs)) {
                $this->no_grs = $itemGrs->implode(', ');
            }
        } elseif (filled($this->no_po) && $this->no_po !== '-') {
            $poDescriptions = PurchaseOrderIssued::where('purchase_order_no', trim($this->no_po))
                ->orderBy('item_no')
                ->pluck('description')
                ->filter(fn ($d) => filled($d))
                ->values();

            if ($poDescriptions->count() > 1) {
                $lines = [];
                foreach ($poDescriptions as $idx => $desc) {
                    $lines[] = ($idx + 1).'. '.trim($desc);
                }
                $this->deskripsi = implode("\n", $lines);
                if (blank($this->nama_barang)) {
                    $this->nama_barang = $poDescriptions->unique()->implode(', ');
                }
            } elseif ($poDescriptions->count() === 1 && blank($this->deskripsi)) {
                $this->deskripsi = trim($poDescriptions->first());
                if (blank($this->nama_barang)) {
                    $this->nama_barang = $this->deskripsi;
                }
            }
        }
    }

    protected function deliveryDatePo(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Utamakan Delivery Date yang diekstrak dari berkas GRS/RDTV yang diunggah
                if ($this->delivery_date) {
                    return $this->delivery_date;
                }

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
        static::created(function (InvoicingReceipt $record): void {
            if ($record->pic_id) {
                app(BadgeMilestoneService::class)->syncMilestonesForUsers([$record->pic_id]);
            }
        });

        static::updated(function (InvoicingReceipt $record): void {
            if ($record->wasChanged('pic_id') && $record->pic_id) {
                app(BadgeMilestoneService::class)->syncMilestonesForUsers([$record->pic_id]);
            }
        });

        static::saving(function (InvoicingReceipt $record) {
            // Tipe material yang kosong/null otomatis diklasifikasikan ke ZPM jika material Karung, selain itu "Non Stock"
            $record->material_type = self::resolveMaterialType(
                $record->material_type,
                null,
                array_filter([$record->nama_barang, $record->deskripsi]),
                $record->no_po,
            );

            // Auto hitung Subtotal Include PPN: rumus = Exclude + (Exclude * 11%)
            // Hanya dijalankan bila subtotal_include_ppn kosong atau exclude diubah tanpa mempertahankan include eksplisit.
            if ($record->subtotal_exclude_ppn !== null) {
                $shouldComputeInclude = $record->subtotal_include_ppn === null
                    || (! $record->preserveExplicitIncludePpn && $record->isDirty('subtotal_exclude_ppn') && ! $record->isDirty('subtotal_include_ppn'));

                if ($shouldComputeInclude) {
                    $exclude = (float) $record->subtotal_exclude_ppn;
                    $record->subtotal_include_ppn = round($exclude + ($exclude * 0.11));
                }
            }

            // Dokumen RDTV selesai sampai tahap Info GRS (info RDTV ke vendor)
            if ($record->jenis === 'RDTV') {
                if ($record->tanggal_info_grs !== null) {
                    $record->status = 'Selesai';
                } elseif (
                    $record->exists
                    && $record->isDirty('tanggal_info_grs')
                    && ! $record->isDirty('status')
                    && $record->getOriginal('status') === 'Selesai'
                ) {
                    $record->status = 'Menunggu Verifikasi';
                }
            }

            // Dokumen yang masih "Menunggu Verifikasi" tetapi data invoice-nya sudah ada otomatis menjadi "Selesai"
            $currentStatus = trim((string) $record->status);
            $isWaitingVerification = $currentStatus === '' || $currentStatus === 'Menunggu Verifikasi';
            if ($isWaitingVerification && $record->hasInvoiceData()) {
                $record->status = 'Selesai';
            }

            // Auto hitung Lama Pekerjaan
            if ($record->tanggal_terima_koreksi_invoice && $record->tanggal_kirim_kasir) {
                $start = Carbon::parse($record->tanggal_terima_koreksi_invoice);
                $end = Carbon::parse($record->tanggal_kirim_kasir);
                $record->lama_pekerjaan = (int) $start->diffInDays($end, absolute: true);
            } elseif ($record->jenis !== 'RDTV' && $record->tanggal_terima_koreksi_invoice && ! $record->tanggal_kirim_kasir) {
                $start = Carbon::parse($record->tanggal_terima_koreksi_invoice);
                $record->lama_pekerjaan = (int) $start->diffInDays(now(), absolute: true);
            }
        });

        static::deleting(function (InvoicingReceipt $record) {
            if ($record->grs_rdtv_item_id) {
                $item = GrsRdtvItem::find($record->grs_rdtv_item_id);
                if ($item) {
                    $record->grs_rdtv_item_id = null;
                    $item->delete();
                }
            } elseif ($record->delivery_order_receipt_id) {
                $do = $record->deliveryOrderReceipt;
                if ($do && in_array($do->status, ['GRS', 'RDTV'], true)) {
                    GrsRdtvItem::syncDeliveryOrderStatus($do);
                }
            }
        });
    }
}
