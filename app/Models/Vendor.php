<?php

namespace App\Models;

use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

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

    /**
     * Menormalkan nama perusahaan dengan menghapus awalan/akhiran badan hukum (PT, CV, UD, PD, Tbk)
     * dan merapikan spasi/tanda baca untuk pencocokan Master Vendor yang akurat.
     */
    public static function normalizeCompanyName(?string $name): string
    {
        $clean = trim((string) preg_replace('/\s+/', ' ', (string) $name));
        if ($clean === '' || $clean === '-') {
            return '';
        }

        $clean = trim((string) preg_replace('/^(?:PT|CV|UD|PD)\.?\s+/i', '', $clean));
        $clean = trim((string) preg_replace('/,?\s*(?:TBK\.?)$/i', '', $clean));
        $clean = trim((string) preg_replace('/,?\s*(?:PT|CV|UD|PD)\.?$/i', '', $clean));
        $clean = trim((string) preg_replace('/,?\s*(?:TBK\.?)$/i', '', $clean));
        $clean = trim($clean, " \t\n\r\0\x0B,.-");

        return trim((string) preg_replace('/\s+/', ' ', $clean));
    }

    /**
     * Mencari Master Vendor terbaik secara deterministik dengan prioritas ketat:
     * 1. Kecocokan tepat `vendor_number`
     * 2. Kecocokan tepat nama (raw atau setelah normalisasi badan hukum)
     * 3. Substring tunggal tanpa ambiguitas (hanya jika tepat 1 kandidat cocok)
     * 4. Reverse-contain aman (diurutkan dari nama terpanjang, batas kata utuh, minimal 8 karakter / 2 kata)
     *
     * @param  array<int, string|null>  $nameCandidates
     */
    public static function findBestMatch(?string $vendorNumber = null, array $nameCandidates = []): ?self
    {
        if (! Schema::hasTable('vendors')) {
            return null;
        }

        $cleanNumber = trim((string) $vendorNumber);
        if ($cleanNumber !== '' && $cleanNumber !== '-') {
            $byNumber = self::query()->where('vendor_number', $cleanNumber)->first();
            if ($byNumber) {
                return $byNumber;
            }
        }

        $uniqueNames = array_values(array_unique(array_filter(
            array_map(static fn (?string $n): string => trim((string) preg_replace('/\s+/', ' ', (string) $n)), $nameCandidates),
            static fn (string $n): bool => $n !== '' && $n !== '-',
        )));

        if ($uniqueNames === []) {
            return null;
        }

        // 2. Exact match (raw & normalized) terlebih dahulu sebelum fuzzy
        foreach ($uniqueNames as $rawName) {
            $normalized = self::normalizeCompanyName($rawName);
            $exact = self::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($rawName)])
                ->first();

            if ($exact) {
                return $exact;
            }

            if ($normalized !== '' && strtolower($normalized) !== strtolower($rawName)) {
                $exactNorm = self::query()
                    ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($normalized)])
                    ->first();

                if ($exactNorm) {
                    return $exactNorm;
                }
            }
        }

        // 3. Bandingkan nama ter-normalisasi terhadap kandidat potensial di database (mis. "PT. ABC" vs "ABC, PT.")
        foreach ($uniqueNames as $rawName) {
            $normalized = self::normalizeCompanyName($rawName);
            if (strlen($normalized) < 4) {
                continue;
            }

            $likeMatches = self::query()
                ->where('name', 'like', "%{$normalized}%")
                ->get();

            foreach ($likeMatches as $candidate) {
                if (strcasecmp(self::normalizeCompanyName($candidate->name), $normalized) === 0) {
                    return $candidate;
                }
            }

            // 4. Terima substring tunggal hanya bila tidak ambigu (tepat 1 vendor cocok) dan panjang >= 6
            if (strlen($normalized) >= 6 && $likeMatches->count() === 1) {
                return $likeMatches->first();
            }
        }

        // 5. Reverse-contain aman (untuk kasus PDF punya kata nyasar di depan/belakang nama vendor):
        //    urutkan dari nama vendor terpanjang, wajib batas kata utuh, dan minimal 8 karakter atau >= 2 kata
        foreach ($uniqueNames as $rawName) {
            $normalized = self::normalizeCompanyName($rawName);
            if (strlen($normalized) < 8) {
                continue;
            }

            $allVendors = self::query()
                ->whereRaw('LENGTH(name) >= 5')
                ->orderByRaw('LENGTH(name) DESC')
                ->get();

            foreach ($allVendors as $candidate) {
                $vNorm = self::normalizeCompanyName($candidate->name);
                if ($vNorm === '') {
                    continue;
                }

                $wordCount = count(preg_split('/\s+/', $vNorm) ?: []);
                if (strlen($vNorm) < 8 && $wordCount < 2) {
                    continue;
                }

                $pattern = '/(?:^|[\s,.-])'.preg_quote($vNorm, '/').'(?:$|[\s,.-])/i';
                if (preg_match($pattern, $normalized) === 1) {
                    return $candidate;
                }
            }
        }

        return null;
    }
}
