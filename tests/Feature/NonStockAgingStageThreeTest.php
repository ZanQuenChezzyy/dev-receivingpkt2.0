<?php

use App\Enums\AgingFollowupStage;
use App\Enums\AgingOperationalStatus;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\NonStockAgingMonitoring;
use App\Filament\Widgets\DashboardNonStockAgingWidget;
use App\Filament\Widgets\NonStockAgingOverviewWidget;
use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\MaterialIssue;
use App\Models\MaterialIssueDetail;
use App\Models\NonStockAgingControl;
use App\Models\NonStockAgingLog;
use App\Models\RequestingUnit;
use App\Models\Setting;
use App\Models\User;
use App\Services\EmailTemplateRenderer;
use App\Services\NonStockAgingMailDispatcher;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Cache::flush();

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

    Role::firstOrCreate(['name' => 'Developer', 'guard_name' => 'web']);

    if (! Schema::hasTable('settings')) {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
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

    if (! Schema::hasTable('purchase_order_issueds')) {
        Schema::create('purchase_order_issueds', function (Blueprint $table): void {
            $table->id();
            $table->string('purchase_order_and_item')->nullable();
            $table->string('material_type')->nullable();
            $table->string('mrp_type')->nullable();
            $table->string('purchase_order_no')->nullable();
            $table->integer('item_no')->default(10);
            $table->string('material_code')->nullable();
            $table->string('description')->nullable();
            $table->decimal('qty_po', 15, 2)->default(1);
            $table->string('uoi')->default('EA');
            $table->string('vendor_name')->nullable();
            $table->string('currency')->default('IDR');
            $table->decimal('net_price', 18, 2)->default(0);
            $table->decimal('total_amount_in_lc', 18, 2)->default(0);
            $table->string('requisitioner')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('location_receivings')) {
        Schema::create('location_receivings', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipts')) {
        Schema::create('delivery_order_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('delivery_order_no')->nullable();
            $table->date('received_date')->nullable();
            $table->date('physical_received_date')->nullable();
            $table->string('status')->nullable();
            $table->string('stage')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipt_details')) {
        Schema::create('delivery_order_receipt_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_id');
            $table->unsignedBigInteger('purchase_order_issued_id')->nullable();
            $table->integer('item_no')->default(10);
            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('material_code')->nullable();
            $table->string('description')->nullable();
            $table->string('uoi')->default('EA');
            $table->string('mrp_type')->nullable();
            $table->string('material_type')->nullable();
            $table->string('requisitioner')->nullable();
            $table->decimal('total_amount_snapshot', 18, 2)->default(0);
            $table->unsignedBigInteger('location_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('grs_rdtvs')) {
        Schema::create('grs_rdtvs', function (Blueprint $table): void {
            $table->id();
            $table->date('transaction_date')->nullable();
            $table->string('category')->default('GRS');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('grs_rdtv_items')) {
        Schema::create('grs_rdtv_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('grs_rdtv_id');
            $table->unsignedBigInteger('delivery_order_receipt_id');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('material_issues')) {
        Schema::create('material_issues', function (Blueprint $table): void {
            $table->id();
            $table->string('jenis_mir')->default('digital');
            $table->string('mir_number')->nullable();
            $table->date('tanggal')->nullable();
            $table->string('departemen')->nullable();
            $table->string('diminta_oleh')->nullable();
            $table->string('no_hp')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('material_issue_details')) {
        Schema::create('material_issue_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('material_issue_id');
            $table->unsignedBigInteger('delivery_order_receipt_detail_id');
            $table->decimal('diminta', 15, 2)->default(0);
            $table->decimal('diserahkan', 15, 2)->default(0);
            $table->decimal('boh', 15, 2)->default(0);
            $table->string('stage_when_issued')->nullable();
            $table->timestamps();
        });
    }

    foreach ([
        '2026_11_23_214628_create_requesting_units_table.php',
        '2026_11_23_214629_create_nonstock_aging_controls_table.php',
        '2026_11_23_214629_create_nonstock_aging_logs_table.php',
        '2026_11_23_214630_create_nonstock_aging_snapshots_table.php',
    ] as $file) {
        $migration = include database_path('migrations/'.$file);
        $migration->down();
        $migration->up();
    }

    config()->set('services.zimbra.base_url', 'https://mailpkt.pupukkaltim.com');
    config()->set('services.zimbra.username', 'receiving.jasa@pupukkaltim.com');
    config()->set('services.zimbra.password', 'secret-pass');
    config()->set('services.zimbra.test_override_to', '');
});

function fakeZimbraSoap(): void
{
    Http::fake([
        'https://mailpkt.pupukkaltim.com/service/soap*' => function (HttpClientRequest $request) {
            $payload = $request->data();
            if (isset($payload['Body']['AuthRequest'])) {
                return Http::response([
                    'Body' => [
                        'AuthResponse' => [
                            'authToken' => [['_content' => 'fake-zimbra-token']],
                        ],
                    ],
                ], 200);
            }

            return Http::response([
                'Body' => [
                    'SendMsgResponse' => [
                        'm' => [['id' => 'zimbra-msg-123']],
                    ],
                ],
            ], 200);
        },
    ]);
}

test('mail dispatcher sends manual PIC, AVP, and VP escalations via Zimbra and honors test override safety mode', function (): void {
    fakeZimbraSoap();

    $unit = RequestingUnit::factory()->pilot()->create([
        'name' => 'Rendal TA P5',
        'pic_name' => 'Ibnu Sina',
        'pic_email' => 'ibnu@pupukkaltim.com',
        'pic_phone' => '085247376525',
        'avp_name' => 'AVP Rendal',
        'avp_email' => 'avp.rendal@pupukkaltim.com',
        'vp_name' => 'VP Pemeliharaan',
        'vp_email' => 'vp.pemeliharaan@pupukkaltim.com',
    ]);

    $control = NonStockAgingControl::factory()->agingDays(75)->create([
        'po_by_item' => '5300058020-10',
        'description_snapshot' => 'BEARING PUMP P5',
        'requesting_unit_id' => $unit->id,
        'initial_value_lc' => 176_000_000,
    ]);

    $dispatcher = app(NonStockAgingMailDispatcher::class);

    // 1. WhatsApp template & URL
    $waMsg = $dispatcher->buildWhatsAppMessage(new Collection([$control]), 'PIC');
    $waUrl = $dispatcher->buildWhatsAppUrl($unit->pic_phone, $waMsg);
    expect($waMsg)->toContain('5300058020-10')
        ->and($waMsg)->toContain('Rp 176.000.000')
        ->and($waUrl)->toStartWith('https://wa.me/6285247376525?text=');

    // 2. PIC Reminder (Normal mode)
    $picRes = $dispatcher->sendPicReminder(new Collection([$control]), 'weekly_digest', 'Mohon segera diambil.');
    expect($picRes['status'])->toBe('sent')
        ->and($picRes['test_mode'])->toBeFalse()
        ->and($control->fresh()->reminder_count)->toBe(1);

    // 3. AVP Warning Confirmation (CC PIC)
    $avpRes = $dispatcher->sendAvpWarningConfirmation(new Collection([$control]), 'Mohon komitmen tanggal GI.');
    expect($avpRes['status'])->toBe('sent')
        ->and($control->fresh()->avp_escalated_at)->not->toBeNull()
        ->and($control->fresh()->followup_stage)->toBe(AgingFollowupStage::KonfirmasiAvp);

    // 4. VP Critical Escalation dengan Mode Uji Coba aktif -> dialihkan & CC asli ditahan
    Setting::query()->updateOrCreate(['key' => EmailTemplateRenderer::KEY_TEST_MODE_ENABLED], ['value' => '1']);
    Setting::query()->updateOrCreate(['key' => EmailTemplateRenderer::KEY_TEST_MODE_EMAIL], ['value' => 'tester@pupukkaltim.com']);

    $vpRes = $dispatcher->sendVpCriticalEscalation(new Collection([$control]), 'Eskalasi >60 hari.');
    expect($vpRes['status'])->toBe('sent')
        ->and($vpRes['test_mode'])->toBeTrue()
        ->and($vpRes['message'])->toContain('tester@pupukkaltim.com')
        ->and($control->fresh()->vp_escalated_at)->not->toBeNull()
        ->and($control->fresh()->followup_stage)->toBe(AgingFollowupStage::EskalasiVp)
        ->and(NonStockAgingLog::query()->where('nonstock_aging_control_id', $control->id)->count())->toBe(3);

    // Pastikan request VP terakhir benar-benar hanya dikirim ke tester@pupukkaltim.com dengan prefix [UJI COBA]
    Http::assertSent(function (HttpClientRequest $req): bool {
        $m = $req->data()['Body']['SendMsgRequest']['m'] ?? null;
        if (! is_array($m)) {
            return false;
        }
        $subject = (string) ($m['su']['_content'] ?? '');
        $emails = $m['e'] ?? [];
        $toAddrs = array_values(array_map(fn (array $e): string => (string) $e['a'], array_filter($emails, fn (array $e): bool => ($e['t'] ?? '') === 't')));
        $ccAddrs = array_values(array_filter($emails, fn (array $e): bool => ($e['t'] ?? '') === 'c'));

        return str_starts_with($subject, '[UJI COBA]') && $toAddrs === ['tester@pupukkaltim.com'] && $ccAddrs === [];
    });
});

test('MaterialIssueDetail saved hook automatically closes NonStockAgingControl when MIR fulfills received quantity', function (): void {
    $do = DeliveryOrderReceipt::query()->create([
        'delivery_order_no' => 'DO-MIR-01',
        'received_date' => '2026-10-01',
        'status' => 'GRS',
    ]);

    $detail = DeliveryOrderReceiptDetail::query()->create([
        'delivery_order_receipt_id' => $do->id,
        'item_no' => 10,
        'quantity' => 5,
        'description' => 'GASKET KIT',
        'mrp_type' => 'NONSTOCK',
        'total_amount_snapshot' => 25_000_000,
    ]);

    $control = NonStockAgingControl::factory()->create([
        'delivery_order_receipt_detail_id' => $detail->id,
        'po_by_item' => '5300090001-10',
        'initial_qty_gr' => 5,
        'initial_value_lc' => 25_000_000,
        'operational_status' => AgingOperationalStatus::BelumDiambil,
    ]);

    $mir = MaterialIssue::query()->create([
        'jenis_mir' => 'digital',
        'mir_number' => 'MIR/202611/0010',
        'tanggal' => '2026-11-20',
    ]);

    // Pengambilan parsial (2 dari 5) -> tetap BelumDiambil
    MaterialIssueDetail::query()->create([
        'material_issue_id' => $mir->id,
        'delivery_order_receipt_detail_id' => $detail->id,
        'diminta' => 2,
        'diserahkan' => 2,
        'boh' => 3,
    ]);

    expect($control->fresh()->operational_status)->toBe(AgingOperationalStatus::BelumDiambil);

    // Pengambilan sisa (3 dari 5) -> otomatis SelesaiDiambil + log auto_closed_mir
    MaterialIssueDetail::query()->create([
        'material_issue_id' => $mir->id,
        'delivery_order_receipt_detail_id' => $detail->id,
        'diminta' => 3,
        'diserahkan' => 3,
        'boh' => 0,
    ]);

    expect($control->fresh()->operational_status)->toBe(AgingOperationalStatus::SelesaiDiambil)
        ->and($control->fresh()->followup_stage)->toBe(AgingFollowupStage::Selesai)
        ->and(NonStockAgingLog::query()->where('nonstock_aging_control_id', $control->id)->where('action_type', 'auto_closed_mir')->exists())->toBeTrue();
});

test('daily check-aging-alerts command sends In-App notifications only and never sends automatic emails', function (): void {
    Http::fake();

    $admin = User::query()->create([
        'name' => 'Admin Receiving',
        'npk' => '2100002',
        'email' => 'admin@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);

    // Critical >60H belum eskalasi VP + overdue planned pickup
    NonStockAgingControl::factory()->agingDays(70)->create([
        'operational_status' => AgingOperationalStatus::BelumDiambil,
        'vp_escalated_at' => null,
        'planned_pickup_date' => now()->subDays(2)->toDateString(),
    ]);

    $this->artisan('nonstock:check-aging-alerts', ['--skip-sync' => true])
        ->assertSuccessful();

    expect(DB::table('notifications')->where('notifiable_id', $admin->id)->count())->toBe(1);
    Http::assertNothingSent();
});

test('Dashboard widget, Overview widget, and NonStockAgingMonitoring page render and execute operational actions', function (): void {
    fakeZimbraSoap();

    $user = User::query()->create([
        'name' => 'AVP Receiving',
        'npk' => '2100003',
        'email' => 'avp.receiving@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->assignRole('Developer');

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $unit = RequestingUnit::factory()->pilot()->create([
        'name' => 'Rendal TA P5',
        'pic_name' => 'Ibnu Sina',
        'pic_email' => 'ibnu@pupukkaltim.com',
        'avp_email' => 'avp.p5@pupukkaltim.com',
        'vp_email' => 'vp.p5@pupukkaltim.com',
    ]);

    $criticalItem = NonStockAgingControl::factory()->agingDays(75)->create([
        'po_by_item' => '5300055468-10',
        'mrp_type_snapshot' => 'NONSTOCK',
        'description_snapshot' => 'CONTROL VALVE CRITICAL',
        'requesting_unit_id' => $unit->id,
        'initial_value_lc' => 252_480_000,
    ]);

    $onCheckItem = NonStockAgingControl::factory()->onCheck()->agingDays(10)->create([
        'po_by_item' => '5300061102-10',
        'mrp_type_snapshot' => 'NONSTOCK',
        'description_snapshot' => 'LAB ITEM ON CHECK',
        'initial_value_lc' => 48_500_000,
    ]);

    // 1. Dashboard widget terdaftar di Halaman Utama & merender dengan benar
    $dashboard = new Dashboard;
    expect($dashboard->getWidgets())->toContain(DashboardNonStockAgingWidget::class);

    Livewire::test(DashboardNonStockAgingWidget::class)
        ->assertSee('Non-Stock Belum Diambil')
        ->assertSee('Zona Critical (> 60 Hari)')
        ->assertSee('Status On Check');

    // 2. Overview widget merender KPI, 4 Zona & Pareto
    Livewire::test(NonStockAgingOverviewWidget::class, ['mrpScope' => 'NONSTOCK'])
        ->assertSee('Analisa Pareto 80/20')
        ->assertSee('Rendal TA P5');

    // 3. Halaman NonStockAgingMonitoring merender tabel & aksi operasional
    Livewire::test(NonStockAgingMonitoring::class)
        ->assertCanSeeTableRecords([$criticalItem])
        ->assertCanNotSeeTableRecords([$onCheckItem])
        ->callTableAction('avpCommitmentAndHold', $criticalItem, [
            'planned_pickup_date' => now()->addDays(10)->toDateString(),
            'delay_reason_category' => 'Menunggu Jadwal TA/Maintenance',
            'scheduled_hold_reason' => 'Dijadwalkan ambil pada window TA P5',
            'set_scheduled_hold' => true,
            'send_avp_email' => true,
        ])
        ->assertHasNoTableActionErrors();

    expect($criticalItem->fresh()->operational_status)->toBe(AgingOperationalStatus::ScheduledHold)
        ->and($criticalItem->fresh()->avp_escalated_at)->not->toBeNull();

    // 4. Validasi fisik pada item On Check -> berubah menjadi Belum Diambil
    Livewire::test(NonStockAgingMonitoring::class, ['activeTab' => 'on_check'])
        ->assertCanSeeTableRecords([$onCheckItem])
        ->callTableAction('validatePhysical', $onCheckItem, [
            'location_text' => 'FLR-C',
            'requesting_unit_id' => $unit->id,
            'mark_ready' => true,
            'warehouse_validation_notes' => 'Fisik lengkap di FLR-C',
        ])
        ->assertHasNoTableActionErrors();

    expect($onCheckItem->fresh()->operational_status)->toBe(AgingOperationalStatus::BelumDiambil)
        ->and($onCheckItem->fresh()->physical_checked)->toBeTrue()
        ->and($onCheckItem->fresh()->location_text)->toBe('FLR-C');

    // 5. Header action Kirim Rekap Mingguan Manual
    Livewire::test(NonStockAgingMonitoring::class)
        ->callAction('sendWeeklyDigestManual', [
            'requesting_unit_id' => $unit->id,
            'additional_note' => 'Rekap mingguan Senin pagi.',
        ])
        ->assertHasNoActionErrors();

    expect(NonStockAgingLog::query()->where('action_type', 'weekly_digest')->count())->toBe(2);
});
