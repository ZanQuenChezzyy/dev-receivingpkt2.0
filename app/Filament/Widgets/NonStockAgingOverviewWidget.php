<?php

namespace App\Filament\Widgets;

use App\Services\NonStockAgingService;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class NonStockAgingOverviewWidget extends Widget
{
    protected string $view = 'filament.widgets.non-stock-aging-overview-widget';

    protected int|string|array $columnSpan = 'full';

    public string $mrpScope = 'NONSTOCK';

    public bool $pilotOnly = false;

    public function placeholder(): View
    {
        return view('filament.widgets.non-stock-aging-overview-skeleton');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $summary = app(NonStockAgingService::class)->buildSummaryMetrics(
            mrpScope: $this->mrpScope,
            pilotOnly: $this->pilotOnly,
        );

        return [
            'summary' => $summary,
        ];
    }

    public static function formatRupiah(float $amount): string
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
