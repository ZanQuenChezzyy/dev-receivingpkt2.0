<?php

use App\Filament\Pages\EditProfile;
use App\Models\User;
use App\Services\BadgeMilestoneService;
use App\Services\UserBadgeRenderer;
use App\Support\DeveloperBadgeRenderer;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
            $table->boolean('has_exclusive_badge')->nullable()->default(null);
            $table->json('unlocked_exclusive_badges')->nullable();
            $table->json('badge_milestone_notified')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
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

    if (! Schema::hasTable('permissions')) {
        $permissionMigration = include database_path('migrations/2026_05_15_101350_create_permission_tables.php');
        $permissionMigration->up();
    }

    Role::firstOrCreate(['name' => 'Developer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'AVP Receiving', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Admin Sparepart', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Admin Chemical', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Admin Bahan Baku', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Operator', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Staff Invoice', 'guard_name' => 'web']);
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
    $dev->assignRole(['Developer', 'Staff Invoice']);

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
        ->and($devHtml)->toContain('Staff Invoice')
        ->and(substr_count($devHtml, 'pkt-dev-badge--flame'))->toBe(2)
        ->and($avpHtml)->toContain('pkt-dev-badge--pkt_combo')
        ->and($avpHtml)->toContain('pkt-dev-icon-aurora')
        ->and($avpHtml)->toContain('pkt-aurora-sheen')
        ->and($avpHtml)->toContain('pkt-aurora-stars')
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
        ->assertSee('Foto Sampul (Cover Photo / GIF)')
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

test('Regular staff user can customize Classic Clean badge while exclusive animated badges appear locked and cannot be saved', function (): void {
    $staff = User::query()->create([
        'name' => 'Staff Biasa',
        'npk' => '2100103',
        'email' => 'staff_profile_badge@pupukkaltim.com',
        'password' => bcrypt('password'),
        'developer_badge_style' => User::DEVELOPER_BADGE_ELECTRIC,
        'is_active' => true,
    ]);
    $staff->assignRole('Admin Sparepart');
    Auth::login($staff);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(EditProfile::class)
        ->assertSee('Gaya Badge')
        ->assertSee('Classic Amber')
        ->assertSee('Classic Cobalt')
        ->assertSee('Classic Emerald')
        ->assertSee('Sparkling Lightning')
        ->assertSee('Blazing Ember')
        ->assertSee('Solar Aurora')
        ->assertSee('Terkunci')
        ->assertSee('Misi 1.000 / 5.000 DO & GRS/RDTV atau hak eksklusif Developer')
        ->assertSee('Misi 50.000 DO & GRS/RDTV atau hak eksklusif Developer')
        ->fillForm([
            'name' => 'Staff Biasa',
            'npk' => '2100103',
            'email' => 'staff_profile_badge@pupukkaltim.com',
            'developer_badge_style' => User::CLASSIC_BADGE_EMERALD,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $freshStaff = $staff->fresh();
    expect($freshStaff->developer_badge_style)->toBe(User::CLASSIC_BADGE_EMERALD)
        ->and($freshStaff->resolvedDeveloperBadgeStyle())->toBe(User::CLASSIC_BADGE_EMERALD)
        ->and($freshStaff->isDeveloperBadgeAnimated())->toBeFalse();

    UserBadgeRenderer::flushCache();
    expect(UserBadgeRenderer::render($freshStaff))->toContain('pkt-classic-pill--classic_emerald')
        ->and(DeveloperBadgeRenderer::renderUserRoles($freshStaff)->toHtml())->toContain('pkt-classic-badge--classic_emerald')
        ->and(DeveloperBadgeRenderer::renderUserRoles($freshStaff)->toHtml())->not->toContain('pkt-dev-badge');

    // Mencoba menyimpan badge eksklusif oleh staf biasa harus ditolak oleh validasi form
    Livewire::test(EditProfile::class)
        ->fillForm([
            'name' => 'Staff Biasa',
            'npk' => '2100103',
            'email' => 'staff_profile_badge@pupukkaltim.com',
            'developer_badge_style' => User::DEVELOPER_BADGE_FLAME,
        ])
        ->call('save')
        ->assertHasFormErrors(['developer_badge_style']);

    expect($staff->fresh()->resolvedDeveloperBadgeStyle())->toBe(User::CLASSIC_BADGE_EMERALD);
});

test('EditProfile accepts animated GIF cover photos without destructive auto-transform', function (): void {
    Storage::fake('public');

    $user = User::query()->create([
        'name' => 'Pengguna GIF',
        'npk' => '2100199',
        'email' => 'gif_cover@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->assignRole('Admin Sparepart');
    Auth::login($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $gifUpload = UploadedFile::fake()->createWithContent(
        'banner-gerak.gif',
        "GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xff\xff\xff!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;"
    );

    $component = Livewire::test(EditProfile::class)
        ->fillForm([
            'name' => 'Pengguna GIF',
            'npk' => '2100199',
            'email' => 'gif_cover@pupukkaltim.com',
            'cover_photo_url' => $gifUpload,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $savedPath = $user->fresh()->cover_photo_url;
    expect($savedPath)->not->toBeNull()
        ->and($savedPath)->toEndWith('.gif');
    Storage::disk('public')->assertExists($savedPath);

    /** @var Filament\Forms\Components\FileUpload $coverField */
    $coverField = $component->instance()->form->getFlatFields()['cover_photo_url'];
    expect($coverField->getAutomaticallyCropImagesAspectRatio())->toBeNull()
        ->and($coverField->getAutomaticallyResizeImagesMode())->toBeNull()
        ->and($coverField->getAcceptedFileTypes())->toContain('image/gif');
});

test('Developer can grant and revoke exclusive animated badge access for any user', function (): void {
    $staff = User::query()->create([
        'name' => 'Staf Pilihan',
        'npk' => '2100301',
        'email' => 'staf_eksklusif@pupukkaltim.com',
        'password' => bcrypt('password'),
        'developer_badge_style' => User::DEVELOPER_BADGE_NONE,
        'is_active' => true,
    ]);
    $staff->assignRole('Staff Invoice');

    // Awalnya belum punya hak eksklusif
    expect($staff->canCustomizeAnimatedBadge())->toBeFalse()
        ->and($staff->isDeveloperBadgeAnimated())->toBeFalse();

    // Developer mengaktifkan hak badge eksklusif untuk user ini
    $staff->forceFill(['has_exclusive_badge' => true])->save();
    $staff->refresh();

    expect($staff->canCustomizeAnimatedBadge())->toBeTrue();

    Auth::login($staff);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(EditProfile::class)
        ->assertSee('Hak Badge Eksklusif Aktif')
        ->fillForm([
            'name' => 'Staf Pilihan',
            'npk' => '2100301',
            'email' => 'staf_eksklusif@pupukkaltim.com',
            'developer_badge_style' => User::DEVELOPER_BADGE_PKT_COMBO,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $staff->refresh();
    UserBadgeRenderer::flushCache();
    expect($staff->resolvedDeveloperBadgeStyle())->toBe(User::DEVELOPER_BADGE_PKT_COMBO)
        ->and($staff->isDeveloperBadgeAnimated())->toBeTrue()
        ->and(UserBadgeRenderer::render($staff))->toContain('pkt-user-pill--pkt_combo')
        ->and(DeveloperBadgeRenderer::renderUserRoles($staff)->toHtml())->toContain('pkt-dev-badge--pkt_combo');

    // Developer mencabut kembali hak eksklusif -> langsung kembali ke Classic Clean
    $staff->forceFill(['has_exclusive_badge' => false])->save();
    $staff->refresh();
    UserBadgeRenderer::flushCache();

    expect($staff->canCustomizeAnimatedBadge())->toBeFalse()
        ->and($staff->resolvedDeveloperBadgeStyle())->toBe(User::DEVELOPER_BADGE_NONE)
        ->and($staff->isDeveloperBadgeAnimated())->toBeFalse()
        ->and(UserBadgeRenderer::render($staff))->not->toContain('pkt-user-pill--');
});

test('regular user unlocks exclusive badges via 1000, 5000, and 50000 DO milestones with gift notifications', function (): void {
    $andre = User::query()->create([
        'name' => 'Andre Receiving',
        'npk' => '2100401',
        'email' => 'andre.do@pupukkaltim.com',
        'password' => bcrypt('password'),
        'developer_badge_style' => User::DEVELOPER_BADGE_NONE,
        'is_active' => true,
    ]);
    $andre->assignRole('Staff Invoice');

    $service = app(BadgeMilestoneService::class);

    // 1. Di bawah 1.000 DO: belum ada hadiah dan klaim ditolak
    expect($service->syncMilestonesAndNotify($andre, doCountOverride: 500))->toBe([])
        ->and($service->claimGiftBadge($andre, User::DEVELOPER_BADGE_ELECTRIC, doCountOverride: 500))->toBeFalse()
        ->and(DB::table('notifications')->where('notifiable_id', $andre->id)->count())->toBe(0);

    // 2. Mencapai 1.000 DO: dapat 1 notifikasi hadiah (tidak duplikat) & bebas pilih Sparkling Lightning atau Blazing Ember
    expect($service->syncMilestonesAndNotify($andre, doCountOverride: 1000))->toBe(['1000'])
        ->and($service->syncMilestonesAndNotify($andre->fresh(), doCountOverride: 1000))->toBe([])
        ->and(DB::table('notifications')->where('notifiable_id', $andre->id)->count())->toBe(1);

    $progress1k = $service->getProgress($andre->fresh(), doCountOverride: 1000);
    expect($progress1k['pending_choice_slots'])->toBe(1)
        ->and($progress1k['claimable_choice_badges'])->toBe(array_keys(User::standardExclusiveBadgeOptions()))
        ->and($progress1k['claimable_mythic_badges'])->toBe([])
        ->and($progress1k['can_claim_solar_aurora'])->toBeFalse();

    // Andre memilih Royal Obsidian (termasuk 6 badge Eksklusif) sebagai hadiah 1.000
    expect($service->claimGiftBadge($andre->fresh(), User::DEVELOPER_BADGE_OBSIDIAN_GOLD, doCountOverride: 1000))->toBeTrue();
    $andre->refresh();

    expect($andre->canUseBadgeStyle(User::DEVELOPER_BADGE_OBSIDIAN_GOLD))->toBeTrue()
        ->and($andre->canUseBadgeStyle(User::DEVELOPER_BADGE_FLAME))->toBeFalse()
        ->and($andre->canUseBadgeStyle(User::DEVELOPER_BADGE_PKT_COMBO))->toBeFalse()
        ->and($andre->resolvedDeveloperBadgeStyle())->toBe(User::DEVELOPER_BADGE_OBSIDIAN_GOLD)
        ->and($service->claimGiftBadge($andre, User::DEVELOPER_BADGE_FLAME, doCountOverride: 1000))->toBeFalse();

    // 3. Mencapai 5.000: membuka seluruh sisa 5 badge Eksklusif (Sparkling, Blazing, Arctic, Amethyst, Rose)
    expect($service->syncMilestonesAndNotify($andre, doCountOverride: 5000))->toBe(['5000'])
        ->and(DB::table('notifications')->where('notifiable_id', $andre->id)->count())->toBe(2);

    $progress5k = $service->getProgress($andre->fresh(), doCountOverride: 5000);
    expect($progress5k['claimable_choice_badges'])->toBe([
        User::DEVELOPER_BADGE_ELECTRIC,
        User::DEVELOPER_BADGE_FLAME,
        User::DEVELOPER_BADGE_ARCTIC_PLATINUM,
        User::DEVELOPER_BADGE_AMETHYST_CROWN,
        User::DEVELOPER_BADGE_ROSE_TITANIUM,
    ])
        ->and($service->claimGiftBadge($andre->fresh(), User::DEVELOPER_BADGE_ROSE_TITANIUM, doCountOverride: 5000))->toBeTrue();

    $andre->refresh();
    expect($andre->canUseBadgeStyle(User::DEVELOPER_BADGE_OBSIDIAN_GOLD))->toBeTrue()
        ->and($andre->canUseBadgeStyle(User::DEVELOPER_BADGE_ROSE_TITANIUM))->toBeTrue()
        ->and($andre->canUseBadgeStyle(User::DEVELOPER_BADGE_PKT_COMBO))->toBeFalse()
        ->and($andre->canUseBadgeStyle(User::DEVELOPER_BADGE_EMERALD_SOVEREIGN))->toBeFalse()
        ->and($andre->canUseBadgeStyle(User::DEVELOPER_BADGE_CRIMSON_ECLIPSE))->toBeFalse()
        ->and($service->claimGiftBadge($andre, User::DEVELOPER_BADGE_PKT_COMBO, doCountOverride: 5000))->toBeFalse();

    // 4. Mencapai 50.000: membuka tepat 3 badge Tier Tertinggi (Solar Aurora, Emerald Sovereign, Crimson Eclipse)
    expect($service->syncMilestonesAndNotify($andre, doCountOverride: 50000))->toBe(['50000'])
        ->and(DB::table('notifications')->where('notifiable_id', $andre->id)->count())->toBe(3);

    $progress50k = $service->getProgress($andre->fresh(), doCountOverride: 50000);
    expect($progress50k['can_claim_solar_aurora'])->toBeTrue()
        ->and($progress50k['claimable_mythic_badges'])->toBe([
            User::DEVELOPER_BADGE_PKT_COMBO,
            User::DEVELOPER_BADGE_EMERALD_SOVEREIGN,
            User::DEVELOPER_BADGE_CRIMSON_ECLIPSE,
        ])
        ->and($service->claimGiftBadge($andre->fresh(), User::DEVELOPER_BADGE_PKT_COMBO, doCountOverride: 50000))->toBeTrue()
        ->and($service->claimGiftBadge($andre->fresh(), User::DEVELOPER_BADGE_EMERALD_SOVEREIGN, doCountOverride: 50000))->toBeTrue()
        ->and($service->claimGiftBadge($andre->fresh(), User::DEVELOPER_BADGE_CRIMSON_ECLIPSE, doCountOverride: 50000))->toBeTrue();

    $andre->refresh();
    UserBadgeRenderer::flushCache();
    expect($andre->canUseBadgeStyle(User::DEVELOPER_BADGE_PKT_COMBO))->toBeTrue()
        ->and($andre->canUseBadgeStyle(User::DEVELOPER_BADGE_EMERALD_SOVEREIGN))->toBeTrue()
        ->and($andre->canUseBadgeStyle(User::DEVELOPER_BADGE_CRIMSON_ECLIPSE))->toBeTrue()
        ->and($andre->resolvedDeveloperBadgeStyle())->toBe(User::DEVELOPER_BADGE_CRIMSON_ECLIPSE)
        ->and(UserBadgeRenderer::render($andre))->toContain('pkt-user-pill--crimson_eclipse')
        ->and(UserBadgeRenderer::render($andre))->toContain('pkt-fx-glitch-bars')
        ->and(DeveloperBadgeRenderer::render(User::DEVELOPER_BADGE_ELECTRIC, 'Developer'))->toContain('pkt-fx-lightning')
        ->and(DeveloperBadgeRenderer::render(User::DEVELOPER_BADGE_FLAME, 'Developer'))->toContain('pkt-fx-flames')
        ->and(DeveloperBadgeRenderer::render(User::DEVELOPER_BADGE_ARCTIC_PLATINUM, 'Developer'))->toContain('pkt-fx-frost')
        ->and(DeveloperBadgeRenderer::render(User::DEVELOPER_BADGE_EMERALD_SOVEREIGN, 'Developer'))->toContain('pkt-fx-sonar')
        ->and(DeveloperBadgeRenderer::render(User::DEVELOPER_BADGE_CRIMSON_ECLIPSE, 'Developer'))->toContain('pkt-fx-glitch-bars')
        ->and(DeveloperBadgeRenderer::render(User::DEVELOPER_BADGE_AMETHYST_CROWN, 'Developer'))->toContain('pkt-fx-warp')
        ->and(DeveloperBadgeRenderer::render(User::DEVELOPER_BADGE_ROSE_TITANIUM, 'Developer'))->toContain('pkt-fx-petals');
});

test('BadgeMilestoneService resolves role-specific mission tracks accurately', function (): void {
    $service = app(BadgeMilestoneService::class);

    $operator = User::query()->create(['name' => 'Op 1', 'email' => 'op1@pkt.test', 'password' => bcrypt('password')]);
    $operator->assignRole('Operator');

    $adminChem = User::query()->create(['name' => 'Chem 1', 'email' => 'chem1@pkt.test', 'password' => bcrypt('password')]);
    $adminChem->assignRole('Admin Chemical');

    $adminSpare = User::query()->create(['name' => 'Spare 1', 'email' => 'spare1@pkt.test', 'password' => bcrypt('password')]);
    $adminSpare->assignRole('Admin Sparepart');

    $adminBb = User::query()->create(['name' => 'BB 1', 'email' => 'bb1@pkt.test', 'password' => bcrypt('password')]);
    $adminBb->assignRole('Admin Bahan Baku');

    $staffInv = User::query()->create(['name' => 'Inv 1', 'email' => 'inv1@pkt.test', 'password' => bcrypt('password')]);
    $staffInv->assignRole('Staff Invoice');

    expect($service->resolveMissionProfile($operator)['track'])->toBe(BadgeMilestoneService::TRACK_OPERATOR_DO)
        ->and($service->resolveMissionProfile($operator)['unit_label'])->toBe('Penerimaan DO')
        ->and($service->resolveMissionProfile($adminChem)['track'])->toBe(BadgeMilestoneService::TRACK_ADMIN_DO_GRS)
        ->and($service->resolveMissionProfile($adminChem)['unit_label'])->toBe('DO & GRS/RDTV')
        ->and($service->resolveMissionProfile($adminSpare)['track'])->toBe(BadgeMilestoneService::TRACK_ADMIN_DO_GRS)
        ->and($service->resolveMissionProfile($adminBb)['track'])->toBe(BadgeMilestoneService::TRACK_ADMIN_DO_GRS)
        ->and($service->resolveMissionProfile($staffInv)['track'])->toBe(BadgeMilestoneService::TRACK_STAFF_INVOICE)
        ->and($service->resolveMissionProfile($staffInv)['unit_label'])->toBe('Invoice Ditangani');
});
