<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaterialIssue;
use App\Models\WarehouseTransmittal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;

class TransmittalPrintController extends Controller
{
    public function print(Request $request, WarehouseTransmittal $transmittal)
    {
        $transmittal->load(['items.detail.deliveryOrderReceipt', 'destination.pic', 'createdBy']);

        // Collect unique Material Issue Records
        $mirRecords = collect();
        foreach ($transmittal->items as $item) {
            $mir = MaterialIssue::whereHas('materialIssueDetails', function ($query) use ($item) {
                $query->where('delivery_order_receipt_detail_id', $item->delivery_order_receipt_detail_id);
            })->first();

            if ($mir) {
                $mirRecords->push($mir);
            }
        }
        $mirRecords = $mirRecords->unique('id')->values();

        // Generate Transmittal PDF (F4 Portrait)
        $transmittalPdfStr = Pdf::loadView('pdf.transmittal', [
            'transmittal' => $transmittal,
        ])->setPaper([0, 0, 595.276, 935.433], 'portrait')->output();

        // If no MIR, just return Transmittal
        if ($mirRecords->isEmpty()) {
            return response($transmittalPdfStr, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Transmittal_' . $transmittal->transmittal_no . '.pdf"',
            ]);
        }

        // Generate MIR PDF (F4 Portrait)
        $mirPdfStr = Pdf::loadView('pdf.mir', [
            'records' => $mirRecords,
        ])->setPaper([0, 0, 595.276, 935.433], 'portrait')->output();

        // Merge PDFs
        return $this->mergePdfs($transmittalPdfStr, $mirPdfStr, 'Transmittal_' . $transmittal->transmittal_no . '.pdf');
    }

    public function printBulk(Request $request)
    {
        $ids = $request->input('transmittals', []);
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        // Clean empty values if any
        $ids = array_filter($ids);

        $transmittals = WarehouseTransmittal::whereIn('id', $ids)
            ->with(['items.detail.deliveryOrderReceipt', 'destination.pic', 'createdBy'])
            ->get();

        if ($transmittals->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data transmittal yang dipilih.');
        }

        // Collect unique MIRs for all selected transmittals
        $mirRecords = collect();
        foreach ($transmittals as $transmittal) {
            foreach ($transmittal->items as $item) {
                $mir = MaterialIssue::whereHas('materialIssueDetails', function ($query) use ($item) {
                    $query->where('delivery_order_receipt_detail_id', $item->delivery_order_receipt_detail_id);
                })->first();

                if ($mir) {
                    $mirRecords->push($mir);
                }
            }
        }
        $mirRecords = $mirRecords->unique('id')->values();

        // Generate Bulk Transmittal PDF (F4 Portrait)
        $transmittalPdfStr = Pdf::loadView('pdf.transmittal_bulk', [
            'transmittals' => $transmittals,
        ])->setPaper([0, 0, 595.276, 935.433], 'portrait')->output();

        if ($mirRecords->isEmpty()) {
            return response($transmittalPdfStr, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Bulk_Transmittals.pdf"',
            ]);
        }

        // Generate MIR PDF (F4 Portrait)
        $mirPdfStr = Pdf::loadView('pdf.mir', [
            'records' => $mirRecords,
        ])->setPaper([0, 0, 595.276, 935.433], 'portrait')->output();

        return $this->mergePdfs($transmittalPdfStr, $mirPdfStr, 'Bulk_Transmittals.pdf');
    }

    private function mergePdfs($transmittalPdfStr, $mirPdfStr, $filename)
    {
        $fpdi = new Fpdi;

        // Write to temp files
        $tmpTrans = tempnam(sys_get_temp_dir(), 'trans_');
        $tmpMir = tempnam(sys_get_temp_dir(), 'mir_');

        file_put_contents($tmpTrans, $transmittalPdfStr);
        file_put_contents($tmpMir, $mirPdfStr);

        try {
            // Import Transmittal pages
            $pageCount = $fpdi->setSourceFile($tmpTrans);
            for ($i = 1; $i <= $pageCount; $i++) {
                $templateId = $fpdi->importPage($i);
                $size = $fpdi->getTemplateSize($templateId);
                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($templateId);
            }

            // Import MIR pages
            $pageCountMir = $fpdi->setSourceFile($tmpMir);
            for ($i = 1; $i <= $pageCountMir; $i++) {
                $templateId = $fpdi->importPage($i);
                $size = $fpdi->getTemplateSize($templateId);
                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($templateId);
            }
        } finally {
            @unlink($tmpTrans);
            @unlink($tmpMir);
        }

        return response($fpdi->Output('S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
