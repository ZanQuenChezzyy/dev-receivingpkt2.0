<?php

use App\Filament\Pages\CombinedGrsVerificationUpload;
use App\Filament\Resources\GrsRdtvs\Pages\ListGrsRdtvs;
use App\Filament\Resources\InvoicingReceipts\Pages\ListInvoicingReceipts;
use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\GrsRdtv;
use App\Models\GrsRdtvItem;
use App\Models\InvoicingEmailLog;
use App\Models\InvoicingReceipt;
use App\Models\PurchaseOrderIssued;
use App\Models\QcHistory;
use App\Models\User;
use App\Models\Vendor;
use App\Services\GrsPdfParserService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function makeTextPdfUpload(string $filename, string $text): UploadedFile
{
    $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    $stream = "BT /F1 12 Tf 40 720 Td ({$escaped}) Tj ET";
    $len = strlen($stream);

    $pdf = "%PDF-1.4\n"
        ."1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n"
        ."2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n"
        ."3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n"
        ."4 0 obj << /Length {$len} >> stream\n{$stream}\nendstream endobj\n"
        ."5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n"
        ."%%EOF\n";

    return UploadedFile::fake()->createWithContent($filename, $pdf);
}

function fakeCombinedZimbraSuccess(): void
{
    $uploadCount = 0;

    Http::fake([
        'https://mailpkt.pupukkaltim.com/service/upload*' => function () use (&$uploadCount) {
            $uploadCount++;

            return Http::response("200,'null','aid-combined-{$uploadCount}'", 200);
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
                        'm' => [['id' => 'msg-combined-001']],
                    ],
                ],
            ], 200);
        },
    ]);
}

beforeEach(function (): void {
    Storage::fake('public');
    Storage::fake('local');

    if (! DbSchema::hasTable('users')) {
        DbSchema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('npk')->nullable();
            $table->boolean('is_active')->default(true);
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
    Role::firstOrCreate(['name' => 'Staff Invoice', 'guard_name' => 'web']);

    if (! DbSchema::hasTable('settings')) {
        DbSchema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('pic_material_assignments')) {
        DbSchema::create('pic_material_assignments', function (Blueprint $table): void {
            $table->id();
            $table->string('material_type')->unique();
            $table->string('assignment_mode')->default('free_pool');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('conditional_rules')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('pic_material_assignment_user')) {
        DbSchema::create('pic_material_assignment_user', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pic_material_assignment_id');
            $table->unsignedBigInteger('user_id');
        });
    }

    if (! DbSchema::hasTable('vendors')) {
        DbSchema::create('vendors', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('vendor_number')->nullable();
            $table->text('email')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('purchase_order_issueds')) {
        DbSchema::create('purchase_order_issueds', function (Blueprint $table): void {
            $table->id();
            $table->string('purchase_order_no')->unique();
            $table->string('material_type')->nullable();
            $table->string('material_code')->nullable();
            $table->text('description')->nullable();
            $table->decimal('qty_po', 15, 4)->nullable();
            $table->string('uoi', 20)->nullable();
            $table->decimal('total_amount_in_lc', 18, 2)->nullable();
            $table->foreignId('vendor_id')->nullable();
            $table->string('vendor_name')->nullable();
            $table->date('delivery_date_po')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('delivery_order_receipts')) {
        DbSchema::create('delivery_order_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('delivery_order_no', 50)->nullable();
            $table->date('received_date')->nullable();
            $table->foreignId('received_by')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->string('source_type', 20)->nullable();
            $table->string('stage', 100)->nullable();
            $table->string('document_code', 100)->nullable()->unique();
            $table->string('status', 50)->default('Diterima');
            $table->dateTime('post_103')->nullable();
            $table->string('delay_reason')->nullable();
            $table->text('delay_notes')->nullable();
            $table->string('receipt_mode', 30)->default('Standard');
            $table->boolean('is_physically_received')->default(false);
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('delivery_order_receipt_details')) {
        DbSchema::create('delivery_order_receipt_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_order_receipt_id');
            $table->foreignId('purchase_order_issued_id')->nullable();
            $table->string('material_code')->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 4)->nullable();
            $table->string('uoi', 20)->nullable();
            $table->string('material_type')->nullable();
            $table->decimal('total_amount_snapshot', 18, 2)->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('delivery_order_receipt_delay_logs')) {
        DbSchema::create('delivery_order_receipt_delay_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_order_receipt_id');
            $table->string('delay_reason')->nullable();
            $table->text('delay_notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('delivery_order_receipt_termins')) {
        DbSchema::create('delivery_order_receipt_termins', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_order_receipt_id');
            $table->integer('termin_number')->default(1);
            $table->string('status')->default('Draft');
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('qc_histories')) {
        DbSchema::create('qc_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_order_receipt_id');
            $table->string('status');
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('grs_rdtvs')) {
        DbSchema::create('grs_rdtvs', function (Blueprint $table): void {
            $table->id();
            $table->date('transaction_date')->nullable();
            $table->string('category')->default('GRS');
            $table->foreignId('created_by')->nullable();
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
            $table->string('padi')->nullable();
            $table->string('material_type')->nullable();
            $table->string('vendor_name')->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('email_vendor')->nullable();
            $table->date('tanggal_info_grs')->nullable();
            $table->string('no_invoice')->nullable();
            $table->date('tanggal_invoice')->nullable();
            $table->string('faktur_pajak')->nullable();
            $table->date('tanggal_faktur_pajak')->nullable();
            $table->text('nama_barang')->nullable();
            $table->decimal('qty_penagihan', 15, 4)->nullable();
            $table->decimal('qty_po', 15, 4)->nullable();
            $table->string('uoi', 20)->nullable();
            $table->decimal('subtotal_exclude_ppn', 18, 2)->nullable();
            $table->decimal('subtotal_include_ppn', 18, 2)->nullable();
            $table->date('tanggal_terima_koreksi_invoice')->nullable();
            $table->date('tanggal_kirim_kasir')->nullable();
            $table->integer('lama_pekerjaan')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('dokumen_grs')->nullable();
            $table->string('dokumen_verifikasi_grs')->nullable();
            $table->string('dokumen_invoice')->nullable();
            $table->string('dokumen_kelengkapan')->nullable();
            $table->string('status')->default('Menunggu Verifikasi');
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('invoicing_receipt_items')) {
        DbSchema::create('invoicing_receipt_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoicing_receipt_id')->constrained('invoicing_receipts')->cascadeOnDelete();
            $table->string('no_grs')->nullable();
            $table->string('no_do')->nullable();
            $table->string('nama_barang')->nullable();
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

function createEligibleDoFixture(
    string $docCode,
    string $poNo,
    string $doNo = 'SJ-001',
    string $vendorName = 'PT Mitra Industri',
    string $vendorEmail = 'vendor@mitra.test',
    bool $withPost103 = true,
    bool $withQcKembali = true,
): array {
    $vendor = Vendor::firstOrCreate(
        ['name' => $vendorName],
        ['vendor_number' => 'V-'.substr($poNo, -4), 'email' => $vendorEmail],
    );

    $po = PurchaseOrderIssued::firstOrCreate(
        ['purchase_order_no' => $poNo],
        [
            'material_type' => 'STOK',
            'material_code' => 'MAT-01',
            'description' => 'Valve Stainless 2 Inch',
            'qty_po' => 10,
            'uoi' => 'EA',
            'total_amount_in_lc' => 1500000,
            'vendor_id' => $vendor->vendor_number,
            'vendor_name' => $vendor->name,
        ],
    );

    $do = DeliveryOrderReceipt::create([
        'delivery_order_no' => $doNo,
        'received_date' => '2026-11-20',
        'document_code' => $docCode,
        'status' => 'Diterima',
        'post_103' => $withPost103 ? now() : null,
    ]);

    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $do->id,
        'purchase_order_issued_id' => $po->id,
        'material_code' => 'MAT-01',
        'description' => 'Valve Stainless 2 Inch',
        'quantity' => 5,
        'uoi' => 'EA',
        'material_type' => 'STOK',
        'total_amount_snapshot' => 750000,
    ]);

    if ($withQcKembali) {
        QcHistory::create([
            'delivery_order_receipt_id' => $do->id,
            'status' => 'Kembali',
        ]);
    }

    return compact('vendor', 'po', 'do');
}

test('GrsPdfParserService classifies GRS slip, Verifikasi GRS, RDTV slip, and Verifikasi RDTV accurately', function (): void {
    $parser = app(GrsPdfParserService::class);

    $grsSlip = makeTextPdfUpload('PKT-DO-1001.pdf', 'GOODS RECEIPT SLIP No. Release GR 5007711111 Purchase Order 5300067497 Delivery Date 20.11.2026');
    $resGrsSlip = $parser->classifyDocument($grsSlip->getRealPath(), 'PKT-DO-1001.pdf', true);
    expect($resGrsSlip['role'])->toBe('grs_slip')
        ->and($resGrsSlip['jenis'])->toBe('GRS')
        ->and($resGrsSlip['no_grs'])->toBe('5007711111')
        ->and($resGrsSlip['no_po'])->toBe('5300067497');

    $verifGrs = makeTextPdfUpload('Verifikasi_GRS_5007711111.pdf', 'VERIFIKASI PENERIMAAN BARANG Nomor PO 5300067497 Nomor GR (103) 5007700000 Nomor GRS (105) 5007711111');
    $resVerifGrs = $parser->classifyDocument($verifGrs->getRealPath(), 'Verifikasi_GRS_5007711111.pdf', false);
    expect($resVerifGrs['role'])->toBe('verifikasi_grs')
        ->and($resVerifGrs['jenis'])->toBe('GRS')
        ->and($resVerifGrs['no_grs'])->toBe('5007711111')
        ->and($resVerifGrs['no_po'])->toBe('5300067497');

    $rdtvSlip = makeTextPdfUpload('PKT-DO-1002.pdf', 'RETURN DELIVERY TO VENDOR RDTV (124) 5007722222 Purchase Order 5300067498');
    $resRdtvSlip = $parser->classifyDocument($rdtvSlip->getRealPath(), 'PKT-DO-1002.pdf', true);
    expect($resRdtvSlip['role'])->toBe('rdtv_slip')
        ->and($resRdtvSlip['jenis'])->toBe('RDTV')
        ->and($resRdtvSlip['no_rdtv'])->toBe('5007722222');

    $verifRdtv = makeTextPdfUpload('Verifikasi_RDTV_5007722222.pdf', 'VERIFIKASI PENOLAKAN BARANG Nomor PO 5300067498 Nomor GR (103) 5007700001 Nomor RDTV (124) 5007722222 Alasan Pengembalian Barang rusak');
    $resVerifRdtv = $parser->classifyDocument($verifRdtv->getRealPath(), 'Verifikasi_RDTV_5007722222.pdf', false);
    expect($resVerifRdtv['role'])->toBe('verifikasi_rdtv')
        ->and($resVerifRdtv['jenis'])->toBe('RDTV')
        ->and($resVerifRdtv['no_rdtv'])->toBe('5007722222');
});

test('inspect and precheck endpoints detect document metadata and enforce DO eligibility rules', function (): void {
    $user = User::create([
        'name' => 'Staff Combined',
        'email' => 'staff_combined@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('Staff Invoice');

    createEligibleDoFixture('PKT-DO-2001', '5300067501', 'SJ-2001');
    createEligibleDoFixture('PKT-DO-2002', '5300067502', 'SJ-2002', withPost103: false);

    $slip = makeTextPdfUpload('PKT-DO-2001.pdf', 'GOODS RECEIPT SLIP No. Release GR 5007733333 Purchase Order 5300067501');

    $inspectRes = $this->actingAs($user)->post(route('filament.admin.grs-rdtv.combined_upload.inspect'), [
        'file' => $slip,
        'original_name' => 'PKT-DO-2001.pdf',
    ]);

    $inspectRes->assertOk()->assertJsonPath('data.role', 'grs_slip')
        ->assertJsonPath('data.no_grs', '5007733333')
        ->assertJsonPath('data.no_po', '5300067501')
        ->assertJsonPath('data.filename_matches_do', true);

    $precheckRes = $this->actingAs($user)->postJson(route('filament.admin.grs-rdtv.combined_upload.precheck'), [
        'transaction_date' => '2026-11-24',
        'rows' => [
            [
                'row_id' => 'r1',
                'mode' => 'pair',
                'jenis' => 'GRS',
                'document_code' => 'PKT-DO-2001',
                'no_grs' => '5007733333',
                'no_po' => '5300067501',
                'verif_no_grs' => '5007733333',
                'verif_no_po' => '5300067501',
            ],
            [
                'row_id' => 'r2',
                'mode' => 'pair',
                'jenis' => 'GRS',
                'document_code' => 'PKT-DO-2002',
                'no_grs' => '5007733334',
                'no_po' => '5300067502',
                'verif_no_grs' => '5007733334',
                'verif_no_po' => '5300067502',
            ],
        ],
    ]);

    $precheckRes->assertOk()
        ->assertJsonPath('results.0.status', 'ready')
        ->assertJsonPath('results.1.status', 'blocked')
        ->assertJsonPath('results.1.reason_code', 'unposted_103');
});

test('Mode A pair GRS + Verifikasi creates GrsRdtv, InvoicingReceipt Terverifikasi, and dispatches Zimbra email', function (): void {
    fakeCombinedZimbraSuccess();

    $user = User::create([
        'name' => 'Staff Pair GRS',
        'email' => 'pair_grs@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('Staff Invoice');

    $fixture = createEligibleDoFixture('PKT-DO-3001', '5300067601', 'SJ-3001');

    $slip = makeTextPdfUpload('PKT-DO-3001.pdf', 'GOODS RECEIPT SLIP No. Release GR 5007744444 Purchase Order 5300067601 Delivery Date 19.11.2026');
    $verif = makeTextPdfUpload('Verifikasi_5007744444.pdf', 'VERIFIKASI PENERIMAAN BARANG Nomor GRS (105) 5007744444 Nomor PO 5300067601');

    $response = $this->actingAs($user)->post(route('filament.admin.grs-rdtv.combined_upload.commit'), [
        'mode' => 'pair',
        'jenis' => 'GRS',
        'transaction_date' => '2026-11-24',
        'send_email' => '1',
        'no_grs' => '5007744444',
        'no_po' => '5300067601',
        'slip_file' => $slip,
        'verif_file' => $verif,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('status', 'Terverifikasi')
        ->assertJsonPath('email_status', 'sent')
        ->assertJsonPath('no_grs', '5007744444');

    $fixture['do']->refresh();
    expect($fixture['do']->status)->toBe('GRS');

    $receipt = InvoicingReceipt::first();
    expect($receipt)->not->toBeNull()
        ->and($receipt->status)->toBe('Terverifikasi')
        ->and($receipt->no_grs)->toBe('5007744444')
        ->and($receipt->dokumen_verifikasi_grs)->toStartWith('verifikasi-grs/');

    Storage::disk('public')->assertExists($receipt->dokumen_verifikasi_grs);
    Storage::disk('public')->assertExists($receipt->grsRdtvItem->file_path);

    $emailLog = InvoicingEmailLog::first();
    expect($emailLog)->not->toBeNull()
        ->and($emailLog->status)->toBe('sent')
        ->and($emailLog->trigger_source)->toBe('combined_upload');
});

test('Mode A pair RDTV + Verifikasi RDTV saves reason, sets DO to RDTV, and dispatches email', function (): void {
    fakeCombinedZimbraSuccess();

    $user = User::create([
        'name' => 'Staff Pair RDTV',
        'email' => 'pair_rdtv@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('Staff Invoice');

    $fixture = createEligibleDoFixture('PKT-DO-3002', '5300067602', 'SJ-3002');

    $slip = makeTextPdfUpload('PKT-DO-3002.pdf', 'RETURN DELIVERY TO VENDOR RDTV (124) 5007755555 Purchase Order 5300067602');
    $verif = makeTextPdfUpload('Verifikasi_RDTV_5007755555.pdf', 'VERIFIKASI PENOLAKAN BARANG Nomor RDTV (124) 5007755555 Nomor PO 5300067602');

    $response = $this->actingAs($user)->post(route('filament.admin.grs-rdtv.combined_upload.commit'), [
        'mode' => 'pair',
        'jenis' => 'RDTV',
        'transaction_date' => '2026-11-24',
        'send_email' => '1',
        'reason' => 'Barang tidak sesuai spesifikasi',
        'no_rdtv' => '5007755555',
        'no_po' => '5300067602',
        'slip_file' => $slip,
        'verif_file' => $verif,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('jenis', 'RDTV')
        ->assertJsonPath('status', 'Terverifikasi')
        ->assertJsonPath('email_status', 'sent');

    $fixture['do']->refresh();
    expect($fixture['do']->status)->toBe('RDTV')
        ->and($fixture['do']->delay_reason)->toBe('RDTV')
        ->and($fixture['do']->delay_notes)->toBe('Barang tidak sesuai spesifikasi');

    $receipt = InvoicingReceipt::first();
    expect($receipt->jenis)->toBe('RDTV')
        ->and($receipt->no_rdtv)->toBe('5007755555')
        ->and($receipt->dokumen_verifikasi_grs)->toStartWith('verifikasi-rdtv/');
});

test('Mode B slip_only creates InvoicingReceipt Menunggu Verifikasi without verif document and without sending email', function (): void {
    fakeCombinedZimbraSuccess();

    $user = User::create([
        'name' => 'Staff Slip Only',
        'email' => 'slip_only@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('Staff Invoice');

    createEligibleDoFixture('PKT-DO-4001', '5300067701', 'SJ-4001');

    $slip = makeTextPdfUpload('PKT-DO-4001.pdf', 'GOODS RECEIPT SLIP No. Release GR 5007766666 Purchase Order 5300067701');

    $response = $this->actingAs($user)->post(route('filament.admin.grs-rdtv.combined_upload.commit'), [
        'mode' => 'slip_only',
        'jenis' => 'GRS',
        'transaction_date' => '2026-11-24',
        'send_email' => '1',
        'no_grs' => '5007766666',
        'no_po' => '5300067701',
        'slip_file' => $slip,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('status', 'Menunggu Verifikasi')
        ->assertJsonPath('email_status', 'waiting_verification');

    $receipt = InvoicingReceipt::first();
    expect($receipt)->not->toBeNull()
        ->and($receipt->status)->toBe('Menunggu Verifikasi')
        ->and($receipt->no_grs)->toBe('5007766666')
        ->and($receipt->dokumen_verifikasi_grs)->toBeNull();

    expect(InvoicingEmailLog::count())->toBe(0);
});

test('Mode C verif_only matches existing InvoicingReceipt and protects partial PO from overwrite', function (): void {
    fakeCombinedZimbraSuccess();

    $user = User::create([
        'name' => 'Staff Verif Only',
        'email' => 'verif_only@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('Staff Invoice');

    $batch = GrsRdtv::create(['transaction_date' => '2026-11-24', 'category' => 'GRS']);
    Storage::disk('public')->put('grs-rdtv-docs/PKT-DO-5001.pdf', '%PDF-1.4 dummy');
    $item = GrsRdtvItem::create([
        'grs_rdtv_id' => $batch->id,
        'document_code' => 'PKT-DO-5001',
        'file_path' => 'grs-rdtv-docs/PKT-DO-5001.pdf',
        'status' => 'Matched',
    ]);

    $receipt = InvoicingReceipt::create([
        'grs_rdtv_item_id' => $item->id,
        'jenis' => 'GRS',
        'no_grs' => '5007777777',
        'no_po' => '5300067801',
        'vendor_name' => 'PT Mitra Industri',
        'email_vendor' => 'vendor@mitra.test',
        'status' => 'Menunggu Verifikasi',
        'dokumen_verifikasi_grs' => null,
    ]);

    $verif = makeTextPdfUpload('Verifikasi_5007777777.pdf', 'VERIFIKASI PENERIMAAN BARANG Nomor GRS (105) 5007777777 Nomor PO 5300067801');

    $response = $this->actingAs($user)->post(route('filament.admin.grs-rdtv.combined_upload.commit'), [
        'mode' => 'verif_only',
        'jenis' => 'GRS',
        'transaction_date' => '2026-11-24',
        'send_email' => '1',
        'no_grs' => '5007777777',
        'no_po' => '5300067801',
        'verif_file' => $verif,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('status', 'Terverifikasi')
        ->assertJsonPath('email_status', 'sent');

    $receipt->refresh();
    expect($receipt->status)->toBe('Terverifikasi')
        ->and($receipt->dokumen_verifikasi_grs)->toStartWith('verifikasi-grs/');

    // Uji guard parsial PO: buat 2 receipt dengan PO sama tanpa no_grs
    InvoicingReceipt::create(['jenis' => 'GRS', 'no_po' => '5300067999', 'status' => 'Menunggu Verifikasi']);
    InvoicingReceipt::create(['jenis' => 'GRS', 'no_po' => '5300067999', 'status' => 'Menunggu Verifikasi']);

    $ambiguousRes = $this->actingAs($user)->postJson(route('filament.admin.grs-rdtv.combined_upload.precheck'), [
        'transaction_date' => '2026-11-24',
        'rows' => [[
            'row_id' => 'amb1',
            'mode' => 'verif_only',
            'jenis' => 'GRS',
            'no_po' => '5300067999',
        ]],
    ]);

    $ambiguousRes->assertOk()
        ->assertJsonPath('results.0.status', 'blocked')
        ->assertJsonPath('results.0.reason_code', 'ambiguous_partial');
});

test('1 PO with 2 distinct partial GRS + Verifikasi pairs creates 2 independent InvoicingReceipts without overwriting', function (): void {
    fakeCombinedZimbraSuccess();

    $user = User::create([
        'name' => 'Staff Partial PO',
        'email' => 'partial_po@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('Staff Invoice');

    createEligibleDoFixture('PKT-DO-67497', '5300067497', 'SJ-67497');

    $slip1 = makeTextPdfUpload('PKT-DO-67497.pdf', 'GOODS RECEIPT SLIP No. Release GR 5007800001 Purchase Order 5300067497');
    $verif1 = makeTextPdfUpload('Verif_1.pdf', 'VERIFIKASI PENERIMAAN BARANG Nomor GRS (105) 5007800001 Nomor PO 5300067497');

    $res1 = $this->actingAs($user)->post(route('filament.admin.grs-rdtv.combined_upload.commit'), [
        'mode' => 'pair',
        'jenis' => 'GRS',
        'transaction_date' => '2026-11-24',
        'send_email' => '1',
        'no_grs' => '5007800001',
        'no_po' => '5300067497',
        'slip_file' => $slip1,
        'verif_file' => $verif1,
    ]);
    $res1->assertOk()->assertJsonPath('success', true);

    $slip2 = makeTextPdfUpload('PKT-DO-67497.pdf', 'GOODS RECEIPT SLIP No. Release GR 5007800002 Purchase Order 5300067497');
    $verif2 = makeTextPdfUpload('Verif_2.pdf', 'VERIFIKASI PENERIMAAN BARANG Nomor GRS (105) 5007800002 Nomor PO 5300067497');

    $res2 = $this->actingAs($user)->post(route('filament.admin.grs-rdtv.combined_upload.commit'), [
        'mode' => 'pair',
        'jenis' => 'GRS',
        'transaction_date' => '2026-11-24',
        'send_email' => '1',
        'no_grs' => '5007800002',
        'no_po' => '5300067497',
        'slip_file' => $slip2,
        'verif_file' => $verif2,
    ]);
    $res2->assertOk()->assertJsonPath('success', true);

    $receipts = InvoicingReceipt::where('no_po', '5300067497')->orderBy('id')->get();
    expect($receipts)->toHaveCount(2)
        ->and($receipts[0]->no_grs)->toBe('5007800001')
        ->and($receipts[1]->no_grs)->toBe('5007800002')
        ->and($receipts[0]->dokumen_verifikasi_grs)->not->toBe($receipts[1]->dokumen_verifikasi_grs);
});

test('CombinedGrsVerificationUpload page and header actions on ListGrsRdtvs and ListInvoicingReceipts are accessible', function (): void {
    $user = User::create([
        'name' => 'Dev Access',
        'email' => 'dev_access@example.com',
        'npk' => '210099',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('Developer');

    Livewire::actingAs($user)
        ->test(CombinedGrsVerificationUpload::class)
        ->assertOk()
        ->assertSee('Upload Gabungan GRS/RDTV & Verifikasi');

    Livewire::actingAs($user)
        ->test(ListGrsRdtvs::class)
        ->assertActionExists('combinedUpload');

    Livewire::actingAs($user)
        ->test(ListInvoicingReceipts::class)
        ->assertActionExists('combinedGrsVerificationUpload');
});
