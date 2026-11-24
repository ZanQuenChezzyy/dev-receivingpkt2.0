<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CombinedGrsVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CombinedGrsVerificationController extends Controller
{
    public function __construct(
        public CombinedGrsVerificationService $service,
    ) {}

    /**
     * Menganalisis dan mengklasifikasikan 1 berkas PDF (Slip GRS/RDTV vs Surat Verifikasi GRS/RDTV).
     */
    public function inspect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:25600'],
            'original_name' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'in:grs_slip,verifikasi_grs,rdtv_slip,verifikasi_rdtv,unknown'],
            'jenis' => ['nullable', 'string', 'in:GRS,RDTV'],
            'no_grs' => ['nullable', 'string', 'max:50'],
            'no_rdtv' => ['nullable', 'string', 'max:50'],
            'no_po' => ['nullable', 'string', 'max:50'],
            'no_do' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->service->inspectFile($request->file('file'), $validated);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Memvalidasi daftar pemetaan (dry-run tanpa mengubah database) untuk seluruh baris antrean.
     */
    public function precheck(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'rows' => ['required', 'array', 'max:300'],
            'rows.*.row_id' => ['required', 'string', 'max:100'],
            'rows.*.mode' => ['required', 'string', 'in:pair,slip_only,verif_only'],
            'rows.*.jenis' => ['required', 'string', 'in:GRS,RDTV'],
            'rows.*.document_code' => ['nullable', 'string', 'max:255'],
            'rows.*.no_grs' => ['nullable', 'string', 'max:50'],
            'rows.*.no_rdtv' => ['nullable', 'string', 'max:50'],
            'rows.*.no_po' => ['nullable', 'string', 'max:50'],
            'rows.*.no_do' => ['nullable', 'string', 'max:100'],
            'rows.*.verif_no_grs' => ['nullable', 'string', 'max:50'],
            'rows.*.verif_no_rdtv' => ['nullable', 'string', 'max:50'],
            'rows.*.verif_no_po' => ['nullable', 'string', 'max:50'],
            'rows.*.lokasi_penerimaan' => ['nullable', 'string', 'max:100'],
            'rows.*.tanggal_penerimaan' => ['nullable', 'string', 'max:30'],
            'rows.*.material_code' => ['nullable', 'string', 'max:30'],
            'rows.*.nama_barang' => ['nullable', 'string', 'max:500'],
            'rows.*.vendor_name' => ['nullable', 'string', 'max:150'],
        ]);

        $results = $this->service->precheckItems(
            rows: $validated['rows'],
            transactionDate: (string) $validated['transaction_date'],
        );

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Mengeksekusi penyimpanan 1 baris (Pasangan Lengkap, Slip Saja, atau Verifikasi Saja) secara atomik.
     */
    public function commit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'string', 'in:pair,slip_only,verif_only'],
            'jenis' => ['required', 'string', 'in:GRS,RDTV'],
            'transaction_date' => ['required', 'date'],
            'send_email' => ['nullable'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'no_grs' => ['nullable', 'string', 'max:50'],
            'no_rdtv' => ['nullable', 'string', 'max:50'],
            'no_po' => ['nullable', 'string', 'max:50'],
            'no_do' => ['nullable', 'string', 'max:100'],
            'lokasi_penerimaan' => ['nullable', 'string', 'max:100'],
            'tanggal_penerimaan' => ['nullable', 'string', 'max:30'],
            'vendor_name' => ['nullable', 'string', 'max:150'],
            'material_code' => ['nullable', 'string', 'max:30'],
            'nama_barang' => ['nullable', 'string', 'max:500'],
            'target_receipt_id' => ['nullable', 'integer'],
            'slip_file' => ['nullable', 'file', 'mimes:pdf', 'max:25600'],
            'verif_file' => ['nullable', 'file', 'mimes:pdf', 'max:25600'],
        ]);

        $result = $this->service->commitRow(
            payload: $validated,
            slipFile: $request->file('slip_file'),
            verifFile: $request->file('verif_file'),
            actorId: $request->user()?->id,
        );

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }
}
