<?php

use App\Filament\Livewire\LeftIconRail;
use App\Filament\Livewire\MegaMenuTopbar;
use App\Models\User;
use App\Services\LeftRailMenuService;
use Filament\Enums\UserMenuPosition;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
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

    if (! Schema::hasTable('notifications')) {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    Role::firstOrCreate(['name' => 'Developer', 'guard_name' => 'web']);
});

test('Admin panel is configured with Top Navigation, MegaMenuTopbar, Plus Jakarta Sans font, and PKT brand colors', function (): void {
    $panel = Filament::getPanel('admin');

    expect($panel->hasTopNavigation())->toBeTrue()
        ->and($panel->getTopbarLivewireComponent())->toBe(MegaMenuTopbar::class)
        ->and($panel->getFontFamily())->toBe('Plus Jakarta Sans')
        ->and($panel->getUserMenuPosition())->toBe(UserMenuPosition::Topbar);
});

test('MegaMenuTopbar renders structured mega menu categories respecting navigation groups', function (): void {
    $user = User::query()->create([
        'name' => 'Developer Redesign',
        'npk' => '2100088',
        'email' => 'dev_redesign@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->assignRole('Developer');
    Auth::login($user);

    $panel = Filament::getPanel('admin');
    Filament::setCurrentPanel($panel);

    $panel->navigation(fn (NavigationBuilder $builder): NavigationBuilder => $builder->groups([
        NavigationGroup::make()->items([
            NavigationItem::make('Dashboard')
                ->url('/admin')
                ->icon('heroicon-o-home')
                ->isActiveWhen(fn (): bool => true),
        ]),
        NavigationGroup::make('Penerimaan Receiving')->items([
            NavigationItem::make('Penerimaan DO')->url('/admin/delivery-order-receipts')->icon('heroicon-o-truck'),
        ]),
        NavigationGroup::make('Transaksi')->items([
            NavigationItem::make('GRS & RDTV')->url('/admin/grs-rdtvs')->icon('heroicon-o-document-check'),
        ]),
        NavigationGroup::make('Penerimaan Jasa / Invoice')->items([
            NavigationItem::make('Invoice Barang')->url('/admin/invoicing-receipts')->icon('heroicon-o-receipt-percent'),
        ]),
        NavigationGroup::make('Data Master')->items([
            NavigationItem::make('Data Vendor')->url('/admin/vendors')->icon('heroicon-o-building-office'),
        ]),
    ]));

    $component = Livewire::test(MegaMenuTopbar::class);
    $structure = $component->instance()->getMegaMenuStructure();
    $keys = array_column($structure, 'key');

    expect($keys)->toBe(['dashboard', 'penerimaan', 'invoice', 'master']);

    $penerimaan = collect($structure)->firstWhere('key', 'penerimaan');
    expect($penerimaan['columns'])->toHaveCount(2)
        ->and($penerimaan['columns'][0]['title'])->toBe(__('Penerimaan Receiving'))
        ->and($penerimaan['columns'][1]['title'])->toBe(__('Transaksi QC & GRS'));

    $component
        ->assertSee('Dashboard')
        ->assertSee('Penerimaan')
        ->assertSee('Invoice')
        ->assertSee('Master & Sistem')
        ->assertSee('Penerimaan DO')
        ->assertSee('GRS & RDTV')
        ->assertSee('Invoice Barang')
        ->assertSee('Data Vendor');
});

test('LeftIconRail locks Dashboard at top, moves Beranda to user menu, and saves per-user custom shortcuts', function (): void {
    $user = User::query()->create([
        'name' => 'Developer Rail',
        'npk' => '2100089',
        'email' => 'dev_rail@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->assignRole('Developer');
    Auth::login($user);

    $panel = Filament::getPanel('admin');
    Filament::setCurrentPanel($panel);

    // Beranda dipindah ke User Menu agar tidak tertukar dengan Dashboard di Rail Kiri
    $userMenuItems = $panel->getUserMenuItems();
    expect($userMenuItems)->toHaveKey('home_frontend')
        ->and($userMenuItems)->toHaveKey('customize_left_rail');

    $component = Livewire::test(LeftIconRail::class)
        ->assertSeeHtml('pkt-left-rail')
        ->assertSeeHtml('pkt-rail-btn')
        ->assertSeeHtml('pkt-rail-theme-pill')
        ->assertSee('Mode Gelap')
        ->assertSee('Mode Terang')
        ->assertDontSee('Beranda');

    // Buka pengaturan, pastikan Dashboard terkunci & tidak bisa dihapus
    $component->call('openSettings')
        ->assertSet('showSettingsModal', true)
        ->assertSee('Atur Menu Navigasi Kiri')
        ->assertSee('Terkunci');

    // Mencoba toggle 'dashboard' tidak berpengaruh
    $beforeToggle = $component->get('selectedKeys');
    $component->call('toggleShortcut', LeftRailMenuService::DASHBOARD_KEY);
    expect($component->get('selectedKeys'))->toBe($beforeToggle);

    // Simpan kustomisasi pilihan user dan pastikan tersimpan ke database
    $service = app(LeftRailMenuService::class);
    $availableKeys = array_keys($service->availableCustomizableItems());
    expect(count($availableKeys))->toBeGreaterThanOrEqual(2);

    $chosen = [$availableKeys[1], $availableKeys[0]];
    $component
        ->set('selectedKeys', $chosen)
        ->call('moveShortcutUp', 1)
        ->call('saveSettings')
        ->assertSet('showSettingsModal', false);

    // Setelah moveShortcutUp(1), urutan berbalik menjadi [$availableKeys[0], $availableKeys[1]]
    expect($user->fresh()->left_rail_shortcuts)->toBe([$availableKeys[0], $availableKeys[1]]);

    // Dashboard selalu wajib di indeks 0 pada railLinks
    $resolved = $service->resolveRailLinks($user->fresh(), url('/admin'));
    expect($resolved[0]['key'])->toBe(LeftRailMenuService::DASHBOARD_KEY)
        ->and($resolved[0]['locked'])->toBeTrue()
        ->and($resolved[1]['key'])->toBe($availableKeys[0])
        ->and($resolved[2]['key'])->toBe($availableKeys[1]);
});
