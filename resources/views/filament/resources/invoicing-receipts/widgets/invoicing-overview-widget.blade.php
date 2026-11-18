<x-filament-widgets::widget class="fi-wi-stats-overview">
    @php
        $stats = $this->getStatsData();
        $periodOptions = $this->getPeriodOptions();
        $periodLabel = $this->getPeriodLabel();
        $workloads = $this->getStaffWorkloads();
    @endphp

    <div class="space-y-3">
        <!-- Widget Header & Filter Periode -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-1">
            <div class="flex items-center gap-2">
                <div class="p-1.5 rounded-lg bg-[#F47920]/10 text-[#F47920] dark:bg-[#F47920]/20">
                    <x-filament::icon
                        icon="heroicon-m-chart-bar-square"
                        class="w-5 h-5"
                    />
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        Ringkasan Invoice Barang
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Periode: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $periodLabel }}</span>
                    </p>
                </div>
            </div>

            <!-- Periode Dropdown Filter -->
            <div class="flex items-center gap-2">
                <label for="widget-invoicing-period-select" class="text-xs font-medium text-slate-500 dark:text-slate-400 shrink-0">
                    Pilih Bulan:
                </label>
                <div class="relative min-w-[200px]">
                    <select
                        id="widget-invoicing-period-select"
                        wire:model.live="selectedPeriod"
                        class="w-full text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 border border-slate-300 dark:border-white/10 text-slate-900 dark:text-white px-3 py-1.5 focus:ring-2 focus:ring-[#F47920] focus:border-[#F47920] outline-none shadow-sm cursor-pointer transition-colors"
                    >
                        @foreach ($periodOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- 3 Stat Cards Grid (Maksimal 3 Widget) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <!-- Widget 1: Top PIC Invoice (Clickable untuk buka list beban kerja staff) -->
            <div
                wire:click="openStaffModal"
                role="button"
                tabindex="0"
                class="group relative overflow-hidden rounded-xl bg-white dark:bg-slate-900/80 border-2 border-amber-500/30 hover:border-amber-500 p-4 shadow-sm hover:shadow-md transition-all cursor-pointer select-none"
            >
                <div class="flex items-start justify-between">
                    <div class="space-y-1 pr-2 min-w-0">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                                Top PIC Invoice
                            </span>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-300">
                                Klik Detail
                            </span>
                        </div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white truncate" title="{{ $stats['top_pic_name'] }}">
                            {{ $stats['top_pic_name'] }}
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            @if ($stats['top_pic_count'] > 0)
                                Menerima <strong class="text-amber-600 dark:text-amber-400 font-bold">{{ $stats['top_pic_count'] }} Dokumen</strong> bulan ini
                            @else
                                Belum ada dokumen ditugaskan
                            @endif
                        </p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-500 dark:bg-amber-500/20 shrink-0 group-hover:scale-110 transition-transform">
                        <x-filament::icon
                            icon="heroicon-m-trophy"
                            class="w-6 h-6"
                        />
                    </div>
                </div>

                <!-- Footer hint -->
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-white/5 flex items-center justify-between text-[11px] font-semibold text-amber-600 dark:text-amber-400">
                    <span>Lihat Beban Semua Staff Invoice</span>
                    <x-filament::icon
                        icon="heroicon-m-arrow-right"
                        class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform"
                    />
                </div>
            </div>

            <!-- Widget 2: Total Invoice Masuk -->
            <div class="relative overflow-hidden rounded-xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-white/10 p-4 shadow-sm">
                <div class="flex items-start justify-between">
                    <div class="space-y-1 pr-2 min-w-0">
                        <span class="text-xs font-bold uppercase tracking-wider text-sky-600 dark:text-sky-400">
                            Total Invoice Masuk
                        </span>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                            {{ number_format($stats['total_masuk'], 0, ',', '.') }} <span class="text-sm font-semibold text-slate-500 dark:text-slate-400">Dokumen</span>
                        </div>
                        <div class="flex items-center gap-2 pt-0.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                {{ $stats['total_grs'] }} GRS
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400">
                                {{ $stats['total_rdtv'] }} RDTV
                            </span>
                        </div>
                    </div>
                    <div class="p-2.5 rounded-xl bg-sky-500/10 text-sky-500 dark:bg-sky-500/20 shrink-0">
                        <x-filament::icon
                            icon="heroicon-m-inbox-arrow-down"
                            class="w-6 h-6"
                        />
                    </div>
                </div>

                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-white/5 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Total Nilai Tagihan:</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200">
                        Rp {{ number_format($stats['total_nominal'], 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <!-- Widget 3: Invoice Selesai -->
            <div class="relative overflow-hidden rounded-xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-white/10 p-4 shadow-sm">
                <div class="flex items-start justify-between">
                    <div class="space-y-1 pr-2 min-w-0 flex-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                Invoice Selesai
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-black bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                {{ $stats['completion_rate'] }}%
                            </span>
                        </div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                            {{ number_format($stats['total_selesai'], 0, ',', '.') }} <span class="text-sm font-semibold text-slate-500 dark:text-slate-400">/ {{ number_format($stats['total_masuk'], 0, ',', '.') }} Dok</span>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 mt-1.5 overflow-hidden">
                            <div
                                class="bg-gradient-to-r from-emerald-500 to-teal-400 h-2 rounded-full transition-all duration-500"
                                style="width: {{ min(100, $stats['completion_rate']) }}%"
                            ></div>
                        </div>
                    </div>
                    <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-500 dark:bg-emerald-500/20 shrink-0 ml-2">
                        <x-filament::icon
                            icon="heroicon-m-check-badge"
                            class="w-6 h-6"
                        />
                    </div>
                </div>

                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-white/5 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Dalam Proses:</span>
                    <span class="font-bold text-amber-600 dark:text-amber-400">
                        {{ number_format($stats['total_proses'], 0, ',', '.') }} Dokumen
                    </span>
                </div>
            </div>

        </div>

        <!-- Modal Staff Invoice Workload List -->
        @if ($showStaffModal)
            @teleport('body')
                <div
                    class="fixed inset-0 z-[99999] overflow-y-auto overflow-x-hidden p-3 sm:p-6 bg-slate-950/70 backdrop-blur-md flex items-center justify-center transition-all"
                    wire:keydown.escape="closeStaffModal"
                    style="-webkit-overflow-scrolling: touch;"
                >
                    <div
                        class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-white/10 shadow-2xl overflow-hidden flex flex-col max-h-[85vh] sm:max-h-[85vh] my-auto animate-in fade-in zoom-in-95 duration-150"
                        @click.outside="$wire.closeStaffModal()"
                    >
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-4 sm:px-6 py-3.5 sm:py-4 border-b border-slate-200 dark:border-white/10 bg-slate-50/50 dark:bg-white/[0.02] shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-xl bg-amber-500/10 text-amber-500 dark:bg-amber-500/20 shrink-0">
                                <x-filament::icon
                                    icon="heroicon-m-user-group"
                                    class="w-5 h-5 sm:w-6 sm:h-6"
                                />
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-white truncate">
                                    Daftar Beban Kerja Staff Invoice
                                </h3>
                                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 truncate">
                                    Periode: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $periodLabel }}</span> &bull; Total {{ $stats['total_masuk'] }} Dokumen
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            wire:click="closeStaffModal"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-white/10 transition-colors shrink-0 ml-2"
                        >
                            <x-filament::icon
                                icon="heroicon-m-x-mark"
                                class="w-5 h-5"
                            />
                        </button>
                    </div>

                    <!-- Search Filter inside Modal -->
                    <div class="px-4 sm:px-6 py-2.5 sm:py-3 border-b border-slate-200/60 dark:border-white/5 bg-white dark:bg-slate-900 shrink-0">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <x-filament::icon
                                    icon="heroicon-m-magnifying-glass"
                                    class="w-4 h-4"
                                />
                            </div>
                            <input
                                type="text"
                                wire:model.live.debounce.250ms="staffSearch"
                                placeholder="Cari nama staff invoice..."
                                class="w-full pl-9 pr-4 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-black/20 rounded-xl border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-[#F47920] focus:border-[#F47920] outline-none transition-all"
                            >
                        </div>
                    </div>

                    <!-- Modal Body: Staff List Table (Fully Scrollable on Mobile) -->
                    <div
                        class="p-3 sm:p-6 overflow-y-auto overscroll-contain space-y-2.5 sm:space-y-3 flex-1 min-h-0 touch-pan-y"
                        style="-webkit-overflow-scrolling: touch;"
                    >
                        @forelse ($workloads as $index => $staff)
                            <div class="flex items-center justify-between p-3 sm:p-3.5 rounded-xl border border-slate-200/80 dark:border-white/10 bg-slate-50/50 dark:bg-white/[0.02] hover:bg-slate-100/70 dark:hover:bg-white/[0.04] transition-all">
                                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 pr-2">
                                    <!-- Rank Badge -->
                                    <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg flex items-center justify-center font-black text-[11px] sm:text-xs shrink-0
                                        @if ($index === 0 && $staff['total'] > 0)
                                            bg-amber-500 text-white shadow-sm shadow-amber-500/30
                                        @elseif ($index === 1 && $staff['total'] > 0)
                                            bg-slate-400 text-white
                                        @elseif ($index === 2 && $staff['total'] > 0)
                                            bg-amber-700 text-white
                                        @else
                                            bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400
                                        @endif
                                    ">
                                        #{{ $index + 1 }}
                                    </div>

                                    <!-- Staff Avatar & Name -->
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate">
                                                {{ $staff['name'] }}
                                            </span>
                                            @if ($index === 0 && $staff['total'] > 0)
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] sm:text-[10px] font-black bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 shrink-0">
                                                    Terbanyak
                                                </span>
                                            @endif
                                        </div>
                                        <span class="text-[10px] sm:text-[11px] text-slate-400 truncate block">
                                            {{ $staff['email'] ?: 'Staff Invoice' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Count & Badges -->
                                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                                    <div class="text-right">
                                        <div class="text-sm sm:text-base font-black text-slate-900 dark:text-white">
                                            {{ $staff['total'] }} <span class="text-[10px] sm:text-xs font-medium text-slate-500">Invoice</span>
                                        </div>
                                        <div class="flex items-center gap-1 sm:gap-1.5 text-[9px] sm:text-[10px] text-slate-400 justify-end">
                                            <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ $staff['selesai'] }} Selesai</span>
                                            <span>&bull;</span>
                                            <span class="text-amber-600 dark:text-amber-400 font-semibold">{{ $staff['proses'] }} Proses</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-12 text-center text-slate-400">
                                <x-filament::icon
                                    icon="heroicon-o-user-minus"
                                    class="w-10 h-10 mx-auto mb-2 text-slate-300 dark:text-slate-600"
                                />
                                <p class="text-sm font-medium">Tidak ada staff invoice yang sesuai dengan pencarian.</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-4 sm:px-6 py-2.5 sm:py-3 border-t border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-white/[0.02] flex items-center justify-between shrink-0">
                        <span class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">
                            Total <strong class="font-bold text-slate-700 dark:text-slate-300">{{ count($workloads) }} Staff</strong> terdaftar
                        </span>
                        <x-filament::button
                            color="gray"
                            size="sm"
                            wire:click="closeStaffModal"
                        >
                            Tutup
                        </x-filament::button>
                    </div>
                </div>
            @endteleport
        @endif
    </div>
</x-filament-widgets::widget>
