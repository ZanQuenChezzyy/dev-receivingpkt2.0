<?php

use App\Filament\Resources\CalendarNotes\CalendarNoteResource;
use App\Filament\Resources\CalendarNotes\Pages\ManageCalendarNotes;
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
    Cache::forget('pkt_id_holidays_v1_2027');
});

test('IndonesianHolidayService parses primary API, groups consecutive dates, and falls back when offline', function (): void {
    Http::fake([
        IndonesianHolidayService::PRIMARY_FEED_URL => Http::response([
            '2027-03-10' => [
                'holiday' => true,
                'summary' => ['Hari Idul Fitri'],
                'description' => ['Hari libur nasional'],
            ],
            '2027-03-11' => [
                'holiday' => true,
                'summary' => ['Hari Idul Fitri'],
                'description' => ['Hari libur nasional'],
            ],
            '2027-03-08' => [
                'holiday' => true,
                'summary' => ['Hari Suci Nyepi'],
                'description' => ['Hari libur nasional'],
            ],
        ], 200),
    ]);

    $service = app(IndonesianHolidayService::class);
    $marHolidays = $service->getHolidaysForMonth(2027, 3);
    $grouped = $service->groupConsecutiveHolidays($marHolidays);

    expect($marHolidays)->toHaveKeys(['2027-03-08', '2027-03-10', '2027-03-11'])
        ->and($grouped)->toHaveCount(2)
        ->and($grouped[0]['range_label'])->toBe('8 Mar')
        ->and($grouped[1]['range_label'])->toBe('10–11 Mar')
        ->and($grouped[1]['name'])->toBe('Hari Idul Fitri');

    // Test offline fallback
    Cache::forget('pkt_id_holidays_v1_2028');
    Http::fake(['*' => Http::response(null, 500)]);

    $fallback2028 = $service->getHolidaysForMonth(2028, 8);
    expect($fallback2028)->toHaveKey('2028-08-17')
        ->and($fallback2028['2028-08-17']['name'])->toBe('Hari Kemerdekaan Republik Indonesia');
});

test('Sekretaris manages calendar notes via native Filament modal actions and Data Master resource while Operator is read-only', function (): void {
    Http::fake([
        IndonesianHolidayService::PRIMARY_FEED_URL => Http::response([
            '2026-12-24' => [
                'holiday' => true,
                'summary' => ['Cuti Bersama Natal'],
                'description' => ['Cuti bersama'],
            ],
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

    // 1. Data Master navigation group & access rules
    expect(CalendarNoteResource::getNavigationGroup())->toBe('Data Master');

    Auth::login($sekretaris);
    expect(CalendarNoteResource::canAccess())->toBeTrue();

    // 2. Sekretaris creates multi-day note via Data Master -> Catatan Kalender page
    Livewire::test(ManageCalendarNotes::class)
        ->callAction('create', data: [
            'user_id' => $targetUser->id,
            'category' => CalendarNote::CATEGORY_DINAS,
            'title' => 'Dinas mutasi bahan baku',
            'start_date' => '2026-12-02',
            'end_date' => '2026-12-07',
            'notes' => 'Kunjungan koordinasi mutasi bahan baku',
        ])
        ->assertHasNoActionErrors()
        ->assertSee('Dinas mutasi bahan baku')
        ->assertSee('Andereyan Muhammat');

    $saved = CalendarNote::query()->first();
    expect($saved)->not->toBeNull()
        ->and($saved->user_id)->toBe($targetUser->id)
        ->and($saved->created_by)->toBe($sekretaris->id)
        ->and($saved->formattedDateRange())->toBe('2–7 Des');

    // 3. DashboardCalendarClockWidget displays notes & holidays without a Tambah Catatan button
    Livewire::test(DashboardCalendarClockWidget::class)
        ->set('viewYear', 2026)
        ->set('viewMonth', 12)
        ->assertActionDoesNotExist('createNote')
        ->assertSee('2–7 Des')
        ->assertSee('Andereyan Muhammat:')
        ->assertSee('Dinas mutasi bahan baku')
        ->assertSee('Hari Raya Natal')
        ->assertSee('Cuti Bersama Natal')
        ->assertSeeHtml('bg-amber-500/14')
        ->assertSeeHtml('bg-rose-500/10');

    // 4. Operator is read-only on widget and denied access to Data Master CalendarNoteResource
    Auth::login($operator);
    expect(CalendarNoteResource::canAccess())->toBeFalse();

    Livewire::test(DashboardCalendarClockWidget::class)
        ->set('viewYear', 2026)
        ->set('viewMonth', 12)
        ->assertActionDoesNotExist('createNote')
        ->assertSee('Andereyan Muhammat:')
        ->assertSee('Dinas mutasi bahan baku')
        ->assertSee('Hari Raya Natal')
        ->call('setAgendaTab', 'holidays')
        ->assertDontSee('2–7 Des')
        ->assertSee('25 Des')
        ->call('setAgendaTab', 'notes')
        ->assertSee('2–7 Des')
        ->assertDontSee('25 Des');

    // 5. Sekretaris edits and deletes note via native Filament modal actions
    Auth::login($sekretaris);
    Livewire::test(DashboardCalendarClockWidget::class)
        ->set('viewYear', 2026)
        ->set('viewMonth', 12)
        ->callAction('editNote', data: [
            'user_id' => $targetUser->id,
            'category' => CalendarNote::CATEGORY_DINAS,
            'title' => 'Dinas mutasi bahan baku (Final)',
            'start_date' => '2026-12-02',
            'end_date' => '2026-12-07',
            'notes' => 'Diperbarui',
        ], arguments: ['noteId' => $saved->id])
        ->assertHasNoActionErrors()
        ->assertSee('Dinas mutasi bahan baku (Final)')
        ->callAction('deleteNote', arguments: ['noteId' => $saved->id]);

    expect(CalendarNote::query()->count())->toBe(0);
});
