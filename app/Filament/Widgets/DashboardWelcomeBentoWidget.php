<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\EditProfile;
use App\Filament\Resources\DeliveryOrderReceipts\DeliveryOrderReceiptResource;
use App\Filament\Resources\GrsRdtvs\GrsRdtvResource;
use App\Filament\Resources\InvoicingReceipts\InvoicingReceiptResource;
use App\Models\DeliveryOrderReceipt;
use App\Models\GrsRdtv;
use App\Models\InvoicingReceipt;
use App\Models\User;
use App\Services\BadgeMilestoneService;
use App\Services\LeftRailMenuService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DashboardWelcomeBentoWidget extends Widget
{
    use InteractsWithPageFilters;

    protected string $view = 'filament.widgets.dashboard-welcome-bento-widget';

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 8,
    ];

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

        // Ringkasan Operasional (DO, GRS/RDTV, Invoice) untuk periode aktif & hari ini
        $operationalPulse = $this->buildOperationalPulse($month, $year, $todayDate, $periodShort);

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

        $badgeTabUrl = null;
        try {
            $baseProfile = filament()->getProfileUrl() ?? EditProfile::getUrl();
            $badgeTabUrl = $baseProfile.(str_contains($baseProfile, '?') ? '&' : '?').'tab=gaya-badge';
        } catch (\Throwable) {
            $badgeTabUrl = null;
        }

        return [
            'user' => $user,
            'greeting' => $greeting,
            'shortcuts' => $shortcuts,
            'operationalPulse' => $operationalPulse,
            'missionSummary' => $missionSummary,
            'badgeTabUrl' => $badgeTabUrl,
        ];
    }

    /**
     * @return list<array{
     *     label: string,
     *     month_count: int,
     *     today_count: int,
     *     unit: string,
     *     period_label: string,
     *     icon: string,
     *     accent: string,
     *     url: ?string
     * }>
     */
    protected function buildOperationalPulse(int $month, int $year, string $todayDate, string $periodShort): array
    {
        $doMonth = 0;
        $doToday = 0;
        try {
            if (Schema::hasTable('delivery_order_receipts')) {
                $doMonth = DeliveryOrderReceipt::query()
                    ->whereMonth('received_date', $month)
                    ->whereYear('received_date', $year)
                    ->count();
                $doToday = DeliveryOrderReceipt::query()
                    ->whereDate('received_date', $todayDate)
                    ->count();
            }
        } catch (\Throwable) {
            $doMonth = 0;
            $doToday = 0;
        }

        $grsMonth = 0;
        $grsToday = 0;
        try {
            if (Schema::hasTable('grs_rdtvs')) {
                $grsMonth = GrsRdtv::query()
                    ->whereMonth('transaction_date', $month)
                    ->whereYear('transaction_date', $year)
                    ->count();
                $grsToday = GrsRdtv::query()
                    ->whereDate('transaction_date', $todayDate)
                    ->count();
            }
        } catch (\Throwable) {
            $grsMonth = 0;
            $grsToday = 0;
        }

        $invMonth = 0;
        $invToday = 0;
        try {
            if (Schema::hasTable('invoicing_receipts')) {
                $invMonth = InvoicingReceipt::query()
                    ->whereMonth('created_at', $month)
                    ->whereYear('created_at', $year)
                    ->count();
                $invToday = InvoicingReceipt::query()
                    ->whereDate('created_at', $todayDate)
                    ->count();
            }
        } catch (\Throwable) {
            $invMonth = 0;
            $invToday = 0;
        }

        return [
            [
                'label' => 'Penerimaan DO',
                'month_count' => $doMonth,
                'today_count' => $doToday,
                'unit' => 'DO',
                'period_label' => $periodShort,
                'icon' => 'heroicon-o-truck',
                'accent' => 'blue',
                'url' => $this->resolveResourceUrl(DeliveryOrderReceiptResource::class),
            ],
            [
                'label' => 'GRS & RDTV',
                'month_count' => $grsMonth,
                'today_count' => $grsToday,
                'unit' => 'Dokumen',
                'period_label' => $periodShort,
                'icon' => 'heroicon-o-document-check',
                'accent' => 'emerald',
                'url' => $this->resolveResourceUrl(GrsRdtvResource::class),
            ],
            [
                'label' => 'Invoice Barang',
                'month_count' => $invMonth,
                'today_count' => $invToday,
                'unit' => 'Invoice',
                'period_label' => $periodShort,
                'icon' => 'heroicon-o-receipt-percent',
                'accent' => 'orange',
                'url' => $this->resolveResourceUrl(InvoicingReceiptResource::class),
            ],
        ];
    }

    /**
     * @param  class-string  $resourceClass
     */
    protected function resolveResourceUrl(string $resourceClass): ?string
    {
        try {
            if (class_exists($resourceClass) && $resourceClass::canAccess()) {
                return (string) $resourceClass::getUrl();
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
