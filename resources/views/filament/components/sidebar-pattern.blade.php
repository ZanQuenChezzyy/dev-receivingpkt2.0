<!-- Mobile Drawer Utilities (Bahasa, Tema, ALEX AI) — dipindah dari topbar HP agar header tetap lega -->
<div class="relative z-10 sm:hidden px-4 py-3 border-t border-black/[0.06] dark:border-white/[0.08] space-y-2.5 bg-white/90 dark:bg-[#151B24]/95">
    <div class="flex items-center justify-between gap-2">
        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Bahasa</span>
        @livewire('language-switcher')
    </div>

    <div
        class="flex items-center justify-between gap-2"
        x-data="{
            isDark: document.documentElement.classList.contains('dark'),
            setMode(mode) {
                localStorage.setItem('theme', mode);
                document.cookie = 'theme=' + mode + '; path=/; max-age=31536000; SameSite=Lax';
                this.$dispatch('theme-changed', mode);
                this.isDark = mode === 'dark';
            }
        }"
        @theme-changed.window="isDark = document.documentElement.classList.contains('dark')"
    >
        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Tampilan</span>
        <div class="inline-flex items-center gap-1 p-1 rounded-full bg-slate-100 dark:bg-white/[0.06] border border-black/[0.06] dark:border-white/[0.08]">
            <button
                type="button"
                @click="setMode('light')"
                class="px-2.5 py-1 rounded-full text-[11px] font-semibold transition-colors"
                :class="!isDark ? 'bg-[#10141A] text-white' : 'text-slate-500 dark:text-slate-400'"
            >
                Terang
            </button>
            <button
                type="button"
                @click="setMode('dark')"
                class="px-2.5 py-1 rounded-full text-[11px] font-semibold transition-colors"
                :class="isDark ? 'bg-[#F47920] text-white' : 'text-slate-500 dark:text-slate-400'"
            >
                Gelap
            </button>
        </div>
    </div>

    @if (auth()->user()?->hasRole('Developer'))
        <div class="flex items-center justify-between gap-2 pt-0.5">
            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Asisten ALEX</span>
            @livewire('ai-system-toggle')
        </div>
    @endif
</div>

<div class="pointer-events-none absolute inset-x-0 bottom-0 flex justify-center items-end overflow-hidden select-none z-0">
    <div class="relative w-full h-72 sm:h-80 flex items-end justify-center overflow-hidden">
        <img 
            src="{{ asset('images/pattern/pkt-pattern-dark.png') }}" 
            alt="Pupuk Kalimantan Timur Pattern" 
            class="fi-sidebar-pattern-light w-full max-w-[260px] h-full object-contain object-bottom opacity-[0.15]"
        >
        <img 
            src="{{ asset('images/pattern/pkt-pattern-white.png') }}" 
            alt="Pupuk Kalimantan Timur Pattern" 
            class="fi-sidebar-pattern-dark w-full max-w-[260px] h-full object-contain object-bottom opacity-[0.06]"
        >
    </div>
</div>

