<?php

namespace App\Filament\GlobalSearch;

use App\Filament\Pages\AntreanPengirimanGudang;
use App\Filament\Pages\EditProfile;
use App\Filament\Pages\NonStockAgingMonitoring;
use App\Filament\Pages\ScanWarehouseTransmittal;
use App\Filament\Resources\CashierTransmittals\CashierTransmittalResource;
use App\Filament\Resources\ChemicalQcTuvs\ChemicalQcTuvResource;
use App\Filament\Resources\DeliveryOrderReceiptDetails\DeliveryOrderReceiptDetailResource;
use App\Filament\Resources\DeliveryOrderReceipts\DeliveryOrderReceiptResource;
use App\Filament\Resources\GrsRdtvs\GrsRdtvResource;
use App\Filament\Resources\InvoicingReceipts\InvoicingReceiptResource;
use App\Filament\Resources\LocationReceivings\LocationReceivingResource;
use App\Filament\Resources\MaterialIssues\MaterialIssueResource;
use App\Filament\Resources\MonitoringChemicalDetails\MonitoringChemicalDetailResource;
use App\Filament\Resources\MonitoringChemicals\MonitoringChemicalResource;
use App\Filament\Resources\MonitoringKarungs\MonitoringKarungResource;
use App\Filament\Resources\MonitoringNpkDetails\MonitoringNpkDetailResource;
use App\Filament\Resources\MonitoringNpks\MonitoringNpkResource;
use App\Filament\Resources\Permissions\PermissionResource;
use App\Filament\Resources\PicMaterialAssignments\PicMaterialAssignmentResource;
use App\Filament\Resources\PurchaseOrderIssueds\PurchaseOrderIssuedResource;
use App\Filament\Resources\RequestingUnits\RequestingUnitResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Transmittals\TransmittalResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Vendors\VendorResource;
use App\Filament\Resources\WarehouseDeliveryBatches\WarehouseDeliveryBatchResource;
use App\Filament\Resources\WarehouseDestinations\WarehouseDestinationResource;
use App\Filament\Resources\WarehouseTransmittals\WarehouseTransmittalResource;
use App\Models\CashierTransmittal;
use App\Models\ChemicalQcTuv;
use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\GrsRdtv;
use App\Models\InvoicingReceipt;
use App\Models\LocationReceiving;
use App\Models\MaterialIssue;
use App\Models\MonitoringChemical;
use App\Models\MonitoringChemicalDetail;
use App\Models\MonitoringKarung;
use App\Models\MonitoringNpk;
use App\Models\MonitoringNpkDetail;
use App\Models\NonStockAgingControl;
use App\Models\Permission;
use App\Models\PicMaterialAssignment;
use App\Models\PurchaseOrderIssued;
use App\Models\RequestingUnit;
use App\Models\Role;
use App\Models\Transmittal;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WarehouseDeliveryBatch;
use App\Models\WarehouseDeliveryBatchItem;
use App\Models\WarehouseDestination;
use App\Models\WarehouseTransmittal;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\GlobalSearch\GlobalSearchResults;
use Filament\GlobalSearch\Providers\Contracts\GlobalSearchProvider;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SystemGlobalSearchProvider implements GlobalSearchProvider
{
    private const MAX_RESULTS_PER_CATEGORY = 6;

    private const MAX_MENU_RESULTS = 8;

    /**
     * Cache keberadaan tabel per-request untuk mencegah error pada skema parsial saat pengujian.
     *
     * @var array<string, bool>
     */
    private array $tableExistsCache = [];

    /**
     * Kata kunci tambahan agar pengguna mudah menemukan halaman/menu berdasarkan istilah umum.
     *
     * @var array<string, list<string>>
     */
    private const MENU_KEYWORD_ALIASES = [
        'dashboard' => ['beranda admin', 'ringkasan', 'utama', 'overview', 'home', 'statistik'],
        'delivery-order-receipts' => ['do', 'delivery order', 'penerimaan barang', 'receiving', '103', 'dof'],
        'delivery-order-receipt-details' => ['detail do', 'item do', 'material do', 'rincian barang'],
        'grs-rdtvs' => ['grs', 'rdtv', 'slip', 'qc', 'dokumen grs'],
        'invoicing-receipts' => ['invoice', 'penagihan', 'faktur pajak', 'tagihan', 'verifikasi grs', 'info grs'],
        'combined-grs-verification-upload' => ['upload grs', 'batch grs', 'verifikasi grs', 'impor grs', 'pdf grs'],
        'cashier-transmittals' => ['kasir', 'akuntansi', 'kirim kasir', 'transmittal kasir'],
        'transmittals' => ['transmittal', 'serah terima', 'dokumen qc'],
        'purchase-order-issueds' => ['po', 'po terbit', 'sap', 'purchase order', 'order pembelian'],
        'material-issues' => ['mir', 'pengeluaran material', 'reservasi', 'jor', 'wo', 'ambil barang'],
        'monitoring-karungs' => ['karung', 'zpm', 'sack', 'packaging', 'kemasan'],
        'monitoring-chemicals' => ['chemical', 'kimia', 'simala', 'coa', 'zsm'],
        'monitoring-chemical-details' => ['detail chemical', 'rincian chemical', 'tahapan chemical'],
        'monitoring-npks' => ['npk', 'laprima', 'sample', 'pupuk'],
        'monitoring-npk-details' => ['detail npk', 'rincian npk'],
        'chemical-qc-tuvs' => ['qc karung', 'tuv', 'tahapan qc'],
        'non-stock-aging-monitoring' => ['aging', 'non-stock', 'nonstock', 'pd', 'tertinggal', 'followup'],
        'monitoring-material' => ['monitoring material', 'tracking material', 'posisi barang'],
        'scan-warehouse-transmittal' => ['scan gudang', 'kirim gudang', 'barcode', 'qr', '105', 'mb51'],
        'warehouse-delivery-batches' => ['mb51', 'batch pengiriman', 'master pengiriman', 'impor mb51'],
        'warehouse-transmittals' => ['transmittal gudang', 'surat jalan gudang', 'pengiriman gudang'],
        'antrean-pengiriman-gudang' => ['antrean gudang', 'antrian pengiriman'],
        'laporan-bulanan-receiving' => ['laporan', 'bulanan', 'report', 'rekap', 'export'],
        'laporan-material-diambil' => ['laporan material', 'material diambil', 'barang diambil'],
        'email-grs-settings' => ['email', 'smtp', 'zimbra', 'pengaturan email', 'template email'],
        'vendors' => ['vendor', 'supplier', 'rekanan', 'mitra', 'email vendor'],
        'location-receivings' => ['lokasi', 'rak', 'penempatan', 'gudang receiving'],
        'warehouse-destinations' => ['gudang tujuan', 'tujuan pengiriman', 'warehouse'],
        'requesting-units' => ['unit peminta', 'departemen', 'pic unit', 'avp', 'vp', 'requisitioner'],
        'pic-material-assignments' => ['pic material', 'pembagian pic', 'penanggung jawab'],
        'users' => ['pengguna', 'user', 'akun', 'npk', 'karyawan'],
        'roles' => ['role', 'peran', 'jabatan', 'hak akses'],
        'permissions' => ['permission', 'izin', 'akses'],
    ];

    public function getResults(string $query): ?GlobalSearchResults
    {
        $cleanQuery = trim($query);

        if ($cleanQuery === '') {
            return null;
        }

        $builder = GlobalSearchResults::make();
        $terms = $this->extractTerms($cleanQuery);

        if ($terms === []) {
            return $builder;
        }

        $menuResults = $this->searchNavigationMenus($terms);
        if ($menuResults->isNotEmpty()) {
            $builder->category(__('Menu & Halaman'), $menuResults);
        }

        foreach ($this->buildResourceSearchDefinitions() as $definition) {
            $results = $this->runResourceSearch($definition, $terms, $cleanQuery);

            if ($results->isNotEmpty()) {
                $builder->category($definition['label'], $results);
            }
        }

        $agingResults = $this->searchNonStockAging($terms, $cleanQuery);
        if ($agingResults->isNotEmpty()) {
            $builder->category(__('Aging Material Non-Stock & PD'), $agingResults);
        }

        $mb51ItemResults = $this->searchWarehouseBatchItems($terms, $cleanQuery);
        if ($mb51ItemResults->isNotEmpty()) {
            $builder->category(__('Item Pengiriman Gudang (MB51)'), $mb51ItemResults);
        }

        return $builder;
    }

    /**
     * Memecah kata kunci pencarian menjadi daftar token unik non-kosong.
     *
     * @return list<string>
     */
    private function extractTerms(string $query): array
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($query)) ?? trim($query);

        if ($normalized === '') {
            return [];
        }

        $parts = explode(' ', $normalized);

        return array_values(array_filter(array_unique($parts), fn (string $word): bool => $word !== ''));
    }

    /**
     * Mencari seluruh menu, halaman, resource, dan cluster yang dapat diakses pengguna.
     *
     * @param  list<string>  $terms
     * @return Collection<int, GlobalSearchResult>
     */
    private function searchNavigationMenus(array $terms): Collection
    {
        $entries = [];
        $seenUrls = [];

        $navigationGroups = [];
        try {
            $navigationGroups = Filament::getNavigation();
        } catch (\Throwable) {
            $navigationGroups = [];
        }

        foreach ($navigationGroups as $group) {
            $groupLabel = trim((string) ($group->getLabel() ?? ''));

            foreach ($group->getItems() as $item) {
                $url = $item->getUrl();
                if (blank($url) || isset($seenUrls[$url])) {
                    continue;
                }

                $seenUrls[$url] = true;
                $label = trim((string) $item->getLabel());
                $entries[] = [
                    'title' => $label,
                    'url' => $url,
                    'group' => $groupLabel !== '' ? $groupLabel : __('Utama'),
                    'haystack' => $this->buildMenuHaystack($label, $groupLabel, $url),
                ];

                foreach ($item->getChildItems() as $child) {
                    $childUrl = $child->getUrl();
                    if (blank($childUrl) || isset($seenUrls[$childUrl])) {
                        continue;
                    }

                    $seenUrls[$childUrl] = true;
                    $childLabel = trim((string) $child->getLabel());
                    $entries[] = [
                        'title' => $childLabel,
                        'url' => $childUrl,
                        'group' => $groupLabel !== '' ? "{$groupLabel} / {$label}" : $label,
                        'haystack' => $this->buildMenuHaystack($childLabel, "{$groupLabel} {$label}", $childUrl),
                    ];
                }
            }
        }

        if (Filament::auth()->check()) {
            try {
                $profileUrl = EditProfile::getUrl();
                if (! isset($seenUrls[$profileUrl])) {
                    $seenUrls[$profileUrl] = true;
                    $entries[] = [
                        'title' => __('Edit Profil'),
                        'url' => $profileUrl,
                        'group' => __('Akun'),
                        'haystack' => 'edit profil akun password sandi foto avatar npk profile',
                    ];
                }
            } catch (\Throwable) {
                // Abaikan jika route profil belum terdaftar di lingkungan test tertentu.
            }

            try {
                $homeUrl = route('home');
                if (! isset($seenUrls[$homeUrl])) {
                    $seenUrls[$homeUrl] = true;
                    $entries[] = [
                        'title' => __('Halaman Depan (Beranda)'),
                        'url' => $homeUrl,
                        'group' => __('Portal Publik'),
                        'haystack' => 'halaman depan beranda frontend portal publik home landing',
                    ];
                }
            } catch (\Throwable) {
                // Abaikan jika route home tidak tersedia.
            }
        }

        $matched = [];

        foreach ($entries as $entry) {
            $allMatch = true;
            foreach ($terms as $term) {
                if (! str_contains($entry['haystack'], Str::lower($term))) {
                    $allMatch = false;
                    break;
                }
            }

            if (! $allMatch) {
                continue;
            }

            $titleLower = Str::lower($entry['title']);
            $firstTerm = Str::lower($terms[0]);
            $score = match (true) {
                $titleLower === $firstTerm => 1,
                str_starts_with($titleLower, $firstTerm) => 2,
                str_contains($titleLower, $firstTerm) => 3,
                default => 4,
            };

            $matched[] = [
                'score' => $score,
                'result' => new GlobalSearchResult(
                    title: $entry['title'],
                    url: $entry['url'],
                    details: [
                        __('Kategori') => $entry['group'],
                    ],
                ),
            ];
        }

        usort($matched, fn (array $a, array $b): int => $a['score'] <=> $b['score']);

        return collect(array_slice($matched, 0, self::MAX_MENU_RESULTS))
            ->map(fn (array $row): GlobalSearchResult => $row['result']);
    }

    private function buildMenuHaystack(string $label, string $groupLabel, string $url): string
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $slug = Str::afterLast($path, '/');

        $aliases = self::MENU_KEYWORD_ALIASES[$slug] ?? [];
        if ($path === 'admin' || $slug === 'admin') {
            $aliases = array_merge($aliases, self::MENU_KEYWORD_ALIASES['dashboard']);
        }

        return Str::lower(implode(' ', array_filter([
            $label,
            $groupLabel,
            str_replace(['-', '_', '/'], ' ', $path),
            implode(' ', $aliases),
        ])));
    }

    /**
     * Daftar lengkap konfigurasi pencarian untuk seluruh 24 Resource pada panel admin.
     *
     * @return list<array{
     *     resource: class-string<resource>,
     *     label: string,
     *     attributes: list<string>,
     *     with?: list<string>,
     *     title: callable(Model): string,
     *     details: callable(Model): array<string, string>,
     *     url?: callable(Model, string): ?string
     * }>
     */
    private function buildResourceSearchDefinitions(): array
    {
        return [
            [
                'resource' => DeliveryOrderReceiptResource::class,
                'label' => DeliveryOrderReceiptResource::getPluralModelLabel(),
                'attributes' => [
                    'delivery_order_no',
                    'document_code',
                    'qr_103_code',
                    'dof_number',
                    'status',
                    'stage',
                    'source_type',
                    'current_location',
                    'description',
                    'delay_reason',
                    'deliveryOrderReceiptDetails.material_code',
                    'deliveryOrderReceiptDetails.description',
                    'deliveryOrderReceiptDetails.purchaseOrderIssued.purchase_order_no',
                    'deliveryOrderReceiptDetails.purchaseOrderIssued.vendor_name',
                    'grsRdtvItems.document_code',
                ],
                'with' => ['deliveryOrderReceiptDetails.purchaseOrderIssued', 'grsRdtvItems'],
                'title' => fn (DeliveryOrderReceipt $r): string => filled($r->delivery_order_no)
                    ? "DO {$r->delivery_order_no}"
                    : "Penerimaan #{$r->id}",
                'details' => function (DeliveryOrderReceipt $r): array {
                    $firstDetail = $r->deliveryOrderReceiptDetails->first();
                    $po = $firstDetail?->purchaseOrderIssued;
                    $grs = $r->grsRdtvItems->pluck('document_code')->filter()->unique()->take(2)->implode(', ');

                    return $this->cleanDetails([
                        'No. PO' => $po?->purchase_order_no,
                        'Vendor' => $po?->vendor_name,
                        'GRS/RDTV' => $grs ?: $r->document_code,
                        'Status' => $r->status,
                        'Tanggal' => $this->formatDate($r->received_date),
                    ]);
                },
            ],
            [
                'resource' => InvoicingReceiptResource::class,
                'label' => InvoicingReceiptResource::getPluralModelLabel(),
                'attributes' => [
                    'no_invoice',
                    'no_grs',
                    'no_rdtv',
                    'no_po',
                    'no_do',
                    'faktur_pajak',
                    'vendor_name',
                    'nama_barang',
                    'deskripsi',
                    'email_vendor',
                    'material_type',
                    'status',
                    'jenis',
                    'keterangan',
                    'items.material_code',
                    'items.no_grs',
                    'items.nama_barang',
                ],
                'title' => function (InvoicingReceipt $r): string {
                    if (InvoicingReceipt::isMeaningfulText($r->no_invoice)) {
                        return "Invoice {$r->no_invoice}";
                    }
                    if (InvoicingReceipt::isMeaningfulText($r->no_grs)) {
                        return "GRS {$r->no_grs}";
                    }
                    if (InvoicingReceipt::isMeaningfulText($r->no_rdtv)) {
                        return "RDTV {$r->no_rdtv}";
                    }

                    return filled($r->no_po) ? "PO {$r->no_po}" : "Tagihan #{$r->id}";
                },
                'details' => fn (InvoicingReceipt $r): array => $this->cleanDetails([
                    'Jenis' => $r->jenis,
                    'No. PO' => $r->no_po,
                    'Vendor' => $r->vendor_name,
                    'No. GRS/RDTV' => $r->no_grs ?: $r->no_rdtv,
                    'Status' => $r->status,
                ]),
            ],
            [
                'resource' => PurchaseOrderIssuedResource::class,
                'label' => PurchaseOrderIssuedResource::getPluralModelLabel(),
                'attributes' => [
                    'purchase_order_and_item',
                    'purchase_order_no',
                    'material_code',
                    'description',
                    'vendor_name',
                    'vendor_id',
                    'material_type',
                    'mrp_type',
                    'po_status',
                    'requisitioner',
                ],
                'title' => fn (PurchaseOrderIssued $r): string => filled($r->purchase_order_and_item)
                    ? $r->purchase_order_and_item
                    : "PO {$r->purchase_order_no}",
                'details' => fn (PurchaseOrderIssued $r): array => $this->cleanDetails([
                    'Material' => $this->truncate(trim("{$r->material_code} {$r->description}"), 60),
                    'Vendor' => $r->vendor_name,
                    'Tipe' => trim("{$r->material_type} / {$r->mrp_type}", ' /'),
                    'Requisitioner' => $r->requisitioner,
                ]),
            ],
            [
                'resource' => GrsRdtvResource::class,
                'label' => GrsRdtvResource::getPluralModelLabel(),
                'attributes' => [
                    'category',
                    'transaction_date',
                    'grsRdtvItems.document_code',
                    'grsRdtvItems.status',
                    'grsRdtvItems.reason',
                    'grsRdtvItems.deliveryOrderReceipt.delivery_order_no',
                    'createdBy.name',
                ],
                'with' => ['grsRdtvItems.deliveryOrderReceipt'],
                'title' => function (GrsRdtv $r): string {
                    $codes = $r->grsRdtvItems->pluck('document_code')->filter()->unique()->take(2)->implode(', ');
                    $cat = strtoupper((string) ($r->category ?: 'GRS/RDTV'));

                    return $codes !== '' ? "{$cat} - {$codes}" : "{$cat} #{$r->id}";
                },
                'details' => function (GrsRdtv $r): array {
                    $dos = $r->grsRdtvItems
                        ->map(fn ($item) => $item->deliveryOrderReceipt?->delivery_order_no)
                        ->filter()
                        ->unique()
                        ->take(2)
                        ->implode(', ');

                    return $this->cleanDetails([
                        'Tanggal' => $this->formatDate($r->transaction_date),
                        'No. DO' => $dos,
                        'Jumlah Item' => (string) $r->grsRdtvItems->count(),
                    ]);
                },
            ],
            [
                'resource' => DeliveryOrderReceiptDetailResource::class,
                'label' => DeliveryOrderReceiptDetailResource::getPluralModelLabel(),
                'attributes' => [
                    'material_code',
                    'description',
                    'mrp_type',
                    'material_type',
                    'requisitioner',
                    'deliveryOrderReceipt.delivery_order_no',
                    'purchaseOrderIssued.purchase_order_no',
                    'purchaseOrderIssued.vendor_name',
                ],
                'with' => ['deliveryOrderReceipt', 'purchaseOrderIssued'],
                'title' => fn (DeliveryOrderReceiptDetail $r): string => trim(
                    ($r->material_code ? "[{$r->material_code}] " : '').($this->truncate((string) $r->description, 55) ?: "Item #{$r->id}")
                ),
                'details' => fn (DeliveryOrderReceiptDetail $r): array => $this->cleanDetails([
                    'No. DO' => $r->deliveryOrderReceipt?->delivery_order_no,
                    'No. PO' => $r->purchaseOrderIssued?->purchase_order_no,
                    'Qty' => $r->quantity !== null ? trim("{$r->quantity} {$r->uoi}") : null,
                    'Vendor' => $r->purchaseOrderIssued?->vendor_name,
                ]),
                'url' => function (DeliveryOrderReceiptDetail $r, string $rawQuery): ?string {
                    if ($r->deliveryOrderReceipt && DeliveryOrderReceiptResource::canView($r->deliveryOrderReceipt)) {
                        return DeliveryOrderReceiptResource::getUrl('view', ['record' => $r->deliveryOrderReceipt]);
                    }

                    return DeliveryOrderReceiptDetailResource::getUrl('index', ['search' => $r->material_code ?: $rawQuery]);
                },
            ],
            [
                'resource' => MaterialIssueResource::class,
                'label' => MaterialIssueResource::getPluralModelLabel(),
                'attributes' => [
                    'mir_number',
                    'po_number',
                    'no_reservasi',
                    'no_jor_wo',
                    'departemen',
                    'bagian',
                    'digunakan_untuk',
                    'no_alat',
                    'kode_biaya',
                    'diminta_oleh',
                    'npk',
                    'diterima_oleh',
                    'jenis_mir',
                    'deliveryOrderReceipt.delivery_order_no',
                ],
                'title' => fn (MaterialIssue $r): string => filled($r->mir_number)
                    ? "MIR {$r->mir_number}"
                    : (filled($r->po_number) ? "Pengeluaran PO {$r->po_number}" : "Pengeluaran #{$r->id}"),
                'details' => fn (MaterialIssue $r): array => $this->cleanDetails([
                    'No. PO' => $r->po_number,
                    'Departemen' => $r->departemen,
                    'Diminta Oleh' => $r->diminta_oleh,
                    'Tanggal' => $this->formatDate($r->tanggal),
                ]),
            ],
            [
                'resource' => CashierTransmittalResource::class,
                'label' => CashierTransmittalResource::getPluralModelLabel(),
                'attributes' => [
                    'transmittal_no',
                    'destination',
                    'status',
                    'notes',
                    'invoicingReceipts.no_invoice',
                    'invoicingReceipts.no_po',
                    'invoicingReceipts.vendor_name',
                ],
                'title' => fn (CashierTransmittal $r): string => $r->transmittal_no ?: "Transmittal Kasir #{$r->id}",
                'details' => fn (CashierTransmittal $r): array => $this->cleanDetails([
                    'Tujuan' => $r->destination,
                    'Status' => $r->status,
                    'Tanggal Kirim' => $this->formatDate($r->sent_date),
                ]),
            ],
            [
                'resource' => TransmittalResource::class,
                'label' => TransmittalResource::getPluralModelLabel(),
                'attributes' => [
                    'transmittal_no',
                    'type',
                    'destination',
                    'createdBy.name',
                    'transmittalItems.deliveryOrderReceipt.delivery_order_no',
                ],
                'title' => fn (Transmittal $r): string => $r->transmittal_no ?: "Transmittal #{$r->id}",
                'details' => fn (Transmittal $r): array => $this->cleanDetails([
                    'Tipe' => $r->type,
                    'Tujuan' => $r->destination,
                    'Tanggal' => $this->formatDate($r->created_at),
                ]),
            ],
            [
                'resource' => WarehouseTransmittalResource::class,
                'label' => WarehouseTransmittalResource::getPluralModelLabel(),
                'attributes' => [
                    'transmittal_no',
                    'destination.name',
                    'createdBy.name',
                    'batchItems.mat_doc_105',
                    'batchItems.po_number',
                    'batchItems.material_code',
                    'items.detail.material_code',
                ],
                'with' => ['destination'],
                'title' => fn (WarehouseTransmittal $r): string => $r->transmittal_no ?: "Transmittal Gudang #{$r->id}",
                'details' => fn (WarehouseTransmittal $r): array => $this->cleanDetails([
                    'Gudang Tujuan' => $r->destination?->name,
                    'Tanggal' => $this->formatDate($r->tanggal),
                ]),
            ],
            [
                'resource' => WarehouseDeliveryBatchResource::class,
                'label' => WarehouseDeliveryBatchResource::getPluralModelLabel(),
                'attributes' => [
                    'batch_title',
                    'file_name',
                    'status',
                    'notes',
                    'items.mat_doc_105',
                    'items.po_number',
                    'items.material_code',
                    'items.reference_do_no',
                ],
                'title' => fn (WarehouseDeliveryBatch $r): string => $r->batch_title ?: "Batch MB51 #{$r->id}",
                'details' => fn (WarehouseDeliveryBatch $r): array => $this->cleanDetails([
                    'Status' => $r->status,
                    'Total Item' => $r->total_items !== null ? (string) $r->total_items : null,
                    'Berkas' => $r->file_name,
                ]),
                'url' => fn (WarehouseDeliveryBatch $r): string => WarehouseDeliveryBatchResource::getUrl('index', [
                    'search' => $r->batch_title,
                ]),
            ],
            [
                'resource' => MonitoringKarungResource::class,
                'label' => MonitoringKarungResource::getPluralModelLabel(),
                'attributes' => [
                    'no_po',
                    'vendor_name',
                    'kategori_karung',
                    'lokasi_pabrik',
                    'status',
                    'catatan',
                    'deliveryItems.no_do',
                    'deliveryItems.no_invoice',
                    'qcItems.no_sik',
                    'qcItems.no_bapp',
                ],
                'title' => fn (MonitoringKarung $r): string => filled($r->no_po)
                    ? "PO {$r->no_po}"
                    : "Karung #{$r->id}",
                'details' => fn (MonitoringKarung $r): array => $this->cleanDetails([
                    'Vendor' => $r->vendor_name,
                    'Kategori' => $r->kategori_karung,
                    'Pabrik' => $r->lokasi_pabrik,
                    'Status' => $r->status,
                ]),
            ],
            [
                'resource' => MonitoringChemicalResource::class,
                'label' => MonitoringChemicalResource::getPluralModelLabel(),
                'attributes' => [
                    'do_number',
                    'material_category',
                    'qc_by',
                    'doc_status',
                    'monitoringChemicalDetails.tahapan',
                    'monitoringChemicalDetails.notes',
                    'monitoringChemicalDetails.purchaseOrderIssued.purchase_order_no',
                    'monitoringChemicalDetails.purchaseOrderIssued.description',
                ],
                'title' => fn (MonitoringChemical $r): string => filled($r->do_number)
                    ? "DO {$r->do_number}"
                    : "Chemical #{$r->id}",
                'details' => fn (MonitoringChemical $r): array => $this->cleanDetails([
                    'Kategori' => $r->material_category,
                    'Status' => $r->doc_status,
                    'Tanggal Terima' => $this->formatDate($r->received_date),
                ]),
            ],
            [
                'resource' => MonitoringChemicalDetailResource::class,
                'label' => MonitoringChemicalDetailResource::getPluralModelLabel(),
                'attributes' => [
                    'tahapan',
                    'notes',
                    'monitoringChemical.do_number',
                    'purchaseOrderIssued.purchase_order_no',
                    'purchaseOrderIssued.material_code',
                    'purchaseOrderIssued.description',
                ],
                'with' => ['monitoringChemical', 'purchaseOrderIssued'],
                'title' => fn (MonitoringChemicalDetail $r): string => trim(
                    ($r->purchaseOrderIssued?->purchase_order_no ? "PO {$r->purchaseOrderIssued->purchase_order_no}" : "Detail Chemical #{$r->id}")
                    .($r->tahapan ? " ({$r->tahapan})" : '')
                ),
                'details' => fn (MonitoringChemicalDetail $r): array => $this->cleanDetails([
                    'No. DO' => $r->monitoringChemical?->do_number,
                    'Material' => $this->truncate((string) $r->purchaseOrderIssued?->description, 50),
                    'Qty' => $r->quantity !== null ? (string) $r->quantity : null,
                ]),
                'url' => function (MonitoringChemicalDetail $r, string $rawQuery): ?string {
                    if ($r->monitoringChemical && MonitoringChemicalResource::canView($r->monitoringChemical)) {
                        return MonitoringChemicalResource::getUrl('view', ['record' => $r->monitoringChemical]);
                    }

                    return MonitoringChemicalDetailResource::getUrl('index', ['search' => $rawQuery]);
                },
            ],
            [
                'resource' => MonitoringNpkResource::class,
                'label' => MonitoringNpkResource::getPluralModelLabel(),
                'attributes' => [
                    'delivery_oder_number',
                    'stage',
                    'purchase_order_status',
                    'doc_status',
                    'purchaseOrderIssued.purchase_order_no',
                    'purchaseOrderIssued.vendor_name',
                    'details.material_code',
                    'details.description',
                ],
                'with' => ['purchaseOrderIssued'],
                'title' => fn (MonitoringNpk $r): string => filled($r->delivery_oder_number)
                    ? "DO {$r->delivery_oder_number}"
                    : "NPK #{$r->id}",
                'details' => fn (MonitoringNpk $r): array => $this->cleanDetails([
                    'No. PO' => $r->purchaseOrderIssued?->purchase_order_no,
                    'Tahap' => $r->stage,
                    'Status' => $r->doc_status ?: $r->purchase_order_status,
                ]),
            ],
            [
                'resource' => MonitoringNpkDetailResource::class,
                'label' => MonitoringNpkDetailResource::getPluralModelLabel(),
                'attributes' => [
                    'material_code',
                    'description',
                    'monitoringNpk.delivery_oder_number',
                ],
                'with' => ['monitoringNpk'],
                'title' => fn (MonitoringNpkDetail $r): string => trim(
                    ($r->material_code ? "[{$r->material_code}] " : '').($this->truncate((string) $r->description, 55) ?: "Detail NPK #{$r->id}")
                ),
                'details' => fn (MonitoringNpkDetail $r): array => $this->cleanDetails([
                    'No. DO' => $r->monitoringNpk?->delivery_oder_number,
                    'Qty' => $r->quantity !== null ? trim("{$r->quantity} {$r->uoi}") : null,
                ]),
                'url' => function (MonitoringNpkDetail $r, string $rawQuery): ?string {
                    if ($r->monitoringNpk && MonitoringNpkResource::canView($r->monitoringNpk)) {
                        return MonitoringNpkResource::getUrl('view', ['record' => $r->monitoringNpk]);
                    }

                    return MonitoringNpkDetailResource::getUrl('index', ['search' => $r->material_code ?: $rawQuery]);
                },
            ],
            [
                'resource' => ChemicalQcTuvResource::class,
                'label' => ChemicalQcTuvResource::getPluralModelLabel(),
                'attributes' => [
                    'tahapan_name',
                    'purchaseOrderIssued.purchase_order_no',
                    'purchaseOrderIssued.description',
                    'purchaseOrderIssued.vendor_name',
                ],
                'with' => ['purchaseOrderIssued'],
                'title' => fn (ChemicalQcTuv $r): string => $r->tahapan_name ?: "QC #{$r->id}",
                'details' => fn (ChemicalQcTuv $r): array => $this->cleanDetails([
                    'No. PO' => $r->purchaseOrderIssued?->purchase_order_no,
                    'Vendor' => $r->purchaseOrderIssued?->vendor_name,
                    'Qty QC' => $r->qty_qc_tuv !== null ? (string) $r->qty_qc_tuv : null,
                ]),
                'url' => fn (ChemicalQcTuv $r): string => ChemicalQcTuvResource::getUrl('index', [
                    'search' => $r->purchaseOrderIssued?->purchase_order_no ?: $r->tahapan_name,
                ]),
            ],
            [
                'resource' => VendorResource::class,
                'label' => VendorResource::getPluralModelLabel(),
                'attributes' => [
                    'name',
                    'vendor_number',
                    'email',
                    'city',
                    'street',
                    'telephone_1',
                    'salesperson',
                ],
                'title' => fn (Vendor $r): string => $r->name ?: "Vendor #{$r->id}",
                'details' => fn (Vendor $r): array => $this->cleanDetails([
                    'Kode Vendor' => $r->vendor_number,
                    'Kota' => $r->city,
                    'Email' => $this->truncate(implode(', ', Vendor::parseEmails($r->email)), 45),
                ]),
            ],
            [
                'resource' => RequestingUnitResource::class,
                'label' => RequestingUnitResource::getPluralModelLabel(),
                'attributes' => [
                    'name',
                    'pic_name',
                    'pic_npk',
                    'pic_email',
                    'avp_name',
                    'vp_name',
                ],
                'title' => fn (RequestingUnit $r): string => $r->name ?: "Unit #{$r->id}",
                'details' => fn (RequestingUnit $r): array => $this->cleanDetails([
                    'PIC' => $r->pic_name,
                    'AVP' => $r->avp_name,
                    'VP' => $r->vp_name,
                ]),
            ],
            [
                'resource' => WarehouseDestinationResource::class,
                'label' => WarehouseDestinationResource::getPluralModelLabel(),
                'attributes' => [
                    'name',
                    'pic.name',
                ],
                'with' => ['pic'],
                'title' => fn (WarehouseDestination $r): string => $r->name ?: "Gudang #{$r->id}",
                'details' => fn (WarehouseDestination $r): array => $this->cleanDetails([
                    'PIC' => $r->pic?->name,
                ]),
            ],
            [
                'resource' => LocationReceivingResource::class,
                'label' => LocationReceivingResource::getPluralModelLabel(),
                'attributes' => ['name'],
                'title' => fn (LocationReceiving $r): string => $r->name ?: "Lokasi #{$r->id}",
                'details' => fn (LocationReceiving $r): array => [],
                'url' => fn (LocationReceiving $r): string => LocationReceivingResource::getUrl('index', [
                    'search' => $r->name,
                ]),
            ],
            [
                'resource' => PicMaterialAssignmentResource::class,
                'label' => PicMaterialAssignmentResource::getPluralModelLabel(),
                'attributes' => [
                    'material_type',
                    'assignment_mode',
                    'notes',
                    'user.name',
                    'users.name',
                ],
                'with' => ['user', 'users'],
                'title' => fn (PicMaterialAssignment $r): string => "PIC {$r->material_type}",
                'details' => function (PicMaterialAssignment $r): array {
                    $pics = $r->users->pluck('name')->filter()->all();
                    if ($pics === [] && $r->user?->name) {
                        $pics = [$r->user->name];
                    }

                    return $this->cleanDetails([
                        'Mode' => $r->assignment_mode,
                        'PIC' => implode(', ', array_slice($pics, 0, 3)),
                    ]);
                },
            ],
            [
                'resource' => UserResource::class,
                'label' => UserResource::getPluralModelLabel(),
                'attributes' => [
                    'name',
                    'npk',
                    'email',
                    'roles.name',
                ],
                'with' => ['roles'],
                'title' => fn (User $r): string => $r->name ?: "User #{$r->id}",
                'details' => fn (User $r): array => $this->cleanDetails([
                    'NPK' => $r->npk,
                    'Email' => $r->email,
                    'Role' => $r->roles->pluck('name')->implode(', '),
                ]),
            ],
            [
                'resource' => RoleResource::class,
                'label' => RoleResource::getPluralModelLabel(),
                'attributes' => ['name', 'guard_name'],
                'title' => fn (Role $r): string => $r->name,
                'details' => fn (Role $r): array => $this->cleanDetails([
                    'Guard' => $r->guard_name,
                ]),
            ],
            [
                'resource' => PermissionResource::class,
                'label' => PermissionResource::getPluralModelLabel(),
                'attributes' => ['name', 'guard_name'],
                'title' => fn (Permission $r): string => $r->name,
                'details' => fn (Permission $r): array => $this->cleanDetails([
                    'Guard' => $r->guard_name,
                ]),
            ],
        ];
    }

    /**
     * Menjalankan query pencarian pada satu Filament Resource dengan pemeriksaan hak akses dan tabel.
     *
     * @param  array{
     *     resource: class-string<resource>,
     *     label: string,
     *     attributes: list<string>,
     *     with?: list<string>,
     *     title: callable(Model): string,
     *     details: callable(Model): array<string, string>,
     *     url?: callable(Model, string): ?string
     * }  $definition
     * @param  list<string>  $terms
     * @return Collection<int, GlobalSearchResult>
     */
    private function runResourceSearch(array $definition, array $terms, string $rawQuery): Collection
    {
        $resource = $definition['resource'];

        if (! $resource::canAccess()) {
            return collect();
        }

        $modelClass = $resource::getModel();
        /** @var Model $modelInstance */
        $modelInstance = new $modelClass;

        if (! $this->tableExists($modelInstance->getTable())) {
            return collect();
        }

        try {
            $query = $resource::getEloquentQuery();

            if (! empty($definition['with'])) {
                $query->with($definition['with']);
            }

            $this->applyMultiWordConstraints($query, $definition['attributes'], $terms);

            $records = $query
                ->latest($modelInstance->qualifyColumn($modelInstance->getKeyName()))
                ->limit(self::MAX_RESULTS_PER_CATEGORY)
                ->get();

            return $records->map(function (Model $record) use ($definition, $resource, $rawQuery): ?GlobalSearchResult {
                $url = isset($definition['url'])
                    ? ($definition['url'])($record, $rawQuery)
                    : $this->resolveDefaultResourceUrl($resource, $record, $rawQuery);

                if (blank($url)) {
                    return null;
                }

                return new GlobalSearchResult(
                    title: ($definition['title'])($record),
                    url: $url,
                    details: ($definition['details'])($record),
                );
            })->filter()->values();
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * Mencari data kontrol Aging Material Non-Stock & PD yang ditampilkan pada halaman khusus.
     *
     * @param  list<string>  $terms
     * @return Collection<int, GlobalSearchResult>
     */
    private function searchNonStockAging(array $terms, string $rawQuery): Collection
    {
        if (! NonStockAgingMonitoring::canAccess() || ! $this->tableExists('nonstock_aging_controls')) {
            return collect();
        }

        try {
            $query = NonStockAgingControl::query()->with('requestingUnit');

            $this->applyMultiWordConstraints($query, [
                'po_by_item',
                'material_code_snapshot',
                'description_snapshot',
                'vendor_name_snapshot',
                'requisitioner_raw',
                'location_text',
                'operational_status',
                'pic_name_override',
                'requestingUnit.name',
            ], $terms);

            return $query
                ->latest('id')
                ->limit(self::MAX_RESULTS_PER_CATEGORY)
                ->get()
                ->map(function (NonStockAgingControl $record) use ($rawQuery): GlobalSearchResult {
                    $statusLabel = $record->operational_status instanceof BackedEnum
                        ? $record->operational_status->value
                        : (string) ($record->operational_status ?? '');

                    return new GlobalSearchResult(
                        title: filled($record->po_by_item)
                            ? "Aging {$record->po_by_item}"
                            : "Aging #{$record->id}",
                        url: NonStockAgingMonitoring::getUrl([
                            'search' => $record->po_by_item ?: $rawQuery,
                        ]),
                        details: $this->cleanDetails([
                            'Material' => $this->truncate(trim("{$record->material_code_snapshot} {$record->description_snapshot}"), 55),
                            'Vendor' => $record->vendor_name_snapshot,
                            'Unit' => $record->requestingUnit?->name ?: $record->requisitioner_raw,
                            'Status' => $statusLabel,
                        ]),
                    );
                });
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * Mencari item pengiriman gudang MB51 (Dokumen 105 / 103 / PO / Material / DO).
     *
     * @param  list<string>  $terms
     * @return Collection<int, GlobalSearchResult>
     */
    private function searchWarehouseBatchItems(array $terms, string $rawQuery): Collection
    {
        $canAccess = WarehouseDeliveryBatchResource::canAccess()
            || ScanWarehouseTransmittal::canAccess()
            || AntreanPengirimanGudang::canAccess();

        if (! $canAccess || ! $this->tableExists('warehouse_delivery_batch_items')) {
            return collect();
        }

        try {
            $query = WarehouseDeliveryBatchItem::query()->with(['warehouseTransmittal', 'batch']);

            $this->applyMultiWordConstraints($query, [
                'mat_doc_105',
                'ref_doc_103',
                'po_number',
                'material_code',
                'description',
                'reference_do_no',
                'storage_location',
                'vendor_code',
                'status',
            ], $terms);

            return $query
                ->latest('id')
                ->limit(self::MAX_RESULTS_PER_CATEGORY)
                ->get()
                ->map(function (WarehouseDeliveryBatchItem $item) use ($rawQuery): ?GlobalSearchResult {
                    $url = null;

                    if (
                        $item->warehouseTransmittal
                        && WarehouseTransmittalResource::canAccess()
                        && WarehouseTransmittalResource::canEdit($item->warehouseTransmittal)
                    ) {
                        $url = WarehouseTransmittalResource::getUrl('edit', ['record' => $item->warehouseTransmittal]);
                    } elseif (WarehouseDeliveryBatchResource::canAccess()) {
                        $url = WarehouseDeliveryBatchResource::getUrl('index', [
                            'search' => $item->batch?->batch_title ?: $rawQuery,
                        ]);
                    } elseif (ScanWarehouseTransmittal::canAccess()) {
                        $url = ScanWarehouseTransmittal::getUrl();
                    }

                    if (blank($url)) {
                        return null;
                    }

                    return new GlobalSearchResult(
                        title: filled($item->mat_doc_105)
                            ? "GRS 105 {$item->mat_doc_105}"
                            : trim("{$item->po_number} / {$item->material_code}"),
                        url: $url,
                        details: $this->cleanDetails([
                            'No. PO' => $item->po_number,
                            'Material' => $this->truncate(trim("{$item->material_code} {$item->description}"), 55),
                            'No. DO' => $item->reference_do_no,
                            'Status' => $item->status,
                        ]),
                    );
                })
                ->filter()
                ->values();
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * Menerapkan filter pencarian multi-kata (AND antar kata, OR antar kolom/relasi) secara case-insensitive.
     *
     * @param  list<string>  $attributes
     * @param  list<string>  $terms
     */
    private function applyMultiWordConstraints(Builder $query, array $attributes, array $terms): void
    {
        $driver = $query->getConnection()->getDriverName();
        $castType = $driver === 'sqlite' ? 'TEXT' : 'CHAR';

        foreach ($terms as $term) {
            $likeTerm = '%'.Str::lower($term).'%';

            $query->where(function (Builder $subQuery) use ($attributes, $likeTerm, $castType): void {
                $isFirst = true;

                foreach ($attributes as $attribute) {
                    $boolean = $isFirst ? 'and' : 'or';

                    if (str_contains($attribute, '.')) {
                        $relation = Str::beforeLast($attribute, '.');
                        $column = Str::afterLast($attribute, '.');
                        $method = $boolean === 'and' ? 'whereHas' : 'orWhereHas';

                        $subQuery->{$method}($relation, function (Builder $relQuery) use ($column, $likeTerm, $castType): void {
                            $wrapped = $relQuery->getGrammar()->wrap($relQuery->qualifyColumn($column));
                            $relQuery->whereRaw("LOWER(CAST({$wrapped} AS {$castType})) LIKE ?", [$likeTerm]);
                        });
                    } else {
                        $wrapped = $subQuery->getGrammar()->wrap($subQuery->qualifyColumn($attribute));
                        $method = $boolean === 'and' ? 'whereRaw' : 'orWhereRaw';

                        $subQuery->{$method}("LOWER(CAST({$wrapped} AS {$castType})) LIKE ?", [$likeTerm]);
                    }

                    $isFirst = false;
                }
            });
        }
    }

    /**
     * Menentukan URL tujuan paling relevan untuk hasil pencarian sebuah Filament Resource.
     *
     * @param  class-string<resource>  $resource
     */
    private function resolveDefaultResourceUrl(string $resource, Model $record, string $rawQuery): ?string
    {
        if ($resource::hasPage('view') && $resource::canView($record)) {
            return $resource::getUrl('view', ['record' => $record]);
        }

        if ($resource::hasPage('edit') && $resource::canEdit($record)) {
            return $resource::getUrl('edit', ['record' => $record]);
        }

        if ($resource::hasPage('index')) {
            return $resource::getUrl('index', ['search' => $rawQuery]);
        }

        return null;
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

    /**
     * Menghapus detail kosong/strip agar tampilan hasil pencarian tetap ringkas.
     *
     * @param  array<string, string|null>  $details
     * @return array<string, string>
     */
    private function cleanDetails(array $details): array
    {
        $cleaned = [];

        foreach ($details as $key => $value) {
            $text = trim((string) $value);
            if ($text === '' || $text === '-') {
                continue;
            }
            $cleaned[$key] = $text;
        }

        return $cleaned;
    }

    private function formatDate(mixed $date): ?string
    {
        if ($date instanceof Carbon) {
            return $date->format('d/m/Y');
        }

        if (is_string($date) && trim($date) !== '') {
            try {
                return Carbon::parse($date)->format('d/m/Y');
            } catch (\Throwable) {
                return $date;
            }
        }

        return null;
    }

    private function truncate(string $value, int $limit): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);

        return Str::limit($clean, $limit);
    }
}
