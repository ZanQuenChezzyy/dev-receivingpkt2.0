<?php

namespace App\Services;

use App\Models\InvoicingReceipt;
use App\Models\Setting;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\Carbon;

class EmailTemplateRenderer
{
    public const KEY_AUTO_SEND_ENABLED = 'grs_email_auto_send_enabled';

    public const KEY_TEST_MODE_ENABLED = 'grs_email_test_mode_enabled';

    public const KEY_TEST_MODE_EMAIL = 'grs_email_test_mode_email';

    public const KEY_GRS_SUBJECT = 'grs_email_grs_subject';

    public const KEY_GRS_BODY = 'grs_email_grs_body';

    public const KEY_RDTV_SUBJECT = 'grs_email_rdtv_subject';

    public const KEY_RDTV_BODY = 'grs_email_rdtv_body';

    /**
     * Daftar placeholder variabel dinamis beserta penjelasannya untuk halaman editor Filament.
     *
     * @return array<string, string>
     */
    public static function availablePlaceholders(): array
    {
        return [
            '{vendor_name}' => 'Nama Vendor',
            '{no_po}' => 'Nomor Purchase Order (PO)',
            '{no_do}' => 'Nomor DO / Surat Jalan',
            '{no_grs}' => 'Nomor GRS (105)',
            '{no_rdtv}' => 'Nomor RDTV (124)',
            '{jenis_dokumen}' => 'Jenis Dokumen (GRS / RDTV)',
            '{nama_barang}' => 'Nama / Deskripsi Barang',
            '{tanggal_terima_grs}' => 'Tanggal Terima GRS / RDTV',
            '{tanggal_penerimaan}' => 'Tanggal Penerimaan Barang',
            '{pic_name}' => 'Nama PIC Invoice',
            '{keterangan}' => 'Keterangan / Catatan (termasuk alasan RDTV)',
            '{tanggal_hari_ini}' => 'Tanggal Pengiriman Email',
            '{link_tracking}' => 'Tautan Halaman Lacak Status PO Publik',
        ];
    }

    public static function defaultGrsSubject(): string
    {
        return 'Pemberitahuan Verifikasi Penerimaan Barang (GRS {no_grs}) - PO {no_po}';
    }

    public static function defaultGrsBody(): string
    {
        return <<<'HTML'
<p>Kepada Yth. <strong>{vendor_name}</strong>,</p>
<p>Dengan hormat,</p>
<p>Bersama ini kami sampaikan dokumen <strong>Goods Receipt Slip (GRS)</strong> beserta <strong>Surat Verifikasi Penerimaan Barang</strong> untuk pengiriman dengan rincian berikut:</p>
<ul>
    <li><strong>Nomor PO:</strong> {no_po}</li>
    <li><strong>Nomor GRS (105):</strong> {no_grs}</li>
    <li><strong>Nomor DO / Surat Jalan:</strong> {no_do}</li>
    <li><strong>Deskripsi Barang:</strong> {nama_barang}</li>
    <li><strong>Tanggal Terima GRS:</strong> {tanggal_terima_grs}</li>
</ul>
<p>Terlampir kami sertakan <strong>2 berkas dokumen</strong> (Dokumen GRS dan Dokumen Verifikasi GRS) sebagai dasar proses penagihan invoice. Status dokumen dan penagihan juga dapat dipantau melalui portal: <a href="{link_tracking}">{link_tracking}</a>.</p>
<p>Terima kasih atas kerja samanya.</p>
<p>Hormat kami,<br><strong>Tim Receiving Jasa — PT Pupuk Kalimantan Timur</strong><br>PIC: {pic_name}</p>
HTML;
    }

    public static function defaultRdtvSubject(): string
    {
        return 'Pemberitahuan Verifikasi Penolakan Barang (RDTV {no_rdtv}) - PO {no_po}';
    }

    public static function defaultRdtvBody(): string
    {
        return <<<'HTML'
<p>Kepada Yth. <strong>{vendor_name}</strong>,</p>
<p>Dengan hormat,</p>
<p>Bersama ini kami sampaikan dokumen <strong>Return Delivery to Vendor (RDTV)</strong> beserta <strong>Surat Verifikasi Penolakan Barang</strong> untuk pengiriman dengan rincian berikut:</p>
<ul>
    <li><strong>Nomor PO:</strong> {no_po}</li>
    <li><strong>Nomor RDTV (124):</strong> {no_rdtv}</li>
    <li><strong>Nomor DO / Surat Jalan:</strong> {no_do}</li>
    <li><strong>Deskripsi Barang:</strong> {nama_barang}</li>
    <li><strong>Tanggal Terima RDTV:</strong> {tanggal_terima_grs}</li>
    <li><strong>Catatan / Keterangan:</strong> {keterangan}</li>
</ul>
<p>Terlampir kami sertakan <strong>2 berkas dokumen</strong> (Dokumen RDTV dan Dokumen Verifikasi RDTV) sebagai acuan tindak lanjut pengambilan atau penggantian barang.</p>
<p>Terima kasih atas perhatian dan kerja samanya.</p>
<p>Hormat kami,<br><strong>Tim Receiving Jasa — PT Pupuk Kalimantan Timur</strong><br>PIC: {pic_name}</p>
HTML;
    }

    /**
     * Memeriksa apakah toggle pengiriman email otomatis di halaman Pengaturan sedang aktif.
     */
    public function isAutoSendEnabled(): bool
    {
        $raw = $this->getSetting(self::KEY_AUTO_SEND_ENABLED, '1');

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Memeriksa apakah Mode Uji Coba (pengalihan semua email ke email tes) sedang aktif.
     * Jika belum pernah disimpan di tabel settings, fallback ke keberadaan ZIMBRA_TEST_OVERRIDE_TO di .env.
     */
    public function isTestModeEnabled(): bool
    {
        $stored = $this->getRawSetting(self::KEY_TEST_MODE_ENABLED);

        if ($stored !== null) {
            return filter_var($stored, FILTER_VALIDATE_BOOLEAN);
        }

        return trim((string) config('services.zimbra.test_override_to', '')) !== '';
    }

    /**
     * Mengembalikan alamat email uji coba yang dikonfigurasi (prioritas settings DB, fallback .env).
     */
    public function getTestModeEmail(): string
    {
        $stored = $this->getRawSetting(self::KEY_TEST_MODE_EMAIL);

        if ($stored !== null && trim($stored) !== '') {
            return trim($stored);
        }

        return trim((string) config('services.zimbra.test_override_to', ''));
    }

    /**
     * Mengembalikan alamat email tujuan pengalih jika Mode Uji Coba aktif, atau null jika mode normal (langsung ke vendor).
     */
    public function resolveEffectiveTestOverrideTo(): ?string
    {
        if (! $this->isTestModeEnabled()) {
            return null;
        }

        $email = $this->getTestModeEmail();

        return $email !== '' ? $email : null;
    }

    /**
     * Mengambil seluruh konfigurasi template saat ini untuk diisi ke form Filament.
     *
     * @return array{
     *     auto_send_enabled: bool,
     *     test_mode_enabled: bool,
     *     test_mode_email: string,
     *     grs_subject: string,
     *     grs_body: string,
     *     rdtv_subject: string,
     *     rdtv_body: string
     * }
     */
    public function getSettingsFormData(): array
    {
        return [
            'auto_send_enabled' => $this->isAutoSendEnabled(),
            'test_mode_enabled' => $this->isTestModeEnabled(),
            'test_mode_email' => $this->getTestModeEmail(),
            'grs_subject' => $this->getSetting(self::KEY_GRS_SUBJECT, self::defaultGrsSubject()),
            'grs_body' => $this->getSetting(self::KEY_GRS_BODY, self::defaultGrsBody()),
            'rdtv_subject' => $this->getSetting(self::KEY_RDTV_SUBJECT, self::defaultRdtvSubject()),
            'rdtv_body' => $this->getSetting(self::KEY_RDTV_BODY, self::defaultRdtvBody()),
        ];
    }

    /**
     * Mengonversi nilai RichEditor Filament v5 (baik HTML string maupun array TipTap/ProseMirror) menjadi HTML string aman.
     */
    public function normalizeRichContentToHtml(mixed $value, string $fallback = ''): string
    {
        if (is_array($value)) {
            $html = trim((string) RichContentRenderer::make($value)->toHtml());

            return $html !== '' ? $html : $fallback;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed !== '' ? $trimmed : $fallback;
        }

        return $fallback;
    }

    /**
     * Menyimpan pengaturan template dari form Filament ke tabel settings.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveSettingsFormData(array $data): void
    {
        $pairs = [
            self::KEY_AUTO_SEND_ENABLED => ! empty($data['auto_send_enabled']) ? '1' : '0',
            self::KEY_TEST_MODE_ENABLED => array_key_exists('test_mode_enabled', $data)
                ? (! empty($data['test_mode_enabled']) ? '1' : '0')
                : ($this->isTestModeEnabled() ? '1' : '0'),
            self::KEY_TEST_MODE_EMAIL => array_key_exists('test_mode_email', $data)
                ? trim((string) ($data['test_mode_email'] ?? ''))
                : $this->getTestModeEmail(),
            self::KEY_GRS_SUBJECT => trim((string) ($data['grs_subject'] ?? '')) ?: self::defaultGrsSubject(),
            self::KEY_GRS_BODY => $this->normalizeRichContentToHtml($data['grs_body'] ?? null, self::defaultGrsBody()),
            self::KEY_RDTV_SUBJECT => trim((string) ($data['rdtv_subject'] ?? '')) ?: self::defaultRdtvSubject(),
            self::KEY_RDTV_BODY => $this->normalizeRichContentToHtml($data['rdtv_body'] ?? null, self::defaultRdtvBody()),
        ];

        foreach ($pairs as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    /**
     * Merender subjek dan isi HTML untuk satu InvoicingReceipt (mendukung custom override per pengiriman).
     *
     * @return array{subject: string, body_html: string, wrapped_html: string, text: string}
     */
    public function renderForReceipt(
        InvoicingReceipt $receipt,
        ?string $customSubject = null,
        ?string $customBodyHtml = null,
    ): array {
        $isRdtv = strtoupper((string) $receipt->jenis) === 'RDTV';

        $subjectTemplate = filled($customSubject)
            ? $customSubject
            : ($isRdtv
                ? $this->getSetting(self::KEY_RDTV_SUBJECT, self::defaultRdtvSubject())
                : $this->getSetting(self::KEY_GRS_SUBJECT, self::defaultGrsSubject()));

        $bodyTemplate = filled($customBodyHtml)
            ? $customBodyHtml
            : ($isRdtv
                ? $this->getSetting(self::KEY_RDTV_BODY, self::defaultRdtvBody())
                : $this->getSetting(self::KEY_GRS_BODY, self::defaultGrsBody()));

        $replacements = $this->buildReplacementsForReceipt($receipt);

        $subject = $this->applyPlaceholders($subjectTemplate, $replacements, escapeHtml: false);
        $bodyHtml = $this->applyPlaceholders($bodyTemplate, $replacements, escapeHtml: true);
        $wrappedHtml = $this->wrapInCorporateLayout($bodyHtml, $isRdtv ? 'RDTV' : 'GRS');

        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'wrapped_html' => $wrappedHtml,
            'text' => $this->htmlToPlainText($bodyHtml),
        ];
    }

    /**
     * Merender pratinjau template menggunakan contoh data realistis untuk tampilan Preview di Filament.
     *
     * @return array{subject: string, wrapped_html: string}
     */
    public function renderPreview(string $jenis, string $subjectTemplate, string $bodyTemplate): array
    {
        $isRdtv = strtoupper($jenis) === 'RDTV';
        $sample = [
            '{vendor_name}' => 'PT Contoh Mitra Industri',
            '{no_po}' => '5300058418',
            '{no_do}' => 'SJ/2026/11/0042',
            '{no_grs}' => $isRdtv ? '-' : '5007712345',
            '{no_rdtv}' => $isRdtv ? '5007798765' : '-',
            '{jenis_dokumen}' => $isRdtv ? 'RDTV' : 'GRS',
            '{nama_barang}' => 'Sparepart Pompa Sentrifugal & Gasket Flange 4 Inch',
            '{tanggal_terima_grs}' => now()->format('d/m/Y'),
            '{tanggal_penerimaan}' => now()->subDays(2)->format('d/m/Y'),
            '{pic_name}' => 'Staf Receiving Jasa',
            '{keterangan}' => $isRdtv ? 'Spesifikasi material tidak sesuai standar QC ISTEK' : '-',
            '{tanggal_hari_ini}' => now()->format('d/m/Y'),
            '{link_tracking}' => $this->trackingUrl(),
        ];

        $subject = $this->applyPlaceholders($subjectTemplate, $sample, escapeHtml: false);
        $bodyHtml = $this->applyPlaceholders($bodyTemplate, $sample, escapeHtml: true);

        return [
            'subject' => $subject,
            'wrapped_html' => $this->wrapInCorporateLayout($bodyHtml, $isRdtv ? 'RDTV' : 'GRS'),
        ];
    }

    /**
     * Membungkus konten email dengan tata letak email korporat PKT yang bersih dan kompatibel dengan seluruh klien email.
     */
    public function wrapInCorporateLayout(string $bodyHtml, string $jenis = 'GRS', ?string $testBannerHtml = null): string
    {
        $accentColor = strtoupper($jenis) === 'RDTV' ? '#DC2626' : '#F47920';
        $badgeLabel = strtoupper($jenis) === 'RDTV'
            ? 'VERIFIKASI PENOLAKAN BARANG (RDTV 124)'
            : 'VERIFIKASI PENERIMAAN BARANG (GRS 105)';

        $bannerSection = $testBannerHtml ? $testBannerHtml : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0;padding:24px 12px;background-color:#f3f6fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1e293b;line-height:1.6;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:640px;margin:0 auto;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="height:4px;background:linear-gradient(90deg,#0054A6 0%,{$accentColor} 100%);"></td>
        </tr>
        <tr>
            <td style="padding:20px 28px 14px 28px;border-bottom:1px solid #f1f5f9;">
                <div style="font-size:11px;font-weight:700;letter-spacing:0.06em;color:{$accentColor};text-transform:uppercase;">{$badgeLabel}</div>
                <div style="font-size:16px;font-weight:700;color:#0f172a;margin-top:4px;">PT Pupuk Kalimantan Timur — Receiving Jasa</div>
            </td>
        </tr>
        {$bannerSection}
        <tr>
            <td style="padding:22px 28px;font-size:14px;color:#1e293b;">
                {$bodyHtml}
            </td>
        </tr>
        <tr>
            <td style="padding:14px 28px;background-color:#f8fafc;border-top:1px solid #f1f5f9;font-size:12px;color:#64748b;">
                Email ini dikirim melalui sistem Receiving PKT v2.0. Mohon pastikan kedua berkas lampiran PDF telah diterima dengan lengkap.
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * @return array<string, string>
     */
    private function buildReplacementsForReceipt(InvoicingReceipt $receipt): array
    {
        $receipt->loadMissing(['pic', 'vendor', 'grsRdtvItem']);

        $isRdtv = strtoupper((string) $receipt->jenis) === 'RDTV';
        $namaBarang = trim((string) ($receipt->nama_barang ?: $receipt->deskripsi ?: '-'));
        $keterangan = trim((string) ($receipt->keterangan ?: $receipt->grsRdtvItem?->reason ?: '-'));

        return [
            '{vendor_name}' => trim((string) ($receipt->vendor_name ?: $receipt->vendor?->name ?: 'Mitra Vendor')),
            '{no_po}' => trim((string) ($receipt->no_po ?: '-')),
            '{no_do}' => trim((string) ($receipt->no_do ?: '-')),
            '{no_grs}' => trim((string) ($receipt->no_grs ?: '-')),
            '{no_rdtv}' => trim((string) ($receipt->no_rdtv ?: '-')),
            '{jenis_dokumen}' => $isRdtv ? 'RDTV' : 'GRS',
            '{nama_barang}' => $namaBarang,
            '{tanggal_terima_grs}' => $this->formatDate($receipt->tanggal_terima_grs),
            '{tanggal_penerimaan}' => $this->formatDate($receipt->tanggal_penerimaan),
            '{pic_name}' => trim((string) ($receipt->pic?->name ?: 'Tim Receiving Jasa')),
            '{keterangan}' => $keterangan,
            '{tanggal_hari_ini}' => now()->format('d/m/Y'),
            '{link_tracking}' => $this->trackingUrl(),
        ];
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function applyPlaceholders(string $template, array $replacements, bool $escapeHtml): string
    {
        $search = array_keys($replacements);
        $replace = array_map(
            static fn (string $value): string => $escapeHtml ? e($value) : $value,
            array_values($replacements),
        );

        return str_replace($search, $replace, $template);
    }

    private function formatDate(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('d/m/Y');
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value)->format('d/m/Y');
            } catch (\Throwable) {
                return $value;
            }
        }

        return '-';
    }

    private function trackingUrl(): string
    {
        try {
            return route('vendor.po.tracking');
        } catch (\Throwable) {
            return rtrim((string) config('app.url', 'https://dev.receivingpkt.com'), '/').'/tracking-po';
        }
    }

    private function getSetting(string $key, string $default): string
    {
        $value = $this->getRawSetting($key);

        return ($value !== null && trim($value) !== '') ? $value : $default;
    }

    private function getRawSetting(string $key): ?string
    {
        try {
            $value = Setting::query()->where('key', $key)->value('value');

            return $value !== null ? (string) $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function htmlToPlainText(string $html): string
    {
        $normalized = preg_replace('/<\s*br\s*\/?>/i', "\n", $html) ?? $html;
        $normalized = preg_replace('/<\/(p|div|li|tr|h[1-6])>/i', "\n", $normalized) ?? $normalized;
        $text = html_entity_decode(strip_tags($normalized), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
