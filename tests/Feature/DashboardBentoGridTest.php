<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\DashboardCalendarClockWidget;
use App\Filament\Widgets\DashboardNonStockAgingWidget;
use App\Filament\Widgets\DashboardWelcomeBentoWidget;
use App\Filament\Widgets\MaterialItemOverview;
use App\Filament\Widgets\MaterialValueOverview;
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
});

test('Dashboard configures 12-column Bento Grid with Welcome, Calendar, and all existing widgets', function (): void {
    $dashboard = new Dashboard;

    expect($dashboard->getColumns())->toBe([
        'default' => 1,
        'lg' => 12,
    ])
        ->and($dashboard->getWidgets())->toBe([
            DashboardWelcomeBentoWidget::class,
            DashboardCalendarClockWidget::class,
            MaterialValueOverview::class,
            MaterialItemOverview::class,
            DashboardNonStockAgingWidget::class,
        ]);
});

test('DashboardWelcomeBentoWidget and DashboardCalendarClockWidget render greeting, weather, shortcuts, and Indonesian calendar clock', function (): void {
    $user = User::query()->create([
        'name' => 'Andereyan Bento',
        'npk' => '2100555',
        'email' => 'bento@pupukkaltim.com',
        'password' => bcrypt('password'),
        'developer_badge_style' => User::DEVELOPER_BADGE_PKT_COMBO,
        'is_active' => true,
    ]);
    $user->assignRole('Developer');

    Auth::login($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(DashboardWelcomeBentoWidget::class)
        ->assertSee('Andereyan Bento')
        ->assertSee('Ringkasan Operasional')
        ->assertSee('Penerimaan DO')
        ->assertSee('GRS & RDTV')
        ->assertSee('Invoice Barang')
        ->assertSee('Pintasan Cepat')
        ->assertSee('Atur Pintasan')
        ->assertSee('Prakiraan Cuaca Area PKT')
        ->assertDontSeeHtml('pkt-mythic-avatar--pkt_combo')
        ->assertDontSeeHtml('pkt-dev-badge--pkt_combo');

    Livewire::test(DashboardCalendarClockWidget::class)
        ->assertSee('Kalender & Waktu')
        ->assertSee('WITA')
        ->assertSee('Sen')
        ->assertSee('Min');
});
