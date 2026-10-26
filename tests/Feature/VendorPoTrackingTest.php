<?php

use App\Livewire\Frontend\VendorPoTracking;
use App\Models\GrsRdtvItem;
use App\Models\InvoicingReceipt;
use App\Models\PurchaseOrderIssued;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    if (! Schema::hasTable('purchase_order_issueds')) {
        Schema::create('purchase_order_issueds', function ($table) {
            $table->id();
            $table->string('purchase_order_no')->nullable();
            $table->string('vendor_id')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('item_no')->nullable();
            $table->decimal('qty_po', 12, 3)->default(0);
            $table->string('uoi')->nullable();
            $table->string('description')->nullable();
            $table->decimal('total_amount_in_lc', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('invoicing_receipts')) {
        Schema::create('invoicing_receipts', function ($table) {
            $table->id();
            $table->unsignedBigInteger('grs_rdtv_item_id')->nullable();
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->unsignedBigInteger('purchase_order_issued_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->string('no_po')->nullable();
            $table->string('no_do')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('email_vendor')->nullable();
            $table->string('jenis')->nullable();
            $table->string('status')->nullable();
            $table->text('keterangan')->nullable();
            $table->date('tanggal_terima_grs')->nullable();
            $table->string('dokumen_verifikasi_grs')->nullable();
            $table->string('dokumen_invoice')->nullable();
            $table->json('dokumen_kelengkapan')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('grs_rdtv_items')) {
        Schema::create('grs_rdtv_items', function ($table) {
            $table->id();
            $table->string('document_code')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('vendors')) {
        Schema::create('vendors', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('vendor_number')->nullable();
            $table->text('email')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipt_details')) {
        Schema::create('delivery_order_receipt_details', function ($table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_issued_id')->nullable();
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }
});

test('lacak po vendor page is accessible via route', function () {
    $response = $this->get(route('frontend.vendor.tracking'));

    $response->assertSuccessful();
    $response->assertSee('Portal Tracking Vendor PKT');
    $response->assertSee('Lacak Dokumen');
});

test('frontend navbar contains link to lacak po vendor on home page', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertSee(route('frontend.vendor.tracking'));
    $response->assertSee('Lacak PO Vendor');
});

test('vendor po tracking component has expected initial state', function () {
    Livewire::test(VendorPoTracking::class)
        ->assertSet('poNumber', '')
        ->assertSet('activePo', null)
        ->assertSet('isVerified', false)
        ->assertSet('showVerificationModal', false)
        ->assertSet('verificationError', null)
        ->assertSet('searchError', null)
        ->assertSet('trackingData', []);
});

test('searching with empty po number sets validation error', function () {
    Livewire::test(VendorPoTracking::class)
        ->set('poNumber', '   ')
        ->call('initiateSearch')
        ->assertSet('searchError', 'Harap masukkan nomor Purchase Order (PO).')
        ->assertSet('showVerificationModal', false)
        ->assertSet('isVerified', false);
});

test('email masking produces secure hint with domain preserved', function () {
    $component = new VendorPoTracking;
    $reflection = new ReflectionClass($component);
    $maskMethod = $reflection->getMethod('maskEmail');
    $maskMethod->setAccessible(true);

    // Standard email
    $masked1 = $maskMethod->invoke($component, 'official@vendor.com');
    expect($masked1)->toBe('of*****l@vendor.com');

    // Short name email
    $masked2 = $maskMethod->invoke($component, 'ab@vendor.com');
    expect($masked2)->toBe('a*@vendor.com');

    // Medium name email
    $masked3 = $maskMethod->invoke($component, 'sales@pkt.co.id');
    expect($masked3)->toBe('s***s@pkt.co.id');
});

test('email extraction handles arrays, multiple strings, and json formats', function () {
    $component = new VendorPoTracking;
    $reflection = new ReflectionClass($component);
    $extractMethod = $reflection->getMethod('extractEmailsFromVendor');
    $extractMethod->setAccessible(true);

    $vendor = new Vendor;
    $vendor->email = ['admin@fajarbenua.co.id', 'finance@fajarbenua.co.id'];

    $emails = $extractMethod->invoke($component, $vendor);
    expect($emails)->toBe(['admin@fajarbenua.co.id', 'finance@fajarbenua.co.id']);

    // Multiple emails separated by spaces/commas inside single item
    $vendor->email = 'contact@arezda.co.id info@arezda.co.id';
    $emails2 = $extractMethod->invoke($component, $vendor);
    expect($emails2)->toContain('contact@arezda.co.id', 'info@arezda.co.id');

    // Null vendor
    $emailsNull = $extractMethod->invoke($component, null);
    expect($emailsNull)->toBe([]);
});

test('verifying with empty or invalid email format sets error', function () {
    Livewire::test(VendorPoTracking::class)
        ->set('activePo', '5300065628')
        ->set('verificationEmail', 'invalid-email-format')
        ->call('verifyVendorEmail')
        ->assertSet('verificationError', 'Harap masukkan format alamat email yang valid.')
        ->assertSet('isVerified', false);
});

test('closing modal resets modal state and errors', function () {
    Livewire::test(VendorPoTracking::class)
        ->set('showVerificationModal', true)
        ->set('verificationEmail', 'temp@vendor.com')
        ->set('verificationError', 'Some error')
        ->call('closeModal')
        ->assertSet('showVerificationModal', false)
        ->assertSet('verificationEmail', '')
        ->assertSet('verificationError', null);
});

test('reset search clears all tracking state', function () {
    Livewire::test(VendorPoTracking::class)
        ->set('poNumber', '5300065628')
        ->set('activePo', '5300065628')
        ->set('isVerified', true)
        ->set('trackingData', ['po' => ['number' => '5300065628']])
        ->call('resetSearch')
        ->assertSet('poNumber', '')
        ->assertSet('activePo', null)
        ->assertSet('isVerified', false)
        ->assertSet('trackingData', [])
        ->assertSet('showVerificationModal', false);
});

test('invoice checklist constant defines all 16 required items in correct order', function () {
    $checklist = VendorPoTracking::INVOICE_CHECKLIST;

    expect(count($checklist))->toBe(16);

    $expectedKeys = [
        'po_asli',
        'invoice',
        'grs',
        'do',
        'faktur_pajak',
        'enofa',
        'sppkp',
        'bank_garansi',
        'skf',
        'surat_pernyataan',
        'lpkb',
        'coa',
        'bill_of_lading',
        'penyesuaian_harga',
        'jadwal_pengiriman',
        'ba_transfering',
    ];

    expect(array_keys($checklist))->toBe($expectedKeys);

    expect($checklist['po_asli']['name'])->toBe('PO ASLI');
    expect($checklist['invoice']['name'])->toBe('INVOICE (NOTA PENJUALAN)');
    expect($checklist['grs']['name'])->toBe('GRS (GOOD RECEIVE SLIP)');
    expect($checklist['do']['name'])->toBe('DO (DELIVERY ORDER)');
    expect($checklist['faktur_pajak']['name'])->toBe('FAKTUR PAJAK');
    expect($checklist['enofa']['name'])->toBe('ENOFA (NO. SERI FAKTUR PAJAK ONLINE)');
    expect($checklist['sppkp']['name'])->toBe('SPPKP (SURAT PENGUKUHAN PENGUSAHA KENA PAJAK)');
    expect($checklist['bank_garansi']['name'])->toBe('BANK GARANSI (JAMINAN PELAKSANA)');
    expect($checklist['skf']['name'])->toBe('SKF (SURAT KETERANGAN FISKAL)');
    expect($checklist['surat_pernyataan']['name'])->toBe('SURAT PERNYATAAN');
    expect($checklist['lpkb']['name'])->toBe('LPKB (LAPORAN PEMERIKSAAN KUALITAS BARANG)');
    expect($checklist['coa']['name'])->toBe('COA (CERTIFICATE OF ANALYSIS)');
    expect($checklist['bill_of_lading']['name'])->toBe('BILL OF LADING (B/L)');
    expect($checklist['penyesuaian_harga']['name'])->toBe('KETERANGAN PENYESUAIAN HARGA');
    expect($checklist['jadwal_pengiriman']['name'])->toBe('JADWAL PENGIRIMAN');
    expect($checklist['ba_transfering']['name'])->toBe('BA TRANSFERING');
});

test('vendor can upload, download, and delete unified dossier penagihan document', function () {
    Storage::fake('public');

    $po = PurchaseOrderIssued::create([
        'purchase_order_no' => '5300099991',
        'vendor_id' => 'VEND-99991',
        'vendor_name' => 'PT Testing Vendor Indonesia',
        'item_no' => '00010',
        'qty_po' => 100,
        'uoi' => 'EA',
        'description' => 'Test Material Dossier',
        'total_amount_in_lc' => 50000000,
    ]);

    $file = UploadedFile::fake()->create('berkas_penagihan_lengkap.pdf', 1500, 'application/pdf');

    $test = Livewire::test(VendorPoTracking::class)
        ->set('activePo', '5300099991')
        ->set('isVerified', true)
        ->set('berkasPenagihanFile', $file)
        ->call('uploadBerkasPenagihan');

    $test->assertHasNoErrors();

    $receipt = InvoicingReceipt::where('no_po', '5300099991')->first();
    expect($receipt)->not->toBeNull();
    expect($receipt->dokumen_invoice)->not->toBeNull();
    expect($receipt->dokumen_kelengkapan)->toBeArray();
    expect($receipt->dokumen_kelengkapan['original_name'])->toBe('berkas_penagihan_lengkap.pdf');
    expect($receipt->dokumen_kelengkapan['file_path'])->toBe($receipt->dokumen_invoice);

    Storage::disk('public')->assertExists($receipt->dokumen_invoice);

    // Test download dossier
    $downloadResponse = $test->call('downloadBerkasPenagihan');
    expect($downloadResponse)->not->toBeNull();

    // Test delete dossier
    $test->call('deleteBerkasPenagihan');
    $receipt->refresh();
    expect($receipt->dokumen_invoice)->toBeNull();
    expect($receipt->dokumen_kelengkapan)->toBeNull();
});

test('vendor can download surat verifikasi grs and berkas grs receiving', function () {
    Storage::fake('public');

    $storedVerifikasi = 'verifikasi-grs/surat_verifikasi_5300099992.pdf';
    Storage::disk('public')->put($storedVerifikasi, 'PDF VERIFIKASI GRS CONTENT');

    $storedGrs = 'grs-rdtv-docs/grs_receiving_5300099992.pdf';
    Storage::disk('public')->put($storedGrs, 'PDF GRS RECEIVING CONTENT');

    $receipt = InvoicingReceipt::create([
        'no_po' => '5300099992',
        'no_do' => 'DO-99992',
        'status' => 'Terverifikasi',
        'dokumen_verifikasi_grs' => $storedVerifikasi,
    ]);

    $grsItem = GrsRdtvItem::create([
        'document_code' => 'GRS-99992',
        'file_path' => $storedGrs,
        'status' => 'Diterima',
    ]);

    $test = Livewire::test(VendorPoTracking::class)
        ->set('activePo', '5300099992')
        ->set('isVerified', true);

    // Download verifikasi GRS
    $responseVerifikasi = $test->call('downloadVerifikasiGrs', $receipt->id);
    expect($responseVerifikasi)->not->toBeNull();

    // Download berkas GRS receiving
    $responseGrs = $test->call('downloadGrsReceiving', $grsItem->id);
    expect($responseGrs)->not->toBeNull();
});

test('vendor tracking view displays single dossier file status and 16 requirements checklist', function () {
    $po = PurchaseOrderIssued::create([
        'purchase_order_no' => '5300099993',
        'vendor_id' => 'VEND-99993',
        'vendor_name' => 'PT Dossier Checklist Test',
        'item_no' => '00010',
        'qty_po' => 50,
        'uoi' => 'EA',
        'description' => 'Test Material Dossier View',
        'total_amount_in_lc' => 25000000,
    ]);

    InvoicingReceipt::create([
        'no_po' => '5300099993',
        'vendor_name' => 'PT Dossier Checklist Test',
        'status' => 'Menunggu Verifikasi',
        'dokumen_invoice' => 'berkas-penagihan/5300099993/berkas_penagihan_lengkap.pdf',
        'dokumen_kelengkapan' => [
            'file_path' => 'berkas-penagihan/5300099993/berkas_penagihan_lengkap.pdf',
            'original_name' => 'berkas_penagihan_lengkap.pdf',
            'file_size' => 2048000,
            'uploaded_at' => '2026-10-26 21:00:00',
        ],
    ]);

    Livewire::test(VendorPoTracking::class)
        ->set('activePo', '5300099993')
        ->set('isVerified', true)
        ->call('loadTrackingData')
        ->assertSee('Upload Dokumen Persyaratan Penagihan')
        ->assertSee('Berkas Lengkap Terunggah')
        ->assertSee('berkas_penagihan_lengkap.pdf')
        ->assertSee('16 Persyaratan Kelengkapan Dokumen Penagihan')
        ->assertSee('PO ASLI')
        ->assertSee('INVOICE (NOTA PENJUALAN)')
        ->assertSee('GRS (GOOD RECEIVE SLIP)')
        ->assertSee('BA TRANSFERING');
});

test('searching a PO from a different vendor prompts verification again, while same vendor keeps verification', function () {
    // 1. Create Vendor A and PO A
    Vendor::create([
        'name' => 'PT Vendor Alfa Indonesia',
        'vendor_number' => 'VEND-AAA',
        'email' => 'alfa@vendor.com',
    ]);

    PurchaseOrderIssued::create([
        'purchase_order_no' => '5300088881',
        'vendor_id' => 'VEND-AAA',
        'vendor_name' => 'PT Vendor Alfa Indonesia',
        'item_no' => '00010',
        'qty_po' => 10,
        'uoi' => 'EA',
        'description' => 'Barang Alfa',
    ]);

    // Another PO for Vendor A
    PurchaseOrderIssued::create([
        'purchase_order_no' => '5300088882',
        'vendor_id' => 'VEND-AAA',
        'vendor_name' => 'PT Vendor Alfa Indonesia',
        'item_no' => '00010',
        'qty_po' => 20,
        'uoi' => 'EA',
        'description' => 'Barang Alfa Kedua',
    ]);

    // 2. Create Vendor B and PO B
    Vendor::create([
        'name' => 'PT Vendor Beta Perkasa',
        'vendor_number' => 'VEND-BBB',
        'email' => 'beta@vendor.com',
    ]);

    PurchaseOrderIssued::create([
        'purchase_order_no' => '5300088883',
        'vendor_id' => 'VEND-BBB',
        'vendor_name' => 'PT Vendor Beta Perkasa',
        'item_no' => '00010',
        'qty_po' => 15,
        'uoi' => 'EA',
        'description' => 'Barang Beta',
    ]);

    // Step 1: Search PO A (Vendor A) -> modal appears
    $test = Livewire::test(VendorPoTracking::class)
        ->set('poNumber', '5300088881')
        ->call('initiateSearch')
        ->assertSet('showVerificationModal', true)
        ->assertSet('isVerified', false);

    // Step 2: Verify with Vendor A email -> successful
    $test->set('verificationEmail', 'alfa@vendor.com')
        ->call('verifyVendorEmail')
        ->assertSet('isVerified', true)
        ->assertSet('showVerificationModal', false)
        ->assertSet('verifiedEmail', 'alfa@vendor.com')
        ->assertSet('verifiedVendorNumber', 'VEND-AAA');

    // Step 3: Search PO B (Vendor B - different vendor) -> MUST show verification again!
    $test->set('poNumber', '5300088883')
        ->call('initiateSearch')
        ->assertSet('showVerificationModal', true)
        ->assertSet('isVerified', false)
        ->assertSet('trackingData', [])
        ->assertSee('PT Vendor Beta Perkasa');

    // Step 4: Trying to verify Vendor B with Vendor A email must fail
    $test->set('verificationEmail', 'alfa@vendor.com')
        ->call('verifyVendorEmail')
        ->assertSet('isVerified', false)
        ->assertSet('verificationError', 'Email yang Anda masukkan tidak sesuai dengan email vendor terdaftar untuk PO ini. Pastikan Anda menggunakan email resmi yang didaftarkan ke Pupuk Kaltim.');

    // Step 5: Verifying Vendor B with correct email -> successful
    $test->set('verificationEmail', 'beta@vendor.com')
        ->call('verifyVendorEmail')
        ->assertSet('isVerified', true)
        ->assertSet('showVerificationModal', false)
        ->assertSet('verifiedEmail', 'beta@vendor.com')
        ->assertSet('verifiedVendorNumber', 'VEND-BBB');

    // Step 6: Searching PO A again (now different from current verified Vendor B) -> MUST prompt verification again!
    $test->set('poNumber', '5300088881')
        ->call('initiateSearch')
        ->assertSet('showVerificationModal', true)
        ->assertSet('isVerified', false);

    // Step 7: Verifying Vendor A again, then searching another PO of Vendor A (5300088882) -> does NOT ask verification
    $test->set('verificationEmail', 'alfa@vendor.com')
        ->call('verifyVendorEmail')
        ->assertSet('isVerified', true);

    $test->set('poNumber', '5300088882')
        ->call('initiateSearch')
        ->assertSet('showVerificationModal', false)
        ->assertSet('isVerified', true)
        ->assertSet('activePo', '5300088882');
});
