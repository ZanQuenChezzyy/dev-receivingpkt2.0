@props([
    'position' => null,
])

@php
    use Filament\Actions\Action;
    use Filament\Enums\UserMenuPosition;
    use Illuminate\Support\Arr;

    $user = filament()->auth()->user();

    $items = $this->getUserMenuItems();

    $itemsBeforeAndAfterThemeSwitcher = collect($items)
        ->groupBy(fn (Action $item): bool => $item->getSort() < 0, preserveKeys: true)
        ->all();
    $itemsBeforeThemeSwitcher = $itemsBeforeAndAfterThemeSwitcher[true] ?? collect();
    $itemsAfterThemeSwitcher = $itemsBeforeAndAfterThemeSwitcher[false] ?? collect();

    $hasProfileHeader = $itemsBeforeThemeSwitcher->has('profile') &&
        blank(($item = Arr::first($itemsBeforeThemeSwitcher))->getUrl()) &&
        (! $item->hasAction());

    if ($itemsBeforeThemeSwitcher->has('profile')) {
        $itemsBeforeThemeSwitcher = $itemsBeforeThemeSwitcher->prepend($itemsBeforeThemeSwitcher->pull('profile'), 'profile');
    }

    $position ??= filament()->getUserMenuPosition();

    $isSidebarCollapsibleOnDesktop = filament()->isSidebarCollapsibleOnDesktop();

    $avatarUrl = $user?->getFilamentAvatarUrl()
        ?? 'https://ui-avatars.com/api/?name='.urlencode($user?->name ?? 'User').'&color=FFFFFF&background=F47920';

    $isOnline = method_exists($user, 'isOnline') ? $user->isOnline() : false;
    $roleName = $user?->roles->first()?->name ?? 'User';
@endphp

{{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_BEFORE) }}

<x-filament::dropdown
    :placement="($position === UserMenuPosition::Topbar) ? 'bottom-end' : 'top'"
    :teleport="true"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($attributes)
            ->class(['fi-user-menu w-full'])
    "
>
    <x-slot name="trigger">
        @if ($position === UserMenuPosition::Topbar)
            <button
                aria-label="{{ __('filament-panels::layout.actions.open_user_menu.label') }}"
                type="button"
                class="fi-user-menu-trigger transition-transform hover:scale-105 active:scale-95"
            >
                <div class="relative shrink-0">
                    <img 
                        src="{{ $avatarUrl }}" 
                        alt="{{ e($user?->name) }}" 
                        class="w-9 h-9 rounded-full object-cover ring-2 ring-slate-200 dark:ring-white/10 shadow-xs" 
                    />
                    @if ($isOnline)
                        <span class="absolute -bottom-0.5 -right-0.5 flex h-2.5 w-2.5 items-center justify-center">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500 ring-1.5 ring-white dark:ring-[#07111e]"></span>
                        </span>
                    @endif
                </div>
            </button>
        @else
            {{-- Collapsed Sidebar View (Desktop): Centered Avatar Icon --}}
            @if ($isSidebarCollapsibleOnDesktop)
                <button
                    aria-label="{{ __('filament-panels::layout.actions.open_user_menu.label') }}"
                    type="button"
                    x-show="! $store.sidebar.isOpen"
                    class="fi-user-card group relative flex items-center justify-center w-full !p-2 !rounded-2xl select-none cursor-pointer outline-none"
                >
                    <div class="relative shrink-0">
                        <img 
                            src="{{ $avatarUrl }}" 
                            alt="{{ e($user?->name) }}"
                            class="w-9 h-9 rounded-full object-cover ring-1 ring-black/[0.06] dark:ring-white/15 shadow-xs transition-transform duration-200 group-hover:scale-105"
                        />
                        @if ($isOnline)
                            <span class="absolute -bottom-0.5 -right-0.5 flex h-2.5 w-2.5 items-center justify-center">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500 ring-1.5 ring-white/95 dark:ring-[#07111e]"></span>
                            </span>
                        @endif
                    </div>
                </button>
            @endif

            {{-- Expanded Sidebar View: Apple Liquid Glass User Card (Mitraku/PKT Glassmorphism) --}}
            <button
                aria-label="{{ __('filament-panels::layout.actions.open_user_menu.label') }}"
                type="button"
                @if ($isSidebarCollapsibleOnDesktop)
                    x-show="$store.sidebar.isOpen"
                @endif
                class="fi-user-card group relative flex items-center justify-between w-full select-none cursor-pointer outline-none"
            >
                {{-- Glass Specular Diagonal Reflection Sheen --}}
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-white/40 via-white/10 to-transparent dark:from-white/10 dark:via-transparent dark:to-transparent z-0"></div>

                {{-- Ambient Accent Glow on Hover --}}
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-[#F47920]/0 via-[#F47920]/[0.03] to-[#0A4F86]/[0.04] opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-0"></div>

                {{-- PKT Corporate Pattern Watermark in Bottom-Left Corner (Half Visible through Frosted Glass) --}}
                <div class="pointer-events-none absolute -bottom-6 -left-6 w-24 h-24 select-none z-0 overflow-hidden">
                    <img 
                        src="{{ asset('images/pattern/pkt-pattern-dark.png') }}" 
                        alt="PKT Pattern" 
                        class="block dark:hidden w-full h-full object-contain opacity-20 group-hover:opacity-30 transition-opacity duration-300"
                    >
                    <img 
                        src="{{ asset('images/pattern/pkt-pattern-white.png') }}" 
                        alt="PKT Pattern" 
                        class="hidden dark:block w-full h-full object-contain opacity-10 group-hover:opacity-20 transition-opacity duration-300"
                    >
                </div>

                <div class="relative z-10 flex items-center gap-3 min-w-0 flex-1">
                    {{-- User Avatar with Online Indicator --}}
                    <div class="relative shrink-0">
                        <img 
                            src="{{ $avatarUrl }}" 
                            alt="{{ e($user?->name) }}"
                            class="w-10 h-10 rounded-full object-cover ring-1 ring-black/[0.06] dark:ring-white/15 shadow-xs transition-transform duration-200 group-hover:scale-105" 
                        />
                        @if ($isOnline)
                            <span class="absolute -bottom-0.5 -right-0.5 flex h-3 w-3 items-center justify-center">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500 ring-2 ring-white/95 dark:ring-[#07111e]" title="Online"></span>
                            </span>
                        @endif
                    </div>

                    {{-- User Info Stack: 3 Lines (Nama, NPK, Role) --}}
                    <div class="flex flex-col min-w-0 flex-1 text-left leading-snug">
                        {{-- Line 1: User Full Name --}}
                        <span class="text-xs sm:text-[13px] font-semibold text-slate-800 dark:text-slate-100 truncate block tracking-tight group-hover:text-[#F47920] dark:group-hover:text-orange-400 transition-colors">
                            {{ $user?->name }}
                        </span>

                        {{-- Line 2: NPK --}}
                        <span class="text-[11px] font-medium text-slate-400 dark:text-slate-400 font-mono tracking-wide truncate mt-0.5">
                            {{ $user?->npk ?: '-' }}
                        </span>

                        {{-- Line 3: Role --}}
                        <span class="text-[11px] font-semibold text-[#0A4F86] dark:text-sky-400 truncate mt-0.5 tracking-tight">
                            {{ $roleName }}
                        </span>
                    </div>
                </div>

                {{-- Action Menu Indicator (Three Dots) --}}
                <div class="relative z-10 shrink-0 pl-1.5 text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300 transition-colors">
                    <div class="p-1 rounded-lg hover:bg-black/[0.04] dark:hover:bg-white/[0.08] transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <circle cx="5" cy="12" r="1.75" />
                            <circle cx="12" cy="12" r="1.75" />
                            <circle cx="19" cy="12" r="1.75" />
                        </svg>
                    </div>
                </div>
            </button>
        @endif
    </x-slot>

    @if ($hasProfileHeader)
        @php
            $item = $itemsBeforeThemeSwitcher['profile'];
            $itemColor = $item->getColor();
            $itemIcon = $item->getIcon();

            unset($itemsBeforeThemeSwitcher['profile']);
        @endphp

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_PROFILE_BEFORE) }}

        <x-filament::dropdown.header :color="$itemColor" :icon="$itemIcon">
            {{ $item->getLabel() }}
        </x-filament::dropdown.header>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_PROFILE_AFTER) }}
    @endif

    @if ($itemsBeforeThemeSwitcher->isNotEmpty())
        <x-filament::dropdown.list>
            @foreach ($itemsBeforeThemeSwitcher as $key => $item)
                @if ($key === 'profile')
                    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_PROFILE_BEFORE) }}

                    {{ $item }}

                    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_PROFILE_AFTER) }}
                @else
                    {{ $item }}
                @endif
            @endforeach
        </x-filament::dropdown.list>
    @endif

    @if (filament()->hasDarkMode() && (! filament()->hasDarkModeForced()))
        <x-filament::dropdown.list>
            <x-filament-panels::theme-switcher />
        </x-filament::dropdown.list>
    @endif

    @if ($itemsAfterThemeSwitcher->isNotEmpty())
        <x-filament::dropdown.list>
            @foreach ($itemsAfterThemeSwitcher as $key => $item)
                @if ($key === 'profile')
                    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_PROFILE_BEFORE) }}

                    {{ $item }}

                    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_PROFILE_AFTER) }}
                @else
                    {{ $item }}
                @endif
            @endforeach
        </x-filament::dropdown.list>
    @endif
</x-filament::dropdown>

{{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_AFTER) }}
