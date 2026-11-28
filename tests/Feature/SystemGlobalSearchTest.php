<?php

use App\Filament\GlobalSearch\SystemGlobalSearchProvider;
use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\InvoicingReceipt;
use App\Models\PurchaseOrderIssued;
use App\Models\User;
use App\Models\Vendor;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    if (! Schema::hasTable('users')) {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('npk')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('avatar_url')->nullable();
            $table->json('left_rail_shortcuts')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('permissions')) {
        $permissionMigration = include database_path('migrations/2026_05_15_101350_create_permission_tables.php');
        $permissionMigration->up();
    }

    if (! Schema::hasTable('vendors')) {
        Schema::create('vendors', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('vendor_number')->nullable();
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->string('street')->nullable();
            $table->string('telephone_1')->nullable();
            $table->string('salesperson')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('purchase_order_issueds')) {
        Schema::create('purchase_order_issueds', function (Blueprint $table): void {
            $table->id();
            $table->string('purchase_order_and_item')->nullable();
            $table->string('purchase_order_no')->nullable();
            $table->integer('item_no')->nullable();
            $table->string('material_code')->nullable();
            $table->text('description')->nullable();
            $table->string('vendor_id')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('material_type')->nullable();
            $table->string('mrp_type')->nullable();
            $table->string('po_status')->nullable();
            $table->string('requisitioner')->nullable();
            $table->decimal('qty_po', 15, 2)->nullable();
            $table->string('uoi')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipts')) {
        Schema::create('delivery_order_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('delivery_order_no')->nullable();
            $table->string('document_code')->nullable();
            $table->string('qr_103_code')->nullable();
            $table->string('dof_number')->nullable();
            $table->string('status')->nullable();
            $table->string('stage')->nullable();
            $table->string('source_type')->nullable();
            $table->string('current_location')->nullable();
            $table->text('description')->nullable();
            $table->string('delay_reason')->nullable();
            $table->date('received_date')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipt_details')) {
        Schema::create('delivery_order_receipt_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->unsignedBigInteger('purchase_order_issued_id')->nullable();
            $table->string('material_code')->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 2)->nullable();
            $table->string('uoi')->nullable();
            $table->string('mrp_type')->nullable();
            $table->string('material_type')->nullable();
            $table->string('requisitioner')->nullable();
            $table->decimal('total_amount_snapshot', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('cashier_transmittals')) {
        Schema::create('cashier_transmittals', function (Blueprint $table): void {
            $table->id();
            $table->string('transmittal_no')->nullable();
            $table->date('sent_date')->nullable();
            $table->string('destination')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('grs_rdtv_items')) {
        Schema::create('grs_rdtv_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('grs_rdtv_id')->nullable();
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->string('document_code')->nullable();
            $table->string('status')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('invoicing_receipts')) {
        Schema::create('invoicing_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('jenis')->nullable();
            $table->string('no_invoice')->nullable();
            $table->string('no_grs')->nullable();
            $table->string('no_rdtv')->nullable();
            $table->string('no_po')->nullable();
            $table->string('no_do')->nullable();
            $table->string('faktur_pajak')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('nama_barang')->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('email_vendor')->nullable();
            $table->string('material_type')->nullable();
            $table->string('status')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('invoicing_receipt_items')) {
        Schema::create('invoicing_receipt_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('invoicing_receipt_id')->nullable();
            $table->string('material_code')->nullable();
            $table->string('no_grs')->nullable();
            $table->string('nama_barang')->nullable();
            $table->timestamps();
        });
    }

    $developerRole = Role::firstOrCreate(['name' => 'Developer', 'guard_name' => 'web']);
    $viewAnyUser = Permission::firstOrCreate(['name' => 'View Any User', 'guard_name' => 'web']);
    $viewUser = Permission::firstOrCreate(['name' => 'View User', 'guard_name' => 'web']);
    $developerRole->givePermissionTo([$viewAnyUser, $viewUser]);

    Role::firstOrCreate(['name' => 'ViewerKosong', 'guard_name' => 'web']);
});

test('Admin panel registers SystemGlobalSearchProvider with keyboard shortcut', function (): void {
    $panel = Filament::getPanel('admin');

    expect($panel->getGlobalSearchProvider())->toBeInstanceOf(SystemGlobalSearchProvider::class)
        ->and($panel->getGlobalSearchKeyBindings())->toContain('mod+k');
});

test('SystemGlobalSearchProvider searches menus, DO, Invoice/GRS, PO, Vendor, and Users', function (): void {
    $developer = User::query()->create([
        'name' => 'Andereyan SearchTester',
        'npk' => '2100999',
        'email' => 'search_dev@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $developer->assignRole('Developer');
    Auth::login($developer);

    $panel = Filament::getPanel('admin');
    Filament::setCurrentPanel($panel);

    $vendor = Vendor::query()->create([
        'name' => 'PT KALTIM PRIMA SEARCH',
        'vendor_number' => 'VND-778899',
        'city' => 'Bontang',
        'email' => 'billing@kaltimprima.co.id',
    ]);

    $po = PurchaseOrderIssued::query()->create([
        'purchase_order_and_item' => '4500998877-00010',
        'purchase_order_no' => '4500998877',
        'item_no' => 10,
        'material_code' => '5001234',
        'description' => 'SACK UREA 50KG SPECIAL SEARCH',
        'vendor_id' => $vendor->vendor_number,
        'vendor_name' => $vendor->name,
        'material_type' => 'ZPM',
        'mrp_type' => 'V1',
        'requisitioner' => 'DEP LOGISTIK',
    ]);

    $do = DeliveryOrderReceipt::query()->create([
        'delivery_order_no' => 'DO-PKT-998877',
        'status' => 'GRS',
        'received_date' => now()->toDateString(),
    ]);

    DeliveryOrderReceiptDetail::query()->create([
        'delivery_order_receipt_id' => $do->id,
        'purchase_order_issued_id' => $po->id,
        'material_code' => '5001234',
        'description' => 'SACK UREA 50KG SPECIAL SEARCH',
        'quantity' => 500,
        'uoi' => 'PCS',
    ]);

    InvoicingReceipt::query()->create([
        'jenis' => 'GRS',
        'no_invoice' => 'INV-PKT-998877',
        'no_grs' => '5000998877',
        'no_po' => '4500998877',
        'no_do' => 'DO-PKT-998877',
        'vendor_name' => $vendor->name,
        'nama_barang' => 'SACK UREA 50KG SPECIAL SEARCH',
        'material_type' => 'ZPM',
        'status' => 'Selesai',
    ]);

    $provider = new SystemGlobalSearchProvider;

    // 1. Pencarian nomor PO menemukan DO, Detail DO, Invoice, dan PO Terbit sekaligus
    $poResults = $provider->getResults('4500998877');
    $poTitles = $poResults->getCategories()->flatten(1)->pluck('title')->all();
    expect($poTitles)->toContain('DO DO-PKT-998877')
        ->and($poTitles)->toContain('Invoice INV-PKT-998877')
        ->and($poTitles)->toContain('4500998877-00010');

    // 2. Pencarian nomor GRS menemukan Invoice terkait
    $grsResults = $provider->getResults('5000998877');
    $grsTitles = $grsResults->getCategories()->flatten(1)->pluck('title')->all();
    expect($grsTitles)->toContain('Invoice INV-PKT-998877');

    // 3. Pencarian Vendor menemukan data vendor & dokumen terkait
    $vendorResults = $provider->getResults('KALTIM PRIMA SEARCH');
    $vendorTitles = $vendorResults->getCategories()->flatten(1)->pluck('title')->all();
    expect($vendorTitles)->toContain('PT KALTIM PRIMA SEARCH');

    // 4. Pencarian User berdasarkan NPK
    $userResults = $provider->getResults('2100999');
    $userTitles = $userResults->getCategories()->flatten(1)->pluck('title')->all();
    expect($userTitles)->toContain('Andereyan SearchTester');

    // 5. Pencarian Menu & Halaman (misal profil / beranda)
    $menuResults = $provider->getResults('profil');
    expect($menuResults->getCategories()->has(__('Menu & Halaman')))->toBeTrue();
    $menuTitles = $menuResults->getCategories()->get(__('Menu & Halaman'))->pluck('title')->all();
    expect($menuTitles)->toContain(__('Edit Profil'));
});

test('SystemGlobalSearchProvider respects user permissions and hides unauthorized resources', function (): void {
    User::query()->create([
        'name' => 'Akun Rahasia Khusus Admin',
        'npk' => '2100888',
        'email' => 'secret_admin@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);

    $restrictedUser = User::query()->create([
        'name' => 'User Terbatas',
        'npk' => '2100777',
        'email' => 'restricted_search@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $restrictedUser->assignRole('ViewerKosong');
    Auth::login($restrictedUser);

    $panel = Filament::getPanel('admin');
    Filament::setCurrentPanel($panel);

    $provider = new SystemGlobalSearchProvider;
    $results = $provider->getResults('Akun Rahasia Khusus Admin');

    $titles = $results->getCategories()->flatten(1)->pluck('title')->all();
    expect($titles)->not->toContain('Akun Rahasia Khusus Admin');
});
