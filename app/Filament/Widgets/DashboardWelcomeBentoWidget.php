<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\EditProfile;
use App\Filament\Pages\MemberProfile;
use App\Models\CalendarNote;
use App\Models\CashierTransmittal;
use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\GrsRdtv;
use App\Models\InvoicingEmailLog;
use App\Models\InvoicingReceipt;
use App\Models\LocationReceiving;
use App\Models\MaterialIssue;
use App\Models\MonitoringChemical;
use App\Models\MonitoringKarung;
use App\Models\MonitoringNpk;
use App\Models\NonStockAgingControl;
use App\Models\PicMaterialAssignment;
use App\Models\PurchaseOrderIssued;
use App\Models\RequestingUnit;
use App\Models\Transmittal;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WarehouseDeliveryBatch;
use App\Models\WarehouseDestination;
use App\Models\WarehouseTransmittal;
use App\Models\WarehouseTransmittalItem;
use App\Services\BadgeMilestoneService;
use App\Services\LeftRailMenuService;
use App\Services\UserActivityLogService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\On;

class DashboardWelcomeBentoWidget extends Widget
{
    use InteractsWithPageFilters;

    protected string $view = 'filament.widgets.dashboard-welcome-bento-widget';

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 8,
    ];

    #[On('left-rail-shortcuts-updated')]
    public function refreshShortcuts(): void
    {
        // Memicu render ulang agar Pintasan Cepat & Ringkasan Operasional langsung sinkron.
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var ?User $user */
        $user = Auth::user();
        $tz = config('app.timezone', 'Asia/Makassar');
        $now = now()->timezone($tz);
        $hour = (int) $now->format('G');

        $greeting = match (true) {
            $hour >= 4 && $hour < 11 => 'Selamat Pagi',
            $hour >= 11 && $hour < 15 => 'Selamat Siang',
            $hour >= 15 && $hour < 19 => 'Selamat Sore',
            default => 'Selamat Malam',
        };

        $month = (int) ($this->filters['month'] ?? $now->month);
        $year = (int) ($this->filters['year'] ?? $now->year);
        $todayDate = $now->toDateString();

        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];
        $periodShort = ($monthNames[$month] ?? $monthNames[$now->month]).' '.$year;

        // 3 Pintasan Cepat sesuai hak akses & pilihan pribadi pengguna
        $shortcuts = [];
        try {
            $menuService = app(LeftRailMenuService::class);
            $catalog = $menuService->catalog();
            $railLinks = $menuService->resolveRailLinks($user, url()->current());
            $filtered = array_values(array_filter(
                $railLinks,
                static fn (array $item): bool => ($item['key'] ?? '') !== LeftRailMenuService::DASHBOARD_KEY,
            ));

            foreach (array_slice($filtered, 0, 3) as $item) {
                $key = (string) ($item['key'] ?? '');
                $item['group'] = (string) ($catalog[$key]['group'] ?? 'Pintasan');
                $shortcuts[] = $item;
            }
        } catch (\Throwable) {
            $shortcuts = [];
        }

        // Ringkasan Operasional mengikuti tepat 3 pintasan yang dipilih pengguna
        $operationalPulse = $this->buildOperationalPulseForShortcuts($shortcuts, $month, $year, $todayDate, $periodShort);

        // 3 Aktivitas terbaru milik pengguna untuk mengisi panel secara padat & informatif
        $recentActivities = [];
        if ($user) {
            try {
                $feed = app(UserActivityLogService::class)->getActivities($user, limit: 3);
                $recentActivities = $feed['items'] ?? [];
            } catch (\Throwable) {
                $recentActivities = [];
            }
        }

        $missionSummary = null;
        if ($user) {
            try {
                $progress = app(BadgeMilestoneService::class)->getProgress($user);
                $missionSummary = [
                    'has_full_access' => $progress['has_full_access'],
                    'has_pending_gift' => $progress['has_any_pending_gift'],
                    'mission_count' => $progress['mission_count'],
                    'unit_label' => $progress['unit_label'],
                ];
            } catch (\Throwable) {
                $missionSummary = null;
            }
        }

        $memberProfileUrl = null;
        $badgeTabUrl = null;
        try {
            if ($user) {
                $memberProfileUrl = MemberProfile::getUrl(['user' => $user->getKey()]);
            }
            $baseProfile = filament()->getProfileUrl() ?? EditProfile::getUrl();
            $badgeTabUrl = $baseProfile.(str_contains($baseProfile, '?') ? '&' : '?').'tab=gaya-badge';
        } catch (\Throwable) {
            $memberProfileUrl = null;
            $badgeTabUrl = null;
        }

        return [
            'user' => $user,
            'greeting' => $greeting,
            'shortcuts' => $shortcuts,
            'operationalPulse' => $operationalPulse,
            'recentActivities' => $recentActivities,
            'missionSummary' => $missionSummary,
            'memberProfileUrl' => $memberProfileUrl,
            'badgeTabUrl' => $badgeTabUrl,
        ];
    }

    /**
     * Membangun 3 kartu Ringkasan Operasional dinamis sesuai 3 pintasan pilihan pengguna.
     *
     * @param  list<array<string, mixed>>  $shortcuts
     * @return list<array{
     *     key: string,
     *     label: string,
     *     main_count: int,
     *     sub_label: string,
     *     sub_highlight: bool,
     *     unit: string,
     *     period_label: string,
     *     icon: string,
     *     accent: string,
     *     url: ?string
     * }>
     */
    protected function buildOperationalPulseForShortcuts(
        array $shortcuts,
        int $month,
        int $year,
        string $todayDate,
        string $periodShort,
    ): array {
        $accents = ['blue', 'emerald', 'orange'];

        // Bila pengguna belum punya pintasan, gunakan 3 modul inti default
        if ($shortcuts === []) {
            $shortcuts = [
                ['key' => 'delivery_order_receipts', 'label' => 'Penerimaan DO', 'icon' => 'heroicon-o-truck', 'url' => null],
                ['key' => 'grs_rdtvs', 'label' => 'GRS & RDTV', 'icon' => 'heroicon-o-document-check', 'url' => null],
                ['key' => 'invoicing_receipts', 'label' => 'Invoice Barang', 'icon' => 'heroicon-o-receipt-percent', 'url' => null],
            ];
        }

        $cards = [];
        foreach (array_values($shortcuts) as $idx => $shortcut) {
            $key = (string) ($shortcut['key'] ?? '');
            $label = (string) ($shortcut['label'] ?? 'Modul');
            $icon = (string) ($shortcut['icon'] ?? 'heroicon-o-squares-2x2');
            $url = isset($shortcut['url']) && is_string($shortcut['url']) ? $shortcut['url'] : null;

            $metric = $this->resolveMetricForShortcut($key, $month, $year, $todayDate, $periodShort);

            $cards[] = [
                'key' => $key,
                'label' => $label,
                'main_count' => $metric['main_count'],
                'sub_label' => $metric['sub_label'],
                'sub_highlight' => $metric['sub_highlight'],
                'unit' => $metric['unit'],
                'period_label' => $metric['period_label'],
                'icon' => $icon,
                'accent' => $accents[$idx % count($accents)],
                'url' => $url,
            ];
        }

        return $cards;
    }

    /**
     * Menghitung angka operasional nyata untuk masing-masing kunci pintasan katalog.
     *
     * @return array{main_count: int, sub_label: string, sub_highlight: bool, unit: string, period_label: string}
     */
    protected function resolveMetricForShortcut(
        string $key,
        int $month,
        int $year,
        string $todayDate,
        string $periodShort,
    ): array {
        return match ($key) {
            'delivery_order_receipts', 'laporan_bulanan', 'laporan_material_diambil' => $this->periodMetric(
                table: 'delivery_order_receipts',
                model: DeliveryOrderReceipt::class,
                dateColumn: 'received_date',
                unit: 'DO',
                month: $month,
                year: $year,
                todayDate: $todayDate,
                periodShort: $periodShort,
            ),
            'delivery_order_receipt_details', 'monitoring_material' => $this->periodMetric(
                table: 'delivery_order_receipt_details',
                model: DeliveryOrderReceiptDetail::class,
                dateColumn: 'created_at',
                unit: 'Item DO',
                month: $month,
                year: $year,
                todayDate: $todayDate,
                periodShort: $periodShort,
            ),
            'monitoring_chemical' => $this->periodMetric(
                table: 'monitoring_chemicals',
                model: MonitoringChemical::class,
                dateColumn: 'created_at',
                unit: 'Data',
                month: $month,
                year: $year,
                todayDate: $todayDate,
                periodShort: $periodShort,
            ),
            'monitoring_npk' => $this->periodMetric(
                table: 'monitoring_npks',
                model: MonitoringNpk::class,
                dateColumn: 'created_at',
                unit: 'Data NPK',
                month: $month,
                year: $year,
                todayDate: $todayDate,
                periodShort: $periodShort,
            ),
            'grs_rdtvs', 'combined_grs_upload' => $this->periodMetric(
                table: 'grs_rdtvs',
                model: GrsRdtv::class,
                dateColumn: 'transaction_date',
                unit: 'Dokumen',
                month: $month,
                year: $year,
                todayDate: $todayDate,
                periodShort: $periodShort,
            ),
            'transmittals' => $this->periodMetric(
                table: 'transmittals',
                model: Transmittal::class,
                dateColumn: 'created_at',
                unit: 'Pengajuan',
                month: $month,
                year: $year,
                todayDate: $todayDate,
                periodShort: $periodShort,
            ),
            'invoicing_receipts' => $this->periodMetric(
                table: 'invoicing_receipts',
                model: InvoicingReceipt::class,
                dateColumn: 'created_at',
                unit: 'Invoice',
                month: $month,
                year: $year,
                todayDate: $todayDate,
                periodShort: $periodShort,
            ),
            'cashier_transmittals' => $this->periodMetric(
                table: 'cashier_transmittals',
                model: CashierTransmittal::class,
                dateColumn: 'created_at',
                unit: 'Transmittal',
                month: $month,
                year: $year,
                todayDate: $todayDate,
                periodShort: $periodShort,
            ),
            'monitoring_karung' => $this->periodMetric(
                table: 'monitoring_karungs',
                model: MonitoringKarung::class,
                dateColumn: 'created_at',
                unit: 'Data',
                month: $month,
                year: $year,
                todayDate: $todayDate,
                periodShort: $periodShort,
            ),
            'email_grs_settings' => $this->periodMetric(
                table: 'invoicing_email_logs',
                model: InvoicingEmailLog::class,
                dateColumn: 'created_at',
                unit: 'Email',
                month: $month,
                year: $year,
                todayDate: $todayDate,
                periodShort: $periodShort,
            ),
            'scan_warehouse_transmittal', 'warehouse_transmittals' => $this->periodMetric(
                table: 'warehouse_transmittals',
                model: WarehouseTransmittal::class,
                dateColumn: 'tanggal',
                unit: 'Transmittal',
                month: $month,
                year: $year,
                todayDate: $todayDate,
                periodShort: $periodShort,
            ),
            'warehouse_delivery_batches' => $this->periodMetric(
                table: 'warehouse_delivery_batches',
                model: WarehouseDeliveryBatch::class,
                dateColumn: 'created_at',
                unit: 'Batch MB51',
                month: $month,
                year: $year,
                todayDate: $todayDate,
                periodShort: $periodShort,
            ),
            'antrean_pengiriman' => $this->periodMetric(
                table: 'warehouse_transmittal_items',
                model: WarehouseTransmittalItem::class,
                dateColumn: 'created_at',
                unit: 'Item Kirim',
                month: $month,
                year: $year,
                todayDate: $todayDate,
                periodShort: $periodShort,
            ),
            'material_issues' => $this->periodMetric(
                table: 'material_issues',
                model: MaterialIssue::class,
                dateColumn: 'created_at',
                unit: 'MIR',
                month: $month,
                year: $year,
                todayDate: $todayDate,
                periodShort: $periodShort,
            ),
            'non_stock_aging' => $this->nonStockAgingMetric(),
            'purchase_order_issueds' => $this->periodMetric(
                table: 'purchase_order_issueds',
                model: PurchaseOrderIssued::class,
                dateColumn: 'created_at',
                unit: 'Item PO',
                month: $month,
                year: $year,
                todayDate: $todayDate,
                periodShort: $periodShort,
            ),
            'calendar_notes' => $this->calendarNotesMetric($month, $year, $todayDate, $periodShort),
            'users' => $this->usersMetric(),
            'vendors' => $this->totalMasterMetric('vendors', Vendor::class, 'Vendor'),
            'warehouse_destinations' => $this->totalMasterMetric('warehouse_destinations', WarehouseDestination::class, 'Gudang'),
            'location_receivings' => $this->totalMasterMetric('location_receivings', LocationReceiving::class, 'Lokasi'),
            'requesting_units' => $this->totalMasterMetric('requesting_units', RequestingUnit::class, 'Unit'),
            'pic_material_assignments' => $this->totalMasterMetric('pic_material_assignments', PicMaterialAssignment::class, 'Aturan PIC'),
            default => [
                'main_count' => 0,
                'sub_label' => 'Akses cepat',
                'sub_highlight' => false,
                'unit' => 'Data',
                'period_label' => $periodShort,
            ],
        };
    }

    /**
     * @param  class-string<Model>  $model
     * @return array{main_count: int, sub_label: string, sub_highlight: bool, unit: string, period_label: string}
     */
    protected function periodMetric(
        string $table,
        string $model,
        string $dateColumn,
        string $unit,
        int $month,
        int $year,
        string $todayDate,
        string $periodShort,
    ): array {
        $monthCount = 0;
        $todayCount = 0;

        try {
            if (Schema::hasTable($table)) {
                $monthCount = $model::query()
                    ->whereMonth($dateColumn, $month)
                    ->whereYear($dateColumn, $year)
                    ->count();
                $todayCount = $model::query()
                    ->whereDate($dateColumn, $todayDate)
                    ->count();
            }
        } catch (\Throwable) {
            $monthCount = 0;
            $todayCount = 0;
        }

        return [
            'main_count' => $monthCount,
            'sub_label' => 'Hari ini: '.number_format($todayCount, 0, ',', '.'),
            'sub_highlight' => $todayCount > 0,
            'unit' => $unit,
            'period_label' => $periodShort,
        ];
    }

    /**
     * @param  class-string<Model>  $model
     * @return array{main_count: int, sub_label: string, sub_highlight: bool, unit: string, period_label: string}
     */
    protected function totalMasterMetric(string $table, string $model, string $unit): array
    {
        $total = 0;
        try {
            if (Schema::hasTable($table)) {
                $total = $model::query()->count();
            }
        } catch (\Throwable) {
            $total = 0;
        }

        return [
            'main_count' => $total,
            'sub_label' => 'Data Master',
            'sub_highlight' => false,
            'unit' => $unit,
            'period_label' => 'Total',
        ];
    }

    /**
     * @return array{main_count: int, sub_label: string, sub_highlight: bool, unit: string, period_label: string}
     */
    protected function usersMetric(): array
    {
        $activeCount = 0;
        $totalCount = 0;

        try {
            if (Schema::hasTable('users')) {
                $totalCount = User::query()->count();
                $activeCount = User::query()->where('is_active', true)->count();
            }
        } catch (\Throwable) {
            $activeCount = 0;
            $totalCount = 0;
        }

        return [
            'main_count' => $activeCount,
            'sub_label' => 'Total: '.number_format($totalCount, 0, ',', '.'),
            'sub_highlight' => $activeCount > 0,
            'unit' => 'Aktif',
            'period_label' => 'Pengguna',
        ];
    }

    /**
     * @return array{main_count: int, sub_label: string, sub_highlight: bool, unit: string, period_label: string}
     */
    protected function calendarNotesMetric(int $month, int $year, string $todayDate, string $periodShort): array
    {
        $monthCount = 0;
        $activeToday = 0;

        try {
            if (Schema::hasTable('calendar_notes')) {
                $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
                $endOfMonth = now()->setDate($year, $month, 1)->endOfMonth()->toDateString();

                $monthCount = CalendarNote::query()
                    ->whereDate('start_date', '<=', $endOfMonth)
                    ->whereDate('end_date', '>=', $startOfMonth)
                    ->count();

                $activeToday = CalendarNote::query()
                    ->whereDate('start_date', '<=', $todayDate)
                    ->whereDate('end_date', '>=', $todayDate)
                    ->count();
            }
        } catch (\Throwable) {
            $monthCount = 0;
            $activeToday = 0;
        }

        return [
            'main_count' => $monthCount,
            'sub_label' => 'Aktif hari ini: '.number_format($activeToday, 0, ',', '.'),
            'sub_highlight' => $activeToday > 0,
            'unit' => 'Agenda',
            'period_label' => $periodShort,
        ];
    }

    /**
     * @return array{main_count: int, sub_label: string, sub_highlight: bool, unit: string, period_label: string}
     */
    protected function nonStockAgingMetric(): array
    {
        $outstanding = 0;
        try {
            if (Schema::hasTable('nonstock_aging_controls')) {
                $outstanding = NonStockAgingControl::query()->count();
            }
        } catch (\Throwable) {
            $outstanding = 0;
        }

        return [
            'main_count' => $outstanding,
            'sub_label' => 'Terpantau',
            'sub_highlight' => $outstanding > 0,
            'unit' => 'Item',
            'period_label' => 'Aging',
        ];
    }
}
