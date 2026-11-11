<?php

use App\Filament\Pages\ScanWarehouseTransmittal;
use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\User;
use App\Models\WarehouseDeliveryBatch;
use App\Models\WarehouseDeliveryBatchItem;
use App\Models\WarehouseDestination;
use App\Models\WarehouseTransmittal;
use App\Models\WarehouseTransmittalItem;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
    // Ensure tables exist for testing environment
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

    if (! Schema::hasTable('warehouse_destinations')) {
        Schema::create('warehouse_destinations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->foreignId('pic_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_transmittals')) {
        Schema::create('warehouse_transmittals', function (Blueprint $table) {
            $table->id();
            $table->string('transmittal_no')->unique();
            $table->foreignId('warehouse_destination_id');
            $table->date('tanggal');
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_delivery_batches')) {
        Schema::create('warehouse_delivery_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_title');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('total_items')->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('uploaded_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_delivery_batch_items')) {
        Schema::create('warehouse_delivery_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_delivery_batch_id');
            $table->date('posting_date')->nullable();
            $table->string('material_code')->nullable();
            $table->text('description')->nullable();
            $table->string('po_number')->nullable();
            $table->string('item_no')->nullable();
            $table->string('ref_doc_103')->nullable();
            $table->string('mat_doc_105');
            $table->string('doc_year', 4)->nullable();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->string('uom', 10)->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('storage_location')->nullable();
            $table->string('plant')->nullable();
            $table->string('vendor_code')->nullable();
            $table->string('reference_do_no')->nullable();
            $table->string('storage_text')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('delivery_order_receipt_id')->nullable();
            $table->foreignId('delivery_order_receipt_detail_id')->nullable();
            $table->foreignId('warehouse_transmittal_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_transmittal_items')) {
        Schema::create('warehouse_transmittal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_transmittal_id');
            $table->foreignId('warehouse_delivery_batch_item_id')->nullable();
            $table->foreignId('delivery_order_receipt_detail_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipts')) {
        Schema::create('delivery_order_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_order_no');
            $table->string('document_code')->nullable();
            $table->timestamps();
        });
    }
});

test('parseScannedCode correctly strips 4-digit year or extracts 10-digit 105 document', function () {
    expect(ScanWarehouseTransmittal::parseScannedCode('50074772742026'))->toBe('5007477274')
        ->and(ScanWarehouseTransmittal::parseScannedCode('5007477274'))->toBe('5007477274')
        ->and(ScanWarehouseTransmittal::parseScannedCode('GRS-50074772742026'))->toBe('5007477274')
        ->and(ScanWarehouseTransmittal::parseScannedCode(''))->toBeNull();
});

test('generateTransmittalNo follows TR-WH/YYYY/MM/XXXX format with sequence increment', function () {
    $now = Carbon::create(2026, 7, 1);
    $generated1 = ScanWarehouseTransmittal::generateTransmittalNo($now);
    expect($generated1)->toBe('TR-WH/2026/07/0001');

    WarehouseTransmittal::create([
        'transmittal_no' => 'TR-WH/2026/07/0001',
        'warehouse_destination_id' => 1,
        'tanggal' => $now->toDateString(),
    ]);

    $generated2 = ScanWarehouseTransmittal::generateTransmittalNo($now);
    expect($generated2)->toBe('TR-WH/2026/07/0002');
});

test('scanning 105 code creates transmittal and adds all multi-items at once', function () {
    $user = User::create([
        'name' => 'Operator Gudang',
        'email' => 'operator@pupuk-kaltim.com',
        'password' => bcrypt('secret'),
    ]);
    Auth::login($user);

    $destination = WarehouseDestination::create(['name' => 'Gudang Pusat']);

    $batch = WarehouseDeliveryBatch::create([
        'batch_title' => 'Batch Minggu 1',
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-07',
        'total_items' => 2,
    ]);

    // 1 Material Document 105 has 2 items (Item 10 and Item 20)
    $item1 = WarehouseDeliveryBatchItem::create([
        'warehouse_delivery_batch_id' => $batch->id,
        'mat_doc_105' => '5007473725',
        'item_no' => '10',
        'material_code' => 'MAT-001',
        'description' => 'BEARING 6205',
        'quantity' => 5,
        'status' => 'pending',
    ]);

    $item2 = WarehouseDeliveryBatchItem::create([
        'warehouse_delivery_batch_id' => $batch->id,
        'mat_doc_105' => '5007473725',
        'item_no' => '20',
        'material_code' => 'MAT-002',
        'description' => 'SEAL RING',
        'quantity' => 10,
        'status' => 'pending',
    ]);

    // Test Livewire component processScan with 14-digit scanned barcode (Code 128 from GRS)
    Livewire::test(ScanWarehouseTransmittal::class)
        ->set('data.warehouse_destination_id', $destination->id)
        ->call('processScan', '50074737252026')
        ->assertDispatched('play-success-sound');

    // Both items must be delivered
    expect($item1->fresh()->status)->toBe('delivered')
        ->and($item2->fresh()->status)->toBe('delivered')
        ->and($item1->fresh()->warehouse_transmittal_id)->not->toBeNull()
        ->and($item2->fresh()->warehouse_transmittal_id)->not->toBeNull();

    // Check WarehouseTransmittal record
    $transmittal = WarehouseTransmittal::where('warehouse_destination_id', $destination->id)->first();
    expect($transmittal)->not->toBeNull()
        ->and($transmittal->items()->count())->toBe(2);

    // Batch progress updated
    expect($batch->fresh()->status)->toBe('completed');
});

test('user can delete an item from active transmittal and status returns to pending', function () {
    $user = User::create([
        'name' => 'Operator Gudang',
        'email' => 'operator2@pupuk-kaltim.com',
        'password' => bcrypt('secret'),
    ]);
    Auth::login($user);

    $destination = WarehouseDestination::create(['name' => 'Gudang 1']);

    $batch = WarehouseDeliveryBatch::create([
        'batch_title' => 'Batch Test',
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-07',
        'total_items' => 1,
    ]);

    $batchItem = WarehouseDeliveryBatchItem::create([
        'warehouse_delivery_batch_id' => $batch->id,
        'mat_doc_105' => '5007479999',
        'item_no' => '10',
        'material_code' => 'MAT-999',
        'description' => 'TEST ITEM',
        'quantity' => 1,
        'status' => 'pending',
    ]);

    $component = Livewire::test(ScanWarehouseTransmittal::class)
        ->set('data.warehouse_destination_id', $destination->id)
        ->call('processScan', '50074799992026');

    $transmittalItem = WarehouseTransmittalItem::first();
    expect($transmittalItem)->not->toBeNull();

    // Now delete this item
    $component->call('deleteItem', $transmittalItem->id);

    expect(WarehouseTransmittalItem::count())->toBe(0)
        ->and($batchItem->fresh()->status)->toBe('pending')
        ->and($batchItem->fresh()->warehouse_transmittal_id)->toBeNull();
});

test('scanning 105 code preserves linkage to delivery order receipt for monitoring', function () {
    $user = User::create([
        'name' => 'Operator Gudang',
        'email' => 'operator3@pupuk-kaltim.com',
        'password' => bcrypt('secret'),
    ]);
    Auth::login($user);

    $destination = WarehouseDestination::create(['name' => 'Gudang Kimia']);

    $do = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'DO-PKT-2026-0099',
        'document_code' => 'DOC-0099',
    ]);

    $batch = WarehouseDeliveryBatch::create([
        'batch_title' => 'Batch Terhubung DO',
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-07',
        'total_items' => 1,
    ]);

    $batchItem = WarehouseDeliveryBatchItem::create([
        'warehouse_delivery_batch_id' => $batch->id,
        'mat_doc_105' => '5007481111',
        'item_no' => '10',
        'material_code' => 'MAT-CHEM-01',
        'description' => 'METHANOL GRADE A',
        'quantity' => 100,
        'status' => 'pending',
        'delivery_order_receipt_id' => $do->id,
    ]);

    Livewire::test(ScanWarehouseTransmittal::class)
        ->set('data.warehouse_destination_id', $destination->id)
        ->call('processScan', '50074811112026')
        ->assertDispatched('play-success-sound');

    $transmittalItem = WarehouseTransmittalItem::first();
    expect($transmittalItem)->not->toBeNull()
        ->and($transmittalItem->batchItem->delivery_order_receipt_id)->toBe($do->id)
        ->and($transmittalItem->batchItem->deliveryOrderReceipt->delivery_order_no)->toBe('DO-PKT-2026-0099');
});

test('deleting transmittal rolls back batch items status to pending', function () {
    $destination = WarehouseDestination::create(['name' => 'Gudang Pusat']);

    $batch = WarehouseDeliveryBatch::create([
        'batch_title' => 'Batch Test Delete Transmittal',
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-07',
        'total_items' => 1,
    ]);

    $transmittal = WarehouseTransmittal::create([
        'transmittal_no' => 'TR-WH/2026/07/9999',
        'warehouse_destination_id' => $destination->id,
        'tanggal' => '2026-07-01',
    ]);

    $batchItem = WarehouseDeliveryBatchItem::create([
        'warehouse_delivery_batch_id' => $batch->id,
        'mat_doc_105' => '5007471234',
        'item_no' => '10',
        'material_code' => 'MAT-1234',
        'description' => 'TEST BEARING',
        'quantity' => 2,
        'status' => 'delivered',
        'warehouse_transmittal_id' => $transmittal->id,
    ]);

    WarehouseTransmittalItem::create([
        'warehouse_transmittal_id' => $transmittal->id,
        'warehouse_delivery_batch_item_id' => $batchItem->id,
    ]);

    $batch->updateProgressStatus();
    expect($batch->fresh()->status)->toBe('completed');

    // Delete the transmittal
    $transmittal->delete();

    expect($batchItem->fresh()->status)->toBe('pending')
        ->and($batchItem->fresh()->warehouse_transmittal_id)->toBeNull()
        ->and($batch->fresh()->status)->toBe('pending');
});

test('scanning item updates warehouse destination on DO detail and unlinking clears it', function () {
    $user = User::create([
        'name' => 'Operator Sync',
        'email' => 'sync@pupuk-kaltim.com',
        'password' => bcrypt('secret'),
    ]);
    Auth::login($user);

    $destination = WarehouseDestination::create(['name' => 'Gudang 3']);

    if (! Schema::hasTable('delivery_order_receipt_details')) {
        Schema::create('delivery_order_receipt_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_receipt_id')->nullable();
            $table->foreignId('warehouse_destination_id')->nullable();
            $table->timestamps();
        });
    }

    $doDetail = DeliveryOrderReceiptDetail::create();

    $batch = WarehouseDeliveryBatch::create([
        'batch_title' => 'Batch Sync DO Detail',
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-07',
        'total_items' => 1,
    ]);

    $batchItem = WarehouseDeliveryBatchItem::create([
        'warehouse_delivery_batch_id' => $batch->id,
        'mat_doc_105' => '5007487777',
        'item_no' => '10',
        'material_code' => 'MAT-SYNC',
        'description' => 'SYNC TEST',
        'quantity' => 1,
        'status' => 'pending',
        'delivery_order_receipt_detail_id' => $doDetail->id,
    ]);

    $component = Livewire::test(ScanWarehouseTransmittal::class)
        ->set('data.warehouse_destination_id', $destination->id)
        ->call('processScan', '50074877772026');

    expect($doDetail->fresh()->warehouse_destination_id)->toBe($destination->id);

    // Delete item from transmittal
    $transmittalItem = WarehouseTransmittalItem::first();
    $component->call('deleteItem', $transmittalItem->id);

    expect($doDetail->fresh()->warehouse_destination_id)->toBeNull();
});

test('can list available transmittals for today and switch between them', function () {
    $destination = WarehouseDestination::create(['name' => 'Gudang Pupuk Curah']);
    $today = now()->toDateString();

    $t1 = WarehouseTransmittal::create([
        'transmittal_no' => 'TR-WH/2026/07/0001',
        'warehouse_destination_id' => $destination->id,
        'tanggal' => $today,
    ]);

    $t2 = WarehouseTransmittal::create([
        'transmittal_no' => 'TR-WH/2026/07/0002',
        'warehouse_destination_id' => $destination->id,
        'tanggal' => $today,
    ]);

    $component = Livewire::test(ScanWarehouseTransmittal::class)
        ->set('data.warehouse_destination_id', $destination->id)
        ->set('data.tanggal', $today);

    $available = $component->instance()->availableTransmittals;
    expect($available)->toHaveCount(2)
        ->and($component->get('transmittalId'))->toBe($t2->id);

    // Switch to first transmittal
    $component->call('selectTransmittal', $t1->id);
    expect($component->get('transmittalId'))->toBe($t1->id)
        ->and($component->get('transmittal_no'))->toBe('TR-WH/2026/07/0001');

    // Switch back to second transmittal
    $component->call('selectTransmittal', $t2->id);
    expect($component->get('transmittalId'))->toBe($t2->id)
        ->and($component->get('transmittal_no'))->toBe('TR-WH/2026/07/0002');
});

test('antrean pengiriman query ignores null delivery_order_receipt_detail_id without sql not in trap', function () {
    $destination = WarehouseDestination::create(['name' => 'Gudang Pusat']);

    $transmittal = WarehouseTransmittal::create([
        'transmittal_no' => 'TR-WH/2026/07/5555',
        'warehouse_destination_id' => $destination->id,
        'tanggal' => now()->toDateString(),
    ]);

    // Create item with NULL delivery_order_receipt_detail_id (typical of unlinked MB51 items)
    WarehouseTransmittalItem::create([
        'warehouse_transmittal_id' => $transmittal->id,
        'delivery_order_receipt_detail_id' => null,
    ]);

    // Verify subquery with whereNotNull safely returns empty and does not break NOT IN
    $excludedIds = WarehouseTransmittalItem::whereNotNull('delivery_order_receipt_detail_id')
        ->select('delivery_order_receipt_detail_id');

    expect($excludedIds->count())->toBe(0);

    // Verify that a dummy detail is NOT excluded
    $availableQuery = DeliveryOrderReceiptDetail::whereNotIn('id', $excludedIds);
    expect(strtolower($availableQuery->toSql()))
        ->toContain('not in')
        ->toContain('is not null');
});


