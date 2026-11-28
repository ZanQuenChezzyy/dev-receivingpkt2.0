<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class DashboardCalendarClockWidget extends Widget
{
    use InteractsWithPageFilters;

    protected string $view = 'filament.widgets.dashboard-calendar-clock-widget';

    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 4,
    ];

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $tz = config('app.timezone', 'Asia/Makassar');
        $now = now()->timezone($tz);

        $dayNames = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $filterMonth = (int) ($this->filters['month'] ?? $now->month);
        $filterYear = (int) ($this->filters['year'] ?? $now->year);
        $filterPeriodLabel = ($monthNames[$filterMonth] ?? $monthNames[$now->month]).' '.$filterYear;

        return [
            'timezone' => $tz,
            'initialHourMinute' => $now->format('H:i'),
            'initialSecond' => $now->format('s'),
            'initialFullDate' => ($dayNames[(int) $now->dayOfWeek] ?? 'Hari Ini').', '.$now->day.' '.($monthNames[(int) $now->month] ?? '').' '.$now->year,
            'initialYear' => (int) $now->year,
            'initialMonth' => (int) $now->month,
            'initialDay' => (int) $now->day,
            'filterPeriodLabel' => $filterPeriodLabel,
        ];
    }
}
