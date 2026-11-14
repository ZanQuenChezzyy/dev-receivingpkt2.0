<?php

namespace App\Services;

use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\MonitoringKarung;
use App\Models\MonitoringKarungDelivery;
use App\Models\PurchaseOrderIssued;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

class SyncMonitoringKarungWithDeliveryOrderService
{
    /** @var array<int, bool> */
    private static array $syncingIds = [];

    /**
     * Synchronize a MonitoringKarung instance with related PO Terbit and DeliveryOrderReceipts.
     *
     * @return array{linked_do_count: int, new_deliveries: int, updated_deliveries: int}
     */
    public function sync(MonitoringKarung $monitoring): array
    {
        if (isset(self::$syncingIds[$monitoring->id])) {
            return [
                'linked_do_count' => 0,
                'new_deliveries' => 0,
                'updated_deliveries' => 0,
            ];
        }

        self::$syncingIds[$monitoring->id] = true;

        try {
            return DB::transaction(function () use ($monitoring) {
                $newDeliveries = 0;
                $updatedDeliveries = 0;
                $linkedDoCount = 0;

                // 1. Link PO Terbit (PurchaseOrderIssued) if not yet linked
                $po = $monitoring->purchaseOrderIssued;
                if (! $po && ! empty($monitoring->no_po)) {
                    $po = PurchaseOrderIssued::where('purchase_order_no', trim($monitoring->no_po))->first();
                    if ($po) {
                        $monitoring->purchase_order_issued_id = $po->id;

                        if (empty($monitoring->vendor_id) && ! empty($po->vendor_name)) {
                            $vendor = Vendor::where('name', $po->vendor_name)->first();
                            if ($vendor) {
                                $monitoring->vendor_id = $vendor->id;
                            }
                        }

                        if ($monitoring->qty_po <= 0 && $po->qty_po > 0) {
                            $monitoring->qty_po = (int) $po->qty_po;
                        }

                        $monitoring->saveQuietly();
                    }
                }

                // 2. Query related DO Receipts via purchase_order_issued_id or no_po
                $detailsQuery = DeliveryOrderReceiptDetail::query()
                    ->with(['deliveryOrderReceipt']);

                if ($monitoring->purchase_order_issued_id) {
                    $detailsQuery->where('purchase_order_issued_id', $monitoring->purchase_order_issued_id);
                } elseif (! empty($monitoring->no_po)) {
                    $detailsQuery->whereHas('purchaseOrderIssued', function ($q) use ($monitoring) {
                        $q->where('purchase_order_no', trim($monitoring->no_po));
                    });
                } else {
                    return [
                        'linked_do_count' => 0,
                        'new_deliveries' => 0,
                        'updated_deliveries' => 0,
                    ];
                }

                $details = $detailsQuery->get();
                if ($details->isEmpty()) {
                    return [
                        'linked_do_count' => 0,
                        'new_deliveries' => 0,
                        'updated_deliveries' => 0,
                    ];
                }

                // Group details by delivery_order_receipt_id
                $groupedByReceipt = $details->groupBy('delivery_order_receipt_id');

                foreach ($groupedByReceipt as $receiptId => $receiptDetails) {
                    /** @var DeliveryOrderReceipt|null $receipt */
                    $receipt = $receiptDetails->first()?->deliveryOrderReceipt;
                    if (! $receipt) {
                        continue;
                    }

                    // Associate DO Receipt with this Monitoring Karung
                    if ($receipt->monitoring_karung_id !== $monitoring->id) {
                        $receipt->monitoring_karung_id = $monitoring->id;
                        $receipt->saveQuietly();
                    }

                    $linkedDoCount++;

                    $totalQty = (int) $receiptDetails->sum('quantity');
                    $receiptDate = $receipt->physical_received_date?->format('Y-m-d')
                        ?? $receipt->received_date?->format('Y-m-d');

                    // Check if delivery item exists by delivery_order_receipt_id OR by matching no_do
                    $deliveryItem = MonitoringKarungDelivery::where('monitoring_karung_id', $monitoring->id)
                        ->where(function ($q) use ($receipt) {
                            $q->where('delivery_order_receipt_id', $receipt->id)
                                ->orWhere('no_do', $receipt->delivery_order_no);
                        })
                        ->first();

                    if ($deliveryItem) {
                        $deliveryItem->delivery_order_receipt_id = $receipt->id;
                        if (empty($deliveryItem->tanggal_penerimaan) && $receiptDate) {
                            $deliveryItem->tanggal_penerimaan = $receiptDate;
                        }
                        if (empty($deliveryItem->no_do)) {
                            $deliveryItem->no_do = $receipt->delivery_order_no;
                        }
                        if (empty($deliveryItem->no_surat) && $receipt->dof_number) {
                            $deliveryItem->no_surat = $receipt->dof_number;
                        }
                        if ($deliveryItem->qty_kirim <= 0 && $totalQty > 0) {
                            $deliveryItem->qty_kirim = $totalQty;
                            $deliveryItem->qty_supply = $totalQty;
                            $deliveryItem->qty_surat = $totalQty;
                        }
                        $deliveryItem->saveQuietly();
                        $updatedDeliveries++;
                    } else {
                        $nextTahap = $monitoring->deliveryItems()->count() + 1;
                        MonitoringKarungDelivery::create([
                            'monitoring_karung_id' => $monitoring->id,
                            'delivery_order_receipt_id' => $receipt->id,
                            'tahap_kirim' => (string) $nextTahap,
                            'qty_kirim' => $totalQty,
                            'no_do' => $receipt->delivery_order_no,
                            'no_invoice' => '',
                            'no_surat' => $receipt->dof_number ?? '',
                            'qty_supply' => $totalQty,
                            'qty_surat' => $totalQty,
                            'tanggal_penerimaan' => $receiptDate,
                            'keterangan' => "Tersambung otomatis dari Penerimaan DO #{$receipt->delivery_order_no}",
                            'sort_order' => $nextTahap,
                        ]);
                        $newDeliveries++;
                    }
                }

                // 3. Match any remaining unlinked delivery items directly by no_do if exists
                $unlinkedDeliveries = MonitoringKarungDelivery::where('monitoring_karung_id', $monitoring->id)
                    ->whereNull('delivery_order_receipt_id')
                    ->whereNotNull('no_do')
                    ->where('no_do', '!=', '')
                    ->get();

                foreach ($unlinkedDeliveries as $unlinkedItem) {
                    $receipt = DeliveryOrderReceipt::where('delivery_order_no', trim((string) $unlinkedItem->no_do))->first();
                    if ($receipt) {
                        $unlinkedItem->delivery_order_receipt_id = $receipt->id;
                        if (empty($unlinkedItem->tanggal_penerimaan)) {
                            $unlinkedItem->tanggal_penerimaan = $receipt->physical_received_date?->format('Y-m-d')
                                ?? $receipt->received_date?->format('Y-m-d');
                        }
                        if (empty($unlinkedItem->no_surat) && $receipt->dof_number) {
                            $unlinkedItem->no_surat = $receipt->dof_number;
                        }
                        $unlinkedItem->saveQuietly();

                        if ($receipt->monitoring_karung_id !== $monitoring->id) {
                            $receipt->monitoring_karung_id = $monitoring->id;
                            $receipt->saveQuietly();
                        }

                        $linkedDoCount++;
                        $updatedDeliveries++;
                    }
                }

                $monitoring->recalculateStatus();

                return [
                    'linked_do_count' => $linkedDoCount,
                    'new_deliveries' => $newDeliveries,
                    'updated_deliveries' => $updatedDeliveries,
                ];
            });
        } finally {
            unset(self::$syncingIds[$monitoring->id]);
        }
    }

    /**
     * Batch synchronize all active MonitoringKarung records with DO receipts.
     *
     * @return array{total_monitored: int, total_linked_dos: int, total_new_deliveries: int}
     */
    public function syncAll(): array
    {
        $monitors = MonitoringKarung::whereNotNull('no_po')->get();

        $totalLinkedDos = 0;
        $totalNewDeliveries = 0;

        foreach ($monitors as $monitor) {
            $res = $this->sync($monitor);
            $totalLinkedDos += $res['linked_do_count'];
            $totalNewDeliveries += $res['new_deliveries'];
        }

        return [
            'total_monitored' => $monitors->count(),
            'total_linked_dos' => $totalLinkedDos,
            'total_new_deliveries' => $totalNewDeliveries,
        ];
    }
}
