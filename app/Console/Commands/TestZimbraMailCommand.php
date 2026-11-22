<?php

namespace App\Console\Commands;

use App\Services\ZimbraMailService;
use Illuminate\Console\Command;
use Throwable;

class TestZimbraMailCommand extends Command
{
    protected $signature = 'zimbra:test-send
        {--to= : Alamat email tujuan uji coba (default: ZIMBRA_TEST_OVERRIDE_TO)}
        {--auth-only : Hanya menguji autentikasi SOAP tanpa mengirim email}
        {--no-attachments : Kirim email tanpa 2 lampiran PDF contoh}';

    protected $description = 'Menguji koneksi Zimbra SOAP API, upload 2 lampiran PDF, dan pengiriman email uji coba.';

    public function handle(ZimbraMailService $zimbra): int
    {
        if (! $zimbra->isConfigured()) {
            $this->error('Konfigurasi Zimbra belum lengkap. Isi ZIMBRA_BASE_URL, ZIMBRA_USERNAME, dan ZIMBRA_PASSWORD di .env terlebih dahulu.');

            return self::FAILURE;
        }

        $this->info("Menguji koneksi ke {$zimbra->baseUrl()} sebagai {$zimbra->username()}...");

        try {
            $token = $zimbra->authenticate(forceRefresh: true);
            $this->line('  [OK] AuthRequest berhasil (authToken diterima).');
        } catch (Throwable $e) {
            $this->error('  [GAGAL] AuthRequest: '.$e->getMessage());

            return self::FAILURE;
        }

        if ((bool) $this->option('auth-only')) {
            $this->info('Uji autentikasi selesai dengan sukses.');

            return self::SUCCESS;
        }

        $to = trim((string) ($this->option('to') ?: $zimbra->testOverrideTo() ?: ''));

        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Alamat tujuan uji coba belum valid. Berikan --to=email@anda atau isi ZIMBRA_TEST_OVERRIDE_TO di .env.');

            return self::FAILURE;
        }

        $tempFiles = [];
        $attachments = [];

        try {
            if (! (bool) $this->option('no-attachments')) {
                $grsPdfPath = $this->createSamplePdf('Dokumen GRS Contoh (105) - Uji Integrasi Zimbra');
                $verifPdfPath = $this->createSamplePdf('Surat Verifikasi GRS Contoh (105) - Uji Integrasi Zimbra');
                $tempFiles = [$grsPdfPath, $verifPdfPath];

                $attachments = [
                    ['path' => $grsPdfPath, 'filename' => 'Sample_GRS_5000000001.pdf'],
                    ['path' => $verifPdfPath, 'filename' => 'Sample_Verifikasi_GRS_5000000001.pdf'],
                ];
            }

            $subject = '[Uji Coba] Integrasi Email Verifikasi GRS/RDTV - Receiving PKT';
            $htmlBody = '<p>Halo,</p>'
                .'<p>Ini adalah email uji coba otomatis dari aplikasi <strong>Receiving PKT v2.0</strong> melalui <strong>Zimbra SOAP API</strong> (<code>'.$zimbra->baseUrl().'</code>).</p>'
                .'<ul>'
                .'<li><strong>Akun Pengirim:</strong> '.e($zimbra->username()).'</li>'
                .'<li><strong>Waktu Kirim:</strong> '.now()->format('d/m/Y H:i:s').'</li>'
                .'<li><strong>Jumlah Lampiran:</strong> '.count($attachments).' berkas PDF</li>'
                .'</ul>'
                .'<p>Jika email beserta 2 lampiran PDF ini diterima dengan baik dan tercatat di folder <em>Sent</em> Zimbra, berarti integrasi Tahap 0 berhasil.</p>';

            $result = $zimbra->sendMessage(
                to: [$to],
                subject: $subject,
                htmlBody: $htmlBody,
                attachments: $attachments,
            );

            $this->line('  [OK] Upload lampiran berhasil ('.count($result['attachment_ids']).' file).');
            $this->line('  [OK] SendMsgRequest berhasil ke '.$to.' (Message ID: '.($result['message_id'] ?? '-').').');
            $this->info('Uji coba Tahap 0 selesai dengan sukses. Silakan periksa inbox tujuan dan folder Sent Zimbra.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('  [GAGAL] Pengiriman email: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            foreach ($tempFiles as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }

    /**
     * Membuat berkas PDF minimal yang valid untuk pengujian upload lampiran Zimbra.
     */
    private function createSamplePdf(string $label): string
    {
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $label);
        $stream = "BT /F1 12 Tf 48 720 Td ({$escaped}) Tj ET";
        $len = strlen($stream);

        $pdf = "%PDF-1.4\n"
            ."1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n"
            ."2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n"
            ."3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n"
            ."4 0 obj << /Length {$len} >> stream\n{$stream}\nendstream endobj\n"
            ."5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n"
            ."xref\n0 6\n0000000000 65535 f \n"
            ."0000000009 00000 n \n"
            ."0000000058 00000 n \n"
            ."0000000115 00000 n \n"
            ."0000000241 00000 n \n"
            ."0000000360 00000 n \n"
            ."trailer << /Size 6 /Root 1 0 R >>\nstartxref\n430\n%%EOF\n";

        $path = tempnam(sys_get_temp_dir(), 'zimbra_test_').'.pdf';
        file_put_contents($path, $pdf);

        return $path;
    }
}
