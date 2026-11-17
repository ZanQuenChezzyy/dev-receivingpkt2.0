@php
    $currentLocale = $currentLocale ?? app()->getLocale();
@endphp

<div 
    x-data="{ current: '{{ $currentLocale }}', pressing: false }"
    @mousedown="pressing = true"
    @mouseup="pressing = false"
    @mouseleave="pressing = false"
    class="relative inline-grid grid-cols-2 p-[3px] rounded-full bg-slate-200/80 dark:bg-slate-900/80 backdrop-blur-md border border-slate-300/70 dark:border-white/10 shadow-[inset_0_1px_3px_rgba(0,0,0,0.12)] shrink-0 select-none overflow-hidden w-[74px] min-w-[74px] max-w-[74px] sm:w-[84px] sm:min-w-[84px] sm:max-w-[84px] transition-transform duration-150 ease-out items-center"
    :class="pressing ? 'scale-[0.97]' : 'scale-100'"
    wire:key="language-switcher-pill-{{ $currentLocale }}"
>
    <!-- Framer Motion-Style Spring Active Pill Indicator -->
    <div 
        class="segmented-spring-pill-round absolute inset-y-[3px] left-[3px] w-[calc(50%-3px)] rounded-full pointer-events-none z-0 bg-white dark:bg-slate-700/95 border border-black/5 dark:border-white/20 shadow-[0_3px_10px_-1px_rgba(0,0,0,0.16),0_1px_3px_0_rgba(0,0,0,0.08)] dark:shadow-[0_2px_8px_rgba(0,0,0,0.4),inset_0_1px_0_rgba(255,255,255,0.2)] transition-transform duration-300 ease-[cubic-bezier(0.34,1.4,0.64,1)]"
        style="transform: translateX({{ $currentLocale === 'en' ? '100%' : '0%' }}); width: calc(50% - 3px);"
        :style="`transform: translateX(${current === 'en' ? '100%' : '0%'});`"
    ></div>

    <!-- Indonesia Button -->
    <a
        href="{{ route('locale.switch', 'id') }}"
        @click.prevent="current = 'id'; window.location.assign($el.href + ($el.href.includes('?') ? '&' : '?') + 'redirect=' + encodeURIComponent(window.location.pathname + window.location.search + window.location.hash))"
        :class="{
            'text-blue-600 dark:text-white font-bold scale-[1.02]': current === 'id',
            'text-slate-600 dark:text-slate-400 font-medium hover:text-slate-900 dark:hover:text-white': current !== 'id'
        }"
        class="relative z-10 px-2 py-1 rounded-full text-xs font-bold transition-all duration-200 text-center select-none whitespace-nowrap focus:outline-none {{ $currentLocale === 'id' ? 'text-blue-600 dark:text-white font-bold scale-[1.02]' : 'text-slate-600 dark:text-slate-400 font-medium' }}"
        title="Bahasa Indonesia"
    >
        ID
    </a>

    <!-- English Button -->
    <a
        href="{{ route('locale.switch', 'en') }}"
        @click.prevent="current = 'en'; window.location.assign($el.href + ($el.href.includes('?') ? '&' : '?') + 'redirect=' + encodeURIComponent(window.location.pathname + window.location.search + window.location.hash))"
        :class="{
            'text-blue-600 dark:text-white font-bold scale-[1.02]': current === 'en',
            'text-slate-600 dark:text-slate-400 font-medium hover:text-slate-900 dark:hover:text-white': current !== 'en'
        }"
        class="relative z-10 px-2 py-1 rounded-full text-xs font-bold transition-all duration-200 text-center select-none whitespace-nowrap focus:outline-none {{ $currentLocale === 'en' ? 'text-blue-600 dark:text-white font-bold scale-[1.02]' : 'text-slate-600 dark:text-slate-400 font-medium' }}"
        title="English"
    >
        EN
    </a>
</div>
