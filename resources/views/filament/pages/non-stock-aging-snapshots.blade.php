@php
    $fmt = fn (float $val): string => \App\Filament\Widgets\NonStockAgingOverviewWidget::formatRupiah($val);
@endphp

<div class="space-y-5">
    @if ($snapshots->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
            Belum ada snapshot yang tersimpan untuk cakupan <strong>{{ $mrpScope }}</strong>.
            Gunakan tombol <strong>Ambil Snapshot Baru</strong> untuk menyimpan Baseline W1 sebelum memulai pilot.
        </div>
    @else
        @if ($comparison !== null)
            <div class="rounded-xl border border-orange-200 bg-orange-50/50 p-4 dark:border-orange-500/30 dark:bg-orange-500/10">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                            Evaluasi Before vs. After — {{ $comparison['baseline']->label }} vs. {{ $comparison['evaluation']->label }}
                        </h4>
                        <p class="text-xs text-gray-600 dark:text-gray-300">
                            {{ $comparison['baseline']->snapshot_date->format('d M Y') }} → {{ $comparison['evaluation']->snapshot_date->format('d M Y') }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                        <div class="text-[11px] font-semibold uppercase text-gray-500">Penurunan Item (Target ≥ 25%)</div>
                        <div class="mt-1 text-xl font-bold {{ $comparison['item_target_met'] ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ $comparison['item_reduction_pct'] >= 0 ? '-' : '+' }}{{ abs($comparison['item_reduction_pct']) }}%
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-300">
                            {{ $comparison['baseline']->total_outstanding_items }} → {{ $comparison['evaluation']->total_outstanding_items }} item
                            ({{ $comparison['item_target_met'] ? 'Target Tercapai' : 'Dalam Progres' }})
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                        <div class="text-[11px] font-semibold uppercase text-gray-500">Penurunan Nilai (Target ≥ 20%)</div>
                        <div class="mt-1 text-xl font-bold {{ $comparison['value_target_met'] ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ $comparison['value_reduction_pct'] >= 0 ? '-' : '+' }}{{ abs($comparison['value_reduction_pct']) }}%
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-300">
                            {{ $fmt($comparison['baseline']->total_outstanding_value) }} → {{ $fmt($comparison['evaluation']->total_outstanding_value) }}
                            ({{ $comparison['value_target_met'] ? 'Target Tercapai' : 'Dalam Progres' }})
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                        <div class="text-[11px] font-semibold uppercase text-gray-500">Follow-Up Warning & Critical</div>
                        <div class="mt-1 text-xl font-bold {{ $comparison['followup_target_met'] ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ number_format($comparison['evaluation']->warning_critical_followed_up_percentage, 1, ',', '.') }}%
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-300">
                            Baseline: {{ number_format($comparison['baseline']->warning_critical_followed_up_percentage, 1, ',', '.') }}%
                            (Target 100%)
                        </div>
                    </div>
                </div>

                @if (! empty($comparison['unit_deltas']))
                    <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                        <table class="w-full divide-y divide-gray-200 text-left text-xs dark:divide-gray-800">
                            <thead class="bg-gray-50 text-[11px] font-semibold uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                <tr>
                                    <th class="px-3 py-2">Unit Peminta</th>
                                    <th class="px-3 py-2 text-center">Item Before</th>
                                    <th class="px-3 py-2 text-center">Item After</th>
                                    <th class="px-3 py-2 text-right">Nilai Before</th>
                                    <th class="px-3 py-2 text-right">Nilai After</th>
                                    <th class="px-3 py-2 text-right">Penurunan Nilai</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($comparison['unit_deltas'] as $u)
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-gray-100">
                                            {{ $u['unit_name'] }}
                                            @if ($u['is_pilot'])
                                                <span class="ml-1 rounded bg-orange-100 px-1.5 py-0.5 text-[10px] font-semibold text-orange-700">Pilot</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-center">{{ $u['before_items'] }}</td>
                                        <td class="px-3 py-2 text-center font-semibold">{{ $u['after_items'] }}</td>
                                        <td class="px-3 py-2 text-right">{{ $fmt($u['before_value']) }}</td>
                                        <td class="px-3 py-2 text-right font-semibold">{{ $fmt($u['after_value']) }}</td>
                                        <td class="px-3 py-2 text-right font-semibold {{ $u['value_reduction_pct'] >= 20 ? 'text-emerald-600' : 'text-gray-700 dark:text-gray-300' }}">
                                            {{ $u['value_reduction_pct'] >= 0 ? '-' : '+' }}{{ abs($u['value_reduction_pct']) }}%
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif

        <div>
            <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                Riwayat Snapshot ({{ $mrpScope }})
            </h4>
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full divide-y divide-gray-200 text-left text-xs dark:divide-gray-700">
                    <thead class="bg-gray-50 text-[11px] font-semibold uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="px-3 py-2">Label</th>
                            <th class="px-3 py-2">Tanggal</th>
                            <th class="px-3 py-2">Cakupan</th>
                            <th class="px-3 py-2 text-center">Item / PO</th>
                            <th class="px-3 py-2 text-right">Nilai Tertahan</th>
                            <th class="px-3 py-2 text-center">Critical (&gt;60H)</th>
                            <th class="px-3 py-2 text-center">On Check</th>
                            <th class="px-3 py-2 text-right">Follow-Up %</th>
                            <th class="px-3 py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($snapshots as $snap)
                            <tr>
                                <td class="px-3 py-2 font-semibold text-gray-900 dark:text-white">{{ $snap->label }}</td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $snap->snapshot_date->format('d M Y') }}</td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $snap->scope === 'pilot_only' ? 'Unit Pilot' : 'Semua Unit' }}</td>
                                <td class="px-3 py-2 text-center font-semibold">{{ $snap->total_outstanding_items }} / {{ $snap->total_outstanding_po }}</td>
                                <td class="px-3 py-2 text-right font-semibold">{{ $fmt($snap->total_outstanding_value) }}</td>
                                <td class="px-3 py-2 text-center text-red-600 font-semibold">{{ $snap->critical_count }}</td>
                                <td class="px-3 py-2 text-center text-sky-600">{{ $snap->on_check_items }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($snap->warning_critical_followed_up_percentage, 1, ',', '.') }}%</td>
                                <td class="px-3 py-2 text-right">
                                    <button
                                        type="button"
                                        wire:click="deleteSnapshot({{ $snap->id }})"
                                        wire:confirm="Yakin ingin menghapus snapshot '{{ addslashes($snap->label) }}'?"
                                        class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-[11px] font-semibold text-red-700 hover:bg-red-100 dark:bg-red-500/15 dark:text-red-300 dark:hover:bg-red-500/25"
                                    >
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
