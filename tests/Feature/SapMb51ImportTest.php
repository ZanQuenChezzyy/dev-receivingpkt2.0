<?php

use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\PurchaseOrderIssued;
use App\Models\User;
use App\Models\WarehouseDeliveryBatch;
use App\Models\WarehouseDeliveryBatchItem;
use App\Services\SapMb51ImportService;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('warehouse_delivery_batch_items');
    Schema::dropIfExists('warehouse_delivery_batches');
    Schema::dropIfExists('users');

    Schema::create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('warehouse_delivery_batches', function ($table) {
        $table->id();
        $table->string('batch_title');
        $table->date('start_date');
        $table->date('end_date');
        $table->integer('total_items')->default(0);
        $table->decimal('total_value', 18, 2)->default(0);
        $table->string('file_name')->nullable();
        $table->string('file_path')->nullable();
        $table->string('status')->default('active');
        $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
        $table->text('notes')->nullable();
        $table->timestamps();
    });

    Schema::create('purchase_order_issueds', function ($table) {
        $table->id();
        $table->string('purchase_order_no')->nullable();
        $table->integer('item_no')->nullable();
        $table->timestamps();
    });

    Schema::create('delivery_order_receipts', function ($table) {
        $table->id();
        $table->string('delivery_order_no')->nullable();
        $table->string('post_103')->nullable();
        $table->timestamps();
    });

    Schema::create('delivery_order_receipt_details', function ($table) {
        $table->id();
        $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
        $table->unsignedBigInteger('purchase_order_issued_id')->nullable();
        $table->timestamps();
    });

    Schema::create('warehouse_delivery_batch_items', function ($table) {
        $table->id();
        $table->foreignId('warehouse_delivery_batch_id')->constrained('warehouse_delivery_batches')->cascadeOnDelete();
        $table->date('posting_date');
        $table->string('material_code')->nullable();
        $table->text('description');
        $table->string('po_number', 50);
        $table->string('item_no', 20);
        $table->string('ref_doc_103', 50)->nullable();
        $table->string('mat_doc_105', 50)->index();
        $table->string('doc_year', 4)->nullable();
        $table->decimal('quantity', 15, 3);
        $table->string('uom', 20);
        $table->decimal('amount', 18, 2)->default(0);
        $table->string('storage_location', 50)->nullable();
        $table->string('plant', 20)->nullable();
        $table->string('vendor_code', 50)->nullable();
        $table->string('reference_do_no', 100)->nullable();
        $table->string('storage_text', 100)->nullable();
        $table->string('status')->default('pending');
        $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
        $table->unsignedBigInteger('delivery_order_receipt_detail_id')->nullable();
        $table->unsignedBigInteger('warehouse_transmittal_id')->nullable();
        $table->timestamps();
    });
});

test('sap mb51 service can import csv and skip summary footer rows', function () {
    $user = User::create([
        'name' => 'Tester',
        'email' => 'tester@example.com',
        'password' => bcrypt('password'),
    ]);

    $csvContent = <<<'CSV'
Document Date,Posting Date,Entry Date,Material,Material Description,Movement Type,Material Doc.Item,Item,Purchase Order,Reference Document,Material Document,Qty in Un. of Entry,Unit of Entry,Document Header Text,Amount in LC,Text,Reference,Vendor,User name,Plant,Name 1,Storage Location,Movement Type Text,Special Stock,Unloading Point,Asset,Subnumber,Counter,Order,Routing number for operations,Qty in OPUn,Order Price Unit,Order Unit,Qty in order unit,Company Code,Valuation Type,Batch,Time of Entry,Smart Number,Ext. Amount in Local Currency,Sales Value,Reason for Movement,Stock Type,Sales Order,Sales Order Schedule,Sales Order Item,Cost Center,Customer,Movement indicator,Consumption,Receipt Indicator,Delivery,Reference Doc. Item,Sales Order2,Sales order item3,Base Unit of Measure,Quantity,Material Doc. Year,Network,Operation/Activity,WBS Element,Reservation,Item No.Stock Transfer Reserv.,Debit/Credit Ind.,Receiving Stor. Loc.,Receiving Plant,Trans./Event Type,Sales Value inc. VAT,Currency,Goods Receipt/Issue Slip,Item automatically created,Original Line Item,Multiple Account Assignment
2026-07-20,2026-07-30,2026-07-30,,CATHODIC PROTECTION TESTING SET,105,1,10,5300063742,5007448635,5007477274,1,SET,Reistek,148000000,FLOOR-A,63742,1000022793,D3140000OP13,D601,PERGUDANGAN & PEMELIHARAAN,,GR asset from blckd,,gd receiving,51017100,0,0,,0,1,SET,SET,1,D000,,,07:49:02,,0,0,0,,,0,0,,,B,A,,,1,,0,,0,2026,,,,0,0,S,,,WE,0,IDR,,,0,
2026-07-07,2026-07-29,2026-07-29,,"FIN TUBE Ø 31,8 MM TYPE 1",105,5,50,5300061794,5007415139,5007473725,40,EA,ISTEK,420000000,DALAM KANTOR RECEIVING,DOKT011-VII2026,1000026462,D3140000OP13,D601,PERGUDANGAN & PEMELIHARAAN,,GR acc.ass.fm blckd,,GD RECEIVING,,,0,,0,40,EA,EA,40,D000,,,07:18:22,,0,0,0,,,0,0,,,B,V,,,5,,0,,0,2026,,,D-12503,0,0,S,,,WE,0,IDR,,,0,
2026-07-07,2026-07-29,2026-07-29,,"FIN TUBE Ø 31,8 MM TYPE 2",105,6,60,5300061794,5007415139,5007473725,160,EA,ISTEK,1942000000,DALAM KANTOR RECEIVING,DOKT011-VII2026,1000026462,D3140000OP13,D601,PERGUDANGAN & PEMELIHARAAN,,GR acc.ass.fm blckd,,GD RECEIVING,,,0,,0,160,EA,EA,160,D000,,,07:18:22,,0,0,0,,,0,0,,,B,V,,,6,,0,,0,2026,,,D-12503,0,0,S,,,WE,0,IDR,,,0,
2026-07-07,2026-07-29,2026-07-29,4003464,DIESEL FUEL:HSD FUEL,105,1,10,5000009235,5007415142,5007474418,15947.19,L,INSPEKSI TEKNIK,185768816,B 9021 TFV,26/0146567,1000014670,D3140000OP22,D601,PERGUDANGAN & PEMELIHARAAN,3P01,GR from blocked stck,,GUDANG RECEIVING,,,0,,0,15947.19,L,L,15947.19,D000,,,08:52:35,,0,0,0,,,0,0,,,B,,,,1,,0,L,15947.19,2026,,,,0,0,S,,,WE,0,IDR,,,0,
,,,,,,,,,,,84,BT,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,BT,79,,,,,,,,,,,,,,,,
,,,,,,,,,,,1188958.75,EA,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,EA,1185209,,,,,,,,,,,,,,,,
CSV;

    $tempPath = tempnam(sys_get_temp_dir(), 'mb51_test_').'.csv';
    file_put_contents($tempPath, $csvContent);

    $service = new SapMb51ImportService;
    $batch = $service->import([
        'file_path' => $tempPath,
        'file_name' => 'sample_mb51.csv',
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
        'uploaded_by' => $user->id,
    ]);

    @unlink($tempPath);

    expect($batch)->toBeInstanceOf(WarehouseDeliveryBatch::class)
        ->and($batch->total_items)->toBe(4) // 4 valid rows, 2 footer rows skipped
        ->and($batch->items()->count())->toBe(4);

    // Verify multi-item material document 5007473725 has 2 items
    $multiItems = WarehouseDeliveryBatchItem::where('mat_doc_105', '5007473725')->get();
    expect($multiItems)->toHaveCount(2)
        ->and($multiItems->pluck('item_no')->toArray())->toBe(['50', '60']);
});

test('sap mb51 service maintains consistent column count when only some items match DO', function () {
    $user = User::create([
        'name' => 'Tester2',
        'email' => 'tester2@example.com',
        'password' => bcrypt('password'),
    ]);

    // Create a DO that matches the second item (PO 5300061794, Item 50)
    $po = PurchaseOrderIssued::create([
        'purchase_order_no' => '5300061794',
        'item_no' => 50,
    ]);

    $do = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'DO-MATCH-1',
    ]);

    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $do->id,
        'purchase_order_issued_id' => $po->id,
    ]);

    $csvContent = <<<'CSV'
Posting Date,Material,Material Description,Item,Purchase Order,Reference Document,Material Document,Qty in Un. of Entry,Unit of Entry,Amount in LC
2026-07-20,,FIRST ITEM NO MATCH,10,5300063742,5007448635,5007477274,1,SET,1000
2026-07-07,,"MATCHES DO",50,5300061794,5007415139,5007473725,40,EA,2000
2026-07-07,,"THIRD ITEM NO MATCH",60,5300061794,5007415139,5007473725,160,EA,3000
CSV;

    $tempPath = tempnam(sys_get_temp_dir(), 'mb51_partial_match_').'.csv';
    file_put_contents($tempPath, $csvContent);

    $service = new SapMb51ImportService;
    $batch = $service->import([
        'file_path' => $tempPath,
        'file_name' => 'partial_match.csv',
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
        'uploaded_by' => $user->id,
    ]);

    @unlink($tempPath);

    expect($batch->items()->count())->toBe(3);

    $matchedItem = WarehouseDeliveryBatchItem::where('item_no', '50')->first();
    $unmatchedItem = WarehouseDeliveryBatchItem::where('item_no', '10')->first();

    expect($matchedItem->delivery_order_receipt_id)->toBe($do->id)
        ->and($unmatchedItem->delivery_order_receipt_id)->toBeNull();
});

test('sap mb51 service can import user sample xlsx file with excel serial dates', function () {
    $filePath = 'C:/Users/ander/.gemini/antigravity/brain/b44274a9-3459-47e1-8eed-de40f636cebd/.user_uploaded/media_1794401911558_e00cc47e.xlsx';
    if (! file_exists($filePath)) {
        $this->markTestSkipped('User sample file not found');
    }

    $user = User::create([
        'name' => 'Tester Real File',
        'email' => 'realfile@example.com',
        'password' => bcrypt('password'),
    ]);

    $service = new SapMb51ImportService;
    $batch = $service->import([
        'file_path' => $filePath,
        'file_name' => 'export_mb51.XLSX',
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
        'uploaded_by' => $user->id,
    ]);

    expect($batch)->toBeInstanceOf(WarehouseDeliveryBatch::class)
        ->and($batch->total_items)->toBeGreaterThan(50)
        ->and($batch->items()->count())->toBe($batch->total_items);
});


