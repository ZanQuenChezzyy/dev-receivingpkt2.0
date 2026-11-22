<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoicingReceipt;
use App\Services\GrsPdfParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BatchVerifikasiGrsUploadController extends Controller
{
    /**
     * Menerima satu berkas PDF Verifikasi GRS, metadata hasil OCR client-side,
     * mencocokkannya ke InvoicingReceipt, dan memperbarui status menjadi Terverifikasi.
     */
    public function uploadAndVerify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:25600'], // max 25MB
            'no_grs' => ['nullable', 'string', 'max:50'],
            'no_po' => ['nullable', 'string', 'max:50'],
            'no_do' => ['nullable', 'string', 'max:100'],
            'original_name' => ['nullable', 'string', 'max:255'],
        ]);

        $uploadedFile = $request->file('file');
        $originalName = $validated['original_name'] ?? $uploadedFile->getClientOriginalName();
        $noGrs = trim((string) ($validated['no_grs'] ?? ''));
        $noPo = trim((string) ($validated['no_po'] ?? ''));
        $noDo = trim((string) ($validated['no_do'] ?? ''));

        // Jika nomor GRS atau PO belum terisi dari client, gunakan parser backend sebagai fallback
        if (empty($noGrs) || empty($noPo)) {
            try {
                $parser = app(GrsPdfParserService::class);
                $parsed = $parser->parseVerifikasiGrs($uploadedFile->getRealPath(), $originalName);
                if (empty($noGrs) && ! empty($parsed['no_grs'])) {
                    $noGrs = $parsed['no_grs'];
                }
                if (empty($noPo) && ! empty($parsed['no_po'])) {
                    $noPo = $parsed['no_po'];
                }
                if (empty($noDo) && ! empty($parsed['no_do'])) {
                    $noDo = $parsed['no_do'];
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $receipt = null;

        // 1. Pencocokan utama: Nomor GRS (105) (500xxxxxxx)
        if (! empty($noGrs)) {
            $cleanGrs = preg_replace('/\D/', '', $noGrs);

            $grsQuery = InvoicingReceipt::where(function ($q) use ($noGrs, $cleanGrs) {
                $q->where('no_grs', $noGrs)
                    ->orWhere('no_grs', 'like', "%{$noGrs}%")
                    ->orWhere('no_rdtv', $noGrs)
                    ->orWhere('no_rdtv', 'like', "%{$noGrs}%");

                if (! empty($cleanGrs)) {
                    $q->orWhere('no_grs', 'like', "%{$cleanGrs}%")
                        ->orWhere('no_rdtv', 'like', "%{$cleanGrs}%");
                }

                $q->orWhereHas('items', function ($itemQuery) use ($noGrs, $cleanGrs) {
                    $itemQuery->where('no_grs', $noGrs)
                        ->orWhere('no_grs', 'like', "%{$noGrs}%");
                    if (! empty($cleanGrs)) {
                        $itemQuery->orWhere('no_grs', 'like', "%{$cleanGrs}%");
                    }
                });
            });

            $receipt = (clone $grsQuery)->where('status', 'Menunggu Verifikasi')->first()
                ?? $grsQuery->first();
        }

        // 2. Fallback pencocokan: Nomor PO + DO terlebih dahulu, lalu fallback PO saja bila format DO berbeda/kosong
        if (! $receipt && ! empty($noPo)) {
            $cleanPo = preg_replace('/\D/', '', $noPo);

            $basePoQuery = InvoicingReceipt::where(function ($q) use ($noPo, $cleanPo) {
                $q->where('no_po', $noPo);
                if (! empty($cleanPo)) {
                    $q->orWhere('no_po', $cleanPo)
                        ->orWhere('no_po', 'like', "%{$cleanPo}%");
                }
            });

            if (! empty($noDo)) {
                $poWithDoQuery = (clone $basePoQuery)->where('no_do', 'like', "%{$noDo}%");
                $receipt = (clone $poWithDoQuery)->where('status', 'Menunggu Verifikasi')->whereNull('dokumen_verifikasi_grs')->first()
                    ?? (clone $poWithDoQuery)->where('status', 'Menunggu Verifikasi')->first()
                    ?? $poWithDoQuery->first();
            }

            if (! $receipt) {
                // Prioritaskan tagihan yang masih Menunggu Verifikasi dan belum memiliki berkas verifikasi
                $receipt = (clone $basePoQuery)->where('status', 'Menunggu Verifikasi')->whereNull('dokumen_verifikasi_grs')->first()
                    ?? (clone $basePoQuery)->where('status', 'Menunggu Verifikasi')->first()
                    ?? $basePoQuery->first();
            }
        }

        if ($receipt) {
            // Simpan file ke storage disk public
            $storedPath = $uploadedFile->store('verifikasi-grs', 'public');

            if ($storedPath) {
                $receipt->dokumen_verifikasi_grs = $storedPath;
            }

            if ($receipt->status === 'Menunggu Verifikasi') {
                $receipt->status = 'Terverifikasi';
            }

            if (empty($receipt->no_grs) && ! empty($noGrs)) {
                $receipt->no_grs = $noGrs;
            }
            if (empty($receipt->no_do) && ! empty($noDo)) {
                $receipt->no_do = $noDo;
            }

            $receipt->save();

            return response()->json([
                'success' => true,
                'matched' => true,
                'receipt_id' => $receipt->id,
                'no_grs' => $receipt->no_grs ?: $noGrs,
                'no_po' => $receipt->no_po,
                'no_do' => $receipt->no_do,
                'vendor_name' => $receipt->vendor_name ?: '-',
                'status' => $receipt->status,
                'stored_path' => $storedPath,
                'message' => "Tagihan PO {$receipt->no_po} berhasil dicocokkan dan status diubah menjadi Terverifikasi.",
            ]);
        }

        // Jika tagihan belum ditemukan
        return response()->json([
            'success' => false,
            'matched' => false,
            'no_grs' => $noGrs ?: null,
            'no_po' => $noPo ?: null,
            'message' => empty($noGrs) && empty($noPo)
                ? 'Nomor GRS (105) maupun PO tidak terdeteksi dari dokumen ini.'
                : 'Tidak ditemukan tagihan Invoice Barang dengan GRS: '.($noGrs ?: '-').' / PO: '.($noPo ?: '-'),
        ]);
    }
}
