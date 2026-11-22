<?php

namespace App\Services;

use App\Models\InvoicingEmailLog;
use App\Models\InvoicingReceipt;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;

class InvoicingGrsEmailDispatcher
{
    public function __construct(
        public ZimbraMailService $zimbra,
        public EmailTemplateRenderer $renderer,
    ) {}

    /**
     * Memeriksa dan mengambil 2 berkas lampiran wajib (Dokumen GRS/RDTV + Dokumen Verifikasi GRS/RDTV).
     *
     * @return array{
     *     ok: bool,
     *     reason: ?string,
     *     attachments: array<int, array{type: string, path: string, storage_path: string, filename: string, size_bytes: int}>
     * }
     */
    public function resolveRequiredAttachments(InvoicingReceipt $receipt): array
    {
        $receipt->loadMissing('grsRdtvItem');

        $isRdtv = strtoupper((string) $receipt->jenis) === 'RDTV';
        $docLabel = $isRdtv ? 'RDTV' : 'GRS';
        $docCode = $this->sanitizeDocCode($isRdtv ? ($receipt->no_rdtv ?: $receipt->no_po) : ($receipt->no_grs ?: $receipt->no_po));

        $disk = Storage::disk('public');

        $slipRelativePath = trim((string) ($receipt->grsRdtvItem?->file_path ?? ''));
        $verifRelativePath = trim((string) ($receipt->dokumen_verifikasi_grs ?? ''));

        $missingParts = [];

        if ($slipRelativePath === '' || ! $disk->exists($slipRelativePath)) {
            $missingParts[] = "Dokumen {$docLabel}";
        }

        $verifIsInvalid = $verifRelativePath === ''
            || $verifRelativePath === $slipRelativePath
            || ! $disk->exists($verifRelativePath);

        if ($verifIsInvalid) {
            $missingParts[] = "Dokumen Verifikasi {$docLabel}";
        }

        if ($missingParts !== []) {
            return [
                'ok' => false,
                'reason' => 'Pengiriman ditahan: '.implode(' dan ', $missingParts)." belum lengkap (wajib 2 lampiran: {$docLabel} dan Verifikasi {$docLabel}).",
                'attachments' => [],
            ];
        }

        $slipFullPath = $disk->path($slipRelativePath);
        $verifFullPath = $disk->path($verifRelativePath);

        $slipExt = strtolower(pathinfo($slipRelativePath, PATHINFO_EXTENSION)) ?: 'pdf';
        $verifExt = strtolower(pathinfo($verifRelativePath, PATHINFO_EXTENSION)) ?: 'pdf';

        return [
            'ok' => true,
            'reason' => null,
            'attachments' => [
                [
                    'type' => $isRdtv ? 'rdtv' : 'grs',
                    'path' => $slipFullPath,
                    'storage_path' => $slipRelativePath,
                    'filename' => "{$docLabel}_{$docCode}.{$slipExt}",
                    'size_bytes' => is_file($slipFullPath) ? (int) filesize($slipFullPath) : 0,
                ],
                [
                    'type' => $isRdtv ? 'verifikasi_rdtv' : 'verifikasi_grs',
                    'path' => $verifFullPath,
                    'storage_path' => $verifRelativePath,
                    'filename' => "Verifikasi_{$docLabel}_{$docCode}.{$verifExt}",
                    'size_bytes' => is_file($verifFullPath) ? (int) filesize($verifFullPath) : 0,
                ],
            ],
        ];
    }

    /**
     * Mengambil daftar email vendor valid dari InvoicingReceipt (fallback ke master Vendor).
     *
     * @return array<int, string>
     */
    public function resolveVendorEmails(InvoicingReceipt $receipt): array
    {
        $receipt->loadMissing('vendor');

        $raw = filled($receipt->email_vendor) ? $receipt->email_vendor : $receipt->vendor?->email;
        $parsed = Vendor::parseEmails($raw);

        return array_values(array_filter(
            $parsed,
            static fn (string $email): bool => (bool) filter_var($email, FILTER_VALIDATE_EMAIL),
        ));
    }

    /**
     * Mengirim email secara otomatis setelah upload Verifikasi GRS/RDTV jika seluruh syarat terpenuhi.
     *
     * @return array{status: string, message: string, log: ?InvoicingEmailLog}
     */
    public function dispatchIfEligible(
        InvoicingReceipt $receipt,
        string $triggerSource = 'batch_ocr',
        ?int $userId = null,
    ): array {
        if (! (bool) config('services.zimbra.enabled', false) || ! $this->renderer->isAutoSendEnabled()) {
            return [
                'status' => 'disabled',
                'message' => 'Pengiriman email otomatis sedang nonaktif.',
                'log' => null,
            ];
        }

        if (! $this->zimbra->isConfigured()) {
            return [
                'status' => 'disabled',
                'message' => 'Kredensial Zimbra di server belum dikonfigurasi.',
                'log' => null,
            ];
        }

        $rendered = $this->renderer->renderForReceipt($receipt);
        $vendorEmails = $this->resolveVendorEmails($receipt);
        $ccEmails = $this->renderer->resolveCcEmails();

        return $this->executeDelivery(
            receipt: $receipt,
            intendedRecipients: $vendorEmails,
            intendedCc: $ccEmails,
            subject: $rendered['subject'],
            bodyHtml: $rendered['body_html'],
            triggerSource: $triggerSource,
            userId: $userId,
        );
    }

    /**
     * Mengirim atau mengirim ulang email secara manual dari modal aksi baris Invoice Barang.
     *
     * @param  array<int, string>  $recipients
     * @param  array<int, string>|null  $ccRecipients
     * @return array{status: string, message: string, log: ?InvoicingEmailLog}
     */
    public function sendManual(
        InvoicingReceipt $receipt,
        array $recipients,
        string $subject,
        string $bodyHtml,
        ?int $userId = null,
        ?array $ccRecipients = null,
    ): array {
        if (! $this->zimbra->isConfigured()) {
            return [
                'status' => 'failed',
                'message' => 'Kredensial Zimbra belum dikonfigurasi di server.',
                'log' => null,
            ];
        }

        $cleanRecipients = array_values(array_filter(
            Vendor::parseEmails($recipients),
            static fn (string $email): bool => (bool) filter_var($email, FILTER_VALIDATE_EMAIL),
        ));

        $cleanCc = $ccRecipients === null
            ? $this->renderer->resolveCcEmails()
            : array_values(array_filter(
                Vendor::parseEmails($ccRecipients),
                static fn (string $email): bool => (bool) filter_var($email, FILTER_VALIDATE_EMAIL),
            ));

        return $this->executeDelivery(
            receipt: $receipt,
            intendedRecipients: $cleanRecipients,
            intendedCc: $cleanCc,
            subject: trim($subject),
            bodyHtml: $bodyHtml,
            triggerSource: 'manual_resend',
            userId: $userId,
        );
    }

    /**
     * @param  array<int, string>  $intendedRecipients
     * @param  array<int, string>  $intendedCc
     * @return array{status: string, message: string, log: ?InvoicingEmailLog}
     */
    private function executeDelivery(
        InvoicingReceipt $receipt,
        array $intendedRecipients,
        array $intendedCc,
        string $subject,
        string $bodyHtml,
        string $triggerSource,
        ?int $userId,
    ): array {
        $jenis = strtoupper((string) $receipt->jenis) === 'RDTV' ? 'RDTV' : 'GRS';
        $actorId = $userId ?? Auth::id();

        // 1. Validasi ketat 2 lampiran (GRS/RDTV + Verifikasi GRS/RDTV sebagai satu kesatuan)
        $attachmentCheck = $this->resolveRequiredAttachments($receipt);
        if (! $attachmentCheck['ok']) {
            $log = InvoicingEmailLog::create([
                'invoicing_receipt_id' => $receipt->id,
                'sent_by' => $actorId,
                'jenis' => $jenis,
                'trigger_source' => $triggerSource,
                'recipients' => $intendedRecipients,
                'cc_recipients' => $intendedCc,
                'subject' => $subject,
                'body_html' => $bodyHtml,
                'attachments' => [],
                'status' => 'skipped',
                'error_message' => $attachmentCheck['reason'],
            ]);

            return [
                'status' => 'skipped',
                'message' => (string) $attachmentCheck['reason'],
                'log' => $log,
            ];
        }

        // 2. Validasi alamat email penerima (mengikuti toggle Mode Uji Coba di panel Filament / fallback .env)
        $overrideTo = $this->renderer->resolveEffectiveTestOverrideTo();
        $overrideEmails = $overrideTo !== null
            ? array_values(array_filter(
                Vendor::parseEmails($overrideTo),
                static fn (string $email): bool => (bool) filter_var($email, FILTER_VALIDATE_EMAIL),
            ))
            : [];

        $isTestMode = $overrideEmails !== [];

        if (! $isTestMode && $intendedRecipients === []) {
            $reason = 'Pengiriman ditahan: alamat email vendor belum diisi atau tidak valid.';
            $log = InvoicingEmailLog::create([
                'invoicing_receipt_id' => $receipt->id,
                'sent_by' => $actorId,
                'jenis' => $jenis,
                'trigger_source' => $triggerSource,
                'recipients' => [],
                'cc_recipients' => $intendedCc,
                'subject' => $subject,
                'body_html' => $bodyHtml,
                'attachments' => $this->formatAttachmentMetadata($attachmentCheck['attachments']),
                'status' => 'skipped',
                'error_message' => $reason,
            ]);

            return [
                'status' => 'skipped',
                'message' => $reason,
                'log' => $log,
            ];
        }

        $actualRecipients = $isTestMode ? $overrideEmails : $intendedRecipients;
        // Selama Mode Uji Coba aktif, jangan kirim CC ke alamat asli agar tidak mengganggu inbox tim
        $actualCc = $isTestMode ? [] : $intendedCc;
        $testBannerHtml = null;

        if ($isTestMode) {
            $intendedList = $intendedRecipients !== []
                ? e(implode(', ', $intendedRecipients))
                : '<em>(alamat email vendor kosong)</em>';
            $intendedCcText = $intendedCc !== []
                ? ' | CC asli (ditahan saat uji coba): <code>'.e(implode(', ', $intendedCc)).'</code>'
                : '';

            $testBannerHtml = '<tr><td style="padding:12px 28px;background-color:#fffbeb;border-bottom:1px solid #fde68a;font-size:12px;color:#92400e;">'
                .'<strong>MODE UJI COBA AKTIF:</strong> Email ini dialihkan ke <code>'.e(implode(', ', $overrideEmails)).'</code>. '
                .'Tujuan vendor asli: '.$intendedList.$intendedCcText.'.'
                .'</td></tr>';
        }

        $finalHtml = $this->renderer->wrapInCorporateLayout($bodyHtml, $jenis, $testBannerHtml);
        $finalSubject = $isTestMode ? '[UJI COBA] '.$subject : $subject;

        try {
            $sendResult = $this->zimbra->sendMessage(
                to: $actualRecipients,
                subject: $finalSubject,
                htmlBody: $finalHtml,
                attachments: array_map(
                    static fn (array $item): array => [
                        'path' => $item['path'],
                        'filename' => $item['filename'],
                    ],
                    $attachmentCheck['attachments'],
                ),
                cc: $actualCc,
            );

            $log = InvoicingEmailLog::create([
                'invoicing_receipt_id' => $receipt->id,
                'sent_by' => $actorId,
                'jenis' => $jenis,
                'trigger_source' => $triggerSource,
                'recipients' => $actualRecipients,
                'original_recipients' => $isTestMode ? $intendedRecipients : null,
                'cc_recipients' => $actualCc !== [] ? $actualCc : null,
                'subject' => $finalSubject,
                'body_html' => $bodyHtml,
                'attachments' => $this->formatAttachmentMetadata($attachmentCheck['attachments']),
                'status' => 'sent',
                'zimbra_message_id' => $sendResult['message_id'],
                'sent_at' => now(),
            ]);

            // Hari ini dikirim -> hari ini terinfo (tanggal_info_grs diisi otomatis)
            $receipt->tanggal_info_grs = now()->toDateString();
            $receipt->saveQuietly();

            $targetText = implode(', ', $actualRecipients);
            $ccNote = $actualCc !== [] ? ' | CC: '.implode(', ', $actualCc) : '';
            $modeNote = $isTestMode ? ' [Mode Uji Coba]' : '';

            return [
                'status' => 'sent',
                'message' => "Email berhasil dikirim ke {$targetText}{$ccNote} (2 lampiran PDF){$modeNote}.",
                'log' => $log,
            ];
        } catch (Throwable $e) {
            report($e);

            $log = InvoicingEmailLog::create([
                'invoicing_receipt_id' => $receipt->id,
                'sent_by' => $actorId,
                'jenis' => $jenis,
                'trigger_source' => $triggerSource,
                'recipients' => $actualRecipients,
                'original_recipients' => $isTestMode ? $intendedRecipients : null,
                'cc_recipients' => $actualCc !== [] ? $actualCc : null,
                'subject' => $finalSubject,
                'body_html' => $bodyHtml,
                'attachments' => $this->formatAttachmentMetadata($attachmentCheck['attachments']),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'message' => 'Gagal mengirim email via Zimbra: '.$e->getMessage(),
                'log' => $log,
            ];
        }
    }

    /**
     * @param  array<int, array{type: string, path: string, storage_path: string, filename: string, size_bytes: int}>  $attachments
     * @return array<int, array{type: string, filename: string, storage_path: string, size_bytes: int}>
     */
    private function formatAttachmentMetadata(array $attachments): array
    {
        return array_map(
            static fn (array $item): array => [
                'type' => $item['type'],
                'filename' => $item['filename'],
                'storage_path' => $item['storage_path'],
                'size_bytes' => $item['size_bytes'],
            ],
            $attachments,
        );
    }

    private function sanitizeDocCode(?string $value): string
    {
        $clean = preg_replace('/[^A-Za-z0-9\-_]/', '', (string) $value);

        return $clean !== '' ? $clean : 'DOKUMEN';
    }
}
