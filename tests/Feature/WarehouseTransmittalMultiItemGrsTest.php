<?php

use App\Filament\Pages\ScanWarehouseTransmittal;
use App\Filament\Resources\WarehouseTransmittals\Pages\EditWarehouseTransmittal;
use App\Filament\Resources\WarehouseTransmittals\RelationManagers\ItemsRelationManager;
use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\PurchaseOrderIssued;
use App\Models\User;
use App\Models\WarehouseDeliveryBatch;
use App\Models\WarehouseDeliveryBatchItem;
use App\Models\WarehouseDestination;
use App\Models\WarehouseTransmittal;
use App\Models\WarehouseTransmittalItem;
use App\Services\SapMb51ImportService;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
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
            $table->string('purchase_order_no')->nullable();
            $table->integer('item_no')->default(10);
            $table->decimal('qty_po', 15, 2)->default(1);
            $table->decimal('net_price', 18, 2)->default(0);
            $table->decimal('total_amount_in_lc', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_destinations')) {
        Schema::create('warehouse_destinations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('pic_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipts')) {
        Schema::create('delivery_order_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('delivery_order_no')->nullable();
            $table->string('post_103')->nullable();
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
            $table->string('material_code')->nullable();
            $table->string('description')->nullable();
            $table->decimal('quantity', 15, 2)->default(1);
            $table->decimal('total_amount_snapshot', 18, 2)->default(0);
            $table->unsignedBigInteger('warehouse_destination_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('material_issue_details')) {
        Schema::create('material_issue_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_detail_id')->nullable();
            $table->decimal('diserahkan', 15, 3)->default(0);
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_delivery_batches')) {
        Schema::create('warehouse_delivery_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('batch_title');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('total_items')->default(0);
            $table->decimal('total_value', 18, 2)->default(0);
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_transmittals')) {
        Schema::create('warehouse_transmittals', function (Blueprint $table): void {
            $table->id();
            $table->string('transmittal_no')->unique();
            $table->unsignedBigInteger('warehouse_destination_id');
            $table->date('tanggal');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_delivery_batch_items')) {
        Schema::create('warehouse_delivery_batch_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('warehouse_delivery_batch_id');
            $table->date('posting_date')->nullable();
            $table->string('material_code')->nullable();
            $table->string('description')->nullable();
            $table->string('po_number')->nullable();
            $table->string('item_no')->nullable();
            $table->string('ref_doc_103')->nullable();
            $table->string('mat_doc_105')->nullable();
            $table->string('doc_year')->nullable();
            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('uom')->default('EA');
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('reference_do_no')->nullable();
            $table->string('storage_text')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->unsignedBigInteger('delivery_order_receipt_detail_id')->nullable();
            $table->unsignedBigInteger('warehouse_transmittal_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_transmittal_items')) {
        Schema::create('warehouse_transmittal_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('warehouse_transmittal_id');
            $table->unsignedBigInteger('warehouse_delivery_batch_item_id')->nullable();
            $table->unsignedBigInteger('delivery_order_receipt_detail_id')->nullable();
            $table->timestamps();
        });
    }
});

test('SapMb51ImportService matches each item_no to its distinct DO detail even when details share one purchase_order_issued_id', function (): void {
    $poi = PurchaseOrderIssued::query()->create([
        'purchase_order_no' => '5300062704',
        'item_no' => 10,
    ]);

    $do = DeliveryOrderReceipt::query()->create([
        'delivery_order_no' => '62704',
        'status' => 'GRS',
    ]);

    $d10 = DeliveryOrderReceiptDetail::query()->create(['delivery_order_receipt_id' => $do->id, 'purchase_order_issued_id' => $poi->id, 'item_no' => 10, 'quantity' => 1]);
    $d20 = DeliveryOrderReceiptDetail::query()->create(['delivery_order_receipt_id' => $do->id, 'purchase_order_issued_id' => $poi->id, 'item_no' => 20, 'quantity' => 1]);
    $d30 = DeliveryOrderReceiptDetail::query()->create(['delivery_order_receipt_id' => $do->id, 'purchase_order_issued_id' => $poi->id, 'item_no' => 30, 'quantity' => 1]);
    $d40 = DeliveryOrderReceiptDetail::query()->create(['delivery_order_receipt_id' => $do->id, 'purchase_order_issued_id' => $poi->id, 'item_no' => 40, 'quantity' => 1]);

    $parsed = [
        ['po_number' => '5300062704', 'item_no' => '10'],
        ['po_number' => '5300062704', 'item_no' => '20'],
        ['po_number' => '5300062704', 'item_no' => '30'],
        ['po_number' => '5300062704', 'item_no' => '40'],
    ];

    $service = app(SapMb51ImportService::class);
    $lookups = $service->buildDeliveryOrderLookup($parsed);

    expect($service->matchFast($parsed[0], $lookups)['delivery_order_receipt_detail_id'])->toBe($d10->id)
        ->and($service->matchFast($parsed[1], $lookups)['delivery_order_receipt_detail_id'])->toBe($d20->id)
        ->and($service->matchFast($parsed[2], $lookups)['delivery_order_receipt_detail_id'])->toBe($d30->id)
        ->and($service->matchFast($parsed[3], $lookups)['delivery_order_receipt_detail_id'])->toBe($d40->id);
});

test('ScanWarehouseTransmittal marks ALL items of a multi-item GRS 105 as delivered even if detail_id overlaps', function (): void {
    $user = User::query()->create([
        'name' => 'Admin Gudang',
        'npk' => '2100009',
        'email' => 'gudang@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->assignRole('Developer');

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $warehouse = WarehouseDestination::query()->create(['name' => 'Gudang 1']);
    $do = DeliveryOrderReceipt::query()->create(['delivery_order_no' => '62704', 'status' => 'GRS']);
    $sharedDetail = DeliveryOrderReceiptDetail::query()->create(['delivery_order_receipt_id' => $do->id, 'item_no' => 40, 'quantity' => 1]);

    $batch = WarehouseDeliveryBatch::query()->create([
        'batch_title' => 'Periode 07 Nov 2026',
        'start_date' => '2026-11-07',
        'end_date' => '2026-11-07',
        'total_items' => 4,
        'uploaded_by' => $user->id,
    ]);

    foreach ([10, 20, 30, 40] as $itm) {
        WarehouseDeliveryBatchItem::query()->create([
            'warehouse_delivery_batch_id' => $batch->id,
            'posting_date' => '2026-04-01',
            'po_number' => '5300062704',
            'item_no' => (string) $itm,
            'mat_doc_105' => '5007150563',
            'description' => 'GEARCASE-MOTOR ITEM '.$itm,
            'status' => 'pending',
            'delivery_order_receipt_id' => $do->id,
            // Simulasi data lama di mana seluruh item sempat mendapat detail_id yang sama
            'delivery_order_receipt_detail_id' => $sharedDetail->id,
        ]);
    }

    Livewire::test(ScanWarehouseTransmittal::class)
        ->fillForm([
            'warehouse_destination_id' => $warehouse->id,
        ])
        ->call('processScan', '5007150563');

    expect(WarehouseDeliveryBatchItem::query()->where('mat_doc_105', '5007150563')->where('status', 'delivered')->count())->toBe(4)
        ->and(WarehouseTransmittalItem::query()->count())->toBe(4);
});

test('WarehouseTransmittal ItemsRelationManager searches across DO, PO, MatDoc 105, Material No, and Description without SQL errors', function (): void {
    $user = User::query()->create([
        'name' => 'Admin Search Transmittal',
        'npk' => '2100010',
        'email' => 'search_transmittal@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->assignRole('Developer');

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $warehouse = WarehouseDestination::query()->create(['name' => 'Gudang 2']);
    $transmittal = WarehouseTransmittal::query()->create([
        'transmittal_no' => 'TR-WH-2026-0034',
        'warehouse_destination_id' => $warehouse->id,
        'tanggal' => '2026-11-28',
        'created_by' => $user->id,
    ]);

    $batch = WarehouseDeliveryBatch::query()->create([
        'batch_title' => 'Batch Test Search',
        'start_date' => '2026-11-28',
        'end_date' => '2026-11-28',
        'total_items' => 2,
        'uploaded_by' => $user->id,
    ]);

    $po1 = PurchaseOrderIssued::query()->create(['purchase_order_no' => '5300077678', 'item_no' => 10]);
    $do1 = DeliveryOrderReceipt::query()->create(['delivery_order_no' => 'SJ-7678-ALPHA', 'status' => 'GRS']);
    $detail1 = DeliveryOrderReceiptDetail::query()->create([
        'delivery_order_receipt_id' => $do1->id,
        'purchase_order_issued_id' => $po1->id,
        'item_no' => 10,
        'material_code' => '6047678',
        'description' => 'BEARING BALL SKF 6308',
        'quantity' => 5,
    ]);
    $batchItem1 = WarehouseDeliveryBatchItem::query()->create([
        'warehouse_delivery_batch_id' => $batch->id,
        'po_number' => '5300077678',
        'mat_doc_105' => '5007678111',
        'material_code' => '6047678',
        'description' => 'BEARING BALL SKF 6308',
        'reference_do_no' => 'SJ-7678-ALPHA',
        'status' => 'delivered',
        'delivery_order_receipt_id' => $do1->id,
        'delivery_order_receipt_detail_id' => $detail1->id,
        'warehouse_transmittal_id' => $transmittal->id,
    ]);

    $batchItem2 = WarehouseDeliveryBatchItem::query()->create([
        'warehouse_delivery_batch_id' => $batch->id,
        'po_number' => '5300099999',
        'mat_doc_105' => '5009999222',
        'material_code' => '7099999',
        'description' => 'VALVE GATE CARBON STEEL',
        'reference_do_no' => 'SJ-9999-BETA',
        'status' => 'delivered',
        'warehouse_transmittal_id' => $transmittal->id,
    ]);

    $item1 = WarehouseTransmittalItem::query()->create([
        'warehouse_transmittal_id' => $transmittal->id,
        'warehouse_delivery_batch_item_id' => $batchItem1->id,
        'delivery_order_receipt_detail_id' => $detail1->id,
    ]);
    $item2 = WarehouseTransmittalItem::query()->create([
        'warehouse_transmittal_id' => $transmittal->id,
        'warehouse_delivery_batch_item_id' => $batchItem2->id,
        'delivery_order_receipt_detail_id' => null,
    ]);

    Livewire::test(ItemsRelationManager::class, [
        'ownerRecord' => $transmittal,
        'pageClass' => EditWarehouseTransmittal::class,
    ])
        ->searchTable('7678')
        ->assertCanSeeTableRecords([$item1])
        ->assertCanNotSeeTableRecords([$item2])
        ->searchTable('SJ-9999-BETA')
        ->assertCanSeeTableRecords([$item2])
        ->assertCanNotSeeTableRecords([$item1])
        ->searchTable('5007678111')
        ->assertCanSeeTableRecords([$item1])
        ->assertCanNotSeeTableRecords([$item2])
        ->searchTable('VALVE GATE')
        ->assertCanSeeTableRecords([$item2])
        ->assertCanNotSeeTableRecords([$item1]);
});

test('SapMb51ImportService disambiguates partial deliveries on same PO and item by DO number and quantity, and PDF uses batchItem quantity first', function (): void {
    $poItem20 = PurchaseOrderIssued::query()->create([
        'purchase_order_no' => '5300063515',
        'item_no' => 20,
        'qty_po' => 65,
    ]);

    $doFirst = DeliveryOrderReceipt::query()->create(['delivery_order_no' => '234', 'status' => 'GRS']);
    $detailFirst = DeliveryOrderReceiptDetail::query()->create([
        'delivery_order_receipt_id' => $doFirst->id,
        'purchase_order_issued_id' => $poItem20->id,
        'item_no' => 20,
        'material_code' => '6049965',
        'description' => 'FILTER-REGULATOR',
        'quantity' => 39,
    ]);

    $doSecond = DeliveryOrderReceipt::query()->create(['delivery_order_no' => '63515', 'status' => 'GRS']);
    $detailSecond = DeliveryOrderReceiptDetail::query()->create([
        'delivery_order_receipt_id' => $doSecond->id,
        'purchase_order_issued_id' => $poItem20->id,
        'item_no' => 20,
        'material_code' => '6049965',
        'description' => 'FILTER-REGULATOR',
        'quantity' => 26,
    ]);

    $parsed = [
        [
            'mat_doc_105' => '5007440171',
            'po_number' => '5300063515',
            'item_no' => '20',
            'reference_do_no' => '234',
            'quantity' => 39.0,
        ],
        [
            'mat_doc_105' => '5007739484',
            'po_number' => '5300063515',
            'item_no' => '20',
            'reference_do_no' => '63515',
            'quantity' => 26.0,
        ],
    ];

    $service = app(SapMb51ImportService::class);
    $lookups = $service->buildDeliveryOrderLookup($parsed);

    $match1 = $service->matchFast($parsed[0], $lookups);
    $match2 = $service->matchFast($parsed[1], $lookups);

    expect($match1['delivery_order_receipt_detail_id'])->toBe($detailFirst->id)
        ->and($match2['delivery_order_receipt_detail_id'])->toBe($detailSecond->id);

    // Pastikan PDF transmittal tetap menampilkan qty aktual batchItem (26), meskipun detail terkait punya qty berbeda (39)
    $warehouse = WarehouseDestination::query()->create(['name' => 'Gudang Sparepart']);
    $user = User::query()->create([
        'name' => 'Admin PDF Test',
        'npk' => '2100099',
        'email' => 'pdf_qty_test@pupukkaltim.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $transmittal = WarehouseTransmittal::query()->create([
        'transmittal_no' => 'TR-WH/2026/11/9090',
        'warehouse_destination_id' => $warehouse->id,
        'tanggal' => '2026-11-28',
        'created_by' => $user->id,
    ]);
    $batch = WarehouseDeliveryBatch::query()->create([
        'batch_title' => 'Batch Partial',
        'start_date' => '2026-11-28',
        'end_date' => '2026-11-28',
        'total_items' => 1,
        'uploaded_by' => $user->id,
    ]);
    $batchItem26 = WarehouseDeliveryBatchItem::query()->create([
        'warehouse_delivery_batch_id' => $batch->id,
        'mat_doc_105' => '5007739484',
        'po_number' => '5300063515',
        'item_no' => '20',
        'material_code' => '6049965',
        'description' => 'FILTER-REGULATOR',
        'quantity' => 26,
        'uom' => 'EA',
        'reference_do_no' => '63515',
        'status' => 'delivered',
        'delivery_order_receipt_id' => $doFirst->id,
        'delivery_order_receipt_detail_id' => $detailFirst->id, // Sengaja tertaut ke detail qty 39
        'warehouse_transmittal_id' => $transmittal->id,
    ]);
    WarehouseTransmittalItem::query()->create([
        'warehouse_transmittal_id' => $transmittal->id,
        'warehouse_delivery_batch_item_id' => $batchItem26->id,
        'delivery_order_receipt_detail_id' => $detailFirst->id,
    ]);

    $transmittal->load(['items.detail.purchaseOrderIssued', 'items.batchItem', 'destination.pic']);
    $htmlSingle = view('pdf.transmittal', ['transmittal' => $transmittal])->render();
    $htmlBulk = view('pdf.transmittal_bulk', ['transmittals' => collect([$transmittal])])->render();

    expect($htmlSingle)->toContain('>26<')->not->toContain('>39<')
        ->and($htmlBulk)->toContain('>26<')->not->toContain('>39<');
});
