<?php

namespace App\Models;

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
     */
    public static function parseRupiah(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) round((float) $value);
        }

        $withDot = str_replace(',', '.', (string) $value);
        if (is_numeric($withDot)) {
            return (float) round((float) $withDot);
        }

        $digits = preg_replace('/[^\d]/', '', (string) $value);

        return $digits !== '' ? (float) $digits : null;
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
            // Tipe material yang kosong/null otomatis diisi "Non Stock"
            $record->material_type = self::normalizeMaterialType($record->material_type);

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
                if ($do && in_array($do->status, ['GRS', 'RDTV'])) {
                    $hasGrs = $do->grsRdtvItems()->whereHas('grsRdtv', fn ($q) => $q->where('category', 'GRS'))->exists();
                    $hasRdtv = $do->grsRdtvItems()->whereHas('grsRdtv', fn ($q) => $q->where('category', 'RDTV'))->exists();
                    if (! $hasGrs && ! $hasRdtv) {
                        $latestQc = $do->qcHistories()->latest()->first();
                        $do->status = $latestQc && $latestQc->status === 'Kembali' ? 'Kembali dari ISTEK' : 'Diterima';
                        if (in_array($do->delay_reason, ['RDTV', 'GRS'])) {
                            $do->delay_reason = null;
                            $do->delay_notes = null;
                        }
                        $do->save();
                    }
                }
            }
        });
    }
}
