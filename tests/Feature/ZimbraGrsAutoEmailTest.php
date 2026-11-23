<?php

use App\Models\GrsRdtv;
use App\Models\GrsRdtvItem;
use App\Models\InvoicingEmailLog;
use App\Models\InvoicingReceipt;
use App\Models\User;
use App\Services\EmailTemplateRenderer;
use App\Services\InvoicingGrsEmailDispatcher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Storage::fake('public');

    if (! DbSchema::hasTable('users')) {
        DbSchema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('permissions')) {
        $permissionMigration = include database_path('migrations/2026_05_15_101350_create_permission_tables.php');
        $permissionMigration->up();
    }

    Role::firstOrCreate(['name' => 'Developer', 'guard_name' => 'web']);

    if (! DbSchema::hasTable('settings')) {
        DbSchema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('vendors')) {
        DbSchema::create('vendors', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('grs_rdtvs')) {
        DbSchema::create('grs_rdtvs', function (Blueprint $table): void {
            $table->id();
            $table->string('category')->default('GRS');
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('grs_rdtv_items')) {
        DbSchema::create('grs_rdtv_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grs_rdtv_id')->nullable();
            $table->foreignId('delivery_order_receipt_id')->nullable();
            $table->string('document_code')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('invoicing_receipts')) {
        DbSchema::create('invoicing_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grs_rdtv_item_id')->nullable();
            $table->foreignId('delivery_order_receipt_id')->nullable();
            $table->foreignId('purchase_order_issued_id')->nullable();
            $table->foreignId('vendor_id')->nullable();
            $table->foreignId('pic_id')->nullable();
            $table->date('tanggal_terima_grs')->nullable();
            $table->date('delivery_date')->nullable();
            $table->date('tanggal_penerimaan')->nullable();
            $table->date('tanggal_po')->nullable();
            $table->string('jenis')->default('GRS');
            $table->string('kategori_barang')->default('Barang');
            $table->string('no_grs')->nullable();
            $table->string('no_rdtv')->nullable();
            $table->string('no_po')->default('-');
            $table->string('no_do')->nullable();
            $table->string('material_type')->nullable();
            $table->string('vendor_name')->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('email_vendor')->nullable();
            $table->date('tanggal_info_grs')->nullable();
            $table->text('nama_barang')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('dokumen_verifikasi_grs')->nullable();
            $table->string('dokumen_invoice')->nullable();
            $table->string('status')->default('Menunggu Verifikasi');
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('invoicing_receipt_items')) {
        DbSchema::create('invoicing_receipt_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoicing_receipt_id');
            $table->string('no_grs')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('invoicing_email_logs')) {
        $logMigration = include database_path('migrations/2026_11_23_012849_create_invoicing_email_logs_table.php');
        $logMigration->up();
    }

    config([
        'services.zimbra.enabled' => true,
        'services.zimbra.base_url' => 'https://mailpkt.pupukkaltim.com',
        'services.zimbra.username' => 'receiving.jasa@pupukkaltim.com',
        'services.zimbra.password' => 'secret-pass',
        'services.zimbra.from_name' => 'Receiving Jasa - PT Pupuk Kalimantan Timur',
        'services.zimbra.test_override_to' => '',
    ]);
});

function fakeZimbraSuccess(): void
{
    $uploadCount = 0;

    Http::fake([
        'https://mailpkt.pupukkaltim.com/service/upload*' => function () use (&$uploadCount) {
            $uploadCount++;

            return Http::response("200,'null','aid-file-{$uploadCount}'", 200);
        },
        'https://mailpkt.pupukkaltim.com/service/soap' => function (HttpClientRequest $request) {
            $body = $request->data();

            if (isset($body['Body']['AuthRequest'])) {
                return Http::response([
                    'Body' => [
                        'AuthResponse' => [
                            'authToken' => [['_content' => 'fake-zimbra-token']],
                            'lifetime' => 3600000,
                        ],
                    ],
                ], 200);
            }

            return Http::response([
                'Body' => [
                    'SendMsgResponse' => [
                        'm' => [['id' => '998877']],
                    ],
                ],
            ], 200);
        },
    ]);
}

it('mengirim email otomatis via Zimbra dengan 2 lampiran PDF saat upload Verifikasi GRS dan mengisi tanggal_info_grs', function (): void {
    fakeZimbraSuccess();

    $user = User::create([
        'name' => 'Staff Receiving',
        'email' => 'staff@pkt.test',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('Developer');

    Storage::disk('public')->put('grs-files/grs-5007711111.pdf', '%PDF-1.4 GRS SLIP');

    $grsHeader = GrsRdtv::create(['category' => 'GRS']);
    $grsItem = GrsRdtvItem::create([
        'grs_rdtv_id' => $grsHeader->id,
        'document_code' => '5007711111',
        'file_path' => 'grs-files/grs-5007711111.pdf',
    ]);

    $receipt = InvoicingReceipt::create([
        'grs_rdtv_item_id' => $grsItem->id,
        'jenis' => 'GRS',
        'no_grs' => '5007711111',
        'no_po' => '5300058418',
        'no_do' => 'DO-100',
        'vendor_name' => 'PT Mitra Sejahtera',
        'email_vendor' => 'billing@mitra.test finance@mitra.test',
        'status' => 'Menunggu Verifikasi',
        'tanggal_info_grs' => null,
    ]);

    $verifPdf = UploadedFile::fake()->create('Verifikasi_GRS_5007711111.pdf', 30, 'application/pdf');

    $response = $this->actingAs($user)->postJson(
        route('filament.admin.resources.invoicing-receipts.upload_verifikasi_grs_ocr'),
        [
            'file' => $verifPdf,
            'jenis' => 'GRS',
            'no_grs' => '5007711111',
            'no_po' => '5300058418',
            'original_name' => 'Verifikasi_GRS_5007711111.pdf',
        ]
    );

    $response->assertOk()
        ->assertJsonPath('matched', true)
        ->assertJsonPath('email_status', 'sent');

    $receipt->refresh();
    expect($receipt->status)->toBe('Terverifikasi')
        ->and($receipt->tanggal_info_grs?->toDateString())->toBe(now()->toDateString());

    $log = InvoicingEmailLog::first();
    expect($log)->not->toBeNull()
        ->and($log->status)->toBe('sent')
        ->and($log->jenis)->toBe('GRS')
        ->and($log->recipients)->toBe(['billing@mitra.test', 'finance@mitra.test'])
        ->and($log->attachments)->toHaveCount(2)
        ->and($log->attachments[0]['filename'])->toBe('GRS_5007711111.pdf')
        ->and($log->attachments[1]['filename'])->toBe('Verifikasi_GRS_5007711111.pdf')
        ->and($log->zimbra_message_id)->toBe('998877');

    Http::assertSent(function (HttpClientRequest $request): bool {
        if (! str_ends_with($request->url(), '/service/soap')) {
            return false;
        }
        $data = $request->data();

        return isset($data['Body']['SendMsgRequest']['m']['attach']['aid'])
            && $data['Body']['SendMsgRequest']['m']['attach']['aid'] === 'aid-file-1,aid-file-2';
    });
});

it('menahan pengiriman email (skipped) jika dokumen GRS/RDTV fisik belum ada karena wajib 2 lampiran', function (): void {
    Http::fake();

    $user = User::create([
        'name' => 'Staff Receiving',
        'email' => 'staff2@pkt.test',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('Developer');

    $receipt = InvoicingReceipt::create([
        'grs_rdtv_item_id' => null,
        'jenis' => 'GRS',
        'no_grs' => '5007722222',
        'no_po' => '5300099999',
        'vendor_name' => 'PT Tanpa Slip',
        'email_vendor' => 'vendor@tanpaslip.test',
        'status' => 'Menunggu Verifikasi',
        'tanggal_info_grs' => null,
    ]);

    $verifPdf = UploadedFile::fake()->create('Verifikasi_GRS_5007722222.pdf', 30, 'application/pdf');

    $response = $this->actingAs($user)->postJson(
        route('filament.admin.resources.invoicing-receipts.upload_verifikasi_grs_ocr'),
        [
            'file' => $verifPdf,
            'jenis' => 'GRS',
            'no_grs' => '5007722222',
            'no_po' => '5300099999',
        ]
    );

    $response->assertOk()
        ->assertJsonPath('matched', true)
        ->assertJsonPath('email_status', 'skipped');

    $receipt->refresh();
    expect($receipt->status)->toBe('Terverifikasi')
        ->and($receipt->tanggal_info_grs)->toBeNull();

    $log = InvoicingEmailLog::first();
    expect($log)->not->toBeNull()
        ->and($log->status)->toBe('skipped')
        ->and($log->error_message)->toContain('wajib 2 lampiran');

    Http::assertNothingSent();
});

it('menggunakan template RDTV kustom dari halaman pengaturan dan mendukung ZIMBRA_TEST_OVERRIDE_TO', function (): void {
    fakeZimbraSuccess();

    config(['services.zimbra.test_override_to' => 'pribadi@gmail.test']);

    app(EmailTemplateRenderer::class)->saveSettingsFormData([
        'auto_send_enabled' => true,
        'grs_subject' => 'Custom GRS {no_grs}',
        'grs_body' => '<p>Halo {vendor_name}</p>',
        'rdtv_subject' => 'Penolakan Resmi RDTV {no_rdtv} untuk PO {no_po}',
        'rdtv_body' => '<p>Yth {vendor_name}, barang ditolak karena {keterangan}.</p>',
    ]);

    Storage::disk('public')->put('rdtv-files/rdtv-5007733333.pdf', '%PDF-1.4 RDTV');
    Storage::disk('public')->put('verifikasi-rdtv/verif-5007733333.pdf', '%PDF-1.4 VERIF RDTV');

    $rdtvHeader = GrsRdtv::create(['category' => 'RDTV']);
    $rdtvItem = GrsRdtvItem::create([
        'grs_rdtv_id' => $rdtvHeader->id,
        'document_code' => '5007733333',
        'file_path' => 'rdtv-files/rdtv-5007733333.pdf',
    ]);

    $receipt = InvoicingReceipt::create([
        'grs_rdtv_item_id' => $rdtvItem->id,
        'jenis' => 'RDTV',
        'no_rdtv' => '5007733333',
        'no_po' => '5300077777',
        'vendor_name' => 'CV Sumber Makmur',
        'email_vendor' => 'vendor-asli@makmur.test',
        'keterangan' => 'Dimensi baut tidak sesuai gambar teknik',
        'dokumen_verifikasi_grs' => 'verifikasi-rdtv/verif-5007733333.pdf',
        'status' => 'Terverifikasi',
    ]);

    $result = app(InvoicingGrsEmailDispatcher::class)->dispatchIfEligible($receipt, 'row_upload');

    expect($result['status'])->toBe('sent');

    $log = InvoicingEmailLog::first();
    expect($log->jenis)->toBe('RDTV')
        ->and($log->recipients)->toBe(['pribadi@gmail.test'])
        ->and($log->original_recipients)->toBe(['vendor-asli@makmur.test'])
        ->and($log->subject)->toBe('[UJI COBA] Penolakan Resmi RDTV 5007733333 untuk PO 5300077777')
        ->and($log->body_html)->toContain('Dimensi baut tidak sesuai gambar teknik')
        ->and($log->attachments[0]['filename'])->toBe('RDTV_5007733333.pdf')
        ->and($log->attachments[1]['filename'])->toBe('Verifikasi_RDTV_5007733333.pdf');
});

it('mengisolasi error Zimbra sehingga upload Verifikasi GRS tetap tersimpan dan tercatat gagal di log', function (): void {
    Http::fake([
        'https://mailpkt.pupukkaltim.com/service/soap' => Http::response([
            'Body' => [
                'Fault' => [
                    'Reason' => ['Text' => 'authentication failed for [receiving.jasa@pupukkaltim.com]'],
                    'Detail' => ['Error' => ['Code' => 'account.AUTH_FAILED']],
                ],
            ],
        ], 500),
    ]);

    $user = User::create([
        'name' => 'Staff Receiving',
        'email' => 'staff3@pkt.test',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('Developer');

    Storage::disk('public')->put('grs-files/grs-5007744444.pdf', '%PDF-1.4 GRS');
    $grsHeader = GrsRdtv::create(['category' => 'GRS']);
    $grsItem = GrsRdtvItem::create([
        'grs_rdtv_id' => $grsHeader->id,
        'document_code' => '5007744444',
        'file_path' => 'grs-files/grs-5007744444.pdf',
    ]);

    $receipt = InvoicingReceipt::create([
        'grs_rdtv_item_id' => $grsItem->id,
        'jenis' => 'GRS',
        'no_grs' => '5007744444',
        'no_po' => '5300088888',
        'vendor_name' => 'PT Uji Error',
        'email_vendor' => 'billing@ujierror.test',
        'status' => 'Menunggu Verifikasi',
        'tanggal_info_grs' => null,
    ]);

    $verifPdf = UploadedFile::fake()->create('Verifikasi_GRS_5007744444.pdf', 30, 'application/pdf');

    $response = $this->actingAs($user)->postJson(
        route('filament.admin.resources.invoicing-receipts.upload_verifikasi_grs_ocr'),
        [
            'file' => $verifPdf,
            'jenis' => 'GRS',
            'no_grs' => '5007744444',
            'no_po' => '5300088888',
        ]
    );

    $response->assertOk()
        ->assertJsonPath('matched', true)
        ->assertJsonPath('email_status', 'failed');

    $receipt->refresh();
    expect($receipt->status)->toBe('Terverifikasi')
        ->and($receipt->tanggal_info_grs)->toBeNull();

    $log = InvoicingEmailLog::first();
    expect($log->status)->toBe('failed')
        ->and($log->error_message)->toContain('account.AUTH_FAILED');
});

it('mengonversi state array TipTap RichEditor Filament v5 menjadi HTML tanpa error Array to string conversion', function (): void {
    $renderer = app(EmailTemplateRenderer::class);

    $tiptapDoc = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Halo {vendor_name}, PO {no_po} sudah diverifikasi.'],
                ],
            ],
        ],
    ];

    $html = $renderer->normalizeRichContentToHtml($tiptapDoc);
    expect($html)->toContain('Halo {vendor_name}, PO {no_po} sudah diverifikasi.');

    $preview = $renderer->renderPreview('GRS', 'GRS {no_grs}', $html);
    expect($preview['wrapped_html'])->toContain('PT Contoh Mitra Industri')
        ->and($preview['wrapped_html'])->toContain('5300058418');
});

it('dapat mengaktifkan dan menonaktifkan Mode Uji Coba langsung dari pengaturan Filament tanpa ubah .env', function (): void {
    fakeZimbraSuccess();

    // Meski .env memiliki ZIMBRA_TEST_OVERRIDE_TO, toggle di Filament yang menentukan aktif/nonaktifnya
    config(['services.zimbra.test_override_to' => 'env-fallback@gmail.test']);

    Storage::disk('public')->put('grs-files/grs-5007755555.pdf', '%PDF-1.4 GRS');
    Storage::disk('public')->put('verifikasi-grs/verif-5007755555.pdf', '%PDF-1.4 VERIF');

    $grsHeader = GrsRdtv::create(['category' => 'GRS']);
    $grsItem = GrsRdtvItem::create([
        'grs_rdtv_id' => $grsHeader->id,
        'document_code' => '5007755555',
        'file_path' => 'grs-files/grs-5007755555.pdf',
    ]);

    $receipt = InvoicingReceipt::create([
        'grs_rdtv_item_id' => $grsItem->id,
        'jenis' => 'GRS',
        'no_grs' => '5007755555',
        'no_po' => '5300066666',
        'vendor_name' => 'PT Uji Toggle',
        'email_vendor' => 'asli@vendor.test',
        'dokumen_verifikasi_grs' => 'verifikasi-grs/verif-5007755555.pdf',
        'status' => 'Terverifikasi',
    ]);

    $renderer = app(EmailTemplateRenderer::class);
    $dispatcher = app(InvoicingGrsEmailDispatcher::class);

    // 1. Aktifkan Mode Uji Coba dengan alamat kustom
    $renderer->saveSettingsFormData([
        'auto_send_enabled' => true,
        'test_mode_enabled' => true,
        'test_mode_email' => 'tes-custom@gmail.test',
        'grs_subject' => 'GRS {no_grs}',
        'grs_body' => '<p>Halo</p>',
        'rdtv_subject' => 'RDTV {no_rdtv}',
        'rdtv_body' => '<p>Halo</p>',
    ]);

    $resOn = $dispatcher->dispatchIfEligible($receipt, 'row_upload');
    expect($resOn['status'])->toBe('sent')
        ->and($resOn['log']->recipients)->toBe(['tes-custom@gmail.test'])
        ->and($resOn['log']->original_recipients)->toBe(['asli@vendor.test']);

    // 2. Nonaktifkan Mode Uji Coba -> langsung kirim ke email vendor asli
    $renderer->saveSettingsFormData([
        'auto_send_enabled' => true,
        'test_mode_enabled' => false,
        'test_mode_email' => 'tes-custom@gmail.test',
        'grs_subject' => 'GRS {no_grs}',
        'grs_body' => '<p>Halo</p>',
        'rdtv_subject' => 'RDTV {no_rdtv}',
        'rdtv_body' => '<p>Halo</p>',
    ]);

    $resOff = $dispatcher->dispatchIfEligible($receipt, 'row_upload');
    expect($resOff['status'])->toBe('sent')
        ->and($resOff['log']->recipients)->toBe(['asli@vendor.test'])
        ->and($resOff['log']->original_recipients)->toBeNull();
});

it('menyertakan CC default receiving.barang@pupukkaltim.com pada mode normal, dapat diedit dari pengaturan, dan ditahan saat Mode Uji Coba', function (): void {
    fakeZimbraSuccess();

    Storage::disk('public')->put('grs-files/grs-5007766666.pdf', '%PDF-1.4 GRS');
    Storage::disk('public')->put('verifikasi-grs/verif-5007766666.pdf', '%PDF-1.4 VERIF');

    $grsHeader = GrsRdtv::create(['category' => 'GRS']);
    $grsItem = GrsRdtvItem::create([
        'grs_rdtv_id' => $grsHeader->id,
        'document_code' => '5007766666',
        'file_path' => 'grs-files/grs-5007766666.pdf',
    ]);

    $receipt = InvoicingReceipt::create([
        'grs_rdtv_item_id' => $grsItem->id,
        'jenis' => 'GRS',
        'no_grs' => '5007766666',
        'no_po' => '5300077788',
        'vendor_name' => 'PT Uji CC',
        'email_vendor' => 'vendor@ujicc.test',
        'dokumen_verifikasi_grs' => 'verifikasi-grs/verif-5007766666.pdf',
        'status' => 'Terverifikasi',
    ]);

    $renderer = app(EmailTemplateRenderer::class);
    $dispatcher = app(InvoicingGrsEmailDispatcher::class);

    // 1. Default CC saat Mode Normal -> receiving.barang@pupukkaltim.com
    $resDefault = $dispatcher->dispatchIfEligible($receipt, 'row_upload');
    expect($resDefault['status'])->toBe('sent')
        ->and($resDefault['log']->cc_recipients)->toBe(['receiving.barang@pupukkaltim.com']);

    Http::assertSent(function (HttpClientRequest $request): bool {
        if (! str_ends_with($request->url(), '/service/soap')) {
            return false;
        }
        $recipients = $request->data()['Body']['SendMsgRequest']['m']['e'] ?? [];

        return collect($recipients)->contains(
            fn (array $r): bool => ($r['t'] ?? null) === 'c' && ($r['a'] ?? null) === 'receiving.barang@pupukkaltim.com'
        );
    });

    // 2. Edit CC via pengaturan + aktifkan Mode Uji Coba -> CC asli ditahan agar tidak spam tim
    $renderer->saveSettingsFormData([
        'auto_send_enabled' => true,
        'test_mode_enabled' => true,
        'test_mode_email' => 'tes@gmail.test',
        'cc_emails' => 'receiving.barang@pupukkaltim.com, arsip@pupukkaltim.com',
        'grs_subject' => 'GRS {no_grs}',
        'grs_body' => '<p>Halo</p>',
        'rdtv_subject' => 'RDTV {no_rdtv}',
        'rdtv_body' => '<p>Halo</p>',
    ]);

    expect($renderer->resolveCcEmails())->toBe([
        'receiving.barang@pupukkaltim.com',
        'arsip@pupukkaltim.com',
    ]);

    $resTest = $dispatcher->dispatchIfEligible($receipt, 'row_upload');
    expect($resTest['status'])->toBe('sent')
        ->and($resTest['log']->recipients)->toBe(['tes@gmail.test'])
        ->and($resTest['log']->cc_recipients)->toBeNull();
});

it('menerapkan kustomisasi nama pengirim, judul header, badge, warna aksen, dan footer dari pengaturan Filament', function (): void {
    fakeZimbraSuccess();

    $renderer = app(EmailTemplateRenderer::class);
    $renderer->saveSettingsFormData([
        'auto_send_enabled' => true,
        'test_mode_enabled' => false,
        'sender_name' => 'receiving jasa custom',
        'header_title' => 'Dept. PP&P — PKT Bontang',
        'header_badge_grs' => 'DOKUMEN GRS RESMI',
        'header_badge_rdtv' => 'DOKUMEN RDTV RESMI',
        'footer_text' => 'Catatan footer kustom untuk vendor PKT.',
        'accent_color_grs' => '#0054A6',
        'accent_color_rdtv' => '#B91C1C',
        'grs_subject' => 'GRS {no_grs}',
        'grs_body' => '<p>Halo {vendor_name}</p>',
        'rdtv_subject' => 'RDTV {no_rdtv}',
        'rdtv_body' => '<p>Halo {vendor_name}</p>',
    ]);

    Storage::disk('public')->put('grs-files/grs-5007790001.pdf', '%PDF-1.4 GRS');
    Storage::disk('public')->put('verifikasi-grs/verif-5007790001.pdf', '%PDF-1.4 VERIF');

    $grsHeader = GrsRdtv::create(['category' => 'GRS']);
    $grsItem = GrsRdtvItem::create([
        'grs_rdtv_id' => $grsHeader->id,
        'document_code' => '5007790001',
        'file_path' => 'grs-files/grs-5007790001.pdf',
    ]);

    $receipt = InvoicingReceipt::create([
        'grs_rdtv_item_id' => $grsItem->id,
        'jenis' => 'GRS',
        'no_grs' => '5007790001',
        'no_po' => '5300090001',
        'vendor_name' => 'PT Kustomisasi',
        'email_vendor' => 'vendor@kustom.test',
        'dokumen_verifikasi_grs' => 'verifikasi-grs/verif-5007790001.pdf',
        'status' => 'Terverifikasi',
    ]);

    $res = app(InvoicingGrsEmailDispatcher::class)->dispatchIfEligible($receipt, 'row_upload');
    expect($res['status'])->toBe('sent');

    Http::assertSent(function (HttpClientRequest $request): bool {
        if (! str_ends_with($request->url(), '/service/soap')) {
            return false;
        }
        $msg = $request->data()['Body']['SendMsgRequest']['m'] ?? null;
        if (! is_array($msg)) {
            return false;
        }

        $hasSender = collect($msg['e'] ?? [])->contains(
            fn (array $r): bool => ($r['t'] ?? null) === 'f' && ($r['p'] ?? null) === 'receiving jasa custom'
        );
        $html = $msg['mp'][0]['mp'][1]['content']['_content'] ?? '';

        return $hasSender
            && str_contains($html, 'Dept. PP&amp;P — PKT Bontang')
            && str_contains($html, 'DOKUMEN GRS RESMI')
            && str_contains($html, 'Catatan footer kustom untuk vendor PKT.')
            && str_contains($html, '#0054A6');
    });
});
