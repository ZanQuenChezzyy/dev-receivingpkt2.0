<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transmittal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class QCTransmittalPrintController extends Controller
{
    public function print(Request $request, Transmittal $transmittal)
    {
        $transmittal->load([
            'createdBy',
            'transmittalItems.deliveryOrderReceipt.deliveryOrderReceiptDetails',
            'transmittalItems.deliveryOrderReceipt.deliveryOrderReceiptDetails.purchaseOrderIssued'
        ]);

        $pdfStr = Pdf::loadView('pdf.qc_transmittal', [
            'transmittal' => $transmittal,
            'printedAt' => now(),
        ])->setPaper([0, 0, 595.276, 935.433], 'portrait')->output();

        return response($pdfStr, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Transmittal_QC_' . $transmittal->transmittal_no . '.pdf"',
        ]);
    }
}
