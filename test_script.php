<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$po_no = '5300062531';
$poItemIds = App\Models\PurchaseOrderIssued::where('purchase_order_no', $po_no)->pluck('id', 'item_no')->toArray();
echo "PO ITEMS:\n";
print_r($poItemIds);

$rawItems = App\Models\DeliveryOrderReceiptDetail::whereIn('purchase_order_issued_id', array_values($poItemIds))->get(['id', 'purchase_order_issued_id', 'item_no', 'material_code'])->toArray();
echo "\nRAW ITEMS:\n";
print_r($rawItems);

$grouped = collect($rawItems)->groupBy('purchase_order_issued_id')->count();
echo "\nGROUPED COUNT: $grouped\n";
