<div class="shrink-0 inline-flex items-center">
    @if(auth()->user()?->hasRole('Developer'))
    <div class="inline-flex items-center shrink-0" wire:key="ai-toggle-container">
        <!-- Tampilan Mobile (Icon Button saja) -->
        <div class="block sm:hidden shrink-0">
            <x-filament::icon-button
                wire:key="ai-toggle-icon-btn-{{ $isActive ? 'on' : 'off' }}"
                wire:click="toggle"
                wire:loading.attr="disabled"
                wire:target="toggle"
                :color="$isActive ? 'primary' : 'gray'"
                size="md"
                :icon="$isActive ? 'heroicon-s-sparkles' : 'heroicon-s-moon'"
                tooltip="{{ $isActive ? 'Matikan Asisten ALEX' : 'Hidupkan Asisten ALEX' }}"
                style="margin: 0;"
                class="alex-ai-toggle-btn !m-0 shrink-0 {{ $isActive ? 'alex-ai-active !text-white [&_svg]:!text-white [&_svg]:!fill-white' : '[&_svg]:!text-slate-400' }}"
            />
        </div>

        <!-- Tampilan Tablet & Desktop (Button lengkap dengan teks) -->
        <div class="hidden sm:block shrink-0">
            <x-filament::button
                wire:key="ai-toggle-full-btn-{{ $isActive ? 'on' : 'off' }}"
                wire:click="toggle"
                wire:loading.attr="disabled"
                wire:target="toggle"
                :color="$isActive ? 'primary' : 'gray'"
                size="sm"
                :icon="$isActive ? 'heroicon-s-sparkles' : 'heroicon-s-moon'"
                tooltip="{{ $isActive ? 'Asisten ALEX Aktif (Klik untuk mematikan)' : 'Asisten ALEX Sedang Maintenance (Klik untuk mengaktifkan)' }}"
                class="alex-ai-toggle-btn {{ $isActive ? 'alex-ai-active !text-white [&_svg]:!text-white [&_svg]:!fill-white' : '' }}"
            >
                {{ $isActive ? 'ALEX ON' : 'ALEX OFF' }}
            </x-filament::button>
        </div>
    </div>
    @endif
</div>
