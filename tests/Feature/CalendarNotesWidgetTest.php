<?php

use App\Filament\Widgets\DashboardCalendarClockWidget;
use App\Models\CalendarNote;
use App\Models\User;
use App\Services\IndonesianHolidayService;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

    if (! Schema::hasTable('calendar_notes')) {
        Schema::create('calendar_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 140);
            $table->string('category', 30)->default('dinas');
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('permissions')) {
        $permissionMigration = include database_path('migrations/2026_05_15_101350_create_permission_tables.php');
        $permissionMigration->up();
    }

    Role::firstOrCreate(['name' => 'Sekretaris', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Developer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Operator', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Staff Invoice', 'guard_name' => 'web']);

    Cache::forget('pkt_id_holidays_v1_2026');
});

test('IndonesianHolidayService parses primary API feed and falls back cleanly when APIs fail', function (): void {
    Http::fake([
        IndonesianHolidayService::PRIMARY_FEED_URL => Http::response([
            '2026-12-25' => [
                'holiday' => true,
                'summary' => ['Hari Raya Natal'],
                'description' => ['Hari libur nasional'],
            ],
            '2026-12-24' => [
                'holiday' => true,
                'summary' => ['Cuti Bersama Natal'],
                'description' => ['Cuti bersama'],
            ],
        ], 200),
    ]);

    $service = app(IndonesianHolidayService::class);
    $decHolidays = $service->getHolidaysForMonth(2026, 12);

    expect($decHolidays)->toHaveKeys(['2026-12-24', '2026-12-25'])
        ->and($decHolidays['2026-12-25']['name'])->toBe('Hari Raya Natal')
        ->and($decHolidays['2026-12-25']['is_joint_leave'])->toBeFalse()
        ->and($decHolidays['2026-12-24']['is_joint_leave'])->toBeTrue();

    // Test offline fallback
    Cache::forget('pkt_id_holidays_v1_2027');
    Http::fake(['*' => Http::response(null, 500)]);

    $fallback2027 = $service->getHolidaysForMonth(2027, 8);
    expect($fallback2027)->toHaveKey('2027-08-17')
        ->and($fallback2027['2027-08-17']['name'])->toBe('Hari Kemerdekaan Republik Indonesia');
});

test('Sekretaris can add, edit, and delete multi-day duty notes visible to all users while Operator is read-only', function (): void {
    Http::fake([
        IndonesianHolidayService::PRIMARY_FEED_URL => Http::response([
            '2026-12-25' => [
                'holiday' => true,
                'summary' => ['Hari Raya Natal'],
                'description' => ['Hari libur nasional'],
            ],
        ], 200),
    ]);

    $sekretaris = User::query()->create([
        'name' => 'Nur Sekretaris',
        'npk' => '2100601',
        'email' => 'sekretaris.cal@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $sekretaris->assignRole('Sekretaris');

    $targetUser = User::query()->create([
        'name' => 'Andereyan Muhammat',
        'npk' => '2100602',
        'email' => 'andereyan.cal@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $targetUser->assignRole('Staff Invoice');

    $operator = User::query()->create([
        'name' => 'Budi Operator',
        'npk' => '2100603',
        'email' => 'operator.cal@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $operator->assignRole('Operator');

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    // 1. Sekretaris menambahkan catatan dinas 2-7 Desember 2026
    Auth::login($sekretaris);
    Livewire::test(DashboardCalendarClockWidget::class)
        ->assertSee('Catatan')
        ->call('openNoteModal')
        ->set('noteUserId', $targetUser->id)
        ->set('noteCategory', CalendarNote::CATEGORY_DINAS)
        ->set('noteTitle', 'Dinas mutasi bahan baku')
        ->set('noteStartDate', '2026-12-02')
        ->set('noteEndDate', '2026-12-07')
        ->set('noteDescription', 'Kunjungan koordinasi mutasi bahan baku')
        ->call('saveNote')
        ->assertHasNoErrors()
        ->assertSee('2–7 Des')
        ->assertSee('Andereyan Muhammat:')
        ->assertSee('Dinas mutasi bahan baku')
        ->assertSee('Hari Raya Natal');

    $saved = CalendarNote::query()->first();
    expect($saved)->not->toBeNull()
        ->and($saved->user_id)->toBe($targetUser->id)
        ->and($saved->created_by)->toBe($sekretaris->id)
        ->and($saved->formattedDateRange())->toBe('2–7 Des');

    // 2. Operator bisa melihat catatan & hari libur, tapi tidak boleh menambah atau menghapus catatan
    Auth::login($operator);
    Livewire::test(DashboardCalendarClockWidget::class)
        ->set('viewYear', 2026)
        ->set('viewMonth', 12)
        ->assertDontSeeHtml('wire:click="openNoteModal()"')
        ->assertSee('Andereyan Muhammat:')
        ->assertSee('Dinas mutasi bahan baku')
        ->assertSee('Hari Raya Natal')
        ->call('selectDate', '2026-12-04')
        ->assertSee('Dinas mutasi bahan baku')
        ->set('noteTitle', 'Coba curi akses')
        ->set('noteStartDate', '2026-12-10')
        ->set('noteEndDate', '2026-12-10')
        ->call('saveNote')
        ->call('deleteNote', $saved->id);

    expect(CalendarNote::query()->count())->toBe(1);

    // 3. Sekretaris dapat mengubah dan menghapus catatan
    Auth::login($sekretaris);
    Livewire::test(DashboardCalendarClockWidget::class)
        ->set('viewYear', 2026)
        ->set('viewMonth', 12)
        ->call('editNote', $saved->id)
        ->set('noteTitle', 'Dinas mutasi bahan baku (Diperbarui)')
        ->call('saveNote')
        ->assertSee('Dinas mutasi bahan baku (Diperbarui)')
        ->call('deleteNote', $saved->id);

    expect(CalendarNote::query()->count())->toBe(0);
});
