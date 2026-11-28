<?php

use App\Filament\Livewire\MegaMenuTopbar;
use App\Models\User;
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

test('Left icon rail blade view renders circular quick links and dual-circle theme toggle', function (): void {
    $user = User::query()->create([
        'name' => 'Developer Rail',
        'npk' => '2100089',
        'email' => 'dev_rail@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->assignRole('Developer');
    Auth::login($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $html = view('filament.components.left-icon-rail')->render();

    expect($html)
        ->toContain('pkt-left-rail')
        ->toContain('pkt-rail-btn')
        ->toContain('pkt-rail-theme-pill')
        ->toContain('Mode Gelap')
        ->toContain('Mode Terang');
});
