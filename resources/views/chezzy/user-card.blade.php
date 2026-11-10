@props([
    'user' => Auth::user(),
])

@if ($user)
    <div class="fi-user-card group relative flex items-center justify-between w-full p-3 rounded-2xl bg-white/95 dark:bg-[#0b1626]/90 backdrop-blur-xl border border-black/[0.06] dark:border-white/[0.08] shadow-[0_2px_12px_rgba(0,0,0,0.04)] dark:shadow-[0_2px_16px_rgba(0,0,0,0.3)] hover:border-[#F47920]/40 hover:shadow-md transition-all duration-200 text-start">
        <div class="flex items-center gap-3 min-w-0 flex-1">
            {{-- Avatar with Online Indicator --}}
            <div class="relative shrink-0">
                <x-filament::avatar
                    :src="filament()->getUserAvatarUrl($user)"
                    :alt="__('filament-panels::layout.avatar.alt', ['name' => filament()->getUserName($user)])"
                    :attributes="\Filament\Support\prepare_inherited_attributes($attributes)->class([
                        'fi-user-avatar rounded-full w-10 h-10 object-cover ring-2 ring-black/[0.04] dark:ring-white/10 shadow-xs shrink-0',
                    ])" 
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

        {{-- Three Dots Menu Icon --}}
        <div class="shrink-0 pl-2 text-slate-400 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-200 transition-colors">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 12a2 2 0 11-4 0 2 2 0 014 0zm8 0a2 2 0 11-4 0 2 2 0 014 0zm8 0a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
        </div>
    </div>
@endif
