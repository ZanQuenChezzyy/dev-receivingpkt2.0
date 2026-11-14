<?php

use App\Filament\Resources\MonitoringKarungs\MonitoringKarungResource;
use App\Filament\Resources\MonitoringKarungs\Pages\CreateMonitoringKarung;
use App\Filament\Resources\MonitoringKarungs\Pages\EditMonitoringKarung;
use App\Filament\Resources\MonitoringKarungs\Pages\ListMonitoringKarungs;
use App\Filament\Resources\MonitoringKarungs\Pages\ViewMonitoringKarung;
use App\Filament\Resources\MonitoringKarungs\RelationManagers\DeliveryItemsRelationManager;
use App\Filament\Resources\MonitoringKarungs\RelationManagers\QcItemsRelationManager;
use App\Filament\Resources\MonitoringKarungs\Schemas\MonitoringKarungInfolist;
use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\MonitoringKarung;
use App\Models\MonitoringKarungDelivery;
use App\Models\MonitoringKarungQc;
use App\Models\PurchaseOrderIssued;
use App\Models\User;
use App\Models\Vendor;
use App\Services\MonitoringKarungImportService;
use App\Services\SyncMonitoringKarungWithDeliveryOrderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! Schema::hasTable('users')) {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('permissions')) {
        $permissionMigration = include database_path('migrations/2026_05_15_101350_create_permission_tables.php');
        $permissionMigration->up();
    }

    if (! Schema::hasTable('vendors')) {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('vendor_number')->nullable();
            $table->text('email')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('purchase_order_issueds')) {
        Schema::create('purchase_order_issueds', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_order_no');
            $table->string('item_no')->nullable();
            $table->string('material_code')->nullable();
            $table->text('description')->nullable();
            $table->decimal('qty_po', 15, 2)->default(0);
            $table->string('uoi')->nullable();
            $table->string('vendor_name')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('invoicing_receipts')) {
        Schema::create('invoicing_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('no_po')->nullable();
            $table->string('no_invoice')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipts')) {
        Schema::create('delivery_order_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_order_no')->nullable();
            $table->date('received_date')->nullable();
            $table->date('physical_received_date')->nullable();
            $table->string('dof_number')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipt_details')) {
        Schema::create('delivery_order_receipt_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_receipt_id')->nullable();
            $table->foreignId('purchase_order_issued_id')->nullable();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('total_amount_snapshot', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('monitoring_karungs')) {
        $migration = include database_path('migrations/2026_11_13_221745_create_monitoring_karungs_table.php');
        $migration->up();
    }

    if (! Schema::hasTable('monitoring_karung_qcs')) {
        $migration = include database_path('migrations/2026_11_13_221752_create_monitoring_karung_qcs_table.php');
        $migration->up();
    }

    if (! Schema::hasTable('monitoring_karung_deliveries')) {
        $migration = include database_path('migrations/2026_11_13_221758_create_monitoring_karung_deliveries_table.php');
        $migration->up();
    }

    if (! Schema::hasColumn('delivery_order_receipts', 'monitoring_karung_id') || ! Schema::hasColumn('monitoring_karung_deliveries', 'delivery_order_receipt_id')) {
        $relationMigration = include database_path('migrations/2026_11_14_185811_add_delivery_order_receipt_relations_to_monitoring_karungs.php');
        $relationMigration->up();
    }

    $this->user = User::firstOrCreate(
        ['email' => 'admin@test.com'],
        [
            'name' => 'Admin Test',
            'password' => bcrypt('password'),
        ]
    );

    Auth::login($this->user);
});

test('monitoring karung model computes totals, percentages and sisa correctly', function () {
    $monitoring = MonitoringKarung::create([
        'no_po' => '5300033630',
        'vendor_name' => 'PT Dasaplast Nusantara',
        'qty_po' => 1000000,
        'kategori_karung' => 'Prill',
        'lokasi_pabrik' => 'Surabaya',
        'status' => 'Aktif',
    ]);

    MonitoringKarungQc::create([
        'monitoring_karung_id' => $monitoring->id,
        'tahap_qc' => 'Awal',
        'qty_qc' => 22,
        'status_qc' => 'Diterima',
        'no_sa' => '1000690323',
    ]);

    MonitoringKarungQc::create([
        'monitoring_karung_id' => $monitoring->id,
        'tahap_qc' => '1',
        'qty_qc' => 300000,
        'status_qc' => 'Diterima',
        'no_sa' => '1000690324',
    ]);

    MonitoringKarungDelivery::create([
        'monitoring_karung_id' => $monitoring->id,
        'tahap_kirim' => '1',
        'qty_kirim' => 150000,
        'no_invoice' => 'INV-001',
        'no_do' => 'DO-001',
    ]);

    MonitoringKarungDelivery::create([
        'monitoring_karung_id' => $monitoring->id,
        'tahap_kirim' => '2',
        'qty_kirim' => 250000,
        'no_invoice' => 'INV-002',
        'no_do' => 'DO-002',
    ]);

    $monitoring->refresh();

    expect($monitoring->total_qty_qc)->toBe(300022)
        ->and($monitoring->total_qty_kirim)->toBe(400000)
        ->and($monitoring->sisa_qty_po)->toBe(600000)
        ->and($monitoring->persentase_qc)->toBe(30.0)
        ->and($monitoring->persentase_kirim)->toBe(40.0)
        ->and($monitoring->has_pending_sa)->toBeFalse()
        ->and($monitoring->pending_sa_count)->toBe(0)
        ->and($monitoring->completed_sa_count)->toBe(2)
        ->and($monitoring->selisih_qc_kirim)->toBe(-99978);
});

test('monitoring karung correctly identifies pending SA status and counts', function () {
    $monitoring = MonitoringKarung::create([
        'no_po' => '5000005382',
        'vendor_name' => 'PT Dasaplast Nusantara',
        'qty_po' => 500000,
    ]);

    MonitoringKarungQc::create([
        'monitoring_karung_id' => $monitoring->id,
        'tahap_qc' => '1',
        'qty_qc' => 150000,
        'status_qc' => 'BLM SA',
        'no_sa' => null,
    ]);

    MonitoringKarungQc::create([
        'monitoring_karung_id' => $monitoring->id,
        'tahap_qc' => '2',
        'qty_qc' => 150000,
        'status_qc' => 'Diterima',
        'no_sa' => '1000690325',
    ]);

    $monitoring->refresh();

    expect($monitoring->has_pending_sa)->toBeTrue()
        ->and($monitoring->pending_sa_count)->toBe(1)
        ->and($monitoring->completed_sa_count)->toBe(1);
});

test('monitoring karung import service successfully parses csv spreadsheet data', function () {
    $sampleCsv = <<<'CSV'
NAMA VENDOR,NO PO,QTY QC,TAHAP QC,NO DOF DAAN,TGL PERMINTAAN,NO SIK,TGL SIK,NO SURAT QC,STATUS,FILE ISTEK,NO BAPP,NO SA,DRAFTER,QTY PO,QTY KIRIM,TAHAP KIRIM,NO INVOICE,NO DO,SURAT,QTY SUPPLY,QTY SURAT,TANGGAL PENERIMAAN,,
PT Dasaplast Nusantara,5300033630,22,Awal,,,158,,1683,Diterima,1891,,,,1500000,150000,1,,,,,,,,
,,300000,1,,,194,,893,Diterima,1125,,,,,150000,2,,,,,,,,
,,300000,2,,,45,,5840,Diterima,6166,,,,,150000,3,,,,,,,,
,5000005382,22,Awal,29204,2024-07-11,10045,2024-07-11,30166,Diterima,30410,01886 ,,CAMEL,1800000,120000,1,,019,,,,,,
,,150000,1,00906,2025-01-20,906,2025-01-20,4638,Diterima,5526,01315/01889,1000690323,PUGUH / CAMEL,,30000,2,,035/25,,,,,,
CSV;

    $service = app(MonitoringKarungImportService::class);
    $result = $service->import($sampleCsv, false);

    expect($result['imported_pos'])->toBe(2)
        ->and($result['imported_qc_items'])->toBe(5)
        ->and($result['imported_delivery_items'])->toBe(5);

    $po1 = MonitoringKarung::where('no_po', '5300033630')->first();
    expect($po1)->not->toBeNull()
        ->and($po1->vendor_name)->toBe('PT Dasaplast Nusantara')
        ->and($po1->qty_po)->toBe(1500000)
        ->and($po1->qcItems()->count())->toBe(3)
        ->and($po1->deliveryItems()->count())->toBe(3);

    $po2 = MonitoringKarung::where('no_po', '5000005382')->first();
    expect($po2)->not->toBeNull()
        ->and($po2->vendor_name)->toBe('PT Dasaplast Nusantara')
        ->and($po2->qty_po)->toBe(1800000)
        ->and($po2->qcItems()->count())->toBe(2)
        ->and($po2->deliveryItems()->count())->toBe(2);

    $qcItemWithSa = $po2->qcItems()->where('tahap_qc', '1')->first();
    expect($qcItemWithSa->no_sa)->toBe('1000690323')
        ->and($qcItemWithSa->drafter)->toBe('PUGUH / CAMEL');
});

test('monitoring karung resource configuration and navigation attributes', function () {
    expect(MonitoringKarungResource::getNavigationLabel())->toBe('Monitoring Karung')
        ->and(MonitoringKarungResource::getModelLabel())->toBe('Monitoring Karung')
        ->and(MonitoringKarungResource::getNavigationGroup())->toBe('Penerimaan Jasa / Invoice');
});

test('monitoring karung import service successfully parses full sample dataset file', function () {
    $filePath = database_path('data/sample_monitoring_karung.csv');
    $service = app(MonitoringKarungImportService::class);
    $result = $service->import($filePath, true);

    expect($result['imported_pos'])->toBeGreaterThan(5)
        ->and($result['imported_qc_items'])->toBeGreaterThan(10)
        ->and($result['imported_delivery_items'])->toBeGreaterThan(10);
});

test('monitoring karung import service successfully parses excel xlsx file', function () {
    $filePath = database_path('data/sample_monitoring_karung.xlsx');
    if (! file_exists($filePath)) {
        $this->markTestSkipped('Sample Excel file not found.');
    }

    $service = app(MonitoringKarungImportService::class);
    $result = $service->import($filePath, true);

    expect($result['imported_pos'])->toBeGreaterThan(50)
        ->and($result['imported_qc_items'])->toBeGreaterThan(100)
        ->and($result['imported_delivery_items'])->toBeGreaterThan(100);
});

test('monitoring karung list page renders successfully', function () {
    Livewire::test(ListMonitoringKarungs::class)
        ->assertSuccessful();
});

test('monitoring karung create and view pages render successfully', function () {
    Livewire::test(CreateMonitoringKarung::class)
        ->assertSuccessful();

    $monitoring = MonitoringKarung::create([
        'no_po' => '5000009999',
        'vendor_name' => 'PT Test Karung',
        'qty_po' => 500000,
    ]);

    Livewire::test(ViewMonitoringKarung::class, [
        'record' => $monitoring->id,
    ])->assertSuccessful();

    Livewire::test(EditMonitoringKarung::class, [
        'record' => $monitoring->id,
    ])->assertSuccessful();
});

test('monitoring karung relation managers render successfully on edit and view tabs', function () {
    $monitoring = MonitoringKarung::create([
        'no_po' => '5300099999',
        'vendor_name' => 'PT Karung Nusantara',
        'qty_po' => 1000000,
    ]);

    $qc = MonitoringKarungQc::create([
        'monitoring_karung_id' => $monitoring->id,
        'tahap_qc' => '1',
        'qty_qc' => 200000,
        'status_qc' => 'BLM SA',
        'no_sik' => '001/SIK/2026',
    ]);

    $delivery = MonitoringKarungDelivery::create([
        'monitoring_karung_id' => $monitoring->id,
        'tahap_kirim' => '1',
        'qty_kirim' => 200000,
        'no_do' => 'DO-001',
    ]);

    Livewire::test(QcItemsRelationManager::class, [
        'ownerRecord' => $monitoring,
        'pageClass' => EditMonitoringKarung::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$qc]);

    Livewire::test(DeliveryItemsRelationManager::class, [
        'ownerRecord' => $monitoring,
        'pageClass' => EditMonitoringKarung::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$delivery]);
});

test('monitoring karung form auto connects PO terbit and DO receipt when entering no_po', function () {
    $po = PurchaseOrderIssued::create([
        'purchase_order_no' => '5300077777',
        'vendor_name' => 'PT Karung Mas Perkasa',
        'qty_po' => 750000,
        'description' => 'KANTONG:UREA;GRANUL;50KG',
    ]);

    $receipt = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'DO-PKT-001',
        'received_date' => '2026-03-01',
        'physical_received_date' => '2026-03-02',
        'dof_number' => 'DOF-991',
    ]);

    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $receipt->id,
        'purchase_order_issued_id' => $po->id,
        'quantity' => 150000,
    ]);

    $test = Livewire::test(CreateMonitoringKarung::class)
        ->fillForm([
            'no_po' => '5300077777',
        ])
        ->assertFormSet([
            'purchase_order_issued_id' => $po->id,
            'vendor_name' => 'PT Karung Mas Perkasa',
            'qty_po' => 750000,
            'kategori_karung' => 'Granul',
        ]);

    $formDeliveries = $test->get('data.deliveryItems');
    expect($formDeliveries)->toBeArray()
        ->and(count($formDeliveries))->toBe(1)
        ->and(array_values($formDeliveries)[0]['no_do'])->toBe('DO-PKT-001')
        ->and(array_values($formDeliveries)[0]['qty_kirim'])->toBe(150000);
});

test('sync monitoring karung with delivery order service connects and synchronizes DO receipts bidirectionally', function () {
    $po = PurchaseOrderIssued::create([
        'purchase_order_no' => '5300088888',
        'vendor_name' => 'PT Karung Sakti',
        'qty_po' => 600000,
        'description' => 'KANTONG:UREA;PRILL;50KG',
    ]);

    $receipt1 = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'DO-KS-001',
        'received_date' => '2026-04-01',
        'physical_received_date' => '2026-04-02',
        'dof_number' => 'DOF-101',
    ]);

    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $receipt1->id,
        'purchase_order_issued_id' => $po->id,
        'quantity' => 200000,
    ]);

    $receipt2 = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'DO-KS-002',
        'received_date' => '2026-04-15',
        'physical_received_date' => '2026-04-16',
        'dof_number' => 'DOF-102',
    ]);

    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $receipt2->id,
        'purchase_order_issued_id' => $po->id,
        'quantity' => 250000,
    ]);

    $monitoring = MonitoringKarung::create([
        'no_po' => '5300088888',
        'vendor_name' => 'PT Karung Sakti',
        'qty_po' => 600000,
        'status' => 'Aktif',
    ]);

    $service = app(SyncMonitoringKarungWithDeliveryOrderService::class);
    $result = $service->sync($monitoring);

    expect($result['linked_do_count'])->toBe(2)
        ->and($result['new_deliveries'])->toBe(2);

    $monitoring->refresh();
    expect($monitoring->purchase_order_issued_id)->toBe($po->id)
        ->and($monitoring->deliveryItems()->count())->toBe(2);

    $receipt1->refresh();
    $receipt2->refresh();
    expect($receipt1->monitoring_karung_id)->toBe($monitoring->id)
        ->and($receipt2->monitoring_karung_id)->toBe($monitoring->id);

    $firstDelivery = $monitoring->deliveryItems()->where('no_do', 'DO-KS-001')->first();
    expect($firstDelivery)->not->toBeNull()
        ->and($firstDelivery->delivery_order_receipt_id)->toBe($receipt1->id)
        ->and($firstDelivery->qty_kirim)->toBe(200000);

    $latestDo = MonitoringKarungInfolist::getLatestDeliveryOrderReceipt($monitoring);
    expect($latestDo)->not->toBeNull()
        ->and($latestDo->id)->toBe($receipt2->id)
        ->and($latestDo->delivery_order_no)->toBe('DO-KS-002');
});

test('monitoring karung form supports vendor select and links vendor_id on change', function () {
    $vendor = Vendor::create([
        'name' => 'PT DASAPLAST NUSANTARA',
    ]);

    $test = Livewire::test(CreateMonitoringKarung::class)
        ->fillForm([
            'vendor_name' => 'PT DASAPLAST NUSANTARA',
        ])
        ->assertFormSet([
            'vendor_name' => 'PT DASAPLAST NUSANTARA',
            'vendor_id' => $vendor->id,
        ]);
});

test('monitoring karung form requires name, email, and vendor_number when adding new vendor', function () {
    $component = Livewire::test(CreateMonitoringKarung::class);

    // Membuka modal aksi createOption pada field vendor_name
    $component->mountFormComponentAction('vendor_name', 'createOption')
        ->assertFormComponentActionMounted('vendor_name', 'createOption')
        ->setFormComponentActionData([
            'vendor_number' => '10009988',
            'name' => 'PT Karung Baru Jaya',
            'email' => 'sales@karungbarujaya.co.id',
        ])
        ->callMountedFormComponentAction()
        ->assertHasNoFormComponentActionErrors();

    $newVendor = Vendor::where('vendor_number', '10009988')->first();
    expect($newVendor)->not->toBeNull()
        ->and($newVendor->name)->toBe('PT Karung Baru Jaya')
        ->and(is_array($newVendor->email) ? $newVendor->email[0] : $newVendor->email)->toBe('sales@karungbarujaya.co.id');

    $component->assertFormSet([
        'vendor_name' => 'PT Karung Baru Jaya',
        'vendor_id' => $newVendor->id,
    ]);
});

test('import monitoring karung automatically synchronizes with PO terbit and DO receipts', function () {
    $po = PurchaseOrderIssued::create([
        'purchase_order_no' => '5300055555',
        'vendor_name' => 'PT Karung Import Sync',
        'qty_po' => 800000,
        'description' => 'KANTONG:UREA;PRILL;50KG',
    ]);

    $receipt = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'DO-IMPORT-001',
        'received_date' => '2026-05-01',
        'physical_received_date' => '2026-05-02',
        'dof_number' => 'DOF-IMPORT-55',
    ]);

    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $receipt->id,
        'purchase_order_issued_id' => $po->id,
        'quantity' => 200000,
    ]);

    $csvContent = implode("\n", [
        'NAMA VENDOR,NO PO,QTY QC,TAHAP QC,NO DOF,TGL PERMINTAAN,NO SIK,TGL SIK,NO SURAT QC,STATUS QC,FILE ISTEK,NO BAPP,NO SA,DRAFTER,QTY PO,QTY KIRIM,TAHAP KIRIM,NO INVOICE,NO DO,NO SURAT,QTY SUPPLY,QTY SURAT,TGL TERIMA',
        'PT Karung Import Sync,5300055555,200000,1,DOF-IMPORT-55,2026-05-01,100/SIK/2026,2026-05-02,30001,Diterima,30001,0101,1000690000,CAMEL,800000,200000,1,INV-001,DO-IMPORT-001,DOF-IMPORT-55,200000,200000,2026-05-02',
    ]);

    $service = app(MonitoringKarungImportService::class);
    $result = $service->import($csvContent, false);

    expect($result['imported_pos'])->toBe(1)
        ->and($result['synced_do_count'])->toBeGreaterThanOrEqual(1);

    $monitoring = MonitoringKarung::where('no_po', '5300055555')->first();
    expect($monitoring)->not->toBeNull()
        ->and($monitoring->purchase_order_issued_id)->toBe($po->id);

    $receipt->refresh();
    expect($receipt->monitoring_karung_id)->toBe($monitoring->id);

    $delivery = $monitoring->deliveryItems()->where('no_do', 'DO-IMPORT-001')->first();
    expect($delivery)->not->toBeNull()
        ->and($delivery->delivery_order_receipt_id)->toBe($receipt->id);
});

test('creating monitoring karung automatically synchronizes DO receipts and PO terbit upon creation', function () {
    $po = PurchaseOrderIssued::create([
        'purchase_order_no' => '5300066666',
        'vendor_name' => 'PT Karung Form Sync',
        'qty_po' => 500000,
        'description' => 'KANTONG:UREA;GRANUL;50KG',
    ]);

    $receipt = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'DO-FORM-001',
        'received_date' => '2026-06-01',
        'physical_received_date' => '2026-06-02',
        'dof_number' => 'DOF-FORM-66',
    ]);

    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $receipt->id,
        'purchase_order_issued_id' => $po->id,
        'quantity' => 125000,
    ]);

    Livewire::test(CreateMonitoringKarung::class)
        ->fillForm([
            'no_po' => '5300066666',
            'vendor_name' => 'PT Karung Form Sync',
            'qty_po' => 500000,
            'status' => 'Aktif',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $monitoring = MonitoringKarung::where('no_po', '5300066666')->first();
    expect($monitoring)->not->toBeNull()
        ->and($monitoring->purchase_order_issued_id)->toBe($po->id);

    $receipt->refresh();
    expect($receipt->monitoring_karung_id)->toBe($monitoring->id);

    $delivery = $monitoring->deliveryItems()->first();
    expect($delivery)->not->toBeNull()
        ->and($delivery->delivery_order_receipt_id)->toBe($receipt->id)
        ->and($delivery->no_do)->toBe('DO-FORM-001');
});

test('monitoring karung automatically transitions status to Selesai when deliveries and SAs are complete', function () {
    $monitoring = MonitoringKarung::create([
        'no_po' => '5000002308',
        'vendor_name' => 'PT Samisuryaindah Plastik',
        'qty_po' => 400000,
        'status' => 'Aktif',
    ]);

    // Tambah QC tahap 1 dan 2 (sudah SA)
    MonitoringKarungQc::create([
        'monitoring_karung_id' => $monitoring->id,
        'tahap_qc' => '1',
        'qty_qc' => 200000,
        'no_sa' => '1000690001',
        'status_qc' => 'Diterima',
    ]);

    MonitoringKarungQc::create([
        'monitoring_karung_id' => $monitoring->id,
        'tahap_qc' => '2',
        'qty_qc' => 200022,
        'no_sa' => '1000690002',
        'status_qc' => 'Diterima',
    ]);

    // Belum selesai karena belum ada pengiriman
    $monitoring->refresh();
    expect($monitoring->status)->toBe('Aktif');

    // Tambah pengiriman hingga memenuhi alokasi PO 400.000 Lbr
    MonitoringKarungDelivery::create([
        'monitoring_karung_id' => $monitoring->id,
        'tahap_kirim' => '1',
        'qty_kirim' => 400000,
        'no_do' => 'DO-SAMISURYA-01',
    ]);

    $monitoring->refresh();
    expect($monitoring->status)->toBe('Selesai');
});
