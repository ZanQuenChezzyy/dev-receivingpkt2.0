<?php

use App\Filament\Pages\Login;
use App\Models\User;
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
            $table->string('developer_badge_style', 30)->nullable()->default('none');
            $table->boolean('has_exclusive_badge')->nullable()->default(null);
            $table->json('unlocked_exclusive_badges')->nullable();
            $table->json('badge_milestone_notified')->nullable();
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
    Role::firstOrCreate(['name' => 'Operator', 'guard_name' => 'web']);
});

test('successful login sets post-login curtain session flag and renders curtain once', function (): void {
    $user = User::query()->create([
        'name' => 'Andereyan Curtain',
        'npk' => '2100777',
        'email' => 'curtain.user@pupukkaltim.com',
        'password' => bcrypt('password123'),
        'developer_badge_style' => User::DEVELOPER_BADGE_CRIMSON_ECLIPSE,
        'is_active' => true,
    ]);
    $user->assignRole('Developer');

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'curtain.user@pupukkaltim.com',
            'password' => 'password123',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    expect(Auth::id())->toBe($user->id)
        ->and(session()->has('pkt_login_curtain'))->toBeTrue()
        ->and(session('pkt_login_curtain.token'))->not->toBeEmpty();

    // First authenticated render shows the loading curtain with user name, role, and mythic avatar, and consumes the session flag
    $firstRender = view('filament.components.login-curtain')->render();
    expect($firstRender)->toContain('id="pkt-login-curtain"')
        ->and($firstRender)->toContain('Andereyan Curtain')
        ->and($firstRender)->toContain('Menyiapkan ruang kerja Receiving PKT...')
        ->and($firstRender)->toContain('pkt-mythic-avatar--crimson_eclipse')
        ->and(session()->has('pkt_login_curtain'))->toBeFalse();

    // Subsequent page render does NOT replay the curtain
    $secondRender = trim(view('filament.components.login-curtain')->render());
    expect($secondRender)->toBe('');
});

test('failed login does not set post-login curtain session flag', function (): void {
    $user = User::query()->create([
        'name' => 'Inactive User',
        'npk' => '2100778',
        'email' => 'inactive.curtain@pupukkaltim.com',
        'password' => bcrypt('password123'),
        'is_active' => false,
    ]);
    $user->assignRole('Operator');

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'inactive.curtain@pupukkaltim.com',
            'password' => 'password123',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    expect(Auth::check())->toBeFalse()
        ->and(session()->has('pkt_login_curtain'))->toBeFalse();
});
