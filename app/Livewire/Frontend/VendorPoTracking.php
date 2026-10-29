<?php

namespace App\Livewire\Frontend;

use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\GrsRdtvItem;
use App\Models\InvoicingReceipt;
use App\Models\PurchaseOrderIssued;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Lacak Pergerakan Dokumen PO - Portal Vendor Pupuk Kalimantan Timur')]
class VendorPoTracking extends Component
{
    use WithFileUploads;

    public const INVOICE_CHECKLIST = [
        'po_asli' => [
            'number' => 1,
            'name' => 'PO ASLI',
            'desc' => 'Purchase Order asli bertanda tangan & cap resmi dari Pupuk Kaltim dan Vendor.',
            'badge' => 'Wajib',
            'badge_color' => 'rose',
            'required' => true,
        ],
        'invoice' => [
            'number' => 2,
            'name' => 'INVOICE (NOTA PENJUALAN)',
            'desc' => 'Kwitansi / Nota Penjualan resmi bermaterai cukup dan bernomor invoice.',
            'badge' => 'Wajib',
            'badge_color' => 'rose',
            'required' => true,
        ],
        'grs' => [
            'number' => 3,
            'name' => 'GRS (GOOD RECEIVE SLIP)',
            'desc' => 'Salinan lembar GRS resmi yang telah diunduh dari sistem Receiving.',
            'badge' => 'Wajib',
            'badge_color' => 'rose',
            'required' => true,
        ],
        'do' => [
            'number' => 4,
            'name' => 'DO (DELIVERY ORDER)',
            'desc' => 'Surat Jalan (DO) asli / legalisir cap basah penerimaan oleh petugas Receiving.',
            'badge' => 'Wajib',
            'badge_color' => 'rose',
            'required' => true,
        ],
        'faktur_pajak' => [
            'number' => 5,
            'name' => 'FAKTUR PAJAK',
            'desc' => 'Faktur Pajak elektronik (e-Faktur) dengan barcode / QR code DJP valid.',
            'badge' => 'Wajib',
            'badge_color' => 'rose',
            'required' => true,
        ],
        'enofa' => [
            'number' => 6,
            'name' => 'ENOFA (NO. SERI FAKTUR PAJAK ONLINE)',
            'desc' => 'Surat konfirmasi alokasi Nomor Seri Faktur Pajak Online dari DJP.',
            'badge' => 'Pelengkap',
            'badge_color' => 'amber',
            'required' => false,
        ],
        'sppkp' => [
            'number' => 7,
            'name' => 'SPPKP (SURAT PENGUKUHAN PENGUSAHA KENA PAJAK)',
            'desc' => 'Salinan Surat Pengukuhan Pengusaha Kena Pajak yang masih berlaku.',
            'badge' => 'Pelengkap',
            'badge_color' => 'amber',
            'required' => false,
        ],
        'bank_garansi' => [
            'number' => 8,
            'name' => 'BANK GARANSI (JAMINAN PELAKSANA)',
            'desc' => 'Surat Jaminan Pelaksanaan dari Bank (apabila dipersyaratkan dalam klausul PO).',
            'badge' => 'Kondisional',
            'badge_color' => 'indigo',
            'required' => false,
        ],
        'skf' => [
            'number' => 9,
            'name' => 'SKF (SURAT KETERANGAN FISKAL)',
            'desc' => 'Surat Keterangan Fiskal dari Kantor Pelayanan Pajak yang masih aktif.',
            'badge' => 'Pelengkap',
            'badge_color' => 'amber',
            'required' => false,
        ],
        'surat_pernyataan' => [
            'number' => 10,
            'name' => 'SURAT PERNYATAAN',
            'desc' => 'Surat pernyataan keabsahan dokumen / bebas sanksi bermaterai Rp 10.000.',
            'badge' => 'Pelengkap',
            'badge_color' => 'amber',
            'required' => false,
        ],
        'lpkb' => [
            'number' => 11,
            'name' => 'LPKB (LAPORAN PEMERIKSAAN KUALITAS BARANG)',
            'desc' => 'Khusus pengadaan Bahan Baku & Chemical dari tim penguji/QC.',
            'badge' => 'Bahan Baku & Chemical',
            'badge_color' => 'teal',
            'required' => false,
        ],
        'coa' => [
            'number' => 12,
            'name' => 'COA (CERTIFICATE OF ANALYSIS)',
            'desc' => 'Khusus Batubara, Bahan Baku Impor, Methanol, dan Karung.',
            'badge' => 'Batubara / Impor / Methanol / Karung',
            'badge_color' => 'teal',
            'required' => false,
        ],
        'bill_of_lading' => [
            'number' => 13,
            'name' => 'BILL OF LADING (B/L)',
            'desc' => 'Khusus moda transportasi laut / pengapalan Batubara.',
            'badge' => 'Khusus Batubara',
            'badge_color' => 'sky',
            'required' => false,
        ],
        'penyesuaian_harga' => [
            'number' => 14,
            'name' => 'KETERANGAN PENYESUAIAN HARGA',
            'desc' => 'Khusus pengadaan Batubara & BBM jika terdapat penyesuaian indeks kalori/harga.',
            'badge' => 'Batubara / BBM',
            'badge_color' => 'sky',
            'required' => false,
        ],
        'jadwal_pengiriman' => [
            'number' => 15,
            'name' => 'JADWAL PENGIRIMAN',
            'desc' => 'Khusus komitmen jadwal pengapalan / pengiriman berkala Batubara.',
            'badge' => 'Khusus Batubara',
            'badge_color' => 'sky',
            'required' => false,
        ],
        'ba_transfering' => [
            'number' => 16,
            'name' => 'BA TRANSFERING',
            'desc' => 'Berita Acara Transfering tangki/aliran khusus material Methanol.',
            'badge' => 'Khusus Methanol',
            'badge_color' => 'purple',
            'required' => false,
        ],
    ];

    #[Url(as: 'po')]
    public string $poNumber = '';

    public ?string $activePo = null;

    public ?string $verifiedEmail = null;

    public ?string $verifiedVendorNumber = null;

    public ?int $verifiedVendorId = null;

    public string $verificationEmail = '';

    public bool $showVerificationModal = false;

    public bool $isVerified = false;

    public ?string $verificationError = null;

    public ?string $searchError = null;

    /**
     * @var array{name?: string, vendor_number?: string, masked_emails?: array<string>, has_registered_email?: bool, error_message?: string}
     */
    public array $vendorInfo = [];

    /**
     * @var array<string, mixed>
     */
    public array $trackingData = [];

    public string $activeDoTab = '';

    /**
     * Single uploaded dossier file for complete invoice submission
     *
     * @var mixed
     */
    public $berkasPenagihanFile = null;

    /**
     * Temporary uploaded files for checklist (backward compatibility)
     *
     * @var array<string, mixed>
     */
    public array $tempUploads = [];

    public function mount(): void
    {
        if (!empty($this->poNumber)) {
            $this->initiateSearch();
        }
    }

    public function selectSamplePo(string $po): void
    {
        $this->poNumber = trim($po);
        $poItems = PurchaseOrderIssued::where('purchase_order_no', $this->poNumber)->get();

        if ($poItems->isNotEmpty() && $this->isPoVerified($poItems->first())) {
            $this->activePo = $this->poNumber;
            $this->resolvePoAndVendor($this->poNumber);
            $this->isVerified = true;
            $this->showVerificationModal = false;
            $this->verificationError = null;
            $this->searchError = null;
            $this->loadTrackingData();

            return;
        }

        $this->resolvePoAndVendor($this->poNumber);
        $this->isVerified = false;
        $this->trackingData = [];
        $this->verificationEmail = '';
        $this->verificationError = null;
        $this->searchError = null;
        $this->showVerificationModal = true;
    }

    public function openTrackingModal(?string $po = null): void
    {
        if (!empty($po)) {
            $this->selectSamplePo($po);
        } else {
            $this->showVerificationModal = true;
        }
    }

    public function updatedPoNumber(): void
    {
        $trimmedPo = trim($this->poNumber);
        $this->verificationError = null;
        $this->searchError = null;

        if (empty($trimmedPo)) {
            $this->activePo = null;
            $this->vendorInfo = [];

            return;
        }

        if (!$this->resolvePoAndVendor($trimmedPo)) {
            $this->verificationError = "Nomor PO \"{$trimmedPo}\" tidak ditemukan dalam sistem.";
            $this->isVerified = false;
            $this->trackingData = [];

            return;
        }

        $po = PurchaseOrderIssued::where('purchase_order_no', $trimmedPo)->first();
        if ($po && !$this->isPoVerified($po)) {
            $this->isVerified = false;
            $this->trackingData = [];
            $this->verificationEmail = '';
        }
    }

    public function initiateSearch(): void
    {
        $this->searchError = null;
        $this->verificationError = null;

        $trimmedPo = trim($this->poNumber);

        if (empty($trimmedPo)) {
            $this->searchError = 'Harap masukkan nomor Purchase Order (PO).';

            return;
        }

        $poItems = PurchaseOrderIssued::where('purchase_order_no', $trimmedPo)->get();

        if ($poItems->isEmpty()) {
            $this->searchError = "Nomor PO \"{$trimmedPo}\" tidak ditemukan dalam sistem. Harap periksa kembali nomor PO yang Anda masukkan.";
            $this->isVerified = false;
            $this->activePo = null;
            $this->trackingData = [];

            return;
        }

        $firstPo = $poItems->first();
        $this->activePo = $firstPo->purchase_order_no;
        $this->resolvePoAndVendor($trimmedPo);

        // Jika user sudah terverifikasi untuk vendor yang sama dengan email yang valid, langsung tampilkan data
        if ($this->isPoVerified($firstPo)) {
            $this->isVerified = true;
            $this->showVerificationModal = false;
            $this->loadTrackingData();

            return;
        }

        // Jika PO berasal dari vendor yang berbeda atau belum terverifikasi:
        $this->isVerified = false;
        $this->trackingData = [];
        $this->verificationEmail = '';
        $this->showVerificationModal = true;
    }

    /**
     * Check if a given PO is already verified for the current session/vendor
     */
    protected function isPoVerified(PurchaseOrderIssued $firstPo): bool
    {
        if (!$this->isVerified || empty($this->verifiedEmail)) {
            return false;
        }

        $vendor = $this->resolveVendorForPo($firstPo);
        $registeredEmails = $this->extractEmailsFromVendor($vendor);

        if (empty($registeredEmails) || !in_array(strtolower(trim($this->verifiedEmail)), $registeredEmails, true)) {
            return false;
        }

        $currentVendorNumber = $vendor?->vendor_number ?? $firstPo->vendor_id;

        if (!empty($this->verifiedVendorNumber) && !empty($currentVendorNumber)) {
            return $this->verifiedVendorNumber === $currentVendorNumber;
        }

        if (!empty($this->verifiedVendorId) && !empty($vendor?->id)) {
            return $this->verifiedVendorId === $vendor->id;
        }

        return true;
    }

    protected function resolvePoAndVendor(string $poNumber): bool
    {
        $trimmedPo = trim($poNumber);

        if (empty($trimmedPo)) {
            $this->activePo = null;
            $this->vendorInfo = [];

            return false;
        }

        $poItems = PurchaseOrderIssued::where('purchase_order_no', $trimmedPo)->get();

        if ($poItems->isEmpty()) {
            $this->activePo = null;
            $this->vendorInfo = [];

            return false;
        }

        $firstPo = $poItems->first();
        $this->activePo = $firstPo->purchase_order_no;

        $vendor = $this->resolveVendorForPo($firstPo);
        $registeredEmails = $this->extractEmailsFromVendor($vendor);

        if (empty($registeredEmails)) {
            $this->vendorInfo = [
                'name' => $vendor?->name ?? $firstPo->vendor_name,
                'vendor_number' => $vendor?->vendor_number ?? $firstPo->vendor_id,
                'masked_emails' => [],
                'has_registered_email' => false,
                'error_message' => 'Vendor untuk PO ini belum memiliki kontak email resmi yang terdaftar pada sistem Master Data Pupuk Kaltim. Silakan hubungi Tim Receiving / Pengadaan PKT untuk validasi berkas.',
            ];
        } else {
            $masked = array_map(fn(string $email): string => $this->maskEmail($email), $registeredEmails);

            $this->vendorInfo = [
                'name' => $vendor?->name ?? $firstPo->vendor_name,
                'vendor_number' => $vendor?->vendor_number ?? $firstPo->vendor_id,
                'masked_emails' => $masked,
                'has_registered_email' => true,
                'error_message' => null,
            ];
        }

        return true;
    }

    public function verifyVendorEmail(): void
    {
        $this->verificationError = null;

        $targetPo = trim($this->poNumber ?: ($this->activePo ?? ''));

        if (empty($targetPo)) {
            $this->verificationError = 'Harap masukkan nomor Purchase Order (PO).';

            return;
        }

        if ($this->activePo !== $targetPo) {
            if (!$this->resolvePoAndVendor($targetPo)) {
                $this->verificationError = "Nomor PO \"{$targetPo}\" tidak ditemukan dalam sistem.";

                return;
            }
        }

        $inputEmail = strtolower(trim($this->verificationEmail));

        if (empty($inputEmail) || !filter_var($inputEmail, FILTER_VALIDATE_EMAIL)) {
            $this->verificationError = 'Harap masukkan format alamat email yang valid.';

            return;
        }

        $po = PurchaseOrderIssued::where('purchase_order_no', $this->activePo)->first();

        if (!$po) {
            $this->verificationError = 'Data PO tidak ditemukan.';

            return;
        }

        $vendor = $this->resolveVendorForPo($po);
        $registeredEmails = $this->extractEmailsFromVendor($vendor);

        if (empty($registeredEmails)) {
            $this->verificationError = 'Tidak ada email terdaftar untuk vendor ini. Silakan hubungi administrator Receiving PKT.';

            return;
        }

        // Cek kecocokan email
        if (!in_array($inputEmail, $registeredEmails, true)) {
            $this->verificationError = 'Email yang Anda masukkan tidak sesuai dengan email vendor terdaftar untuk PO ini. Pastikan Anda menggunakan email resmi yang didaftarkan ke Pupuk Kaltim.';

            return;
        }

        // Verifikasi berhasil!
        $this->isVerified = true;
        $this->verifiedEmail = $inputEmail;
        $this->verifiedVendorNumber = $vendor?->vendor_number ?? $po->vendor_id;
        $this->verifiedVendorId = $vendor?->id;
        $this->showVerificationModal = false;
        $this->verificationError = null;
        $this->loadTrackingData();
    }

    public function closeModal(): void
    {
        $this->showVerificationModal = false;
        $this->verificationError = null;
        $this->verificationEmail = '';

        if (!empty($this->activePo)) {
            $po = PurchaseOrderIssued::where('purchase_order_no', $this->activePo)->first();
            if (!$po || !$this->isPoVerified($po)) {
                $this->isVerified = false;
                $this->trackingData = [];
            }
        }
    }

    public function resetSearch(): void
    {
        $this->poNumber = '';
        $this->activePo = null;
        $this->verifiedEmail = null;
        $this->verifiedVendorNumber = null;
        $this->verifiedVendorId = null;
        $this->isVerified = false;
        $this->trackingData = [];
        $this->searchError = null;
        $this->verificationError = null;
        $this->showVerificationModal = false;
    }

    public function selectDoTab(string $doId): void
    {
        $this->activeDoTab = $doId;
    }

    public function loadTrackingData(): void
    {
        if (empty($this->activePo)) {
            return;
        }

        $poItems = PurchaseOrderIssued::where('purchase_order_no', $this->activePo)->get();

        if ($poItems->isEmpty()) {
            return;
        }

        $firstPo = $poItems->first();
        $vendor = $this->resolveVendorForPo($firstPo);

        // Ambil semua detail DO terkait item-item PO ini
        $poItemIds = $poItems->pluck('id')->toArray();

        $details = DeliveryOrderReceiptDetail::with([
            'deliveryOrderReceipt.qcHistories.createdBy',
            'deliveryOrderReceipt.grsRdtvItems.grsRdtv.createdBy',
            'deliveryOrderReceipt.invoicingReceipts',
            'deliveryOrderReceipt.delayLogs.createdBy',
            'deliveryOrderReceipt.termins',
            'deliveryOrderReceipt.receivedBy',
            'locationReceiving',
            'warehouseDestination',
            'materialIssueDetails.materialIssue',
        ])->whereIn('purchase_order_issued_id', $poItemIds)->get();

        // Kumpulkan DO receipts unik (urutkan berdasarkan tanggal kedatangan fisik / ID)
        /** @var Collection<int, DeliveryOrderReceipt> $uniqueDos */
        $uniqueDos = $details->pluck('deliveryOrderReceipt')
            ->unique('id')
            ->filter()
            ->sortBy(fn(DeliveryOrderReceipt $do): string => sprintf(
                '%s-%010d',
                Carbon::parse($do->physical_received_date ?? $do->received_date ?? $do->created_at)->format('Y-m-d H:i:s'),
                $do->id
            ))
            ->values();

        if ($uniqueDos->isNotEmpty() && empty($this->activeDoTab)) {
            $this->activeDoTab = (string) $uniqueDos->first()->id;
        }

        // Buat Timeline Komprehensif
        $timeline = [];

        // 1. PO Terbit
        if ($firstPo->date_create) {
            $timeline[] = [
                'do_order' => 0,
                'stage_order' => 10,
                'date' => $firstPo->date_create->format('d M Y'),
                'time' => '08:00',
                'timestamp' => $firstPo->date_create->timestamp,
                'title' => 'Purchase Order (PO) Diterbitkan',
                'badge' => 'PO Issued',
                'badge_color' => 'blue',
                'desc' => "Nomor PO: {$firstPo->purchase_order_no} resmi diterbitkan oleh PT Pupuk Kalimantan Timur kepada {$firstPo->vendor_name} ({$poItems->count()} item material).",
                'icon' => 'document-check',
            ];
        }

        // 2. Timeline dari setiap Delivery Order (DO)
        foreach ($uniqueDos as $doIndex => $do) {
            $doOrder = $doIndex + 1;

            $arrDate = $do->physical_received_date ?? $do->received_date;
            if ($arrDate) {
                $cDate = Carbon::parse($arrDate);
                $penerima = $do->receivedBy->name ?? 'Staff Receiving';
                $mode = $do->receipt_mode ?? 'Standard';
                $timeline[] = [
                    'do_order' => $doOrder,
                    'stage_order' => 20,
                    'date' => $cDate->format('d M Y'),
                    'time' => $cDate->format('H:i') !== '00:00' ? $cDate->format('H:i') : '-',
                    'timestamp' => $cDate->timestamp,
                    'title' => 'Kedatangan Fisik Barang',
                    'badge' => 'Receiving Entry',
                    'badge_color' => 'emerald',
                    'desc' => "Surat Jalan (DO) No: {$do->delivery_order_no} diterima di Pintu Receiving oleh {$penerima} (Mode: {$mode}, Kode: {$do->document_code}).",
                    'icon' => 'truck',
                ];
            }

            // Post 103 (Pengajuan QC)
            if ($do->post_103) {
                $pDate = Carbon::parse($do->post_103);
                $timeline[] = [
                    'do_order' => $doOrder,
                    'stage_order' => 30,
                    'date' => $pDate->format('d M Y'),
                    'time' => $pDate->format('H:i') !== '00:00' ? $pDate->format('H:i') : '-',
                    'timestamp' => $pDate->timestamp,
                    'title' => 'Post 103 (Pengajuan QC)',
                    'badge' => 'Post 103 (Pengajuan QC)',
                    'badge_color' => 'indigo',
                    'desc' => 'Pencatatan MIGO 103 (Pengajuan QC) di sistem SAP berhasil.' . ($do->qr_103_code ? " Terbit Kode QR 103: {$do->qr_103_code}." : ''),
                    'icon' => 'qr-code',
                ];
            }

            // Termin Post 103 (jika mode termin)
            if ($do->receipt_mode === 'Termin' && $do->termins->isNotEmpty()) {
                foreach ($do->termins as $termin) {
                    if ($termin->post_103) {
                        $tDate = Carbon::parse($termin->post_103);
                        $timeline[] = [
                            'do_order' => $doOrder,
                            'stage_order' => 31,
                            'date' => $tDate->format('d M Y'),
                            'time' => $tDate->format('H:i') !== '00:00' ? $tDate->format('H:i') : '-',
                            'timestamp' => $tDate->timestamp,
                            'title' => "Post 103 (Pengajuan QC) - {$termin->stage} ({$termin->percentage}%)",
                            'badge' => 'Termin Post 103',
                            'badge_color' => 'indigo',
                            'desc' => "Tahapan {$termin->stage} dengan porsi {$termin->percentage}% berhasil di-post 103." . ($termin->qr_103_code ? " Kode QR: {$termin->qr_103_code}" : ''),
                            'icon' => 'chart-pie',
                        ];
                    }
                }
            }

            // QC Histories (Transmittal Kirim & Kembali)
            foreach ($do->qcHistories as $qc) {
                $qcDate = Carbon::parse($qc->created_at);
                $isKirim = $qc->status === 'Kirim';
                $petugas = $qc->createdBy->name ?? 'Staff Receiving';

                $timeline[] = [
                    'do_order' => $doOrder,
                    'stage_order' => $isKirim ? 40 : 50,
                    'date' => $qcDate->format('d M Y'),
                    'time' => $qcDate->format('H:i'),
                    'timestamp' => $qcDate->timestamp,
                    'title' => $isKirim
                        ? 'Dokumen Dikirim dari Receiving ke ISTEK (Transmittal QC Kirim)'
                        : 'Dokumen Kembali dari ISTEK ke Receiving (Transmittal QC Kembali)',
                    'badge' => $isKirim ? 'Transmittal QC Kirim' : 'Transmittal QC Kembali',
                    'badge_color' => $isKirim ? 'sky' : 'purple',
                    'desc' => ($isKirim
                        ? "Berkas Surat Jalan diserahkan dari Receiving ke tim ISTEK (Inspeksi Teknik) untuk proses uji mutu & kesesuaian fisik oleh {$petugas}."
                        : "Pemeriksaan mutu oleh ISTEK selesai dan dokumen telah dikembalikan dari ISTEK ke Gudang Receiving oleh {$petugas}."),
                    'icon' => 'beaker',
                ];
            }

            // GRS / RDTV
            foreach ($do->grsRdtvItems as $item) {
                if ($item->grsRdtv) {
                    $grsDate = Carbon::parse($item->grsRdtv->transaction_date ?? $item->created_at);
                    $cat = $item->grsRdtv->category;
                    $petugas = $item->grsRdtv->createdBy->name ?? 'Staff Gudang';

                    $timeline[] = [
                        'do_order' => $doOrder,
                        'stage_order' => 60,
                        'date' => $grsDate->format('d M Y'),
                        'time' => $grsDate->format('H:i') !== '00:00' ? $grsDate->format('H:i') : '-',
                        'timestamp' => $grsDate->timestamp,
                        'title' => "Dokumen {$cat} Terbit",
                        'badge' => "Dokumen {$cat} Terbit",
                        'badge_color' => $cat === 'GRS' ? 'emerald' : 'rose',
                        'desc' => "Dokumen {$cat} (SAP 105) resmi terbit dan ditautkan ke sistem penagihan oleh {$petugas}." . ($item->reason ? " Catatan: {$item->reason}" : ''),
                        'icon' => 'clipboard-check',
                    ];
                }
            }

            // Delay / Pending Logs
            foreach ($do->delayLogs as $log) {
                $logDate = Carbon::parse($log->created_at);
                $isClear = $log->delay_reason === 'Penundaan Selesai (Clear)';
                $timeline[] = [
                    'do_order' => $doOrder,
                    'stage_order' => 35,
                    'date' => $logDate->format('d M Y'),
                    'time' => $logDate->format('H:i'),
                    'timestamp' => $logDate->timestamp,
                    'title' => $isClear ? 'Status Pending Diselesaikan' : 'Catatan Penundaan (Pending Status)',
                    'badge' => $isClear ? 'Pending Resolved' : 'Pending',
                    'badge_color' => $isClear ? 'emerald' : 'amber',
                    'desc' => $isClear
                        ? 'Status penundaan telah diselesaikan, alur penerimaan dilanjutkan kembali.'
                        : "Penundaan: {$log->delay_reason}" . ($log->delay_notes ? " - {$log->delay_notes}" : ''),
                    'icon' => 'clock',
                ];
            }

            // Material Issues (MIR)
            foreach ($do->deliveryOrderReceiptDetails as $dDetail) {
                foreach ($dDetail->materialIssueDetails as $miDetail) {
                    if ($miDetail->materialIssue) {
                        $miDate = Carbon::parse($miDetail->materialIssue->tanggal ?? $miDetail->created_at);
                        $timeline[] = [
                            'do_order' => $doOrder,
                            'stage_order' => 70,
                            'date' => $miDate->format('d M Y'),
                            'time' => $miDate->format('H:i') !== '00:00' ? $miDate->format('H:i') : '-',
                            'timestamp' => $miDate->timestamp,
                            'title' => "Pengambilan Barang (MIR: {$miDetail->materialIssue->mir_number})",
                            'badge' => 'Material Issued',
                            'badge_color' => 'teal',
                            'desc' => "Material '{$dDetail->description}' sebanyak {$miDetail->diserahkan} {$dDetail->uoi} diserahkan kepada {$miDetail->materialIssue->diminta_oleh} (Stage: {$miDetail->stage_when_issued}).",
                            'icon' => 'arrow-right-on-rectangle',
                        ];
                    }
                }
            }

            // Invoicing Receipts
            foreach ($do->invoicingReceipts as $inv) {
                if ($inv->tanggal_terima_grs) {
                    $invDate = Carbon::parse($inv->tanggal_terima_grs);
                    $timeline[] = [
                        'do_order' => $doOrder,
                        'stage_order' => 80,
                        'date' => $invDate->format('d M Y'),
                        'time' => '-',
                        'timestamp' => $invDate->timestamp,
                        'title' => 'Penerimaan Berkas Penagihan (Invoicing)',
                        'badge' => 'Invoicing Entry',
                        'badge_color' => 'cyan',
                        'desc' => "Berkas penagihan untuk DO {$inv->no_do} diterima oleh Bagian Keuangan/Invoice." . ($inv->no_invoice ? " No Invoice: {$inv->no_invoice}" : ''),
                        'icon' => 'currency-dollar',
                    ];
                }

                if ($inv->tanggal_kirim_kasir) {
                    $kasirDate = Carbon::parse($inv->tanggal_kirim_kasir);
                    $timeline[] = [
                        'do_order' => $doOrder,
                        'stage_order' => 90,
                        'date' => $kasirDate->format('d M Y'),
                        'time' => '-',
                        'timestamp' => $kasirDate->timestamp,
                        'title' => 'Berkas Dikirim ke Kasir untuk Pembayaran',
                        'badge' => 'Kasir / Payment',
                        'badge_color' => 'emerald',
                        'desc' => 'Dokumen penagihan telah diverifikasi dan diserahkan ke bagian Kasir untuk proses pembayaran.',
                        'icon' => 'banknotes',
                    ];
                }
            }
        }

        // Urutkan timeline berdasarkan urutan DO, tahapan SOP alur proses, lalu timestamp
        usort($timeline, function (array $a, array $b): int {
            return ($a['do_order'] <=> $b['do_order'])
                ?: ($a['stage_order'] <=> $b['stage_order'])
                ?: ($a['timestamp'] <=> $b['timestamp']);
        });

        // Tentukan Current Pipeline Stage
        $currentStageIndex = 1;
        $currentStageLabel = 'PO Diterbitkan';

        if ($uniqueDos->isNotEmpty()) {
            $currentStageIndex = 2;
            $currentStageLabel = 'Fisik Tiba di Receiving';

            $hasPost103 = $uniqueDos->contains(fn(DeliveryOrderReceipt $do): bool => $do->post_103 !== null);
            if ($hasPost103) {
                $currentStageIndex = 3;
                $currentStageLabel = 'Posting MIGO 103 Selesai';
            }

            $hasQcKirim = $uniqueDos->contains(fn(DeliveryOrderReceipt $do): bool => $do->qcHistories->where('status', 'Kirim')->isNotEmpty());
            if ($hasQcKirim) {
                $currentStageIndex = 4;
                $currentStageLabel = 'Inspeksi ISTEK (QC)';
            }

            $hasQcKembali = $uniqueDos->contains(fn(DeliveryOrderReceipt $do): bool => $do->qcHistories->where('status', 'Kembali')->isNotEmpty());
            if ($hasQcKembali) {
                $currentStageIndex = 4;
                $currentStageLabel = 'Inspeksi ISTEK Selesai';
            }

            $hasGrs = $uniqueDos->contains(fn(DeliveryOrderReceipt $do): bool => $do->grsRdtvItems->where('grsRdtv.category', 'GRS')->isNotEmpty() || $do->status === 'GRS');
            if ($hasGrs) {
                $currentStageIndex = 5;
                $currentStageLabel = 'GRS Terbit (105)';
            }
        }

        // Format Material Items & Fulfillment
        $materialItems = [];
        foreach ($poItems as $item) {
            $receivedQty = (float) $details->where('purchase_order_issued_id', $item->id)->sum('quantity');
            $poQty = (float) $item->qty_po;

            $statusText = 'Belum Diterima';
            $statusColor = 'gray';

            if ($receivedQty >= $poQty && $poQty > 0) {
                $statusText = 'Diterima Lengkap';
                $statusColor = 'emerald';
            } elseif ($receivedQty > 0) {
                $statusText = 'Diterima Parsial';
                $statusColor = 'amber';
            }

            // Lokasi penyimpanan aktual
            $itemLocations = $details->where('purchase_order_issued_id', $item->id)
                ->map(fn(DeliveryOrderReceiptDetail $d): string => $d->locationReceiving->name ?? '-')
                ->unique()
                ->values()
                ->toArray();

            $materialItems[] = [
                'item_no' => $item->item_no,
                'material_code' => $item->material_code ?? '-',
                'description' => $item->description,
                'qty_po' => $poQty,
                'qty_received' => $receivedQty,
                'uoi' => $item->uoi,
                'mrp_type' => $item->mrp_type,
                'material_type' => $item->material_type ?? '-',
                'status_text' => $statusText,
                'status_color' => $statusColor,
                'locations' => !empty($itemLocations) ? implode(', ', $itemLocations) : 'Belum diatur',
            ];
        }

        // Invoicing & GRS Documents
        $invoicingReceipt = $this->resolveOrCreateInvoicingReceipt();
        $grsItems = $uniqueDos->flatMap(fn(DeliveryOrderReceipt $do) => $do->grsRdtvItems)->unique('id')->values();

        // Single dossier file info
        $dossierInfo = null;
        if ($invoicingReceipt && !empty($invoicingReceipt->dokumen_invoice)) {
            $kelengkapan = $invoicingReceipt->dokumen_kelengkapan;
            $dossierInfo = (is_array($kelengkapan) && isset($kelengkapan['file_path']))
                ? $kelengkapan
                : [
                    'file_path' => $invoicingReceipt->dokumen_invoice,
                    'original_name' => basename($invoicingReceipt->dokumen_invoice),
                    'file_size' => null,
                    'uploaded_at' => $invoicingReceipt->updated_at?->format('Y-m-d H:i:s'),
                ];
        }

        $this->trackingData = [
            'po' => [
                'number' => $firstPo->purchase_order_no,
                'vendor_id' => $firstPo->vendor_id,
                'vendor_name' => $vendor?->name ?? $firstPo->vendor_name,
                'date_create' => $firstPo->date_create?->format('d M Y') ?? '-',
                'delivery_date_po' => $firstPo->delivery_date_po?->format('d M Y') ?? '-',
                'po_status' => $firstPo->po_status ?? 'Active',
                'requisitioner' => $firstPo->requisitioner,
                'currency' => $firstPo->currency ?? 'IDR',
                'total_amount' => (float) $firstPo->total_amount_in_lc,
                'total_items' => $poItems->count(),
            ],
            'pipeline' => [
                'current_stage_index' => $currentStageIndex,
                'current_stage_label' => $currentStageLabel,
            ],
            'unique_dos' => $uniqueDos,
            'details' => $details,
            'material_items' => $materialItems,
            'timeline' => $timeline,
            'invoicing_receipt' => $invoicingReceipt,
            'grs_items' => $grsItems,
            'checklist_definitions' => self::INVOICE_CHECKLIST,
            'dossier_file' => $dossierInfo,
            'has_dossier' => !empty($invoicingReceipt?->dokumen_invoice),
            'uploaded_checklist' => $invoicingReceipt?->dokumen_kelengkapan ?? [],
            'uploaded_count' => !empty($invoicingReceipt?->dokumen_invoice) ? 1 : 0,
        ];
    }

    public function uploadBerkasPenagihan(): void
    {
        $this->validate([
            'berkasPenagihanFile' => ['required', 'file', 'mimes:pdf,zip,rar,jpg,jpeg,png', 'max:25600'],
        ], [
            'berkasPenagihanFile.required' => 'Harap pilih berkas penagihan invoice terlebih dahulu.',
            'berkasPenagihanFile.file' => 'Berkas tidak valid.',
            'berkasPenagihanFile.mimes' => 'Format berkas wajib berupa PDF, ZIP, RAR, JPG, JPEG, atau PNG.',
            'berkasPenagihanFile.max' => 'Ukuran berkas maksimal 25 MB.',
        ]);

        $invoicingReceipt = $this->resolveOrCreateInvoicingReceipt();
        if (!$invoicingReceipt) {
            session()->flash('upload_error', 'Gagal menemukan atau membuat berkas penagihan untuk PO ini.');

            return;
        }

        $file = $this->berkasPenagihanFile;
        $ext = $file->getClientOriginalExtension();
        $origName = $file->getClientOriginalName();
        $size = $file->getSize();

        $safeFilename = "berkas_penagihan_{$this->activePo}_" . time() . '_' . Str::random(6) . ".{$ext}";
        $storedPath = $file->storeAs("berkas-penagihan/{$this->activePo}", $safeFilename, 'public');

        $invoicingReceipt->dokumen_invoice = $storedPath;
        $invoicingReceipt->dokumen_kelengkapan = [
            'file_path' => $storedPath,
            'original_name' => $origName,
            'file_size' => $size,
            'uploaded_at' => now()->format('Y-m-d H:i:s'),
        ];
        $invoicingReceipt->save();

        $this->berkasPenagihanFile = null;
        $this->loadTrackingData();

        session()->flash('upload_success', "Berkas penagihan invoice \"{$origName}\" berhasil diunggah.");
    }

    public function downloadBerkasPenagihan(): mixed
    {
        $invoicingReceipt = $this->resolveOrCreateInvoicingReceipt();
        $path = $invoicingReceipt?->dokumen_invoice;

        if (empty($path)) {
            session()->flash('download_error', 'Berkas penagihan invoice belum diunggah.');

            return null;
        }

        if (!Storage::disk('public')->exists($path)) {
            session()->flash('download_error', 'File tidak ditemukan di penyimpanan server.');

            return null;
        }

        $kelengkapan = $invoicingReceipt->dokumen_kelengkapan;
        $filename = (is_array($kelengkapan) && isset($kelengkapan['original_name']))
            ? $kelengkapan['original_name']
            : basename($path);

        return Storage::disk('public')->download($path, $filename);
    }

    public function deleteBerkasPenagihan(): void
    {
        $invoicingReceipt = $this->resolveOrCreateInvoicingReceipt();
        if (!$invoicingReceipt) {
            return;
        }

        $path = $invoicingReceipt->dokumen_invoice;
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $invoicingReceipt->dokumen_invoice = null;
        $invoicingReceipt->dokumen_kelengkapan = null;
        $invoicingReceipt->save();

        $this->loadTrackingData();

        session()->flash('upload_success', 'Berkas dokumen penagihan berhasil dihapus.');
    }

    // Backward-compatibility methods for checklist calls
    public function uploadChecklistDoc(string $key): void
    {
        if (isset($this->tempUploads[$key])) {
            $this->berkasPenagihanFile = $this->tempUploads[$key];
            $this->uploadBerkasPenagihan();
            unset($this->tempUploads[$key]);
        }
    }

    public function deleteChecklistDoc(?string $key = null): void
    {
        $this->deleteBerkasPenagihan();
    }

    public function downloadChecklistDoc(?string $key = null): mixed
    {
        return $this->downloadBerkasPenagihan();
    }

    public function downloadVerifikasiGrs(?int $invoicingReceiptId = null): mixed
    {
        $receipt = $invoicingReceiptId
            ? InvoicingReceipt::find($invoicingReceiptId)
            : $this->resolveOrCreateInvoicingReceipt();

        if (!$receipt || empty($receipt->dokumen_verifikasi_grs)) {
            session()->flash('download_error', 'Surat Verifikasi GRS belum diunggah oleh Tim Invoice.');

            return null;
        }

        if (!Storage::disk('public')->exists($receipt->dokumen_verifikasi_grs)) {
            session()->flash('download_error', 'File Surat Verifikasi GRS tidak ditemukan di penyimpanan server.');

            return null;
        }

        $safeName = "Surat_Verifikasi_GRS_PO_{$this->activePo}.pdf";

        return Storage::disk('public')->download($receipt->dokumen_verifikasi_grs, $safeName);
    }

    public function downloadGrsReceiving(int $grsItemId): mixed
    {
        $item = GrsRdtvItem::find($grsItemId);

        if (!$item || empty($item->file_path)) {
            session()->flash('download_error', 'Berkas GRS belum diunggah oleh Tim Receiving.');

            return null;
        }

        if (!Storage::disk('public')->exists($item->file_path)) {
            session()->flash('download_error', 'File Berkas GRS tidak ditemukan di penyimpanan server.');

            return null;
        }

        $filename = basename($item->file_path);

        return Storage::disk('public')->download($item->file_path, $filename);
    }

    protected function resolveOrCreateInvoicingReceipt(): ?InvoicingReceipt
    {
        if (empty($this->activePo)) {
            return null;
        }

        $poItems = PurchaseOrderIssued::where('purchase_order_no', $this->activePo)->get();
        if ($poItems->isEmpty()) {
            return null;
        }

        $poItemIds = $poItems->pluck('id')->toArray();

        // 1. Cek apakah ada InvoicingReceipt yang match dengan active DO
        if (!empty($this->activeDoTab)) {
            $matchedDoReceipt = InvoicingReceipt::where('delivery_order_receipt_id', (int) $this->activeDoTab)
                ->latest()
                ->first();
            if ($matchedDoReceipt) {
                return $matchedDoReceipt;
            }
        }

        // 2. Cek apakah ada InvoicingReceipt berdasarkan no_po atau purchase_order_issued_id
        $existing = InvoicingReceipt::where('no_po', $this->activePo)
            ->orWhereIn('purchase_order_issued_id', $poItemIds)
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        // 3. Jika belum ada, buat record baru
        $firstPo = $poItems->first();
        $vendor = $this->resolveVendorForPo($firstPo);

        $detail = DeliveryOrderReceiptDetail::with(['deliveryOrderReceipt.grsRdtvItems'])
            ->whereIn('purchase_order_issued_id', $poItemIds)
            ->first();
        $do = $detail?->deliveryOrderReceipt;
        $grsItem = $do?->grsRdtvItems?->first();

        return InvoicingReceipt::create([
            'no_po' => $this->activePo,
            'purchase_order_issued_id' => $firstPo->id,
            'delivery_order_receipt_id' => $do?->id,
            'grs_rdtv_item_id' => $grsItem?->id,
            'vendor_id' => $vendor?->id,
            'vendor_name' => $vendor?->name ?? $firstPo->vendor_name,
            'email_vendor' => is_array($vendor?->email) ? implode(', ', $vendor->email) : $vendor?->email,
            'no_do' => $do?->delivery_order_no ?? '-',
            'jenis' => 'GRS',
            'status' => 'Menunggu Verifikasi',
            'keterangan' => 'Didaftarkan melalui Portal Tracking Vendor (Self-service penagihan)',
            'tanggal_terima_grs' => now(),
            'dokumen_kelengkapan' => [],
        ]);
    }

    protected function resolveVendorForPo(PurchaseOrderIssued $po): ?Vendor
    {
        if (!empty($po->vendor_id)) {
            $vendor = Vendor::where('vendor_number', $po->vendor_id)->first();
            if ($vendor) {
                return $vendor;
            }
        }

        if (!empty($po->vendor_name)) {
            $vendor = Vendor::where('name', $po->vendor_name)->first();
            if ($vendor) {
                return $vendor;
            }

            $cleaned = trim((string) preg_replace('/(Pusat|Cabang|\bPT\b|\bCV\b)/i', '', $po->vendor_name));
            if (!empty($cleaned)) {
                $vendor = Vendor::where('name', 'like', "%{$cleaned}%")->first();
                if ($vendor) {
                    return $vendor;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string>
     */
    protected function extractEmailsFromVendor(?Vendor $vendor): array
    {
        if (!$vendor) {
            return [];
        }

        $emails = [];
        $rawList = is_array($vendor->email) ? $vendor->email : [$vendor->email];

        foreach ($rawList as $item) {
            if (is_string($item)) {
                preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $item, $matches);
                if (!empty($matches[0])) {
                    foreach ($matches[0] as $match) {
                        $emails[] = strtolower(trim($match));
                    }
                }
            }
        }

        return array_values(array_unique($emails));
    }

    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return '***';
        }

        $name = $parts[0];
        $domain = $parts[1];
        $len = strlen($name);

        if ($len <= 2) {
            $maskedName = substr($name, 0, 1) . '*';
        } elseif ($len <= 5) {
            $maskedName = substr($name, 0, 1) . '***' . substr($name, -1);
        } else {
            $maskedName = substr($name, 0, 2) . str_repeat('*', min(6, $len - 3)) . substr($name, -1);
        }

        return $maskedName . '@' . $domain;
    }

    public function render()
    {
        return view('livewire.frontend.vendor-po-tracking')
            ->layout('components.layouts.frontend');
    }
}
