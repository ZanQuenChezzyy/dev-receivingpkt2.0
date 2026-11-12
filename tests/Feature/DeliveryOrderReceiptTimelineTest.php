<?php

use App\Filament\Resources\DeliveryOrderReceipts\Schemas\DeliveryOrderReceiptInfolist;
use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\User;
use App\Models\WarehouseDeliveryBatch;
use App\Models\WarehouseDeliveryBatchItem;
use App\Models\WarehouseDestination;
use App\Models\WarehouseTransmittal;
use App\Models\WarehouseTransmittalItem;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema as DbSchema;
use Livewire\Component;

beforeEach(function () {
    if (! DbSchema::hasTable('users')) {
        DbSchema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('warehouse_destinations')) {
        DbSchema::create('warehouse_destinations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->foreignId('pic_id')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('warehouse_transmittals')) {
        DbSchema::create('warehouse_transmittals', function (Blueprint $table) {
            $table->id();
            $table->string('transmittal_no')->unique();
            $table->foreignId('warehouse_destination_id');
            $table->date('tanggal');
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('warehouse_delivery_batches')) {
        DbSchema::create('warehouse_delivery_batches', function (Blueprint $table) {
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

    if (! DbSchema::hasTable('warehouse_delivery_batch_items')) {
        DbSchema::create('warehouse_delivery_batch_items', function (Blueprint $table) {
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

    if (! DbSchema::hasTable('warehouse_transmittal_items')) {
        DbSchema::create('warehouse_transmittal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_transmittal_id');
            $table->foreignId('warehouse_delivery_batch_item_id')->nullable();
            $table->foreignId('delivery_order_receipt_detail_id')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('delivery_order_receipts')) {
        DbSchema::create('delivery_order_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_order_no');
            $table->string('document_code')->nullable();
            $table->date('received_date')->nullable();
            $table->date('physical_received_date')->nullable();
            $table->string('receipt_mode')->default('Standard');
            $table->string('source_type')->nullable();
            $table->string('status')->default('Pending');
            $table->dateTime('post_103')->nullable();
            $table->string('qr_103_code')->nullable();
            $table->dateTime('pending_date')->nullable();
            $table->dateTime('pending_resolved_date')->nullable();
            $table->string('delay_reason')->nullable();
            $table->text('delay_notes')->nullable();
            $table->foreignId('received_by')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('delivery_order_receipt_details')) {
        DbSchema::create('delivery_order_receipt_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_receipt_id');
            $table->foreignId('purchase_order_issued_id')->nullable();
            $table->string('item_no')->nullable();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->string('material_code')->nullable();
            $table->string('description')->nullable();
            $table->string('uoi', 10)->nullable();
            $table->string('mrp_type')->nullable();
            $table->string('material_type')->nullable();
            $table->foreignId('warehouse_destination_id')->nullable();
            $table->foreignId('location_id')->nullable();
            $table->boolean('is_different_location')->default(false);
            $table->boolean('is_qty_tolerance')->default(false);
            $table->decimal('total_amount_snapshot', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('qc_histories')) {
        DbSchema::create('qc_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_receipt_id');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('grs_rdtv_items')) {
        DbSchema::create('grs_rdtv_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_receipt_id')->nullable();
            $table->foreignId('grs_rdtv_id')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('delivery_order_receipt_delay_logs')) {
        DbSchema::create('delivery_order_receipt_delay_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_receipt_id');
            $table->string('delay_reason')->nullable();
            $table->text('delay_notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! DbSchema::hasTable('delivery_order_receipt_termins')) {
        DbSchema::create('delivery_order_receipt_termins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_receipt_id');
            $table->string('stage')->nullable();
            $table->decimal('percentage', 5, 2)->default(0);
            $table->dateTime('post_103')->nullable();
            $table->string('qr_103_code')->nullable();
            $table->timestamps();
        });
    }
});

class TestHasSchemasComponent extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public function render()
    {
        return '<div></div>';
    }
}

test('timeline shows Dikirim ke Gudang when DeliveryOrderReceipt items are linked to a WarehouseTransmittal', function () {
    $user = User::create([
        'name' => 'Budi Gudang',
        'email' => 'budi@pupuk-kaltim.com',
        'password' => bcrypt('password'),
    ]);
    Auth::login($user);

    $destination = WarehouseDestination::create([
        'name' => 'Gudang Sparepart 3P01',
        'code' => '3P01',
    ]);

    $do = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'DO-PKT-2026-001',
        'document_code' => 'DOC-DO-001',
        'received_date' => '2026-11-01',
        'physical_received_date' => '2026-11-01',
    ]);

    $detail = DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $do->id,
        'material_code' => 'MAT-BEARING-01',
        'description' => 'BEARING BALL 6205',
        'quantity' => 10,
        'uoi' => 'EA',
        'warehouse_destination_id' => $destination->id,
    ]);

    $transmittal = WarehouseTransmittal::create([
        'transmittal_no' => 'TR-WH/2026/11/0001',
        'warehouse_destination_id' => $destination->id,
        'tanggal' => '2026-11-10',
        'created_by' => $user->id,
    ]);

    $batch = WarehouseDeliveryBatch::create([
        'batch_title' => 'Batch 105 Nov',
        'start_date' => '2026-11-01',
        'end_date' => '2026-11-10',
    ]);

    $batchItem = WarehouseDeliveryBatchItem::create([
        'warehouse_delivery_batch_id' => $batch->id,
        'mat_doc_105' => '5007477274',
        'item_no' => '10',
        'delivery_order_receipt_id' => $do->id,
        'delivery_order_receipt_detail_id' => $detail->id,
        'warehouse_transmittal_id' => $transmittal->id,
        'status' => 'delivered',
    ]);

    WarehouseTransmittalItem::create([
        'warehouse_transmittal_id' => $transmittal->id,
        'warehouse_delivery_batch_item_id' => $batchItem->id,
        'delivery_order_receipt_detail_id' => $detail->id,
        'created_at' => Carbon::parse('2026-11-10 14:30:00'),
    ]);

    // Test timeline_view entry
    $livewire = new TestHasSchemasComponent;
    $schema = Schema::make($livewire)->record($do);
    $configured = DeliveryOrderReceiptInfolist::configure($schema);
    $timelineSection = collect($configured->getComponents())
        ->flatMap(fn ($c) => method_exists($c, 'getChildComponents') ? $c->getChildComponents() : [$c])
        ->first(fn ($c) => method_exists($c, 'getHeading') && str_contains((string) $c->getHeading(), 'Timeline'));

    expect($timelineSection)->not->toBeNull();

    $timelineEntry = collect($timelineSection->getChildComponents())
        ->first(fn ($c) => method_exists($c, 'getName') && $c->getName() === 'timeline_view');

    expect($timelineEntry)->not->toBeNull();

    $html = $timelineEntry->getState();

    expect($html)
        ->toContain('Dikirim ke Gudang: Gudang Sparepart 3P01')
        ->toContain('TR-WH/2026/11/0001')
        ->toContain('1 item material')
        ->toContain('GRS: 5007477274')
        ->toContain('Budi Gudang')
        ->toContain('bg-teal-500');

    // Test dikirim_ke_gudang grid card
    $grid = collect($timelineSection->getChildComponents())
        ->first(fn ($c) => $c instanceof Grid);

    expect($grid)->not->toBeNull();

    $gudangCard = collect($grid->getChildComponents())
        ->first(fn ($c) => method_exists($c, 'getName') && $c->getName() === 'dikirim_ke_gudang');

    expect($gudangCard)->not->toBeNull();

    $cardState = $gudangCard->getState();
    expect($cardState)->toContain('Gudang Sparepart 3P01');
});

test('timeline does not show Dikirim ke Gudang when items have not been sent', function () {
    $do = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'DO-PKT-2026-003',
        'document_code' => 'DOC-DO-003',
        'received_date' => '2026-11-01',
    ]);

    $detail = DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $do->id,
        'material_code' => 'MAT-VALVE-01',
        'description' => 'GATE VALVE 2 INCH',
        'quantity' => 2,
        'uoi' => 'EA',
    ]);

    $livewire = new TestHasSchemasComponent;
    $schema = Schema::make($livewire)->record($do);
    $configured = DeliveryOrderReceiptInfolist::configure($schema);
    $timelineSection = collect($configured->getComponents())
        ->flatMap(fn ($c) => method_exists($c, 'getChildComponents') ? $c->getChildComponents() : [$c])
        ->first(fn ($c) => method_exists($c, 'getHeading') && str_contains((string) $c->getHeading(), 'Timeline'));

    $timelineEntry = collect($timelineSection->getChildComponents())
        ->first(fn ($c) => method_exists($c, 'getName') && $c->getName() === 'timeline_view');

    $html = $timelineEntry->getState();
    expect($html)->not->toContain('Dikirim ke Gudang');

    $grid = collect($timelineSection->getChildComponents())
        ->first(fn ($c) => $c instanceof Grid);

    $gudangCard = collect($grid->getChildComponents())
        ->first(fn ($c) => method_exists($c, 'getName') && $c->getName() === 'dikirim_ke_gudang');

    expect($gudangCard->getState())->toBeNull();
});

test('timeline shows multiple warehouse transmittals when items are sent to different warehouses', function () {
    $destination1 = WarehouseDestination::create(['name' => 'Gudang 1 - Sparepart']);
    $destination2 = WarehouseDestination::create(['name' => 'Gudang 2 - Kimia']);

    $do = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'DO-PKT-2026-004',
        'document_code' => 'DOC-DO-004',
        'received_date' => '2026-11-01',
    ]);

    $detail1 = DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $do->id,
        'material_code' => 'MAT-01',
        'description' => 'SPAREPART A',
        'quantity' => 1,
    ]);

    $detail2 = DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $do->id,
        'material_code' => 'MAT-02',
        'description' => 'CHEMICAL B',
        'quantity' => 1,
    ]);

    $transmittal1 = WarehouseTransmittal::create([
        'transmittal_no' => 'TR-WH/2026/11/0010',
        'warehouse_destination_id' => $destination1->id,
        'tanggal' => '2026-11-05',
    ]);

    $transmittal2 = WarehouseTransmittal::create([
        'transmittal_no' => 'TR-WH/2026/11/0011',
        'warehouse_destination_id' => $destination2->id,
        'tanggal' => '2026-11-06',
    ]);

    WarehouseTransmittalItem::create([
        'warehouse_transmittal_id' => $transmittal1->id,
        'delivery_order_receipt_detail_id' => $detail1->id,
    ]);

    WarehouseTransmittalItem::create([
        'warehouse_transmittal_id' => $transmittal2->id,
        'delivery_order_receipt_detail_id' => $detail2->id,
    ]);

    $livewire = new TestHasSchemasComponent;
    $schema = Schema::make($livewire)->record($do);
    $configured = DeliveryOrderReceiptInfolist::configure($schema);
    $timelineSection = collect($configured->getComponents())
        ->flatMap(fn ($c) => method_exists($c, 'getChildComponents') ? $c->getChildComponents() : [$c])
        ->first(fn ($c) => method_exists($c, 'getHeading') && str_contains((string) $c->getHeading(), 'Timeline'));

    $timelineEntry = collect($timelineSection->getChildComponents())
        ->first(fn ($c) => method_exists($c, 'getName') && $c->getName() === 'timeline_view');

    $html = $timelineEntry->getState();
    expect($html)
        ->toContain('Dikirim ke Gudang: Gudang 1 - Sparepart')
        ->toContain('TR-WH/2026/11/0010')
        ->toContain('Dikirim ke Gudang: Gudang 2 - Kimia')
        ->toContain('TR-WH/2026/11/0011');
});

test('DeliveryOrderReceipt model has warehouseDeliveryBatchItems relationship', function () {
    $do = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'DO-PKT-2026-002',
    ]);

    expect($do->warehouseDeliveryBatchItems())->toBeInstanceOf(HasMany::class);
});
