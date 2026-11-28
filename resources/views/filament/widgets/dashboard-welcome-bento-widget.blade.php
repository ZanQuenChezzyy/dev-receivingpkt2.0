<x-filament-widgets::widget>
    <div class="pkt-bento-card pkt-bento-welcome relative h-full rounded-[24px] bg-white dark:bg-[#151B24] border border-black/[0.06] dark:border-white/[0.08] shadow-xs overflow-hidden p-4 sm:p-5 flex flex-col gap-4">
        {{-- Subtle PKT Corporate Ambient Glow & Pattern Watermark --}}
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_0%_0%,rgba(0,84,166,0.08),transparent_48%),radial-gradient(circle_at_100%_100%,rgba(244,121,32,0.09),transparent_48%)] dark:bg-[radial-gradient(circle_at_0%_0%,rgba(0,84,166,0.20),transparent_52%),radial-gradient(circle_at_100%_100%,rgba(244,121,32,0.16),transparent_50%)]"></div>
        <div class="pointer-events-none absolute -top-10 -right-10 w-48 h-48 select-none overflow-hidden">
            <img
                src="{{ asset('images/pattern/pkt-pattern-dark.png') }}"
                alt=""
                class="w-full h-full object-contain dark:hidden opacity-[0.065]"
            />
            <img
                src="{{ asset('images/pattern/pkt-pattern-white.png') }}"
                alt=""
                class="w-full h-full object-contain hidden dark:block opacity-[0.055]"
            />
        </div>

        {{-- Row 1: Clean Greeting + Live Weather Pill (Bontang / PKT) --}}
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex flex-col min-w-0 gap-0.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-[#0054A6] dark:text-[#83A2DB]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#F47920]"></span>
                        Receiving PKT
                    </span>
                    @if ($missionSummary && $missionSummary['has_pending_gift'] && $badgeTabUrl)
                        <a
                            href="{{ $badgeTabUrl }}"
                            wire:navigate
                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#F47920]/15 text-[#F47920] dark:text-orange-300 border border-[#F47920]/30 hover:bg-[#F47920] hover:text-white transition-colors"
                        >
                            <x-heroicon-m-gift class="w-3 h-3" />
                            Hadiah Badge Tersedia
                        </a>
                    @endif
                </div>

                <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight text-[#10141A] dark:text-white leading-snug">
                    {{ $greeting }}, {{ $user?->name ?? 'Pengguna' }}
                </h2>

                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Pantau ringkasan operasional sesuai pintasan aktif dan aktivitas terbaru Anda hari ini.
                </p>
            </div>

            {{-- Right: Live Prakiraan Cuaca Bontang (PKT) --}}
            <div
                x-data="{
                    temp: null,
                    humidity: null,
                    wind: null,
                    label: 'Bontang, Kaltim',
                    icon: 'partly-cloudy',
                    loaded: false,
                    init() {
                        const cacheKey = 'pkt_weather_bontang_v1';
                        try {
                            const raw = localStorage.getItem(cacheKey);
                            if (raw) {
                                const parsed = JSON.parse(raw);
                                if (parsed && parsed.ts && (Date.now() - parsed.ts) < 1800000) {
                                    this.apply(parsed.data);
                                    return;
                                }
                            }
                        } catch (e) {}

                        fetch('https://api.open-meteo.com/v1/forecast?latitude=0.1333&longitude=117.5000&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m&timezone=Asia%2FMakassar')
                            .then((res) => res.ok ? res.json() : null)
                            .then((json) => {
                                if (!json || !json.current) return;
                                const data = {
                                    temp: Math.round(json.current.temperature_2m),
                                    humidity: Math.round(json.current.relative_humidity_2m),
                                    wind: Math.round(json.current.wind_speed_10m),
                                    code: Number(json.current.weather_code),
                                };
                                this.apply(data);
                                try {
                                    localStorage.setItem(cacheKey, JSON.stringify({ ts: Date.now(), data }));
                                } catch (e) {}
                            })
                            .catch(() => {});
                    },
                    apply(data) {
                        this.temp = data.temp;
                        this.humidity = data.humidity;
                        this.wind = data.wind;
                        this.loaded = true;
                        const code = data.code;
                        if (code === 0) {
                            this.label = 'Cerah';
                            this.icon = 'sun';
                        } else if (code >= 1 && code <= 2) {
                            this.label = 'Cerah Berawan';
                            this.icon = 'partly-cloudy';
                        } else if (code === 3 || (code >= 45 && code <= 48)) {
                            this.label = 'Berawan';
                            this.icon = 'cloud';
                        } else if ((code >= 51 && code <= 67) || (code >= 80 && code <= 82)) {
                            this.label = code >= 63 ? 'Hujan' : 'Hujan Ringan';
                            this.icon = 'rain';
                        } else if (code >= 95) {
                            this.label = 'Hujan Petir';
                            this.icon = 'storm';
                        } else {
                            this.label = 'Berawan';
                            this.icon = 'partly-cloudy';
                        }
                    }
                }"
                class="w-full md:w-auto md:min-w-[230px] shrink-0 rounded-2xl px-3.5 py-2.5 bg-slate-50/95 dark:bg-white/[0.04] border border-black/[0.06] dark:border-white/[0.08] flex items-center gap-3"
            >
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#0054A6]/12 to-[#F47920]/14 dark:from-[#0054A6]/25 dark:to-[#F47920]/20 flex items-center justify-center shrink-0 text-[#F47920]">
                    <template x-if="icon === 'sun'">
                        <svg class="w-6 h-6 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                    </template>
                    <template x-if="icon === 'partly-cloudy'">
                        <svg class="w-6 h-6 text-[#F47920]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v2M4.93 4.93l1.41 1.41M20 12h2M19.07 4.93l-1.41 1.41M15.95 10A4 4 0 0 0 8.5 8.1"/><path d="M17 19H7a4 4 0 0 1-.5-7.97A5.5 5.5 0 0 1 17.2 12H17a3.5 3.5 0 0 1 0 7Z" class="text-[#0054A6] dark:text-sky-300"/></svg>
                    </template>
                    <template x-if="icon === 'cloud'">
                        <svg class="w-6 h-6 text-slate-500 dark:text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
                    </template>
                    <template x-if="icon === 'rain'">
                        <svg class="w-6 h-6 text-sky-500 dark:text-sky-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M8 19v2M12 19v2M16 19v2"/></svg>
                    </template>
                    <template x-if="icon === 'storm'">
                        <svg class="w-6 h-6 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 16.326A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 .5 8.973"/><path d="m13 12-3 5h4l-3 5"/></svg>
                    </template>
                </div>

                <div class="flex flex-col min-w-0 leading-tight">
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-lg font-extrabold tracking-tight text-[#10141A] dark:text-white tabular-nums" x-text="loaded ? (temp + '°C') : 'Bontang'">Bontang</span>
                        <span class="text-xs font-semibold text-slate-600 dark:text-slate-300 truncate" x-text="loaded ? label : 'WITA (UTC+8)'">WITA (UTC+8)</span>
                    </div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate" x-show="loaded" x-cloak>
                        Bontang • Lembap <span class="font-semibold tabular-nums" x-text="humidity + '%'"></span> • <span class="tabular-nums" x-text="wind + ' km/j'"></span>
                    </div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate" x-show="!loaded">
                        Prakiraan Cuaca Area PKT
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 2: Ringkasan Operasional (Dynamic — mirrors the user's 3 selected shortcuts) --}}
        <div class="relative z-10 flex flex-col gap-2">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                    Ringkasan Operasional
                </span>
                @if ($missionSummary && ! $missionSummary['has_full_access'] && $badgeTabUrl)
                    <a
                        href="{{ $badgeTabUrl }}"
                        wire:navigate
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-[#0054A6]/10 dark:bg-[#83A2DB]/15 text-[#0054A6] dark:text-[#93C5FD] tabular-nums hover:opacity-85 transition-opacity"
                    >
                        Misi Anda: {{ number_format($missionSummary['mission_count'], 0, ',', '.') }} {{ $missionSummary['unit_label'] }}
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                @foreach ($operationalPulse as $metric)
                    @php
                        $iconClasses = match ($metric['accent']) {
                            'emerald' => 'bg-emerald-500/12 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300',
                            'orange' => 'bg-[#F47920]/12 text-[#F47920] dark:bg-[#F47920]/20 dark:text-orange-300',
                            default => 'bg-[#0054A6]/12 text-[#0054A6] dark:bg-[#83A2DB]/15 dark:text-[#93C5FD]',
                        };
                        $subPillClasses = $metric['sub_highlight']
                            ? 'bg-emerald-500/12 text-emerald-700 dark:text-emerald-300'
                            : 'bg-slate-200/75 dark:bg-white/[0.07] text-slate-500 dark:text-slate-400';
                    @endphp

                    @if ($metric['url'])
                        <a
                            href="{{ $metric['url'] }}"
                            wire:navigate
                            class="group rounded-2xl p-3 bg-slate-50/90 dark:bg-white/[0.03] border border-black/[0.05] dark:border-white/[0.07] hover:border-[#0054A6]/35 dark:hover:border-white/[0.16] hover:bg-white dark:hover:bg-white/[0.055] transition-all flex flex-col gap-2"
                        >
                    @else
                        <div class="rounded-2xl p-3 bg-slate-50/90 dark:bg-white/[0.03] border border-black/[0.05] dark:border-white/[0.07] flex flex-col gap-2">
                    @endif
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 min-w-0">
                                <span class="w-6 h-6 rounded-lg inline-flex items-center justify-center shrink-0 {{ $iconClasses }}">
                                    <x-filament::icon :icon="$metric['icon']" class="w-3.5 h-3.5" />
                                </span>
                                <span class="truncate">{{ $metric['label'] }}</span>
                            </span>
                            <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 shrink-0">
                                {{ $metric['period_label'] }}
                            </span>
                        </div>

                        <div class="flex items-baseline justify-between gap-2">
                            <div class="flex items-baseline gap-1.5 min-w-0">
                                <span class="text-xl font-extrabold tracking-tight text-[#10141A] dark:text-white tabular-nums">
                                    {{ number_format($metric['main_count'], 0, ',', '.') }}
                                </span>
                                <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 truncate">
                                    {{ $metric['unit'] }}
                                </span>
                            </div>

                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold tabular-nums shrink-0 {{ $subPillClasses }}">
                                {{ $metric['sub_label'] }}
                            </span>
                        </div>
                    @if ($metric['url'])
                        </a>
                    @else
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Row 3: Aktivitas Terbaru Anda (Fills Bento Card harmoniously with real user timeline) --}}
        <div class="relative z-10 flex flex-col gap-2 flex-1">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                    Aktivitas Terbaru Anda
                </span>
                @if ($memberProfileUrl)
                    <a
                        href="{{ $memberProfileUrl }}"
                        wire:navigate
                        class="text-[11px] font-semibold text-[#0054A6] dark:text-[#83A2DB] hover:text-[#F47920] dark:hover:text-orange-300 transition-colors"
                    >
                        Lihat Semua &rarr;
                    </a>
                @endif
            </div>

            @if ($recentActivities !== [])
                <div class="flex flex-col gap-1.5">
                    @foreach ($recentActivities as $act)
                        @php
                            $actDot = match ($act['accent'] ?? 'blue') {
                                'orange' => 'bg-[#F47920]/15 text-[#F47920] dark:text-orange-300',
                                'emerald' => 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300',
                                'amber' => 'bg-amber-500/15 text-amber-700 dark:text-amber-300',
                                'violet' => 'bg-violet-500/15 text-violet-700 dark:text-violet-300',
                                default => 'bg-[#0054A6]/12 text-[#0054A6] dark:bg-[#83A2DB]/15 dark:text-[#93C5FD]',
                            };
                            $actUrl = $act['url'] ?? null;
                        @endphp

                        @if ($actUrl)
                            <a
                                href="{{ $actUrl }}"
                                wire:navigate
                                class="group rounded-xl px-3 py-2 bg-slate-50/85 dark:bg-white/[0.025] border border-black/[0.05] dark:border-white/[0.06] hover:border-[#F47920]/35 hover:bg-white dark:hover:bg-white/[0.05] transition-all flex items-center justify-between gap-3"
                            >
                        @else
                            <div class="rounded-xl px-3 py-2 bg-slate-50/85 dark:bg-white/[0.025] border border-black/[0.05] dark:border-white/[0.06] flex items-center justify-between gap-3">
                        @endif
                            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                <span class="w-7 h-7 rounded-lg inline-flex items-center justify-center shrink-0 {{ $actDot }}">
                                    <x-filament::icon :icon="$act['icon'] ?? 'heroicon-o-clock'" class="w-3.5 h-3.5" />
                                </span>
                                <div class="flex flex-col min-w-0 leading-tight">
                                    <span class="text-xs font-semibold text-[#10141A] dark:text-white truncate group-hover:text-[#F47920] dark:group-hover:text-orange-300 transition-colors">
                                        {{ $act['title'] }}
                                    </span>
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                        {{ $act['category_label'] }}@if (! empty($act['subtitle'])) &bull; {{ $act['subtitle'] }}@endif
                                    </span>
                                </div>
                            </div>

                            <span class="text-[10px] font-medium text-slate-400 dark:text-slate-500 shrink-0 tabular-nums">
                                {{ $act['timestamp']->diffForHumans() }}
                            </span>
                        @if ($actUrl)
                            </a>
                        @else
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="rounded-xl px-3.5 py-3 bg-slate-50/80 dark:bg-white/[0.025] border border-dashed border-black/[0.08] dark:border-white/[0.08] text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between gap-2">
                    <span>Belum ada aktivitas operasional terbaru yang tercatat pada akun Anda.</span>
                    @if ($memberProfileUrl)
                        <a href="{{ $memberProfileUrl }}" wire:navigate class="text-[11px] font-semibold text-[#0054A6] dark:text-[#83A2DB] shrink-0">
                            Profil &rarr;
                        </a>
                    @endif
                </div>
            @endif
        </div>

        {{-- Row 4: 3 Quick Shortcuts (1-col Mobile -> 3-col Tablet/Desktop, anchored cleanly) --}}
        <div class="relative z-10 mt-auto pt-3 border-t border-black/[0.06] dark:border-white/[0.08] flex flex-col gap-2">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                    Pintasan Cepat
                </span>

                <button
                    type="button"
                    x-on:click="$dispatch('open-left-rail-settings')"
                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-500 hover:text-[#F47920] dark:text-slate-400 dark:hover:text-orange-300 transition-colors cursor-pointer"
                >
                    <x-heroicon-m-adjustments-horizontal class="w-3.5 h-3.5" />
                    Atur Pintasan
                </button>
            </div>

            @if ($shortcuts !== [])
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    @foreach ($shortcuts as $item)
                        <a
                            href="{{ $item['url'] }}"
                            wire:navigate
                            class="pkt-bento-shortcut group flex items-center gap-2.5 p-2.5 rounded-2xl bg-slate-50/90 dark:bg-white/[0.03] border border-black/[0.05] dark:border-white/[0.07] hover:border-[#F47920]/45 hover:bg-white dark:hover:bg-white/[0.06] transition-all duration-200"
                        >
                            <span class="w-8 h-8 rounded-xl bg-[#0054A6]/10 dark:bg-[#83A2DB]/15 text-[#0054A6] dark:text-[#93C5FD] group-hover:bg-[#F47920] group-hover:text-white inline-flex items-center justify-center shrink-0 transition-colors duration-200">
                                <x-filament::icon
                                    :icon="$item['icon']"
                                    class="w-4 h-4"
                                />
                            </span>
                            <div class="flex flex-col min-w-0 flex-1 leading-tight">
                                <span class="text-xs font-bold text-[#10141A] dark:text-white truncate group-hover:text-[#F47920] dark:group-hover:text-orange-300 transition-colors">
                                    {{ $item['label'] }}
                                </span>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                    {{ $item['group'] ?? 'Pintasan' }}
                                </span>
                            </div>
                            <x-heroicon-m-chevron-right class="w-3.5 h-3.5 text-slate-300 dark:text-slate-600 group-hover:text-[#F47920] group-hover:translate-x-0.5 transition-all shrink-0" />
                        </a>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl p-3 bg-slate-50/80 dark:bg-white/[0.03] border border-dashed border-black/10 dark:border-white/10 text-xs text-slate-500 dark:text-slate-400">
                    Belum ada pintasan yang diatur. Klik tombol <strong>Atur Pintasan</strong> untuk memilih menu favorit Anda.
                </div>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
