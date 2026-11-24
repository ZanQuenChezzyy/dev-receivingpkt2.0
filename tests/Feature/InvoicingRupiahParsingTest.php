<?php

use App\Filament\Resources\InvoicingReceipts\Pages\EditInvoicingReceipt;
use App\Models\InvoicingReceipt;
use App\Models\User;
use App\Services\MonitoringBarangImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Schema::dropIfExists('invoicing_email_logs');
    Schema::dropIfExists('invoicing_receipt_items');
    Schema::dropIfExists('invoicing_receipts');
    Schema::dropIfExists('cashier_transmittals');
    Schema::dropIfExists('grs_rdtv_items');
    Schema::dropIfExists('grs_rdtvs');
    Schema::dropIfExists('delivery_order_receipt_details');
    Schema::dropIfExists('delivery_order_receipts');
    Schema::dropIfExists('purchase_order_issueds');
    Schema::dropIfExists('vendors');
    Schema::dropIfExists('users');

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->boolean('is_active')->default(true);
        $table->string('npk')->nullable();
        $table->string('avatar_url')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    if (! Schema::hasTable('permissions')) {
        $permissionMigration = include database_path('migrations/2026_05_15_101350_create_permission_tables.php');
        $permissionMigration->up();
    }

    Schema::create('vendors', function (Blueprint $table): void {
        $table->id();
        $table->string('name')->nullable();
        $table->string('vendor_number')->nullable();
        $table->text('email')->nullable();
        $table->timestamps();
    });

    Schema::create('purchase_order_issueds', function (Blueprint $table): void {
        $table->id();
        $table->string('purchase_order_no')->nullable();
        $table->integer('item_no')->nullable();
        $table->string('material_code')->nullable();
        $table->string('description')->nullable();
        $table->decimal('qty_po', 12, 3)->default(0);
        $table->string('uoi')->nullable();
        $table->string('material_type')->nullable();
        $table->string('vendor_id')->nullable();
        $table->string('vendor_name')->nullable();
        $table->date('delivery_date_po')->nullable();
        $table->decimal('total_amount_in_lc', 18, 2)->default(0);
        $table->timestamps();
    });

    Schema::create('delivery_order_receipts', function (Blueprint $table): void {
        $table->id();
        $table->string('delivery_order_no')->nullable();
        $table->string('document_code')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
    });

    Schema::create('delivery_order_receipt_details', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
        $table->unsignedBigInteger('purchase_order_issued_id')->nullable();
        $table->decimal('quantity', 12, 3)->default(0);
        $table->string('description')->nullable();
        $table->string('uoi')->nullable();
        $table->string('material_type')->nullable();
        $table->timestamps();
    });

    Schema::create('grs_rdtvs', function (Blueprint $table): void {
        $table->id();
        $table->date('transaction_date')->nullable();
        $table->string('category')->nullable();
        $table->timestamps();
    });

    Schema::create('grs_rdtv_items', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('grs_rdtv_id')->nullable();
        $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
        $table->string('file_path')->nullable();
        $table->timestamps();
    });

    Schema::create('cashier_transmittals', function (Blueprint $table): void {
        $table->id();
        $table->string('transmittal_no')->nullable();
        $table->timestamps();
    });

    $createInvoicingMigration = include database_path('migrations/2026_10_24_121247_create_invoicing_receipts_table.php');
    $createInvoicingMigration->up();
    $addInvoiceDocMigration = include database_path('migrations/2026_10_24_135117_add_dokumen_invoice_to_invoicing_receipts_table.php');
    $addInvoiceDocMigration->up();
    $addKelengkapanMigration = include database_path('migrations/2026_10_26_194128_add_dokumen_kelengkapan_to_invoicing_receipts_table.php');
    $addKelengkapanMigration->up();
    $addKategoriMigration = include database_path('migrations/2026_11_17_182934_add_kategori_barang_to_invoicing_receipts_table.php');
    $addKategoriMigration->up();
    $addGrsMetaMigration = include database_path('migrations/2026_11_19_231800_add_grs_metadata_to_invoicing_receipts_table.php');
    $addGrsMetaMigration->up();
    $addInvoiceDatesMigration = include database_path('migrations/2026_11_20_001344_add_invoice_dates_to_invoicing_receipts_table.php');
    $addInvoiceDatesMigration->up();
    $addCashierMigration = include database_path('migrations/2026_11_22_194127_add_cashier_transmittal_id_to_invoicing_receipts_table.php');
    $addCashierMigration->up();

    $createItemsMigration = include database_path('migrations/2026_11_18_093000_create_invoicing_receipt_items_table.php');
    $createItemsMigration->up();
    $dropSubtotalsMigration = include database_path('migrations/2026_11_19_223946_drop_subtotals_from_invoicing_receipt_items_table.php');
    $dropSubtotalsMigration->up();

    $createEmailLogsMigration = include database_path('migrations/2026_11_23_012849_create_invoicing_email_logs_table.php');
    $createEmailLogsMigration->up();
});

it('parses single-dot Indonesian thousands like 946.000 and 1.050 without dividing by 1000', function (mixed $input, ?float $expected): void {
    expect(InvoicingReceipt::parseRupiah($input))->toBe($expected);
})->with([
    '946.000 masked' => ['946.000', 946000.0],
    '1.050 masked' => ['1.050', 1050.0],
    '1.050.060 masked' => ['1.050.060', 1050060.0],
    '1.000 masked' => ['1.000', 1000.0],
    '999.999 masked' => ['999.999', 999999.0],
    'Rp 946.000' => ['Rp 946.000', 946000.0],
    'Rp 946.000,50' => ['Rp 946.000,50', 946001.0],
    'database decimal string 946000.00' => ['946000.00', 946000.0],
    'database decimal string 946.00' => ['946.00', 946.0],
    'integer 946000' => [946000, 946000.0],
    'float 946000.0' => [946000.0, 946000.0],
    'comma thousand 946,000' => ['946,000', 946000.0],
    'comma thousand with decimals 1,050,060.00' => ['1,050,060.00', 1050060.0],
    'zero string' => ['0', 0.0],
    'zero decimal string' => ['0.00', 0.0],
    'empty string' => ['', null],
    'null value' => [null, null],
]);

it('computes and persists exact PPN 11% on EditInvoicingReceipt when entering 946.000 and preserves it on re-save', function (): void {
    $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = User::create([
        'name' => 'Admin Test',
        'email' => 'admin@receiving.com',
        'password' => Hash::make('password'),
        'is_active' => true,
    ]);
    $user->assignRole($superAdmin);

    $receipt = InvoicingReceipt::create([
        'jenis' => 'GRS',
        'no_po' => '5300067497',
        'no_grs' => '5007756199',
        'no_do' => 'DO-67497-01',
        'tanggal_terima_grs' => '2026-11-23',
        'status' => 'Menunggu Verifikasi',
    ]);

    Livewire::actingAs($user)
        ->test(EditInvoicingReceipt::class, ['record' => $receipt->getRouteKey()])
        ->set('data.subtotal_exclude_ppn', '946.000')
        ->assertSet('data.subtotal_include_ppn', '1.050.060')
        ->call('save')
        ->assertHasNoFormErrors();

    $receipt->refresh();
    expect((float) $receipt->subtotal_exclude_ppn)->toBe(946000.0)
        ->and((float) $receipt->subtotal_include_ppn)->toBe(1050060.0);

    // Buka ulang halaman Edit dan klik Save tanpa mengubah angka: nilai tidak boleh terbagi 1.000
    Livewire::actingAs($user)
        ->test(EditInvoicingReceipt::class, ['record' => $receipt->getRouteKey()])
        ->assertFormSet([
            'subtotal_exclude_ppn' => '946.000',
            'subtotal_include_ppn' => '1.050.060',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $receipt->refresh();
    expect((float) $receipt->subtotal_exclude_ppn)->toBe(946000.0)
        ->and((float) $receipt->subtotal_include_ppn)->toBe(1050060.0);
});

it('parses foreign currency single-dot thousands cleanly during Monitoring Barang CSV import', function (): void {
    $csv = implode("\n", [
        ' ,Tanggal Terima GRS,Jenis,No. GRS,No. RDTV,No. PO,,PIC,PADI,Vendor,Deskripsi,Email,Tanggal Info GRS,,Vendor,PO,No. Invoice, No. DO ,Faktur Pajak,Nama Barang,Quantity Penagihan,Quantity di PO,UOI, Sub Total (Exclude PPN), Sub Total (Include PPN),Tanggal terima & koreksi Invoice,Tanggal Kirim Kasir,Lama Pekerjaan,Keterangan',
        '1,9 Juni 2025,Sparepart,5006440673,,5300057228,,RIZKI,,SHIN NIPPON,TURBIN,test@vendor.com,9 Juni 2025,,SHIN NIPPON,5300057228,KALTI-52085,57228,000000,TURBIN,1,1,LOT,¥485.000," Rp 538.350 ",10 Juni 2025,10 Juni 2025,0,',
    ]);

    app(MonitoringBarangImportService::class)->importFromString($csv);

    $record = InvoicingReceipt::where('no_grs', '5006440673')->firstOrFail();
    expect((float) $record->subtotal_exclude_ppn)->toBe(485000.0)
        ->and((float) $record->subtotal_include_ppn)->toBe(538350.0);
});
