<?php

namespace App\Services;

use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\MonitoringChemical;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SyncChemicalToDeliveryOrderService
{
    public function sync(MonitoringChemical $chemical): DeliveryOrderReceipt
    {
        return DB::transaction(function () use ($chemical) {
            $actorId = Auth::id() ?? $chemical->getAttribute('created_by');

            // Ambil detail pertama untuk location_id utama DO
            $firstDetail = $chemical->monitoringChemicalDetails()->first();
            $mainLocationId = $firstDetail ? $firstDetail->location_id : null;
            $po = $firstDetail ? $firstDetail->purchaseOrderIssued : null;
            $poNo = $po ? $po->getAttribute('purchase_order_no') : null;

            // Calculate document_code
            $detailCount = $chemical->monitoringChemicalDetails()->count();
            $itemCountStr = '';
            if ($detailCount > 0) {
                $itemCountStr = str_pad((string) $detailCount, 2, '0', STR_PAD_LEFT);
            }
            $doNo = $chemical->getAttribute('do_number');
            $recDate = $chemical->getAttribute('received_date');
            $dateStr = $recDate ? Carbon::parse($recDate)->format('dmY') : '';
            $stage = $firstDetail && $firstDetail->chemicalQcTuv ? $firstDetail->chemicalQcTuv->tahapan_name : null;

            $parts = array_filter([$poNo, $itemCountStr, $doNo, $dateStr, $stage]);
            $documentCode = null;
            if (!empty($parts)) {
                $joinedString = implode('-', $parts);
                $upperString = strtoupper($joinedString);
                $documentCode = preg_replace('/[^A-Z0-9\-_]/', '', $upperString);
            }

            // Find existing DO or create new
            $dor = DeliveryOrderReceipt::where('monitoring_chemical_id', $chemical->id)->first() ?? new DeliveryOrderReceipt;

            $payload = [
                'monitoring_chemical_id' => $chemical->id,
                'delivery_order_no' => $doNo,
                'document_code' => $documentCode,
                'location_id' => $mainLocationId,
                'received_date' => $recDate,
                'source_type' => $chemical->getAttribute('material_category') ?? 'Chemical/Karung',
                'status' => $dor->getAttribute('status') ?? 'Diterima', // initial status
                'received_by' => $dor->getAttribute('received_by') ?? $actorId,
                'created_by' => $dor->getAttribute('created_by') ?? $actorId,
                'incoterms' => $po ? $po->getAttribute('incoterm') : $dor->getAttribute('incoterms'),
                'eta_date' => $po ? $po->getAttribute('delivery_date_po') : $dor->getAttribute('eta_date'),
            ];

            if (!$dor->exists) {
                $payload['receipt_mode'] = 'Standard';
                $payload['is_physically_received'] = true;
                $payload['physical_received_date'] = $recDate;
                
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

            $validItemNos = [];

            foreach ($chemical->monitoringChemicalDetails as $detailRow) {
                $poItem = $detailRow->purchaseOrderIssued;
                if (! $poItem) {
                    continue;
                }

                $poItemNo = (int) $poItem->getAttribute('item_no');
                $validItemNos[] = $poItemNo;

                $detail = DeliveryOrderReceiptDetail::query()->firstOrNew([
                    'delivery_order_receipt_id' => $dor->getAttribute('id'),
                    'item_no' => $poItemNo,
                ]);

                // Calculate total amount
                $quantity = (float) str_replace(',', '.', (string) $detailRow->quantity);
                $unitPrice = ($poItem->qty_po > 0) ? ((float) $poItem->total_amount_in_lc / (float) $poItem->qty_po) : (float) $poItem->net_price;
                $totalAmountSnapshot = $quantity * $unitPrice;

                $detail->fill([
                    'purchase_order_issued_id' => $poItem->id,
                    'quantity' => (string) $quantity,
                    'material_code' => $poItem->getAttribute('material_code'),
                    'description' => $poItem->getAttribute('description'),
                    'uoi' => $poItem->getAttribute('uoi'),
                    'mrp_type' => $poItem->getAttribute('mrp_type'),
                    'material_type' => $poItem->getAttribute('material_type'),
                    'aac' => $poItem->getAttribute('aac'),
                    'abc_indicator' => $poItem->getAttribute('abc_indicator'),
                    'requisitioner' => $poItem->getAttribute('requisitioner'),
                    'location_id' => $detailRow->location_id,
                    'is_qty_tolerance' => $detailRow->is_qty_tolerance ?? false,
                    'total_amount_snapshot' => $totalAmountSnapshot,
                    'is_different_location' => $detailRow->location_id != $mainLocationId,
                ])->save();
            }

            // Remove any other details that might have been there
            if (! empty($validItemNos)) {
                DeliveryOrderReceiptDetail::where('delivery_order_receipt_id', $dor->getAttribute('id'))
                    ->whereNotIn('item_no', $validItemNos)
                    ->delete();
            } else {
                DeliveryOrderReceiptDetail::where('delivery_order_receipt_id', $dor->getAttribute('id'))
                    ->delete();
            }

            return $dor;
        });
    }
}
