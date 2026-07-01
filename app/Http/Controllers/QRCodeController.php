<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrderReceipt;
use App\Models\WarehouseTransmittal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRCodeController extends Controller
{
    public function print(Request $request, DeliveryOrderReceipt $deliveryOrderReceipt)
    {
        $mode = $request->input('mode', 'both'); // 'material', 'document', 'both'
        $do = $deliveryOrderReceipt->load(['deliveryOrderReceiptDetails.purchaseOrderIssued', 'deliveryOrderReceiptDetails.locationReceiving', 'receivedBy']);

        $qrContent = $do->document_code; // Ambil dari kolom document_code
        $qrDo = 'data:image/png;base64,'.base64_encode(QrCode::size(200)->generate($qrContent));

        $logoPath = public_path('images/logo/logo-pupuk-kaltim-hitam.png');
        $logoBase64 = 'data:image/png;base64,'.base64_encode(@file_get_contents($logoPath) ?: '');

        // QR per item
        $items = collect();
        if (in_array($mode, ['material', 'both'])) {
            $items = $do->deliveryOrderReceiptDetails->map(function ($item) use ($do) {
                $qr = base64_encode(QrCode::size(200)->generate("{$do->delivery_order_no}-{$item->item_no}"));

                return [
                    'label' => "Item {$item->item_no}",
                    'qr' => 'data:image/png;base64,'.$qr,
                ];
            });
        }

        $pdf = Pdf::loadView('pdf.do-qr', [
            'mode' => $mode,
            'qrDo' => $qrDo,
            'items' => $items,
            'do' => $do,
            'logo' => $logoBase64,
        ])->setPaper([0, 0, 144, 216], 'landscape');

        $filename = 'QR-DO-'.str_replace(['/', '\\'], '_', $do->delivery_order_no);
        if ($mode === 'material') {
            $filename .= '-Material';
        } elseif ($mode === 'document') {
            $filename .= '-Dokumen';
        }

        return $pdf->stream($filename.'.pdf');
    }

    public function bulkPrint(Request $request)
    {
        $mode = $request->input('mode', 'both');

        if ($request->has('transmittal')) {
            $transmittalId = $request->input('transmittal');
            $transmittal = WarehouseTransmittal::with('items.detail')->findOrFail($transmittalId);

            $doIds = $transmittal->items->pluck('detail.delivery_order_receipt_id')->unique()->toArray();
            $dos = DeliveryOrderReceipt::with(['deliveryOrderReceiptDetails.purchaseOrderIssued', 'deliveryOrderReceiptDetails.locationReceiving', 'deliveryOrderReceiptDetails.materialIssueDetails.materialIssue', 'receivedBy'])->findMany($doIds);
        } else {
            $ids = array_filter(explode(',', (string) $request->input('ids')));
            $dos = DeliveryOrderReceipt::with(['deliveryOrderReceiptDetails.purchaseOrderIssued', 'deliveryOrderReceiptDetails.locationReceiving', 'deliveryOrderReceiptDetails.materialIssueDetails.materialIssue', 'receivedBy'])->findMany($ids);
        }

        $data = [];

        foreach ($dos as $do) {
            $qrContent = $do->document_code;
            $qrDo = 'data:image/png;base64,'.base64_encode(QrCode::size(200)->generate($qrContent));

            $items = collect();
            if (in_array($mode, ['material', 'both'])) {
                $items = $do->deliveryOrderReceiptDetails->map(function ($item) use ($do) {
                    $qr = base64_encode(QrCode::size(200)->generate("{$do->delivery_order_no}-{$item->item_no}"));

                    return [
                        'label' => "Item {$item->item_no}",
                        'qr' => 'data:image/png;base64,'.$qr,
                    ];
                });
            }

            $data[] = [
                'do' => $do,
                'qrDo' => $qrDo,
                'items' => $items,
            ];
        }

        $logoPath = public_path('images/logo/logo-pupuk-kaltim-hitam.png');
        $logoBase64 = 'data:image/png;base64,'.base64_encode(@file_get_contents($logoPath) ?: '');

        $pdf = Pdf::loadView('pdf.bulk-do-qr', [
            'mode' => $mode,
            'records' => $data,
            'logo' => $logoBase64,
        ])->setPaper([0, 0, 144, 216], 'landscape');

        $filename = 'Bulk-QR-DO';
        if ($mode === 'material') {
            $filename .= '-Material';
        } elseif ($mode === 'document') {
            $filename .= '-Dokumen';
        }

        return $pdf->stream($filename.'.pdf');
    }
}
