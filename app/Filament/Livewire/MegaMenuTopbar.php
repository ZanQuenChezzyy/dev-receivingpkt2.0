<?php

namespace App\Filament\Livewire;

use App\Filament\Pages\AntreanPengirimanGudang;
use App\Filament\Pages\ScanWarehouseTransmittal;
use App\Filament\Resources\WarehouseDeliveryBatches\WarehouseDeliveryBatchResource;
use App\Filament\Resources\WarehouseTransmittals\WarehouseTransmittalResource;
use Filament\Facades\Filament;
use Filament\Livewire\Topbar;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Contracts\View\View;

class MegaMenuTopbar extends Topbar
{
    public function render(): View
    {
        return view('filament.livewire.mega-menu-topbar', [
            'megaMenu' => $this->getMegaMenuStructure(),
        ]);
    }

    /**
     * Membangun struktur 6 kategori Mega Menu terstruktur dari navigasi aktif Filament
     * sambil mempertahankan penuh hak akses role, badge dinamis, status aktif, dan terjemahan.
     *
     * @return list<array{
     *     key: string,
     *     label: string,
     *     type: 'link'|'mega',
     *     url?: string,
     *     open_in_new_tab?: bool,
     *     is_active: bool,
     *     icon: mixed,
     *     columns?: list<array{
     *         title: string,
     *         accent: 'orange'|'blue',
     *         items: list<array{
     *             label: string,
     *             url: string,
     *             icon: mixed,
     *             badge: mixed,
     *             badge_color: mixed,
     *             is_active: bool,
     *             open_in_new_tab: bool
     *         }>
     *     }>
     * }>
     */
    public function getMegaMenuStructure(): array
    {
        $navigation = Filament::getNavigation();

        /** @var array<string, NavigationGroup> $groupsByNormalized */
        $groupsByNormalized = [];
        /** @var list<NavigationItem> $rootItems */
        $rootItems = [];

        foreach ($navigation as $group) {
            $label = trim((string) $group->getLabel());
            if ($label === '') {
                foreach ($group->getItems() as $item) {
                    $rootItems[] = $item;
                }

                continue;
            }

            $groupsByNormalized[strtolower($label)] = $group;
        }

        $consumedGroupKeys = [];

        $takeGroup = function (array $aliases) use ($groupsByNormalized, &$consumedGroupKeys): ?NavigationGroup {
            foreach ($aliases as $alias) {
                $candidates = [
                    strtolower(trim($alias)),
                    strtolower(trim((string) __($alias))),
                ];

                foreach ($candidates as $candidate) {
                    if ($candidate !== '' && isset($groupsByNormalized[$candidate])) {
                        $consumedGroupKeys[$candidate] = true;

                        return $groupsByNormalized[$candidate];
                    }
                }
            }

            return null;
        };

        $serializeItem = static function (NavigationItem $item): array {
            $isActive = $item->isActive();
            $badge = $item->getBadge();

            return [
                'label' => (string) $item->getLabel(),
                'url' => (string) $item->getUrl(),
                'icon' => $isActive ? ($item->getActiveIcon() ?? $item->getIcon()) : $item->getIcon(),
                'badge' => $badge,
                'badge_color' => $item->getBadgeColor($badge),
                'is_active' => $isActive,
                'open_in_new_tab' => $item->shouldOpenUrlInNewTab(),
            ];
        };

        $serializeGroupItems = function (?NavigationGroup $group, bool $expandWarehouseCluster = false) use ($serializeItem): array {
            if (! $group) {
                return [];
            }

            $items = [];
            foreach ($group->getItems() as $item) {
                $serialized = $serializeItem($item);

                // Ekspansi halaman Cluster Pengiriman Gudang langsung di Mega Menu Gudang agar 1-klik
                if ($expandWarehouseCluster && str_contains($serialized['url'], '/pengiriman-gudang')) {
                    $clusterSubItems = $this->getExpandedWarehouseClusterItems();
                    if ($clusterSubItems !== []) {
                        foreach ($clusterSubItems as $sub) {
                            $items[] = $sub;
                        }

                        continue;
                    }
                }

                $items[] = $serialized;
            }

            return $items;
        };

        $menu = [];

        // 1. Dashboard (Tautan langsung)
        $dashboardItem = null;
        foreach ($rootItems as $rootItem) {
            $url = (string) $rootItem->getUrl();
            if ($url === Filament::getHomeUrl() || str_ends_with(rtrim($url, '/'), '/admin')) {
                $dashboardItem = $rootItem;

                break;
            }
        }

        if ($dashboardItem) {
            $menu[] = [
                'key' => 'dashboard',
                'label' => __('Dashboard'),
                'type' => 'link',
                'url' => (string) $dashboardItem->getUrl(),
                'open_in_new_tab' => $dashboardItem->shouldOpenUrlInNewTab(),
                'is_active' => $dashboardItem->isActive(),
                'icon' => $dashboardItem->isActive()
                    ? ($dashboardItem->getActiveIcon() ?? $dashboardItem->getIcon())
                    : $dashboardItem->getIcon(),
            ];
        }

        // Definisi 5 domain Mega Menu terstruktur
        $domains = [
            [
                'key' => 'penerimaan',
                'label' => __('Penerimaan'),
                'icon' => 'heroicon-o-inbox-arrow-down',
                'columns' => [
                    [
                        'title' => __('Penerimaan Receiving'),
                        'accent' => 'orange',
                        'aliases' => ['Penerimaan Receiving'],
                        'expand_warehouse' => false,
                    ],
                    [
                        'title' => __('Transaksi QC & GRS'),
                        'accent' => 'blue',
                        'aliases' => ['Transaksi'],
                        'expand_warehouse' => false,
                    ],
                ],
            ],
            [
                'key' => 'invoice',
                'label' => __('Invoice'),
                'icon' => 'heroicon-o-document-currency-dollar',
                'columns' => [
                    [
                        'title' => __('Penerimaan Jasa / Invoice'),
                        'accent' => 'orange',
                        'aliases' => ['Penerimaan Jasa / Invoice'],
                        'expand_warehouse' => false,
                    ],
                ],
            ],
            [
                'key' => 'gudang',
                'label' => __('Gudang'),
                'icon' => 'heroicon-o-building-storefront',
                'columns' => [
                    [
                        'title' => __('Pengeluaran & Pengiriman'),
                        'accent' => 'orange',
                        'aliases' => ['Pengeluaran Material'],
                        'expand_warehouse' => true,
                    ],
                    [
                        'title' => __('Monitoring Receiving'),
                        'accent' => 'blue',
                        'aliases' => ['Monitoring Receiving'],
                        'expand_warehouse' => false,
                    ],
                ],
            ],
            [
                'key' => 'laporan',
                'label' => __('Laporan'),
                'icon' => 'heroicon-o-chart-bar-square',
                'columns' => [
                    [
                        'title' => __('Laporan'),
                        'accent' => 'blue',
                        'aliases' => ['Laporan'],
                        'expand_warehouse' => false,
                    ],
                ],
            ],
            [
                'key' => 'master',
                'label' => __('Master & Sistem'),
                'icon' => 'heroicon-o-squares-2x2',
                'columns' => [
                    [
                        'title' => __('Data Master'),
                        'accent' => 'blue',
                        'aliases' => ['Data Master'],
                        'expand_warehouse' => false,
                    ],
                    [
                        'title' => __('Kelola Pengguna'),
                        'accent' => 'orange',
                        'aliases' => ['Kelola Pengguna'],
                        'expand_warehouse' => false,
                    ],
                ],
            ],
        ];

        foreach ($domains as $domain) {
            $builtColumns = [];
            $domainIsActive = false;

            foreach ($domain['columns'] as $colDef) {
                $group = $takeGroup($colDef['aliases']);
                $items = $serializeGroupItems($group, $colDef['expand_warehouse']);

                if ($items === []) {
                    continue;
                }

                foreach ($items as $it) {
                    if ($it['is_active']) {
                        $domainIsActive = true;
                    }
                }

                $builtColumns[] = [
                    'title' => $colDef['title'],
                    'accent' => $colDef['accent'],
                    'items' => $items,
                ];
            }

            if ($builtColumns === []) {
                continue;
            }

            $menu[] = [
                'key' => $domain['key'],
                'label' => $domain['label'],
                'type' => 'mega',
                'is_active' => $domainIsActive,
                'icon' => $domain['icon'],
                'columns' => $builtColumns,
            ];
        }

        // Fallback aman: grup navigasi baru di masa depan yang belum masuk peta tetap ditampilkan di "Lainnya"
        $remainingColumns = [];
        $remainingActive = false;
        foreach ($groupsByNormalized as $normKey => $group) {
            if (isset($consumedGroupKeys[$normKey])) {
                continue;
            }

            $items = $serializeGroupItems($group);
            if ($items === []) {
                continue;
            }

            foreach ($items as $it) {
                if ($it['is_active']) {
                    $remainingActive = true;
                }
            }

            $remainingColumns[] = [
                'title' => (string) $group->getLabel(),
                'accent' => 'blue',
                'items' => $items,
            ];
        }

        if ($remainingColumns !== []) {
            $menu[] = [
                'key' => 'lainnya',
                'label' => __('Lainnya'),
                'type' => 'mega',
                'is_active' => $remainingActive,
                'icon' => 'heroicon-o-ellipsis-horizontal-circle',
                'columns' => $remainingColumns,
            ];
        }

        return $menu;
    }

    /**
     * Mengambil daftar sub-halaman aktif di dalam Cluster Pengiriman Gudang (sesuai izin user)
     * agar dapat diakses langsung 1-klik dari Mega Menu Gudang.
     *
     * @return list<array{
     *     label: string,
     *     url: string,
     *     icon: mixed,
     *     badge: mixed,
     *     badge_color: mixed,
     *     is_active: bool,
     *     open_in_new_tab: bool
     * }>
     */
    protected function getExpandedWarehouseClusterItems(): array
    {
        $entries = [
            [
                'class' => ScanWarehouseTransmittal::class,
                'label' => ScanWarehouseTransmittal::getNavigationLabel(),
                'icon' => 'heroicon-o-qr-code',
                'is_resource' => false,
            ],
            [
                'class' => WarehouseTransmittalResource::class,
                'label' => WarehouseTransmittalResource::getNavigationLabel(),
                'icon' => 'heroicon-o-document-text',
                'is_resource' => true,
            ],
            [
                'class' => WarehouseDeliveryBatchResource::class,
                'label' => WarehouseDeliveryBatchResource::getNavigationLabel(),
                'icon' => 'heroicon-o-table-cells',
                'is_resource' => true,
            ],
            [
                'class' => AntreanPengirimanGudang::class,
                'label' => AntreanPengirimanGudang::getNavigationLabel(),
                'icon' => 'heroicon-o-queue-list',
                'is_resource' => false,
            ],
        ];

        $currentUrl = rtrim(url()->current(), '/');
        $items = [];

        foreach ($entries as $entry) {
            $class = $entry['class'];
            if (! class_exists($class)) {
                continue;
            }

            try {
                $canAccess = $entry['is_resource']
                    ? $class::canAccess()
                    : $class::canAccess();

                if (! $canAccess) {
                    continue;
                }

                $url = (string) $class::getUrl();
                $normalizedUrl = rtrim($url, '/');
                $isActive = $currentUrl === $normalizedUrl || str_starts_with($currentUrl, $normalizedUrl.'/');

                $items[] = [
                    'label' => (string) $entry['label'],
                    'url' => $url,
                    'icon' => $entry['icon'],
                    'badge' => null,
                    'badge_color' => null,
                    'is_active' => $isActive,
                    'open_in_new_tab' => false,
                ];
            } catch (\Throwable) {
                continue;
            }
        }

        return $items;
    }
}
