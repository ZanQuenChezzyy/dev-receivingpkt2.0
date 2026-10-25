<?php

namespace App\Filament\Widgets;

use App\Models\DeliveryOrderReceiptDetail;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class MaterialItemOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 1,
        'md' => 2,
        'xl' => 2,
        '2xl' => 4,
    ];

    protected function getStats(): array
    {
        $month = $this->filters['month'] ?? now()->month;
        $year = $this->filters['year'] ?? now()->year;

        $mrpTypes = ['V1', 'PD', 'NONSTOCK', 'INVESTASI'];
        $stats = [];

        $uiConfig = [
            'V1' => ['color' => 'primary'],
            'PD' => ['color' => 'success'],
            'NONSTOCK' => ['color' => 'warning'],
            'INVESTASI' => ['color' => 'danger'],
        ];

        foreach ($mrpTypes as $mrpType) {
            // 1. GRS Selesai Bulan Ini (Untuk Total Utama)
            $baseGrsQuery = DeliveryOrderReceiptDetail::query()
                ->join('purchase_order_issueds', 'delivery_order_receipt_details.purchase_order_issued_id', '=', 'purchase_order_issueds.id')
                ->where('purchase_order_issueds.mrp_type', $mrpType)
                ->whereHas('deliveryOrderReceipt.grsRdtvItems.grsRdtv', function ($query) use ($month, $year) {
                    $query->whereMonth('transaction_date', $month)
                        ->whereYear('transaction_date', $year)
                        ->where('category', 'GRS');
                });

            $totalGrsBulanIni = (clone $baseGrsQuery)->count(DB::raw('DISTINCT delivery_order_receipt_details.purchase_order_issued_id'));

            // 2. Kedatangan Murni (Hanya melihat fisik tiba bulan ini, GRS atau belum tidak peduli)
            $kedatanganMurni = DeliveryOrderReceiptDetail::query()
                ->join('purchase_order_issueds', 'delivery_order_receipt_details.purchase_order_issued_id', '=', 'purchase_order_issueds.id')
                ->where('purchase_order_issueds.mrp_type', $mrpType)
                ->whereHas('deliveryOrderReceipt', function ($query) use ($month, $year) {
                    $query->whereMonth('received_date', $month)
                        ->whereYear('received_date', $year);
                })
                ->count(DB::raw('DISTINCT delivery_order_receipt_details.purchase_order_issued_id'));

            // 3. Datang & Selesai GRS (Datang bulan ini DAN selesai di-GRS bulan ini)
            $kedatanganDanGrs = DeliveryOrderReceiptDetail::query()
                ->join('purchase_order_issueds', 'delivery_order_receipt_details.purchase_order_issued_id', '=', 'purchase_order_issueds.id')
                ->where('purchase_order_issueds.mrp_type', $mrpType)
                ->whereHas('deliveryOrderReceipt', function ($query) use ($month, $year) {
                    $query->whereMonth('received_date', $month)
                        ->whereYear('received_date', $year);
                })
                ->whereHas('deliveryOrderReceipt.grsRdtvItems.grsRdtv', function ($query) use ($month, $year) {
                    $query->whereMonth('transaction_date', $month)
                        ->whereYear('transaction_date', $year)
                        ->where('category', 'GRS');
                })
                ->count(DB::raw('DISTINCT delivery_order_receipt_details.purchase_order_issued_id'));

            // 4. Belum GRS (Total barang tiba di bulan terkait yang belum di-GRS)
            $belumGrs = DeliveryOrderReceiptDetail::query()
                ->join('purchase_order_issueds', 'delivery_order_receipt_details.purchase_order_issued_id', '=', 'purchase_order_issueds.id')
                ->where('purchase_order_issueds.mrp_type', $mrpType)
                ->whereHas('deliveryOrderReceipt', function ($query) use ($month, $year) {
                    $query->whereMonth('received_date', $month)
                        ->whereYear('received_date', $year);
                })
                ->whereDoesntHave('deliveryOrderReceipt.grsRdtvItems.grsRdtv', function ($query) {
                    $query->where('category', 'GRS');
                })
                ->count(DB::raw('DISTINCT delivery_order_receipt_details.purchase_order_issued_id'));

            // 4.5. RDTV (Barang tiba di bulan terkait yang di-RDTV)
            $rdtv = DeliveryOrderReceiptDetail::query()
                ->join('purchase_order_issueds', 'delivery_order_receipt_details.purchase_order_issued_id', '=', 'purchase_order_issueds.id')
                ->where('purchase_order_issueds.mrp_type', $mrpType)
                ->whereHas('deliveryOrderReceipt', function ($query) use ($month, $year) {
                    $query->whereMonth('received_date', $month)
                        ->whereYear('received_date', $year);
                })
                ->whereHas('deliveryOrderReceipt.grsRdtvItems.grsRdtv', function ($query) {
                    $query->where('category', 'RDTV');
                })
                ->count(DB::raw('DISTINCT delivery_order_receipt_details.purchase_order_issued_id'));

            // 5. Hitung total berdasarkan masing-masing ABC Indicator dari Kedatangan Bulan Ini (Semua Barang Tiba)
            $abcIndicators = DeliveryOrderReceiptDetail::query()
                ->join('purchase_order_issueds', 'delivery_order_receipt_details.purchase_order_issued_id', '=', 'purchase_order_issueds.id')
                ->where('purchase_order_issueds.mrp_type', $mrpType)
                ->whereHas('deliveryOrderReceipt', function ($query) use ($month, $year) {
                    $query->whereMonth('received_date', $month)
                        ->whereYear('received_date', $year);
                })
                ->select('purchase_order_issueds.abc_indicator', DB::raw('COUNT(DISTINCT delivery_order_receipt_details.purchase_order_issued_id) as total'))
                ->whereNotNull('purchase_order_issueds.abc_indicator')
                ->groupBy('purchase_order_issueds.abc_indicator')
                ->orderByDesc('total')
                ->get();

            // Susun tampilan deskripsi yang rapi dan tidak akan pecah/wrap ke baris baru
            $descriptionHtml = '<div class="w-full block text-xs mt-3 space-y-1.5">';

            // Baris: Kedatangan Murni (Warna Biru Netral)
            $descriptionHtml .= '<div class="flex items-center justify-between gap-2 py-0.5 border-b border-gray-100/80 dark:border-gray-800/80 text-blue-600 dark:text-blue-400 font-medium">';
            $descriptionHtml .= '  <span class="flex items-center gap-1.5 shrink-0 text-xs">';
            $descriptionHtml .= '    <svg class="w-3.5 h-3.5 shrink-0 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>';
            $descriptionHtml .= '    <span>Kedatangan</span>';
            $descriptionHtml .= '  </span>';
            $descriptionHtml .= '  <span class="font-mono text-xs font-semibold tabular-nums text-right shrink-0 tracking-tight whitespace-nowrap">' . number_format($kedatanganMurni, 0, ',', '.') . ' Item</span>';
            $descriptionHtml .= '</div>';

            // Baris: Datang & Selesai GRS (Warna Hijau)
            $descriptionHtml .= '<div class="flex items-center justify-between gap-2 py-0.5 border-b border-gray-100/80 dark:border-gray-800/80 text-success-600 dark:text-success-400 font-medium">';
            $descriptionHtml .= '  <span class="flex items-center gap-1.5 shrink-0 text-xs">';
            $descriptionHtml .= '    <svg class="w-3.5 h-3.5 shrink-0 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
            $descriptionHtml .= '    <span>GRS</span>';
            $descriptionHtml .= '  </span>';
            $descriptionHtml .= '  <span class="font-mono text-xs font-semibold tabular-nums text-right shrink-0 tracking-tight whitespace-nowrap">' . number_format($kedatanganDanGrs, 0, ',', '.') . ' Item</span>';
            $descriptionHtml .= '</div>';

            // Baris: RDTV (Warna Kuning/Warning)
            $descriptionHtml .= '<div class="flex items-center justify-between gap-2 py-0.5 border-b border-gray-100/80 dark:border-gray-800/80 text-warning-600 dark:text-warning-400 font-medium">';
            $descriptionHtml .= '  <span class="flex items-center gap-1.5 shrink-0 text-xs">';
            $descriptionHtml .= '    <svg class="w-3.5 h-3.5 shrink-0 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
            $descriptionHtml .= '    <span>RDTV</span>';
            $descriptionHtml .= '  </span>';
            $descriptionHtml .= '  <span class="font-mono text-xs font-semibold tabular-nums text-right shrink-0 tracking-tight whitespace-nowrap">' . number_format($rdtv, 0, ',', '.') . ' Item</span>';
            $descriptionHtml .= '</div>';

            // Baris: Belum GRS (Warna Merah)
            $descriptionHtml .= '<div class="flex items-center justify-between gap-2 py-0.5 text-danger-600 dark:text-danger-400 font-medium">';
            $descriptionHtml .= '  <span class="flex items-center gap-1.5 shrink-0 text-xs">';
            $descriptionHtml .= '    <svg class="w-3.5 h-3.5 shrink-0 text-danger-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
            $descriptionHtml .= '    <span>Belum GRS</span>';
            $descriptionHtml .= '  </span>';
            $descriptionHtml .= '  <span class="font-mono text-xs font-semibold tabular-nums text-right shrink-0 tracking-tight whitespace-nowrap">' . number_format($belumGrs, 0, ',', '.') . ' Item</span>';
            $descriptionHtml .= '</div>';

            if ($abcIndicators->isNotEmpty()) {
                $descriptionHtml .= '<div class="pt-2 mt-2 border-t border-gray-200/70 dark:border-gray-700/70">';
                $descriptionHtml .= '  <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">Breakdown ABC Kedatangan</div>';
                $descriptionHtml .= '  <div class="space-y-1">';
                foreach ($abcIndicators as $abc) {
                    $descriptionHtml .= '    <div class="flex items-center justify-between gap-2 py-0.5 w-full text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 transition-colors">';
                    $descriptionHtml .= '      <span class="flex items-center gap-1.5 shrink-0 text-[11px] font-medium leading-none">';
                    $descriptionHtml .= '        <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 shrink-0"></span>';
                    $descriptionHtml .= '        <span>Tipe ' . $abc->abc_indicator . '</span>';
                    $descriptionHtml .= '      </span>';
                    $descriptionHtml .= '      <span class="font-mono text-[11px] font-medium tabular-nums text-right shrink-0 tracking-tight whitespace-nowrap text-gray-700 dark:text-gray-300">' . number_format($abc->total, 0, ',', '.') . ' Item</span>';
                    $descriptionHtml .= '    </div>';
                }
                $descriptionHtml .= '  </div>';
                $descriptionHtml .= '</div>';
            }
            $descriptionHtml .= '</div>';

            $color = $uiConfig[$mrpType]['color'] ?? 'primary';

            $title = "Total Material {$mrpType}";

            $exactTotal = number_format($totalGrsBulanIni, 0, ',', '.');
            $htmlValue = new HtmlString('<span class="text-xl sm:text-2xl font-bold tracking-tight font-mono whitespace-nowrap">' . $exactTotal . ' Item</span>');

            $stats[] = Stat::make($title, $htmlValue)
                ->description(new HtmlString($descriptionHtml))
                ->color($color);
        }

        return $stats;
    }
}
