<?php

use App\Filament\Resources\GrsRdtvs\Pages\CreateGrsRdtv;
use App\Filament\Resources\GrsRdtvs\Pages\EditGrsRdtv;
use App\Models\DeliveryOrderReceipt;
use App\Models\GrsRdtv;
use App\Models\GrsRdtvItem;
use App\Models\InvoicingReceipt;
use App\Models\QcHistory;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('public');

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

    if (! DbSchema::hasTable('pic_material_assignments')) {
        DbSchema::create('pic_material_assignments', function ($table) {
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
        DbSchema::create('pic_material_assignment_user', function ($table) {
            $table->id();
            $table->unsignedBigInteger('pic_material_assignment_id');
            $table->unsignedBigInteger('user_id');
        });
    }

    Role::firstOrCreate(['name' => 'Staff Invoice', 'guard_name' => 'web']);

    if (! DbSchema::hasTable('vendors')) {
        DbSchema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('vendor_number')->nullable();
            $table->text('email')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('purchase_order_issueds')) {
        DbSchema::create('purchase_order_issueds', function (Blueprint $table) {
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
        DbSchema::create('delivery_order_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_order_no', 25)->nullable();
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
        DbSchema::create('delivery_order_receipt_details', function (Blueprint $table) {
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
        DbSchema::create('delivery_order_receipt_delay_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_receipt_id');
            $table->string('delay_reason')->nullable();
            $table->text('delay_notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('qc_histories')) {
        DbSchema::create('qc_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_receipt_id');
            $table->string('status');
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('grs_rdtvs')) {
        DbSchema::create('grs_rdtvs', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date')->nullable();
            $table->string('category')->default('GRS');
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('grs_rdtv_items')) {
        DbSchema::create('grs_rdtv_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grs_rdtv_id')->nullable();
            $table->foreignId('delivery_order_receipt_id')->nullable();
            $table->string('document_code');
            $table->string('file_path');
            $table->string('status')->default('Matched');
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

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
            $table->string('no_grs')->nullable();
            $table->string('no_rdtv')->nullable();
            $table->string('no_po')->default('-');
            $table->string('no_do')->nullable();
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
});

test('deleting a GrsRdtvItem resets DO status, deletes storage file, and removes linked InvoicingReceipt', function () {
    $user = User::create(['name' => 'Test User', 'email' => 'test@example.com', 'password' => 'secret']);
    $do = DeliveryOrderReceipt::create([
        'document_code' => 'DO-GRS-TEST-101',
        'delivery_order_no' => 'DO-101',
        'status' => 'Diterima',
        'post_103' => now(),
    ]);

    QcHistory::create([
        'delivery_order_receipt_id' => $do->id,
        'status' => 'Kembali',
    ]);

    $filePath = 'grs-rdtv-docs/DO-GRS-TEST-101.pdf';
    Storage::disk('public')->put($filePath, 'PDF CONTENT');
    expect(Storage::disk('public')->exists($filePath))->toBeTrue();

    $grs = GrsRdtv::create(['transaction_date' => now(), 'category' => 'GRS']);
    $item = GrsRdtvItem::create([
        'grs_rdtv_id' => $grs->id,
        'delivery_order_receipt_id' => $do->id,
        'document_code' => 'DO-GRS-TEST-101',
        'file_path' => $filePath,
        'status' => 'Matched',
    ]);

    $do->update(['status' => 'GRS']);

    $inv = InvoicingReceipt::create([
        'grs_rdtv_item_id' => $item->id,
        'delivery_order_receipt_id' => $do->id,
        'no_po' => '5300099991',
        'jenis' => 'GRS',
        'status' => 'Menunggu Verifikasi',
    ]);

    expect($do->fresh()->status)->toBe('GRS')
        ->and(InvoicingReceipt::find($inv->id))->not->toBeNull();

    // Hapus file / record GRS
    $item->delete();

    // Pastikan status DO kembali normal ('Kembali dari ISTEK' karena ada QC Kembali)
    expect($do->fresh()->status)->toBe('Kembali dari ISTEK')
        ->and(Storage::disk('public')->exists($filePath))->toBeFalse()
        ->and(InvoicingReceipt::find($inv->id))->toBeNull()
        ->and(GrsRdtvItem::find($item->id))->toBeNull();
});

test('deleting a GrsRdtv batch triggers item deletion and resets all DO statuses', function () {
    $do = DeliveryOrderReceipt::create([
        'document_code' => 'DO-GRS-BATCH-201',
        'delivery_order_no' => 'DO-201',
        'status' => 'GRS',
        'post_103' => now(),
    ]);

    $filePath = 'grs-rdtv-docs/DO-GRS-BATCH-201.pdf';
    Storage::disk('public')->put($filePath, 'PDF CONTENT');

    $grs = GrsRdtv::create(['transaction_date' => now(), 'category' => 'GRS']);
    $item = GrsRdtvItem::create([
        'grs_rdtv_id' => $grs->id,
        'delivery_order_receipt_id' => $do->id,
        'document_code' => 'DO-GRS-BATCH-201',
        'file_path' => $filePath,
        'status' => 'Matched',
    ]);

    $inv = InvoicingReceipt::create([
        'grs_rdtv_item_id' => $item->id,
        'delivery_order_receipt_id' => $do->id,
        'no_po' => '5300099992',
        'jenis' => 'GRS',
    ]);

    $grs->delete();

    expect(GrsRdtv::find($grs->id))->toBeNull()
        ->and(GrsRdtvItem::find($item->id))->toBeNull()
        ->and(InvoicingReceipt::find($inv->id))->toBeNull()
        ->and(Storage::disk('public')->exists($filePath))->toBeFalse()
        ->and($do->fresh()->status)->toBe('Diterima');
});

test('deleting an InvoicingReceipt deletes its associated GrsRdtvItem and resets DO status', function () {
    $do = DeliveryOrderReceipt::create([
        'document_code' => 'DO-GRS-INV-301',
        'delivery_order_no' => 'DO-301',
        'status' => 'GRS',
        'post_103' => now(),
    ]);

    $filePath = 'grs-rdtv-docs/DO-GRS-INV-301.pdf';
    Storage::disk('public')->put($filePath, 'PDF CONTENT');

    $grs = GrsRdtv::create(['transaction_date' => now(), 'category' => 'GRS']);
    $item = GrsRdtvItem::create([
        'grs_rdtv_id' => $grs->id,
        'delivery_order_receipt_id' => $do->id,
        'document_code' => 'DO-GRS-INV-301',
        'file_path' => $filePath,
        'status' => 'Matched',
    ]);

    $inv = InvoicingReceipt::create([
        'grs_rdtv_item_id' => $item->id,
        'delivery_order_receipt_id' => $do->id,
        'no_po' => '5300099993',
        'jenis' => 'GRS',
    ]);

    // Hapus dari Invoice Barang
    $inv->delete();

    expect(InvoicingReceipt::find($inv->id))->toBeNull()
        ->and(GrsRdtvItem::find($item->id))->toBeNull()
        ->and(Storage::disk('public')->exists($filePath))->toBeFalse()
        ->and($do->fresh()->status)->toBe('Diterima');
});

test('re-uploading GRS after previous file was deleted succeeds without alreadyProcessed error', function () {
    $user = User::create(['name' => 'Staff Receiving', 'email' => 'staff@example.com', 'password' => 'secret']);
    $this->actingAs($user);

    $do = DeliveryOrderReceipt::create([
        'document_code' => 'DO-REUPLOAD-401',
        'delivery_order_no' => 'DO-401',
        'status' => 'Diterima',
        'post_103' => now(),
        'received_by' => $user->id,
        'created_by' => $user->id,
    ]);

    QcHistory::create([
        'delivery_order_receipt_id' => $do->id,
        'status' => 'Kembali',
    ]);

    $filePath = 'grs-rdtv-docs/DO-REUPLOAD-401.pdf';
    Storage::disk('public')->put($filePath, 'OLD PDF CONTENT');

    $grs = GrsRdtv::create(['transaction_date' => now(), 'category' => 'GRS', 'created_by' => $user->id]);
    $item = GrsRdtvItem::create([
        'grs_rdtv_id' => $grs->id,
        'delivery_order_receipt_id' => $do->id,
        'document_code' => 'DO-REUPLOAD-401',
        'file_path' => $filePath,
        'status' => 'Matched',
    ]);
    $do->update(['status' => 'GRS']);

    // Hapus file / item GRS lama
    $item->delete();
    expect($do->fresh()->status)->toBe('Kembali dari ISTEK');

    // Sekarang coba upload ulang via CreateGrsRdtv
    $tempFile = TemporaryUploadedFile::fake()->create('DO-REUPLOAD-401.pdf', 100, 'application/pdf');

    Livewire::test(CreateGrsRdtv::class)
        ->fillForm([
            'transaction_date' => now()->toDateString(),
            'category' => 'GRS',
            'files' => [$tempFile],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect($do->fresh()->status)->toBe('GRS')
        ->and($do->grsRdtvItems()->count())->toBe(1)
        ->and(InvoicingReceipt::where('delivery_order_receipt_id', $do->id)->exists())->toBeTrue();
});

test('EditGrsRdtv allows re-uploading file for DO whose GRS was previously deleted', function () {
    $user = User::create(['name' => 'Staff Edit', 'email' => 'editstaff@example.com', 'password' => 'secret']);
    $this->actingAs($user);

    $do = DeliveryOrderReceipt::create([
        'document_code' => 'DO-EDIT-REUPLOAD-501',
        'delivery_order_no' => 'DO-501',
        'status' => 'Diterima',
        'post_103' => now(),
        'received_by' => $user->id,
        'created_by' => $user->id,
    ]);

    QcHistory::create([
        'delivery_order_receipt_id' => $do->id,
        'status' => 'Kembali',
    ]);

    $grs = GrsRdtv::create(['transaction_date' => now(), 'category' => 'GRS', 'created_by' => $user->id]);

    $tempFile = TemporaryUploadedFile::fake()->create('DO-EDIT-REUPLOAD-501.pdf', 100, 'application/pdf');

    Livewire::test(EditGrsRdtv::class, ['record' => $grs->id])
        ->fillForm([
            'files' => [$tempFile],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($do->fresh()->status)->toBe('GRS')
        ->and($do->grsRdtvItems()->count())->toBe(1)
        ->and(InvoicingReceipt::where('delivery_order_receipt_id', $do->id)->exists())->toBeTrue();
});

test('if physical GRS file is missing from disk, validation auto-cleans ghost item and allows re-upload', function () {
    $user = User::create(['name' => 'Staff Ghost', 'email' => 'ghoststaff@example.com', 'password' => 'secret']);
    $this->actingAs($user);

    $do = DeliveryOrderReceipt::create([
        'document_code' => 'DO-GHOST-601',
        'delivery_order_no' => 'DO-601',
        'status' => 'GRS',
        'post_103' => now(),
        'received_by' => $user->id,
        'created_by' => $user->id,
    ]);

    QcHistory::create([
        'delivery_order_receipt_id' => $do->id,
        'status' => 'Kembali',
    ]);

    $grs = GrsRdtv::create(['transaction_date' => now(), 'category' => 'GRS', 'created_by' => $user->id]);

    // Ghost item: file_path does not exist on disk!
    $ghostItem = GrsRdtvItem::create([
        'grs_rdtv_id' => $grs->id,
        'delivery_order_receipt_id' => $do->id,
        'document_code' => 'DO-GHOST-601',
        'file_path' => 'grs-rdtv-docs/DO-GHOST-601-NONEXISTENT.pdf',
        'status' => 'Matched',
    ]);

    // Pastikan file fisik memang tidak ada
    expect(Storage::disk('public')->exists($ghostItem->file_path))->toBeFalse();

    $newFile = TemporaryUploadedFile::fake()->create('DO-GHOST-601.pdf', 100, 'application/pdf');

    Livewire::test(CreateGrsRdtv::class)
        ->fillForm([
            'transaction_date' => now()->toDateString(),
            'category' => 'GRS',
            'files' => [$newFile],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect($do->fresh()->status)->toBe('GRS')
        ->and(GrsRdtvItem::find($ghostItem->id))->toBeNull()
        ->and($do->grsRdtvItems()->count())->toBe(1);
});

test('uploading rdtv document populates no_rdtv and parsed fields on invoicing_receipts', function () {
    $user = User::create(['name' => 'Staff RDTV', 'email' => 'rdtv@example.com', 'password' => 'secret']);
    $this->actingAs($user);

    $do = DeliveryOrderReceipt::create([
        'document_code' => 'FTGI-1026-0115',
        'delivery_order_no' => 'FTGI-1026-0115',
        'status' => 'Kembali dari ISTEK',
        'post_103' => now(),
        'received_by' => $user->id,
        'created_by' => $user->id,
    ]);

    QcHistory::create([
        'delivery_order_receipt_id' => $do->id,
        'status' => 'Kembali',
    ]);

    $fixtureContent = file_get_contents(base_path('tests/Fixtures/contoh_RDTV.pdf'));
    $file = TemporaryUploadedFile::fake()->createWithContent('FTGI-1026-0115.pdf', $fixtureContent);

    Livewire::test(CreateGrsRdtv::class)
        ->fillForm([
            'transaction_date' => now()->toDateString(),
            'category' => 'RDTV',
            'items' => [
                [
                    'file' => [$file],
                    'reason' => 'TIDAK MELAMPIRKAN COO FROM MANUFACTURE',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $receipt = InvoicingReceipt::where('delivery_order_receipt_id', $do->id)->first();
    expect($receipt)->not->toBeNull()
        ->and($receipt->jenis)->toBe('RDTV')
        ->and($receipt->no_rdtv)->toBe('5007756200')
        ->and($receipt->no_grs)->toBeNull()
        ->and($receipt->tanggal_terima_grs?->format('Y-m-d'))->toBe('2026-11-21')
        ->and($receipt->dokumen_verifikasi_grs)->not->toBeNull();
});
