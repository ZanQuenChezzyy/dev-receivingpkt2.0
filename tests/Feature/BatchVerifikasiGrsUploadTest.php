<?php

use App\Filament\Resources\InvoicingReceipts\Pages\ListInvoicingReceipts;
use App\Models\InvoicingReceipt;
use App\Models\User;
use App\Services\GrsPdfParserService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');

    if (! DbSchema::hasTable('users')) {
        DbSchema::create('users', function (Blueprint $table) {
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

    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    if (! DbSchema::hasTable('invoicing_receipts')) {
        DbSchema::create('invoicing_receipts', function (Blueprint $table) {
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
        DbSchema::create('invoicing_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoicing_receipt_id')->constrained('invoicing_receipts')->cascadeOnDelete();
            $table->string('no_grs')->nullable();
            $table->string('no_do')->nullable();
            $table->string('nama_barang')->nullable();
            $table->timestamps();
        });
    }
});

test('GrsPdfParserService can parse verifikasi GRS from digital text and filename fallback', function () {
    $parser = app(GrsPdfParserService::class);

    // 1. Digital text test
    $textPdf = base_path('tests/Fixtures/contoh_GRS.pdf');
    if (file_exists($textPdf)) {
        $result = $parser->parseVerifikasiGrs($textPdf, 'contoh_GRS.pdf');
        expect($result['no_grs'])->toBe('5007731110')
            ->and($result['detection_source'])->toBe('text');
    }

    // 2. Filename fallback test
    $fallbackResult = $parser->parseVerifikasiGrs('', '5007697783_Verifikasi_5300066419.pdf');
    expect($fallbackResult['no_grs'])->toBe('5007697783')
        ->and($fallbackResult['no_po'])->toBe('5300066419')
        ->and($fallbackResult['detection_source'])->toBe('filename');

    $comboResult = $parser->parseVerifikasiGrs('', 'PO5300066419_GRS5007697783.pdf');
    expect($comboResult['no_grs'])->toBe('5007697783')
        ->and($comboResult['no_po'])->toBe('5300066419')
        ->and($comboResult['detection_source'])->toBe('filename');

    // 3. Binary stream safety test
    $fakeImagePdf = "%PDF-1.7\n4 0 obj\n<</Subtype /Image /Filter /DCTDecode /Length 5000>>\nstream\n".str_repeat("\x00\xFF\x28\x29\x50\x30\x30", 500)."\nendstream\nendobj";
    $safeText = $parser->extractRawStreamsText($fakeImagePdf);
    expect($safeText)->toBe('');
});

test('batch upload action exists on ListInvoicingReceipts with OCR modal', function () {
    $user = User::create([
        'name' => 'Admin User',
        'email' => 'admin_test_'.uniqid().'@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('super_admin');

    Livewire::actingAs($user)
        ->test(ListInvoicingReceipts::class)
        ->assertActionExists('batchUploadVerifikasiGrs');
});

test('BatchVerifikasiGrsUploadController matches GRS, saves file, and updates status to Terverifikasi', function () {
    $user = User::create([
        'name' => 'Admin User Controller',
        'email' => 'admin_ctrl_'.uniqid().'@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('super_admin');

    $receipt = InvoicingReceipt::create([
        'no_grs' => '5007697783',
        'no_po' => '5300066419',
        'status' => 'Menunggu Verifikasi',
        'dokumen_verifikasi_grs' => null,
    ]);

    $file = UploadedFile::fake()->create('Scan_001.pdf', 150, 'application/pdf');

    $response = $this->actingAs($user)
        ->post(route('filament.admin.resources.invoicing-receipts.upload_verifikasi_grs_ocr'), [
            'file' => $file,
            'no_grs' => '5007697783',
            'no_po' => '5300066419',
            'original_name' => 'Scan_001.pdf',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'matched' => true,
            'no_grs' => '5007697783',
            'status' => 'Terverifikasi',
        ]);

    $receipt->refresh();
    expect($receipt->status)->toBe('Terverifikasi')
        ->and($receipt->dokumen_verifikasi_grs)->not->toBeNull();
    Storage::disk('public')->assertExists($receipt->dokumen_verifikasi_grs);
});

test('BatchVerifikasiGrsUploadController falls back to PO matching when no_grs is null', function () {
    $user = User::create([
        'name' => 'Admin User Fallback',
        'email' => 'admin_fallback_'.uniqid().'@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('super_admin');

    $receipt = InvoicingReceipt::create([
        'no_grs' => null,
        'no_po' => '5300066419',
        'status' => 'Menunggu Verifikasi',
        'dokumen_verifikasi_grs' => null,
    ]);

    $file = UploadedFile::fake()->create('Scan_Arbitrary_Name.pdf', 150, 'application/pdf');

    $response = $this->actingAs($user)
        ->post(route('filament.admin.resources.invoicing-receipts.upload_verifikasi_grs_ocr'), [
            'file' => $file,
            'no_grs' => '5007697783',
            'no_po' => '5300066419',
            'original_name' => 'Scan_Arbitrary_Name.pdf',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'matched' => true,
            'status' => 'Terverifikasi',
        ]);

    $receipt->refresh();
    expect($receipt->status)->toBe('Terverifikasi')
        ->and($receipt->no_grs)->toBe('5007697783')
        ->and($receipt->dokumen_verifikasi_grs)->not->toBeNull();
});

test('BatchVerifikasiGrsUploadController returns unmatched when receipt not found', function () {
    $user = User::create([
        'name' => 'Admin User Unmatched',
        'email' => 'admin_unmatched_'.uniqid().'@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('super_admin');

    $file = UploadedFile::fake()->create('unknown_scan.pdf', 100, 'application/pdf');

    $response = $this->actingAs($user)
        ->post(route('filament.admin.resources.invoicing-receipts.upload_verifikasi_grs_ocr'), [
            'file' => $file,
            'no_grs' => '5009999999',
            'no_po' => '5309999999',
            'original_name' => 'unknown_scan.pdf',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => false,
            'matched' => false,
        ]);
});

test('BatchVerifikasiGrsUploadController matches receipt with comma separated GRS or in items', function () {
    $user = User::create([
        'name' => 'Admin User Multi GRS',
        'email' => 'admin_multi_grs_'.uniqid().'@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('super_admin');

    $receipt = InvoicingReceipt::create([
        'no_grs' => '5007697780, 5007697781',
        'no_po' => '5300099999',
        'status' => 'Menunggu Verifikasi',
    ]);

    $file = UploadedFile::fake()->create('Scan_5007697781.pdf', 150, 'application/pdf');

    $response = $this->actingAs($user)
        ->post(route('filament.admin.resources.invoicing-receipts.upload_verifikasi_grs_ocr'), [
            'file' => $file,
            'no_grs' => '5007697781',
            'no_po' => '5300099999',
            'original_name' => 'Scan_5007697781.pdf',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'matched' => true,
            'status' => 'Terverifikasi',
        ]);

    $receipt->refresh();
    expect($receipt->status)->toBe('Terverifikasi')
        ->and($receipt->dokumen_verifikasi_grs)->not->toBeNull();
});

test('BatchVerifikasiGrsUploadController falls back to PO matching even when no_do on receipt is empty or formatted differently', function () {
    $user = User::create([
        'name' => 'Admin PO Fallback DO',
        'email' => 'admin_po_do_'.uniqid().'@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('super_admin');

    $receipt = InvoicingReceipt::create([
        'no_grs' => null,
        'no_po' => '5300066928',
        'no_do' => null,
        'vendor_name' => 'PT GRASIKA KARYA PRIMA',
        'status' => 'Menunggu Verifikasi',
    ]);

    $file = UploadedFile::fake()->create('Print.pdf', 150, 'application/pdf');

    $response = $this->actingAs($user)
        ->post(route('filament.admin.resources.invoicing-receipts.upload_verifikasi_grs_ocr'), [
            'file' => $file,
            'no_grs' => '5007725170',
            'no_po' => '5300066928',
            'no_do' => '26020653',
            'original_name' => 'Print.pdf',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'matched' => true,
            'receipt_id' => $receipt->id,
            'no_grs' => '5007725170',
            'no_po' => '5300066928',
            'no_do' => '26020653',
            'status' => 'Terverifikasi',
        ]);

    $receipt->refresh();
    expect($receipt->status)->toBe('Terverifikasi')
        ->and($receipt->no_grs)->toBe('5007725170')
        ->and($receipt->no_do)->toBe('26020653')
        ->and($receipt->dokumen_verifikasi_grs)->not->toBeNull();
});

test('batch upload verifikasi GRS modal uses valid tesseract.js-core v5 CDN and disambiguates GR 103 from GRS 105 and RDTV 124', function () {
    $bladeContent = file_get_contents(resource_path('views/filament/actions/batch-upload-verifikasi-grs-modal.blade.php'));

    expect($bladeContent)
        ->toContain('tesseract.js-core')
        ->not->toContain("jsdelivr.net/npm/tesseract-core'")
        ->toContain('__batchOcrScriptPromises')
        ->toContain('cropCanvasRegion')
        ->toContain('mGr103')
        ->toContain('VERIFIKASI\\s+PENOLAKAN\\s+BARANG')
        ->toContain('toggleDocType');
});

test('GrsPdfParserService distinguishes Verifikasi Penerimaan Barang (GRS 105) from Verifikasi Penolakan Barang (RDTV 124)', function () {
    $parser = app(GrsPdfParserService::class);

    $rdtvText = <<<'TEXT'
    VERIFIKASI PENOLAKAN BARANG
    Nomor: RDTV- 58872/D/PL/D52100/IT/2026
    Tanggal: 11 November 2026
    Bersama ini terlampir dokumen Return Delivery To Vendor (Berita Acara Penolakan Barang)
    Nomor PO : 5300065841
    Nomor GR (103) : 5007689041
    Nomor RDTV (124) : 5007730671
    Nama Penyedia Barang : INDOMOULD ANUGRAH CIPTA
    Alasan Pengembalian : DITOLAK ITEM 10 & 30 MATERIAL TDK SESUAI PO
    Nomor DO/SJ : DN109026PKT
    TEXT;

    $grsText = <<<'TEXT'
    VERIFIKASI PENERIMAAN BARANG
    Nomor: GRS- 58718/D/PL/D52100/IT/2026
    Tanggal: 10 November 2026
    Bersama ini terlampir dokumen Goods Receipt Slip (Berita Acara Penerimaan Barang)
    Nomor PO : 5300066928
    Nomor GR (103) : 5007689045
    Nomor GRS (105) : 5007725170
    Nama Penyedia Barang : PT GRASIKA KARYA PRIMA
    Nomor DO/SJ : 26020653
    TEXT;

    $parsedRdtv = $parser->parseVerifikasiGrs($rdtvText, 'Print.pdf');
    $parsedGrs = $parser->parseVerifikasiGrs($grsText, 'Print.pdf');

    expect($parsedRdtv['jenis'])->toBe('RDTV')
        ->and($parsedRdtv['no_rdtv'])->toBe('5007730671')
        ->and($parsedRdtv['no_gr_103'])->toBe('5007689041')
        ->and($parsedRdtv['no_grs'])->toBeNull()
        ->and($parsedRdtv['no_po'])->toBe('5300065841')
        ->and($parsedRdtv['no_do'])->toBe('DN109026PKT');

    expect($parsedGrs['jenis'])->toBe('GRS')
        ->and($parsedGrs['no_grs'])->toBe('5007725170')
        ->and($parsedGrs['no_gr_103'])->toBe('5007689045')
        ->and($parsedGrs['no_rdtv'])->toBeNull()
        ->and($parsedGrs['no_po'])->toBe('5300066928')
        ->and($parsedGrs['no_do'])->toBe('26020653');
});

test('BatchVerifikasiGrsUploadController strictly separates RDTV and GRS receipts on the same PO', function () {
    $user = User::create([
        'name' => 'Admin Strict Type',
        'email' => 'admin_strict_type_'.uniqid().'@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('super_admin');

    // PO yang sama memiliki 1 baris GRS dan 1 baris RDTV
    $grsReceipt = InvoicingReceipt::create([
        'jenis' => 'GRS',
        'no_po' => '5300065841',
        'no_grs' => null,
        'vendor_name' => 'INDOMOULD ANUGRAH CIPTA',
        'status' => 'Menunggu Verifikasi',
    ]);
    $rdtvReceipt = InvoicingReceipt::create([
        'jenis' => 'RDTV',
        'no_po' => '5300065841',
        'no_rdtv' => null,
        'vendor_name' => 'INDOMOULD ANUGRAH CIPTA',
        'status' => 'Menunggu Verifikasi',
    ]);

    // 1. Upload Verifikasi Penolakan Barang (RDTV 124) -> hanya menyentuh baris RDTV
    $rdtvFile = UploadedFile::fake()->create('Print_RDTV.pdf', 150, 'application/pdf');
    $rdtvResponse = $this->actingAs($user)
        ->post(route('filament.admin.resources.invoicing-receipts.upload_verifikasi_grs_ocr'), [
            'file' => $rdtvFile,
            'jenis' => 'RDTV',
            'no_rdtv' => '5007730671',
            'no_po' => '5300065841',
            'no_do' => 'DN109026PKT',
            'original_name' => 'Print_RDTV.pdf',
        ]);

    $rdtvResponse->assertOk()
        ->assertJson([
            'success' => true,
            'matched' => true,
            'receipt_id' => $rdtvReceipt->id,
            'jenis' => 'RDTV',
            'no_rdtv' => '5007730671',
            'status' => 'Terverifikasi',
        ]);

    $rdtvReceipt->refresh();
    $grsReceipt->refresh();

    expect($rdtvReceipt->status)->toBe('Terverifikasi')
        ->and($rdtvReceipt->no_rdtv)->toBe('5007730671')
        ->and($rdtvReceipt->dokumen_verifikasi_grs)->toStartWith('verifikasi-rdtv/')
        ->and($grsReceipt->status)->toBe('Menunggu Verifikasi')
        ->and($grsReceipt->dokumen_verifikasi_grs)->toBeNull();

    // 2. Upload Verifikasi Penerimaan Barang (GRS 105) -> hanya menyentuh baris GRS
    $grsFile = UploadedFile::fake()->create('Print_GRS.pdf', 150, 'application/pdf');
    $grsResponse = $this->actingAs($user)
        ->post(route('filament.admin.resources.invoicing-receipts.upload_verifikasi_grs_ocr'), [
            'file' => $grsFile,
            'jenis' => 'GRS',
            'no_grs' => '5007725170',
            'no_po' => '5300065841',
            'no_do' => '26020653',
            'original_name' => 'Print_GRS.pdf',
        ]);

    $grsResponse->assertOk()
        ->assertJson([
            'success' => true,
            'matched' => true,
            'receipt_id' => $grsReceipt->id,
            'jenis' => 'GRS',
            'no_grs' => '5007725170',
            'status' => 'Terverifikasi',
        ]);

    $grsReceipt->refresh();
    expect($grsReceipt->status)->toBe('Terverifikasi')
        ->and($grsReceipt->no_grs)->toBe('5007725170')
        ->and($grsReceipt->dokumen_verifikasi_grs)->toStartWith('verifikasi-grs/');
});

test('BatchVerifikasiGrsUploadController never overwrites an older partial GRS with a different no_grs on the same PO (PO 5300067497 regression)', function () {
    $user = User::create([
        'name' => 'Admin Partial Guard',
        'email' => 'admin_partial_guard_'.uniqid().'@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('super_admin');

    // Parsial 1 (5 item lama) sudah punya No. GRS 5007739482 & placeholder slip grs-rdtv-docs/
    $partialOld = InvoicingReceipt::create([
        'jenis' => 'GRS',
        'no_po' => '5300067497',
        'no_grs' => '5007739482',
        'no_do' => '67497',
        'dokumen_verifikasi_grs' => 'grs-rdtv-docs/5300067497-06-67497-09112026.pdf',
        'status' => 'Menunggu Verifikasi',
    ]);

    // Parsial 2 (1 item baru) belum sempat terisi no_grs
    $partialNew = InvoicingReceipt::create([
        'jenis' => 'GRS',
        'no_po' => '5300067497',
        'no_grs' => null,
        'no_do' => '05429715',
        'dokumen_verifikasi_grs' => 'grs-rdtv-docs/5300067497-01-05429715-11112026.pdf',
        'status' => 'Menunggu Verifikasi',
    ]);

    $verifFile = UploadedFile::fake()->create('Verifikasi_5007756199.pdf', 150, 'application/pdf');

    // Upload Verifikasi untuk No. GRS 5007756199 tanpa no_do -> WAJIB cocok ke $partialNew, BUKAN $partialOld!
    $response = $this->actingAs($user)
        ->post(route('filament.admin.resources.invoicing-receipts.upload_verifikasi_grs_ocr'), [
            'file' => $verifFile,
            'jenis' => 'GRS',
            'no_grs' => '5007756199',
            'no_po' => '5300067497',
            'original_name' => 'Verifikasi_5007756199.pdf',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'matched' => true,
            'receipt_id' => $partialNew->id,
            'no_grs' => '5007756199',
            'status' => 'Terverifikasi',
        ]);

    expect($partialOld->fresh()->status)->toBe('Menunggu Verifikasi')
        ->and($partialOld->fresh()->no_grs)->toBe('5007739482')
        ->and($partialOld->fresh()->dokumen_verifikasi_grs)->toBe('grs-rdtv-docs/5300067497-06-67497-09112026.pdf')
        ->and($partialNew->fresh()->status)->toBe('Terverifikasi')
        ->and($partialNew->fresh()->no_grs)->toBe('5007756199')
        ->and($partialNew->fresh()->dokumen_verifikasi_grs)->toStartWith('verifikasi-grs/');
});

test('BatchVerifikasiGrsUploadController rejects unmatched no_grs instead of overwriting a different existing no_grs on the same PO', function () {
    $user = User::create([
        'name' => 'Admin Strict NoGrs',
        'email' => 'admin_strict_nogrs_'.uniqid().'@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('super_admin');

    $existingPartial = InvoicingReceipt::create([
        'jenis' => 'GRS',
        'no_po' => '5300067497',
        'no_grs' => '5007739482',
        'no_do' => '67497',
        'dokumen_verifikasi_grs' => 'grs-rdtv-docs/5300067497-06-67497-09112026.pdf',
        'status' => 'Menunggu Verifikasi',
    ]);

    $verifFile = UploadedFile::fake()->create('Verifikasi_5007799999.pdf', 150, 'application/pdf');

    $response = $this->actingAs($user)
        ->post(route('filament.admin.resources.invoicing-receipts.upload_verifikasi_grs_ocr'), [
            'file' => $verifFile,
            'jenis' => 'GRS',
            'no_grs' => '5007799999',
            'no_po' => '5300067497',
            'original_name' => 'Verifikasi_5007799999.pdf',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => false,
            'matched' => false,
        ]);

    expect($existingPartial->fresh()->status)->toBe('Menunggu Verifikasi')
        ->and($existingPartial->fresh()->no_grs)->toBe('5007739482');
});

test('BatchVerifikasiGrsUploadController blocks ambiguous PO-only fallback when multiple partial receipts have null no_grs', function () {
    $user = User::create([
        'name' => 'Admin Ambiguous Partial',
        'email' => 'admin_ambig_'.uniqid().'@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('super_admin');

    $p1 = InvoicingReceipt::create([
        'jenis' => 'GRS',
        'no_po' => '5300067498',
        'no_grs' => null,
        'no_do' => 'DO-A',
        'status' => 'Menunggu Verifikasi',
    ]);
    $p2 = InvoicingReceipt::create([
        'jenis' => 'GRS',
        'no_po' => '5300067498',
        'no_grs' => null,
        'no_do' => 'DO-B',
        'status' => 'Menunggu Verifikasi',
    ]);

    $verifFile = UploadedFile::fake()->create('Verifikasi_Ambig.pdf', 150, 'application/pdf');

    $response = $this->actingAs($user)
        ->post(route('filament.admin.resources.invoicing-receipts.upload_verifikasi_grs_ocr'), [
            'file' => $verifFile,
            'jenis' => 'GRS',
            'no_grs' => '5007788888',
            'no_po' => '5300067498',
            'original_name' => 'Verifikasi_Ambig.pdf',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => false,
            'matched' => false,
        ]);

    expect($p1->fresh()->status)->toBe('Menunggu Verifikasi')
        ->and($p2->fresh()->status)->toBe('Menunggu Verifikasi');
});
