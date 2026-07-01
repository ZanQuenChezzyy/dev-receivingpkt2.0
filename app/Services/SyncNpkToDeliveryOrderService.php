<?php

namespace App\Services;

use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\MonitoringNpk;
use App\Models\PurchaseOrderIssued;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SyncNpkToDeliveryOrderService
{
    public function sync(MonitoringNpk $npk): DeliveryOrderReceipt
    {
        return DB::transaction(function () use ($npk) {
            $actorId = Auth::id() ?? $npk->getAttribute('created_by');

            /** @var PurchaseOrderIssued $anchor */
            $anchor = $npk->purchaseOrderIssued()->firstOrFail();

            // Find existing DO or create new
            $dor = DeliveryOrderReceipt::where('monitoring_npk_id', $npk->id)->first() ?? new DeliveryOrderReceipt;

            $tgl103 = $npk->getAttribute('purchase_order_103_date');
            
            // Calculate document_code
            $details = $npk->details()->get();
            $itemCountStr = '';
            if ($details && $details->count() > 0) {
                $itemCountStr = str_pad((string) $details->count(), 2, '0', STR_PAD_LEFT);
            }
            $poNo = $anchor->purchase_order_no;
            $doNo = $npk->getAttribute('delivery_oder_number');
            $recDate = $npk->getAttribute('received_date');
            $dateStr = $recDate ? Carbon::parse($recDate)->format('dmY') : '';
            $stage = $npk->getAttribute('stage');

            $parts = array_filter([$poNo, $itemCountStr, $doNo, $dateStr, $stage]);
            $documentCode = null;
            if (!empty($parts)) {
                $joinedString = implode('-', $parts);
                $upperString = strtoupper($joinedString);
                $documentCode = preg_replace('/[^A-Z0-9\-_]/', '', $upperString);
            }

            $payload = [
                'monitoring_npk_id' => $npk->id,
                'delivery_oder_no' => $doNo,
                'document_code' => $documentCode, // Ensure document_code is saved
                'location_id' => $npk->getAttribute('location_id'),
                'received_date' => $recDate,
                'stage' => $stage,
                'source_type' => 'Bahan Baku NPK',
                'status' => $dor->getAttribute('status') ?? 'Diterima', // initial status
                'post_103' => $tgl103 ? Carbon::parse($tgl103)->startOfDay() : null,
                'received_by' => $dor->getAttribute('received_by') ?? $actorId,
                'created_by' => $dor->getAttribute('created_by') ?? $actorId,
                'incoterms' => $anchor->getAttribute('incoterm') ?? $dor->getAttribute('incoterms'),
                'eta_date' => $anchor->getAttribute('delivery_date_po') ?? $dor->getAttribute('eta_date'),
            ];

            if (!$dor->exists) {
                $receiptMode = 'Standard';
                $isPhysicallyReceived = true;
                $physicalReceivedDate = $recDate;
                $stageUpper = strtoupper((string) $stage);

                if (str_contains($stageUpper, 'TERMIN')) {
                    $receiptMode = 'Termin';
                    $isPhysicallyReceived = false;
                    $physicalReceivedDate = null;
                } elseif (str_contains($stageUpper, 'DOF')) {
                    $receiptMode = 'DOF_Incoterm';
                    $isPhysicallyReceived = false;
                    $physicalReceivedDate = null;
                }

                $payload['receipt_mode'] = $receiptMode;
                $payload['is_physically_received'] = $isPhysicallyReceived;
                $payload['physical_received_date'] = $physicalReceivedDate;

                if ($poNo) {
                    $receiptQuery = DeliveryOrderReceipt::whereHas('deliveryOrderReceiptDetails.purchaseOrderIssued', function ($q) use ($poNo) {
                        $q->where('purchase_order_no', $poNo);
                    });
                    $payload['arrival_sequence'] = $receiptQuery->count() + 1;
                } else {
                    $payload['arrival_sequence'] = 1;
                }
            }

            $dor->fill($payload)->save();

            // NPK Details
            $details = $npk->details()->get();

            $expectedItemNos = [];

            foreach ($details as $d) {
                // Find PO item using anchor PO number and detail item no
                $po = PurchaseOrderIssued::where('purchase_order_no', $anchor->purchase_order_no)
                    ->where('item_no', $d->getAttribute('item_no'))
                    ->first();

                if (! $po) {
                    continue;
                }

                $poItemNo = (int) $po->getAttribute('item_no');
                $expectedItemNos[] = $poItemNo;

                $detail = DeliveryOrderReceiptDetail::query()->firstOrNew([
                    'delivery_order_receipt_id' => $dor->getAttribute('id'),
                    'item_no' => $poItemNo,
                ]);

                // Calculate total amount
                $quantity = (float) str_replace(',', '.', (string) $d->getAttribute('quantity'));
                $unitPrice = ($po->qty_po > 0) ? ((float) $po->total_amount_in_lc / (float) $po->qty_po) : (float) $po->net_price;
                $totalAmountSnapshot = $quantity * $unitPrice;

                $detail->fill([
                    'purchase_order_issued_id' => $po->id,
                    'quantity' => (string) $quantity,
                    'material_code' => $po->getAttribute('material_code'),
                    'description' => $po->getAttribute('description'),
                    'uoi' => $po->getAttribute('uoi'),
                    'mrp_type' => $po->getAttribute('mrp_type'),
                    'material_type' => $po->getAttribute('material_type'),
                    'aac' => $po->getAttribute('aac'),
                    'abc_indicator' => $po->getAttribute('abc_indicator'),
                    'requisitioner' => $po->getAttribute('requisitioner'),
                    'location_id' => $npk->getAttribute('location_id'),
                    'is_qty_tolerance' => $d->getAttribute('is_qty_tolerance') ?? false,
                    'total_amount_snapshot' => $totalAmountSnapshot,
                    'is_different_location' => false,
                ])->save();
            }

            if (! empty($expectedItemNos)) {
                DeliveryOrderReceiptDetail::where('delivery_order_receipt_id', $dor->getAttribute('id'))
                    ->whereNotIn('item_no', $expectedItemNos)
                    ->delete();
            }

            return $dor;
        });
    }
}
