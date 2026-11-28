<?php

namespace App\Services;

use App\Filament\Pages\AntreanPengirimanGudang;
use App\Filament\Pages\CombinedGrsVerificationUpload;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\EmailGrsSettings;
use App\Filament\Pages\LaporanBulananReceiving;
use App\Filament\Pages\LaporanMaterialDiambil;
use App\Filament\Pages\MonitoringMaterial;
use App\Filament\Pages\NonStockAgingMonitoring;
use App\Filament\Pages\ScanWarehouseTransmittal;
use App\Filament\Resources\CashierTransmittals\CashierTransmittalResource;
use App\Filament\Resources\DeliveryOrderReceiptDetails\DeliveryOrderReceiptDetailResource;
use App\Filament\Resources\DeliveryOrderReceipts\DeliveryOrderReceiptResource;
use App\Filament\Resources\GrsRdtvs\GrsRdtvResource;
use App\Filament\Resources\InvoicingReceipts\InvoicingReceiptResource;
use App\Filament\Resources\LocationReceivings\LocationReceivingResource;
use App\Filament\Resources\MaterialIssues\MaterialIssueResource;
use App\Filament\Resources\MonitoringChemicals\MonitoringChemicalResource;
use App\Filament\Resources\MonitoringKarungs\MonitoringKarungResource;
use App\Filament\Resources\MonitoringNpks\MonitoringNpkResource;
use App\Filament\Resources\PicMaterialAssignments\PicMaterialAssignmentResource;
use App\Filament\Resources\PurchaseOrderIssueds\PurchaseOrderIssuedResource;
use App\Filament\Resources\RequestingUnits\RequestingUnitResource;
use App\Filament\Resources\Transmittals\TransmittalResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Vendors\VendorResource;
use App\Filament\Resources\WarehouseDeliveryBatches\WarehouseDeliveryBatchResource;
use App\Filament\Resources\WarehouseDestinations\WarehouseDestinationResource;
use App\Filament\Resources\WarehouseTransmittals\WarehouseTransmittalResource;
use App\Models\User;
use Filament\Facades\Filament;

class LeftRailMenuService
{
    public const string DASHBOARD_KEY = 'dashboard';

    public const int MAX_CUSTOM_SHORTCUTS = 8;

    /**
     * Daftar kunci shortcut bawaan (di luar Dashboard yang selalu terkunci di urutan pertama).
     *
     * @return list<string>
     */
    public function defaultShortcutKeys(): array
    {
        return [
            'delivery_order_receipts',
            'grs_rdtvs',
            'combined_grs_upload',
            'invoicing_receipts',
            'scan_warehouse_transmittal',
            'non_stock_aging',
        ];
    }

    /**
     * Katalog lengkap menu Admin Panel yang dapat dipilih oleh user untuk Navigasi Kiri.
     *
     * @return array<string, array{
     *     key: string,
     *     label: string,
     *     group: string,
     *     class: class-string,
     *     icon: string,
     *     exact: bool,
     *     locked: bool
     * }>
     */
    public function catalog(): array
    {
        $items = [
            [
                'key' => self::DASHBOARD_KEY,
                'label' => __('Dashboard'),
                'group' => __('Utama'),
                'class' => Dashboard::class,
                'icon' => 'heroicon-o-squares-2x2',
                'exact' => true,
                'locked' => true,
            ],
            // Penerimaan & QC
            [
                'key' => 'delivery_order_receipts',
                'label' => __('Penerimaan DO'),
                'group' => __('Penerimaan & QC'),
                'class' => DeliveryOrderReceiptResource::class,
                'icon' => 'heroicon-o-truck',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'delivery_order_receipt_details',
                'label' => __('Detail Penerimaan DO'),
                'group' => __('Penerimaan & QC'),
                'class' => DeliveryOrderReceiptDetailResource::class,
                'icon' => 'heroicon-o-list-bullet',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'monitoring_material',
                'label' => __('Monitoring Material'),
                'group' => __('Penerimaan & QC'),
                'class' => MonitoringMaterial::class,
                'icon' => 'heroicon-o-cube',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'monitoring_chemical',
                'label' => __('Monitoring Chemical'),
                'group' => __('Penerimaan & QC'),
                'class' => MonitoringChemicalResource::class,
                'icon' => 'heroicon-o-beaker',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'monitoring_npk',
                'label' => __('Monitoring NPK'),
                'group' => __('Penerimaan & QC'),
                'class' => MonitoringNpkResource::class,
                'icon' => 'heroicon-o-circle-stack',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'grs_rdtvs',
                'label' => __('GRS & RDTV'),
                'group' => __('Penerimaan & QC'),
                'class' => GrsRdtvResource::class,
                'icon' => 'heroicon-o-document-check',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'transmittals',
                'label' => __('Pengajuan QC'),
                'group' => __('Penerimaan & QC'),
                'class' => TransmittalResource::class,
                'icon' => 'heroicon-o-paper-airplane',
                'exact' => false,
                'locked' => false,
            ],
            // Invoice & Verifikasi
            [
                'key' => 'invoicing_receipts',
                'label' => __('Invoice Barang'),
                'group' => __('Invoice'),
                'class' => InvoicingReceiptResource::class,
                'icon' => 'heroicon-o-receipt-percent',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'combined_grs_upload',
                'label' => __('Upload Gabungan GRS/RDTV'),
                'group' => __('Invoice'),
                'class' => CombinedGrsVerificationUpload::class,
                'icon' => 'heroicon-o-arrow-up-tray',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'cashier_transmittals',
                'label' => __('Transmittal Kasir'),
                'group' => __('Invoice'),
                'class' => CashierTransmittalResource::class,
                'icon' => 'heroicon-o-banknotes',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'monitoring_karung',
                'label' => __('Monitoring Karung'),
                'group' => __('Invoice'),
                'class' => MonitoringKarungResource::class,
                'icon' => 'heroicon-o-archive-box',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'email_grs_settings',
                'label' => __('Template Email GRS/RDTV'),
                'group' => __('Invoice'),
                'class' => EmailGrsSettings::class,
                'icon' => 'heroicon-o-envelope',
                'exact' => false,
                'locked' => false,
            ],
            // Gudang & Monitoring
            [
                'key' => 'scan_warehouse_transmittal',
                'label' => __('Scan Transmittal Gudang'),
                'group' => __('Gudang & Monitoring'),
                'class' => ScanWarehouseTransmittal::class,
                'icon' => 'heroicon-o-qr-code',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'warehouse_transmittals',
                'label' => __('Transmittal Gudang'),
                'group' => __('Gudang & Monitoring'),
                'class' => WarehouseTransmittalResource::class,
                'icon' => 'heroicon-o-document-text',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'warehouse_delivery_batches',
                'label' => __('Master MB51'),
                'group' => __('Gudang & Monitoring'),
                'class' => WarehouseDeliveryBatchResource::class,
                'icon' => 'heroicon-o-table-cells',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'antrean_pengiriman',
                'label' => __('Antrean Pengiriman'),
                'group' => __('Gudang & Monitoring'),
                'class' => AntreanPengirimanGudang::class,
                'icon' => 'heroicon-o-queue-list',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'material_issues',
                'label' => __('Material Issue (MIR)'),
                'group' => __('Gudang & Monitoring'),
                'class' => MaterialIssueResource::class,
                'icon' => 'heroicon-o-arrow-right-start-on-rectangle',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'non_stock_aging',
                'label' => __('Aging Material Non-Stock & PD'),
                'group' => __('Gudang & Monitoring'),
                'class' => NonStockAgingMonitoring::class,
                'icon' => 'heroicon-o-clock',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'requesting_units',
                'label' => __('Unit Peminta & PIC'),
                'group' => __('Gudang & Monitoring'),
                'class' => RequestingUnitResource::class,
                'icon' => 'heroicon-o-user-group',
                'exact' => false,
                'locked' => false,
            ],
            // Laporan
            [
                'key' => 'laporan_bulanan',
                'label' => __('Laporan Bulanan'),
                'group' => __('Laporan'),
                'class' => LaporanBulananReceiving::class,
                'icon' => 'heroicon-o-chart-bar',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'laporan_material_diambil',
                'label' => __('Laporan Material Diambil'),
                'group' => __('Laporan'),
                'class' => LaporanMaterialDiambil::class,
                'icon' => 'heroicon-o-clipboard-document-list',
                'exact' => false,
                'locked' => false,
            ],
            // Master & Sistem
            [
                'key' => 'purchase_order_issueds',
                'label' => __('Purchase Order Terbit'),
                'group' => __('Data Master & Sistem'),
                'class' => PurchaseOrderIssuedResource::class,
                'icon' => 'heroicon-o-document-duplicate',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'vendors',
                'label' => __('Data Vendor'),
                'group' => __('Data Master & Sistem'),
                'class' => VendorResource::class,
                'icon' => 'heroicon-o-building-office',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'warehouse_destinations',
                'label' => __('Gudang Tujuan'),
                'group' => __('Data Master & Sistem'),
                'class' => WarehouseDestinationResource::class,
                'icon' => 'heroicon-o-map-pin',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'location_receivings',
                'label' => __('Lokasi Receiving'),
                'group' => __('Data Master & Sistem'),
                'class' => LocationReceivingResource::class,
                'icon' => 'heroicon-o-building-office-2',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'pic_material_assignments',
                'label' => __('Aturan PIC Material'),
                'group' => __('Data Master & Sistem'),
                'class' => PicMaterialAssignmentResource::class,
                'icon' => 'heroicon-o-identification',
                'exact' => false,
                'locked' => false,
            ],
            [
                'key' => 'users',
                'label' => __('Pengguna'),
                'group' => __('Data Master & Sistem'),
                'class' => UserResource::class,
                'icon' => 'heroicon-o-users',
                'exact' => false,
                'locked' => false,
            ],
        ];

        $indexed = [];
        foreach ($items as $item) {
            $indexed[$item['key']] = $item;
        }

        return $indexed;
    }

    /**
     * Mengembalikan seluruh menu kustomisasi yang boleh diakses user saat ini (tanpa Dashboard yang terkunci).
     *
     * @return array<string, array{key: string, label: string, group: string, url: string, icon: string, exact: bool, locked: bool}>
     */
    public function availableCustomizableItems(): array
    {
        $catalog = $this->catalog();
        $accessible = [];

        foreach ($catalog as $key => $item) {
            if ($item['locked']) {
                continue;
            }

            $resolved = $this->resolveCatalogItem($item);
            if ($resolved !== null) {
                $accessible[$key] = $resolved;
            }
        }

        return $accessible;
    }

    /**
     * Mengelompokkan menu yang dapat dikustomisasi berdasarkan kategori untuk modal pengaturan.
     *
     * @return array<string, list<array{key: string, label: string, group: string, url: string, icon: string, exact: bool, locked: bool}>>
     */
    public function availableGroupedByCategory(): array
    {
        $grouped = [];
        foreach ($this->availableCustomizableItems() as $item) {
            $grouped[$item['group']][] = $item;
        }

        return $grouped;
    }

    /**
     * Mengembalikan daftar kunci shortcut aktif (non-dashboard) milik user,
     * memakai pilihan tersimpan (bila ada) atau daftar bawaan.
     *
     * @return list<string>
     */
    public function getSelectedCustomKeys(?User $user): array
    {
        $available = $this->availableCustomizableItems();
        $saved = $user?->left_rail_shortcuts;

        if (is_array($saved)) {
            return $this->sanitizeShortcutKeys($saved, array_keys($available));
        }

        return $this->sanitizeShortcutKeys($this->defaultShortcutKeys(), array_keys($available));
    }

    /**
     * Membersihkan daftar kunci pilihan user: menghapus 'dashboard' (karena selalu terkunci),
     * menghapus duplikat/menu tanpa akses, dan membatasi hingga MAX_CUSTOM_SHORTCUTS.
     *
     * @param  array<int, mixed>  $keys
     * @param  list<string>|null  $allowedKeys
     * @return list<string>
     */
    public function sanitizeShortcutKeys(array $keys, ?array $allowedKeys = null): array
    {
        $allowedLookup = array_flip($allowedKeys ?? array_keys($this->availableCustomizableItems()));
        $clean = [];
        $seen = [];

        foreach ($keys as $rawKey) {
            if (! is_string($rawKey)) {
                continue;
            }

            $key = trim($rawKey);
            if ($key === '' || $key === self::DASHBOARD_KEY) {
                continue;
            }

            if (! isset($allowedLookup[$key]) || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $clean[] = $key;

            if (count($clean) >= self::MAX_CUSTOM_SHORTCUTS) {
                break;
            }
        }

        return $clean;
    }

    /**
     * Menyusun daftar akhir tombol Rail Kiri: Dashboard SELALU terkunci di posisi pertama,
     * diikuti shortcut pilihan user sesuai urutan dan hak akses.
     *
     * @return list<array{key: string, label: string, url: string, icon: string, active: bool, locked: bool}>
     */
    public function resolveRailLinks(?User $user, ?string $currentUrl = null): array
    {
        $normCurrent = rtrim($currentUrl ?? url()->current(), '/');
        $links = [];

        // 1. Dashboard wajib di posisi pertama dan selalu terkunci
        $dashboardItem = $this->resolveDashboardItem();
        $dashNorm = rtrim($dashboardItem['url'], '/');
        $links[] = [
            'key' => self::DASHBOARD_KEY,
            'label' => $dashboardItem['label'],
            'url' => $dashboardItem['url'],
            'icon' => $dashboardItem['icon'],
            'active' => $normCurrent === $dashNorm,
            'locked' => true,
        ];

        // 2. Shortcut kustomisasi milik user
        $available = $this->availableCustomizableItems();
        $selectedKeys = $this->getSelectedCustomKeys($user);

        foreach ($selectedKeys as $key) {
            if (! isset($available[$key])) {
                continue;
            }

            $item = $available[$key];
            $itemNorm = rtrim($item['url'], '/');
            $isActive = $item['exact']
                ? $normCurrent === $itemNorm
                : ($normCurrent === $itemNorm || str_starts_with($normCurrent, $itemNorm.'/'));

            $links[] = [
                'key' => $item['key'],
                'label' => $item['label'],
                'url' => $item['url'],
                'icon' => $item['icon'],
                'active' => $isActive,
                'locked' => false,
            ];
        }

        return $links;
    }

    /**
     * @return array{key: string, label: string, url: string, icon: string}
     */
    public function resolveDashboardItem(): array
    {
        $url = (string) (Filament::getHomeUrl() ?: url('/admin'));
        try {
            if (class_exists(Dashboard::class)) {
                $url = (string) Dashboard::getUrl();
            }
        } catch (\Throwable) {
            // Fallback ke Filament::getHomeUrl()
        }

        return [
            'key' => self::DASHBOARD_KEY,
            'label' => __('Dashboard'),
            'url' => $url,
            'icon' => 'heroicon-o-squares-2x2',
        ];
    }

    /**
     * @param  array{key: string, label: string, group: string, class: class-string, icon: string, exact: bool, locked: bool}  $item
     * @return ?array{key: string, label: string, group: string, url: string, icon: string, exact: bool, locked: bool}
     */
    protected function resolveCatalogItem(array $item): ?array
    {
        $class = $item['class'];
        if (! class_exists($class)) {
            return null;
        }

        try {
            if (! $class::canAccess()) {
                return null;
            }

            $url = (string) $class::getUrl();
            if ($url === '') {
                return null;
            }

            return [
                'key' => $item['key'],
                'label' => $item['label'],
                'group' => $item['group'],
                'url' => $url,
                'icon' => $item['icon'],
                'exact' => $item['exact'],
                'locked' => $item['locked'],
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
