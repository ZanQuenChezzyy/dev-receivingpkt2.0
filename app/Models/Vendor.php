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
     * Interact with the vendor's email attribute.
     * Mengembalikan array email untuk multi-email support,
     * serta mendukung format JSON maupun string (koma / titik koma).
     *
     * @return Attribute<array<string>, array<string>|string|null>
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            get: function ($value): array {
                if (blank($value)) {
                    return [];
                }

                if (is_array($value)) {
                    return array_values(array_filter(array_map('trim', $value)));
                }

                $decoded = json_decode((string) $value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return array_values(array_filter(array_map('trim', $decoded)));
                }

                $parts = preg_split('/[,;]+/', (string) $value);

                return array_values(array_filter(array_map('trim', (array) $parts)));
            },
            set: function ($value): ?string {
                if (blank($value)) {
                    return null;
                }

                if (is_array($value)) {
                    $cleaned = array_values(array_filter(array_map('trim', $value)));

                    return ! empty($cleaned) ? json_encode($cleaned) : null;
                }

                $parts = preg_split('/[,;]+/', (string) $value);
                $cleaned = array_values(array_filter(array_map('trim', (array) $parts)));

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
