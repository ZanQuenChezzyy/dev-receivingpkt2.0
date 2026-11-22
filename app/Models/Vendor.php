<?php

namespace App\Models;

use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'vendor_number',
    'city',
    'street',
    'street2',
    'street3',
    'street4',
    'telephone_1',
    'telephone_2',
    'fax_number',
    'salesperson',
    'telp_salesperson',
    'email',
])]
class Vendor extends Model
{
    /** @use HasFactory<VendorFactory> */
    use HasFactory;

    /**
     * Memecah dan membersihkan daftar email dari berbagai format (array, JSON, koma, titik koma, spasi, atau baris baru).
     *
     * @return array<int, string>
     */
    public static function parseEmails(mixed $value): array
    {
        if (blank($value)) {
            return [];
        }

        $items = [];
        if (is_array($value)) {
            $items = $value;
        } else {
            $raw = trim((string) $value);
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $items = $decoded;
            } else {
                $items = [$raw];
            }
        }

        $emails = [];
        $seen = [];

        foreach ($items as $item) {
            if (! is_scalar($item)) {
                continue;
            }

            $parts = preg_split('/[\s,;]+/', trim((string) $item)) ?: [];
            foreach ($parts as $part) {
                $clean = trim($part, " \t\n\r\0\x0B<>,;");
                if ($clean === '' || $clean === '-') {
                    continue;
                }

                $lower = strtolower($clean);
                if (! isset($seen[$lower])) {
                    $seen[$lower] = true;
                    $emails[] = $clean;
                }
            }
        }

        return $emails;
    }

    /**
     * Interact with the vendor's email attribute.
     * Mengembalikan array email untuk multi-email support,
     * serta mendukung format JSON maupun string (koma / titik koma / spasi).
     *
     * @return Attribute<array<int, string>, array<int, string>|string|null>
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): array => self::parseEmails($value),
            set: function (mixed $value): ?string {
                $cleaned = self::parseEmails($value);

                return ! empty($cleaned) ? json_encode($cleaned) : null;
            }
        );
    }

    public function getPrimaryEmailAttribute(): ?string
    {
        $emails = $this->email;

        return is_array($emails) && ! empty($emails) ? $emails[0] : null;
    }

    public function getEmailStringAttribute(): ?string
    {
        $emails = $this->email;

        return is_array($emails) && ! empty($emails) ? implode(', ', $emails) : null;
    }

    public function invoicingReceipts(): HasMany
    {
        return $this->hasMany(InvoicingReceipt::class);
    }
}
