<?php

use App\Filament\Resources\GrsRdtvs\Pages\CreateGrsRdtv;
use App\Filament\Resources\GrsRdtvs\Pages\EditGrsRdtv;
use App\Filament\Resources\GrsRdtvs\RelationManagers\GrsRdtvItemsRelationManager;
use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\GrsRdtv;
use App\Models\GrsRdtvItem;
use App\Models\InvoicingReceipt;
use App\Models\PurchaseOrderIssued;
use App\Models\QcHistory;
use App\Models\User;
use App\Services\GrsRdtvProcessingService;
use Filament\Actions\DeleteAction;
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

test('re-uploading via EditGrsRdtv stores a unique versioned file path, clears upload state, and dispatches refresh', function () {
    $user = User::create(['name' => 'Staff Audit', 'email' => 'audit@example.com', 'password' => 'secret']);
    $this->actingAs($user);

    $do = DeliveryOrderReceipt::create([
        'document_code' => 'DO-VERSION-701',
        'delivery_order_no' => 'DO-701',
        'status' => 'Diterima',
        'post_103' => now(),
        'received_by' => $user->id,
        'created_by' => $user->id,
    ]);

    QcHistory::create([
        'delivery_order_receipt_id' => $do->id,
        'status' => 'Kembali',
    ]);

    $grs = GrsRdtv::create(['transaction_date' => '2026-11-23', 'category' => 'GRS', 'created_by' => $user->id]);

    $oldFile = TemporaryUploadedFile::fake()->createWithContent('DO-VERSION-701.pdf', '%PDF-1.4 OLD_CONTENT_V1');

    $component = Livewire::test(EditGrsRdtv::class, ['record' => $grs->id])
        ->fillForm([
            'files' => [$oldFile],
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertDispatched('grs-rdtv-items-updated');

    $firstItem = $do->grsRdtvItems()->first();
    expect($firstItem)->not->toBeNull()
        ->and($firstItem->file_path)->not->toBe('grs-rdtv-docs/DO-VERSION-701.pdf')
        ->and(Storage::disk('public')->get($firstItem->file_path))->toBe('%PDF-1.4 OLD_CONTENT_V1');

    // Klik simpan ulang tanpa upload file baru tidak boleh gagal karena state upload sudah dibersihkan
    $component
        ->call('save')
        ->assertHasNoFormErrors();

    $oldPath = $firstItem->file_path;

    // Hapus item lama lalu unggah file baru dengan nama yang sama persis
    $firstItem->delete();
    expect(Storage::disk('public')->exists($oldPath))->toBeFalse()
        ->and($do->fresh()->status)->toBe('Kembali dari ISTEK');

    $newFile = TemporaryUploadedFile::fake()->createWithContent('DO-VERSION-701.pdf', '%PDF-1.4 NEW_CONTENT_V2');

    Livewire::test(EditGrsRdtv::class, ['record' => $grs->id])
        ->fillForm([
            'files' => [$newFile],
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertDispatched('grs-rdtv-items-updated');

    $secondItem = $do->grsRdtvItems()->first();
    expect($secondItem)->not->toBeNull()
        ->and($secondItem->file_path)->not->toBe($oldPath)
        ->and(Storage::disk('public')->get($secondItem->file_path))->toBe('%PDF-1.4 NEW_CONTENT_V2')
        ->and($secondItem->document_url)->toContain('?v=');
});

test('re-uploading via CreateGrsRdtv on same date produces new unique file content and reuses batch', function () {
    $user = User::create(['name' => 'Staff Create Same Date', 'email' => 'createsame@example.com', 'password' => 'secret']);
    $this->actingAs($user);

    $do = DeliveryOrderReceipt::create([
        'document_code' => 'DO-SAMEDATE-801',
        'delivery_order_no' => 'DO-801',
        'status' => 'Diterima',
        'post_103' => now(),
        'received_by' => $user->id,
        'created_by' => $user->id,
    ]);

    QcHistory::create([
        'delivery_order_receipt_id' => $do->id,
        'status' => 'Kembali',
    ]);

    $fileV1 = TemporaryUploadedFile::fake()->createWithContent('DO-SAMEDATE-801.pdf', '%PDF-1.4 VERSION_1');

    Livewire::test(CreateGrsRdtv::class)
        ->fillForm([
            'transaction_date' => '2026-11-23',
            'category' => 'GRS',
            'files' => [$fileV1],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $item1 = $do->grsRdtvItems()->first();
    $batchId = $item1->grs_rdtv_id;
    $path1 = $item1->file_path;

    // Hapus item lama lalu create ulang di tanggal yang sama
    $item1->delete();

    $fileV2 = TemporaryUploadedFile::fake()->createWithContent('DO-SAMEDATE-801.pdf', '%PDF-1.4 VERSION_2_LATEST');

    Livewire::test(CreateGrsRdtv::class)
        ->fillForm([
            'transaction_date' => '2026-11-23',
            'category' => 'GRS',
            'files' => [$fileV2],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $item2 = $do->grsRdtvItems()->first();
    expect($item2)->not->toBeNull()
        ->and($item2->grs_rdtv_id)->toBe($batchId)
        ->and($item2->file_path)->not->toBe($path1)
        ->and(Storage::disk('public')->exists($path1))->toBeFalse()
        ->and(Storage::disk('public')->get($item2->file_path))->toBe('%PDF-1.4 VERSION_2_LATEST');
});

test('deleting one item sharing a legacy file_path preserves the physical file for the remaining item', function () {
    $do = DeliveryOrderReceipt::create([
        'document_code' => 'DO-SHARED-901',
        'delivery_order_no' => 'DO-901',
        'status' => 'GRS',
        'post_103' => now(),
    ]);

    $sharedPath = 'grs-rdtv-docs/DO-SHARED-901.pdf';
    Storage::disk('public')->put($sharedPath, '%PDF-1.4 SHARED_LEGACY');

    $rdtvBatch = GrsRdtv::create(['transaction_date' => '2026-11-20', 'category' => 'RDTV']);
    $grsBatch = GrsRdtv::create(['transaction_date' => '2026-11-23', 'category' => 'GRS']);

    $rdtvItem = GrsRdtvItem::create([
        'grs_rdtv_id' => $rdtvBatch->id,
        'delivery_order_receipt_id' => $do->id,
        'document_code' => 'DO-SHARED-901',
        'file_path' => $sharedPath,
        'status' => 'Matched',
    ]);

    $grsItem = GrsRdtvItem::create([
        'grs_rdtv_id' => $grsBatch->id,
        'delivery_order_receipt_id' => $do->id,
        'document_code' => 'DO-SHARED-901',
        'file_path' => $sharedPath,
        'status' => 'Matched',
    ]);

    // Hapus RDTV lama -> file fisik harus tetap aman karena masih dipakai oleh GRS
    $rdtvItem->delete();

    expect(Storage::disk('public')->exists($sharedPath))->toBeTrue()
        ->and($do->fresh()->status)->toBe('GRS');

    // Baru setelah GRS terakhir ikut dihapus, file fisik ikut dihapus
    $grsItem->delete();
    expect(Storage::disk('public')->exists($sharedPath))->toBeFalse();
});

test('replacing an item PDF via GrsRdtvProcessingService swaps file safely without deleting InvoicingReceipt', function () {
    $do = DeliveryOrderReceipt::create([
        'document_code' => 'DO-REPLACE-1001',
        'delivery_order_no' => 'DO-1001',
        'status' => 'GRS',
        'post_103' => now(),
    ]);

    $oldPath = 'grs-rdtv-docs/DO-REPLACE-1001__20261123000001_aaaaaa.pdf';
    Storage::disk('public')->put($oldPath, '%PDF-1.4 OLD_SLIP');

    $grs = GrsRdtv::create(['transaction_date' => '2026-11-23', 'category' => 'GRS']);
    $item = GrsRdtvItem::create([
        'grs_rdtv_id' => $grs->id,
        'delivery_order_receipt_id' => $do->id,
        'document_code' => 'DO-REPLACE-1001',
        'file_path' => $oldPath,
        'status' => 'Matched',
    ]);

    $inv = InvoicingReceipt::create([
        'grs_rdtv_item_id' => $item->id,
        'delivery_order_receipt_id' => $do->id,
        'no_po' => '5300099999',
        'jenis' => 'GRS',
        'dokumen_verifikasi_grs' => 'verifikasi-grs/official-vp-signed.pdf',
        'no_invoice' => 'INV-2026-001',
        'status' => 'Selesai',
    ]);

    $replacement = TemporaryUploadedFile::fake()->createWithContent('DO-REPLACE-1001 (1).pdf', '%PDF-1.4 NEW_REVISED_SLIP');

    $updated = app(GrsRdtvProcessingService::class)->replaceItemFile($item, $replacement);

    expect($updated->file_path)->not->toBe($oldPath)
        ->and(Storage::disk('public')->exists($oldPath))->toBeFalse()
        ->and(Storage::disk('public')->get($updated->file_path))->toBe('%PDF-1.4 NEW_REVISED_SLIP')
        ->and($inv->fresh()->id)->toBe($inv->id)
        ->and($inv->fresh()->no_invoice)->toBe('INV-2026-001')
        ->and($inv->fresh()->dokumen_verifikasi_grs)->toBe('verifikasi-grs/official-vp-signed.pdf');
});

test('EditGrsRdtv blocks changing transaction_date to a date that already has a batch of the same category', function () {
    $user = User::create(['name' => 'Staff Dup Date', 'email' => 'dupdate@example.com', 'password' => 'secret']);
    $this->actingAs($user);

    $existingBatch = GrsRdtv::create(['transaction_date' => '2026-11-20', 'category' => 'GRS', 'created_by' => $user->id]);
    $editedBatch = GrsRdtv::create(['transaction_date' => '2026-11-23', 'category' => 'GRS', 'created_by' => $user->id]);

    Livewire::test(EditGrsRdtv::class, ['record' => $editedBatch->id])
        ->fillForm([
            'transaction_date' => '2026-11-20',
        ])
        ->call('save');

    expect($editedBatch->fresh()->transaction_date->toDateString())->toBe('2026-11-23')
        ->and($existingBatch->fresh()->transaction_date->toDateString())->toBe('2026-11-20');
});

test('GrsRdtvItemsRelationManager replace_file and delete table actions work cleanly end-to-end', function () {
    $user = User::create(['name' => 'Staff RM', 'email' => 'rm@example.com', 'password' => 'secret']);
    $this->actingAs($user);

    $do = DeliveryOrderReceipt::create([
        'document_code' => 'DO-RM-1101',
        'delivery_order_no' => 'DO-1101',
        'status' => 'GRS',
        'post_103' => now(),
        'received_by' => $user->id,
        'created_by' => $user->id,
    ]);

    QcHistory::create([
        'delivery_order_receipt_id' => $do->id,
        'status' => 'Kembali',
    ]);

    $oldPath = 'grs-rdtv-docs/DO-RM-1101__20261123000001_111111.pdf';
    Storage::disk('public')->put($oldPath, '%PDF-1.4 OLD_RM_FILE');

    $grs = GrsRdtv::create(['transaction_date' => '2026-11-23', 'category' => 'GRS', 'created_by' => $user->id]);
    $item = GrsRdtvItem::create([
        'grs_rdtv_id' => $grs->id,
        'delivery_order_receipt_id' => $do->id,
        'document_code' => 'DO-RM-1101',
        'file_path' => $oldPath,
        'status' => 'Matched',
    ]);

    $inv = InvoicingReceipt::create([
        'grs_rdtv_item_id' => $item->id,
        'delivery_order_receipt_id' => $do->id,
        'no_po' => '5300099988',
        'jenis' => 'GRS',
        'dokumen_verifikasi_grs' => $oldPath,
        'status' => 'Menunggu Verifikasi',
    ]);

    $newFile = TemporaryUploadedFile::fake()->createWithContent('DO-RM-1101.pdf', '%PDF-1.4 NEW_RM_FILE');

    Livewire::test(GrsRdtvItemsRelationManager::class, [
        'ownerRecord' => $grs,
        'pageClass' => EditGrsRdtv::class,
    ])
        ->callTableAction('replace_file', $item, [
            'file' => [$newFile],
        ])
        ->assertHasNoTableActionErrors();

    $item->refresh();
    expect($item->file_path)->not->toBe($oldPath)
        ->and(Storage::disk('public')->exists($oldPath))->toBeFalse()
        ->and(Storage::disk('public')->get($item->file_path))->toBe('%PDF-1.4 NEW_RM_FILE')
        ->and($inv->fresh()->dokumen_verifikasi_grs)->toBe($item->file_path);

    $replacedPath = $item->file_path;

    // Sekarang hapus item lewat DeleteAction di RelationManager
    Livewire::test(GrsRdtvItemsRelationManager::class, [
        'ownerRecord' => $grs,
        'pageClass' => EditGrsRdtv::class,
    ])
        ->callTableAction(DeleteAction::class, $item)
        ->assertHasNoTableActionErrors();

    expect(GrsRdtvItem::find($item->id))->toBeNull()
        ->and(InvoicingReceipt::find($inv->id))->toBeNull()
        ->and(Storage::disk('public')->exists($replacedPath))->toBeFalse()
        ->and($do->fresh()->status)->toBe('Kembali dari ISTEK');
});

test('uploading GRS PDF populates delivery_date from GRS document rather than PurchaseOrderIssued and copy modal prioritizes it', function () {
    $user = User::create(['name' => 'Staff DelivDate', 'email' => 'delivdate@example.com', 'password' => 'secret']);
    $this->actingAs($user);

    $po = PurchaseOrderIssued::create([
        'purchase_order_no' => '5300066637',
        'material_type' => 'ZSP',
        'description' => 'VIDEO CONFERENCE DEP DANBA',
        'qty_po' => 1,
        'uoi' => 'SET',
        'total_amount_in_lc' => 40770000,
        'delivery_date_po' => '2026-09-01', // Tanggal lama di Purchase Order Terbit
    ]);

    $do = DeliveryOrderReceipt::create([
        'document_code' => 'DO-GRS-DELIV-1201',
        'delivery_order_no' => '66637',
        'status' => 'Kembali dari ISTEK',
        'post_103' => now(),
        'received_by' => $user->id,
        'created_by' => $user->id,
    ]);

    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $do->id,
        'purchase_order_issued_id' => $po->id,
        'description' => 'VIDEO CONFERENCE DEP DANBA',
        'quantity' => 1,
        'uoi' => 'SET',
        'material_type' => 'ZSP',
    ]);

    QcHistory::create([
        'delivery_order_receipt_id' => $do->id,
        'status' => 'Kembali',
    ]);

    // contoh_GRS.pdf memiliki Delivery Date 23.11.2026 (berbeda dengan PO Terbit 01.09.2026)
    $fixtureContent = file_get_contents(base_path('tests/Fixtures/contoh_GRS.pdf'));
    $file = TemporaryUploadedFile::fake()->createWithContent('DO-GRS-DELIV-1201.pdf', $fixtureContent);

    Livewire::test(CreateGrsRdtv::class)
        ->fillForm([
            'transaction_date' => '2026-11-23',
            'category' => 'GRS',
            'files' => [$file],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $receipt = InvoicingReceipt::with('purchaseOrderIssued')->where('delivery_order_receipt_id', $do->id)->first();
    expect($receipt)->not->toBeNull()
        ->and($receipt->delivery_date?->format('Y-m-d'))->toBe('2026-11-23')
        ->and($receipt->delivery_date_po?->format('Y-m-d'))->toBe('2026-11-23')
        ->and($receipt->purchaseOrderIssued?->delivery_date_po?->format('Y-m-d'))->toBe('2026-09-01');

    // Modal Salin ke Mitraku harus menampilkan Delivery Date dari GRS (23-11-2026), bukan dari PO Terbit (01-09-2026)
    $modalHtml = (string) view('filament.actions.copy-to-mitraku-modal', ['record' => $receipt]);
    expect($modalHtml)->toContain("copyText('23-11-2026', 'delivery_date_po')")
        ->and($modalHtml)->toContain("copyText('23-11-2026', 'delivery_date_grs')")
        ->and($modalHtml)->not->toContain("copyText('01-09-2026', 'delivery_date_po')");
});

test('invoicing:backfill-grs-delivery-date command fills missing delivery_date from stored GRS PDFs', function () {
    $do = DeliveryOrderReceipt::create([
        'document_code' => 'DO-BACKFILL-1301',
        'delivery_order_no' => '66637',
        'status' => 'GRS',
        'post_103' => now(),
    ]);

    $pdfPath = 'grs-rdtv-docs/DO-BACKFILL-1301__20261123000001_abc123.pdf';
    Storage::disk('public')->put($pdfPath, file_get_contents(base_path('tests/Fixtures/contoh_GRS.pdf')));

    $grs = GrsRdtv::create(['transaction_date' => '2026-11-10', 'category' => 'GRS']);
    $item = GrsRdtvItem::create([
        'grs_rdtv_id' => $grs->id,
        'delivery_order_receipt_id' => $do->id,
        'document_code' => 'DO-BACKFILL-1301',
        'file_path' => $pdfPath,
        'status' => 'Matched',
    ]);

    $inv = InvoicingReceipt::create([
        'grs_rdtv_item_id' => $item->id,
        'delivery_order_receipt_id' => $do->id,
        'no_po' => '5300066637',
        'jenis' => 'GRS',
        'delivery_date' => null,
    ]);

    $this->artisan('invoicing:backfill-grs-delivery-date')
        ->assertExitCode(0);

    expect($inv->fresh()->delivery_date?->format('Y-m-d'))->toBe('2026-11-23');
});
