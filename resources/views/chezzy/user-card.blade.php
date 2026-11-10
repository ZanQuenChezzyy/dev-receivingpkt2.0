@props([
    'user' => Auth::user(),
])

@php
    $avatarUrl = $user?->getFilamentAvatarUrl()
        ?? 'https://ui-avatars.com/api/?name='.urlencode($user?->name ?? 'User').'&color=FFFFFF&background=F47920';
    $isOnline = method_exists($user, 'isOnline') ? $user->isOnline() : false;
    $roleName = $user?->roles->first()?->name ?? 'User';
@endphp

@if ($user)
    <div class="fi-user-card group relative flex items-center justify-between w-full select-none cursor-pointer outline-none">
        
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
                    alt="{{ e($user->name) }}"
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
                    {{ $user->name }}
                </span>

                {{-- Line 2: NPK --}}
                <span class="text-[11px] font-medium text-slate-400 dark:text-slate-400 font-mono tracking-wide truncate mt-0.5">
                    {{ $user->npk ?: '-' }}
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
    </div>
@endif
