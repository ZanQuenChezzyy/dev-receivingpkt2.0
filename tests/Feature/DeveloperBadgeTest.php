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
    Role::firstOrCreate(['name' => 'AVP Receiving', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Admin Sparepart', 'guard_name' => 'web']);
});

test('Developer and AVP Receiving users resolve selected animated badge style while other roles get standard none', function (): void {
    $dev = User::query()->create([
        'name' => 'Dev User',
        'npk' => '2100100',
        'email' => 'dev_badge@pupukkaltim.com',
        'password' => bcrypt('password'),
        'developer_badge_style' => User::DEVELOPER_BADGE_FLAME,
        'is_active' => true,
    ]);
    $dev->assignRole('Developer');

    $avp = User::query()->create([
        'name' => 'AVP Receiving User',
        'npk' => '2100109',
        'email' => 'avp_badge@pupukkaltim.com',
        'password' => bcrypt('password'),
        'developer_badge_style' => User::DEVELOPER_BADGE_PKT_COMBO,
        'is_active' => true,
    ]);
    $avp->assignRole('AVP Receiving');

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
        ->and($avp->resolvedDeveloperBadgeStyle())->toBe(User::DEVELOPER_BADGE_PKT_COMBO)
        ->and($avp->isDeveloperBadgeAnimated())->toBeTrue()
        ->and($staff->resolvedDeveloperBadgeStyle())->toBe(User::DEVELOPER_BADGE_NONE)
        ->and($staff->isDeveloperBadgeAnimated())->toBeFalse();

    $devHtml = DeveloperBadgeRenderer::renderUserRoles($dev)->toHtml();
    $avpHtml = DeveloperBadgeRenderer::renderUserRoles($avp)->toHtml();
    $staffHtml = DeveloperBadgeRenderer::renderUserRoles($staff)->toHtml();

    expect($devHtml)->toContain('pkt-dev-badge--flame')
        ->and($devHtml)->toContain('pkt-dev-embers')
        ->and($avpHtml)->toContain('pkt-dev-badge--pkt_combo')
        ->and($avpHtml)->toContain('AVP Receiving')
        ->and($staffHtml)->not->toContain('pkt-dev-badge')
        ->and($staffHtml)->toContain('Admin Sparepart');

    // UserBadgeRenderer (pill Avatar + Nama + NPK di tabel) juga menganimasikan Developer & AVP Receiving
    UserBadgeRenderer::flushCache();
    $devPillHtml = UserBadgeRenderer::render($dev);
    $avpPillHtml = UserBadgeRenderer::render($avp);
    $staffPillHtml = UserBadgeRenderer::render($staff);

    expect($devPillHtml)->toContain('pkt-user-pill--flame')
        ->and($devPillHtml)->toContain('pkt-user-pill-embers')
        ->and($devPillHtml)->toContain('2100100')
        ->and($avpPillHtml)->toContain('pkt-user-pill--pkt_combo')
        ->and($avpPillHtml)->toContain('2100109')
        ->and($staffPillHtml)->not->toContain('pkt-user-pill--')
        ->and($staffPillHtml)->toContain('2100101');
});

test('Developer and AVP Receiving can choose and save animated badge style from EditProfile page', function (): void {
    $avp = User::query()->create([
        'name' => 'AVP PKT',
        'npk' => '2100102',
        'email' => 'avp_profile_badge@pupukkaltim.com',
        'password' => bcrypt('password'),
        'developer_badge_style' => User::DEVELOPER_BADGE_ELECTRIC,
        'is_active' => true,
    ]);
    $avp->assignRole('AVP Receiving');
    Auth::login($avp);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(EditProfile::class)
        ->assertSee('Gaya Badge')
        ->assertSee('Sparkling Lightning')
        ->assertSee('Blazing Ember')
        ->assertSee('Solar Aurora')
        ->assertSee('Classic Clean')
        ->fillForm([
            'name' => 'AVP PKT',
            'npk' => '2100102',
            'email' => 'avp_profile_badge@pupukkaltim.com',
            'developer_badge_style' => User::DEVELOPER_BADGE_FLAME,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($avp->fresh()->developer_badge_style)->toBe(User::DEVELOPER_BADGE_FLAME);
});

test('Regular staff user does not see Gaya Badge tab on EditProfile page', function (): void {
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
        ->assertDontSee('Gaya Badge');
});
