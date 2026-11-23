<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZimbraMailService
{
    private const AUTH_TOKEN_CACHE_KEY = 'zimbra_mail_auth_token';

    /**
     * Memeriksa apakah konfigurasi dasar Zimbra sudah terisi.
     */
    public function isConfigured(): bool
    {
        return filled($this->baseUrl())
            && filled($this->username())
            && filled($this->password());
    }

    /**
     * Mengembalikan alamat URL dasar server Zimbra tanpa garis miring akhir.
     */
    public function baseUrl(): string
    {
        return rtrim((string) config('services.zimbra.base_url', 'https://mailpkt.pupukkaltim.com'), '/');
    }

    /**
     * Mengembalikan username / alamat email akun Zimbra.
     */
    public function username(): string
    {
        return trim((string) config('services.zimbra.username', ''));
    }

    /**
     * Mengembalikan alamat email pengalih khusus mode uji coba (jika diisi).
     */
    public function testOverrideTo(): ?string
    {
        $override = trim((string) config('services.zimbra.test_override_to', ''));

        return $override !== '' ? $override : null;
    }

    /**
     * Melakukan autentikasi ke Zimbra SOAP API dan mengembalikan authToken.
     */
    public function authenticate(bool $forceRefresh = false): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Kredensial Zimbra (ZIMBRA_BASE_URL, ZIMBRA_USERNAME, ZIMBRA_PASSWORD) belum dikonfigurasi.');
        }

        $cacheKey = $this->cacheKey();

        if (! $forceRefresh) {
            try {
                $cached = Cache::get($cacheKey);
                if (is_string($cached) && $cached !== '') {
                    return $cached;
                }
            } catch (\Throwable) {
                // Abaikan gangguan cache agar autentikasi Zimbra tetap berjalan.
            }
        }

        $payload = [
            'Header' => [
                'context' => [
                    '_jsns' => 'urn:zimbra',
                    'userAgent' => [
                        'name' => 'ReceivingPKT',
                        'version' => '2.0',
                    ],
                ],
            ],
            'Body' => [
                'AuthRequest' => [
                    '_jsns' => 'urn:zimbraAccount',
                    'account' => [
                        'by' => 'name',
                        '_content' => $this->username(),
                    ],
                    'password' => [
                        '_content' => $this->password(),
                    ],
                ],
            ],
        ];

        $response = $this->httpClient()
            ->acceptJson()
            ->asJson()
            ->post($this->soapEndpoint(), $payload);

        $data = $this->decodeSoapResponse($response, 'Autentikasi Zimbra');

        $token = $data['Body']['AuthResponse']['authToken'][0]['_content']
            ?? $data['Body']['AuthResponse']['authToken']['_content']
            ?? null;

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Zimbra tidak mengembalikan authToken pada AuthResponse.');
        }

        $lifetimeMs = (int) ($data['Body']['AuthResponse']['lifetime'] ?? 0);
        $ttlSeconds = $lifetimeMs > 120000
            ? min(2700, (int) floor(($lifetimeMs / 1000) - 60))
            : 2700;

        try {
            Cache::put($cacheKey, $token, now()->addSeconds(max(300, $ttlSeconds)));
        } catch (\Throwable) {
            // Abaikan gangguan cache agar pengiriman tetap berjalan.
        }

        return $token;
    }

    /**
     * Mengunggah satu berkas lampiran ke endpoint Zimbra /service/upload?fmt=raw dan mengembalikan attachment ID (aid).
     */
    public function uploadAttachment(string $absolutePath, string $filename, ?string $authToken = null): string
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            throw new RuntimeException("Berkas lampiran tidak ditemukan atau tidak dapat dibaca: {$filename}");
        }

        $token = $authToken ?: $this->authenticate();
        $contents = file_get_contents($absolutePath);

        if ($contents === false) {
            throw new RuntimeException("Gagal membaca isi berkas lampiran: {$filename}");
        }

        $safeFilename = $this->sanitizeFilename($filename);
        $mimeType = mime_content_type($absolutePath) ?: 'application/pdf';

        $response = $this->httpClient()
            ->withHeaders([
                'Cookie' => "ZM_AUTH_TOKEN={$token}",
                'Content-Disposition' => 'attachment; filename="'.$safeFilename.'"',
            ])
            ->withBody($contents, $mimeType)
            ->post($this->uploadEndpoint());

        $body = trim($response->body());

        if (! $response->successful()) {
            throw new RuntimeException("Upload lampiran Zimbra gagal (HTTP {$response->status()}): {$body}");
        }

        $aid = $this->parseUploadAid($body);

        if ($aid === null) {
            throw new RuntimeException("Format respons upload lampiran Zimbra tidak dikenali: {$body}");
        }

        return $aid;
    }

    /**
     * Mengirim email melalui Zimbra SendMsgRequest dengan dukungan multi-penerima dan multi-lampiran PDF.
     *
     * @param  array<int, string>  $to
     * @param  array<int, array{path: string, filename: string}>  $attachments
     * @param  array<int, string>  $cc
     * @return array{message_id: ?string, attachment_ids: array<int, string>}
     */
    public function sendMessage(
        array $to,
        string $subject,
        string $htmlBody,
        array $attachments = [],
        array $cc = [],
        ?string $plainTextBody = null,
        ?string $fromName = null,
    ): array {
        $validTo = $this->filterValidEmails($to);
        if ($validTo === []) {
            throw new RuntimeException('Tidak ada alamat email tujuan (To) yang valid.');
        }

        $validCc = $this->filterValidEmails($cc);

        try {
            return $this->performSend($validTo, $validCc, $subject, $htmlBody, $plainTextBody, $attachments, false, $fromName);
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), 'AUTH_EXPIRED') || str_contains($e->getMessage(), 'AUTH_REQUIRED')) {
                try {
                    Cache::forget($this->cacheKey());
                } catch (\Throwable) {
                    // Abaikan gangguan cache saat membersihkan token.
                }

                return $this->performSend($validTo, $validCc, $subject, $htmlBody, $plainTextBody, $attachments, true, $fromName);
            }

            throw $e;
        }
    }

    /**
     * Menguji koneksi dan kredensial Zimbra tanpa mengirim email.
     *
     * @return array{ok: bool, username: string, base_url: string}
     */
    public function testConnection(): array
    {
        $this->authenticate(forceRefresh: true);

        return [
            'ok' => true,
            'username' => $this->username(),
            'base_url' => $this->baseUrl(),
        ];
    }

    /**
     * @param  array<int, string>  $validTo
     * @param  array<int, string>  $validCc
     * @param  array<int, array{path: string, filename: string}>  $attachments
     * @return array{message_id: ?string, attachment_ids: array<int, string>}
     */
    private function performSend(
        array $validTo,
        array $validCc,
        string $subject,
        string $htmlBody,
        ?string $plainTextBody,
        array $attachments,
        bool $forceRefreshAuth,
        ?string $fromNameOverride = null,
    ): array {
        $token = $this->authenticate(forceRefresh: $forceRefreshAuth);

        $attachmentIds = [];
        foreach ($attachments as $attachment) {
            $attachmentIds[] = $this->uploadAttachment(
                $attachment['path'],
                $attachment['filename'],
                $token,
            );
        }

        $recipients = [];
        foreach ($validTo as $email) {
            $recipients[] = ['t' => 't', 'a' => $email];
        }
        foreach ($validCc as $email) {
            $recipients[] = ['t' => 'c', 'a' => $email];
        }

        $fromName = trim((string) ($fromNameOverride ?? config('services.zimbra.from_name', '')));
        if ($fromName !== '') {
            $recipients[] = [
                't' => 'f',
                'a' => $this->username(),
                'p' => $fromName,
            ];
        }

        $plainText = $plainTextBody ?? $this->htmlToPlainText($htmlBody);

        $messageNode = [
            'e' => $recipients,
            'su' => ['_content' => $subject],
            'mp' => [
                [
                    'ct' => 'multipart/alternative',
                    'mp' => [
                        [
                            'ct' => 'text/plain',
                            'content' => ['_content' => $plainText],
                        ],
                        [
                            'ct' => 'text/html',
                            'content' => ['_content' => $htmlBody],
                        ],
                    ],
                ],
            ],
        ];

        if ($attachmentIds !== []) {
            $messageNode['attach'] = [
                'aid' => implode(',', $attachmentIds),
            ];
        }

        $payload = [
            'Header' => [
                'context' => [
                    '_jsns' => 'urn:zimbra',
                    'authToken' => ['_content' => $token],
                    'userAgent' => [
                        'name' => 'ReceivingPKT',
                        'version' => '2.0',
                    ],
                ],
            ],
            'Body' => [
                'SendMsgRequest' => [
                    '_jsns' => 'urn:zimbraMail',
                    'm' => $messageNode,
                ],
            ],
        ];

        $response = $this->httpClient()
            ->acceptJson()
            ->asJson()
            ->post($this->soapEndpoint(), $payload);

        $data = $this->decodeSoapResponse($response, 'Pengiriman email Zimbra');

        $messageId = $data['Body']['SendMsgResponse']['m'][0]['id']
            ?? $data['Body']['SendMsgResponse']['m']['id']
            ?? null;

        return [
            'message_id' => is_string($messageId) ? $messageId : null,
            'attachment_ids' => $attachmentIds,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeSoapResponse(Response $response, string $context): array
    {
        $json = $response->json();

        if (is_array($json) && isset($json['Body']['Fault'])) {
            $fault = $json['Body']['Fault'];
            $reason = $fault['Reason']['Text'] ?? 'Terjadi kesalahan SOAP Zimbra.';
            $code = $fault['Detail']['Error']['Code'] ?? 'UNKNOWN_FAULT';

            throw new RuntimeException("{$context} gagal [{$code}]: {$reason}");
        }

        if (! $response->successful() || ! is_array($json)) {
            $snippet = mb_substr(trim(strip_tags($response->body())), 0, 240);

            throw new RuntimeException("{$context} gagal (HTTP {$response->status()}): {$snippet}");
        }

        return $json;
    }

    private function parseUploadAid(string $responseBody): ?string
    {
        if (preg_match("/^200\s*,\s*[^,]*,\s*'([^']+)'/", $responseBody, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/"aid"\s*:\s*"([^"]+)"/', $responseBody, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function sanitizeFilename(string $filename): string
    {
        $clean = preg_replace('/[^\w.\- ()]/u', '_', basename($filename)) ?: 'document.pdf';

        return trim($clean);
    }

    private function htmlToPlainText(string $html): string
    {
        $normalized = preg_replace('/<\s*br\s*\/?>/i', "\n", $html) ?? $html;
        $normalized = preg_replace('/<\/(p|div|li|tr|h[1-6])>/i', "\n", $normalized) ?? $normalized;
        $text = html_entity_decode(strip_tags($normalized), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * @param  array<int, string>  $emails
     * @return array<int, string>
     */
    private function filterValidEmails(array $emails): array
    {
        $result = [];
        $seen = [];

        foreach ($emails as $email) {
            $clean = trim($email);
            if ($clean === '' || ! filter_var($clean, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $lower = strtolower($clean);
            if (! isset($seen[$lower])) {
                $seen[$lower] = true;
                $result[] = $clean;
            }
        }

        return $result;
    }

    private function httpClient(): PendingRequest
    {
        $client = Http::timeout((int) config('services.zimbra.timeout', 20));

        if (! (bool) config('services.zimbra.verify_ssl', true)) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    private function soapEndpoint(): string
    {
        return $this->baseUrl().'/service/soap';
    }

    private function uploadEndpoint(): string
    {
        return $this->baseUrl().'/service/upload?fmt=raw';
    }

    private function password(): string
    {
        return (string) config('services.zimbra.password', '');
    }

    private function cacheKey(): string
    {
        return self::AUTH_TOKEN_CACHE_KEY.':'.sha1($this->baseUrl().'|'.strtolower($this->username()));
    }
}
