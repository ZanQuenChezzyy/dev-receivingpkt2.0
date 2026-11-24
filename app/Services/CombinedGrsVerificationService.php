<?php

namespace App\Services;

use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\InvoicingReceipt;
use App\Models\LocationReceiving;
use App\Models\PurchaseOrderIssued;
use App\Models\QcHistory;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CombinedGrsVerificationService
{
    public function __construct(
        public GrsPdfParserService $pdfParser,
        public GrsRdtvProcessingService $processingService,
        public InvoicingGrsEmailDispatcher $emailDispatcher,
    ) {}

    /**
     * Menganalisis 1 berkas PDF (backend parser + fallback metadata client OCR) untuk mengklasifikasikan
     * jenis dokumen (Slip GRS/RDTV vs Surat Verifikasi GRS/RDTV) beserta nomor & tanggal kunci.
     *
     * @param  array<string, mixed>  $clientMetadata
     * @return array<string, mixed>
     */
    public function inspectFile(UploadedFile $file, array $clientMetadata = []): array
    {
        $originalName = trim((string) ($clientMetadata['original_name'] ?? $file->getClientOriginalName()));
        $documentCode = $this->processingService->stripStoredFilenameSuffix(
            trim((string) pathinfo($originalName, PATHINFO_FILENAME))
        );
        $filenameMatchesDo = $documentCode !== ''
            && DeliveryOrderReceipt::query()->where('document_code', $documentCode)->exists();

        $parsed = $this->pdfParser->classifyDocument(
            filePathOrContent: (string) $file->getRealPath(),
            originalFilename: $originalName,
            filenameMatchesDo: $filenameMatchesDo,
        );

        $clientJenis = strtoupper(trim((string) ($clientMetadata['jenis'] ?? '')));
        $clientRole = trim((string) ($clientMetadata['role'] ?? ''));
        $clientNoGrs = trim((string) ($clientMetadata['no_grs'] ?? ''));
        $clientNoRdtv = trim((string) ($clientMetadata['no_rdtv'] ?? ''));
        $clientNoPo = trim((string) ($clientMetadata['no_po'] ?? ''));
        $clientNoDo = trim((string) ($clientMetadata['no_do'] ?? ''));
        $clientLokasi = trim((string) ($clientMetadata['lokasi_penerimaan'] ?? ''));
        $ocrDone = filter_var($clientMetadata['ocr_done'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $jenis = $parsed['jenis'] ?: (in_array($clientJenis, ['GRS', 'RDTV'], true) ? $clientJenis : null);
        $noGrs = $parsed['no_grs'] ?: ($clientNoGrs !== '' ? $clientNoGrs : null);
        $noRdtv = $parsed['no_rdtv'] ?: ($clientNoRdtv !== '' ? $clientNoRdtv : null);
        $noPo = $parsed['no_po'] ?: ($clientNoPo !== '' ? $clientNoPo : null);
        $noDo = $parsed['no_do'] ?: ($clientNoDo !== '' ? $clientNoDo : null);
        $lokasi = ($parsed['lokasi_penerimaan'] ?? null) ?: ($clientLokasi !== '' ? $clientLokasi : null);

        if ($jenis === 'RDTV' && blank($noRdtv) && filled($noGrs)) {
            $noRdtv = $noGrs;
            $noGrs = null;
        } elseif ($jenis === null && filled($noRdtv) && blank($noGrs)) {
            $jenis = 'RDTV';
        } elseif ($jenis === null && filled($noGrs)) {
            $jenis = 'GRS';
        }

        $role = $parsed['role'];
        $validRoles = ['grs_slip', 'verifikasi_grs', 'rdtv_slip', 'verifikasi_rdtv'];
        $backendHadNoDocNumber = blank($parsed['no_grs'] ?? null) && blank($parsed['no_rdtv'] ?? null);
        if (in_array($clientRole, $validRoles, true) && ($role === 'unknown' || ($ocrDone && $backendHadNoDocNumber))) {
            $role = $clientRole;
        }

        if ($role === 'unknown' && $filenameMatchesDo) {
            $role = $jenis === 'RDTV' ? 'rdtv_slip' : 'grs_slip';
        }

        if ($jenis === null && $role !== 'unknown') {
            $jenis = str_contains($role, 'rdtv') ? 'RDTV' : 'GRS';
        }

        // Lengkapi info PO/DO dari DeliveryOrderReceipt jika nama file cocok dengan document_code
        $doVendorName = null;
        if ($filenameMatchesDo) {
            $do = DeliveryOrderReceipt::with('deliveryOrderReceiptDetails.purchaseOrderIssued')
                ->where('document_code', $documentCode)
                ->first();

            if ($do) {
                $firstPo = $do->deliveryOrderReceiptDetails->first()?->purchaseOrderIssued;
                $noPo ??= $firstPo?->purchase_order_no;
                $noDo ??= $do->delivery_order_no ?: null;
                $doVendorName = $firstPo?->vendor_name;
            }
        }

        $hasCompleteNumbers = (filled($noGrs) || filled($noRdtv)) && filled($noPo);
        $needsOcr = ! $ocrDone && (! $hasCompleteNumbers || $role === 'unknown');

        return [
            'original_name' => $originalName,
            'document_code' => $documentCode !== '' ? $documentCode : null,
            'filename_matches_do' => $filenameMatchesDo,
            'role' => $role,
            'jenis' => $jenis ?? 'GRS',
            'no_grs' => $noGrs,
            'no_rdtv' => $noRdtv,
            'no_po' => $noPo,
            'no_do' => $noDo,
            'delivery_date' => $parsed['delivery_date'],
            'tanggal_release_gr' => $parsed['tanggal_release_gr'],
            'tanggal_penolakan' => $parsed['tanggal_penolakan'],
            'tanggal_penerimaan' => $parsed['tanggal_penerimaan'],
            'tanggal_po' => $parsed['tanggal_po'],
            'vendor_name' => $parsed['vendor_name'] ?: $doVendorName,
            'material_code' => $parsed['material_code'] ?? null,
            'nama_barang' => $parsed['nama_barang'] ?? null,
            'uoi' => $parsed['uoi'] ?? null,
            'qty_penagihan' => $parsed['qty_penagihan'] ?? null,
            'subtotal_exclude_ppn' => $parsed['subtotal_exclude_ppn'] ?? null,
            'nilai_po' => $parsed['nilai_po'] ?? null,
            'nilai_gr' => $parsed['nilai_gr'] ?? null,
            'lokasi_penerimaan' => $lokasi,
            'alasan_penolakan' => $parsed['alasan_penolakan'],
            'needs_ocr' => $needsOcr,
            'confidence' => $parsed['confidence'],
        ];
    }

    /**
     * Validasi dry-run untuk seluruh baris hasil pemetaan (Pasangan Lengkap, Slip Saja, dan Verifikasi Saja).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function precheckItems(array $rows, string $transactionDate): array
    {
        $results = [];
        $seenDocCodes = [];
        $seenDocNumbers = [];

        foreach ($rows as $row) {
            $rowId = (string) ($row['row_id'] ?? '');
            $mode = (string) ($row['mode'] ?? 'pair');
            $jenis = strtoupper(trim((string) ($row['jenis'] ?? 'GRS'))) === 'RDTV' ? 'RDTV' : 'GRS';
            $documentCode = $this->processingService->stripStoredFilenameSuffix(trim((string) ($row['document_code'] ?? '')));
            $noGrs = trim((string) ($row['no_grs'] ?? ''));
            $noRdtv = trim((string) ($row['no_rdtv'] ?? ''));
            $noPo = trim((string) ($row['no_po'] ?? ''));
            $noDo = trim((string) ($row['no_do'] ?? ''));
            $verifNoGrs = trim((string) ($row['verif_no_grs'] ?? ''));
            $verifNoRdtv = trim((string) ($row['verif_no_rdtv'] ?? ''));
            $verifNoPo = trim((string) ($row['verif_no_po'] ?? ''));
            $rowLokasi = trim((string) ($row['lokasi_penerimaan'] ?? ''));
            $rowTanggalPenerimaan = trim((string) ($row['tanggal_penerimaan'] ?? ''));
            $rowMaterialCode = trim((string) ($row['material_code'] ?? ''));
            $rowNamaBarang = trim((string) ($row['nama_barang'] ?? ''));
            $rowVendorName = trim((string) ($row['vendor_name'] ?? ''));

            if ($mode === 'verif_only') {
                $match = $this->findMatchingReceiptForVerification(
                    jenis: $jenis,
                    noGrs: $noGrs !== '' ? $noGrs : $verifNoGrs,
                    noRdtv: $noRdtv !== '' ? $noRdtv : $verifNoRdtv,
                    noPo: $noPo !== '' ? $noPo : $verifNoPo,
                    noDo: $noDo,
                );

                if ($match['receipt']) {
                    $receipt = $match['receipt'];
                    $receipt->loadMissing(['grsRdtvItem', 'vendor']);
                    $emails = $this->emailDispatcher->resolveVendorEmails($receipt);
                    $hasSlip = filled($receipt->grsRdtvItem?->file_path)
                        && Storage::disk('public')->exists((string) $receipt->grsRdtvItem->file_path);
                    $hasExistingVerif = filled($receipt->dokumen_verifikasi_grs)
                        && ! str_starts_with((string) $receipt->dokumen_verifikasi_grs, 'grs-rdtv-docs/');

                    $notes = ["Cocok dengan Invoice Barang #{$receipt->id} (PO: {$receipt->no_po})."];
                    $status = 'ready';
                    $reasonCode = null;

                    if ($hasExistingVerif) {
                        $status = 'warning';
                        $reasonCode = 'already_verified';
                        $notes = ["Invoice Barang #{$receipt->id} (PO: {$receipt->no_po}) sudah memiliki Surat Verifikasi sebelumnya (Status: {$receipt->status}). Tidak dicentang otomatis agar tidak menimpa berkas lama; centang hanya bila ingin memperbarui Surat Verifikasi."];
                    }

                    if (! $hasSlip) {
                        $status = 'warning';
                        $notes[] = 'Berkas Slip GRS/RDTV fisik belum tersedia di database — status tetap menjadi Terverifikasi, namun email ditahan sampai 2 berkas lengkap.';
                    }
                    if ($emails === []) {
                        $notes[] = 'Alamat email vendor kosong (email tidak akan terkirim otomatis).';
                    }

                    $results[] = [
                        'row_id' => $rowId,
                        'status' => $status,
                        'reason_code' => $reasonCode,
                        'already_has_verif' => $hasExistingVerif,
                        'message' => implode(' ', $notes),
                        'target_receipt_id' => $receipt->id,
                        'vendor_name' => $receipt->vendor_name ?: ($receipt->vendor?->name ?: '-'),
                        'vendor_emails' => $emails,
                        'resolved_no_po' => $receipt->no_po,
                        'resolved_no_do' => $receipt->no_do,
                        'has_slip' => $hasSlip,
                    ];
                } else {
                    $results[] = [
                        'row_id' => $rowId,
                        'status' => 'blocked',
                        'reason_code' => $match['ambiguous'] ? 'ambiguous_partial' : 'receipt_not_found',
                        'message' => $match['message'],
                        'target_receipt_id' => null,
                        'vendor_name' => '-',
                        'vendor_emails' => [],
                        'resolved_no_po' => $noPo !== '' ? $noPo : ($verifNoPo ?: null),
                        'resolved_no_do' => $noDo !== '' ? $noDo : null,
                        'has_slip' => false,
                    ];
                }

                continue;
            }

            // Mode A (pair) & Mode B (slip_only)
            if ($documentCode === '') {
                $results[] = [
                    'row_id' => $rowId,
                    'status' => 'blocked',
                    'reason_code' => 'missing_document_code',
                    'message' => 'Nama berkas Slip GRS/RDTV (kode dokumen DO) kosong.',
                    'target_receipt_id' => null,
                    'vendor_name' => '-',
                    'vendor_emails' => [],
                    'resolved_no_po' => $noPo ?: null,
                    'resolved_no_do' => $noDo ?: null,
                    'has_slip' => true,
                ];

                continue;
            }

            $batchDocNo = $jenis === 'RDTV'
                ? ($noRdtv !== '' ? $noRdtv : $verifNoRdtv)
                : ($noGrs !== '' ? $noGrs : $verifNoGrs);
            $dupKey = strtolower($jenis.'|'.$documentCode.'|'.$batchDocNo);
            $docNoKey = $batchDocNo !== '' ? strtolower($jenis.'|'.$batchDocNo) : null;

            if (isset($seenDocCodes[$dupKey]) || ($docNoKey !== null && isset($seenDocNumbers[$docNoKey]))) {
                $dupLabel = $docNoKey !== null && isset($seenDocNumbers[$docNoKey])
                    ? "No. {$jenis} {$batchDocNo}"
                    : "berkas {$documentCode}";

                $results[] = [
                    'row_id' => $rowId,
                    'status' => 'blocked',
                    'reason_code' => 'duplicate_in_batch',
                    'message' => "Duplikat {$dupLabel} terdeteksi di dalam antrean unggah yang sama.",
                    'target_receipt_id' => null,
                    'vendor_name' => '-',
                    'vendor_emails' => [],
                    'resolved_no_po' => $noPo ?: null,
                    'resolved_no_do' => $noDo ?: null,
                    'has_slip' => true,
                ];

                continue;
            }
            $seenDocCodes[$dupKey] = true;
            if ($docNoKey !== null) {
                $seenDocNumbers[$docNoKey] = true;
            }

            $eligibility = $this->processingService->inspectDocumentEligibility(
                documentCode: $documentCode,
                category: $jenis,
                uploadedNoGrs: $jenis === 'GRS' && $noGrs !== '' ? $noGrs : null,
            );

            $do = $eligibility['do'];
            $firstPo = $do?->deliveryOrderReceiptDetails?->first()?->purchaseOrderIssued;
            $vendorName = $firstPo?->vendor_name ?: '-';
            $vendorEmails = [];

            if ($firstPo && ($firstPo->vendor_id || $firstPo->vendor_name)) {
                $vendor = Vendor::findBestMatch(
                    vendorNumber: $firstPo->vendor_id,
                    nameCandidates: [$firstPo->vendor_name, $rowVendorName],
                );

                if ($vendor) {
                    $vendorName = $vendorName !== '-' ? $vendorName : ($vendor->name ?: '-');
                    $vendorEmails = array_values(array_filter(
                        Vendor::parseEmails($vendor->email),
                        static fn (string $email): bool => (bool) filter_var($email, FILTER_VALIDATE_EMAIL),
                    ));
                }
            }

            $status = $eligibility['status'];
            $reasonCode = $eligibility['reason_code'];
            $messages = [$eligibility['message']];
            $targetReceiptId = null;
            $alreadyHasVerif = false;
            $willAutoCreateDo = false;
            $willAutoCreatePo = false;
            $autoReceiverName = null;
            $autoLocationName = null;

            $effectivePoNo = $firstPo?->purchase_order_no ?: ($noPo !== '' ? $noPo : $verifNoPo);
            $effectiveDocNo = $jenis === 'RDTV'
                ? ($noRdtv !== '' ? $noRdtv : $verifNoRdtv)
                : ($noGrs !== '' ? $noGrs : $verifNoGrs);

            // Cegah duplikasi DO & Invoice Barang bila kode DO belum cocok (mis. nama file berubah),
            // tetapi No. GRS / No. RDTV sudah terdaftar di Invoice Barang eksisting.
            $existingByDocNo = ($do === null && $effectiveDocNo !== '')
                ? $this->findExistingReceiptByExactDocNumber($jenis, $effectiveDocNo)
                : null;

            if ($existingByDocNo !== null) {
                $existingByDocNo->loadMissing(['grsRdtvItem', 'vendor', 'deliveryOrderReceipt']);
                $targetReceiptId = $existingByDocNo->id;
                $vendorName = $existingByDocNo->vendor_name ?: ($existingByDocNo->vendor?->name ?: ($vendorName !== '-' ? $vendorName : '-'));
                $vendorEmails = $this->emailDispatcher->resolveVendorEmails($existingByDocNo);
                $hasPhysicalSlip = filled($existingByDocNo->grsRdtvItem?->file_path)
                    && Storage::disk('public')->exists((string) $existingByDocNo->grsRdtvItem->file_path);
                $alreadyHasVerif = filled($existingByDocNo->dokumen_verifikasi_grs)
                    && ! str_starts_with((string) $existingByDocNo->dokumen_verifikasi_grs, 'grs-rdtv-docs/');

                if ($mode === 'pair') {
                    $status = 'warning';
                    $reasonCode = $alreadyHasVerif ? 'already_verified' : 'reuse_existing_slip';

                    if ($alreadyHasVerif) {
                        $messages = ["No. {$jenis} {$effectiveDocNo} sudah ada pada Invoice Barang #{$existingByDocNo->id} dan sudah memiliki Surat Verifikasi (Status: {$existingByDocNo->status}). Tidak dicentang otomatis agar tidak menimpa berkas lama."];
                    } elseif ($hasPhysicalSlip) {
                        $messages = ["No. {$jenis} {$effectiveDocNo} sudah terdaftar pada Invoice Barang #{$existingByDocNo->id}. Berkas Slip lama tetap dipertahankan (tidak membuat DO/Invoice duplikat); hanya berkas Surat Verifikasi yang akan ditambahkan dan diverifikasi."];
                    } else {
                        $messages = ["No. {$jenis} {$effectiveDocNo} sudah terdaftar pada Invoice Barang #{$existingByDocNo->id} (belum ada berkas Slip fisik). Slip & Surat Verifikasi akan dilampirkan ke Invoice #{$existingByDocNo->id} tanpa membuat duplikat."];
                    }

                    if ($vendorEmails === []) {
                        $messages[] = 'Email vendor belum terdaftar (dokumen tetap Terverifikasi, namun email otomatis ditahan).';
                    }
                } elseif ($hasPhysicalSlip) {
                    $status = 'blocked';
                    $reasonCode = 'already_processed';
                    $messages = ["No. {$jenis} {$effectiveDocNo} sudah terdaftar pada Invoice Barang #{$existingByDocNo->id}. Berkas Slip lama tetap dipertahankan dan tidak ditimpa."];
                } else {
                    $status = 'warning';
                    $reasonCode = 'attach_slip_to_existing';
                    $messages = ["No. {$jenis} {$effectiveDocNo} sudah terdaftar pada Invoice Barang #{$existingByDocNo->id} (belum ada berkas Slip fisik). Berkas Slip akan dilampirkan tanpa membuat duplikat."];
                }
            } elseif ($do === null && $effectivePoNo !== '') {
                // Jika DO belum ditemukan di Penerimaan Barang namun Nomor PO tersedia, siapkan rencana auto-create DO (+ PO Terbit jika belum ada)
                $existingPo = PurchaseOrderIssued::query()->where('purchase_order_no', $effectivePoNo)->first();
                $willAutoCreateDo = true;
                $willAutoCreatePo = $existingPo === null;

                $resolvedMatType = InvoicingReceipt::resolveMaterialType(
                    $existingPo?->material_type,
                    array_filter([$existingPo?->material_code, $rowMaterialCode]),
                    array_filter([$existingPo?->description, $rowNamaBarang]),
                    $effectivePoNo,
                );

                $receiverInfo = $this->resolveAutoReceiverInfo($resolvedMatType);
                $autoReceiverName = $receiverInfo['label'];
                $autoLocationName = $this->normalizeLocationName($rowLokasi);
                $locationExists = $this->locationExists($autoLocationName);

                if ($vendorName === '-') {
                    $lookupVendor = $this->findVendorByPoOrName($existingPo, $rowVendorName);
                    if ($lookupVendor) {
                        $vendorName = $lookupVendor->name ?: ($existingPo?->vendor_name ?: ($rowVendorName ?: '-'));
                        $vendorEmails = array_values(array_filter(
                            Vendor::parseEmails($lookupVendor->email),
                            static fn (string $email): bool => (bool) filter_var($email, FILTER_VALIDATE_EMAIL),
                        ));
                    } elseif ($existingPo?->vendor_name) {
                        $vendorName = $existingPo->vendor_name;
                    } elseif ($rowVendorName !== '') {
                        $vendorName = $rowVendorName;
                    }
                }

                $tglTerimaLabel = $rowTanggalPenerimaan !== '' ? $rowTanggalPenerimaan : $transactionDate;
                $locStatusNote = $locationExists ? $autoLocationName : "{$autoLocationName} (lokasi baru)";
                $poNote = $willAutoCreatePo
                    ? "PO Terbit baru ({$effectivePoNo}) + DO Receipt"
                    : "DO Receipt (taut ke PO {$effectivePoNo})";

                $status = 'warning';
                $reasonCode = 'auto_create_do';
                $messages = ["Kode DO belum ada di Penerimaan Barang — sistem otomatis membuat {$poNote} (Tgl Terima: {$tglTerimaLabel}, Diterima Oleh: {$autoReceiverName}, Lokasi: {$locStatusNote}, Tipe: {$resolvedMatType})."];
            }

            if ($mode === 'pair') {
                // Validasi konsistensi nomor antar Slip & Surat Verifikasi
                $slipDocNo = $jenis === 'RDTV' ? $noRdtv : $noGrs;
                $verifDocNo = $jenis === 'RDTV' ? $verifNoRdtv : $verifNoGrs;

                if ($slipDocNo !== '' && $verifDocNo !== '' && strcasecmp($slipDocNo, $verifDocNo) !== 0) {
                    $status = 'blocked';
                    $messages[] = "Nomor {$jenis} tidak cocok antara Slip ({$slipDocNo}) dan Verifikasi ({$verifDocNo}). Periksa pemetaan pasangan.";
                } elseif ($noPo !== '' && $verifNoPo !== '' && strcasecmp($noPo, $verifNoPo) !== 0) {
                    $status = 'blocked';
                    $messages[] = "Nomor PO tidak cocok antara Slip ({$noPo}) dan Verifikasi ({$verifNoPo}).";
                } elseif ($do === null && ! $willAutoCreateDo && $existingByDocNo === null) {
                    $status = 'blocked';
                    $messages[] = 'Kode DO belum ditemukan dan Nomor PO kosong — isi Nomor PO agar sistem dapat membuat DO Receipt & PO Terbit otomatis.';
                } elseif ($reasonCode === 'already_processed' && $do !== null) {
                    // Slip sudah pernah diunggah sebelumnya: jangan timpa Slip lama, arahkan ke Invoice Barang eksisting untuk memasang Surat Verifikasi
                    $existingReceipt = $this->findExistingReceiptForUploadedSlip(
                        do: $do,
                        jenis: $jenis,
                        noGrs: $noGrs !== '' ? $noGrs : $verifNoGrs,
                        noRdtv: $noRdtv !== '' ? $noRdtv : $verifNoRdtv,
                        noPo: $effectivePoNo,
                        noDo: $do->delivery_order_no ?: $noDo,
                    );

                    if ($existingReceipt) {
                        $targetReceiptId = $existingReceipt->id;
                        $alreadyHasVerif = filled($existingReceipt->dokumen_verifikasi_grs)
                            && ! str_starts_with((string) $existingReceipt->dokumen_verifikasi_grs, 'grs-rdtv-docs/');
                        $status = 'warning';
                        $reasonCode = $alreadyHasVerif ? 'already_verified' : 'reuse_existing_slip';

                        if ($alreadyHasVerif) {
                            $messages = ["Slip {$jenis} & Surat Verifikasi untuk Invoice #{$existingReceipt->id} sudah ada di sistem (Status: {$existingReceipt->status}). Tidak dicentang otomatis agar tidak menimpa berkas lama; centang hanya bila ingin memperbarui Surat Verifikasi."];
                        } else {
                            $messages = ["Slip {$jenis} ini sudah ada di sistem (Invoice #{$existingReceipt->id}). Berkas Slip lama tetap dipertahankan (tidak ditimpa); hanya berkas Surat Verifikasi yang akan ditambahkan dan diverifikasi."];
                        }

                        if ($vendorEmails === []) {
                            $messages[] = 'Email vendor belum terdaftar (dokumen tetap Terverifikasi, namun email otomatis ditahan).';
                        }
                    }
                } elseif (($status === 'ready' || $willAutoCreateDo) && $vendorEmails === []) {
                    $status = 'warning';
                    $messages[] = 'Email vendor belum terdaftar (dokumen tetap Terverifikasi, namun email otomatis ditahan).';
                }
            } elseif ($mode === 'slip_only' && $status === 'ready') {
                $messages = ['Siap diproses — akan membuat Invoice Barang berstatus Menunggu Verifikasi (tanpa kirim email dulu).'];
            } elseif ($mode === 'slip_only' && $willAutoCreateDo) {
                $messages[] = 'Invoice Barang akan dibuat dengan status Menunggu Verifikasi (tanpa kirim email dulu).';
            } elseif ($mode === 'slip_only' && $reasonCode === 'already_processed' && $existingByDocNo === null) {
                $messages[] = 'Berkas Slip lama tetap dipertahankan dan tidak ditimpa.';
            }

            $results[] = [
                'row_id' => $rowId,
                'status' => $status,
                'reason_code' => $reasonCode,
                'already_has_verif' => $alreadyHasVerif,
                'will_auto_create_do' => $willAutoCreateDo,
                'will_auto_create_po' => $willAutoCreatePo,
                'auto_receiver_name' => $autoReceiverName,
                'auto_location_name' => $autoLocationName,
                'message' => implode(' ', array_unique($messages)),
                'target_receipt_id' => $targetReceiptId,
                'vendor_name' => $vendorName,
                'vendor_emails' => $vendorEmails,
                'resolved_no_po' => $effectivePoNo !== '' ? $effectivePoNo : null,
                'resolved_no_do' => $do?->delivery_order_no ?: ($noDo ?: null),
                'has_slip' => true,
            ];
        }

        return $results;
    }

    /**
     * Mengeksekusi penyimpanan 1 baris (Pasangan Lengkap, Slip Saja, atau Verifikasi Saja) secara atomik.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function commitRow(
        array $payload,
        ?UploadedFile $slipFile,
        ?UploadedFile $verifFile,
        ?int $actorId = null,
    ): array {
        $mode = (string) ($payload['mode'] ?? 'pair');
        $jenis = strtoupper(trim((string) ($payload['jenis'] ?? 'GRS'))) === 'RDTV' ? 'RDTV' : 'GRS';
        $transactionDate = (string) ($payload['transaction_date'] ?? now()->toDateString());
        $sendEmail = filter_var($payload['send_email'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $reason = trim((string) ($payload['reason'] ?? ''));
        $overrideNoGrs = trim((string) ($payload['no_grs'] ?? ''));
        $overrideNoRdtv = trim((string) ($payload['no_rdtv'] ?? ''));
        $overrideNoPo = trim((string) ($payload['no_po'] ?? ''));
        $overrideNoDo = trim((string) ($payload['no_do'] ?? ''));
        $overrideLokasi = trim((string) ($payload['lokasi_penerimaan'] ?? ''));
        $overrideTanggalPenerimaan = trim((string) ($payload['tanggal_penerimaan'] ?? ''));
        $overrideVendorName = trim((string) ($payload['vendor_name'] ?? ''));
        $overrideVendorEmail = trim((string) ($payload['vendor_email'] ?? ''));
        $overrideMaterialCode = trim((string) ($payload['material_code'] ?? ''));
        $overrideNamaBarang = trim((string) ($payload['nama_barang'] ?? ''));
        $targetReceiptId = isset($payload['target_receipt_id']) && is_numeric($payload['target_receipt_id'])
            ? (int) $payload['target_receipt_id']
            : null;

        if ($mode === 'verif_only') {
            return $this->commitVerificationOnly(
                verifFile: $verifFile,
                jenis: $jenis,
                noGrs: $overrideNoGrs,
                noRdtv: $overrideNoRdtv,
                noPo: $overrideNoPo,
                noDo: $overrideNoDo,
                targetReceiptId: $targetReceiptId,
                sendEmail: $sendEmail,
                actorId: $actorId,
                overrideVendorEmail: $overrideVendorEmail,
            );
        }

        if (! $slipFile instanceof UploadedFile) {
            return [
                'success' => false,
                'message' => 'Berkas Slip GRS/RDTV wajib disertakan untuk mode ini.',
            ];
        }

        if ($mode === 'pair' && ! $verifFile instanceof UploadedFile) {
            return [
                'success' => false,
                'message' => 'Berkas Surat Verifikasi wajib disertakan untuk mode Pasangan Lengkap.',
            ];
        }

        $documentCode = $this->processingService->extractDocumentCode($slipFile);

        // Ekstrak metadata Slip (dan Surat Verifikasi bila ada) untuk validasi parsial serta auto-create DO/PO/Lokasi
        $parsedSlip = [];
        try {
            $parsedSlip = $this->pdfParser->parse((string) $slipFile->getRealPath());
        } catch (Throwable $e) {
            report($e);
        }

        $parsedVerif = [];
        if ($verifFile instanceof UploadedFile) {
            try {
                $parsedVerif = $this->pdfParser->parseVerifikasiGrs(
                    (string) $verifFile->getRealPath(),
                    $verifFile->getClientOriginalName(),
                );
            } catch (Throwable $e) {
                report($e);
            }
        }

        $detectedNoGrs = $overrideNoGrs !== '' ? $overrideNoGrs : trim((string) ($parsedSlip['no_grs'] ?? ($parsedVerif['no_grs'] ?? '')));
        $detectedNoPo = $overrideNoPo !== '' ? $overrideNoPo : trim((string) ($parsedSlip['no_po'] ?? ($parsedVerif['no_po'] ?? '')));

        $eligibility = $this->processingService->inspectDocumentEligibility(
            documentCode: $documentCode,
            category: $jenis,
            uploadedNoGrs: $detectedNoGrs !== '' ? $detectedNoGrs : null,
        );

        if ($eligibility['status'] === 'blocked') {
            // Khusus Mode Pasangan Lengkap: bila Slip sudah ada sebelumnya, pertahankan Slip lama (tidak ditimpa)
            // dan cukup pasang Surat Verifikasi ke Invoice Barang eksisting.
            if ($mode === 'pair' && $eligibility['reason_code'] === 'already_processed' && $eligibility['do'] !== null) {
                $existingReceipt = $targetReceiptId !== null
                    ? InvoicingReceipt::find($targetReceiptId)
                    : $this->findExistingReceiptForUploadedSlip(
                        do: $eligibility['do'],
                        jenis: $jenis,
                        noGrs: $detectedNoGrs,
                        noRdtv: $overrideNoRdtv,
                        noPo: $detectedNoPo,
                        noDo: $overrideNoDo ?: (string) ($eligibility['do']->delivery_order_no ?? ''),
                    );

                if ($existingReceipt) {
                    $reuseRes = $this->commitVerificationOnly(
                        verifFile: $verifFile,
                        jenis: $jenis,
                        noGrs: $detectedNoGrs,
                        noRdtv: $overrideNoRdtv,
                        noPo: $detectedNoPo,
                        noDo: $overrideNoDo,
                        targetReceiptId: $existingReceipt->id,
                        sendEmail: $sendEmail,
                        actorId: $actorId,
                        overrideVendorEmail: $overrideVendorEmail,
                    );

                    if ($reuseRes['success'] ?? false) {
                        $reuseRes['reused_existing_slip'] = true;
                        $reuseRes['message'] = "Slip {$jenis} lama dipertahankan (tidak ditimpa). ".$reuseRes['message'];
                    }

                    return $reuseRes;
                }
            }

            return [
                'success' => false,
                'message' => $eligibility['message'],
            ];
        }

        if ($mode === 'pair' && $eligibility['do'] === null && $detectedNoPo === '') {
            $detectedDocNoFallback = $jenis === 'RDTV'
                ? ($overrideNoRdtv !== '' ? $overrideNoRdtv : trim((string) ($parsedSlip['no_rdtv'] ?? ($parsedVerif['no_rdtv'] ?? ''))))
                : $detectedNoGrs;

            if ($detectedDocNoFallback === '' || ! $this->findExistingReceiptByExactDocNumber($jenis, $detectedDocNoFallback)) {
                return [
                    'success' => false,
                    'message' => "Kode DO {$documentCode} tidak ditemukan dan Nomor PO kosong sehingga DO Receipt & PO Terbit tidak dapat dibuat otomatis.",
                ];
            }
        }

        // Cegah duplikasi DO & Invoice Barang apabila DO belum ditemukan dari nama berkas,
        // tetapi No. GRS / No. RDTV sudah terdaftar pada Invoice Barang eksisting.
        $detectedDocNo = $jenis === 'RDTV'
            ? ($overrideNoRdtv !== '' ? $overrideNoRdtv : trim((string) ($parsedSlip['no_rdtv'] ?? ($parsedVerif['no_rdtv'] ?? ''))))
            : $detectedNoGrs;

        if ($eligibility['do'] === null && $detectedDocNo !== '') {
            $existingByDocNo = ($targetReceiptId !== null ? InvoicingReceipt::find($targetReceiptId) : null)
                ?? $this->findExistingReceiptByExactDocNumber($jenis, $detectedDocNo);

            if ($existingByDocNo) {
                $existingByDocNo->loadMissing(['grsRdtvItem', 'deliveryOrderReceipt']);
                $hasPhysicalSlip = filled($existingByDocNo->grsRdtvItem?->file_path)
                    && Storage::disk('public')->exists((string) $existingByDocNo->grsRdtvItem->file_path);

                if ($mode === 'slip_only' && $hasPhysicalSlip) {
                    return [
                        'success' => false,
                        'message' => "No. {$jenis} {$detectedDocNo} sudah terdaftar pada Invoice Barang #{$existingByDocNo->id}. Berkas Slip lama tetap dipertahankan dan tidak ditimpa.",
                    ];
                }

                // Jika Invoice eksisting belum memiliki Slip fisik (mis. hasil impor Monitoring), simpan Slip & tautkan tanpa membuat duplikat Invoice
                if (! $hasPhysicalSlip) {
                    $batch = $this->processingService->findOrCreateBatch($transactionDate, $jenis, $actorId);
                    $attachedSlipPath = $this->processingService->storeDocumentFile($slipFile, $documentCode);
                    $attachedItem = $batch->grsRdtvItems()->create([
                        'delivery_order_receipt_id' => $existingByDocNo->delivery_order_receipt_id,
                        'document_code' => $documentCode,
                        'file_path' => $attachedSlipPath,
                        'status' => $existingByDocNo->delivery_order_receipt_id ? 'Matched' : 'Not Found',
                        'reason' => $jenis === 'RDTV' && $reason !== '' ? $reason : null,
                    ]);
                    $existingByDocNo->update(['grs_rdtv_item_id' => $attachedItem->id]);

                    if ($mode === 'slip_only') {
                        return [
                            'success' => true,
                            'mode' => $mode,
                            'jenis' => $jenis,
                            'grs_rdtv_id' => $batch->id,
                            'grs_rdtv_item_id' => $attachedItem->id,
                            'receipt_id' => $existingByDocNo->id,
                            'no_po' => $existingByDocNo->no_po,
                            'no_grs' => $existingByDocNo->no_grs,
                            'no_rdtv' => $existingByDocNo->no_rdtv,
                            'status' => $existingByDocNo->status,
                            'email_status' => 'waiting_verification',
                            'email_message' => 'Menunggu berkas Surat Verifikasi diunggah di kemudian hari.',
                            'message' => "Slip {$jenis} dilampirkan ke Invoice Barang #{$existingByDocNo->id} tanpa membuat duplikat.",
                        ];
                    }
                }

                if ($mode === 'pair') {
                    $reuseRes = $this->commitVerificationOnly(
                        verifFile: $verifFile,
                        jenis: $jenis,
                        noGrs: $detectedNoGrs,
                        noRdtv: $overrideNoRdtv,
                        noPo: $detectedNoPo,
                        noDo: $overrideNoDo,
                        targetReceiptId: $existingByDocNo->id,
                        sendEmail: $sendEmail,
                        actorId: $actorId,
                        overrideVendorEmail: $overrideVendorEmail,
                    );

                    if ($reuseRes['success'] ?? false) {
                        $reuseRes['reused_existing_slip'] = $hasPhysicalSlip;
                        $prefix = $hasPhysicalSlip
                            ? "Slip {$jenis} lama pada Invoice #{$existingByDocNo->id} dipertahankan (tidak membuat duplikat). "
                            : "Slip {$jenis} dilampirkan ke Invoice #{$existingByDocNo->id} tanpa membuat duplikat. ";
                        $reuseRes['message'] = $prefix.$reuseRes['message'];
                    }

                    return $reuseRes;
                }
            }
        }

        $storedSlipPath = null;
        $storedVerifPath = null;

        try {
            /** @var array{receipt: ?InvoicingReceipt, grs_rdtv_id: int, grs_rdtv_item_id: int, do_matched: bool, auto_created_do: bool, auto_created_po: bool, auto_created_location: bool, auto_receiver_name: ?string, auto_location_name: ?string} $txResult */
            $txResult = DB::transaction(function () use (
                $transactionDate,
                $jenis,
                $actorId,
                $slipFile,
                $verifFile,
                $documentCode,
                $mode,
                $reason,
                $overrideNoGrs,
                $overrideNoRdtv,
                $overrideNoDo,
                $overrideLokasi,
                $overrideTanggalPenerimaan,
                $overrideVendorName,
                $overrideVendorEmail,
                $overrideMaterialCode,
                $overrideNamaBarang,
                $detectedNoPo,
                $parsedSlip,
                $parsedVerif,
                &$storedSlipPath,
                &$storedVerifPath,
            ): array {
                $batch = $this->processingService->findOrCreateBatch($transactionDate, $jenis, $actorId);
                $storedSlipPath = $this->processingService->storeDocumentFile($slipFile, $documentCode);

                $do = DeliveryOrderReceipt::where('document_code', $documentCode)->first();
                $autoCreatedDo = false;
                $autoCreatedPo = false;
                $autoCreatedLocation = false;
                $autoReceiverName = null;
                $autoLocationName = null;

                if ($do) {
                    if ($jenis === 'RDTV') {
                        $do->update([
                            'status' => 'RDTV',
                            'delay_reason' => 'RDTV',
                            'delay_notes' => $reason !== '' ? $reason : $do->delay_notes,
                        ]);
                    } else {
                        $updateDo = ['status' => 'GRS'];
                        if ($do->delay_reason === 'RDTV') {
                            $updateDo['delay_reason'] = null;
                            $updateDo['delay_notes'] = null;
                        }
                        $do->update($updateDo);
                    }
                } elseif ($detectedNoPo !== '') {
                    $autoBuilt = $this->autoCreateDeliveryOrderForMissingDo(
                        documentCode: $documentCode,
                        noPo: $detectedNoPo,
                        jenis: $jenis,
                        transactionDate: $transactionDate,
                        reason: $reason,
                        overrideNoDo: $overrideNoDo,
                        overrideLokasi: $overrideLokasi,
                        overrideTanggalPenerimaan: $overrideTanggalPenerimaan,
                        overrideVendorName: $overrideVendorName,
                        overrideMaterialCode: $overrideMaterialCode,
                        overrideNamaBarang: $overrideNamaBarang,
                        parsedSlip: $parsedSlip,
                        parsedVerif: $parsedVerif,
                        actorId: $actorId,
                    );

                    $do = $autoBuilt['do'];
                    $autoCreatedDo = true;
                    $autoCreatedPo = $autoBuilt['po_created'];
                    $autoCreatedLocation = $autoBuilt['location_created'];
                    $autoReceiverName = $autoBuilt['receiver_name'];
                    $autoLocationName = $autoBuilt['location_name'];
                }

                $grsRdtvItem = $batch->grsRdtvItems()->create([
                    'delivery_order_receipt_id' => $do?->id,
                    'document_code' => $documentCode,
                    'file_path' => $storedSlipPath,
                    'status' => $do ? 'Matched' : 'Not Found',
                    'reason' => $jenis === 'RDTV' && $reason !== '' ? $reason : null,
                ]);

                $receipt = $this->processingService->createInvoicingReceiptFor(
                    grsRdtvItem: $grsRdtvItem,
                    do: $do,
                    category: $jenis,
                    transactionDate: $batch->transaction_date,
                    attachSlipAsFallbackVerif: false,
                );

                if ($receipt) {
                    $receiptUpdates = [];

                    if ($jenis === 'RDTV') {
                        if (empty($receipt->no_rdtv) && $overrideNoRdtv !== '') {
                            $receiptUpdates['no_rdtv'] = $overrideNoRdtv;
                        }
                    } else {
                        if (empty($receipt->no_grs) && $overrideNoGrs !== '') {
                            $receiptUpdates['no_grs'] = $overrideNoGrs;
                        }
                    }

                    if (empty($receipt->no_do) && $overrideNoDo !== '') {
                        $receiptUpdates['no_do'] = $overrideNoDo;
                    }

                    if ($overrideVendorEmail !== '') {
                        $validEmails = $this->parseValidEmails($overrideVendorEmail);
                        if ($validEmails !== []) {
                            $receiptUpdates['email_vendor'] = implode(', ', $validEmails);
                            $this->syncMissingEmailToVendorMaster($receipt->vendor, $validEmails);
                        }
                    }

                    if ($mode === 'pair' && $verifFile instanceof UploadedFile) {
                        $verifDir = $jenis === 'RDTV' ? 'verifikasi-rdtv' : 'verifikasi-grs';
                        $storedVerifPath = $verifFile->store($verifDir, 'public') ?: null;

                        if ($storedVerifPath !== null) {
                            $receiptUpdates['dokumen_verifikasi_grs'] = $storedVerifPath;
                            $receiptUpdates['status'] = 'Terverifikasi';
                        }
                    }

                    if ($receiptUpdates !== []) {
                        $receipt->update($receiptUpdates);
                    }
                }

                return [
                    'receipt' => $receipt?->fresh(['grsRdtvItem', 'vendor', 'pic']),
                    'grs_rdtv_id' => $batch->id,
                    'grs_rdtv_item_id' => $grsRdtvItem->id,
                    'do_matched' => $do !== null,
                    'auto_created_do' => $autoCreatedDo,
                    'auto_created_po' => $autoCreatedPo,
                    'auto_created_location' => $autoCreatedLocation,
                    'auto_receiver_name' => $autoReceiverName,
                    'auto_location_name' => $autoLocationName,
                ];
            });
        } catch (Throwable $e) {
            if ($storedSlipPath !== null && Storage::disk('public')->exists($storedSlipPath)) {
                Storage::disk('public')->delete($storedSlipPath);
            }
            if ($storedVerifPath !== null && Storage::disk('public')->exists($storedVerifPath)) {
                Storage::disk('public')->delete($storedVerifPath);
            }

            report($e);

            return [
                'success' => false,
                'message' => 'Gagal menyimpan data: '.$e->getMessage(),
            ];
        }

        $receipt = $txResult['receipt'];
        $emailStatus = $mode === 'pair' ? 'disabled' : 'waiting_verification';
        $emailMessage = $mode === 'pair'
            ? 'Pengiriman email otomatis dinonaktifkan untuk sesi unggah ini.'
            : 'Menunggu berkas Surat Verifikasi diunggah di kemudian hari.';

        if ($mode === 'pair' && $receipt && $sendEmail) {
            $emailResult = $this->emailDispatcher->dispatchIfEligible(
                receipt: $receipt,
                triggerSource: 'combined_upload',
                userId: $actorId,
            );
            $emailStatus = $emailResult['status'];
            $emailMessage = $emailResult['message'];
        }

        $autoNote = '';
        if ($txResult['auto_created_do']) {
            $createdParts = ['DO Receipt'];
            if ($txResult['auto_created_po']) {
                $createdParts[] = 'PO Terbit';
            }
            if ($txResult['auto_created_location']) {
                $createdParts[] = 'Lokasi '.$txResult['auto_location_name'];
            }
            $autoNote = ' (Otomatis membuat '.implode(' + ', $createdParts)
                .', Diterima Oleh: '.($txResult['auto_receiver_name'] ?: '-')
                .', Lokasi: '.($txResult['auto_location_name'] ?: '-').')';
        }

        if ($mode === 'pair') {
            $baseMsg = "Berhasil menyimpan {$jenis} & Verifikasi untuk PO ".($receipt?->no_po ?: '-')." (Status: Terverifikasi){$autoNote}.";
            $suffix = match ($emailStatus) {
                'sent' => ' '.$emailMessage,
                'skipped' => ' Email ditahan: '.$emailMessage,
                'failed' => ' Email gagal dikirim: '.$emailMessage,
                default => '',
            };
        } elseif ($txResult['do_matched'] && $receipt) {
            $baseMsg = "Slip {$jenis} tersimpan & Invoice Barang PO {$receipt->no_po} dibuat (Status: Menunggu Verifikasi){$autoNote}.";
            $suffix = '';
        } else {
            $baseMsg = "Slip {$jenis} ({$documentCode}) tersimpan sebagai Not Found (DO belum terdaftar di sistem).";
            $suffix = '';
        }

        return [
            'success' => true,
            'mode' => $mode,
            'jenis' => $jenis,
            'grs_rdtv_id' => $txResult['grs_rdtv_id'],
            'grs_rdtv_item_id' => $txResult['grs_rdtv_item_id'],
            'receipt_id' => $receipt?->id,
            'no_po' => $receipt?->no_po,
            'no_grs' => $receipt?->no_grs,
            'no_rdtv' => $receipt?->no_rdtv,
            'delivery_date' => $receipt?->delivery_date?->format('Y-m-d'),
            'status' => $receipt?->status ?? ($txResult['do_matched'] ? 'Menunggu Verifikasi' : 'Not Found'),
            'auto_created_do' => $txResult['auto_created_do'],
            'auto_created_po' => $txResult['auto_created_po'],
            'auto_created_location' => $txResult['auto_created_location'],
            'auto_receiver_name' => $txResult['auto_receiver_name'],
            'auto_location_name' => $txResult['auto_location_name'],
            'email_status' => $emailStatus,
            'email_message' => $emailMessage,
            'message' => $baseMsg.$suffix,
        ];
    }

    /**
     * Membuat otomatis PO Terbit (jika belum ada), Lokasi Penerimaan (jika belum ada),
     * dan DeliveryOrderReceipt + Detail ketika berkas GRS/RDTV tidak menemukan kode DO di Penerimaan Barang.
     *
     * @param  array<string, mixed>  $parsedSlip
     * @param  array<string, mixed>  $parsedVerif
     * @return array{
     *     do: DeliveryOrderReceipt,
     *     po: PurchaseOrderIssued,
     *     po_created: bool,
     *     location_created: bool,
     *     receiver_name: string,
     *     location_name: string
     * }
     */
    protected function autoCreateDeliveryOrderForMissingDo(
        string $documentCode,
        string $noPo,
        string $jenis,
        string $transactionDate,
        string $reason,
        string $overrideNoDo,
        string $overrideLokasi,
        string $overrideTanggalPenerimaan,
        string $overrideVendorName,
        string $overrideMaterialCode,
        string $overrideNamaBarang,
        array $parsedSlip,
        array $parsedVerif,
        ?int $actorId,
    ): array {
        $cleanPo = substr(trim($noPo), 0, 12);

        $receivedDateRaw = $overrideTanggalPenerimaan !== ''
            ? $overrideTanggalPenerimaan
            : (trim((string) ($parsedSlip['tanggal_penerimaan'] ?? ''))
                ?: (trim((string) ($parsedSlip['tanggal_release_gr'] ?? ''))
                    ?: (trim((string) ($parsedVerif['tanggal_release_gr'] ?? '')) ?: $transactionDate)));

        try {
            $receivedDate = Carbon::parse($receivedDateRaw)->toDateString();
        } catch (Throwable) {
            $receivedDate = Carbon::parse($transactionDate)->toDateString();
        }

        $materialCode = $overrideMaterialCode !== ''
            ? $overrideMaterialCode
            : trim((string) ($parsedSlip['material_code'] ?? ''));
        $namaBarang = $overrideNamaBarang !== ''
            ? $overrideNamaBarang
            : trim((string) ($parsedSlip['nama_barang'] ?? ''));
        $uoi = trim((string) ($parsedSlip['uoi'] ?? '')) ?: 'EA';
        $qty = is_numeric($parsedSlip['qty_penagihan'] ?? null) && (float) $parsedSlip['qty_penagihan'] > 0
            ? (float) $parsedSlip['qty_penagihan']
            : 1.0;

        $vendorNameInput = $overrideVendorName !== ''
            ? $overrideVendorName
            : (trim((string) ($parsedSlip['vendor_name'] ?? '')) ?: trim((string) ($parsedVerif['vendor_name'] ?? '')));

        $po = PurchaseOrderIssued::query()->where('purchase_order_no', $cleanPo)->first();
        $poCreated = false;

        $resolvedMatType = InvoicingReceipt::resolveMaterialType(
            $po?->material_type,
            array_filter([$po?->material_code, $materialCode]),
            array_filter([$po?->description, $namaBarang]),
            $cleanPo,
        );

        $vendor = $this->findVendorByPoOrName($po, $vendorNameInput);

        if (! $po) {
            $poValue = is_numeric($parsedVerif['nilai_po'] ?? null) && (float) $parsedVerif['nilai_po'] > 0
                ? (float) $parsedVerif['nilai_po']
                : (is_numeric($parsedSlip['subtotal_exclude_ppn'] ?? null) && (float) $parsedSlip['subtotal_exclude_ppn'] > 0
                    ? (float) $parsedSlip['subtotal_exclude_ppn']
                    : (is_numeric($parsedVerif['nilai_gr'] ?? null) ? (float) $parsedVerif['nilai_gr'] : 0.0));

            $dateCreate = $receivedDate;
            if (! empty($parsedSlip['tanggal_po'])) {
                try {
                    $dateCreate = Carbon::parse((string) $parsedSlip['tanggal_po'])->toDateString();
                } catch (Throwable) {
                    $dateCreate = $receivedDate;
                }
            }

            $deliveryDatePo = null;
            if (! empty($parsedSlip['delivery_date'])) {
                try {
                    $deliveryDatePo = Carbon::parse((string) $parsedSlip['delivery_date'])->toDateString();
                } catch (Throwable) {
                    $deliveryDatePo = null;
                }
            }

            $poPayload = [
                'purchase_order_and_item' => substr($cleanPo.'-10', 0, 20),
                'purchase_order_no' => $cleanPo,
                'item_no' => 10,
                'material_type' => $resolvedMatType !== InvoicingReceipt::DEFAULT_MATERIAL_TYPE ? substr($resolvedMatType, 0, 5) : null,
                'mrp_type' => $resolvedMatType === 'ZPM' ? 'ND' : 'PD',
                'material_code' => $materialCode !== '' ? substr($materialCode, 0, 20) : null,
                'description' => $namaBarang !== '' ? $namaBarang : "Material PO {$cleanPo}",
                'qty_po' => $qty,
                'uoi' => substr(strtoupper($uoi), 0, 5),
                'vendor_id' => $vendor?->vendor_number ? substr((string) $vendor->vendor_number, 0, 20) : null,
                'vendor_name' => substr($vendor?->name ?: ($vendorNameInput !== '' ? $vendorNameInput : '-'), 0, 100),
                'date_create' => $dateCreate,
                'delivery_date_po' => $deliveryDatePo,
                'currency' => 'IDR',
                'net_price' => $qty > 0 && $poValue > 0 ? round($poValue / $qty, 4) : 0.0,
                'total_amount_in_lc' => $poValue,
                'requisitioner' => 'RECEIVING',
            ];

            $po = PurchaseOrderIssued::create($this->filterColumnsForTable('purchase_order_issueds', $poPayload));
            $poCreated = true;
        }

        // Lokasi Penerimaan: prioritas input user -> Surat Verifikasi -> kolom Keterangan Slip GRS -> GUDANG RECEIVING
        $rawLocation = $overrideLokasi !== ''
            ? $overrideLokasi
            : (trim((string) ($parsedVerif['lokasi_penerimaan'] ?? ''))
                ?: trim((string) ($parsedSlip['lokasi_penerimaan'] ?? '')));
        $locationName = $this->normalizeLocationName($rawLocation);
        $location = null;
        $locationCreated = false;

        if (Schema::hasTable('location_receivings')) {
            $location = LocationReceiving::query()
                ->whereRaw('LOWER(name) = ?', [strtolower($locationName)])
                ->first();

            if (! $location) {
                $location = LocationReceiving::create(['name' => $locationName]);
                $locationCreated = true;
            }
        }

        // Penerima otomatis berdasarkan kategori material: Karung (ZPM) -> Arief, Bahan Baku (ZRM/ZSM) -> Dio Indranata, Sparepart -> Michael Siburian
        $receiverInfo = $this->resolveAutoReceiverInfo($resolvedMatType, $actorId);
        $receivedById = $receiverInfo['user_id'] ?? $actorId ?? (int) (User::query()->value('id') ?? 1);
        $createdById = $actorId ?? $receivedById;

        $noDo = $overrideNoDo !== ''
            ? $overrideNoDo
            : (trim((string) ($parsedSlip['no_do'] ?? '')) ?: trim((string) ($parsedVerif['no_do'] ?? '')));
        $cleanDoNo = substr($noDo !== '' ? $noDo : $documentCode, 0, 25);

        $doPayload = [
            'delivery_order_no' => $cleanDoNo,
            'received_date' => $receivedDate,
            'received_by' => $receivedById,
            'created_by' => $createdById,
            'document_code' => substr($documentCode, 0, 100),
            'status' => $jenis,
            'post_103' => Carbon::parse($receivedDate)->startOfDay(),
            'delay_reason' => $jenis === 'RDTV' ? 'RDTV' : null,
            'delay_notes' => $jenis === 'RDTV' && $reason !== '' ? $reason : null,
            'receipt_mode' => 'Standard',
            'is_physically_received' => true,
            'physical_received_date' => $receivedDate,
            'current_location' => $location?->name ?? $locationName,
            'description' => "Auto-create dari Upload Gabungan {$jenis} ({$documentCode})",
        ];

        $do = DeliveryOrderReceipt::create($this->filterColumnsForTable('delivery_order_receipts', $doPayload));

        $grAmount = is_numeric($parsedVerif['nilai_gr'] ?? null) && (float) $parsedVerif['nilai_gr'] > 0
            ? (float) $parsedVerif['nilai_gr']
            : (is_numeric($parsedSlip['subtotal_exclude_ppn'] ?? null) && (float) $parsedSlip['subtotal_exclude_ppn'] > 0
                ? (float) $parsedSlip['subtotal_exclude_ppn']
                : (float) ($po->total_amount_in_lc ?? 0));

        $detailPayload = [
            'delivery_order_receipt_id' => $do->id,
            'purchase_order_issued_id' => $po->id,
            'item_no' => (int) ($po->item_no ?: 10),
            'quantity' => $qty > 0 ? $qty : (float) ($po->qty_po ?: 1),
            'material_code' => $po->material_code ?: ($materialCode !== '' ? substr($materialCode, 0, 20) : null),
            'description' => $po->description ?: ($namaBarang !== '' ? $namaBarang : "Material PO {$cleanPo}"),
            'uoi' => substr(strtoupper((string) ($po->uoi ?: $uoi)), 0, 5),
            'mrp_type' => $po->mrp_type ?: ($resolvedMatType === 'ZPM' ? 'ND' : 'PD'),
            'material_type' => $resolvedMatType !== InvoicingReceipt::DEFAULT_MATERIAL_TYPE ? substr($resolvedMatType, 0, 5) : null,
            'total_amount_snapshot' => $grAmount,
            'location_id' => $location?->id ?? 1,
            'is_different_location' => false,
            'is_qty_tolerance' => false,
        ];

        DeliveryOrderReceiptDetail::create($this->filterColumnsForTable('delivery_order_receipt_details', $detailPayload));

        if (! in_array(strtoupper($resolvedMatType), ['ZPM', 'ZRM', 'ZSM'], true) && Schema::hasTable('qc_histories')) {
            QcHistory::create([
                'delivery_order_receipt_id' => $do->id,
                'status' => 'Kembali',
                'created_by' => $createdById,
            ]);
        }

        return [
            'do' => $do->fresh(['deliveryOrderReceiptDetails.purchaseOrderIssued']),
            'po' => $po,
            'po_created' => $poCreated,
            'location_created' => $locationCreated,
            'receiver_name' => $receiverInfo['label'],
            'location_name' => $location?->name ?? $locationName,
        ];
    }

    /**
     * Menentukan user penerima DO otomatis berdasarkan kategori material:
     * - Karung (ZPM) -> Arief
     * - Bahan Baku / Supporting (ZRM / ZSM) -> Dio Indranata
     * - Sparepart (ZSP / Non Stock / lainnya) -> Michael Siburian (Michael Marihot Hasiribuan)
     *
     * @return array{user_id: ?int, label: string}
     */
    public function resolveAutoReceiverInfo(string $materialType, ?int $fallbackActorId = null): array
    {
        $upper = strtoupper(trim($materialType));

        if ($upper === 'ZPM') {
            $user = User::query()
                ->where(function (Builder $q): void {
                    $q->whereRaw('LOWER(name) = ?', ['arief'])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%arief%'])
                        ->orWhereRaw('LOWER(email) LIKE ?', ['arief@%']);
                })
                ->first();

            return [
                'user_id' => $user?->id ?? $fallbackActorId,
                'label' => $user?->name ?? 'Arief',
            ];
        }

        if (in_array($upper, ['ZRM', 'ZSM'], true)) {
            $user = User::query()
                ->where(function (Builder $q): void {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%dio indranata%'])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%dio%'])
                        ->orWhereRaw('LOWER(email) LIKE ?', ['dio@%']);
                })
                ->first();

            return [
                'user_id' => $user?->id ?? $fallbackActorId,
                'label' => $user?->name ?? 'Dio Indranata',
            ];
        }

        $user = User::query()
            ->where(function (Builder $q): void {
                $q->whereRaw('LOWER(name) LIKE ?', ['%michael marihot%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%michael siburian%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%michael%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['michael@%']);
            })
            ->first();

        return [
            'user_id' => $user?->id ?? $fallbackActorId,
            'label' => $user?->name ?? 'Michael Siburian',
        ];
    }

    protected function normalizeLocationName(?string $rawLocation): string
    {
        $clean = trim((string) preg_replace('/\s+/', ' ', (string) $rawLocation));
        if ($clean === '' || $clean === '-') {
            return 'GUDANG RECEIVING';
        }

        return substr(strtoupper($clean), 0, 100);
    }

    protected function locationExists(string $locationName): bool
    {
        if (! Schema::hasTable('location_receivings')) {
            return false;
        }

        return LocationReceiving::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($locationName)])
            ->exists();
    }

    protected function findVendorByPoOrName(?PurchaseOrderIssued $po, string $vendorName = ''): ?Vendor
    {
        return Vendor::findBestMatch(
            vendorNumber: $po?->vendor_id,
            nameCandidates: [$po?->vendor_name, $vendorName],
        );
    }

    /**
     * Memfilter payload kolom sesuai kolom aktual tabel agar aman di MySQL maupun schema ringan SQLite test.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function filterColumnsForTable(string $table, array $attributes): array
    {
        $columns = Schema::getColumnListing($table);
        if ($columns === []) {
            return $attributes;
        }

        return array_intersect_key($attributes, array_flip($columns));
    }

    /**
     * Memperbarui email vendor pada Invoice Barang yang pengiriman emailnya ditahan/gagal,
     * menyinkronkan ke Master Vendor bila email di master masih kosong, lalu mengirim ulang email verifikasi.
     *
     * @return array<string, mixed>
     */
    public function updateVendorEmailAndResend(
        int $receiptId,
        string $vendorEmail = '',
        ?int $actorId = null,
    ): array {
        $receipt = InvoicingReceipt::with(['grsRdtvItem', 'vendor', 'pic'])->find($receiptId);
        if (! $receipt) {
            return [
                'success' => false,
                'email_status' => 'failed',
                'message' => "Invoice Barang #{$receiptId} tidak ditemukan.",
            ];
        }

        if ($vendorEmail !== '') {
            $validEmails = $this->parseValidEmails($vendorEmail);
            if ($validEmails === []) {
                return [
                    'success' => false,
                    'email_status' => 'skipped',
                    'message' => 'Format alamat email vendor tidak valid. Contoh: vendor@perusahaan.com (pisahkan dengan koma bila lebih dari satu).',
                ];
            }

            $receipt->email_vendor = implode(', ', $validEmails);
            $receipt->save();
            $this->syncMissingEmailToVendorMaster($receipt->vendor, $validEmails);
        }

        $freshReceipt = $receipt->fresh(['grsRdtvItem', 'vendor', 'pic']);
        $emailResult = $this->emailDispatcher->dispatchIfEligible(
            receipt: $freshReceipt,
            triggerSource: 'combined_upload_retry',
            userId: $actorId,
        );

        $isSent = ($emailResult['status'] ?? '') === 'sent';

        return [
            'success' => $isSent,
            'receipt_id' => $freshReceipt->id,
            'email_vendor' => $freshReceipt->email_vendor,
            'email_status' => $emailResult['status'],
            'email_message' => $emailResult['message'],
            'message' => $emailResult['message'],
        ];
    }

    /**
     * @return list<string>
     */
    protected function parseValidEmails(string $raw): array
    {
        return array_values(array_unique(array_filter(
            Vendor::parseEmails($raw),
            static fn (string $email): bool => (bool) filter_var($email, FILTER_VALIDATE_EMAIL),
        )));
    }

    /**
     * @param  list<string>  $validEmails
     */
    protected function syncMissingEmailToVendorMaster(?Vendor $vendor, array $validEmails): void
    {
        if (! $vendor || $validEmails === []) {
            return;
        }

        $existing = Vendor::parseEmails($vendor->email);
        if ($existing === []) {
            $vendor->update(['email' => $validEmails]);
        }
    }

    /**
     * Memproses baris Mode C (Surat Verifikasi saja, tanpa Slip di batch ini) dengan mencocokkan ke InvoicingReceipt eksisting.
     *
     * @return array<string, mixed>
     */
    protected function commitVerificationOnly(
        ?UploadedFile $verifFile,
        string $jenis,
        string $noGrs,
        string $noRdtv,
        string $noPo,
        string $noDo,
        ?int $targetReceiptId,
        bool $sendEmail,
        ?int $actorId,
        string $overrideVendorEmail = '',
    ): array {
        if (! $verifFile instanceof UploadedFile) {
            return [
                'success' => false,
                'message' => 'Berkas Surat Verifikasi wajib disertakan.',
            ];
        }

        $receipt = null;
        if ($targetReceiptId !== null) {
            $receipt = InvoicingReceipt::find($targetReceiptId);
        }

        if (! $receipt) {
            $match = $this->findMatchingReceiptForVerification($jenis, $noGrs, $noRdtv, $noPo, $noDo);
            $receipt = $match['receipt'];

            if (! $receipt) {
                return [
                    'success' => false,
                    'message' => $match['message'],
                ];
            }
        }

        $resolvedJenis = $jenis ?: ($receipt->jenis === 'RDTV' ? 'RDTV' : 'GRS');
        $storageDir = $resolvedJenis === 'RDTV' ? 'verifikasi-rdtv' : 'verifikasi-grs';
        $storedPath = null;

        try {
            DB::transaction(function () use (
                $verifFile,
                $storageDir,
                $receipt,
                $resolvedJenis,
                $noGrs,
                $noRdtv,
                $noDo,
                $overrideVendorEmail,
                &$storedPath,
            ): void {
                $storedPath = $verifFile->store($storageDir, 'public') ?: null;

                if ($storedPath !== null) {
                    $receipt->dokumen_verifikasi_grs = $storedPath;
                }

                if ($receipt->status === 'Menunggu Verifikasi') {
                    $receipt->status = 'Terverifikasi';
                }

                if ($resolvedJenis === 'RDTV') {
                    if (empty($receipt->no_rdtv) && $noRdtv !== '') {
                        $receipt->no_rdtv = $noRdtv;
                    }
                } else {
                    if (empty($receipt->no_grs) && $noGrs !== '') {
                        $receipt->no_grs = $noGrs;
                    }
                }

                if (empty($receipt->no_do) && $noDo !== '') {
                    $receipt->no_do = $noDo;
                }

                // Pastikan klasifikasi Karung (ZPM) tetap ter-upgrade bila Invoice lama masih tercatat Non Stock
                $receipt->loadMissing(['purchaseOrderIssued', 'deliveryOrderReceipt.deliveryOrderReceiptDetails', 'vendor']);
                $resolvedMatType = InvoicingReceipt::resolveMaterialType(
                    $receipt->material_type,
                    array_filter([
                        $receipt->purchaseOrderIssued?->material_code,
                        ...($receipt->deliveryOrderReceipt?->deliveryOrderReceiptDetails?->pluck('material_code')->all() ?? []),
                    ]),
                    array_filter([
                        $receipt->nama_barang,
                        $receipt->deskripsi,
                        $receipt->purchaseOrderIssued?->description,
                    ]),
                    $receipt->no_po,
                );
                if ($resolvedMatType !== InvoicingReceipt::normalizeMaterialType($receipt->material_type)) {
                    $receipt->material_type = $resolvedMatType;
                }

                // Lengkapi relasi Master Vendor & email otomatis jika masih kosong
                if (! $receipt->vendor_id || blank($receipt->email_vendor)) {
                    $matchedVendor = Vendor::findBestMatch(
                        vendorNumber: $receipt->purchaseOrderIssued?->vendor_id,
                        nameCandidates: [$receipt->vendor_name, $receipt->purchaseOrderIssued?->vendor_name],
                    );

                    if ($matchedVendor) {
                        if (! $receipt->vendor_id) {
                            $receipt->vendor_id = $matchedVendor->id;
                        }
                        if (blank($receipt->vendor_name)) {
                            $receipt->vendor_name = $matchedVendor->name;
                        }
                        if (blank($receipt->email_vendor)) {
                            $masterEmails = $this->parseValidEmails(implode(', ', Vendor::parseEmails($matchedVendor->email)));
                            if ($masterEmails !== []) {
                                $receipt->email_vendor = implode(', ', $masterEmails);
                            }
                        }
                    }
                }

                if ($overrideVendorEmail !== '') {
                    $validEmails = $this->parseValidEmails($overrideVendorEmail);
                    if ($validEmails !== []) {
                        $receipt->email_vendor = implode(', ', $validEmails);
                        $this->syncMissingEmailToVendorMaster($receipt->vendor, $validEmails);
                    }
                }

                $receipt->save();
            });
        } catch (Throwable $e) {
            if ($storedPath !== null && Storage::disk('public')->exists($storedPath)) {
                Storage::disk('public')->delete($storedPath);
            }

            report($e);

            return [
                'success' => false,
                'message' => 'Gagal menyimpan berkas verifikasi: '.$e->getMessage(),
            ];
        }

        $freshReceipt = $receipt->fresh(['grsRdtvItem', 'vendor', 'pic']);
        $emailStatus = 'disabled';
        $emailMessage = 'Pengiriman email otomatis dinonaktifkan untuk sesi unggah ini.';

        if ($sendEmail && $freshReceipt) {
            $emailResult = $this->emailDispatcher->dispatchIfEligible(
                receipt: $freshReceipt,
                triggerSource: 'combined_upload',
                userId: $actorId,
            );
            $emailStatus = $emailResult['status'];
            $emailMessage = $emailResult['message'];
        }

        $baseMsg = "Verifikasi {$resolvedJenis} untuk PO {$freshReceipt->no_po} berhasil disimpan (Status: {$freshReceipt->status}).";
        $suffix = match ($emailStatus) {
            'sent' => ' '.$emailMessage,
            'skipped' => ' Email ditahan: '.$emailMessage,
            'failed' => ' Email gagal dikirim: '.$emailMessage,
            default => '',
        };

        return [
            'success' => true,
            'mode' => 'verif_only',
            'jenis' => $resolvedJenis,
            'receipt_id' => $freshReceipt->id,
            'no_po' => $freshReceipt->no_po,
            'no_grs' => $freshReceipt->no_grs,
            'no_rdtv' => $freshReceipt->no_rdtv,
            'status' => $freshReceipt->status,
            'email_status' => $emailStatus,
            'email_message' => $emailMessage,
            'message' => $baseMsg.$suffix,
        ];
    }

    /**
     * Mencari InvoicingReceipt eksisting milik Slip GRS/RDTV yang sudah pernah diunggah pada DO terkait
     * agar upload pasangan tidak menimpa Slip lama melainkan langsung memasangkan Surat Verifikasi.
     */
    protected function findExistingReceiptForUploadedSlip(
        DeliveryOrderReceipt $do,
        string $jenis,
        string $noGrs = '',
        string $noRdtv = '',
        string $noPo = '',
        string $noDo = '',
    ): ?InvoicingReceipt {
        $do->loadMissing(['grsRdtvItems.grsRdtv', 'grsRdtvItems.invoicingReceipt']);

        $matchingReceipts = $do->grsRdtvItems
            ->filter(fn ($it): bool => $it->grsRdtv !== null && strtoupper((string) $it->grsRdtv->category) === strtoupper($jenis))
            ->map(fn ($it) => $it->invoicingReceipt)
            ->filter()
            ->values();

        if ($matchingReceipts->count() === 1) {
            return $matchingReceipts->first();
        }

        if ($matchingReceipts->count() > 1) {
            $targetNum = $jenis === 'RDTV' ? trim($noRdtv) : trim($noGrs);
            if ($targetNum !== '') {
                $exact = $matchingReceipts->first(function (InvoicingReceipt $r) use ($jenis, $targetNum): bool {
                    $val = $jenis === 'RDTV' ? (string) $r->no_rdtv : (string) $r->no_grs;

                    return str_contains($val, $targetNum);
                });
                if ($exact) {
                    return $exact;
                }
            }
        }

        $fallback = $this->findMatchingReceiptForVerification($jenis, $noGrs, $noRdtv, $noPo, $noDo);

        return $fallback['receipt'];
    }

    /**
     * Mencari InvoicingReceipt eksisting secara ketat berdasarkan kecocokan tepat No. GRS atau No. RDTV
     * untuk mencegah duplikasi DO & Invoice Barang saat nama file Slip berubah/memiliki akhiran unduhan.
     */
    protected function findExistingReceiptByExactDocNumber(string $jenis, string $docNo): ?InvoicingReceipt
    {
        $docNo = trim($docNo);
        if ($docNo === '') {
            return null;
        }

        $query = InvoicingReceipt::query();
        if ($jenis === 'RDTV') {
            $query->where('jenis', 'RDTV')->where('no_rdtv', $docNo);
        } else {
            $query->where(function (Builder $q): void {
                $q->whereNull('jenis')->orWhere('jenis', '!=', 'RDTV');
            })->where(function (Builder $q) use ($docNo): void {
                $q->where('no_grs', $docNo)
                    ->orWhereHas('items', fn (Builder $sub) => $sub->where('no_grs', $docNo));
            });
        }

        return (clone $query)
            ->where('status', 'Menunggu Verifikasi')
            ->where(function (Builder $sub): void {
                $sub->whereNull('dokumen_verifikasi_grs')
                    ->orWhere('dokumen_verifikasi_grs', '')
                    ->orWhere('dokumen_verifikasi_grs', 'like', 'grs-rdtv-docs/%');
            })
            ->orderByDesc('id')
            ->first()
            ?? (clone $query)->where('status', 'Menunggu Verifikasi')->orderByDesc('id')->first()
            ?? $query->orderByDesc('id')->first();
    }

    /**
     * Pencocokan aman Surat Verifikasi ke InvoicingReceipt eksisting (anti-overwrite antar parsial PO).
     *
     * @return array{receipt: ?InvoicingReceipt, ambiguous: bool, message: string}
     */
    public function findMatchingReceiptForVerification(
        string $jenis,
        string $noGrs = '',
        string $noRdtv = '',
        string $noPo = '',
        string $noDo = '',
    ): array {
        $jenis = strtoupper(trim($jenis));
        $noGrs = trim($noGrs);
        $noRdtv = trim($noRdtv);
        $noPo = trim($noPo);
        $noDo = trim($noDo);

        if ($jenis === 'RDTV' && $noRdtv === '' && $noGrs !== '') {
            $noRdtv = $noGrs;
            $noGrs = '';
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

        // 1a. Nomor RDTV
        if ($jenis === 'RDTV' && $noRdtv !== '') {
            $cleanRdtv = preg_replace('/\D/', '', $noRdtv);
            $rdtvQuery = $applyJenisScope(InvoicingReceipt::query())->where(function (Builder $q) use ($noRdtv, $cleanRdtv): void {
                $q->where('no_rdtv', $noRdtv)
                    ->orWhere('no_rdtv', 'like', "%{$noRdtv}%")
                    ->orWhere('no_grs', $noRdtv);

                if (! empty($cleanRdtv)) {
                    $q->orWhere('no_rdtv', 'like', "%{$cleanRdtv}%");
                }
            });

            $receipt = (clone $rdtvQuery)->where('status', 'Menunggu Verifikasi')->tap($whereUnverifiedDoc)->first()
                ?? (clone $rdtvQuery)->where('status', 'Menunggu Verifikasi')->first()
                ?? $rdtvQuery->first();
        }

        // 1b. Nomor GRS
        if (! $receipt && $jenis !== 'RDTV' && $noGrs !== '') {
            $cleanGrs = preg_replace('/\D/', '', $noGrs);
            $grsQuery = $applyJenisScope(InvoicingReceipt::query())->where(function (Builder $q) use ($noGrs, $cleanGrs): void {
                $q->where('no_grs', $noGrs)
                    ->orWhere('no_grs', 'like', "%{$noGrs}%");

                if (! empty($cleanGrs)) {
                    $q->orWhere('no_grs', 'like', "%{$cleanGrs}%");
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

        // 2. Fallback aman PO + DO lalu PO tunggal (anti-bentrok parsial)
        if (! $receipt && $noPo !== '') {
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

            if ($jenis === 'RDTV' && $noRdtv !== '') {
                $basePoQuery->where(function (Builder $q) use ($noRdtv, $cleanRdtv): void {
                    $q->whereNull('no_rdtv')
                        ->orWhere('no_rdtv', '')
                        ->orWhere('no_rdtv', $noRdtv);
                    if (! empty($cleanRdtv)) {
                        $q->orWhere('no_rdtv', 'like', "%{$cleanRdtv}%");
                    }
                });
            } elseif ($jenis !== 'RDTV' && $noGrs !== '') {
                $basePoQuery->where(function (Builder $q) use ($noGrs, $cleanGrs): void {
                    $q->whereNull('no_grs')
                        ->orWhere('no_grs', '')
                        ->orWhere('no_grs', $noGrs);
                    if (! empty($cleanGrs)) {
                        $q->orWhere('no_grs', 'like', "%{$cleanGrs}%");
                    }
                });
            }

            if ($noDo !== '') {
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

                    return [
                        'receipt' => null,
                        'ambiguous' => true,
                        'message' => "Ditemukan {$unverifiedCandidates->count()} tagihan parsial pada PO {$noPo} yang belum memiliki No. {$docLabel} cocok. Pasangkan dengan berkas Slip {$docLabel} atau isi No. {$docLabel} yang tepat.",
                    ];
                } else {
                    $waitingCandidates = (clone $basePoQuery)->where('status', 'Menunggu Verifikasi')->get();
                    if ($waitingCandidates->count() === 1) {
                        $receipt = $waitingCandidates->first();
                    } elseif ($waitingCandidates->count() > 1) {
                        $docLabel = $jenis === 'RDTV' ? 'RDTV' : 'GRS';

                        return [
                            'receipt' => null,
                            'ambiguous' => true,
                            'message' => "Ditemukan {$waitingCandidates->count()} tagihan parsial pada PO {$noPo} tanpa No. {$docLabel} cocok.",
                        ];
                    } elseif ($noGrs === '' && $noRdtv === '') {
                        $allCandidates = (clone $basePoQuery)->get();
                        if ($allCandidates->count() === 1) {
                            $receipt = $allCandidates->first();
                        }
                    }
                }
            }
        }

        if ($receipt) {
            return [
                'receipt' => $receipt,
                'ambiguous' => false,
                'message' => 'Cocok.',
            ];
        }

        $docLabel = $jenis === 'RDTV' ? 'RDTV' : 'GRS';
        $docNo = $jenis === 'RDTV' ? ($noRdtv ?: '-') : ($noGrs ?: '-');

        return [
            'receipt' => null,
            'ambiguous' => false,
            'message' => "Tidak ditemukan tagihan Invoice Barang ({$docLabel}) untuk No. {$docLabel}: {$docNo} / PO: ".($noPo ?: '-').'. Unggah juga berkas Slip-nya atau pasangkan manual.',
        ];
    }
}
