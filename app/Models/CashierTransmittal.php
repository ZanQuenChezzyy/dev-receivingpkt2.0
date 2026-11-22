<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'transmittal_no',
    'sent_date',
    'destination',
    'status',
    'notes',
    'created_by',
    'printed_at',
])]
class CashierTransmittal extends Model
{
    use HasFactory;

    public const DEFAULT_DESTINATION = 'Department Akuntansi / Dept. Akuntansi';

    public const STATUS_OPEN = 'Terbuka';

    public const STATUS_LOCKED = 'Terkunci';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_date' => 'date',
            'printed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (CashierTransmittal $transmittal): void {
            $transmittal->invoicingReceipts()->update([
                'cashier_transmittal_id' => null,
            ]);
        });
    }

    public function invoicingReceipts(): HasMany
    {
        return $this->hasMany(InvoicingReceipt::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    /**
     * Membuat nomor transmittal kasir berurutan per tanggal kirim (contoh: TR-KSR/20261122/001).
     */
    public static function generateTransmittalNo(Carbon|string $sentDate): string
    {
        $date = $sentDate instanceof Carbon ? $sentDate : Carbon::parse($sentDate);
        $prefix = 'TR-KSR/'.$date->format('Ymd').'/';

        $existingNumbers = self::query()
            ->where('transmittal_no', 'like', $prefix.'%')
            ->pluck('transmittal_no');

        $maxSequence = 0;
        foreach ($existingNumbers as $number) {
            $suffix = substr((string) $number, strlen($prefix));
            if (ctype_digit($suffix)) {
                $maxSequence = max($maxSequence, (int) $suffix);
            }
        }

        return $prefix.str_pad((string) ($maxSequence + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Mencari Transmittal Kasir berstatus Terbuka pada tanggal kirim yang sama agar pengiriman
     * dari beberapa PIC otomatis bergabung ke satu transmittal, atau membuat batch baru bila belum ada.
     */
    public static function findOrCreateOpenForDate(
        Carbon|string $sentDate,
        ?int $userId = null,
        bool $forceNewBatch = false,
        ?string $notes = null,
    ): self {
        $dateString = ($sentDate instanceof Carbon ? $sentDate : Carbon::parse($sentDate))->toDateString();

        return DB::transaction(function () use ($dateString, $userId, $forceNewBatch, $notes): self {
            if (! $forceNewBatch) {
                $existingOpen = self::query()
                    ->whereDate('sent_date', $dateString)
                    ->where('status', self::STATUS_OPEN)
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if ($existingOpen !== null) {
                    return $existingOpen;
                }
            }

            return self::create([
                'transmittal_no' => self::generateTransmittalNo($dateString),
                'sent_date' => $dateString,
                'destination' => self::DEFAULT_DESTINATION,
                'status' => self::STATUS_OPEN,
                'notes' => $notes,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Memasukkan sekumpulan invoice GRS ke Transmittal Kasir pada tanggal kirim yang dipilih.
     *
     * @param  Collection<int, InvoicingReceipt>|array<int, InvoicingReceipt>  $receipts
     * @return array{
     *     transmittal: ?self,
     *     attached_count: int,
     *     skipped_rdtv_count: int
     * }
     */
    public static function attachReceipts(
        Collection|array $receipts,
        Carbon|string $sentDate,
        ?int $userId = null,
        bool $forceNewBatch = false,
    ): array {
        $collection = $receipts instanceof Collection ? $receipts : collect($receipts);
        $grsRecords = $collection
            ->filter(fn (InvoicingReceipt $record): bool => $record->jenis !== 'RDTV')
            ->values();

        $skippedRdtvCount = $collection->count() - $grsRecords->count();

        if ($grsRecords->isEmpty()) {
            return [
                'transmittal' => null,
                'attached_count' => 0,
                'skipped_rdtv_count' => $skippedRdtvCount,
            ];
        }

        $dateString = ($sentDate instanceof Carbon ? $sentDate : Carbon::parse($sentDate))->toDateString();

        $transmittal = DB::transaction(function () use ($grsRecords, $dateString, $userId, $forceNewBatch): self {
            $transmittal = self::findOrCreateOpenForDate($dateString, $userId, $forceNewBatch);

            foreach ($grsRecords as $record) {
                $record->update([
                    'cashier_transmittal_id' => $transmittal->id,
                    'tanggal_kirim_kasir' => $dateString,
                    'status' => $record->status === 'Selesai' ? 'Selesai' : 'Terkirim Kasir',
                ]);
            }

            return $transmittal;
        });

        return [
            'transmittal' => $transmittal,
            'attached_count' => $grsRecords->count(),
            'skipped_rdtv_count' => $skippedRdtvCount,
        ];
    }

    /**
     * Menarik seluruh invoice GRS pada tanggal kirim tertentu yang belum memiliki Transmittal Kasir.
     *
     * @return array{
     *     transmittal: ?self,
     *     attached_count: int
     * }
     */
    public static function syncUnassignedReceiptsForDate(
        Carbon|string $sentDate,
        ?int $userId = null,
        bool $forceNewBatch = false,
    ): array {
        $dateString = ($sentDate instanceof Carbon ? $sentDate : Carbon::parse($sentDate))->toDateString();

        $unassigned = InvoicingReceipt::query()
            ->whereDate('tanggal_kirim_kasir', $dateString)
            ->whereNull('cashier_transmittal_id')
            ->where(function ($query): void {
                $query->whereNull('jenis')
                    ->orWhere('jenis', '!=', 'RDTV');
            })
            ->orderBy('id')
            ->get();

        if ($unassigned->isEmpty()) {
            return [
                'transmittal' => null,
                'attached_count' => 0,
            ];
        }

        $result = self::attachReceipts($unassigned, $dateString, $userId, $forceNewBatch);

        return [
            'transmittal' => $result['transmittal'],
            'attached_count' => $result['attached_count'],
        ];
    }
}
