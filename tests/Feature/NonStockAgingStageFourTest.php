<?php

use App\Enums\AgingFollowupStage;
use App\Enums\AgingOperationalStatus;
use App\Filament\Pages\NonStockAgingMonitoring;
use App\Models\NonStockAgingControl;
use App\Models\NonStockAgingSnapshot;
use App\Models\RequestingUnit;
use App\Models\User;
use App\Services\NonStockAgingReportService;
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
            $table->string('purchase_order_no')->nullable();
            $table->integer('item_no')->default(10);
            $table->string('material_code')->nullable();
            $table->string('description')->nullable();
            $table->decimal('qty_po', 15, 2)->default(1);
            $table->string('uoi')->default('EA');
            $table->string('vendor_name')->nullable();
            $table->string('mrp_type')->nullable();
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

    if (! Schema::hasTable('material_issue_details')) {
        Schema::create('material_issue_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('material_issue_id');
            $table->unsignedBigInteger('delivery_order_receipt_detail_id');
            $table->decimal('diminta', 15, 2)->default(0);
            $table->decimal('diserahkan', 15, 2)->default(0);
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
});

test('captures baseline and evaluation snapshots and computes LEAD 2026 Before vs After comparison accurately', function (): void {
    $unitP5 = RequestingUnit::factory()->pilot()->create(['name' => 'Rendal TA P5']);
    $unitLab = RequestingUnit::factory()->pilot()->create(['name' => 'Laboratorium']);
    $reportService = app(NonStockAgingReportService::class);

    // Baseline W1: 4 item Non-Stock senilai Rp 1 M (0% follow-up)
    $i1 = NonStockAgingControl::factory()->create([
        'po_by_item' => '5300011001-10',
        'mrp_type_snapshot' => 'NONSTOCK',
        'grs_posting_date' => '2026-09-01',
        'initial_value_lc' => 400_000_000,
        'requesting_unit_id' => $unitP5->id,
    ]);
    $i2 = NonStockAgingControl::factory()->create([
        'po_by_item' => '5300011002-10',
        'mrp_type_snapshot' => 'NONSTOCK',
        'grs_posting_date' => '2026-10-01',
        'initial_value_lc' => 300_000_000,
        'requesting_unit_id' => $unitP5->id,
    ]);
    NonStockAgingControl::factory()->create([
        'po_by_item' => '5300011003-10',
        'mrp_type_snapshot' => 'NONSTOCK',
        'grs_posting_date' => '2026-11-01',
        'initial_value_lc' => 200_000_000,
        'requesting_unit_id' => $unitLab->id,
    ]);
    NonStockAgingControl::factory()->create([
        'po_by_item' => '5300011004-10',
        'mrp_type_snapshot' => 'NONSTOCK',
        'grs_posting_date' => '2026-11-18',
        'initial_value_lc' => 100_000_000,
        'requesting_unit_id' => $unitLab->id,
    ]);

    $baseline = $reportService->captureSnapshot('Baseline W1', 'NONSTOCK', false, Carbon::parse('2026-11-23'));

    expect($baseline->total_outstanding_items)->toBe(4)
        ->and($baseline->total_outstanding_value)->toBe(1_000_000_000.0)
        ->and($baseline->warning_critical_followed_up_percentage)->toBe(0.0);

    // Simulasi progres W8: 1 item (Rp 400 jt) selesai diambil, 1 item Warning sudah dikonfirmasi AVP
    $i1->update([
        'operational_status' => AgingOperationalStatus::SelesaiDiambil,
        'followup_stage' => AgingFollowupStage::Selesai,
    ]);
    $i2->update([
        'followup_stage' => AgingFollowupStage::KonfirmasiAvp,
        'avp_escalated_at' => now(),
    ]);

    $evaluation = $reportService->captureSnapshot('Evaluasi W8', 'NONSTOCK', false, Carbon::parse('2026-11-23'));
    $comparison = $reportService->buildBeforeAfterComparison($baseline, $evaluation);

    expect($comparison['item_delta'])->toBe(-1)
        ->and($comparison['item_reduction_pct'])->toBe(25.0)
        ->and($comparison['item_target_met'])->toBeTrue()
        ->and($comparison['value_delta'])->toBe(-400_000_000.0)
        ->and($comparison['value_reduction_pct'])->toBe(40.0)
        ->and($comparison['value_target_met'])->toBeTrue()
        ->and($comparison['followup_target_met'])->toBeTrue()
        ->and($comparison['unit_deltas'])->toHaveCount(2)
        ->and($comparison['unit_deltas'][0]['unit_name'])->toBe('Rendal TA P5')
        ->and($comparison['unit_deltas'][0]['before_value'])->toBe(700_000_000.0)
        ->and($comparison['unit_deltas'][0]['after_value'])->toBe(300_000_000.0);
});

test('exports standardized CSV with UTF-8 BOM and 2-sheet Excel XLSX workbook accurately', function (): void {
    $unit = RequestingUnit::factory()->pilot()->create([
        'name' => 'Rendal TA P5',
        'pic_name' => 'Ibnu Sina',
        'pic_phone' => '085247376525',
    ]);

    NonStockAgingControl::factory()->agingDays(75)->create([
        'po_by_item' => '5300055468-10',
        'mrp_type_snapshot' => 'NONSTOCK',
        'description_snapshot' => 'CONTROL VALVE EXPORT TEST',
        'location_text' => 'FLR-C',
        'initial_value_lc' => 252_480_000,
        'requesting_unit_id' => $unit->id,
    ]);

    $reportService = app(NonStockAgingReportService::class);

    // 1. Uji CSV stream
    $csvResponse = $reportService->exportRegisterCsv('NONSTOCK', 'critical');
    ob_start();
    $csvResponse->sendContent();
    $csvContent = (string) ob_get_clean();

    expect(str_starts_with($csvContent, "\xEF\xBB\xBF"))->toBeTrue()
        ->and($csvContent)->toContain('PO by Item')
        ->and($csvContent)->toContain('5300055468-10')
        ->and($csvContent)->toContain('CONTROL VALVE EXPORT TEST')
        ->and($csvContent)->toContain('Rendal TA P5')
        ->and($csvContent)->toContain('252480000');

    // 2. Uji XLSX 2-worksheet
    $xlsxPath = $reportService->generateRegisterXlsxFile('NONSTOCK', 'belum_diambil');

    try {
        expect(is_file($xlsxPath))->toBeTrue();

        $zip = new ZipArchive;
        expect($zip->open($xlsxPath))->toBeTrue();

        $wbXml = (string) $zip->getFromName('xl/workbook.xml');
        $ssXml = (string) $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        expect($wbXml)->toContain('Ringkasan KPI &amp; Pareto')
            ->and($wbXml)->toContain('Register Monitoring')
            ->and($ssXml)->toContain('5300055468-10')
            ->and($ssXml)->toContain('CONTROL VALVE EXPORT TEST')
            ->and($ssXml)->toContain('Rendal TA P5');
    } finally {
        @unlink($xlsxPath);
    }
});

test('Filament NonStockAgingMonitoring executes captureSnapshot and export actions cleanly', function (): void {
    $user = User::query()->create([
        'name' => 'AVP Receiving',
        'npk' => '2100004',
        'email' => 'avp.report@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->assignRole('Developer');

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    NonStockAgingControl::factory()->agingDays(40)->create([
        'po_by_item' => '5300056314-10',
        'mrp_type_snapshot' => 'NONSTOCK',
        'initial_value_lc' => 127_000_000,
    ]);

    Livewire::test(NonStockAgingMonitoring::class)
        ->callAction('captureSnapshot', [
            'label' => 'Baseline W1 (23 Nov 2026)',
            'pilot_only' => false,
        ])
        ->assertHasNoActionErrors();

    $snapshot = NonStockAgingSnapshot::query()->where('label', 'Baseline W1 (23 Nov 2026)')->first();
    expect($snapshot)->not->toBeNull();

    Livewire::test(NonStockAgingMonitoring::class)
        ->call('deleteSnapshot', $snapshot->id)
        ->assertHasNoErrors();

    expect(NonStockAgingSnapshot::query()->where('id', $snapshot->id)->exists())->toBeFalse();

    Livewire::test(NonStockAgingMonitoring::class)
        ->callAction('exportCsv')
        ->assertFileDownloaded();

    Livewire::test(NonStockAgingMonitoring::class)
        ->callAction('exportExcel')
        ->assertFileDownloaded();
});
