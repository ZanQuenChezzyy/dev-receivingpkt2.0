<div class="fi-topbar-custom-actions flex items-center gap-1.5 sm:gap-2 shrink-0">
    @if (auth()->user()?->hasRole('Developer'))
        <div class="shrink-0 flex items-center">
            @livewire('ai-system-toggle')
        </div>
    @endif

    <div class="shrink-0 flex items-center">
        @livewire('language-switcher')
    </div>

    <div
        class="shrink-0 flex items-center"
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
            toggle() {
                const next = this.isDark ? 'light' : 'dark';
                localStorage.setItem('theme', next);
                document.cookie = 'theme=' + next + '; path=/; max-age=31536000; SameSite=Lax';
                this.$dispatch('theme-changed', next);
                this.$nextTick(() => this.sync());
            },
        }"
        @theme-changed.window="$nextTick(() => sync())"
    >
        <button
            type="button"
            role="switch"
            :aria-checked="isDark ? 'true' : 'false'"
            :aria-label="isDark ? 'Ubah ke Mode Terang' : 'Ubah ke Mode Gelap'"
            :title="isDark ? 'Ubah ke Mode Terang' : 'Ubah ke Mode Gelap'"
            @click="toggle()"
            class="uiverse-theme-switch"
        >
            <span class="uiverse-theme-stars" aria-hidden="true"></span>
            <span class="uiverse-theme-cloud" aria-hidden="true"></span>
            <span class="uiverse-theme-knob" aria-hidden="true"></span>
        </button>
    </div>
</div>


