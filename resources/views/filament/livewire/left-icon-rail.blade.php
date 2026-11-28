<div class="contents">
    @if (filament()->auth()->check())
        <aside
            aria-label="Navigasi Cepat"
            class="pkt-left-rail hidden lg:flex flex-col items-center justify-between"
        >
            <!-- Top Stack: Locked Dashboard + User Custom Shortcuts + Settings Trigger -->
            <div class="flex flex-col items-center gap-2.5">
                @foreach ($railLinks as $link)
                    <a
                        {{ \Filament\Support\generate_href_html($link['url']) }}
                        title="{{ $link['label'] }}"
                        aria-label="{{ $link['label'] }}"
                        @class([
                            'pkt-rail-btn group',
                            'pkt-rail-btn-active' => $link['active'],
                        ])
                    >
                        <x-filament::icon :icon="$link['icon']" class="w-[18px] h-[18px]" />
                        <span class="pkt-rail-tooltip" role="tooltip">
                            {{ $link['label'] }}
                        </span>
                    </a>
                @endforeach

                <div class="w-5 h-px bg-slate-300/70 dark:bg-white/10 my-0.5"></div>

                <!-- Customize Left Navigation Button -->
                <button
                    type="button"
                    wire:click="openSettings"
                    title="Atur Menu Navigasi Kiri"
                    aria-label="Atur Menu Navigasi Kiri"
                    class="pkt-rail-btn pkt-rail-settings-btn group"
                >
                    <x-heroicon-o-cog-6-tooth class="w-[18px] h-[18px] transition-transform duration-300 group-hover:rotate-45" />
                    <span class="pkt-rail-tooltip" role="tooltip">Atur Navigasi Kiri</span>
                </button>
            </div>

            <!-- Bottom Vertical Dual-Circle Theme Toggle -->
            <div
                x-data="{
                    isDark: document.documentElement.classList.contains('dark'),
                    observer: null,
                    init() {
                        this.sync();
                        this.observer = new MutationObserver(() => this.sync());
                        this.observer.observe(document.documentElement, {
                            attributes: true,
                            attributeFilter: ['class'],
                        });
                    },
                    destroy() {
                        if (this.observer) {
                            this.observer.disconnect();
                        }
                    },
                    sync() {
                        this.isDark = document.documentElement.classList.contains('dark');
                    },
                    setMode(mode) {
                        localStorage.setItem('theme', mode);
                        document.cookie = 'theme=' + mode + '; path=/; max-age=31536000; SameSite=Lax';
                        this.$dispatch('theme-changed', mode);
                        this.$nextTick(() => this.sync());
                    },
                }"
                @theme-changed.window="$nextTick(() => sync())"
                class="pkt-rail-theme-pill"
                role="group"
                aria-label="Pengaturan Tema"
            >
                <button
                    type="button"
                    @click="setMode('dark')"
                    :aria-pressed="isDark ? 'true' : 'false'"
                    title="Mode Gelap"
                    aria-label="Mode Gelap"
                    class="pkt-rail-theme-btn"
                    :class="isDark ? 'pkt-rail-theme-btn-active' : ''"
                >
                    <x-heroicon-o-moon class="w-4 h-4" />
                </button>

                <button
                    type="button"
                    @click="setMode('light')"
                    :aria-pressed="!isDark ? 'true' : 'false'"
                    title="Mode Terang"
                    aria-label="Mode Terang"
                    class="pkt-rail-theme-btn"
                    :class="!isDark ? 'pkt-rail-theme-btn-active' : ''"
                >
                    <x-heroicon-o-sun class="w-4 h-4" />
                </button>
            </div>
        </aside>

        <!-- Mobile & Tablet Floating Bottom Navigation (MedTech 5-Slot Style + Center Saved-Shortcuts Sheet) -->
        @php
            $slotDashboard = $railLinks[0] ?? null;
            $slotSecond = $railLinks[1] ?? null;
            $slotFourth = $railLinks[2] ?? null;
        @endphp
        <div
            x-data="{ mobileShortcutsOpen: false }"
            @keydown.escape.window="mobileShortcutsOpen = false"
            class="lg:hidden"
        >
            <!-- Backdrop when Center Saved-Shortcuts Popup is Open -->
            <div
                x-cloak
                x-show="mobileShortcutsOpen"
                x-transition.opacity.duration.200ms
                @click="mobileShortcutsOpen = false"
                class="fixed inset-0 z-[37] bg-slate-950/45 backdrop-blur-[2px]"
            ></div>

            <!-- Floating Popup Sheet Showing User's Saved Left Navigation Menus -->
            <div
                x-cloak
                x-show="mobileShortcutsOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-3 scale-95"
                class="pkt-mobile-shortcuts-sheet"
                role="dialog"
                aria-label="Menu Navigasi Tersimpan"
            >
                <div class="flex items-center justify-between gap-2 pb-3 mb-3 border-b border-black/[0.06] dark:border-white/[0.08]">
                    <div>
                        <div class="text-xs font-bold text-[#10141A] dark:text-white">
                            Menu Pintasan Anda
                        </div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400">
                            Sesuai pengaturan navigasi kiri akun Anda
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="mobileShortcutsOpen = false; $wire.openSettings()"
                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-[11px] font-semibold bg-[#0054A6]/10 dark:bg-[#83A2DB]/15 text-[#0054A6] dark:text-[#83A2DB] hover:bg-[#F47920]/15 hover:text-[#F47920] transition-colors shrink-0"
                    >
                        <x-heroicon-o-cog-6-tooth class="w-3.5 h-3.5" />
                        Atur
                    </button>
                </div>

                <div class="grid grid-cols-3 sm:grid-cols-4 gap-2 max-h-[48dvh] overflow-y-auto pr-0.5">
                    @foreach ($railLinks as $link)
                        <a
                            {{ \Filament\Support\generate_href_html($link['url']) }}
                            @click="mobileShortcutsOpen = false"
                            @class([
                                'pkt-shortcut-tile',
                                'pkt-shortcut-tile-active' => $link['active'],
                            ])
                        >
                            <span class="pkt-shortcut-tile-icon">
                                <x-filament::icon :icon="$link['icon']" class="w-5 h-5" />
                            </span>
                            <span class="pkt-shortcut-tile-label">{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- 5-Slot Floating Bottom Navigation Bar -->
            <nav
                aria-label="Navigasi Bawah Mobile"
                class="pkt-mobile-dock"
            >
                <div class="pkt-mobile-dock-bar">
                    <!-- Slot 1: Locked Dashboard -->
                    @if ($slotDashboard)
                        <a
                            {{ \Filament\Support\generate_href_html($slotDashboard['url']) }}
                            @click="mobileShortcutsOpen = false"
                            @class([
                                'pkt-dock-slot',
                                'pkt-dock-slot-active' => $slotDashboard['active'],
                            ])
                        >
                            <x-filament::icon :icon="$slotDashboard['icon']" class="w-5 h-5" />
                            <span class="pkt-dock-label">{{ $slotDashboard['short_label'] }}</span>
                        </a>
                    @endif

                    <!-- Slot 2: User's 1st Saved Shortcut -->
                    @if ($slotSecond)
                        <a
                            {{ \Filament\Support\generate_href_html($slotSecond['url']) }}
                            @click="mobileShortcutsOpen = false"
                            @class([
                                'pkt-dock-slot',
                                'pkt-dock-slot-active' => $slotSecond['active'],
                            ])
                        >
                            <x-filament::icon :icon="$slotSecond['icon']" class="w-5 h-5" />
                            <span class="pkt-dock-label">{{ $slotSecond['short_label'] }}</span>
                        </a>
                    @else
                        <button
                            type="button"
                            @click="mobileShortcutsOpen = false; $wire.openSettings()"
                            class="pkt-dock-slot"
                        >
                            <x-heroicon-o-plus-circle class="w-5 h-5" />
                            <span class="pkt-dock-label">Tambah</span>
                        </button>
                    @endif

                    <!-- Slot 3 (Center): Elevated Circular Button -> Opens User's Saved Left Nav Menu -->
                    <div class="pkt-dock-center-wrap">
                        <button
                            type="button"
                            @click="mobileShortcutsOpen = !mobileShortcutsOpen"
                            :aria-expanded="mobileShortcutsOpen ? 'true' : 'false'"
                            title="Menu Pintasan Saya"
                            aria-label="Buka Menu Pintasan Saya"
                            class="pkt-dock-center-fab"
                            :class="mobileShortcutsOpen ? 'pkt-dock-center-fab-open' : ''"
                        >
                            <x-heroicon-o-squares-plus x-show="!mobileShortcutsOpen" class="w-6 h-6" />
                            <x-heroicon-m-x-mark x-show="mobileShortcutsOpen" x-cloak class="w-6 h-6" />
                        </button>
                    </div>

                    <!-- Slot 4: User's 2nd Saved Shortcut -->
                    @if ($slotFourth)
                        <a
                            {{ \Filament\Support\generate_href_html($slotFourth['url']) }}
                            @click="mobileShortcutsOpen = false"
                            @class([
                                'pkt-dock-slot',
                                'pkt-dock-slot-active' => $slotFourth['active'],
                            ])
                        >
                            <x-filament::icon :icon="$slotFourth['icon']" class="w-5 h-5" />
                            <span class="pkt-dock-label">{{ $slotFourth['short_label'] }}</span>
                        </a>
                    @else
                        <button
                            type="button"
                            @click="mobileShortcutsOpen = false; $wire.openSettings()"
                            class="pkt-dock-slot"
                        >
                            <x-heroicon-o-cog-6-tooth class="w-5 h-5" />
                            <span class="pkt-dock-label">Atur</span>
                        </button>
                    @endif

                    <!-- Slot 5: Full Menu Drawer -->
                    <button
                        type="button"
                        @click="mobileShortcutsOpen = false; $store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open()"
                        title="Semua Menu"
                        aria-label="Buka Semua Menu"
                        class="pkt-dock-slot"
                    >
                        <x-heroicon-o-bars-3-bottom-right class="w-5 h-5" />
                        <span class="pkt-dock-label">Menu</span>
                    </button>
                </div>
            </nav>
        </div>

        <!-- Per-User Left Rail Customization Modal (Responsive Bottom-Sheet on Mobile, Centered Card on Desktop) -->
        @if ($showSettingsModal)
            <div
                x-data="{}"
                @keydown.escape.window="$wire.closeSettings()"
                class="fixed inset-0 z-[99990] flex items-end sm:items-center justify-center p-0 sm:p-6"
                role="dialog"
                aria-modal="true"
                aria-labelledby="left-rail-settings-title"
            >
                <!-- Backdrop -->
                <div
                    wire:click="closeSettings"
                    class="fixed inset-0 bg-slate-950/55 backdrop-blur-xs transition-opacity"
                ></div>

                <!-- Modal Window -->
                <div class="relative z-10 w-full max-w-4xl max-h-[92dvh] sm:max-h-[88vh] flex flex-col rounded-t-[28px] sm:rounded-[28px] bg-white dark:bg-[#151B24] border border-black/[0.08] dark:border-white/[0.09] shadow-2xl overflow-hidden">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-black/[0.06] dark:border-white/[0.08] flex items-center justify-between gap-4 shrink-0">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-2xl bg-[#F47920]/12 text-[#F47920] flex items-center justify-center shrink-0">
                                <x-heroicon-o-adjustments-horizontal class="w-5 h-5" />
                            </div>
                            <div class="min-w-0">
                                <h2 id="left-rail-settings-title" class="text-base font-bold text-[#10141A] dark:text-white tracking-tight">
                                    Atur Menu Navigasi Kiri
                                </h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Pilih hingga {{ $maxShortcuts }} pintasan menu favorit Anda. Pengaturan ini tersimpan khusus untuk akun Anda.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            wire:click="closeSettings"
                            aria-label="Tutup"
                            class="w-9 h-9 rounded-full bg-slate-100 dark:bg-white/[0.07] text-slate-500 dark:text-slate-300 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/20 dark:hover:text-rose-400 inline-flex items-center justify-center transition-colors shrink-0"
                        >
                            <x-heroicon-m-x-mark class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Body (2 Columns on Desktop) -->
                    <div class="flex-1 overflow-y-auto p-6 grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Left Column: Active Order -->
                        <div class="lg:col-span-5 flex flex-col gap-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Susunan Aktif
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#0054A6]/10 dark:bg-[#83A2DB]/20 text-[#0054A6] dark:text-[#83A2DB]">
                                    {{ count($selectedKeys) }} / {{ $maxShortcuts }} dipilih
                                </span>
                            </div>

                            <!-- Locked Dashboard Item (Always #1) -->
                            <div class="flex items-center gap-3 p-3 rounded-2xl bg-[#F47920]/[0.08] dark:bg-[#F47920]/[0.14] border border-[#F47920]/30">
                                <span class="w-9 h-9 rounded-xl bg-[#F47920] text-white inline-flex items-center justify-center shrink-0 shadow-xs">
                                    <x-filament::icon :icon="$dashboardItem['icon']" class="w-4 h-4" />
                                </span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-bold text-[#10141A] dark:text-white truncate">
                                        {{ $dashboardItem['label'] }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                        Posisi teratas (wajib)
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-white/90 dark:bg-black/30 text-[#C95B0C] dark:text-orange-300 border border-[#F47920]/25 shrink-0">
                                    <x-heroicon-m-lock-closed class="w-3 h-3" />
                                    Terkunci
                                </span>
                            </div>

                            <!-- User Selected Shortcuts -->
                            <div class="space-y-2">
                                @forelse ($selectedKeys as $index => $selectedKey)
                                    @if (isset($availableItems[$selectedKey]))
                                        @php($sel = $availableItems[$selectedKey])
                                        <div
                                            wire:key="rail-selected-{{ $selectedKey }}"
                                            class="flex items-center gap-2.5 p-2.5 rounded-2xl bg-slate-50 dark:bg-white/[0.04] border border-black/[0.06] dark:border-white/[0.08]"
                                        >
                                            <span class="w-8 h-8 rounded-xl bg-white dark:bg-white/[0.07] text-[#0054A6] dark:text-[#83A2DB] border border-black/[0.05] dark:border-white/[0.08] inline-flex items-center justify-center shrink-0">
                                                <x-filament::icon :icon="$sel['icon']" class="w-4 h-4" />
                                            </span>
                                            <span class="flex-1 min-w-0 text-xs font-semibold text-slate-800 dark:text-slate-100 truncate">
                                                {{ $sel['label'] }}
                                            </span>
                                            <div class="flex items-center gap-1 shrink-0">
                                                <button
                                                    type="button"
                                                    wire:click="moveShortcutUp({{ $index }})"
                                                    @disabled($index === 0)
                                                    title="Geser ke atas"
                                                    class="w-7 h-7 rounded-full inline-flex items-center justify-center text-slate-500 hover:bg-slate-200/80 dark:hover:bg-white/10 disabled:opacity-30 transition-colors"
                                                >
                                                    <x-heroicon-m-chevron-up class="w-4 h-4" />
                                                </button>
                                                <button
                                                    type="button"
                                                    wire:click="moveShortcutDown({{ $index }})"
                                                    @disabled($index === count($selectedKeys) - 1)
                                                    title="Geser ke bawah"
                                                    class="w-7 h-7 rounded-full inline-flex items-center justify-center text-slate-500 hover:bg-slate-200/80 dark:hover:bg-white/10 disabled:opacity-30 transition-colors"
                                                >
                                                    <x-heroicon-m-chevron-down class="w-4 h-4" />
                                                </button>
                                                <button
                                                    type="button"
                                                    wire:click="toggleShortcut('{{ $selectedKey }}')"
                                                    title="Hapus dari rail"
                                                    class="w-7 h-7 rounded-full inline-flex items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/15 transition-colors"
                                                >
                                                    <x-heroicon-m-x-mark class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                @empty
                                    <div class="p-4 rounded-2xl border border-dashed border-slate-300 dark:border-white/15 text-center text-xs text-slate-500 dark:text-slate-400">
                                        Belum ada menu tambahan dipilih. Klik menu di sebelah kanan untuk menambahkan.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Right Column: Available Catalog -->
                        <div class="lg:col-span-7 flex flex-col gap-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Menu yang Tersedia untuk Role Anda
                                </span>
                            </div>

                            <!-- Search Box -->
                            <div class="relative">
                                <x-heroicon-m-magnifying-glass class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                                <input
                                    type="text"
                                    wire:model.live.debounce.150ms="search"
                                    placeholder="Cari nama menu..."
                                    class="w-full pl-9 pr-4 py-2 text-xs rounded-full bg-slate-50 dark:bg-[#0F141C] border border-black/[0.1] dark:border-white/[0.1] text-slate-800 dark:text-slate-100 placeholder:text-slate-400 focus:border-[#F47920] focus:ring-2 focus:ring-[#F47920]/20 focus:outline-none"
                                />
                            </div>

                            <!-- Grouped Options -->
                            <div class="space-y-4 max-h-[46vh] overflow-y-auto pr-1 scanner-composer-scroll">
                                @forelse ($groupedItems as $groupName => $items)
                                    <div class="space-y-2">
                                        <div class="text-[11px] font-bold uppercase tracking-wider text-[#0054A6] dark:text-[#83A2DB] px-1">
                                            {{ $groupName }}
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            @foreach ($items as $item)
                                                @php($isSelected = in_array($item['key'], $selectedKeys, true))
                                                <button
                                                    type="button"
                                                    wire:key="rail-opt-{{ $item['key'] }}"
                                                    wire:click="toggleShortcut('{{ $item['key'] }}')"
                                                    @class([
                                                        'flex items-center gap-2.5 p-2.5 rounded-2xl border text-left transition-all cursor-pointer',
                                                        'bg-[#F47920]/10 dark:bg-[#F47920]/15 border-[#F47920]/50 text-[#10141A] dark:text-white' => $isSelected,
                                                        'bg-white dark:bg-white/[0.03] border-black/[0.07] dark:border-white/[0.08] text-slate-700 dark:text-slate-200 hover:border-[#0054A6]/40 dark:hover:border-[#83A2DB]/40 hover:bg-slate-50 dark:hover:bg-white/[0.06]' => ! $isSelected,
                                                    ])
                                                >
                                                    <span @class([
                                                        'w-8 h-8 rounded-xl inline-flex items-center justify-center shrink-0 transition-colors',
                                                        'bg-[#F47920] text-white' => $isSelected,
                                                        'bg-slate-100 dark:bg-white/[0.07] text-[#0054A6] dark:text-[#83A2DB]' => ! $isSelected,
                                                    ])>
                                                        <x-filament::icon :icon="$item['icon']" class="w-4 h-4" />
                                                    </span>
                                                    <span class="flex-1 min-w-0 text-xs font-semibold truncate">
                                                        {{ $item['label'] }}
                                                    </span>
                                                    @if ($isSelected)
                                                        <x-heroicon-s-check-circle class="w-4 h-4 text-[#F47920] shrink-0" />
                                                    @else
                                                        <x-heroicon-o-plus-circle class="w-4 h-4 text-slate-400 shrink-0" />
                                                    @endif
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-6 text-center text-xs text-slate-500 dark:text-slate-400">
                                        Menu tidak ditemukan.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-black/[0.06] dark:border-white/[0.08] bg-slate-50/70 dark:bg-white/[0.02] flex flex-wrap items-center justify-between gap-3 shrink-0">
                        <button
                            type="button"
                            wire:click="resetToDefaults"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-white/10 transition-colors"
                        >
                            <x-heroicon-o-arrow-path class="w-4 h-4" />
                            Reset Bawaan
                        </button>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                wire:click="closeSettings"
                                class="px-4 py-2 rounded-full text-xs font-semibold text-slate-600 dark:text-slate-300 bg-white dark:bg-white/[0.07] border border-black/[0.08] dark:border-white/[0.1] hover:bg-slate-100 dark:hover:bg-white/[0.12] transition-colors"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                wire:click="saveSettings"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-full text-xs font-bold text-white bg-[#F47920] hover:bg-[#e06512] shadow-sm transition-all disabled:opacity-60"
                            >
                                <x-heroicon-m-check class="w-4 h-4" />
                                <span wire:loading.remove wire:target="saveSettings">Simpan Pengaturan</span>
                                <span wire:loading wire:target="saveSettings">Menyimpan...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
