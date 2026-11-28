<div
    class="fi-topbar-ctn pkt-topbar-ctn"
    x-data="{
        openMenu: null,
        closeTimer: null,
        mobileSearchOpen: false,
        openOnHover(key) {
            if (this.closeTimer) {
                clearTimeout(this.closeTimer);
                this.closeTimer = null;
            }
            this.openMenu = key;
        },
        scheduleClose() {
            if (this.closeTimer) {
                clearTimeout(this.closeTimer);
            }
            this.closeTimer = setTimeout(() => {
                this.openMenu = null;
                this.closeTimer = null;
            }, 160);
        },
        cancelClose() {
            if (this.closeTimer) {
                clearTimeout(this.closeTimer);
                this.closeTimer = null;
            }
        },
        toggleMenu(key) {
            this.cancelClose();
            this.openMenu = this.openMenu === key ? null : key;
        },
        closeMenu() {
            this.cancelClose();
            this.openMenu = null;
        },
        toggleMobileSearch() {
            this.mobileSearchOpen = !this.mobileSearchOpen;
            if (this.mobileSearchOpen) {
                this.$nextTick(() => {
                    this.$el.querySelector('.fi-global-search-field input')?.focus();
                });
            }
        }
    }"
    :class="{ 'pkt-mobile-search-open': mobileSearchOpen }"
    @keydown.escape.window="closeMenu(); mobileSearchOpen = false"
    @click.outside="closeMenu(); mobileSearchOpen = false"
>
    @php
        $hasNavigation = filament()->hasNavigation();
        $hasTenancy = filament()->hasTenancy();
    @endphp

    <nav class="fi-topbar pkt-topbar">
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_START) }}

        @if ($hasNavigation)
            <x-filament::icon-button
                color="gray"
                :icon="\Filament\Support\Icons\Heroicon::OutlinedBars3"
                :icon-alias="\Filament\View\PanelsIconAlias::TOPBAR_OPEN_SIDEBAR_BUTTON"
                icon-size="lg"
                :label="__('filament-panels::layout.actions.sidebar.expand.label')"
                x-cloak
                x-data="{}"
                x-on:click="$store.sidebar.open()"
                x-show="! $store.sidebar.isOpen"
                class="fi-topbar-open-sidebar-btn hidden sm:inline-flex lg:hidden"
            />

            <x-filament::icon-button
                color="gray"
                :icon="\Filament\Support\Icons\Heroicon::OutlinedXMark"
                :icon-alias="\Filament\View\PanelsIconAlias::TOPBAR_CLOSE_SIDEBAR_BUTTON"
                icon-size="lg"
                :label="__('filament-panels::layout.actions.sidebar.collapse.label')"
                x-cloak
                x-data="{}"
                x-on:click="$store.sidebar.close()"
                x-show="$store.sidebar.isOpen"
                class="fi-topbar-close-sidebar-btn hidden sm:inline-flex lg:hidden"
            />
        @endif

        <div class="fi-topbar-start pkt-topbar-brand">
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_LOGO_BEFORE) }}

            @if ($homeUrl = filament()->getHomeUrl())
                <a {{ \Filament\Support\generate_href_html($homeUrl) }} class="pkt-brand-link">
                    <x-filament-panels::logo />
                </a>
            @else
                <x-filament-panels::logo />
            @endif

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_LOGO_AFTER) }}
        </div>

        @if ($hasNavigation)
            @if ($hasTenancy && filament()->hasTenantMenu())
                <x-filament-panels::tenant-menu teleport />
            @endif

            <!-- Desktop Pill Navigation + Structured Mega Menu -->
            <div class="pkt-mega-nav hidden lg:flex items-center justify-center gap-1 flex-1 min-w-0 px-2">
                @foreach ($megaMenu as $entry)
                    @if ($entry['type'] === 'link')
                        <a
                            {{ \Filament\Support\generate_href_html($entry['url'], $entry['open_in_new_tab'] ?? false) }}
                            @mouseenter="closeMenu()"
                            @click="closeMenu()"
                            @class([
                                'pkt-nav-pill',
                                'pkt-nav-pill-active' => $entry['is_active'],
                            ])
                        >
                            <span>{{ $entry['label'] }}</span>
                        </a>
                    @else
                        <div
                            class="relative"
                            @mouseenter="openOnHover('{{ $entry['key'] }}')"
                            @mouseleave="scheduleClose()"
                        >
                            <button
                                type="button"
                                @click="toggleMenu('{{ $entry['key'] }}')"
                                :aria-expanded="openMenu === '{{ $entry['key'] }}' ? 'true' : 'false'"
                                @class([
                                    'pkt-nav-pill',
                                    'pkt-nav-pill-active' => $entry['is_active'],
                                ])
                                :class="{ 'pkt-nav-pill-open': openMenu === '{{ $entry['key'] }}' }"
                            >
                                <span>{{ $entry['label'] }}</span>
                                <x-heroicon-m-chevron-down
                                    class="w-3.5 h-3.5 transition-transform duration-200 opacity-75"
                                    ::class="openMenu === '{{ $entry['key'] }}' ? 'rotate-180' : ''"
                                />
                            </button>

                            <!-- Structured Mega Menu Panel -->
                            <div
                                x-cloak
                                x-show="openMenu === '{{ $entry['key'] }}'"
                                @mouseenter="cancelClose()"
                                @mouseleave="scheduleClose()"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]"
                                @class([
                                    'pkt-mega-panel',
                                    'pkt-mega-panel-wide' => count($entry['columns']) > 1 || count($entry['columns'][0]['items'] ?? []) >= 5,
                                    'pkt-mega-panel-right' => in_array($entry['key'], ['laporan', 'master', 'lainnya'], true),
                                ])
                            >
                                <div @class([
                                    'grid gap-4',
                                    'grid-cols-2' => count($entry['columns']) > 1,
                                    'grid-cols-1' => count($entry['columns']) === 1,
                                ])>
                                    @foreach ($entry['columns'] as $column)
                                        <div class="space-y-2">
                                            <div class="pkt-mega-col-header">
                                                <span @class([
                                                    'pkt-mega-col-dot',
                                                    'bg-[#F47920]' => ($column['accent'] ?? 'orange') === 'orange',
                                                    'bg-[#0054A6] dark:bg-[#83A2DB]' => ($column['accent'] ?? 'orange') === 'blue',
                                                ])></span>
                                                <span>{{ $column['title'] }}</span>
                                            </div>

                                            <div @class([
                                                'grid gap-1',
                                                'grid-cols-2' => count($entry['columns']) === 1 && count($column['items']) >= 5,
                                                'grid-cols-1' => ! (count($entry['columns']) === 1 && count($column['items']) >= 5),
                                            ])>
                                                @foreach ($column['items'] as $item)
                                                    <a
                                                        {{ \Filament\Support\generate_href_html($item['url'], $item['open_in_new_tab']) }}
                                                        @click="closeMenu()"
                                                        @class([
                                                            'pkt-mega-item',
                                                            'pkt-mega-item-active' => $item['is_active'],
                                                        ])
                                                    >
                                                        <span class="pkt-mega-item-icon">
                                                            @if ($item['icon'])
                                                                <x-filament::icon
                                                                    :icon="$item['icon']"
                                                                    class="w-4 h-4"
                                                                />
                                                            @else
                                                                <x-heroicon-o-document class="w-4 h-4" />
                                                            @endif
                                                        </span>
                                                        <span class="flex-1 min-w-0 truncate">{{ $item['label'] }}</span>
                                                        @if (filled($item['badge']))
                                                            <span class="pkt-mega-item-badge">{{ $item['badge'] }}</span>
                                                        @endif
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif

        <div
            @if ($hasTenancy)
                x-persist="topbar.end.panel-{{ filament()->getId() }}.tenant-{{ filament()->getTenant()?->getKey() }}"
            @else
                x-persist="topbar.end.panel-{{ filament()->getId() }}"
            @endif
            class="fi-topbar-end pkt-topbar-end"
        >
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::GLOBAL_SEARCH_BEFORE) }}

            @if (filament()->auth()->check() && filament()->getGlobalSearchProvider() !== null && filament()->getGlobalSearchPosition() === \Filament\Enums\GlobalSearchPosition::Topbar)
                <button
                    type="button"
                    @click="toggleMobileSearch()"
                    aria-label="Cari"
                    title="Pencarian Global"
                    class="pkt-mobile-search-toggle sm:hidden"
                >
                    <x-heroicon-o-magnifying-glass x-show="!mobileSearchOpen" class="w-4 h-4" />
                    <x-heroicon-m-x-mark x-show="mobileSearchOpen" x-cloak class="w-4 h-4" />
                </button>

                <div class="pkt-global-search-wrap">
                    @livewire(Filament\Livewire\GlobalSearch::class)
                </div>
            @endif

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::GLOBAL_SEARCH_AFTER) }}

            @if (filament()->auth()->check())
                @if (filament()->hasDatabaseNotifications() && filament()->getDatabaseNotificationsPosition() === \Filament\Enums\DatabaseNotificationsPosition::Topbar)
                    @livewire(filament()->getDatabaseNotificationsLivewireComponent(), [
                        'lazy' => filament()->hasLazyLoadedDatabaseNotifications(),
                    ])
                @endif

                @if (filament()->hasUserMenu() && filament()->getUserMenuPosition() === \Filament\Enums\UserMenuPosition::Topbar)
                    <x-filament-panels::user-menu />
                @endif
            @endif
        </div>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_END) }}
    </nav>

    <x-filament-actions::modals />
</div>
