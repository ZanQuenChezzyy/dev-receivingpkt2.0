<?php

namespace App\Filament\Resources\InvoicingReceipts\Widgets;

use App\Models\InvoicingReceipt;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InvoicingOverviewWidget extends Widget
{
    protected string $view = 'filament.resources.invoicing-receipts.widgets.invoicing-overview-widget';

    protected int|string|array $columnSpan = 'full';

    public string $selectedPeriod = '';

    public bool $showStaffModal = false;

    public string $staffSearch = '';

    public function mount(): void
    {
        $this->selectedPeriod = now()->format('Y-m');
    }

    public function openStaffModal(): void
    {
        $this->showStaffModal = true;
        $this->dispatch('open-modal', id: 'staff-workload-modal');
    }

    public function closeStaffModal(): void
    {
        $this->showStaffModal = false;
        $this->staffSearch = '';
        $this->dispatch('close-modal', id: 'staff-workload-modal');
    }

    /**
     * @return array<string, string>
     */
    public function getPeriodOptions(): array
    {
        $options = [];
        $current = now()->startOfMonth();
        $options[$current->format('Y-m')] = $this->formatMonthYear($current).' (Bulan Ini)';

        for ($i = 1; $i <= 11; $i++) {
            $prev = (clone $current)->subMonths($i);
            $options[$prev->format('Y-m')] = $this->formatMonthYear($prev);
        }

        return $options;
    }

    public function getPeriodLabel(): string
    {
        if (blank($this->selectedPeriod)) {
            return $this->formatMonthYear(now());
        }

        try {
            $date = Carbon::createFromFormat('Y-m', $this->selectedPeriod);

            return $date ? $this->formatMonthYear($date) : $this->selectedPeriod;
        } catch (\Throwable) {
            return $this->selectedPeriod;
        }
    }

    protected function formatMonthYear(Carbon $date): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $m = (int) $date->format('n');
        $monthName = $months[$m] ?? $date->format('F');

        return "{$monthName} {$date->format('Y')}";
    }

    protected function getPeriodQuery()
    {
        $period = $this->selectedPeriod ?: now()->format('Y-m');
        $parts = explode('-', $period);
        $year = (int) ($parts[0] ?? now()->year);
        $month = (int) ($parts[1] ?? now()->month);

        return InvoicingReceipt::query()->where(function ($q) use ($year, $month) {
            $q->where(function ($sub) use ($year, $month) {
                $sub->whereYear('tanggal_terima_grs', $year)
                    ->whereMonth('tanggal_terima_grs', $month);
            })->orWhere(function ($sub) use ($year, $month) {
                $sub->whereNull('tanggal_terima_grs')
                    ->whereYear('tanggal_terima_koreksi_invoice', $year)
                    ->whereMonth('tanggal_terima_koreksi_invoice', $month);
            })->orWhere(function ($sub) use ($year, $month) {
                $sub->whereNull('tanggal_terima_grs')
                    ->whereNull('tanggal_terima_koreksi_invoice')
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month);
            });
        });
    }

    /**
     * @return array{
     *     top_pic_name: string,
     *     top_pic_count: int,
     *     total_masuk: int,
     *     total_grs: int,
     *     total_rdtv: int,
     *     total_nominal: float,
     *     total_selesai: int,
     *     total_proses: int,
     *     completion_rate: int
     * }
     */
    public function getStatsData(): array
    {
        $baseQuery = $this->getPeriodQuery();

        $totalMasuk = (clone $baseQuery)->count();
        $totalGrs = (clone $baseQuery)->where('jenis', 'GRS')->count();
        $totalRdtv = (clone $baseQuery)->where('jenis', 'RDTV')->count();
        $totalNominal = (clone $baseQuery)->sum('subtotal_include_ppn');

        $totalSelesai = (clone $baseQuery)->whereIn('status', ['Terkirim Kasir', 'Selesai'])->count();
        $totalProses = (clone $baseQuery)->whereIn('status', ['Menunggu Verifikasi', 'Terverifikasi'])->count();
        $completionRate = $totalMasuk > 0 ? (int) round(($totalSelesai / $totalMasuk) * 100) : 0;

        // Top PIC
        $topPicRecord = (clone $baseQuery)
            ->whereNotNull('pic_id')
            ->select('pic_id', DB::raw('count(*) as total'))
            ->groupBy('pic_id')
            ->orderByDesc('total')
            ->first();

        $topPicName = 'Belum Ada';
        $topPicCount = 0;
        if ($topPicRecord && $topPicRecord->pic_id) {
            $user = User::find($topPicRecord->pic_id);
            $topPicName = $user?->name ?? "Staff #{$topPicRecord->pic_id}";
            $topPicCount = (int) $topPicRecord->total;
        }

        return [
            'top_pic_name' => $topPicName,
            'top_pic_count' => $topPicCount,
            'total_masuk' => $totalMasuk,
            'total_grs' => $totalGrs,
            'total_rdtv' => $totalRdtv,
            'total_nominal' => (float) $totalNominal,
            'total_selesai' => $totalSelesai,
            'total_proses' => $totalProses,
            'completion_rate' => $completionRate,
        ];
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     email: ?string,
     *     total: int,
     *     selesai: int,
     *     proses: int,
     *     rate: int
     * }>
     */
    public function getStaffWorkloads(): array
    {
        $baseQuery = $this->getPeriodQuery();

        $staffUsers = collect();
        if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
            $staffUsers = User::where('is_active', true)
                ->whereHas('roles', fn ($q) => $q->where('name', 'Staff Invoice'))
                ->get();
        }

        $periodPicIds = (clone $baseQuery)->whereNotNull('pic_id')->pluck('pic_id')->unique();
        $additionalUsers = User::whereIn('id', $periodPicIds)->whereNotIn('id', $staffUsers->pluck('id'))->get();
        $allStaff = $staffUsers->concat($additionalUsers);

        $workloads = [];
        $search = strtolower(trim($this->staffSearch));

        foreach ($allStaff as $staff) {
            if ($search !== '') {
                $nameMatch = str_contains(strtolower($staff->name ?? ''), $search);
                $emailMatch = str_contains(strtolower($staff->email ?? ''), $search);
                if (! $nameMatch && ! $emailMatch) {
                    continue;
                }
            }

            $userQuery = (clone $baseQuery)->where('pic_id', $staff->id);
            $total = (clone $userQuery)->count();
            $selesai = (clone $userQuery)->whereIn('status', ['Terkirim Kasir', 'Selesai'])->count();
            $proses = (clone $userQuery)->whereIn('status', ['Menunggu Verifikasi', 'Terverifikasi'])->count();
            $rate = $total > 0 ? (int) round(($selesai / $total) * 100) : 0;

            $workloads[] = [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'total' => $total,
                'selesai' => $selesai,
                'proses' => $proses,
                'rate' => $rate,
            ];
        }

        usort($workloads, function ($a, $b) {
            if ($a['total'] !== $b['total']) {
                return $b['total'] <=> $a['total'];
            }

            return strcmp($a['name'], $b['name']);
        });

        return $workloads;
    }
}
