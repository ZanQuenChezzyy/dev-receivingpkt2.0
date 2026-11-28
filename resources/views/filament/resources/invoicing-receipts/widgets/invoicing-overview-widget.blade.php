<x-filament-widgets::widget>
    @php
        $stats = $this->getStatsData();
        $periodOptions = $this->getPeriodOptions();
        $periodLabel = $this->getPeriodLabel();
        $workloads = $this->getStaffWorkloads();
        $topPicShare = ($stats['total_masuk'] > 0 && $stats['top_pic_count'] > 0)
            ? (int) round(($stats['top_pic_count'] / $stats['total_masuk']) * 100)
            : 0;
    @endphp

    <div class="pkt-bento-card relative rounded-[24px] bg-white dark:bg-[#151B24] border border-black/[0.06] dark:border-white/[0.08] shadow-xs overflow-hidden p-4 sm:p-5 flex flex-col gap-4">
        {{-- Subtle PKT Corporate Ambient Glow & Pattern Watermark --}}
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_0%_0%,rgba(0,84,166,0.07),transparent_46%),radial-gradient(circle_at_100%_100%,rgba(244,121,32,0.08),transparent_48%)] dark:bg-[radial-gradient(circle_at_0%_0%,rgba(0,84,166,0.18),transparent_50%),radial-gradient(circle_at_100%_100%,rgba(244,121,32,0.14),transparent_48%)]"></div>
        <div class="pointer-events-none absolute -top-10 -right-10 w-44 h-44 select-none overflow-hidden">
            <img
                src="{{ asset('images/pattern/pkt-pattern-dark.png') }}"
                alt=""
                class="w-full h-full object-contain dark:hidden opacity-[0.06]"
            />
            <img
                src="{{ asset('images/pattern/pkt-pattern-white.png') }}"
                alt=""
                class="w-full h-full object-contain hidden dark:block opacity-[0.05]"
            />
        </div>

        {{-- Header Bar & Filter Periode --}}
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex flex-col min-w-0 gap-0.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-[#0054A6] dark:text-[#83A2DB]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#F47920]"></span>
                        Invoice Barang
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-white/[0.07] text-slate-600 dark:text-slate-300">
                        {{ $periodLabel }}
                    </span>
                    <span
                        wire:loading
                        wire:target="selectedPeriod"
                        class="inline-flex items-center gap-1 text-[10px] font-semibold text-[#F47920] dark:text-orange-300"
                    >
                        <x-filament::loading-indicator class="w-3 h-3" />
                        Memuat...
                    </span>
                </div>

                <h3 class="text-lg sm:text-xl font-extrabold tracking-tight text-[#10141A] dark:text-white leading-snug">
                    Ringkasan Invoice Barang
                </h3>
            </div>

            {{-- Filter Periode --}}
            <div class="flex items-center gap-2 w-full sm:w-auto shrink-0">
                <label
                    for="widget-invoicing-period-select"
                    class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 shrink-0"
                >
                    Periode
                </label>
                <div class="relative flex-1 sm:flex-none sm:min-w-[210px]">
                    <span class="pointer-events-none absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 dark:text-slate-500">
                        <x-heroicon-m-calendar-days class="w-4 h-4" />
                    </span>
                    <select
                        id="widget-invoicing-period-select"
                        wire:model.live="selectedPeriod"
                        class="w-full appearance-none text-xs font-semibold rounded-xl bg-slate-50/95 dark:bg-[#0F141C] border border-black/[0.09] dark:border-white/[0.1] text-[#10141A] dark:text-white pl-9 pr-8 py-2 focus:ring-2 focus:ring-[#F47920]/30 focus:border-[#F47920] outline-none cursor-pointer transition-colors"
                    >
                        @foreach ($periodOptions as $key => $label)
                            <option value="{{ $key }}" class="bg-white dark:bg-[#151B24] text-[#10141A] dark:text-white">{{ $label }}</option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 dark:text-slate-500">
                        <x-heroicon-m-chevron-down class="w-4 h-4" />
                    </span>
                </div>
            </div>
        </div>

        {{-- 3 Bento Metric Cards --}}
        <div
            wire:loading.class="opacity-65 pointer-events-none"
            wire:target="selectedPeriod"
            class="relative z-10 grid grid-cols-1 md:grid-cols-3 gap-3 transition-opacity duration-200"
        >
            {{-- Card 1: Total Invoice Masuk --}}
            <div class="rounded-2xl p-4 bg-slate-50/90 dark:bg-white/[0.03] border border-black/[0.05] dark:border-white/[0.07] flex flex-col justify-between gap-3">
                <div class="flex flex-col gap-2.5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-300 min-w-0">
                            <span class="w-8 h-8 rounded-xl bg-[#0054A6]/12 dark:bg-[#83A2DB]/15 text-[#0054A6] dark:text-[#93C5FD] inline-flex items-center justify-center shrink-0">
                                <x-heroicon-m-inbox-arrow-down class="w-4 h-4" />
                            </span>
                            <span class="truncate">Total Invoice Masuk</span>
                        </span>

                        <div class="flex items-center gap-1 shrink-0">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold tabular-nums bg-emerald-500/12 text-emerald-700 dark:text-emerald-300">
                                {{ $stats['total_grs'] }} GRS
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold tabular-nums bg-amber-500/14 text-amber-700 dark:text-amber-300">
                                {{ $stats['total_rdtv'] }} RDTV
                            </span>
                        </div>
                    </div>

                    <div class="flex items-baseline gap-1.5 min-w-0">
                        <span class="text-2xl sm:text-[26px] font-extrabold tracking-tight text-[#10141A] dark:text-white tabular-nums leading-none">
                            {{ number_format($stats['total_masuk'], 0, ',', '.') }}
                        </span>
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                            Dokumen
                        </span>
                    </div>
                </div>

                <div class="pt-2.5 border-t border-black/[0.05] dark:border-white/[0.06] flex items-center justify-between gap-2 text-[11px]">
                    <span class="text-slate-500 dark:text-slate-400 shrink-0">Total Nilai Tagihan</span>
                    <span class="font-bold text-[#10141A] dark:text-slate-100 tabular-nums truncate" title="Rp {{ number_format($stats['total_nominal'], 0, ',', '.') }}">
                        Rp {{ number_format($stats['total_nominal'], 0, ',', '.') }}
                    </span>
                </div>
            </div>

            {{-- Card 2: Invoice Selesai --}}
            <div class="rounded-2xl p-4 bg-slate-50/90 dark:bg-white/[0.03] border border-black/[0.05] dark:border-white/[0.07] flex flex-col justify-between gap-3">
                <div class="flex flex-col gap-2.5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-300 min-w-0">
                            <span class="w-8 h-8 rounded-xl bg-emerald-500/12 dark:bg-emerald-400/15 text-emerald-700 dark:text-emerald-300 inline-flex items-center justify-center shrink-0">
                                <x-heroicon-m-check-badge class="w-4 h-4" />
                            </span>
                            <span class="truncate">Invoice Selesai</span>
                        </span>

                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-extrabold tabular-nums bg-emerald-500/12 text-emerald-700 dark:text-emerald-300 shrink-0">
                            {{ $stats['completion_rate'] }}%
                        </span>
                    </div>

                    <div class="flex flex-col gap-2">
                        <div class="flex items-baseline gap-1.5 min-w-0">
                            <span class="text-2xl sm:text-[26px] font-extrabold tracking-tight text-[#10141A] dark:text-white tabular-nums leading-none">
                                {{ number_format($stats['total_selesai'], 0, ',', '.') }}
                            </span>
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400 tabular-nums">
                                / {{ number_format($stats['total_masuk'], 0, ',', '.') }} Dokumen
                            </span>
                        </div>

                        <div class="w-full h-1.5 rounded-full bg-slate-200/80 dark:bg-white/[0.08] overflow-hidden">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-500"
                                style="width: {{ min(100, max(0, $stats['completion_rate'])) }}%"
                            ></div>
                        </div>
                    </div>
                </div>

                <div class="pt-2.5 border-t border-black/[0.05] dark:border-white/[0.06] flex items-center justify-between gap-2 text-[11px]">
                    <span class="text-slate-500 dark:text-slate-400">Dalam Proses</span>
                    <span @class([
                        'font-bold tabular-nums',
                        'text-[#F47920] dark:text-orange-300' => $stats['total_proses'] > 0,
                        'text-slate-500 dark:text-slate-400' => $stats['total_proses'] === 0,
                    ])>
                        {{ number_format($stats['total_proses'], 0, ',', '.') }} Dokumen
                    </span>
                </div>
            </div>

            {{-- Card 3: Top PIC Invoice (Clickable — opens Staff Workload Modal) --}}
            <button
                type="button"
                x-on:click="$dispatch('open-modal', { id: 'staff-workload-modal' })"
                class="pkt-bento-shortcut group text-left rounded-2xl p-4 bg-slate-50/90 dark:bg-white/[0.03] border border-black/[0.05] dark:border-white/[0.07] hover:border-[#F47920]/45 hover:bg-white dark:hover:bg-white/[0.055] transition-all duration-200 flex flex-col justify-between gap-3 cursor-pointer w-full focus:outline-none focus-visible:ring-2 focus-visible:ring-[#F47920]"
            >
                <div class="flex flex-col gap-2.5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-300 min-w-0">
                            <span class="w-8 h-8 rounded-xl bg-[#F47920]/12 dark:bg-[#F47920]/20 text-[#F47920] dark:text-orange-300 group-hover:bg-[#F47920] group-hover:text-white inline-flex items-center justify-center shrink-0 transition-colors duration-200">
                                <x-heroicon-m-trophy class="w-4 h-4" />
                            </span>
                            <span class="truncate">Top PIC Invoice</span>
                        </span>

                        @if ($topPicShare > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold tabular-nums bg-[#F47920]/12 text-[#F47920] dark:text-orange-300 shrink-0">
                                {{ $topPicShare }}% beban
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-200/75 dark:bg-white/[0.07] text-slate-500 dark:text-slate-400 shrink-0">
                                Detail Staff
                            </span>
                        @endif
                    </div>

                    <div class="flex flex-col gap-0.5 min-w-0">
                        <span
                            class="text-lg sm:text-xl font-extrabold tracking-tight text-[#10141A] dark:text-white group-hover:text-[#F47920] dark:group-hover:text-orange-300 transition-colors truncate leading-snug"
                            title="{{ $stats['top_pic_name'] }}"
                        >
                            {{ $stats['top_pic_name'] }}
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 truncate">
                            @if ($stats['top_pic_count'] > 0)
                                Menangani <strong class="font-bold text-[#10141A] dark:text-slate-200 tabular-nums">{{ number_format($stats['top_pic_count'], 0, ',', '.') }} Dokumen</strong> periode ini
                            @else
                                Belum ada dokumen ditugaskan
                            @endif
                        </span>
                    </div>
                </div>

                <div class="pt-2.5 border-t border-black/[0.05] dark:border-white/[0.06] flex items-center justify-between gap-2 text-[11px] font-semibold text-[#0054A6] dark:text-[#83A2DB] group-hover:text-[#F47920] dark:group-hover:text-orange-300 transition-colors">
                    <span>Lihat Beban Semua Staff Invoice</span>
                    <x-heroicon-m-arrow-right class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform shrink-0" />
                </div>
            </button>
        </div>

        {{-- Modal Daftar Beban Kerja Staff Invoice (Unified Filament Modal Styling) --}}
        <x-filament::modal
            id="staff-workload-modal"
            teleport="body"
            width="2xl"
            sticky-header
            sticky-footer
            :extra-modal-window-attribute-bag="new \Illuminate\View\ComponentAttributeBag([
                'x-data' => '{ search: \'\' }',
            ])"
        >
            <x-slot name="header">
                <div class="w-full space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-[#F47920]/12 dark:bg-[#F47920]/20 text-[#F47920] dark:text-orange-300 inline-flex items-center justify-center shrink-0">
                            <x-heroicon-m-user-group class="w-5 h-5" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-base sm:text-lg font-extrabold tracking-tight text-[#10141A] dark:text-white leading-tight">
                                Daftar Beban Kerja Staff Invoice
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Periode <strong class="font-semibold text-slate-700 dark:text-slate-200">{{ $periodLabel }}</strong>
                                &bull; Total {{ number_format($stats['total_masuk'], 0, ',', '.') }} Dokumen
                            </p>
                        </div>
                    </div>

                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 dark:text-slate-500">
                            <x-heroicon-m-magnifying-glass class="w-4 h-4" />
                        </span>
                        <input
                            type="text"
                            x-model="search"
                            placeholder="Cari nama atau email staff invoice..."
                            class="w-full pl-9 pr-4 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-[#0F141C] rounded-xl border border-black/[0.09] dark:border-white/[0.1] text-[#10141A] dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-[#F47920]/25 focus:border-[#F47920] outline-none transition-colors"
                        >
                    </div>
                </div>
            </x-slot>

            <div class="space-y-2">
                @forelse ($workloads as $index => $staff)
                    @php
                        $rankClass = match (true) {
                            $index === 0 && $staff['total'] > 0 => 'bg-[#F47920] text-white shadow-xs',
                            $index === 1 && $staff['total'] > 0 => 'bg-[#0054A6] text-white',
                            $index === 2 && $staff['total'] > 0 => 'bg-slate-600 dark:bg-slate-500 text-white',
                            default => 'bg-slate-200/80 dark:bg-white/[0.08] text-slate-600 dark:text-slate-400',
                        };
                    @endphp
                    <div
                        x-show="!search.trim() || {{ \Illuminate\Support\Js::from(strtolower($staff['name'].' '.$staff['email'])) }}.includes(search.toLowerCase().trim())"
                        class="flex items-center justify-between gap-3 p-3 sm:p-3.5 rounded-2xl border border-black/[0.06] dark:border-white/[0.07] bg-slate-50/85 dark:bg-white/[0.025] hover:bg-white dark:hover:bg-white/[0.05] hover:border-[#0054A6]/25 dark:hover:border-white/[0.14] transition-all"
                    >
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <div class="w-7 h-7 rounded-xl inline-flex items-center justify-center font-extrabold text-xs tabular-nums shrink-0 {{ $rankClass }}">
                                #{{ $index + 1 }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-xs sm:text-sm font-bold text-[#10141A] dark:text-white truncate">
                                        {{ $staff['name'] }}
                                    </span>
                                    @if ($index === 0 && $staff['total'] > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#F47920]/12 text-[#F47920] dark:text-orange-300 shrink-0">
                                            Terbanyak
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 truncate block">
                                    {{ $staff['email'] ?: 'Staff Invoice' }}
                                </span>
                                @if ($staff['total'] > 0)
                                    <div class="mt-1.5 flex items-center gap-2 max-w-[200px]">
                                        <div class="flex-1 h-1.5 rounded-full bg-slate-200/80 dark:bg-white/[0.08] overflow-hidden">
                                            <div
                                                class="h-full rounded-full bg-emerald-500"
                                                style="width: {{ min(100, max(0, $staff['rate'])) }}%"
                                            ></div>
                                        </div>
                                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 tabular-nums shrink-0">
                                            {{ $staff['rate'] }}%
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <div class="text-sm sm:text-base font-extrabold text-[#10141A] dark:text-white tabular-nums leading-tight">
                                {{ number_format($staff['total'], 0, ',', '.') }}
                                <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Invoice</span>
                            </div>
                            <div class="flex items-center justify-end gap-1.5 text-[10px] sm:text-[11px] mt-0.5 tabular-nums">
                                <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ $staff['selesai'] }} Selesai</span>
                                <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                                <span class="text-[#F47920] dark:text-orange-300 font-semibold">{{ $staff['proses'] }} Proses</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center text-slate-500 dark:text-slate-400">
                        <x-heroicon-o-user-minus class="w-9 h-9 mx-auto mb-2 text-slate-300 dark:text-slate-600" />
                        <p class="text-sm font-medium">Belum ada staff invoice terdaftar.</p>
                    </div>
                @endforelse
            </div>

            <x-slot name="footer">
                <div class="flex items-center justify-between w-full gap-3">
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        Total <strong class="font-bold text-[#10141A] dark:text-slate-200 tabular-nums">{{ count($workloads) }} Staff</strong> terdaftar
                    </span>
                    <x-filament::button
                        color="gray"
                        size="sm"
                        x-on:click="$dispatch('close-modal', { id: 'staff-workload-modal' })"
                    >
                        Tutup
                    </x-filament::button>
                </div>
            </x-slot>
        </x-filament::modal>
    </div>
</x-filament-widgets::widget>
