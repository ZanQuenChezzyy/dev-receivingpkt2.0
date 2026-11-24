<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoicingReceipt;
use App\Models\Vendor;
use App\Services\GrsPdfParserService;
use App\Services\InvoicingGrsEmailDispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class BatchVerifikasiGrsUploadController extends Controller
{
    /**
     * Menerima satu berkas PDF Verifikasi GRS (105) atau RDTV (124), metadata hasil OCR client-side,
     * mencocokkannya ke InvoicingReceipt sesuai jenis dokumen (GRS vs RDTV), dan memperbarui status menjadi Terverifikasi.
     */
    public function uploadAndVerify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:25600'], // max 25MB
            'jenis' => ['nullable', 'string', 'in:GRS,RDTV'],
            'no_grs' => ['nullable', 'string', 'max:50'],
            'no_rdtv' => ['nullable', 'string', 'max:50'],
            'no_po' => ['nullable', 'string', 'max:50'],
            'no_do' => ['nullable', 'string', 'max:100'],
            'original_name' => ['nullable', 'string', 'max:255'],
        ]);

        $uploadedFile = $request->file('file');
        $originalName = $validated['original_name'] ?? $uploadedFile->getClientOriginalName();
        $jenis = strtoupper(trim((string) ($validated['jenis'] ?? '')));
        $noGrs = trim((string) ($validated['no_grs'] ?? ''));
        $noRdtv = trim((string) ($validated['no_rdtv'] ?? ''));
        $noPo = trim((string) ($validated['no_po'] ?? ''));
        $noDo = trim((string) ($validated['no_do'] ?? ''));

        // Jika jenis / nomor GRS / RDTV / PO belum terisi dari client, gunakan parser backend sebagai fallback
        if (empty($jenis) || (empty($noGrs) && empty($noRdtv)) || empty($noPo)) {
            try {
                $parser = app(GrsPdfParserService::class);
                $parsed = $parser->parseVerifikasiGrs($uploadedFile->getRealPath(), $originalName);

                if (empty($jenis) && ! empty($parsed['jenis'])) {
                    $jenis = strtoupper((string) $parsed['jenis']);
                }
                if (empty($noRdtv) && ! empty($parsed['no_rdtv'])) {
                    $noRdtv = (string) $parsed['no_rdtv'];
                }
                if (empty($noGrs) && ! empty($parsed['no_grs'])) {
                    $noGrs = (string) $parsed['no_grs'];
                }
                if (empty($noPo) && ! empty($parsed['no_po'])) {
                    $noPo = (string) $parsed['no_po'];
                }
                if (empty($noDo) && ! empty($parsed['no_do'])) {
                    $noDo = (string) $parsed['no_do'];
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Jika jenis RDTV namun user/client menaruh nomor di no_grs, pindahkan ke no_rdtv
        if ($jenis === 'RDTV' && empty($noRdtv) && ! empty($noGrs)) {
            $noRdtv = $noGrs;
            $noGrs = '';
        }

        // Jika no_rdtv terisi tanpa jenis eksplisit, tandai sebagai RDTV
        if (empty($jenis) && ! empty($noRdtv) && empty($noGrs)) {
            $jenis = 'RDTV';
        }

        $applyJenisScope = static function (Builder $query) use ($jenis): Builder {
            if ($jenis === 'RDTV') {
                return $query->where('jenis', 'RDTV');
            }

            if ($jenis === 'GRS') {
                return $query->where(function (Builder $q): void {
                    $q->whereNull('jenis')
                        ->orWhere('jenis', '!=', 'RDTV');
                });
            }

            return $query;
        };

        $whereUnverifiedDoc = static function (Builder $q): void {
            $q->where(function (Builder $sub): void {
                $sub->whereNull('dokumen_verifikasi_grs')
                    ->orWhere('dokumen_verifikasi_grs', '')
                    ->orWhere('dokumen_verifikasi_grs', 'like', 'grs-rdtv-docs/%');
            });
        };

        $receipt = null;
        $ambiguousMessage = null;

        // 1a. Pencocokan utama untuk RDTV: Nomor RDTV (124) pada record berjenis RDTV
        if ($jenis === 'RDTV' && ! empty($noRdtv)) {
            $cleanRdtv = preg_replace('/\D/', '', $noRdtv);

            $rdtvQuery = $applyJenisScope(InvoicingReceipt::query())->where(function (Builder $q) use ($noRdtv, $cleanRdtv): void {
                $q->where('no_rdtv', $noRdtv)
                    ->orWhere('no_rdtv', 'like', "%{$noRdtv}%")
                    ->orWhere('no_grs', $noRdtv)
                    ->orWhere('no_grs', 'like', "%{$noRdtv}%");

                if (! empty($cleanRdtv)) {
                    $q->orWhere('no_rdtv', 'like', "%{$cleanRdtv}%")
                        ->orWhere('no_grs', 'like', "%{$cleanRdtv}%");
                }
            });

            $receipt = (clone $rdtvQuery)->where('status', 'Menunggu Verifikasi')->tap($whereUnverifiedDoc)->first()
                ?? (clone $rdtvQuery)->where('status', 'Menunggu Verifikasi')->first()
                ?? $rdtvQuery->first();
        }

        // 1b. Pencocokan utama untuk GRS (atau tanpa jenis): Nomor GRS (105) (500xxxxxxx)
        if (! $receipt && $jenis !== 'RDTV' && ! empty($noGrs)) {
            $cleanGrs = preg_replace('/\D/', '', $noGrs);

            $grsQuery = $applyJenisScope(InvoicingReceipt::query())->where(function (Builder $q) use ($noGrs, $cleanGrs, $jenis): void {
                $q->where('no_grs', $noGrs)
                    ->orWhere('no_grs', 'like', "%{$noGrs}%");

                if ($jenis !== 'GRS') {
                    $q->orWhere('no_rdtv', $noGrs)
                        ->orWhere('no_rdtv', 'like', "%{$noGrs}%");
                }

                if (! empty($cleanGrs)) {
                    $q->orWhere('no_grs', 'like', "%{$cleanGrs}%");
                    if ($jenis !== 'GRS') {
                        $q->orWhere('no_rdtv', 'like', "%{$cleanGrs}%");
                    }
                }

                $q->orWhereHas('items', function (Builder $itemQuery) use ($noGrs, $cleanGrs): void {
                    $itemQuery->where('no_grs', $noGrs)
                        ->orWhere('no_grs', 'like', "%{$noGrs}%");
                    if (! empty($cleanGrs)) {
                        $itemQuery->orWhere('no_grs', 'like', "%{$cleanGrs}%");
                    }
                });
            });

            $receipt = (clone $grsQuery)->where('status', 'Menunggu Verifikasi')->tap($whereUnverifiedDoc)->first()
                ?? (clone $grsQuery)->where('status', 'Menunggu Verifikasi')->first()
                ?? $grsQuery->first();
        }

        // 2. Fallback pencocokan aman: Nomor PO + DO terlebih dahulu, lalu fallback PO saja
        //    PENTING: Jangan pernah menimpa record parsial lain pada PO yang sama yang sudah memiliki No. GRS / No. RDTV berbeda!
        if (! $receipt && ! empty($noPo)) {
            $cleanPo = preg_replace('/\D/', '', $noPo);
            $cleanGrs = preg_replace('/\D/', '', $noGrs);
            $cleanRdtv = preg_replace('/\D/', '', $noRdtv);

            $basePoQuery = $applyJenisScope(InvoicingReceipt::query())->where(function (Builder $q) use ($noPo, $cleanPo): void {
                $q->where('no_po', $noPo);
                if (! empty($cleanPo)) {
                    $q->orWhere('no_po', $cleanPo)
                        ->orWhere('no_po', 'like', "%{$cleanPo}%");
                }
            });

            // Guard anti-bentrok antar parsial PO: bila dokumen punya No. GRS / No. RDTV, abaikan baris yang sudah punya nomor BERBEDA
            if ($jenis === 'RDTV' && ! empty($noRdtv)) {
                $basePoQuery->where(function (Builder $q) use ($noRdtv, $cleanRdtv): void {
                    $q->whereNull('no_rdtv')
                        ->orWhere('no_rdtv', '')
                        ->orWhere('no_rdtv', $noRdtv);
                    if (! empty($cleanRdtv)) {
                        $q->orWhere('no_rdtv', 'like', "%{$cleanRdtv}%");
                    }
                });
            } elseif ($jenis !== 'RDTV' && ! empty($noGrs)) {
                $basePoQuery->where(function (Builder $q) use ($noGrs, $cleanGrs): void {
                    $q->whereNull('no_grs')
                        ->orWhere('no_grs', '')
                        ->orWhere('no_grs', $noGrs);
                    if (! empty($cleanGrs)) {
                        $q->orWhere('no_grs', 'like', "%{$cleanGrs}%");
                    }
                });
            }

            if (! empty($noDo)) {
                $poWithDoQuery = (clone $basePoQuery)->where('no_do', 'like', "%{$noDo}%");
                $receipt = (clone $poWithDoQuery)->where('status', 'Menunggu Verifikasi')->tap($whereUnverifiedDoc)->first()
                    ?? (clone $poWithDoQuery)->where('status', 'Menunggu Verifikasi')->first()
                    ?? $poWithDoQuery->first();
            }

            if (! $receipt) {
                $unverifiedCandidates = (clone $basePoQuery)
                    ->where('status', 'Menunggu Verifikasi')
                    ->tap($whereUnverifiedDoc)
                    ->get();

                if ($unverifiedCandidates->count() === 1) {
                    $receipt = $unverifiedCandidates->first();
                } elseif ($unverifiedCandidates->count() > 1) {
                    $docLabel = $jenis === 'RDTV' ? 'RDTV' : 'GRS';
                    $ambiguousMessage = "Ditemukan {$unverifiedCandidates->count()} tagihan parsial pada PO {$noPo} yang belum memiliki No. {$docLabel} cocok. Silakan pastikan No. {$docLabel} terisi atau unggah langsung di baris tagihan yang sesuai agar tidak salah kirim ke vendor.";
                } else {
                    $waitingCandidates = (clone $basePoQuery)->where('status', 'Menunggu Verifikasi')->get();
                    if ($waitingCandidates->count() === 1) {
                        $receipt = $waitingCandidates->first();
                    } elseif ($waitingCandidates->count() > 1) {
                        $docLabel = $jenis === 'RDTV' ? 'RDTV' : 'GRS';
                        $ambiguousMessage = "Ditemukan {$waitingCandidates->count()} tagihan parsial pada PO {$noPo} yang menunggu verifikasi tanpa No. {$docLabel} cocok. Silakan unggah langsung di baris tagihan yang sesuai.";
                    } elseif (empty($noGrs) && empty($noRdtv)) {
                        $allCandidates = (clone $basePoQuery)->get();
                        if ($allCandidates->count() === 1) {
                            $receipt = $allCandidates->first();
                        }
                    }
                }
            }
        }

        if ($receipt) {
            $resolvedJenis = $jenis ?: ($receipt->jenis === 'RDTV' ? 'RDTV' : 'GRS');
            $storageDir = $resolvedJenis === 'RDTV' ? 'verifikasi-rdtv' : 'verifikasi-grs';
            $storedPath = $uploadedFile->store($storageDir, 'public');

            if ($storedPath) {
                $receipt->dokumen_verifikasi_grs = $storedPath;
            }

            if ($receipt->status === 'Menunggu Verifikasi') {
                $receipt->status = 'Terverifikasi';
            }

            if ($resolvedJenis === 'RDTV') {
                if (empty($receipt->no_rdtv) && ! empty($noRdtv)) {
                    $receipt->no_rdtv = $noRdtv;
                }
            } else {
                if (empty($receipt->no_grs) && ! empty($noGrs)) {
                    $receipt->no_grs = $noGrs;
                }
            }

            if (empty($receipt->no_do) && ! empty($noDo)) {
                $receipt->no_do = $noDo;
            }

            $relationsToLoad = [];
            if (Schema::hasTable('purchase_order_issueds')) {
                $relationsToLoad[] = 'purchaseOrderIssued';
            }
            if (Schema::hasTable('delivery_order_receipts') && Schema::hasTable('delivery_order_receipt_details')) {
                $relationsToLoad[] = 'deliveryOrderReceipt.deliveryOrderReceiptDetails';
            }
            if (Schema::hasTable('vendors')) {
                $relationsToLoad[] = 'vendor';
            }
            if ($relationsToLoad !== []) {
                $receipt->loadMissing($relationsToLoad);
            }

            $poRelation = Schema::hasTable('purchase_order_issueds') ? $receipt->purchaseOrderIssued : null;
            $doDetails = (Schema::hasTable('delivery_order_receipts') && Schema::hasTable('delivery_order_receipt_details'))
                ? ($receipt->deliveryOrderReceipt?->deliveryOrderReceiptDetails?->pluck('material_code')->all() ?? [])
                : [];

            $resolvedMatType = InvoicingReceipt::resolveMaterialType(
                $receipt->material_type,
                array_filter([
                    $poRelation?->material_code,
                    ...$doDetails,
                ]),
                array_filter([
                    $receipt->nama_barang,
                    $receipt->deskripsi,
                    $poRelation?->description,
                ]),
                $receipt->no_po,
            );
            if ($resolvedMatType !== InvoicingReceipt::normalizeMaterialType($receipt->material_type)) {
                $receipt->material_type = $resolvedMatType;
            }

            if (! $receipt->vendor_id || blank($receipt->email_vendor)) {
                $matchedVendor = Vendor::findBestMatch(
                    vendorNumber: $poRelation?->vendor_id,
                    nameCandidates: [$receipt->vendor_name, $poRelation?->vendor_name],
                );

                if ($matchedVendor) {
                    if (! $receipt->vendor_id) {
                        $receipt->vendor_id = $matchedVendor->id;
                    }
                    if (blank($receipt->vendor_name)) {
                        $receipt->vendor_name = $matchedVendor->name;
                    }
                    if (blank($receipt->email_vendor)) {
                        $masterEmails = array_values(array_filter(
                            Vendor::parseEmails($matchedVendor->email),
                            static fn (string $e): bool => (bool) filter_var($e, FILTER_VALIDATE_EMAIL),
                        ));
                        if ($masterEmails !== []) {
                            $receipt->email_vendor = implode(', ', $masterEmails);
                        }
                    }
                }
            }

            $receipt->save();

            $emailResult = app(InvoicingGrsEmailDispatcher::class)->dispatchIfEligible(
                receipt: $receipt->fresh(['grsRdtvItem', 'vendor', 'pic']),
                triggerSource: 'batch_ocr',
                userId: $request->user()?->id,
            );

            $baseMessage = "Dokumen Verifikasi {$resolvedJenis} untuk PO {$receipt->no_po} berhasil diverifikasi.";
            $emailSuffix = match ($emailResult['status']) {
                'sent' => ' '.$emailResult['message'],
                'skipped' => ' Email ditahan: '.$emailResult['message'],
                'failed' => ' Email gagal dikirim: '.$emailResult['message'],
                default => '',
            };

            return response()->json([
                'success' => true,
                'matched' => true,
                'receipt_id' => $receipt->id,
                'jenis' => $resolvedJenis,
                'no_grs' => $receipt->no_grs ?: ($noGrs ?: null),
                'no_rdtv' => $receipt->no_rdtv ?: ($noRdtv ?: null),
                'no_po' => $receipt->no_po,
                'no_do' => $receipt->no_do,
                'vendor_name' => $receipt->vendor_name ?: '-',
                'status' => $receipt->status,
                'stored_path' => $storedPath,
                'email_status' => $emailResult['status'],
                'email_message' => $emailResult['message'],
                'message' => $baseMessage.$emailSuffix,
            ]);
        }

        // Jika tagihan belum ditemukan atau ambigu antar parsial PO
        $hasAnyNumber = ! empty($noGrs) || ! empty($noRdtv) || ! empty($noPo);
        if ($ambiguousMessage !== null) {
            $notFoundMessage = $ambiguousMessage;
        } elseif (! $hasAnyNumber) {
            $notFoundMessage = 'Nomor GRS (105/101), RDTV (124), maupun PO tidak terdeteksi dari dokumen ini.';
        } elseif ($jenis === 'RDTV') {
            $notFoundMessage = 'Tidak ditemukan dokumen RDTV dengan No. RDTV: '.($noRdtv ?: '-').' / PO: '.($noPo ?: '-');
        } else {
            $notFoundMessage = 'Tidak ditemukan tagihan Invoice Barang (GRS) dengan No. GRS: '.($noGrs ?: '-').' / PO: '.($noPo ?: '-');
        }

        return response()->json([
            'success' => false,
            'matched' => false,
            'jenis' => $jenis ?: null,
            'no_grs' => $noGrs ?: null,
            'no_rdtv' => $noRdtv ?: null,
            'no_po' => $noPo ?: null,
            'message' => $notFoundMessage,
        ]);
    }
}
