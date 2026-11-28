<?php

use App\Filament\Pages\MemberProfile;
use App\Models\DeliveryOrderReceipt;
use App\Models\GrsRdtv;
use App\Models\InvoicingReceipt;
use App\Models\MaterialIssue;
use App\Models\User;
use App\Models\WarehouseDestination;
use App\Models\WarehouseTransmittal;
use App\Services\UserActivityLogService;
use App\Services\UserBadgeRenderer;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
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
            $table->string('cover_photo_url')->nullable();
            $table->json('left_rail_shortcuts')->nullable();
            $table->string('developer_badge_style', 30)->nullable()->default('electric');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('permissions')) {
        $permissionMigration = include database_path('migrations/2026_05_15_101350_create_permission_tables.php');
        $permissionMigration->up();
    }

    if (! Schema::hasTable('delivery_order_receipts')) {
        Schema::create('delivery_order_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('delivery_order_no')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->nullable();
            $table->string('stage')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('grs_rdtvs')) {
        Schema::create('grs_rdtvs', function (Blueprint $table): void {
            $table->id();
            $table->string('category')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('grs_rdtv_items')) {
        Schema::create('grs_rdtv_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('grs_rdtv_id');
            $table->string('document_code')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_destinations')) {
        Schema::create('warehouse_destinations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_transmittals')) {
        Schema::create('warehouse_transmittals', function (Blueprint $table): void {
            $table->id();
            $table->string('transmittal_no');
            $table->unsignedBigInteger('warehouse_destination_id')->nullable();
            $table->date('tanggal')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_transmittal_items')) {
        Schema::create('warehouse_transmittal_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('warehouse_transmittal_id');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('invoicing_receipts')) {
        Schema::create('invoicing_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('no_po')->nullable();
            $table->string('no_grs')->nullable();
            $table->string('no_invoice')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('nama_barang')->nullable();
            $table->string('jenis')->nullable();
            $table->string('material_type')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('pic_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('material_issues')) {
        Schema::create('material_issues', function (Blueprint $table): void {
            $table->id();
            $table->string('mir_number')->nullable();
            $table->string('departemen')->nullable();
            $table->string('diminta_oleh')->nullable();
            $table->string('po_number')->nullable();
            $table->string('no_reservasi')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    Role::firstOrCreate(['name' => 'Developer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Admin Sparepart', 'guard_name' => 'web']);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('UserBadgeRenderer renders clickable profile trigger by default and respects clickable=false', function (): void {
    $dev = User::query()->create([
        'name' => 'Budi Developer',
        'npk' => '2100555',
        'email' => 'budi.dev@pupukkaltim.com',
        'password' => bcrypt('password'),
        'developer_badge_style' => User::DEVELOPER_BADGE_ELECTRIC,
        'is_active' => true,
    ]);
    $dev->assignRole('Developer');

    $staff = User::query()->create([
        'name' => 'Siti Receiving',
        'npk' => '2100556',
        'email' => 'siti.rec@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $staff->assignRole('Admin Sparepart');

    UserBadgeRenderer::flushCache();

    $clickableDevHtml = UserBadgeRenderer::render($dev);
    $clickableStaffHtml = UserBadgeRenderer::render($staff);
    $staticDevHtml = UserBadgeRenderer::render($dev, 'Developer', null, clickable: false);

    expect($clickableDevHtml)->toContain('role="link"')
        ->and($clickableDevHtml)->toContain('member-profile/'.$dev->id)
        ->and($clickableDevHtml)->toContain('event.stopPropagation()')
        ->and($clickableStaffHtml)->toContain('role="link"')
        ->and($clickableStaffHtml)->toContain('member-profile/'.$staff->id)
        ->and($staticDevHtml)->not->toContain('data-profile-url=')
        ->and($staticDevHtml)->not->toContain('role="link"');
});

test('any authenticated user can view another user profile and filter their real system activity logs', function (): void {
    $viewer = User::query()->create([
        'name' => 'Staff Peninjau',
        'npk' => '2100701',
        'email' => 'viewer.staff@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $viewer->assignRole('Admin Sparepart');

    $target = User::query()->create([
        'name' => 'Andi Operasional',
        'npk' => '2100702',
        'email' => 'andi.ops@pupukkaltim.com',
        'password' => bcrypt('password'),
        'developer_badge_style' => User::DEVELOPER_BADGE_FLAME,
        'is_active' => true,
    ]);
    $target->assignRole('Developer');

    DeliveryOrderReceipt::query()->create([
        'delivery_order_no' => 'DO-PKT-2026-8801',
        'description' => 'Valve stainless untuk unit utilitas',
        'status' => 'Received',
        'stage' => 'Receiving',
        'source_type' => 'Sparepart',
        'created_by' => $target->id,
    ]);

    GrsRdtv::query()->create([
        'category' => 'grs',
        'created_by' => $target->id,
    ]);

    $destination = WarehouseDestination::query()->create(['name' => 'Gudang Sparepart 1']);
    WarehouseTransmittal::query()->create([
        'transmittal_no' => 'TR-WH/2026/0099',
        'warehouse_destination_id' => $destination->id,
        'tanggal' => now()->toDateString(),
        'created_by' => $target->id,
    ]);

    InvoicingReceipt::query()->create([
        'no_po' => '5300099887',
        'no_invoice' => 'INV-2026-4455',
        'vendor_name' => 'PT Mitra Teknik Mandiri',
        'jenis' => 'Sparepart',
        'status' => 'Proses',
        'pic_id' => $target->id,
    ]);

    MaterialIssue::query()->create([
        'mir_number' => 'MIR-2026-7711',
        'departemen' => 'Pemeliharaan Pabrik 5',
        'diminta_oleh' => 'Rudi',
        'po_number' => '5300099887',
        'created_by' => $target->id,
    ]);

    // Aktivitas milik user lain tidak boleh muncul di profil target
    DeliveryOrderReceipt::query()->create([
        'delivery_order_no' => 'DO-OTHER-9999',
        'description' => 'Milik viewer',
        'created_by' => $viewer->id,
    ]);

    Auth::login($viewer);

    Livewire::test(MemberProfile::class, ['user' => $target->id])
        ->assertOk()
        ->assertSee('Andi Operasional')
        ->assertSee('NPK 2100702')
        ->assertSee('andi.ops@pupukkaltim.com')
        ->assertDontSee('Ubah Profil Saya')
        ->assertSee('DO-PKT-2026-8801')
        ->assertSee('TR-WH/2026/0099')
        ->assertSee('INV-2026-4455')
        ->assertSee('MIR-2026-7711')
        ->assertDontSee('DO-OTHER-9999')
        ->call('setCategory', UserActivityLogService::CATEGORY_WAREHOUSE)
        ->assertSee('TR-WH/2026/0099')
        ->assertDontSee('DO-PKT-2026-8801')
        ->call('setCategory', UserActivityLogService::CATEGORY_ALL)
        ->set('search', 'MIR-2026-7711')
        ->assertSee('MIR-2026-7711')
        ->assertDontSee('TR-WH/2026/0099');

    $summary = app(UserActivityLogService::class)->getSummaryCounts($target);
    expect($summary['total'])->toBe(5)
        ->and($summary['receiving_grs'])->toBe(2)
        ->and($summary['warehouse_mir'])->toBe(2)
        ->and($summary['invoice_monitoring'])->toBe(1);
});

test('viewing own MemberProfile displays the Ubah Profil Saya button', function (): void {
    $user = User::query()->create([
        'name' => 'Pemilik Akun',
        'npk' => '2100800',
        'email' => 'owner.profile@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->assignRole('Admin Sparepart');
    Auth::login($user);

    Livewire::test(MemberProfile::class, ['user' => $user->id])
        ->assertOk()
        ->assertSee('Pemilik Akun')
        ->assertSee('Ubah Profil Saya');
});
