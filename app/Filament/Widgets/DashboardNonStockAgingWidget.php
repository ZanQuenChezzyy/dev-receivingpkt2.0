<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\NonStockAgingMonitoring;
use App\Services\NonStockAgingService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class DashboardNonStockAgingWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 2,
        'xl' => 4,
    ];

    protected function getHeading(): ?string
    {
        return 'Aging Material Non-Stock';
    }

    protected function getDescription(): ?string
    {
        return null;
    }

    protected function getStats(): array
    {
        if (! Schema::hasTable('nonstock_aging_controls')) {
            return [];
        }

        $summary = app(NonStockAgingService::class)->buildSummaryMetrics('NONSTOCK');

        $baselineDesc = $this->formatRupiahShort($summary['outstanding_value']).' • '.$summary['outstanding_po'].' PO';
        if ($summary['baseline_comparison'] !== null) {
            $cmp = $summary['baseline_comparison'];
            $sign = $cmp['item_reduction_pct'] >= 0 ? 'Turun ' : 'Naik ';
            $baselineDesc .= ' • '.$sign.abs($cmp['item_reduction_pct']).'%';
        }

        $critical = $summary['zones']['Critical'];
        $warning = $summary['zones']['Warning'];

        return [
            Stat::make('Non-Stock Belum Diambil', number_format($summary['outstanding_items'], 0, ',', '.').' item')
                ->description($baselineDesc)
                ->descriptionIcon('heroicon-m-cube')
                ->color($summary['outstanding_items'] > 0 ? 'primary' : 'success')
                ->url(NonStockAgingMonitoring::getUrl(['activeTab' => 'belum_diambil'])),

            Stat::make('Critical (> 60 Hari)', number_format($critical['items'], 0, ',', '.').' item')
                ->description($this->formatRupiahShort($critical['value']).' • '.$critical['po_count'].' PO')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($critical['items'] > 0 ? 'danger' : 'success')
                ->url(NonStockAgingMonitoring::getUrl(['activeTab' => 'critical'])),

            Stat::make('Warning (31–60 Hari)', number_format($warning['items'], 0, ',', '.').' item')
                ->description($this->formatRupiahShort($warning['value']).' • '.$warning['po_count'].' PO')
                ->descriptionIcon('heroicon-m-clock')
                ->color($warning['items'] > 0 ? 'warning' : 'success')
                ->url(NonStockAgingMonitoring::getUrl(['activeTab' => 'warning'])),

            Stat::make('On Check', number_format($summary['on_check_items'], 0, ',', '.').' item')
                ->description($this->formatRupiahShort($summary['on_check_value']).' • '.$summary['on_check_po'].' PO')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($summary['on_check_items'] > 0 ? 'info' : 'gray')
                ->url(NonStockAgingMonitoring::getUrl(['activeTab' => 'on_check'])),
        ];
    }

    protected function formatRupiahShort(float $amount): string
    {
        if ($amount >= 1_000_000_000) {
            return 'Rp '.number_format($amount / 1_000_000_000, 2, ',', '.').' M';
        }

        if ($amount >= 1_000_000) {
            return 'Rp '.number_format($amount / 1_000_000, 2, ',', '.').' Jt';
        }

        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
