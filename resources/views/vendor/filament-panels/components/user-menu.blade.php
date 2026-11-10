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
                class="fi-user-menu-trigger"
            >
                <x-filament-panels::avatar.user :user="$user" loading="lazy" />
            </button>
        @else
            {{-- Collapsed Sidebar View (Desktop): Centered Avatar Icon --}}
            @if ($isSidebarCollapsibleOnDesktop)
                <button
                    aria-label="{{ __('filament-panels::layout.actions.open_user_menu.label') }}"
                    type="button"
                    x-show="! $store.sidebar.isOpen"
                    class="flex items-center justify-center w-full p-2 rounded-2xl bg-white/90 dark:bg-[#0b1626]/90 border border-black/[0.06] dark:border-white/[0.08] shadow-[0_2px_8px_rgba(0,0,0,0.04)] dark:shadow-[0_2px_12px_rgba(0,0,0,0.3)] hover:border-[#F47920]/40 transition-all duration-200"
                >
                    <div class="relative shrink-0">
                        <x-filament-panels::avatar.user 
                            :user="$user" 
                            loading="lazy" 
                            class="w-9 h-9 rounded-full object-cover ring-2 ring-black/[0.04] dark:ring-white/10" 
                        />
                        @if (method_exists($user, 'isOnline') && $user->isOnline())
                            <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-500 rounded-full ring-2 ring-white dark:ring-[#0b1626]"></span>
                        @endif
                    </div>
                </button>
            @endif

            {{-- Expanded Sidebar View: Card User (Sesuai Desain Navigasi PKT / Mitraku) --}}
            <button
                aria-label="{{ __('filament-panels::layout.actions.open_user_menu.label') }}"
                type="button"
                @if ($isSidebarCollapsibleOnDesktop)
                    x-show="$store.sidebar.isOpen"
                @endif
                class="fi-user-card group relative flex items-center justify-between w-full p-3 rounded-2xl bg-white/95 dark:bg-[#0b1626]/90 backdrop-blur-xl border border-black/[0.06] dark:border-white/[0.08] shadow-[0_2px_12px_rgba(0,0,0,0.04)] dark:shadow-[0_2px_16px_rgba(0,0,0,0.3)] hover:border-[#F47920]/40 hover:shadow-md transition-all duration-200 text-start outline-none"
            >
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    {{-- Avatar with Online Indicator --}}
                    <div class="relative shrink-0">
                        <x-filament-panels::avatar.user 
                            :user="$user" 
                            loading="lazy" 
                            class="w-10 h-10 rounded-full object-cover ring-2 ring-black/[0.04] dark:ring-white/10 shadow-xs" 
                        />
                        @if (method_exists($user, 'isOnline') && $user->isOnline())
                            <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-500 rounded-full ring-2 ring-white dark:ring-[#0b1626]" title="Online"></span>
                        @endif
                    </div>

                    {{-- User Details (4 Lines) --}}
                    <div class="flex flex-col min-w-0 flex-1">
                        {{-- 1. Name --}}
                        <span class="text-xs sm:text-[13px] font-bold text-slate-800 dark:text-slate-100 truncate block leading-tight">
                            {{ $user->name }}
                        </span>

                        {{-- 2. NPK --}}
                        <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 truncate block leading-tight mt-0.5">
                            {{ $user->npk ?: '-' }}
                        </span>

                        {{-- 3. Role --}}
                        <span class="text-[11px] font-semibold text-[#0A4F86] dark:text-[#F47920] truncate block leading-tight mt-0.5">
                            {{ $user->roles->first()?->name ?? 'User' }}
                        </span>

                        {{-- 4. Location / Unit --}}
                        <span class="text-[10px] font-normal text-slate-400 dark:text-slate-500 truncate block leading-tight mt-0.5">
                            Pusat
                        </span>
                    </div>
                </div>

                {{-- Action Menu Indicator (Three Dots) --}}
                <div class="shrink-0 pl-2 text-slate-400 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-200 transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6 12a2 2 0 11-4 0 2 2 0 014 0zm8 0a2 2 0 11-4 0 2 2 0 014 0zm8 0a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
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

