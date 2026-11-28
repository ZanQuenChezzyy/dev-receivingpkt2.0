<?php

namespace App\Services;

use App\Filament\Pages\NonStockAgingMonitoring;
use App\Filament\Resources\CashierTransmittals\CashierTransmittalResource;
use App\Filament\Resources\DeliveryOrderReceipts\DeliveryOrderReceiptResource;
use App\Filament\Resources\GrsRdtvs\GrsRdtvResource;
use App\Filament\Resources\InvoicingReceipts\InvoicingReceiptResource;
use App\Filament\Resources\MaterialIssues\MaterialIssueResource;
use App\Filament\Resources\MonitoringChemicals\MonitoringChemicalResource;
use App\Filament\Resources\MonitoringKarungs\MonitoringKarungResource;
use App\Filament\Resources\MonitoringNpks\MonitoringNpkResource;
use App\Filament\Resources\Transmittals\TransmittalResource;
use App\Filament\Resources\WarehouseDeliveryBatches\WarehouseDeliveryBatchResource;
use App\Filament\Resources\WarehouseTransmittals\WarehouseTransmittalResource;
use App\Models\CashierTransmittal;
use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDelayLog;
use App\Models\GrsRdtv;
use App\Models\InvoicingEmailLog;
use App\Models\InvoicingReceipt;
use App\Models\MaterialIssue;
use App\Models\MonitoringChemical;
use App\Models\MonitoringKarung;
use App\Models\MonitoringNpk;
use App\Models\NonStockAgingLog;
use App\Models\QcHistory;
use App\Models\RdtvCompliance;
use App\Models\Transmittal;
use App\Models\User;
use App\Models\WarehouseDeliveryBatch;
use App\Models\WarehouseTransmittal;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserActivityLogService
{
    public const CATEGORY_ALL = 'all';

    public const CATEGORY_RECEIVING = 'receiving';

    public const CATEGORY_GRS_RDTV = 'grs_rdtv';

    public const CATEGORY_INVOICING = 'invoicing';

    public const CATEGORY_WAREHOUSE = 'warehouse';

    public const CATEGORY_MATERIAL_ISSUE = 'material_issue';

    public const CATEGORY_MONITORING = 'monitoring';

    /**
     * Cache keberadaan tabel per instance untuk keamanan pada pengujian skema parsial.
     *
     * @var array<string, bool>
     */
    private array $tableExistsCache = [];

    /**
     * Daftar tab filter kategori aktivitas pengguna.
     *
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            self::CATEGORY_ALL => 'Semua',
            self::CATEGORY_RECEIVING => 'Penerimaan DO',
            self::CATEGORY_GRS_RDTV => 'GRS & RDTV',
            self::CATEGORY_INVOICING => 'Invoice & Kasir',
            self::CATEGORY_WAREHOUSE => 'Pengiriman Gudang',
            self::CATEGORY_MATERIAL_ISSUE => 'Pengeluaran Material',
            self::CATEGORY_MONITORING => 'Monitoring & QC',
        ];
    }

    /**
     * Menghitung ringkasan jumlah aktivitas pengguna pada empat kelompok besar sistem.
     *
     * @return array{
     *     total: int,
     *     receiving_grs: int,
     *     warehouse_mir: int,
     *     invoice_monitoring: int
     * }
     */
    public function getSummaryCounts(User $user): array
    {
        $userId = (int) $user->getKey();

        $doCount = $this->safeCount('delivery_order_receipts', fn (): int => DeliveryOrderReceipt::query()
            ->where(fn ($q) => $q->where('created_by', $userId)->orWhere('received_by', $userId))
            ->count());

        $grsCount = $this->safeCount('grs_rdtvs', fn (): int => GrsRdtv::query()->where('created_by', $userId)->count());
        $qcCount = $this->safeCount('qc_histories', fn (): int => QcHistory::query()->where('created_by', $userId)->count());
        $delayCount = $this->safeCount('delivery_order_receipt_delay_logs', fn (): int => DeliveryOrderReceiptDelayLog::query()->where('created_by', $userId)->count());
        $rdtvCompCount = $this->safeCount('rdtv_compliances', fn (): int => RdtvCompliance::query()->where('reviewed_by', $userId)->count());

        $whTransCount = $this->safeCount('warehouse_transmittals', fn (): int => WarehouseTransmittal::query()->where('created_by', $userId)->count());
        $mb51Count = $this->safeCount('warehouse_delivery_batches', fn (): int => WarehouseDeliveryBatch::query()->where('uploaded_by', $userId)->count());
        $transCount = $this->safeCount('transmittals', fn (): int => Transmittal::query()->where('created_by', $userId)->count());
        $mirCount = $this->safeCount('material_issues', fn (): int => MaterialIssue::query()->where('created_by', $userId)->count());

        $invPicCount = $this->safeCount('invoicing_receipts', fn (): int => InvoicingReceipt::query()->where('pic_id', $userId)->count());
        $emailLogCount = $this->safeCount('invoicing_email_logs', fn (): int => InvoicingEmailLog::query()->where('sent_by', $userId)->count());
        $cashierCount = $this->safeCount('cashier_transmittals', fn (): int => CashierTransmittal::query()->where('created_by', $userId)->count());
        $karungCount = $this->safeCount('monitoring_karungs', fn (): int => MonitoringKarung::query()
            ->where(fn ($q) => $q->where('created_by', $userId)->orWhere('updated_by', $userId))
            ->count());
        $chemCount = $this->safeCount('monitoring_chemicals', fn (): int => MonitoringChemical::query()
            ->where(fn ($q) => $q->where('created_by', $userId)->orWhere('received_by', $userId))
            ->count());
        $npkCount = $this->safeCount('monitoring_npks', fn (): int => MonitoringNpk::query()->where('created_by', $userId)->count());
        $agingLogCount = $this->safeCount('nonstock_aging_logs', fn (): int => NonStockAgingLog::query()->where('created_by', $userId)->count());

        $receivingGrs = $doCount + $grsCount + $qcCount + $delayCount + $rdtvCompCount;
        $warehouseMir = $whTransCount + $mb51Count + $transCount + $mirCount;
        $invoiceMonitoring = $invPicCount + $emailLogCount + $cashierCount + $karungCount + $chemCount + $npkCount + $agingLogCount;

        return [
            'total' => $receivingGrs + $warehouseMir + $invoiceMonitoring,
            'receiving_grs' => $receivingGrs,
            'warehouse_mir' => $warehouseMir,
            'invoice_monitoring' => $invoiceMonitoring,
        ];
    }

    /**
     * Mengambil lini masa aktivitas terbaru milik pengguna lintas seluruh modul.
     *
     * @return array{
     *     items: list<array{
     *         id: string,
     *         category: string,
     *         category_label: string,
     *         accent: string,
     *         icon: string,
     *         title: string,
     *         subtitle: string,
     *         meta: list<string>,
     *         timestamp: Carbon,
     *         url: ?string
     *     }>,
     *     has_more: bool,
     *     total_fetched: int
     * }
     */
    public function getActivities(User $user, string $category = self::CATEGORY_ALL, string $search = '', int $limit = 25): array
    {
        $userId = (int) $user->getKey();
        $perSourceLimit = min(max($limit + 15, 40), 150);
        $entries = [];

        $include = fn (string $cat): bool => $category === self::CATEGORY_ALL || $category === $cat;

        if ($include(self::CATEGORY_RECEIVING)) {
            $entries = array_merge($entries, $this->fetchDeliveryOrderActivities($userId, $perSourceLimit));
            $entries = array_merge($entries, $this->fetchDelayLogActivities($userId, $perSourceLimit));
        }

        if ($include(self::CATEGORY_GRS_RDTV)) {
            $entries = array_merge($entries, $this->fetchGrsRdtvActivities($userId, $perSourceLimit));
            $entries = array_merge($entries, $this->fetchRdtvComplianceActivities($userId, $perSourceLimit));
        }

        if ($include(self::CATEGORY_INVOICING)) {
            $entries = array_merge($entries, $this->fetchInvoicingEmailActivities($userId, $perSourceLimit));
            $entries = array_merge($entries, $this->fetchCashierTransmittalActivities($userId, $perSourceLimit));
            $entries = array_merge($entries, $this->fetchInvoicingPicActivities($userId, $perSourceLimit));
        }

        if ($include(self::CATEGORY_WAREHOUSE)) {
            $entries = array_merge($entries, $this->fetchWarehouseTransmittalActivities($userId, $perSourceLimit));
            $entries = array_merge($entries, $this->fetchWarehouseBatchActivities($userId, $perSourceLimit));
            $entries = array_merge($entries, $this->fetchTransmittalActivities($userId, $perSourceLimit));
        }

        if ($include(self::CATEGORY_MATERIAL_ISSUE)) {
            $entries = array_merge($entries, $this->fetchMaterialIssueActivities($userId, $perSourceLimit));
        }

        if ($include(self::CATEGORY_MONITORING)) {
            $entries = array_merge($entries, $this->fetchQcHistoryActivities($userId, $perSourceLimit));
            $entries = array_merge($entries, $this->fetchMonitoringKarungActivities($userId, $perSourceLimit));
            $entries = array_merge($entries, $this->fetchMonitoringChemicalActivities($userId, $perSourceLimit));
            $entries = array_merge($entries, $this->fetchMonitoringNpkActivities($userId, $perSourceLimit));
            $entries = array_merge($entries, $this->fetchNonStockAgingLogActivities($userId, $perSourceLimit));
        }

        $cleanSearch = Str::lower(trim($search));
        if ($cleanSearch !== '') {
            $terms = array_values(array_filter(explode(' ', preg_replace('/\s+/u', ' ', $cleanSearch) ?? $cleanSearch)));
            $entries = array_values(array_filter($entries, function (array $entry) use ($terms): bool {
                $haystack = Str::lower(implode(' ', [
                    $entry['title'],
                    $entry['subtitle'],
                    $entry['category_label'],
                    implode(' ', $entry['meta']),
                ]));

                foreach ($terms as $term) {
                    if (! str_contains($haystack, $term)) {
                        return false;
                    }
                }

                return true;
            }));
        }

        usort($entries, fn (array $a, array $b): int => $b['timestamp']->getTimestamp() <=> $a['timestamp']->getTimestamp());

        $totalFetched = count($entries);
        $hasMore = $totalFetched > $limit;

        return [
            'items' => array_slice($entries, 0, $limit),
            'has_more' => $hasMore,
            'total_fetched' => $totalFetched,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchDeliveryOrderActivities(int $userId, int $limit): array
    {
        if (! $this->tableExists('delivery_order_receipts')) {
            return [];
        }

        try {
            return DeliveryOrderReceipt::query()
                ->where(fn ($q) => $q->where('created_by', $userId)->orWhere('received_by', $userId))
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(function (DeliveryOrderReceipt $r) use ($userId): array {
                    $verb = ((int) $r->created_by === $userId) ? 'Mencatat penerimaan DO' : 'Menerima fisik DO';
                    $doNo = filled($r->delivery_order_no) ? $r->delivery_order_no : "#{$r->id}";

                    return [
                        'id' => "do-{$r->id}",
                        'category' => self::CATEGORY_RECEIVING,
                        'category_label' => 'Penerimaan DO',
                        'accent' => 'orange',
                        'icon' => 'heroicon-o-truck',
                        'title' => "{$verb} {$doNo}",
                        'subtitle' => $r->description ? Str::limit(trim((string) $r->description), 90) : 'Dokumen penerimaan barang di Receiving.',
                        'meta' => array_values(array_filter([
                            filled($r->status) ? "Status: {$r->status}" : null,
                            filled($r->stage) ? "Tahap: {$r->stage}" : null,
                            filled($r->source_type) ? $r->source_type : null,
                        ])),
                        'timestamp' => $r->created_at ?? now(),
                        'url' => $this->resolveResourceUrl(DeliveryOrderReceiptResource::class, $r),
                    ];
                })
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchDelayLogActivities(int $userId, int $limit): array
    {
        if (! $this->tableExists('delivery_order_receipt_delay_logs')) {
            return [];
        }

        try {
            return DeliveryOrderReceiptDelayLog::query()
                ->with('deliveryOrderReceipt:id,delivery_order_no')
                ->where('created_by', $userId)
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(function (DeliveryOrderReceiptDelayLog $log): array {
                    $doNo = $log->deliveryOrderReceipt?->delivery_order_no ?: "#{$log->delivery_order_receipt_id}";

                    return [
                        'id' => "delay-{$log->id}",
                        'category' => self::CATEGORY_RECEIVING,
                        'category_label' => 'Catatan Penundaan DO',
                        'accent' => 'amber',
                        'icon' => 'heroicon-o-clock',
                        'title' => "Memperbarui status penundaan DO {$doNo}",
                        'subtitle' => Str::limit(trim((string) ($log->delay_notes ?: $log->delay_reason)), 110),
                        'meta' => array_values(array_filter([
                            filled($log->delay_reason) ? $log->delay_reason : null,
                        ])),
                        'timestamp' => $log->created_at ?? now(),
                        'url' => $log->deliveryOrderReceipt
                            ? $this->resolveResourceUrl(DeliveryOrderReceiptResource::class, $log->deliveryOrderReceipt)
                            : null,
                    ];
                })
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchGrsRdtvActivities(int $userId, int $limit): array
    {
        if (! $this->tableExists('grs_rdtvs')) {
            return [];
        }

        try {
            return GrsRdtv::query()
                ->with('grsRdtvItems:id,grs_rdtv_id,document_code')
                ->where('created_by', $userId)
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(function (GrsRdtv $r): array {
                    $cat = strtoupper((string) ($r->category ?: 'GRS/RDTV'));
                    $codes = $r->grsRdtvItems->pluck('document_code')->filter()->unique()->take(3)->implode(', ');

                    return [
                        'id' => "grs-{$r->id}",
                        'category' => self::CATEGORY_GRS_RDTV,
                        'category_label' => 'GRS & RDTV',
                        'accent' => 'blue',
                        'icon' => 'heroicon-o-document-check',
                        'title' => "Mencatat dokumen {$cat}".($codes !== '' ? " ({$codes})" : " #{$r->id}"),
                        'subtitle' => 'Transaksi GRS/RDTV tercatat di sistem.',
                        'meta' => array_values(array_filter([
                            "Kategori: {$cat}",
                            $r->grsRdtvItems->count() > 0 ? "{$r->grsRdtvItems->count()} item dokumen" : null,
                        ])),
                        'timestamp' => $r->created_at ?? now(),
                        'url' => $this->resolveResourceUrl(GrsRdtvResource::class, $r),
                    ];
                })
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchRdtvComplianceActivities(int $userId, int $limit): array
    {
        if (! $this->tableExists('rdtv_compliances')) {
            return [];
        }

        try {
            return RdtvCompliance::query()
                ->with('deliveryOrderReceipt:id,delivery_order_no')
                ->where('reviewed_by', $userId)
                ->latest('reviewed_at')
                ->limit($limit)
                ->get()
                ->map(function (RdtvCompliance $r): array {
                    $doNo = $r->deliveryOrderReceipt?->delivery_order_no ?: "#{$r->delivery_order_receipt_id}";

                    return [
                        'id' => "rdtv-comp-{$r->id}",
                        'category' => self::CATEGORY_GRS_RDTV,
                        'category_label' => 'Review RDTV',
                        'accent' => 'blue',
                        'icon' => 'heroicon-o-shield-check',
                        'title' => "Meninjau dokumen compliance RDTV untuk DO {$doNo}",
                        'subtitle' => Str::limit(trim((string) ($r->review_notes ?: $r->original_filename ?: 'Dokumen compliance vendor')), 100),
                        'meta' => array_values(array_filter([
                            filled($r->document_type) ? $r->document_type : null,
                            filled($r->status) ? "Status: {$r->status}" : null,
                        ])),
                        'timestamp' => $r->reviewed_at ?? $r->updated_at ?? now(),
                        'url' => $r->deliveryOrderReceipt
                            ? $this->resolveResourceUrl(DeliveryOrderReceiptResource::class, $r->deliveryOrderReceipt)
                            : null,
                    ];
                })
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchInvoicingEmailActivities(int $userId, int $limit): array
    {
        if (! $this->tableExists('invoicing_email_logs')) {
            return [];
        }

        try {
            return InvoicingEmailLog::query()
                ->with('invoicingReceipt:id,no_po,no_grs,no_invoice,vendor_name')
                ->where('sent_by', $userId)
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(function (InvoicingEmailLog $log): array {
                    $inv = $log->invoicingReceipt;

                    return [
                        'id' => "email-log-{$log->id}",
                        'category' => self::CATEGORY_INVOICING,
                        'category_label' => 'Email Info GRS/Invoice',
                        'accent' => 'emerald',
                        'icon' => 'heroicon-o-envelope',
                        'title' => 'Mengirim email '.($log->jenis ?: 'Info GRS').($inv?->no_po ? " untuk PO {$inv->no_po}" : ''),
                        'subtitle' => Str::limit(trim((string) ($log->subject ?: 'Notifikasi email ke vendor')), 100),
                        'meta' => array_values(array_filter([
                            $inv?->vendor_name ? Str::limit($inv->vendor_name, 35) : null,
                            filled($log->status) ? "Status: {$log->status}" : null,
                        ])),
                        'timestamp' => $log->sent_at ?? $log->created_at ?? now(),
                        'url' => $inv ? $this->resolveResourceUrl(InvoicingReceiptResource::class, $inv) : null,
                    ];
                })
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchCashierTransmittalActivities(int $userId, int $limit): array
    {
        if (! $this->tableExists('cashier_transmittals')) {
            return [];
        }

        try {
            return CashierTransmittal::query()
                ->where('created_by', $userId)
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(fn (CashierTransmittal $r): array => [
                    'id' => "cashier-{$r->id}",
                    'category' => self::CATEGORY_INVOICING,
                    'category_label' => 'Transmittal Kasir',
                    'accent' => 'emerald',
                    'icon' => 'heroicon-o-banknotes',
                    'title' => "Membuat Transmittal Kasir {$r->transmittal_no}",
                    'subtitle' => $r->destination ?: 'Pengiriman dokumen invoice ke kasir/akuntansi.',
                    'meta' => array_values(array_filter([
                        filled($r->status) ? "Status: {$r->status}" : null,
                    ])),
                    'timestamp' => $r->created_at ?? now(),
                    'url' => $this->resolveResourceUrl(CashierTransmittalResource::class, $r),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchInvoicingPicActivities(int $userId, int $limit): array
    {
        if (! $this->tableExists('invoicing_receipts')) {
            return [];
        }

        try {
            return InvoicingReceipt::query()
                ->where('pic_id', $userId)
                ->latest('updated_at')
                ->limit($limit)
                ->get()
                ->map(function (InvoicingReceipt $r): array {
                    $ref = InvoicingReceipt::isMeaningfulText($r->no_invoice)
                        ? "Invoice {$r->no_invoice}"
                        : (InvoicingReceipt::isMeaningfulText($r->no_grs) ? "GRS {$r->no_grs}" : "PO {$r->no_po}");

                    return [
                        'id' => "inv-pic-{$r->id}",
                        'category' => self::CATEGORY_INVOICING,
                        'category_label' => 'PIC Invoice',
                        'accent' => 'emerald',
                        'icon' => 'heroicon-o-receipt-percent',
                        'title' => "Menangani {$ref} sebagai PIC",
                        'subtitle' => Str::limit(trim((string) ($r->vendor_name ?: $r->nama_barang ?: 'Penagihan barang')), 95),
                        'meta' => array_values(array_filter([
                            filled($r->no_po) ? "PO: {$r->no_po}" : null,
                            filled($r->jenis) ? $r->jenis : null,
                            filled($r->status) ? "Status: {$r->status}" : null,
                        ])),
                        'timestamp' => $r->updated_at ?? $r->created_at ?? now(),
                        'url' => $this->resolveResourceUrl(InvoicingReceiptResource::class, $r),
                    ];
                })
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchWarehouseTransmittalActivities(int $userId, int $limit): array
    {
        if (! $this->tableExists('warehouse_transmittals')) {
            return [];
        }

        try {
            return WarehouseTransmittal::query()
                ->with('destination:id,name')
                ->withCount('items')
                ->where('created_by', $userId)
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(fn (WarehouseTransmittal $r): array => [
                    'id' => "wh-trans-{$r->id}",
                    'category' => self::CATEGORY_WAREHOUSE,
                    'category_label' => 'Transmittal Gudang',
                    'accent' => 'blue',
                    'icon' => 'heroicon-o-qr-code',
                    'title' => "Membuat Transmittal Gudang {$r->transmittal_no}",
                    'subtitle' => $r->destination?->name ? "Tujuan: {$r->destination->name}" : 'Pengiriman material ke gudang tujuan.',
                    'meta' => array_values(array_filter([
                        "{$r->items_count} item material",
                        $r->tanggal ? $r->tanggal->format('d/m/Y') : null,
                    ])),
                    'timestamp' => $r->created_at ?? now(),
                    'url' => $this->resolveResourceUrl(WarehouseTransmittalResource::class, $r),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchWarehouseBatchActivities(int $userId, int $limit): array
    {
        if (! $this->tableExists('warehouse_delivery_batches')) {
            return [];
        }

        try {
            return WarehouseDeliveryBatch::query()
                ->where('uploaded_by', $userId)
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(fn (WarehouseDeliveryBatch $r): array => [
                    'id' => "mb51-{$r->id}",
                    'category' => self::CATEGORY_WAREHOUSE,
                    'category_label' => 'Master MB51',
                    'accent' => 'blue',
                    'icon' => 'heroicon-o-arrow-up-tray',
                    'title' => "Mengunggah Master MB51: {$r->batch_title}",
                    'subtitle' => $r->file_name ? "Berkas: {$r->file_name}" : 'Impor data SAP MB51.',
                    'meta' => array_values(array_filter([
                        $r->total_items !== null ? "{$r->total_items} item" : null,
                        filled($r->status) ? "Status: {$r->status}" : null,
                    ])),
                    'timestamp' => $r->created_at ?? now(),
                    'url' => WarehouseDeliveryBatchResource::canAccess()
                        ? WarehouseDeliveryBatchResource::getUrl('index', ['search' => $r->batch_title])
                        : null,
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchTransmittalActivities(int $userId, int $limit): array
    {
        if (! $this->tableExists('transmittals')) {
            return [];
        }

        try {
            return Transmittal::query()
                ->where('created_by', $userId)
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(fn (Transmittal $r): array => [
                    'id' => "trans-{$r->id}",
                    'category' => self::CATEGORY_WAREHOUSE,
                    'category_label' => 'Transmittal QC',
                    'accent' => 'blue',
                    'icon' => 'heroicon-o-paper-airplane',
                    'title' => "Membuat Transmittal {$r->transmittal_no}",
                    'subtitle' => trim(implode(' — ', array_filter([$r->type, $r->destination]))) ?: 'Pengiriman dokumen transmittal.',
                    'meta' => array_values(array_filter([
                        filled($r->type) ? "Tipe: {$r->type}" : null,
                        filled($r->destination) ? "Tujuan: {$r->destination}" : null,
                    ])),
                    'timestamp' => $r->created_at ?? now(),
                    'url' => $this->resolveResourceUrl(TransmittalResource::class, $r),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchMaterialIssueActivities(int $userId, int $limit): array
    {
        if (! $this->tableExists('material_issues')) {
            return [];
        }

        try {
            return MaterialIssue::query()
                ->where('created_by', $userId)
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(fn (MaterialIssue $r): array => [
                    'id' => "mir-{$r->id}",
                    'category' => self::CATEGORY_MATERIAL_ISSUE,
                    'category_label' => 'Pengeluaran Material (MIR)',
                    'accent' => 'orange',
                    'icon' => 'heroicon-o-cube',
                    'title' => 'Mencatat pengeluaran material '.($r->mir_number ? "MIR {$r->mir_number}" : "#{$r->id}"),
                    'subtitle' => trim(implode(' • ', array_filter([$r->departemen, $r->diminta_oleh]))) ?: 'Pengeluaran material receiving.',
                    'meta' => array_values(array_filter([
                        filled($r->po_number) ? "PO: {$r->po_number}" : null,
                        filled($r->no_reservasi) ? "Reservasi: {$r->no_reservasi}" : null,
                    ])),
                    'timestamp' => $r->created_at ?? now(),
                    'url' => $this->resolveResourceUrl(MaterialIssueResource::class, $r),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchQcHistoryActivities(int $userId, int $limit): array
    {
        if (! $this->tableExists('qc_histories')) {
            return [];
        }

        try {
            return QcHistory::query()
                ->with('deliveryOrderReceipt:id,delivery_order_no')
                ->where('created_by', $userId)
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(function (QcHistory $r): array {
                    $doNo = $r->deliveryOrderReceipt?->delivery_order_no ?: "#{$r->delivery_order_receipt_id}";

                    return [
                        'id' => "qc-{$r->id}",
                        'category' => self::CATEGORY_MONITORING,
                        'category_label' => 'Riwayat QC',
                        'accent' => 'violet',
                        'icon' => 'heroicon-o-beaker',
                        'title' => "Memperbarui QC ({$r->status}) pada DO {$doNo}",
                        'subtitle' => Str::limit(trim((string) ($r->notes ?: 'Pembaruan proses inspeksi/QC.')), 100),
                        'meta' => array_values(array_filter([
                            filled($r->status) ? "Status QC: {$r->status}" : null,
                        ])),
                        'timestamp' => $r->created_at ?? now(),
                        'url' => $r->deliveryOrderReceipt
                            ? $this->resolveResourceUrl(DeliveryOrderReceiptResource::class, $r->deliveryOrderReceipt)
                            : null,
                    ];
                })
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchMonitoringKarungActivities(int $userId, int $limit): array
    {
        if (! $this->tableExists('monitoring_karungs')) {
            return [];
        }

        try {
            return MonitoringKarung::query()
                ->where(fn ($q) => $q->where('created_by', $userId)->orWhere('updated_by', $userId))
                ->latest('updated_at')
                ->limit($limit)
                ->get()
                ->map(fn (MonitoringKarung $r): array => [
                    'id' => "karung-{$r->id}",
                    'category' => self::CATEGORY_MONITORING,
                    'category_label' => 'Monitoring Karung',
                    'accent' => 'violet',
                    'icon' => 'heroicon-o-archive-box',
                    'title' => "Mengelola Monitoring Karung PO {$r->no_po}",
                    'subtitle' => trim(implode(' • ', array_filter([$r->vendor_name, $r->kategori_karung]))) ?: 'Monitoring penerimaan karung.',
                    'meta' => array_values(array_filter([
                        filled($r->lokasi_pabrik) ? $r->lokasi_pabrik : null,
                        filled($r->status) ? "Status: {$r->status}" : null,
                    ])),
                    'timestamp' => $r->updated_at ?? $r->created_at ?? now(),
                    'url' => $this->resolveResourceUrl(MonitoringKarungResource::class, $r),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchMonitoringChemicalActivities(int $userId, int $limit): array
    {
        if (! $this->tableExists('monitoring_chemicals')) {
            return [];
        }

        try {
            return MonitoringChemical::query()
                ->where(fn ($q) => $q->where('created_by', $userId)->orWhere('received_by', $userId))
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(fn (MonitoringChemical $r): array => [
                    'id' => "chem-{$r->id}",
                    'category' => self::CATEGORY_MONITORING,
                    'category_label' => 'Monitoring Chemical',
                    'accent' => 'violet',
                    'icon' => 'heroicon-o-beaker',
                    'title' => 'Mencatat Monitoring Chemical'.($r->do_number ? " DO {$r->do_number}" : " #{$r->id}"),
                    'subtitle' => $r->material_category ? "Kategori: {$r->material_category}" : 'Monitoring penerimaan chemical.',
                    'meta' => array_values(array_filter([
                        filled($r->doc_status) ? "Status: {$r->doc_status}" : null,
                    ])),
                    'timestamp' => $r->created_at ?? now(),
                    'url' => $this->resolveResourceUrl(MonitoringChemicalResource::class, $r),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchMonitoringNpkActivities(int $userId, int $limit): array
    {
        if (! $this->tableExists('monitoring_npks')) {
            return [];
        }

        try {
            return MonitoringNpk::query()
                ->where('created_by', $userId)
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(fn (MonitoringNpk $r): array => [
                    'id' => "npk-{$r->id}",
                    'category' => self::CATEGORY_MONITORING,
                    'category_label' => 'Monitoring NPK',
                    'accent' => 'violet',
                    'icon' => 'heroicon-o-sparkles',
                    'title' => 'Mencatat Monitoring NPK'.($r->delivery_oder_number ? " DO {$r->delivery_oder_number}" : " #{$r->id}"),
                    'subtitle' => $r->stage ? "Tahap: {$r->stage}" : 'Monitoring penerimaan bahan baku NPK.',
                    'meta' => array_values(array_filter([
                        filled($r->doc_status) ? "Status: {$r->doc_status}" : null,
                    ])),
                    'timestamp' => $r->created_at ?? now(),
                    'url' => $this->resolveResourceUrl(MonitoringNpkResource::class, $r),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchNonStockAgingLogActivities(int $userId, int $limit): array
    {
        if (! $this->tableExists('nonstock_aging_logs')) {
            return [];
        }

        try {
            return NonStockAgingLog::query()
                ->where('created_by', $userId)
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(fn (NonStockAgingLog $log): array => [
                    'id' => "aging-log-{$log->id}",
                    'category' => self::CATEGORY_MONITORING,
                    'category_label' => 'Aging Non-Stock',
                    'accent' => 'amber',
                    'icon' => 'heroicon-o-bell-alert',
                    'title' => "Tindak lanjut aging: {$log->action_type}",
                    'subtitle' => Str::limit(trim((string) ($log->notes ?: $log->recipient_name ?: 'Pembaruan kontrol aging non-stock.')), 100),
                    'meta' => array_values(array_filter([
                        filled($log->channel) ? "Kanal: {$log->channel}" : null,
                        filled($log->aging_zone_snapshot) ? "Zona: {$log->aging_zone_snapshot}" : null,
                    ])),
                    'timestamp' => $log->created_at ?? now(),
                    'url' => NonStockAgingMonitoring::canAccess() ? NonStockAgingMonitoring::getUrl() : null,
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param  class-string<resource>  $resource
     */
    private function resolveResourceUrl(string $resource, Model $record): ?string
    {
        try {
            if (! $resource::canAccess()) {
                return null;
            }

            if ($resource::hasPage('view') && $resource::canView($record)) {
                return $resource::getUrl('view', ['record' => $record]);
            }

            if ($resource::hasPage('edit') && $resource::canEdit($record)) {
                return $resource::getUrl('edit', ['record' => $record]);
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  callable(): int  $callback
     */
    private function safeCount(string $table, callable $callback): int
    {
        if (! $this->tableExists($table)) {
            return 0;
        }

        try {
            return $callback();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function tableExists(string $table): bool
    {
        if (! array_key_exists($table, $this->tableExistsCache)) {
            try {
                $this->tableExistsCache[$table] = Schema::hasTable($table);
            } catch (\Throwable) {
                $this->tableExistsCache[$table] = false;
            }
        }

        return $this->tableExistsCache[$table];
    }
}
