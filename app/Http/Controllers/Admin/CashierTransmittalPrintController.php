<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashierTransmittal;
use App\Models\InvoicingReceipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CashierTransmittalPrintController extends Controller
{
    public function print(Request $request): Response
    {
        $rawIds = trim((string) $request->query('ids', ''));
        $ids = collect(explode(',', $rawIds))
            ->map(fn (string $id): int => (int) trim($id))
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        abort_if($ids->isEmpty(), 404, 'Tidak ada data invoice yang dipilih untuk dicetak.');

        $receipts = InvoicingReceipt::query()
            ->with(['vendor', 'pic', 'purchaseOrderIssued'])
            ->whereIn('id', $ids->all())
            ->where(function ($query): void {
                $query->whereNull('jenis')
                    ->orWhere('jenis', '!=', 'RDTV');
            })
            ->get()
            ->sortBy(fn (InvoicingReceipt $receipt): int => $ids->search($receipt->id) !== false ? (int) $ids->search($receipt->id) : PHP_INT_MAX)
            ->values();

        abort_if($receipts->isEmpty(), 404, 'Tidak ditemukan dokumen invoice GRS yang valid untuk transmittal kasir.');

        $sentDateRaw = $request->query('sent_date');
        $sentDate = null;
        if (is_string($sentDateRaw) && trim($sentDateRaw) !== '') {
            try {
                $sentDate = Carbon::parse($sentDateRaw);
            } catch (\Throwable) {
                $sentDate = null;
            }
        }

        return $this->renderPdfResponse(
            receipts: $receipts,
            sentDate: $sentDate,
            transmittalNo: null,
            destination: CashierTransmittal::DEFAULT_DESTINATION,
        );
    }

    public function printTransmittal(CashierTransmittal $cashierTransmittal): Response
    {
        $receipts = $cashierTransmittal->invoicingReceipts()
            ->with(['vendor', 'pic', 'purchaseOrderIssued'])
            ->where(function ($query): void {
                $query->whereNull('jenis')
                    ->orWhere('jenis', '!=', 'RDTV');
            })
            ->orderBy('id')
            ->get();

        abort_if($receipts->isEmpty(), 404, 'Transmittal Kasir ini belum memiliki dokumen invoice GRS untuk dicetak.');

        $cashierTransmittal->forceFill([
            'printed_at' => now(),
        ])->saveQuietly();

        return $this->renderPdfResponse(
            receipts: $receipts,
            sentDate: $cashierTransmittal->sent_date,
            transmittalNo: $cashierTransmittal->transmittal_no,
            destination: $cashierTransmittal->destination ?: CashierTransmittal::DEFAULT_DESTINATION,
        );
    }

    /**
     * @param  Collection<int, InvoicingReceipt>  $receipts
     */
    protected function renderPdfResponse(
        Collection $receipts,
        ?Carbon $sentDate,
        ?string $transmittalNo,
        string $destination,
    ): Response {
        $printedAt = now();
        $totalSubtotalExclude = (float) $receipts->sum(fn (InvoicingReceipt $receipt): float => (float) ($receipt->subtotal_exclude_ppn ?? 0));

        $pdfBinary = Pdf::loadView('pdf.cashier_transmittal', [
            'receipts' => $receipts,
            'printedAt' => $printedAt,
            'sentDate' => $sentDate,
            'transmittalNo' => $transmittalNo,
            'destination' => $destination,
            'senderName' => Auth::user()?->name ?? 'Tim Penerimaan Invoice',
            'totalSubtotalExclude' => $totalSubtotalExclude,
            'watermarkText' => 'Dicetak menggunakan Mokondo V2.0 - dev.receivingpkt.com',
        ])->setPaper([0, 0, 595.276, 935.433], 'portrait')->output();

        $safeNo = $transmittalNo !== null
            ? preg_replace('/[^A-Za-z0-9\-_]+/', '_', $transmittalNo)
            : $printedAt->format('Ymd_His');
        $filename = 'Transmittal_Pengiriman_Invoice_Kasir_'.$safeNo.'.pdf';

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
