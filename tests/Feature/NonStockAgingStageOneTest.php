<?php

use App\Enums\AgingFollowupStage;
use App\Enums\AgingOperationalStatus;
use App\Enums\AgingZone;
use App\Filament\Resources\RequestingUnits\Pages\CreateRequestingUnit;
use App\Filament\Resources\RequestingUnits\Pages\EditRequestingUnit;
use App\Filament\Resources\RequestingUnits\Pages\ListRequestingUnits;
use App\Filament\Resources\RequestingUnits\RequestingUnitResource;
use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\GrsRdtv;
use App\Models\GrsRdtvItem;
use App\Models\LocationReceiving;
use App\Models\MaterialIssue;
use App\Models\MaterialIssueDetail;
use App\Models\NonStockAgingControl;
use App\Models\NonStockAgingLog;
use App\Models\NonStockAgingSnapshot;
use App\Models\PurchaseOrderIssued;
use App\Models\RequestingUnit;
use App\Models\User;
use App\Services\NonStockAgingService;
use Database\Seeders\RequestingUnitSeeder;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
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

    Role::firstOrCreate(['name' => 'Developer', 'guard_name' => 'web']);

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
            $table->unsignedBigInteger('created_by')->nullable();
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
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('grs_rdtv_items')) {
        Schema::create('grs_rdtv_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('grs_rdtv_id');
            $table->unsignedBigInteger('delivery_order_receipt_id');
            $table->string('document_code')->nullable();
            $table->string('status')->nullable();
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
            $table->string('bagian')->nullable();
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

    // Jalankan 4 migrasi baru Tahap 1 secara nyata
    $migrations = [
        '2026_11_23_214628_create_requesting_units_table.php',
        '2026_11_23_214629_create_nonstock_aging_controls_table.php',
        '2026_11_23_214629_create_nonstock_aging_logs_table.php',
        '2026_11_23_214630_create_nonstock_aging_snapshots_table.php',
    ];

    foreach ($migrations as $file) {
        $migration = include database_path('migrations/'.$file);
        $migration->down();
        $migration->up();
    }
});

test('aging zone enum classifies LEAD 2026 thresholds accurately', function (): void {
    expect(AgingZone::fromDays(0))->toBe(AgingZone::Normal)
        ->and(AgingZone::fromDays(14))->toBe(AgingZone::Normal)
        ->and(AgingZone::fromDays(15))->toBe(AgingZone::Attention)
        ->and(AgingZone::fromDays(30))->toBe(AgingZone::Attention)
        ->and(AgingZone::fromDays(31))->toBe(AgingZone::Warning)
        ->and(AgingZone::fromDays(60))->toBe(AgingZone::Warning)
        ->and(AgingZone::fromDays(61))->toBe(AgingZone::Critical)
        ->and(AgingZone::fromDays(120))->toBe(AgingZone::Critical);
});

test('service normalizes po keys, whatsapp numbers, contact parsing, and longest-alias unit matching', function (): void {
    $service = app(NonStockAgingService::class);

    expect($service->normalizePoByItem('5300057304', 10))->toBe('5300057304-10')
        ->and($service->normalizePoByItem('5.300057304E9', '20.0'))->toBe('5300057304-20')
        ->and($service->normalizePoByItem(null, null, '5300057304 - 030'))->toBe('5300057304-30')
        ->and($service->normalizePhoneToWa('0852-4737-6525'))->toBe('6285247376525')
        ->and($service->normalizePhoneToWa('+62 812 5888 9131'))->toBe('6281258889131')
        ->and($service->extractNameAndPhone('Ibnu Sina Al Barry 085247376525'))->toBe([
            'name' => 'Ibnu Sina Al Barry',
            'phone' => '085247376525',
        ]);

    $this->seed(RequestingUnitSeeder::class);

    expect(RequestingUnit::query()->count())->toBe(8)
        ->and(RequestingUnit::query()->where('is_pilot', true)->count())->toBe(4);

    // Alias lebih spesifik "RENDAL-TAP5" harus cocok ke "Rendal TA P5", bukan "Rendal TA (Area Lain / All Area)"
    $matchedP5 = $service->matchRequestingUnit('RENDAL-TAP5 MR NO : 8120/MR/X/2025');
    $matchedLab = $service->matchRequestingUnit('LAB KIMIA PABRIK');
    $matchedAreaLain = $service->matchRequestingUnit('RENDAL-TAP3 MR NO. 7525/MR/III/2025');

    expect($matchedP5?->name)->toBe('Rendal TA P5')
        ->and($matchedLab?->name)->toBe('Laboratorium')
        ->and($matchedAreaLain?->name)->toBe('Rendal TA (Area Lain / All Area)');
});

test('service syncs DO details for NONSTOCK and PD, skips RDTV, prioritizes GRS date, and auto-closes fully issued MIR', function (): void {
    $this->seed(RequestingUnitSeeder::class);
    $service = app(NonStockAgingService::class);
    $location = LocationReceiving::query()->create(['name' => 'FLOOR-A']);

    $po1 = PurchaseOrderIssued::query()->create([
        'purchase_order_and_item' => '5300060001-10',
        'purchase_order_no' => '5300060001',
        'item_no' => 10,
        'mrp_type' => 'NONSTOCK',
        'material_type' => 'ZSP',
        'description' => 'CONTROL VALVE P5',
        'qty_po' => 4,
        'uoi' => 'EA',
        'vendor_name' => 'PT VENDOR A',
        'currency' => 'USD',
        'net_price' => 1000,
        'total_amount_in_lc' => 400_000_000,
        'requisitioner' => 'RENDAL-TAP5 MR 1001',
    ]);

    $doGrs = DeliveryOrderReceipt::query()->create([
        'delivery_order_no' => 'DO-001',
        'received_date' => '2026-09-01',
        'physical_received_date' => '2026-09-02',
        'status' => 'GRS',
    ]);

    $grsHeader = GrsRdtv::query()->create([
        'transaction_date' => '2026-09-10',
        'category' => 'GRS',
    ]);

    GrsRdtvItem::query()->create([
        'grs_rdtv_id' => $grsHeader->id,
        'delivery_order_receipt_id' => $doGrs->id,
        'document_code' => '5007000001',
        'status' => 'OK',
    ]);

    $detail1 = DeliveryOrderReceiptDetail::query()->create([
        'delivery_order_receipt_id' => $doGrs->id,
        'purchase_order_issued_id' => $po1->id,
        'item_no' => 10,
        'quantity' => 4,
        'description' => 'CONTROL VALVE P5',
        'mrp_type' => 'NONSTOCK',
        'material_type' => 'ZSP',
        'requisitioner' => 'RENDAL-TAP5 MR 1001',
        'location_id' => $location->id,
    ]);

    // Pengambilan parsial 1 dari 4 (sisa 3 -> nilai tertahan 300 jt)
    $mir = MaterialIssue::query()->create([
        'jenis_mir' => 'digital',
        'mir_number' => 'MIR/202610/0001',
        'tanggal' => '2026-10-01',
        'departemen' => 'Rendal TA P5',
        'diminta_oleh' => 'Budi Santoso',
        'no_hp' => '081234567890',
    ]);

    MaterialIssueDetail::query()->create([
        'material_issue_id' => $mir->id,
        'delivery_order_receipt_detail_id' => $detail1->id,
        'diminta' => 1,
        'diserahkan' => 1,
        'boh' => 3,
    ]);

    // DO PD yang masih belum GRS (seharusnya masuk status On Check)
    $doOnCheck = DeliveryOrderReceipt::query()->create([
        'delivery_order_no' => 'DO-002',
        'received_date' => '2026-11-10',
        'status' => 'Diterima',
    ]);

    DeliveryOrderReceiptDetail::query()->create([
        'delivery_order_receipt_id' => $doOnCheck->id,
        'item_no' => 20,
        'quantity' => 2,
        'description' => 'BEARING PD',
        'mrp_type' => 'PD',
        'material_type' => 'ZSP',
        'requisitioner' => 'LAB KIMIA',
        'total_amount_snapshot' => 50_000_000,
    ]);

    // DO RDTV harus dilewati
    $doRdtv = DeliveryOrderReceipt::query()->create([
        'delivery_order_no' => 'DO-003',
        'received_date' => '2026-11-01',
        'status' => 'RDTV',
    ]);

    DeliveryOrderReceiptDetail::query()->create([
        'delivery_order_receipt_id' => $doRdtv->id,
        'item_no' => 10,
        'quantity' => 1,
        'description' => 'REJECTED ITEM',
        'mrp_type' => 'NONSTOCK',
        'total_amount_snapshot' => 10_000_000,
    ]);

    $stats = $service->syncFromDeliveryOrderDetails();

    expect($stats['created'])->toBe(2)
        ->and($stats['mapped'])->toBe(2)
        ->and(NonStockAgingControl::query()->count())->toBe(2);

    $ctrl1 = NonStockAgingControl::query()->where('delivery_order_receipt_detail_id', $detail1->id)->firstOrFail();
    $ctrl1->load(['deliveryOrderReceiptDetail.deliveryOrderReceipt.grsRdtvItems.grsRdtv', 'deliveryOrderReceiptDetail.materialIssueDetails', 'requestingUnit']);

    expect($ctrl1->operational_status)->toBe(AgingOperationalStatus::BelumDiambil)
        ->and($ctrl1->grs_posting_date?->toDateString())->toBe('2026-09-10')
        ->and($ctrl1->resolvedAgingDays(Carbon::parse('2026-11-23')))->toBe(74)
        ->and($ctrl1->resolvedAgingZone(Carbon::parse('2026-11-23')))->toBe(AgingZone::Critical)
        ->and($ctrl1->resolvedRemainingQuantity())->toBe(3.0)
        ->and($ctrl1->resolvedOutstandingValue())->toBe(300_000_000.0)
        ->and($ctrl1->requestingUnit?->name)->toBe('Rendal TA P5');

    $ctrl2 = NonStockAgingControl::query()->where('mrp_type_snapshot', 'PD')->firstOrFail();
    expect($ctrl2->operational_status)->toBe(AgingOperationalStatus::OnCheck)
        ->and($ctrl2->physical_checked)->toBeFalse();

    // Tutup sisa 3 via MIR tanpa event (simulasi insert lama) -> batch sync harus auto-close & catat log
    MaterialIssueDetail::withoutEvents(function () use ($mir, $detail1): void {
        MaterialIssueDetail::query()->create([
            'material_issue_id' => $mir->id,
            'delivery_order_receipt_detail_id' => $detail1->id,
            'diminta' => 3,
            'diserahkan' => 3,
            'boh' => 0,
        ]);
    });

    $stats2 = $service->syncFromDeliveryOrderDetails();
    $ctrl1->refresh();

    expect($stats2['auto_closed'])->toBe(1)
        ->and($ctrl1->operational_status)->toBe(AgingOperationalStatus::SelesaiDiambil)
        ->and($ctrl1->followup_stage)->toBe(AgingFollowupStage::Selesai)
        ->and(NonStockAgingLog::query()->where('nonstock_aging_control_id', $ctrl1->id)->where('action_type', 'auto_closed_mir')->exists())->toBeTrue();
});

test('service builds accurate LEAD 2026 summary metrics, 4 zones, Pareto 80/20, and baseline comparison', function (): void {
    $unitP5 = RequestingUnit::factory()->pilot()->create(['name' => 'Rendal TA P5']);
    $unitLab = RequestingUnit::factory()->pilot()->create(['name' => 'Laboratorium']);
    $asOf = Carbon::parse('2026-11-23');

    // 1. Critical (>60H) - Unattended - Rp 400 jt
    NonStockAgingControl::factory()->create([
        'po_by_item' => '5300010001-10',
        'mrp_type_snapshot' => 'NONSTOCK',
        'grs_posting_date' => '2026-09-01', // 83 hari
        'initial_qty_gr' => 1,
        'initial_value_lc' => 400_000_000,
        'requesting_unit_id' => $unitP5->id,
        'operational_status' => AgingOperationalStatus::BelumDiambil,
        'followup_stage' => AgingFollowupStage::EskalasiVp,
    ]);

    // 2. Warning (31-60H) - Scheduled Hold - Rp 300 jt
    NonStockAgingControl::factory()->scheduledHold()->create([
        'po_by_item' => '5300010002-10',
        'mrp_type_snapshot' => 'NONSTOCK',
        'grs_posting_date' => '2026-10-10', // 44 hari
        'initial_qty_gr' => 1,
        'initial_value_lc' => 300_000_000,
        'requesting_unit_id' => $unitP5->id,
    ]);

    // 3. Attention (15-30H) - Rp 200 jt
    NonStockAgingControl::factory()->create([
        'po_by_item' => '5300010003-10',
        'mrp_type_snapshot' => 'NONSTOCK',
        'grs_posting_date' => '2026-11-01', // 22 hari
        'initial_qty_gr' => 1,
        'initial_value_lc' => 200_000_000,
        'requesting_unit_id' => $unitLab->id,
        'operational_status' => AgingOperationalStatus::BelumDiambil,
    ]);

    // 4. Normal (0-14H) - Rp 100 jt (belum dipetakan)
    NonStockAgingControl::factory()->create([
        'po_by_item' => '5300010004-10',
        'mrp_type_snapshot' => 'NONSTOCK',
        'grs_posting_date' => '2026-11-18', // 5 hari
        'initial_qty_gr' => 1,
        'initial_value_lc' => 100_000_000,
        'requesting_unit_id' => null,
        'operational_status' => AgingOperationalStatus::BelumDiambil,
    ]);

    // 5. On Check - Rp 150 jt (terpisah dari outstanding)
    NonStockAgingControl::factory()->onCheck()->create([
        'po_by_item' => '5300010005-10',
        'mrp_type_snapshot' => 'NONSTOCK',
        'grs_posting_date' => '2026-11-15',
        'initial_qty_gr' => 1,
        'initial_value_lc' => 150_000_000,
    ]);

    // 6. Dipindah (harus tidak masuk outstanding)
    NonStockAgingControl::factory()->create([
        'po_by_item' => '5300010006-10',
        'mrp_type_snapshot' => 'NONSTOCK',
        'initial_value_lc' => 500_000_000,
        'operational_status' => AgingOperationalStatus::Dipindah,
    ]);

    // Baseline W1: 8 items, Rp 1,6 M (turun ke 4 items = -50% dan Rp 1 M = -37.5%)
    NonStockAgingSnapshot::factory()->create([
        'label' => 'Baseline W1',
        'snapshot_date' => '2026-11-01',
        'scope' => 'all',
        'mrp_scope' => 'NONSTOCK',
        'total_outstanding_items' => 8,
        'total_outstanding_value' => 1_600_000_000,
    ]);

    $summary = app(NonStockAgingService::class)->buildSummaryMetrics('NONSTOCK', $asOf);

    expect($summary['outstanding_items'])->toBe(4)
        ->and($summary['outstanding_po'])->toBe(4)
        ->and($summary['outstanding_value'])->toBe(1_000_000_000.0)
        ->and($summary['on_check_items'])->toBe(1)
        ->and($summary['on_check_value'])->toBe(150_000_000.0)
        ->and($summary['scheduled_hold_items'])->toBe(1)
        ->and($summary['unattended_critical_items'])->toBe(1)
        ->and($summary['zones']['Normal']['items'])->toBe(1)
        ->and($summary['zones']['Attention']['items'])->toBe(1)
        ->and($summary['zones']['Warning']['items'])->toBe(1)
        ->and($summary['zones']['Critical']['items'])->toBe(1)
        ->and($summary['pic_mapped_percentage'])->toBe(75.0)
        ->and($summary['warning_critical_followed_up_percentage'])->toBe(100.0)
        ->and($summary['pareto'][0]['unit_name'])->toBe('Rendal TA P5')
        ->and($summary['pareto'][0]['share_pct'])->toBe(70.0)
        ->and($summary['pareto'][1]['cumulative_pct'])->toBe(90.0)
        ->and($summary['baseline_comparison']['item_reduction_pct'])->toBe(50.0)
        ->and($summary['baseline_comparison']['value_reduction_pct'])->toBe(37.5)
        ->and($summary['baseline_comparison']['item_target_met'])->toBeTrue()
        ->and($summary['baseline_comparison']['value_target_met'])->toBeTrue();
});

test('filament RequestingUnitResource renders, creates, edits, and syncs requisitioner mappings', function (): void {
    $user = User::query()->create([
        'name' => 'Admin Receiving',
        'npk' => '2100001',
        'email' => 'admin.receiving@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->assignRole('Developer');

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    expect(RequestingUnitResource::getNavigationGroup())->toBe('Monitoring Receiving');

    Livewire::test(CreateRequestingUnit::class)
        ->fillForm([
            'name' => 'Rendal TA P5',
            'requisitioner_aliases' => ['RENDAL-TAP5', 'HAR1-TAP5'],
            'pic_name' => 'Ibnu Sina Al Barry',
            'pic_email' => 'ibnu@pupukkaltim.com',
            'pic_phone' => '085247376525',
            'avp_name' => 'AVP Rendal',
            'avp_title' => 'AVP Rendal TA P5',
            'avp_email' => 'avp.rendal@pupukkaltim.com',
            'vp_name' => 'VP TA',
            'vp_title' => 'VP Pemeliharaan',
            'vp_email' => 'vp.ta@pupukkaltim.com',
            'is_pilot' => true,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $unit = RequestingUnit::query()->where('name', 'Rendal TA P5')->firstOrFail();

    $unmappedControl = NonStockAgingControl::factory()->create([
        'requisitioner_raw' => 'HAR1-TAP5 MR 6648/MR/VII/2024',
        'requesting_unit_id' => null,
    ]);

    Livewire::test(ListRequestingUnits::class)
        ->assertCanSeeTableRecords([$unit])
        ->callAction('syncMappings');

    expect($unmappedControl->fresh()->requesting_unit_id)->toBe($unit->id);

    Livewire::test(EditRequestingUnit::class, ['record' => $unit->getRouteKey()])
        ->fillForm([
            'pic_name' => 'Ibnu Sina Updated',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($unit->fresh()->pic_name)->toBe('Ibnu Sina Updated');
});
