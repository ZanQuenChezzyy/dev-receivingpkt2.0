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
            $table->string('dokumen_verifikasi_grs')->nullable();
            $table->string('dokumen_invoice')->nullable();
            $table->string('dokumen_kelengkapan')->nullable();
            $table->string('status')->default('Menunggu Verifikasi');
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

test('batch upload action exists on ListInvoicingReceipts and verifikasi GRS updates status', function () {
    $user = User::create([
        'name' => 'Admin User',
        'email' => 'admin_test_'.uniqid().'@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('super_admin');

    $receipt1 = InvoicingReceipt::create([
        'no_grs' => '5007697783',
        'no_po' => '5300066419',
        'status' => 'Menunggu Verifikasi',
        'dokumen_verifikasi_grs' => null,
    ]);

    $receipt2 = InvoicingReceipt::create([
        'no_grs' => '5007888999',
        'no_po' => '5300099999',
        'status' => 'Menunggu Verifikasi',
        'dokumen_verifikasi_grs' => null,
    ]);

    $file1 = UploadedFile::fake()->create('5007697783.pdf', 100, 'application/pdf');
    $file2 = UploadedFile::fake()->create('5007888999.pdf', 100, 'application/pdf');
    $unmatchedFile = UploadedFile::fake()->create('unknown_document.pdf', 50, 'application/pdf');

    Livewire::actingAs($user)
        ->test(ListInvoicingReceipts::class)
        ->assertActionExists('batchUploadVerifikasiGrs')
        ->callAction('batchUploadVerifikasiGrs', [
            'verifikasi_files' => [$file1, $file2, $unmatchedFile],
        ])
        ->assertHasNoActionErrors();

    $receipt1->refresh();
    $receipt2->refresh();

    expect($receipt1->status)->toBe('Terverifikasi')
        ->and($receipt1->dokumen_verifikasi_grs)->not->toBeNull();
    Storage::disk('public')->assertExists($receipt1->dokumen_verifikasi_grs);

    expect($receipt2->status)->toBe('Terverifikasi')
        ->and($receipt2->dokumen_verifikasi_grs)->not->toBeNull();
    Storage::disk('public')->assertExists($receipt2->dokumen_verifikasi_grs);
});

test('batch upload action falls back to PO matching when no_grs is null on receipt', function () {
    $user = User::create([
        'name' => 'Admin User 2',
        'email' => 'admin_test_2_'.uniqid().'@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('super_admin');

    $receipt = InvoicingReceipt::create([
        'no_grs' => null,
        'no_po' => '5300066419',
        'status' => 'Menunggu Verifikasi',
        'dokumen_verifikasi_grs' => null,
    ]);

    $file = UploadedFile::fake()->create('5007697783_5300066419.pdf', 100, 'application/pdf');

    Livewire::actingAs($user)
        ->test(ListInvoicingReceipts::class)
        ->callAction('batchUploadVerifikasiGrs', [
            'verifikasi_files' => [$file],
        ])
        ->assertHasNoActionErrors();

    $receipt->refresh();

    expect($receipt->status)->toBe('Terverifikasi')
        ->and($receipt->no_grs)->toBe('5007697783')
        ->and($receipt->dokumen_verifikasi_grs)->not->toBeNull();
});
