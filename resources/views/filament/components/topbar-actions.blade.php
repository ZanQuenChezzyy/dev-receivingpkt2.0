<div class="fi-topbar-custom-actions hidden sm:flex items-center gap-2 shrink-0">
    @if (auth()->user()?->hasRole('Developer'))
        <div class="shrink-0 flex items-center">
            @livewire('ai-system-toggle')
        </div>
    @endif

    <div class="shrink-0 flex items-center">
        @livewire('language-switcher')
    </div>
</div>


