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
use App\Models\LocationReceiving;
use App\Models\PicMaterialAssignment;
use App\Models\PurchaseOrderIssued;
use App\Models\QcHistory;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CombinedGrsVerificationService;
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

    if (! DbSchema::hasTable('location_receivings')) {
        DbSchema::create('location_receivings', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('purchase_order_issueds')) {
        DbSchema::create('purchase_order_issueds', function (Blueprint $table): void {
            $table->id();
            $table->string('purchase_order_and_item')->nullable();
            $table->string('purchase_order_no')->unique();
            $table->integer('item_no')->default(10);
            $table->string('material_type')->nullable();
            $table->string('mrp_type')->default('ND');
            $table->string('material_code')->nullable();
            $table->text('description')->nullable();
            $table->decimal('qty_po', 15, 4)->nullable();
            $table->string('uoi', 20)->nullable();
            $table->decimal('net_price', 18, 4)->default(0);
            $table->decimal('total_amount_in_lc', 18, 2)->nullable();
            $table->string('vendor_id')->nullable();
            $table->string('vendor_name')->nullable();
            $table->date('date_create')->nullable();
            $table->date('delivery_date_po')->nullable();
            $table->string('currency', 5)->default('IDR');
            $table->string('requisitioner')->default('RECEIVING');
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('delivery_order_receipts')) {
        DbSchema::create('delivery_order_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('delivery_order_no', 50)->nullable();
            $table->date('received_date')->nullable();
            $table->date('physical_received_date')->nullable();
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
            $table->string('current_location')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('delivery_order_receipt_details')) {
        DbSchema::create('delivery_order_receipt_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_order_receipt_id');
            $table->foreignId('purchase_order_issued_id')->nullable();
            $table->integer('item_no')->default(10);
            $table->string('material_code')->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 4)->nullable();
            $table->string('uoi', 20)->nullable();
            $table->string('mrp_type')->nullable();
            $table->string('material_type')->nullable();
            $table->decimal('total_amount_snapshot', 18, 2)->nullable();
            $table->foreignId('location_id')->nullable();
            $table->boolean('is_different_location')->default(false);
            $table->boolean('is_qty_tolerance')->default(false);
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

test('CombinedGrsVerificationUpload classifies, prechecks, and commits Karung Slip GRS + Verifikasi GR (101) pair', function (): void {
    fakeCombinedZimbraSuccess();

    $user = User::create([
        'name' => 'Staff Karung Combined',
        'email' => 'karung_combined@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('Staff Invoice');

    // ZPM (Karung) tanpa QC Kembali tetap eligible selama Post 103 terisi
    $vendor = Vendor::create([
        'name' => 'SAMISURYAINDAH PLASTIK',
        'vendor_number' => 'V-9408',
        'email' => 'karung@samisurya.test',
    ]);
    $po = PurchaseOrderIssued::create([
        'purchase_order_no' => '5000009408',
        'material_type' => 'ZPM',
        'material_code' => '5000232',
        'description' => 'SACK:UREA GRANUL;PIHC;SUB;50KG',
        'qty_po' => 1080000,
        'uoi' => 'EA',
        'total_amount_in_lc' => 4345920000,
        'vendor_id' => $vendor->vendor_number,
        'vendor_name' => $vendor->name,
    ]);
    $do = DeliveryOrderReceipt::create([
        'delivery_order_no' => '08517/08518',
        'received_date' => '2026-11-21',
        'document_code' => '5000009408-01-0851708518-21112026-TAHAP4SCI',
        'status' => 'Diterima',
        'post_103' => now(),
    ]);
    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $do->id,
        'purchase_order_issued_id' => $po->id,
        'material_code' => '5000232',
        'description' => 'SACK:UREA GRANUL;PIHC;SUB;50KG',
        'quantity' => 80000,
        'uoi' => 'EA',
        'material_type' => 'ZPM',
        'total_amount_snapshot' => 321920000,
    ]);

    $slip = makeTextPdfUpload(
        '5000009408-01-0851708518-21112026-TAHAP4SCI.pdf',
        'GOODS RECEIPT SLIP No Release GR : 5007760024 No PO : 5000009408 No. Surat Jalan : 08517/08518'
    );
    $verif = makeTextPdfUpload(
        'Verifikasi_Karung_5007760024.pdf',
        'VERIFIKASI PENERIMAAN BARANG Nomor: GRS- 61937/D/PL/D52100/IT/2026 Bersama ini terlampir dokumen Goods Receipt Slip Nomor PO : 5000009408 Tahap 4 Nomor GR (101) : 5007760024 Nama Penyedia Barang : PT Samisuryaindah Plastik Nomor DO/SJ : 08517/08518'
    );

    $inspectVerif = $this->actingAs($user)->post(route('filament.admin.grs-rdtv.combined_upload.inspect'), [
        'file' => $verif,
        'original_name' => 'Verifikasi_Karung_5007760024.pdf',
    ]);
    $inspectVerif->assertOk()
        ->assertJsonPath('data.role', 'verifikasi_grs')
        ->assertJsonPath('data.jenis', 'GRS')
        ->assertJsonPath('data.no_grs', '5007760024')
        ->assertJsonPath('data.no_po', '5000009408')
        ->assertJsonPath('data.no_do', '08517/08518');

    $commitRes = $this->actingAs($user)->post(route('filament.admin.grs-rdtv.combined_upload.commit'), [
        'mode' => 'pair',
        'jenis' => 'GRS',
        'transaction_date' => '2026-11-24',
        'send_email' => '1',
        'no_grs' => '5007760024',
        'no_po' => '5000009408',
        'no_do' => '08517/08518',
        'slip_file' => $slip,
        'verif_file' => $verif,
    ]);

    $commitRes->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('jenis', 'GRS')
        ->assertJsonPath('no_grs', '5007760024')
        ->assertJsonPath('no_po', '5000009408')
        ->assertJsonPath('status', 'Terverifikasi')
        ->assertJsonPath('email_status', 'sent');
});

test('Karung PO with missing material_type auto-classifies as ZPM, skips QC Kembali, and routes strictly to ZPM PICs', function (): void {
    fakeCombinedZimbraSuccess();

    $zpmPic1 = User::create([
        'name' => 'Camelya ZPM',
        'email' => 'camelya_zpm@example.com',
        'password' => bcrypt('password'),
    ]);
    $zpmPic1->assignRole('Staff Invoice');

    $zpmPic2 = User::create([
        'name' => 'Rizki ZPM',
        'email' => 'rizki_zpm@example.com',
        'password' => bcrypt('password'),
    ]);
    $zpmPic2->assignRole('Staff Invoice');

    $otherStaff = User::create([
        'name' => 'Staff Lain Non ZPM',
        'email' => 'staff_other@example.com',
        'password' => bcrypt('password'),
    ]);
    $otherStaff->assignRole('Staff Invoice');

    $zpmRule = PicMaterialAssignment::create([
        'material_type' => 'ZPM',
        'assignment_mode' => 'user',
        'user_id' => $zpmPic1->id,
        'notes' => 'PIC Khusus Karung / Packaging Material',
    ]);
    $zpmRule->users()->sync([$zpmPic1->id, $zpmPic2->id]);

    // Guard false-positive: jasa inspeksi karung & geotextile TIDAK boleh dianggap ZPM
    expect(InvoicingReceipt::resolveMaterialType(null, null, 'PEMERIKSAAN KUALITAS KARUNG UREA & NPK', '5300061045'))
        ->toBe('Non Stock')
        ->and(InvoicingReceipt::resolveMaterialType(null, null, 'POLYFELT NONWOVEN GEOTEXTILE', '5300061797'))
        ->toBe('Non Stock')
        ->and(InvoicingReceipt::resolveMaterialType(null, '5000024', 'SACK:UREA;PIHC;SS;50KG', '5000009305'))
        ->toBe('ZPM');

    // PO Karung tanpa material_type (null) dan tanpa QC Kembali
    $vendor = Vendor::create([
        'name' => 'POLIPLAS INDAH SEJAHTERA',
        'vendor_number' => 'V-9305',
        'email' => 'poliplas@test.local',
    ]);
    $po = PurchaseOrderIssued::create([
        'purchase_order_no' => '5000009305',
        'material_type' => null,
        'material_code' => '5000024',
        'description' => 'SACK:UREA;PIHC;SS;50KG',
        'qty_po' => 50000,
        'uoi' => 'EA',
        'total_amount_in_lc' => 200000000,
        'vendor_id' => $vendor->vendor_number,
        'vendor_name' => $vendor->name,
    ]);
    $do = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'SJ-9305',
        'received_date' => '2026-11-22',
        'document_code' => '5000009305-01-SJ9305',
        'status' => 'Diterima',
        'post_103' => now(),
    ]);
    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $do->id,
        'purchase_order_issued_id' => $po->id,
        'material_code' => '5000024',
        'description' => 'SACK:UREA;PIHC;SS;50KG',
        'quantity' => 10000,
        'uoi' => 'EA',
        'material_type' => null,
        'total_amount_snapshot' => 40000000,
    ]);

    $slip = makeTextPdfUpload('5000009305-01-SJ9305.pdf', 'GOODS RECEIPT SLIP No Release GR : 5007761111 No PO : 5000009305');

    $res = $this->actingAs($otherStaff)->post(route('filament.admin.grs-rdtv.combined_upload.commit'), [
        'mode' => 'slip_only',
        'jenis' => 'GRS',
        'transaction_date' => '2026-11-24',
        'send_email' => '0',
        'no_grs' => '5007761111',
        'no_po' => '5000009305',
        'slip_file' => $slip,
    ]);

    $res->assertOk()->assertJsonPath('success', true);

    $receipt = InvoicingReceipt::where('no_po', '5000009305')->first();
    expect($receipt)->not->toBeNull()
        ->and($receipt->material_type)->toBe('ZPM')
        ->and($receipt->pic_id)->toBeIn([$zpmPic1->id, $zpmPic2->id])
        ->and($receipt->pic_id)->not->toBe($otherStaff->id);
});

test('Combined upload keeps existing Slip intact without overwriting when uploading pair for already-uploaded GRS', function (): void {
    fakeCombinedZimbraSuccess();

    $user = User::create([
        'name' => 'Staff Reuse Slip',
        'email' => 'reuse_slip@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('Staff Invoice');

    createEligibleDoFixture('PKT-DO-8801', '5300068801', 'SJ-8801');

    // 1. Unggah Slip Saja terlebih dahulu
    $originalSlip = makeTextPdfUpload('PKT-DO-8801.pdf', 'GOODS RECEIPT SLIP No Release GR : 5007888801 No PO : 5300068801');
    $firstRes = $this->actingAs($user)->post(route('filament.admin.grs-rdtv.combined_upload.commit'), [
        'mode' => 'slip_only',
        'jenis' => 'GRS',
        'transaction_date' => '2026-11-24',
        'send_email' => '0',
        'no_grs' => '5007888801',
        'no_po' => '5300068801',
        'slip_file' => $originalSlip,
    ]);
    $firstRes->assertOk()->assertJsonPath('status', 'Menunggu Verifikasi');

    $existingItem = GrsRdtvItem::first();
    $existingReceipt = InvoicingReceipt::first();
    $originalSlipPath = $existingItem->file_path;

    // 2. Precheck Slip Saja lagi -> harus diblokir (tidak boleh menimpa/duplikat)
    $precheckSlipOnly = $this->actingAs($user)->postJson(route('filament.admin.grs-rdtv.combined_upload.precheck'), [
        'transaction_date' => '2026-11-24',
        'rows' => [[
            'row_id' => 'dup_slip',
            'mode' => 'slip_only',
            'jenis' => 'GRS',
            'document_code' => 'PKT-DO-8801',
            'no_grs' => '5007888801',
            'no_po' => '5300068801',
        ]],
    ]);
    $precheckSlipOnly->assertOk()
        ->assertJsonPath('results.0.status', 'blocked')
        ->assertJsonPath('results.0.reason_code', 'already_processed');

    // 3. Precheck Pasangan (Slip lama + Verifikasi baru) -> status warning reuse_existing_slip
    $precheckPair = $this->actingAs($user)->postJson(route('filament.admin.grs-rdtv.combined_upload.precheck'), [
        'transaction_date' => '2026-11-24',
        'rows' => [[
            'row_id' => 'reuse_pair',
            'mode' => 'pair',
            'jenis' => 'GRS',
            'document_code' => 'PKT-DO-8801',
            'no_grs' => '5007888801',
            'no_po' => '5300068801',
            'verif_no_grs' => '5007888801',
            'verif_no_po' => '5300068801',
        ]],
    ]);
    $precheckPair->assertOk()
        ->assertJsonPath('results.0.status', 'warning')
        ->assertJsonPath('results.0.reason_code', 'reuse_existing_slip')
        ->assertJsonPath('results.0.already_has_verif', false)
        ->assertJsonPath('results.0.target_receipt_id', $existingReceipt->id);

    // 4. Commit Pasangan -> Slip lama tetap dipertahankan, tidak membuat item/invoice duplikat, hanya memasang Verifikasi
    $newSlip = makeTextPdfUpload('PKT-DO-8801.pdf', 'GOODS RECEIPT SLIP No Release GR : 5007888801 No PO : 5300068801');
    $verif = makeTextPdfUpload('Verif_8801.pdf', 'VERIFIKASI PENERIMAAN BARANG Nomor GRS (105) 5007888801 Nomor PO 5300068801');

    $commitReuse = $this->actingAs($user)->post(route('filament.admin.grs-rdtv.combined_upload.commit'), [
        'mode' => 'pair',
        'jenis' => 'GRS',
        'transaction_date' => '2026-11-24',
        'send_email' => '1',
        'no_grs' => '5007888801',
        'no_po' => '5300068801',
        'target_receipt_id' => $existingReceipt->id,
        'slip_file' => $newSlip,
        'verif_file' => $verif,
    ]);
    $commitReuse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('reused_existing_slip', true)
        ->assertJsonPath('receipt_id', $existingReceipt->id)
        ->assertJsonPath('status', 'Terverifikasi')
        ->assertJsonPath('email_status', 'sent');

    expect(GrsRdtvItem::count())->toBe(1)
        ->and(InvoicingReceipt::count())->toBe(1)
        ->and($existingItem->fresh()->file_path)->toBe($originalSlipPath)
        ->and($existingReceipt->fresh()->status)->toBe('Terverifikasi')
        ->and($existingReceipt->fresh()->dokumen_verifikasi_grs)->toStartWith('verifikasi-grs/');

    // 5. Precheck ulang setelah sudah terverifikasi -> already_has_verif = true (tidak dicentang otomatis)
    $precheckAlreadyVerif = $this->actingAs($user)->postJson(route('filament.admin.grs-rdtv.combined_upload.precheck'), [
        'transaction_date' => '2026-11-24',
        'rows' => [[
            'row_id' => 'already_v',
            'mode' => 'verif_only',
            'jenis' => 'GRS',
            'no_grs' => '5007888801',
            'no_po' => '5300068801',
        ]],
    ]);
    $precheckAlreadyVerif->assertOk()
        ->assertJsonPath('results.0.status', 'warning')
        ->assertJsonPath('results.0.reason_code', 'already_verified')
        ->assertJsonPath('results.0.already_has_verif', true);
});

test('Missing DO auto-creates PO Terbit, new LocationReceiving, DO Receipt with Tanggal Penerimaan & category-based Receiver (Arief/Michael/Dio)', function (): void {
    fakeCombinedZimbraSuccess();

    $arief = User::create(['name' => 'Arief', 'email' => 'arief@receiving.com', 'password' => bcrypt('password')]);
    $michael = User::create(['name' => 'Michael Siburian', 'email' => 'michael@receiving.com', 'password' => bcrypt('password')]);
    $dio = User::create(['name' => 'Dio Indranata', 'email' => 'dio@receiving.com', 'password' => bcrypt('password')]);

    $zpmPic = User::create(['name' => 'Rizki Ardita', 'email' => 'rizki@receiving.com', 'password' => bcrypt('password')]);
    $zpmPic->assignRole('Staff Invoice');
    $zpmRule = PicMaterialAssignment::create(['material_type' => 'ZPM', 'assignment_mode' => 'user', 'user_id' => $zpmPic->id]);
    $zpmRule->users()->sync([$zpmPic->id]);

    $actor = User::create(['name' => 'Uploader Staff', 'email' => 'uploader@receiving.com', 'password' => bcrypt('password')]);
    $actor->assignRole('Staff Invoice');

    Vendor::create([
        'name' => 'SAMISURYAINDAH PLASTIK',
        'vendor_number' => 'V-9999',
        'email' => 'samisurya@test.local',
    ]);

    // 1. Karung (ZPM) tanpa DO & tanpa PO Terbit & Lokasi baru "Gudang Banyuwangi" -> Penerima = Arief
    $slipKarung = makeTextPdfUpload(
        'GRS TAHAP 2-5007734805.pdf',
        "GOODS RECEIPT SLIP\nNo Release GR : 5007734805\nNo PO : 5000009999\nTanggal PO : 23.07.2026\nTanggal Penerimaan : 18.11.2026\nTanggal Release GR : 19.11.2026\nNo. Surat Jalan : 08517/08518\n1 5000232 SACK:UREA GRANUL;PIHC;SUB;50KG D205 EA 80.000\n0 Gudang Banyuwangi\nTotal : 321.920.00\n0"
    );
    $verifKarung = makeTextPdfUpload(
        'Verifikasi_5007734805.pdf',
        "VERIFIKASI PENERIMAAN BARANG\nNomor: GRS- 61937/D/PL/D52100/IT/2026\nNomor PO : 5000009999 Tahap 2\nNomor GR (101) : 5007734805\nNama Penyedia Barang : PT Samisuryaindah Plastik\nNilai PO : Rp4.345.920.000\nNilai GR : Rp321.920.000\nNomor DO/SJ : 08517/08518\nLokasi Penerimaan : Gudang Banyuwangi\nDapat kami sampaikan"
    );

    $precheckRes = $this->actingAs($actor)->postJson(route('filament.admin.grs-rdtv.combined_upload.precheck'), [
        'transaction_date' => '2026-11-24',
        'rows' => [[
            'row_id' => 'karung_auto',
            'mode' => 'pair',
            'jenis' => 'GRS',
            'document_code' => 'GRS TAHAP 2-5007734805',
            'no_grs' => '5007734805',
            'no_po' => '5000009999',
            'verif_no_grs' => '5007734805',
            'verif_no_po' => '5000009999',
            'lokasi_penerimaan' => 'Gudang Banyuwangi',
            'tanggal_penerimaan' => '2026-11-18',
            'material_code' => '5000232',
            'nama_barang' => 'SACK:UREA GRANUL;PIHC;SUB;50KG',
        ]],
    ]);

    $precheckRes->assertOk()
        ->assertJsonPath('results.0.status', 'warning')
        ->assertJsonPath('results.0.reason_code', 'auto_create_do')
        ->assertJsonPath('results.0.will_auto_create_do', true)
        ->assertJsonPath('results.0.will_auto_create_po', true)
        ->assertJsonPath('results.0.auto_receiver_name', 'Arief')
        ->assertJsonPath('results.0.auto_location_name', 'GUDANG BANYUWANGI');

    $commitKarung = $this->actingAs($actor)->post(route('filament.admin.grs-rdtv.combined_upload.commit'), [
        'mode' => 'pair',
        'jenis' => 'GRS',
        'transaction_date' => '2026-11-24',
        'send_email' => '1',
        'no_grs' => '5007734805',
        'no_po' => '5000009999',
        'no_do' => '08517/08518',
        'lokasi_penerimaan' => 'Gudang Banyuwangi',
        'tanggal_penerimaan' => '2026-11-18',
        'material_code' => '5000232',
        'nama_barang' => 'SACK:UREA GRANUL;PIHC;SUB;50KG',
        'slip_file' => $slipKarung,
        'verif_file' => $verifKarung,
    ]);

    $commitKarung->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('auto_created_do', true)
        ->assertJsonPath('auto_created_po', true)
        ->assertJsonPath('auto_created_location', true)
        ->assertJsonPath('auto_receiver_name', 'Arief')
        ->assertJsonPath('auto_location_name', 'GUDANG BANYUWANGI')
        ->assertJsonPath('status', 'Terverifikasi')
        ->assertJsonPath('email_status', 'sent');

    $createdLoc = LocationReceiving::where('name', 'GUDANG BANYUWANGI')->first();
    expect($createdLoc)->not->toBeNull();

    $createdPo = PurchaseOrderIssued::where('purchase_order_no', '5000009999')->first();
    expect($createdPo)->not->toBeNull()
        ->and($createdPo->material_type)->toBe('ZPM');

    $createdDo = DeliveryOrderReceipt::where('document_code', 'GRS TAHAP 2-5007734805')->first();
    expect($createdDo)->not->toBeNull()
        ->and($createdDo->received_date->toDateString())->toBe('2026-11-18')
        ->and((int) $createdDo->received_by)->toBe($arief->id)
        ->and($createdDo->current_location)->toBe('GUDANG BANYUWANGI');

    $receiptKarung = InvoicingReceipt::where('no_po', '5000009999')->first();
    expect($receiptKarung)->not->toBeNull()
        ->and($receiptKarung->material_type)->toBe('ZPM')
        ->and((int) $receiptKarung->pic_id)->toBe($zpmPic->id)
        ->and($receiptKarung->status)->toBe('Terverifikasi');

    // 2. Sparepart (ZSP) tanpa DO -> Penerima = Michael Siburian; Bahan Baku (ZRM) tanpa DO -> Penerima = Dio Indranata
    $service = app(CombinedGrsVerificationService::class);
    expect($service->resolveAutoReceiverInfo('ZPM')['user_id'])->toBe($arief->id)
        ->and($service->resolveAutoReceiverInfo('ZSP')['user_id'])->toBe($michael->id)
        ->and($service->resolveAutoReceiverInfo('Non Stock')['user_id'])->toBe($michael->id)
        ->and($service->resolveAutoReceiverInfo('ZRM')['user_id'])->toBe($dio->id)
        ->and($service->resolveAutoReceiverInfo('ZSM')['user_id'])->toBe($dio->id);
});

test('Chrome PDF.js viewer printed Verifikasi PDF (header/footer text > 150 chars, image body) triggers needs_ocr=true and accepts client OCR metadata', function (): void {
    $user = User::create([
        'name' => 'Staff PDFJS',
        'email' => 'pdfjs_staff@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('Staff Invoice');

    // Simulasikan PDF cetak Chrome PDF.js viewer dari Digital Office (hanya punya header/footer teks, body gambar kosong)
    $chromePrintPdf = makeTextPdfUpload(
        'Verifikasi_Karung_Tahap3.pdf',
        '11/24/26, 7:51 PM PDF.js viewer https://dof.pupuk-indonesia.com/FileHandling/Print/53e0802c-d832-4c7a-bc44-d4bc2432bf1b?type=PRINT_DOC&hmm=oprx 1/1'
    );

    // 1. Inspect awal WAJIB menandai needs_ocr = true walau teks header/footer > 150 karakter
    $firstInspect = $this->actingAs($user)->post(route('filament.admin.grs-rdtv.combined_upload.inspect'), [
        'file' => $chromePrintPdf,
        'original_name' => 'Verifikasi_Karung_Tahap3.pdf',
    ]);

    $firstInspect->assertOk()
        ->assertJsonPath('data.needs_ocr', true)
        ->assertJsonPath('data.no_grs', null);

    // 2. Re-inspect setelah OCR client-side mengisi metadata + ocr_done=1
    $secondInspect = $this->actingAs($user)->post(route('filament.admin.grs-rdtv.combined_upload.inspect'), [
        'file' => $chromePrintPdf,
        'original_name' => 'Verifikasi_Karung_Tahap3.pdf',
        'ocr_done' => '1',
        'jenis' => 'GRS',
        'role' => 'verifikasi_grs',
        'no_grs' => '5007754968',
        'no_po' => '5000009408',
        'no_do' => '20261108407',
        'lokasi_penerimaan' => 'Gudang Banyuwangi',
    ]);

    $secondInspect->assertOk()
        ->assertJsonPath('data.needs_ocr', false)
        ->assertJsonPath('data.role', 'verifikasi_grs')
        ->assertJsonPath('data.jenis', 'GRS')
        ->assertJsonPath('data.no_grs', '5007754968')
        ->assertJsonPath('data.no_po', '5000009408')
        ->assertJsonPath('data.no_do', '20261108407')
        ->assertJsonPath('data.lokasi_penerimaan', 'Gudang Banyuwangi');
});

test('GrsPdfParserService extracts vendor_name cleanly without bleeding into previous address line', function (): void {
    $parser = app(GrsPdfParserService::class);

    $rawSapHeader = "Ciruas Kab. Serang \n\nJl. Raya Serang KM. 80 Cimiung, Kas \n\nMURNI MAPAN MANDIRI, PT. \n\n: \n\n: \n\nNama Vendor \n\nAlamat Vendor \n\nGOODS RECEIPT SLIP \nNo Release GR : 5007734805\nNo PO : 5000009407";
    $parsed = $parser->parse($rawSapHeader);

    expect($parsed['vendor_name'])->toBe('MURNI MAPAN MANDIRI, PT.')
        ->and($parsed['no_grs'])->toBe('5007734805')
        ->and($parsed['no_po'])->toBe('5000009407');
});

test('Skipped email can be updated with vendor_email and resent via resend-email endpoint', function (): void {
    fakeCombinedZimbraSuccess();

    $user = User::create([
        'name' => 'Staff Resend',
        'email' => 'resend_staff@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('Staff Invoice');

    // Vendor tanpa alamat email
    $fixture = createEligibleDoFixture('PKT-DO-9901', '5300069901', 'SJ-9901', 'PT Tanpa Email', '');

    $slip = makeTextPdfUpload('PKT-DO-9901.pdf', 'GOODS RECEIPT SLIP No Release GR : 5007990101 No PO : 5300069901');
    $verif = makeTextPdfUpload('Verif_9901.pdf', 'VERIFIKASI PENERIMAAN BARANG Nomor GRS (105) 5007990101 Nomor PO 5300069901');

    // 1. Commit awal -> tersimpan & Terverifikasi, namun email_status = skipped
    $commitRes = $this->actingAs($user)->post(route('filament.admin.grs-rdtv.combined_upload.commit'), [
        'mode' => 'pair',
        'jenis' => 'GRS',
        'transaction_date' => '2026-11-24',
        'send_email' => '1',
        'no_grs' => '5007990101',
        'no_po' => '5300069901',
        'slip_file' => $slip,
        'verif_file' => $verif,
    ]);

    $commitRes->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('status', 'Terverifikasi')
        ->assertJsonPath('email_status', 'skipped');

    $receiptId = $commitRes->json('receipt_id');

    // 2. Email format tidak valid -> ditolak 422 dengan pesan jelas
    $invalidRes = $this->actingAs($user)->postJson(route('filament.admin.grs-rdtv.combined_upload.resend_email'), [
        'receipt_id' => $receiptId,
        'vendor_email' => 'bukan-email-valid',
    ]);
    $invalidRes->assertStatus(422)
        ->assertJsonPath('success', false);

    // 3. Isi email vendor valid & kirim ulang -> tersimpan ke receipt & master vendor, email terkirim!
    $resendRes = $this->actingAs($user)->postJson(route('filament.admin.grs-rdtv.combined_upload.resend_email'), [
        'receipt_id' => $receiptId,
        'vendor_email' => 'info@tanpaemail.test',
    ]);
    $resendRes->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('email_status', 'sent')
        ->assertJsonPath('email_vendor', 'info@tanpaemail.test');

    expect(InvoicingReceipt::find($receiptId)->email_vendor)->toBe('info@tanpaemail.test')
        ->and($fixture['vendor']->fresh()->email)->toContain('info@tanpaemail.test');
});

test('Re-uploading downloaded Slip with stored timestamp_hash suffix matches existing DO without duplicating DO or Invoice', function (): void {
    fakeCombinedZimbraSuccess();

    $user = User::create([
        'name' => 'Staff Suffix Dedup',
        'email' => 'suffix_dedup@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('Staff Invoice');

    createEligibleDoFixture('5000009305-01-S-PKT04726-17112026-TAHAP5SCI', '5000009305', 'S-PKT04726');

    // 1. Unggah Slip Saja awal
    $firstSlip = makeTextPdfUpload('5000009305-01-S-PKT04726-17112026-TAHAP5SCI.pdf', 'GOODS RECEIPT SLIP No Release GR : 5007761421 No PO : 5000009305');
    $this->actingAs($user)->post(route('filament.admin.grs-rdtv.combined_upload.commit'), [
        'mode' => 'slip_only',
        'jenis' => 'GRS',
        'transaction_date' => '2026-11-24',
        'send_email' => '0',
        'no_grs' => '5007761421',
        'no_po' => '5000009305',
        'slip_file' => $firstSlip,
    ])->assertOk();

    $existingReceipt = InvoicingReceipt::where('no_grs', '5007761421')->first();
    expect($existingReceipt)->not->toBeNull();

    // 2. Unggah ulang Slip yang diunduh dengan akhiran __20261124083036_p96zzg + Verifikasi
    $downloadedSlip = makeTextPdfUpload('5000009305-01-S-PKT04726-17112026-TAHAP5SCI__20261124083036_p96zzg.pdf', 'GOODS RECEIPT SLIP No Release GR : 5007761421 No PO : 5000009305');
    $verif = makeTextPdfUpload('5000009305-Verifikasi-TAHAP5SCI.pdf', 'VERIFIKASI PENERIMAAN BARANG Nomor GR (101) 5007761421 Nomor PO 5000009305');

    $inspectRes = $this->actingAs($user)->post(route('filament.admin.grs-rdtv.combined_upload.inspect'), [
        'file' => $downloadedSlip,
    ]);
    $inspectRes->assertOk()
        ->assertJsonPath('data.document_code', '5000009305-01-S-PKT04726-17112026-TAHAP5SCI')
        ->assertJsonPath('data.filename_matches_do', true);

    $precheckRes = $this->actingAs($user)->postJson(route('filament.admin.grs-rdtv.combined_upload.precheck'), [
        'transaction_date' => '2026-11-24',
        'rows' => [[
            'row_id' => 'suffix_pair',
            'mode' => 'pair',
            'jenis' => 'GRS',
            'document_code' => '5000009305-01-S-PKT04726-17112026-TAHAP5SCI__20261124083036_p96zzg',
            'no_grs' => '5007761421',
            'no_po' => '5000009305',
            'verif_no_grs' => '5007761421',
            'verif_no_po' => '5000009305',
        ]],
    ]);
    $precheckRes->assertOk()
        ->assertJsonPath('results.0.reason_code', 'reuse_existing_slip')
        ->assertJsonPath('results.0.will_auto_create_do', false)
        ->assertJsonPath('results.0.target_receipt_id', $existingReceipt->id);

    $commitRes = $this->actingAs($user)->post(route('filament.admin.grs-rdtv.combined_upload.commit'), [
        'mode' => 'pair',
        'jenis' => 'GRS',
        'transaction_date' => '2026-11-24',
        'send_email' => '1',
        'no_grs' => '5007761421',
        'no_po' => '5000009305',
        'slip_file' => $downloadedSlip,
        'verif_file' => $verif,
    ]);
    $commitRes->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('receipt_id', $existingReceipt->id)
        ->assertJsonPath('reused_existing_slip', true)
        ->assertJsonPath('status', 'Terverifikasi');

    expect(DeliveryOrderReceipt::count())->toBe(1)
        ->and(InvoicingReceipt::count())->toBe(1)
        ->and($existingReceipt->fresh()->status)->toBe('Terverifikasi')
        ->and($existingReceipt->fresh()->dokumen_verifikasi_grs)->toStartWith('verifikasi-grs/');
});

test('Uploading renamed Slip whose No. GRS already exists reuses existing Invoice instead of auto-creating duplicate DO and Invoice', function (): void {
    fakeCombinedZimbraSuccess();

    $user = User::create([
        'name' => 'Staff Renamed Dedup',
        'email' => 'renamed_dedup@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('Staff Invoice');

    createEligibleDoFixture('PKT-DO-7701', '5000009305', 'SJ-7701');

    // 1. Unggah Slip Saja awal
    $firstSlip = makeTextPdfUpload('PKT-DO-7701.pdf', 'GOODS RECEIPT SLIP No Release GR : 5007761425 No PO : 5000009305');
    $this->actingAs($user)->post(route('filament.admin.grs-rdtv.combined_upload.commit'), [
        'mode' => 'slip_only',
        'jenis' => 'GRS',
        'transaction_date' => '2026-11-24',
        'send_email' => '0',
        'no_grs' => '5007761425',
        'no_po' => '5000009305',
        'slip_file' => $firstSlip,
    ])->assertOk();

    $existingReceipt = InvoicingReceipt::where('no_grs', '5007761425')->first();
    expect($existingReceipt)->not->toBeNull();

    // 2. Unggah ulang dengan nama berkas berbeda total, tetapi No. GRS sama (5007761425)
    $renamedSlip = makeTextPdfUpload('Slip_Karung_Revisi_Final.pdf', 'GOODS RECEIPT SLIP No Release GR : 5007761425 No PO : 5000009305');
    $verif = makeTextPdfUpload('Verif_Karung_Final.pdf', 'VERIFIKASI PENERIMAAN BARANG Nomor GR (101) 5007761425 Nomor PO 5000009305');

    $precheckRes = $this->actingAs($user)->postJson(route('filament.admin.grs-rdtv.combined_upload.precheck'), [
        'transaction_date' => '2026-11-24',
        'rows' => [[
            'row_id' => 'renamed_pair',
            'mode' => 'pair',
            'jenis' => 'GRS',
            'document_code' => 'Slip_Karung_Revisi_Final',
            'no_grs' => '5007761425',
            'no_po' => '5000009305',
            'verif_no_grs' => '5007761425',
            'verif_no_po' => '5000009305',
        ]],
    ]);
    $precheckRes->assertOk()
        ->assertJsonPath('results.0.status', 'warning')
        ->assertJsonPath('results.0.reason_code', 'reuse_existing_slip')
        ->assertJsonPath('results.0.will_auto_create_do', false)
        ->assertJsonPath('results.0.target_receipt_id', $existingReceipt->id);

    $commitRes = $this->actingAs($user)->post(route('filament.admin.grs-rdtv.combined_upload.commit'), [
        'mode' => 'pair',
        'jenis' => 'GRS',
        'transaction_date' => '2026-11-24',
        'send_email' => '1',
        'no_grs' => '5007761425',
        'no_po' => '5000009305',
        'slip_file' => $renamedSlip,
        'verif_file' => $verif,
    ]);
    $commitRes->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('receipt_id', $existingReceipt->id)
        ->assertJsonPath('reused_existing_slip', true)
        ->assertJsonPath('status', 'Terverifikasi');

    expect(DeliveryOrderReceipt::count())->toBe(1)
        ->and(InvoicingReceipt::count())->toBe(1)
        ->and($existingReceipt->fresh()->status)->toBe('Terverifikasi')
        ->and($existingReceipt->fresh()->dokumen_verifikasi_grs)->toStartWith('verifikasi-grs/');
});
