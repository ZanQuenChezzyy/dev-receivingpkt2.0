@php
    $fmt = fn (float $val): string => \App\Filament\Widgets\NonStockAgingOverviewWidget::formatRupiah($val);
@endphp

<div
    x-data="{
        confirmId: null,
        confirmLabel: '',
        deleting: false,
        askDelete(id, label) {
            this.confirmId = id;
            this.confirmLabel = label;
        },
        cancelDelete() {
            if (this.deleting) return;
            this.confirmId = null;
            this.confirmLabel = '';
        },
        async confirmDelete() {
            if (!this.confirmId || this.deleting) return;
            this.deleting = true;
            try {
                await $wire.deleteSnapshot(this.confirmId);
                this.confirmId = null;
                this.confirmLabel = '';
            } finally {
                this.deleting = false;
            }
        }
    }"
    class="space-y-5"
>
    @if ($snapshots->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
            Belum ada snapshot yang tersimpan untuk cakupan <strong>{{ $mrpScope }}</strong>.
            Gunakan menu <strong>Ambil Snapshot</strong> untuk menyimpan data awal.
        </div>
    @else
        @if ($comparison !== null)
            <div class="rounded-xl border border-orange-200 bg-orange-50/50 p-4 dark:border-orange-500/30 dark:bg-orange-500/10">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                            Perbandingan Snapshot — {{ $comparison['baseline']->label }} vs. {{ $comparison['evaluation']->label }}
                        </h4>
                        <p class="text-xs text-gray-600 dark:text-gray-300">
                            {{ $comparison['baseline']->snapshot_date->format('d M Y') }} → {{ $comparison['evaluation']->snapshot_date->format('d M Y') }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                        <div class="text-[11px] font-semibold uppercase text-gray-500">Perubahan Item</div>
                        <div class="mt-1 text-xl font-bold {{ $comparison['item_reduction_pct'] > 0 ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ $comparison['item_reduction_pct'] >= 0 ? '-' : '+' }}{{ abs($comparison['item_reduction_pct']) }}%
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-300">
                            {{ $comparison['baseline']->total_outstanding_items }} → {{ $comparison['evaluation']->total_outstanding_items }} item
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                        <div class="text-[11px] font-semibold uppercase text-gray-500">Perubahan Nilai</div>
                        <div class="mt-1 text-xl font-bold {{ $comparison['value_reduction_pct'] > 0 ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ $comparison['value_reduction_pct'] >= 0 ? '-' : '+' }}{{ abs($comparison['value_reduction_pct']) }}%
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-300">
                            {{ $fmt($comparison['baseline']->total_outstanding_value) }} → {{ $fmt($comparison['evaluation']->total_outstanding_value) }}
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                        <div class="text-[11px] font-semibold uppercase text-gray-500">Follow-Up (&gt; 30 Hari)</div>
                        <div class="mt-1 text-xl font-bold {{ $comparison['followup_target_met'] ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ number_format($comparison['evaluation']->warning_critical_followed_up_percentage, 1, ',', '.') }}%
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-300">
                            Sebelumnya: {{ number_format($comparison['baseline']->warning_critical_followed_up_percentage, 1, ',', '.') }}%
                        </div>
                    </div>
                </div>

                @if (! empty($comparison['unit_deltas']))
                    <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                        <table class="w-full divide-y divide-gray-200 text-left text-xs dark:divide-gray-800">
                            <thead class="bg-gray-50 text-[11px] font-semibold uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                <tr>
                                    <th class="px-3 py-2">Unit Peminta</th>
                                    <th class="px-3 py-2 text-center">Item Sebelum</th>
                                    <th class="px-3 py-2 text-center">Item Sesudah</th>
                                    <th class="px-3 py-2 text-right">Nilai Sebelum</th>
                                    <th class="px-3 py-2 text-right">Nilai Sesudah</th>
                                    <th class="px-3 py-2 text-right">Perubahan Nilai</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($comparison['unit_deltas'] as $u)
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-gray-100">
                                            {{ $u['unit_name'] }}
                                            @if ($u['is_pilot'])
                                                <span class="ml-1 rounded bg-orange-100 px-1.5 py-0.5 text-[10px] font-semibold text-orange-700">Prioritas</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-center">{{ $u['before_items'] }}</td>
                                        <td class="px-3 py-2 text-center font-semibold">{{ $u['after_items'] }}</td>
                                        <td class="px-3 py-2 text-right">{{ $fmt($u['before_value']) }}</td>
                                        <td class="px-3 py-2 text-right font-semibold">{{ $fmt($u['after_value']) }}</td>
                                        <td class="px-3 py-2 text-right font-semibold {{ $u['value_reduction_pct'] > 0 ? 'text-emerald-600' : 'text-gray-700 dark:text-gray-300' }}">
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

        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                    Riwayat Snapshot ({{ $mrpScope }})
                </h4>
            </div>

            {{-- Kartu Konfirmasi Hapus Modern (tanpa popup browser native) --}}
            <div
                x-show="confirmId !== null"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50/90 p-3.5 shadow-sm dark:border-red-500/30 dark:bg-red-950/40"
            >
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400">
                        <x-filament::icon icon="heroicon-o-trash" class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-red-900 dark:text-red-200">
                            Hapus snapshot "<span x-text="confirmLabel"></span>"?
                        </div>
                        <div class="text-xs text-red-700/80 dark:text-red-300/80">
                            Data snapshot yang dihapus tidak dapat dikembalikan.
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="cancelDelete()"
                        :disabled="deleting"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmDelete()"
                        :disabled="deleting"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-500 disabled:opacity-50"
                    >
                        <span x-show="!deleting">Ya, Hapus</span>
                        <span x-show="deleting" x-cloak>Menghapus...</span>
                    </button>
                </div>
            </div>

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
                            <tr
                                :class="confirmId === {{ $snap->id }} ? 'bg-red-50/60 dark:bg-red-950/30' : ''"
                                class="transition-colors"
                            >
                                <td class="px-3 py-2 font-semibold text-gray-900 dark:text-white">{{ $snap->label }}</td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $snap->snapshot_date->format('d M Y') }}</td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $snap->scope === 'pilot_only' ? 'Unit Prioritas' : 'Semua Unit' }}</td>
                                <td class="px-3 py-2 text-center font-semibold">{{ $snap->total_outstanding_items }} / {{ $snap->total_outstanding_po }}</td>
                                <td class="px-3 py-2 text-right font-semibold">{{ $fmt($snap->total_outstanding_value) }}</td>
                                <td class="px-3 py-2 text-center font-semibold text-red-600">{{ $snap->critical_count }}</td>
                                <td class="px-3 py-2 text-center text-sky-600">{{ $snap->on_check_items }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($snap->warning_critical_followed_up_percentage, 1, ',', '.') }}%</td>
                                <td class="px-3 py-2 text-right">
                                    <button
                                        type="button"
                                        @click="askDelete({{ $snap->id }}, @js($snap->label))"
                                        class="inline-flex items-center gap-1 rounded-md bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700 transition hover:bg-red-100 dark:bg-red-500/15 dark:text-red-300 dark:hover:bg-red-500/25"
                                    >
                                        <x-filament::icon icon="heroicon-m-trash" class="h-3.5 w-3.5" />
                                        <span>Hapus</span>
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
