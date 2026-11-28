<?php

use App\Filament\Pages\EditProfile;
use App\Models\User;
use App\Services\UserBadgeRenderer;
use App\Support\DeveloperBadgeRenderer;
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

    Role::firstOrCreate(['name' => 'Developer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Admin Sparepart', 'guard_name' => 'web']);
});

test('Developer user resolves selected animated badge style while non-developer always gets standard none', function (): void {
    $dev = User::query()->create([
        'name' => 'Dev User',
        'npk' => '2100100',
        'email' => 'dev_badge@pupukkaltim.com',
        'password' => bcrypt('password'),
        'developer_badge_style' => User::DEVELOPER_BADGE_FLAME,
        'is_active' => true,
    ]);
    $dev->assignRole('Developer');

    $staff = User::query()->create([
        'name' => 'Staff User',
        'npk' => '2100101',
        'email' => 'staff_badge@pupukkaltim.com',
        'password' => bcrypt('password'),
        'developer_badge_style' => User::DEVELOPER_BADGE_FLAME,
        'is_active' => true,
    ]);
    $staff->assignRole('Admin Sparepart');

    expect($dev->resolvedDeveloperBadgeStyle())->toBe(User::DEVELOPER_BADGE_FLAME)
        ->and($dev->isDeveloperBadgeAnimated())->toBeTrue()
        ->and($staff->resolvedDeveloperBadgeStyle())->toBe(User::DEVELOPER_BADGE_NONE)
        ->and($staff->isDeveloperBadgeAnimated())->toBeFalse();

    $devHtml = DeveloperBadgeRenderer::renderUserRoles($dev)->toHtml();
    $staffHtml = DeveloperBadgeRenderer::renderUserRoles($staff)->toHtml();

    expect($devHtml)->toContain('pkt-dev-badge--flame')
        ->and($devHtml)->toContain('pkt-dev-embers')
        ->and($staffHtml)->not->toContain('pkt-dev-badge')
        ->and($staffHtml)->toContain('Admin Sparepart');

    // UserBadgeRenderer (pill Avatar + Nama + NPK di tabel) juga menganimasikan khusus Developer
    UserBadgeRenderer::flushCache();
    $devPillHtml = UserBadgeRenderer::render($dev);
    $staffPillHtml = UserBadgeRenderer::render($staff);

    expect($devPillHtml)->toContain('pkt-user-pill--flame')
        ->and($devPillHtml)->toContain('pkt-user-pill-embers')
        ->and($devPillHtml)->toContain('2100100')
        ->and($staffPillHtml)->not->toContain('pkt-user-pill--')
        ->and($staffPillHtml)->toContain('2100101');
});

test('Developer can choose and save animated badge style from EditProfile page', function (): void {
    $dev = User::query()->create([
        'name' => 'Andereyan Dev',
        'npk' => '2100102',
        'email' => 'dev_profile_badge@pupukkaltim.com',
        'password' => bcrypt('password'),
        'developer_badge_style' => User::DEVELOPER_BADGE_ELECTRIC,
        'is_active' => true,
    ]);
    $dev->assignRole('Developer');
    Auth::login($dev);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(EditProfile::class)
        ->assertSee('Badge Developer')
        ->assertSee('Listrik (Energi Biru PKT)')
        ->assertSee('Api (Bara Oranye PKT)')
        ->assertSee('Kombinasi PKT (Biru & Oranye)')
        ->fillForm([
            'name' => 'Andereyan Dev',
            'npk' => '2100102',
            'email' => 'dev_profile_badge@pupukkaltim.com',
            'developer_badge_style' => User::DEVELOPER_BADGE_FLAME,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($dev->fresh()->developer_badge_style)->toBe(User::DEVELOPER_BADGE_FLAME);
});

test('Non-developer user does not see Badge Developer tab on EditProfile page', function (): void {
    $staff = User::query()->create([
        'name' => 'Staff Biasa',
        'npk' => '2100103',
        'email' => 'staff_profile_badge@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $staff->assignRole('Admin Sparepart');
    Auth::login($staff);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(EditProfile::class)
        ->assertDontSee('Badge Developer');
});
