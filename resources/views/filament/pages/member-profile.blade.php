<x-filament-panels::page>
    @php
        $member = $this->profileUser;
        $isOwnProfile = (int) filament()->auth()->id() === (int) $member->getKey();
        $canCustomize = $member->canCustomizeAnimatedBadge();
        $badgeStyle = $member->resolvedDeveloperBadgeStyle();
        $hasAnimatedRing = $canCustomize && \App\Models\User::isExclusiveBadgeStyle($badgeStyle);
        $roleLabel = $member->primaryAnimatedBadgeRole() ?? ($member->roles->first()?->name ?? 'User');
        $avatarUrl = $member->getFilamentAvatarUrl()
            ?? 'https://ui-avatars.com/api/?name='.urlencode($member->name).'&color=FFFFFF&background=F47920';
        $coverUrl = $member->getCoverPhotoUrl();
        $isOnline = $member->isOnline();
        $summary = $this->summaryCounts;
        $feed = $this->activityFeed;
    @endphp

    <div class="space-y-6">
        <!-- 1. Hero Profile Banner Card -->
        <div class="rounded-[24px] bg-white dark:bg-[#151B24] border border-black/[0.06] dark:border-white/[0.08] shadow-sm overflow-hidden">
            <!-- Cover Photo -->
            <div class="relative w-full h-36 sm:h-52 overflow-hidden bg-slate-900">
                @if ($coverUrl)
                    <img src="{{ $coverUrl }}" alt="Foto Sampul {{ e($member->name) }}" class="w-full h-full object-cover" />
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-slate-950/15 to-transparent"></div>
                @else
                    <div class="w-full h-full bg-gradient-to-r from-[#0054A6] via-[#0B67C2] to-[#F47920]"></div>
                    <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 50%, #ffffff 1px, transparent 1px), radial-gradient(circle at 80% 30%, #ffffff 1px, transparent 1px); background-size: 22px 22px;"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/35 via-transparent to-transparent"></div>
                @endif

                <!-- Top Floating Action Bar -->
                <div class="absolute top-3.5 left-4 right-4 flex items-center justify-between gap-2">
                    <button
                        type="button"
                        x-on:click="document.referrer ? window.history.back() : Livewire.navigate('{{ filament()->getHomeUrl() }}')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-black/35 hover:bg-black/55 text-white backdrop-blur-md border border-white/15 transition-colors cursor-pointer"
                    >
                        <x-heroicon-m-arrow-left class="w-3.5 h-3.5" />
                        Kembali
                    </button>

                    @if ($isOwnProfile)
                        <a
                            href="{{ filament()->getProfileUrl() ?? \App\Filament\Pages\EditProfile::getUrl() }}"
                            wire:navigate
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-[#F47920] hover:bg-[#e06512] text-white shadow-sm transition-colors"
                        >
                            <x-heroicon-m-pencil-square class="w-3.5 h-3.5" />
                            Ubah Profil Saya
                        </a>
                    @endif
                </div>
            </div>

            <!-- Identity Strip -->
            <div class="px-5 sm:px-7 pt-4 pb-6 flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div class="flex flex-col sm:flex-row items-center sm:items-center gap-4 sm:gap-5 min-w-0">
                    <!-- Avatar + Online Dot -->
                    <div class="relative shrink-0 -mt-14 sm:-mt-16 z-10">
                        <div @class([
                            'relative inline-flex items-center justify-center shadow-xl',
                            "pkt-dev-avatar-ring pkt-dev-avatar-ring--{$badgeStyle}" => $hasAnimatedRing,
                            'ring-4 ring-white dark:ring-[#151B24] rounded-full' => ! $hasAnimatedRing,
                        ])>
                            <img
                                src="{{ $avatarUrl }}"
                                alt="{{ e($member->name) }}"
                                class="w-24 h-24 rounded-full object-cover relative z-10 bg-white dark:bg-[#151B24]"
                            />
                        </div>
                        @if ($isOnline)
                            <span class="absolute bottom-2 right-2 z-20 flex h-3.5 w-3.5 items-center justify-center" title="Sedang Online">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500 ring-2 ring-white dark:ring-[#151B24]"></span>
                            </span>
                        @endif
                    </div>

                    <!-- Name, Roles & Contact -->
                    <div class="flex flex-col items-center sm:items-start text-center sm:text-left min-w-0 gap-1.5">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5">
                            <h1 class="text-xl sm:text-2xl font-bold text-[#10141A] dark:text-white tracking-tight leading-tight">
                                {{ $member->name }}
                            </h1>
                            {!! \App\Support\DeveloperBadgeRenderer::renderUserRoles($member, 'md') !!}
                        </div>

                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-x-2.5 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                            <span class="font-mono font-semibold text-slate-700 dark:text-slate-300">
                                NPK {{ $member->npk ?: '-' }}
                            </span>
                            <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                            <span class="break-all">{{ $member->email }}</span>
                            @if ($member->created_at)
                                <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                                <span>Bergabung {{ $member->created_at->translatedFormat('M Y') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Right Status Pill -->
                <div class="flex items-center justify-center md:justify-end gap-2 shrink-0">
                    @if ($isOnline)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/12 text-emerald-700 dark:text-emerald-300 border border-emerald-500/25">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Online
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20">
                            <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                            Offline
                        </span>
                    @endif

                    @if (! $member->is_active)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/12 text-rose-600 dark:text-rose-400 border border-rose-500/25">
                            Nonaktif
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- 2. Summary KPI Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="p-4 sm:p-5 rounded-[20px] bg-white dark:bg-[#151B24] border border-black/[0.06] dark:border-white/[0.08] shadow-xs">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Total Aktivitas</div>
                <div class="mt-1.5 text-2xl font-extrabold text-[#10141A] dark:text-white tabular-nums">
                    {{ number_format($summary['total'], 0, ',', '.') }}
                </div>
                <div class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Tercatat di seluruh modul</div>
            </div>

            <div class="p-4 sm:p-5 rounded-[20px] bg-white dark:bg-[#151B24] border border-black/[0.06] dark:border-white/[0.08] shadow-xs">
                <div class="text-[11px] font-bold uppercase tracking-wider text-[#F47920]">Penerimaan & GRS</div>
                <div class="mt-1.5 text-2xl font-extrabold text-[#10141A] dark:text-white tabular-nums">
                    {{ number_format($summary['receiving_grs'], 0, ',', '.') }}
                </div>
                <div class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">DO, GRS/RDTV, & QC</div>
            </div>

            <div class="p-4 sm:p-5 rounded-[20px] bg-white dark:bg-[#151B24] border border-black/[0.06] dark:border-white/[0.08] shadow-xs">
                <div class="text-[11px] font-bold uppercase tracking-wider text-[#0054A6] dark:text-[#83A2DB]">Gudang & MIR</div>
                <div class="mt-1.5 text-2xl font-extrabold text-[#10141A] dark:text-white tabular-nums">
                    {{ number_format($summary['warehouse_mir'], 0, ',', '.') }}
                </div>
                <div class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Transmittal, MB51, & MIR</div>
            </div>

            <div class="p-4 sm:p-5 rounded-[20px] bg-white dark:bg-[#151B24] border border-black/[0.06] dark:border-white/[0.08] shadow-xs">
                <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Invoice & Monitoring</div>
                <div class="mt-1.5 text-2xl font-extrabold text-[#10141A] dark:text-white tabular-nums">
                    {{ number_format($summary['invoice_monitoring'], 0, ',', '.') }}
                </div>
                <div class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Invoice, Kasir, & Monitoring</div>
            </div>
        </div>

        <!-- 3. Activity Log Timeline -->
        <div class="rounded-[24px] bg-white dark:bg-[#151B24] border border-black/[0.06] dark:border-white/[0.08] shadow-sm overflow-hidden">
            <!-- Header & Controls -->
            <div class="p-5 sm:p-6 border-b border-black/[0.06] dark:border-white/[0.08] space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-[#10141A] dark:text-white tracking-tight">
                            Log Aktivitas Sistem
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Riwayat tindakan terbaru yang dilakukan oleh {{ $member->name }} di sistem Receiving PKT.
                        </p>
                    </div>

                    <!-- Search Box -->
                    <div class="relative w-full sm:w-72">
                        <x-heroicon-m-magnifying-glass class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Cari no. DO, PO, GRS, Transmittal..."
                            class="w-full pl-9 pr-4 py-2 text-xs rounded-full bg-slate-50 dark:bg-[#0F141C] border border-black/[0.09] dark:border-white/[0.1] text-slate-800 dark:text-slate-100 placeholder:text-slate-400 focus:border-[#F47920] focus:ring-2 focus:ring-[#F47920]/20 focus:outline-none"
                        />
                    </div>
                </div>

                <!-- Category Filter Pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
                    @foreach ($this->categoryOptions as $catKey => $catLabel)
                        <button
                            type="button"
                            wire:click="setCategory('{{ $catKey }}')"
                            @class([
                                'px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all cursor-pointer shrink-0',
                                'bg-[#F47920] text-white shadow-xs' => $category === $catKey,
                                'bg-slate-100 dark:bg-white/[0.05] text-slate-600 dark:text-slate-300 hover:bg-slate-200/80 dark:hover:bg-white/[0.1]' => $category !== $catKey,
                            ])
                        >
                            {{ $catLabel }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Timeline Items -->
            <div class="divide-y divide-black/[0.05] dark:divide-white/[0.06]" wire:loading.class="opacity-60">
                @forelse ($feed['items'] as $item)
                    @php
                        $iconColorClass = match ($item['accent']) {
                            'orange' => 'bg-[#F47920]/12 text-[#F47920] dark:bg-[#F47920]/20 dark:text-orange-300',
                            'blue' => 'bg-[#0054A6]/12 text-[#0054A6] dark:bg-[#83A2DB]/20 dark:text-[#93C5FD]',
                            'emerald' => 'bg-emerald-500/12 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-300',
                            'amber' => 'bg-amber-500/12 text-amber-600 dark:bg-amber-500/20 dark:text-amber-300',
                            default => 'bg-violet-500/12 text-violet-600 dark:bg-violet-500/20 dark:text-violet-300',
                        };
                    @endphp
                    <div wire:key="act-{{ $item['id'] }}" class="px-5 sm:px-6 py-4 flex items-start gap-3.5 hover:bg-slate-50/70 dark:hover:bg-white/[0.02] transition-colors">
                        <span class="w-9 h-9 rounded-2xl inline-flex items-center justify-center shrink-0 mt-0.5 {{ $iconColorClass }}">
                            <x-filament::icon :icon="$item['icon']" class="w-4 h-4" />
                        </span>

                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-1 sm:gap-4">
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-[#10141A] dark:text-white leading-snug">
                                        {{ $item['title'] }}
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                                        {{ $item['subtitle'] }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-2 shrink-0 sm:text-right">
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500 whitespace-nowrap" title="{{ $item['timestamp']->format('d/m/Y H:i:s') }}">
                                        <span class="font-medium text-slate-600 dark:text-slate-300">{{ $item['timestamp']->translatedFormat('d M Y, H:i') }}</span>
                                        <span class="hidden sm:inline"> &bull; {{ $item['timestamp']->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 dark:bg-white/[0.07] text-slate-600 dark:text-slate-300">
                                        {{ $item['category_label'] }}
                                    </span>
                                    @foreach ($item['meta'] as $chip)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-50 dark:bg-white/[0.04] text-slate-500 dark:text-slate-400 border border-black/[0.05] dark:border-white/[0.07]">
                                            {{ $chip }}
                                        </span>
                                    @endforeach
                                </div>

                                @if ($item['url'])
                                    <a
                                        href="{{ $item['url'] }}"
                                        wire:navigate
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-[#0054A6] dark:text-[#83A2DB] hover:text-[#F47920] dark:hover:text-orange-400 transition-colors"
                                    >
                                        Lihat Dokumen
                                        <x-heroicon-m-arrow-right class="w-3.5 h-3.5" />
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-12 text-center">
                        <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 dark:bg-white/[0.06] text-slate-400 inline-flex items-center justify-center mb-3">
                            <x-heroicon-o-clipboard-document-list class="w-6 h-6" />
                        </div>
                        <div class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                            Belum ada aktivitas ditemukan
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Coba ubah filter kategori atau kata kunci pencarian.
                        </p>
                    </div>
                @endforelse
            </div>

            @if ($feed['has_more'])
                <div class="px-6 py-4 border-t border-black/[0.05] dark:border-white/[0.06] text-center bg-slate-50/50 dark:bg-white/[0.01]">
                    <button
                        type="button"
                        wire:click="loadMore"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-xs font-semibold bg-white dark:bg-white/[0.06] border border-black/[0.08] dark:border-white/[0.1] text-slate-700 dark:text-slate-200 hover:border-[#F47920] hover:text-[#F47920] transition-colors cursor-pointer"
                    >
                        <span wire:loading.remove wire:target="loadMore">Muat Aktivitas Lebih Banyak</span>
                        <span wire:loading wire:target="loadMore">Memuat...</span>
                    </button>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
