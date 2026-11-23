<x-filament-widgets::widget>
    @php
        $fmt = fn (float $val): string => \App\Filament\Widgets\NonStockAgingOverviewWidget::formatRupiah($val);
        $zones = $summary['zones'];
        $baseline = $summary['baseline_comparison'];
        $pareto = array_slice($summary['pareto'], 0, 8);
    @endphp

    <div class="space-y-4">
        {{-- 4 Kartu Ringkasan Utama --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Belum Diambil ({{ $summary['mrp_scope'] }})
                </div>
                <div class="mt-1 flex items-baseline justify-between">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($summary['outstanding_items'], 0, ',', '.') }} item
                    </div>
                    <span class="rounded-md bg-orange-50 px-2 py-0.5 text-xs font-semibold text-orange-700 dark:bg-orange-500/15 dark:text-orange-300">
                        {{ $summary['outstanding_po'] }} PO
                    </span>
                </div>
                <div class="mt-1 text-sm font-semibold text-orange-600 dark:text-orange-400">
                    {{ $fmt($summary['outstanding_value']) }}
                </div>
                @if ($baseline !== null)
                    <div class="mt-2 border-t border-gray-100 pt-2 text-xs text-gray-600 dark:border-gray-800 dark:text-gray-400">
                        vs {{ $baseline['baseline_label'] }}:
                        <span class="font-semibold {{ $baseline['item_target_met'] ? 'text-emerald-600' : 'text-amber-600' }}">
                            Item {{ $baseline['item_reduction_pct'] >= 0 ? '-' : '+' }}{{ abs($baseline['item_reduction_pct']) }}%
                        </span>
                        &bull;
                        <span class="font-semibold {{ $baseline['value_target_met'] ? 'text-emerald-600' : 'text-amber-600' }}">
                            Nilai {{ $baseline['value_reduction_pct'] >= 0 ? '-' : '+' }}{{ abs($baseline['value_reduction_pct']) }}%
                        </span>
                    </div>
                @else
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Scheduled Hold: {{ $summary['scheduled_hold_items'] }} item ({{ $fmt($summary['scheduled_hold_value']) }})
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    On Check
                </div>
                <div class="mt-1 flex items-baseline justify-between">
                    <div class="text-2xl font-bold text-sky-700 dark:text-sky-400">
                        {{ number_format($summary['on_check_items'], 0, ',', '.') }} item
                    </div>
                    <span class="rounded-md bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                        {{ $summary['on_check_po'] }} PO
                    </span>
                </div>
                <div class="mt-1 text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ $fmt($summary['on_check_value']) }}
                </div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Menunggu cek fisik
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Follow-Up (&gt; 30 Hari)
                </div>
                <div class="mt-1 text-2xl font-bold {{ $summary['warning_critical_followed_up_percentage'] >= 100 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                    {{ number_format($summary['warning_critical_followed_up_percentage'], 1, ',', '.') }}%
                </div>
                <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                    {{ $summary['warning_critical_followed_up'] }} dari {{ $summary['warning_critical_total'] }} item sudah ditindaklanjuti
                </div>
                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                    <div class="h-full rounded-full {{ $summary['warning_critical_followed_up_percentage'] >= 100 ? 'bg-emerald-500' : 'bg-amber-500' }}"
                         style="width: {{ min(100, $summary['warning_critical_followed_up_percentage']) }}%"></div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Pemetaan Unit &amp; PIC
                </div>
                <div class="mt-1 text-2xl font-bold {{ $summary['pic_mapped_percentage'] >= 100 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-900 dark:text-white' }}">
                    {{ number_format($summary['pic_mapped_percentage'], 1, ',', '.') }}%
                </div>
                <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                    {{ $summary['pic_mapped_count'] }} dari {{ $summary['outstanding_items'] }} item terpetakan
                </div>
                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                    <div class="h-full rounded-full {{ $summary['pic_mapped_percentage'] >= 100 ? 'bg-emerald-500' : 'bg-sky-500' }}"
                         style="width: {{ min(100, $summary['pic_mapped_percentage']) }}%"></div>
                </div>
            </div>
        </div>

        {{-- Distribusi 4 Zona Aging --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['key' => 'Normal', 'title' => 'Normal (0–14 Hari)', 'sub' => 'Notifikasi Awal', 'border' => 'border-l-emerald-500', 'badge' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'],
                ['key' => 'Attention', 'title' => 'Attention (15–30 Hari)', 'sub' => 'Reminder PIC', 'border' => 'border-l-sky-500', 'badge' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300'],
                ['key' => 'Warning', 'title' => 'Warning (31–60 Hari)', 'sub' => 'Konfirmasi AVP', 'border' => 'border-l-amber-500', 'badge' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'],
                ['key' => 'Critical', 'title' => 'Critical (> 60 Hari)', 'sub' => 'Eskalasi VP', 'border' => 'border-l-red-600', 'badge' => 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300'],
            ] as $card)
                @php $z = $zones[$card['key']]; @endphp
                <div class="rounded-xl border border-gray-200 border-l-4 {{ $card['border'] }} bg-white p-3.5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold text-gray-800 dark:text-gray-100">{{ $card['title'] }}</span>
                        <span class="rounded px-2 py-0.5 text-xs font-semibold {{ $card['badge'] }}">
                            {{ $z['po_count'] }} PO
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $z['items'] }} item</span>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $fmt($z['value']) }}</span>
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ $card['sub'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Rekap per Unit Peminta --}}
        @if (! empty($pareto))
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Rekap per Unit Peminta</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Critical Belum Terjadwal (&gt;60H): <strong class="text-red-600 dark:text-red-400">{{ $summary['unattended_critical_items'] }} item ({{ $fmt($summary['unattended_critical_value']) }})</strong>
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 text-left text-xs dark:divide-gray-800">
                        <thead class="bg-gray-50 text-[11px] font-semibold uppercase text-gray-600 dark:bg-gray-800/60 dark:text-gray-300">
                            <tr>
                                <th class="px-3 py-2">Unit Peminta</th>
                                <th class="px-3 py-2 text-center">Item</th>
                                <th class="px-3 py-2 text-center">PO</th>
                                <th class="px-3 py-2 text-right">Nilai Tertahan</th>
                                <th class="px-3 py-2 text-right">Kontribusi</th>
                                <th class="px-3 py-2 text-right">Kumulatif</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($pareto as $row)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-gray-900 dark:text-gray-100">
                                        {{ $row['unit_name'] }}
                                        @if ($row['is_pilot'])
                                            <span class="ml-1.5 rounded bg-orange-100 px-1.5 py-0.5 text-[10px] font-semibold text-orange-700 dark:bg-orange-500/20 dark:text-orange-300">Prioritas</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center text-gray-700 dark:text-gray-300">{{ $row['items'] }}</td>
                                    <td class="px-3 py-2 text-center text-gray-700 dark:text-gray-300">{{ $row['po_count'] }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">{{ $fmt($row['value']) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['share_pct'], 1, ',', '.') }}%</td>
                                    <td class="px-3 py-2 text-right font-semibold {{ $row['cumulative_pct'] <= 80.5 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ number_format($row['cumulative_pct'], 1, ',', '.') }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
