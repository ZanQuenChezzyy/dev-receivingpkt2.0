<?php

namespace App\Services;

use App\Models\InvoicingReceipt;
use App\Models\Setting;
use App\Models\Vendor;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class EmailTemplateRenderer
{
    public const KEY_AUTO_SEND_ENABLED = 'grs_email_auto_send_enabled';

    public const KEY_TEST_MODE_ENABLED = 'grs_email_test_mode_enabled';

    public const KEY_TEST_MODE_EMAIL = 'grs_email_test_mode_email';

    public const KEY_CC_EMAILS = 'grs_email_cc_emails';

    public const DEFAULT_CC_EMAILS = 'receiving.barang@pupukkaltim.com';

    public const KEY_SENDER_NAME = 'grs_email_sender_name';

    public const KEY_HEADER_TITLE = 'grs_email_header_title';

    public const KEY_HEADER_BADGE_GRS = 'grs_email_header_badge_grs';

    public const KEY_HEADER_BADGE_RDTV = 'grs_email_header_badge_rdtv';

    public const KEY_FOOTER_TEXT = 'grs_email_footer_text';

    public const KEY_ACCENT_COLOR_GRS = 'grs_email_accent_color_grs';

    public const KEY_ACCENT_COLOR_RDTV = 'grs_email_accent_color_rdtv';

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

    public static function defaultSenderName(): string
    {
        $configured = trim((string) config('services.zimbra.from_name', ''));

        return $configured !== '' ? $configured : 'receiving jasa';
    }

    public static function defaultHeaderTitle(): string
    {
        return 'Receiving Jasa - PT. Pupuk Kalimantan Timur';
    }

    public static function defaultHeaderBadgeGrs(): string
    {
        return 'VERIFIKASI PENERIMAAN BARANG (GRS)';
    }

    public static function defaultHeaderBadgeRdtv(): string
    {
        return 'VERIFIKASI PENOLAKAN BARANG (RDTV)';
    }

    public static function defaultFooterText(): string
    {
        return 'Email ini dikirim melalui sistem Receiving PKT v2.0. Mohon pastikan kedua berkas lampiran PDF telah diterima dengan lengkap.';
    }

    public static function defaultAccentColorGrs(): string
    {
        return '#F47920';
    }

    public static function defaultAccentColorRdtv(): string
    {
        return '#DC2626';
    }

    public static function defaultGrsSubject(): string
    {
        return 'INFORMASI GRS {no_grs} - PO {no_po}';
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
</ul>
<p>Terlampir kami sertakan <strong>2 berkas dokumen</strong> (Dokumen GRS dan Dokumen Verifikasi GRS) sebagai dasar proses penagihan invoice. Status dokumen dan penagihan juga dapat dipantau melalui portal: <a href="{link_tracking}">Lacak Status PO {no_po}</a>.</p>
<p>Harap mengirimkan dokumen tagihan ke alamat PT Pupuk Kalimantan Timur (Dept PP&amp;P) paling lambat pada tanggal 20 di bulan terbitnya faktur pajak.<br>Harap mengirim softfilenya terlebih dahulu untuk saya cek kelengkapannya.</p>
<p><strong><em>Note : Surat Verifikasi Penerimaan Barang dan GRS Digital merupakan satu kesatuan, sehingga tanda tangan pada GRS tidak lagi diperlukan. Rekanan WAJIB melampirkan print out Surat Verifikasi Penerimaan Barang dan GRS saat melakukan penagihan.</em></strong></p>
<p>Demikian pemberitahuan ini disampaikan, atas kerjasamanya diucapkan terima kasih.</p>
<p>Hormat saya,<br>{pic_name}<br><strong>Staff Penerimaan Invoice Barang</strong><br>Dept. PP&amp;P</p>
HTML;
    }

    public static function defaultRdtvSubject(): string
    {
        return 'INFORMASI RDTV {no_rdtv} - PO {no_po}';
    }

    public static function defaultRdtvBody(): string
    {
        return <<<'HTML'
<p>Kepada Yth. <strong>{vendor_name}</strong>,</p>
<p>Dengan hormat,</p>
<p>Bersama ini kami sampaikan bahwa material Anda telah kami tolak, beserta <strong>Surat Verifikasi Penolakan Barang</strong> untuk pengiriman dengan rincian berikut:</p>
<ul>
    <li><strong>Nomor PO:</strong> {no_po}</li>
    <li><strong>Nomor RDTV (124):</strong> {no_rdtv}</li>
</ul>
<p>Terlampir kami sertakan <strong>2 berkas dokumen</strong> (Dokumen RDTV dan Dokumen Verifikasi RDTV), status dokumen serta alasan penolakan juga dapat dipantau melalui portal: <a href="{link_tracking}">Lacak Status PO {no_po}</a> atau dapat menghubungi WhatsApp dengan nomor <strong>081139008113</strong>.</p>
<p>Untuk koordinasi mengenai klarifikasi maupun pengambilan barang tolak dapat menghubungi bagian penerimaan barang melalui email <a href="mailto:receiving.barang@pupukkaltim.com">receiving.barang@pupukkaltim.com</a>.</p>
<p>Demikian pemberitahuan ini disampaikan, atas kerjasamanya diucapkan terima kasih.</p>
<p>Hormat saya,<br>{pic_name}<br><strong>Staff Penerimaan Invoice Barang</strong><br>Dept. PP&amp;P</p>
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
     * Mengembalikan string mentah alamat CC (default receiving.barang@pupukkaltim.com jika belum pernah disimpan).
     */
    public function getCcEmailsRaw(): string
    {
        $stored = $this->getRawSetting(self::KEY_CC_EMAILS);

        if ($stored !== null) {
            return trim($stored);
        }

        return self::DEFAULT_CC_EMAILS;
    }

    /**
     * Mengembalikan daftar alamat CC valid hasil parsing dari pengaturan.
     *
     * @return array<int, string>
     */
    public function resolveCcEmails(): array
    {
        return array_values(array_filter(
            Vendor::parseEmails($this->getCcEmailsRaw()),
            static fn (string $email): bool => (bool) filter_var($email, FILTER_VALIDATE_EMAIL),
        ));
    }

    /**
     * Mengembalikan nama pengirim (From Name) email dari pengaturan Filament atau fallback .env.
     */
    public function getSenderName(): string
    {
        return $this->getSetting(self::KEY_SENDER_NAME, self::defaultSenderName());
    }

    /**
     * Mengembalikan seluruh konfigurasi tampilan header, footer, dan warna aksen email.
     *
     * @param  array<string, mixed>  $overrides
     * @return array{
     *     sender_name: string,
     *     header_title: string,
     *     header_badge_grs: string,
     *     header_badge_rdtv: string,
     *     footer_text: string,
     *     accent_color_grs: string,
     *     accent_color_rdtv: string
     * }
     */
    public function getLayoutSettings(array $overrides = []): array
    {
        $pick = function (string $field, string $key, string $default) use ($overrides): string {
            if (array_key_exists($field, $overrides) && is_string($overrides[$field]) && trim($overrides[$field]) !== '') {
                return trim($overrides[$field]);
            }

            return $this->getSetting($key, $default);
        };

        return [
            'sender_name' => $pick('sender_name', self::KEY_SENDER_NAME, self::defaultSenderName()),
            'header_title' => $pick('header_title', self::KEY_HEADER_TITLE, self::defaultHeaderTitle()),
            'header_badge_grs' => $pick('header_badge_grs', self::KEY_HEADER_BADGE_GRS, self::defaultHeaderBadgeGrs()),
            'header_badge_rdtv' => $pick('header_badge_rdtv', self::KEY_HEADER_BADGE_RDTV, self::defaultHeaderBadgeRdtv()),
            'footer_text' => $pick('footer_text', self::KEY_FOOTER_TEXT, self::defaultFooterText()),
            'accent_color_grs' => $this->sanitizeHexColor(
                $pick('accent_color_grs', self::KEY_ACCENT_COLOR_GRS, self::defaultAccentColorGrs()),
                self::defaultAccentColorGrs(),
            ),
            'accent_color_rdtv' => $this->sanitizeHexColor(
                $pick('accent_color_rdtv', self::KEY_ACCENT_COLOR_RDTV, self::defaultAccentColorRdtv()),
                self::defaultAccentColorRdtv(),
            ),
        ];
    }

    /**
     * Mengambil seluruh konfigurasi template saat ini untuk diisi ke form Filament.
     *
     * @return array{
     *     auto_send_enabled: bool,
     *     test_mode_enabled: bool,
     *     test_mode_email: string,
     *     cc_emails: string,
     *     sender_name: string,
     *     header_title: string,
     *     header_badge_grs: string,
     *     header_badge_rdtv: string,
     *     footer_text: string,
     *     accent_color_grs: string,
     *     accent_color_rdtv: string,
     *     grs_subject: string,
     *     grs_body: string,
     *     rdtv_subject: string,
     *     rdtv_body: string
     * }
     */
    public function getSettingsFormData(): array
    {
        $layout = $this->getLayoutSettings();

        return [
            'auto_send_enabled' => $this->isAutoSendEnabled(),
            'test_mode_enabled' => $this->isTestModeEnabled(),
            'test_mode_email' => $this->getTestModeEmail(),
            'cc_emails' => $this->getCcEmailsRaw(),
            'sender_name' => $layout['sender_name'],
            'header_title' => $layout['header_title'],
            'header_badge_grs' => $layout['header_badge_grs'],
            'header_badge_rdtv' => $layout['header_badge_rdtv'],
            'footer_text' => $layout['footer_text'],
            'accent_color_grs' => $layout['accent_color_grs'],
            'accent_color_rdtv' => $layout['accent_color_rdtv'],
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
        $layout = $this->getLayoutSettings();

        $pairs = [
            self::KEY_AUTO_SEND_ENABLED => ! empty($data['auto_send_enabled']) ? '1' : '0',
            self::KEY_TEST_MODE_ENABLED => array_key_exists('test_mode_enabled', $data)
                ? (! empty($data['test_mode_enabled']) ? '1' : '0')
                : ($this->isTestModeEnabled() ? '1' : '0'),
            self::KEY_TEST_MODE_EMAIL => array_key_exists('test_mode_email', $data)
                ? trim((string) ($data['test_mode_email'] ?? ''))
                : $this->getTestModeEmail(),
            self::KEY_CC_EMAILS => array_key_exists('cc_emails', $data)
                ? trim((string) ($data['cc_emails'] ?? ''))
                : $this->getCcEmailsRaw(),
            self::KEY_SENDER_NAME => array_key_exists('sender_name', $data)
                ? (trim((string) ($data['sender_name'] ?? '')) ?: self::defaultSenderName())
                : $layout['sender_name'],
            self::KEY_HEADER_TITLE => array_key_exists('header_title', $data)
                ? (trim((string) ($data['header_title'] ?? '')) ?: self::defaultHeaderTitle())
                : $layout['header_title'],
            self::KEY_HEADER_BADGE_GRS => array_key_exists('header_badge_grs', $data)
                ? (trim((string) ($data['header_badge_grs'] ?? '')) ?: self::defaultHeaderBadgeGrs())
                : $layout['header_badge_grs'],
            self::KEY_HEADER_BADGE_RDTV => array_key_exists('header_badge_rdtv', $data)
                ? (trim((string) ($data['header_badge_rdtv'] ?? '')) ?: self::defaultHeaderBadgeRdtv())
                : $layout['header_badge_rdtv'],
            self::KEY_FOOTER_TEXT => array_key_exists('footer_text', $data)
                ? (trim((string) ($data['footer_text'] ?? '')) ?: self::defaultFooterText())
                : $layout['footer_text'],
            self::KEY_ACCENT_COLOR_GRS => array_key_exists('accent_color_grs', $data)
                ? $this->sanitizeHexColor((string) ($data['accent_color_grs'] ?? ''), self::defaultAccentColorGrs())
                : $layout['accent_color_grs'],
            self::KEY_ACCENT_COLOR_RDTV => array_key_exists('accent_color_rdtv', $data)
                ? $this->sanitizeHexColor((string) ($data['accent_color_rdtv'] ?? ''), self::defaultAccentColorRdtv())
                : $layout['accent_color_rdtv'],
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
     * @param  array<string, mixed>  $layoutOverrides
     * @return array{subject: string, wrapped_html: string}
     */
    public function renderPreview(
        string $jenis,
        string $subjectTemplate,
        string $bodyTemplate,
        array $layoutOverrides = [],
    ): array {
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
            '{link_tracking}' => $this->trackingUrl(new InvoicingReceipt(['no_po' => '5300058418'])),
        ];

        $subject = $this->applyPlaceholders($subjectTemplate, $sample, escapeHtml: false);
        $bodyHtml = $this->applyPlaceholders($bodyTemplate, $sample, escapeHtml: true);

        return [
            'subject' => $subject,
            'wrapped_html' => $this->wrapInCorporateLayout($bodyHtml, $isRdtv ? 'RDTV' : 'GRS', null, $layoutOverrides),
        ];
    }

    /**
     * Membungkus konten email dengan tata letak email korporat PKT yang bersih dan kompatibel dengan seluruh klien email.
     *
     * @param  array<string, mixed>  $layoutOverrides
     */
    public function wrapInCorporateLayout(
        string $bodyHtml,
        string $jenis = 'GRS',
        ?string $testBannerHtml = null,
        array $layoutOverrides = [],
    ): string {
        $isRdtv = strtoupper($jenis) === 'RDTV';
        $layout = $this->getLayoutSettings($layoutOverrides);

        $accentColor = $isRdtv ? $layout['accent_color_rdtv'] : $layout['accent_color_grs'];
        $badgeLabel = e($isRdtv ? $layout['header_badge_rdtv'] : $layout['header_badge_grs']);
        $headerTitle = e($layout['header_title']);
        $footerText = nl2br(e($layout['footer_text']));

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
                <div style="font-size:16px;font-weight:700;color:#0f172a;margin-top:4px;">{$headerTitle}</div>
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
                {$footerText}
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    private function sanitizeHexColor(string $color, string $fallback): string
    {
        $clean = trim($color);

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $clean) ? strtoupper($clean) : $fallback;
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

        $resolvedGrs = trim((string) ($receipt->no_grs ?: ($isRdtv ? ($receipt->no_rdtv ?: '-') : '-')));
        $resolvedRdtv = trim((string) ($receipt->no_rdtv ?: ($isRdtv ? ($receipt->no_grs ?: '-') : '-')));

        return [
            '{vendor_name}' => trim((string) ($receipt->vendor_name ?: $receipt->vendor?->name ?: 'Mitra Vendor')),
            '{no_po}' => trim((string) ($receipt->no_po ?: '-')),
            '{no_do}' => trim((string) ($receipt->no_do ?: '-')),
            '{no_grs}' => $resolvedGrs,
            '{no_rdtv}' => $resolvedRdtv,
            '{jenis_dokumen}' => $isRdtv ? 'RDTV' : 'GRS',
            '{nama_barang}' => $namaBarang,
            '{tanggal_terima_grs}' => $this->formatDate($receipt->tanggal_terima_grs),
            '{tanggal_penerimaan}' => $this->formatDate($receipt->tanggal_penerimaan),
            '{pic_name}' => trim((string) ($receipt->pic?->name ?: 'Tim Receiving Jasa')),
            '{keterangan}' => $keterangan,
            '{tanggal_hari_ini}' => now()->format('d/m/Y'),
            '{link_tracking}' => $this->trackingUrl($receipt),
        ];
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function applyPlaceholders(string $template, array $replacements, bool $escapeHtml): string
    {
        $search = [];
        $replace = [];

        foreach ($replacements as $token => $value) {
            $formatted = $escapeHtml ? e($value) : $value;
            $search[] = $token;
            $replace[] = $formatted;

            // Antisipasi TipTap/RichEditor yang kadang meng-encode "{...}" menjadi "%7B...%7D" di dalam atribut href
            $encodedToken = str_replace(['{', '}'], ['%7B', '%7D'], $token);
            if ($encodedToken !== $token) {
                $search[] = $encodedToken;
                $replace[] = $formatted;
                $search[] = strtolower($encodedToken);
                $replace[] = $formatted;
            }
        }

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

    private function trackingUrl(?InvoicingReceipt $receipt = null): string
    {
        $cleanPo = trim((string) ($receipt?->no_po ?? ''));

        try {
            if ($cleanPo !== '' && $cleanPo !== '-') {
                return URL::temporarySignedRoute(
                    'frontend.vendor.tracking',
                    now()->addDays(30),
                    [
                        'po' => $cleanPo,
                        'auto' => '1',
                    ],
                );
            }

            return route('frontend.vendor.tracking');
        } catch (\Throwable) {
            $base = rtrim((string) config('app.url', 'https://dev.receivingpkt.com'), '/').'/lacak-po-vendor';

            return ($cleanPo !== '' && $cleanPo !== '-')
                ? $base.'?po='.urlencode($cleanPo)
                : $base;
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
