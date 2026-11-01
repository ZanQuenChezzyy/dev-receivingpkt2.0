<div>
    @if(auth()->user()?->hasRole('Developer'))
    <div class="flex items-center px-3" wire:key="ai-toggle-container">
        <!-- Tampilan Mobile (Icon Button saja) -->
        <div class="block sm:hidden">
            <x-filament::icon-button
                wire:key="ai-toggle-icon-btn-{{ $isActive ? 'on' : 'off' }}"
                wire:click="toggle"
                wire:loading.attr="disabled"
                wire:target="toggle"
                :color="$isActive ? 'primary' : 'gray'"
                size="md"
                :icon="$isActive ? 'heroicon-s-sparkles' : 'heroicon-s-moon'"
                tooltip="{{ $isActive ? 'Matikan ALEX Asisten AI Receiving' : 'Hidupkan ALEX Asisten AI Receiving' }}"
            />
        </div>

        <!-- Tampilan Tablet & Desktop (Button lengkap dengan teks) -->
        <div class="hidden sm:block">
            <x-filament::button
                wire:key="ai-toggle-full-btn-{{ $isActive ? 'on' : 'off' }}"
                wire:click="toggle"
                wire:loading.attr="disabled"
                wire:target="toggle"
                :color="$isActive ? 'primary' : 'gray'"
                size="sm"
                :icon="$isActive ? 'heroicon-s-sparkles' : 'heroicon-s-moon'"
                tooltip="{{ $isActive ? 'ALEX Asisten AI Receiving Aktif (Klik untuk mematikan)' : 'ALEX Asisten AI Receiving Sedang Maintenance (Klik untuk mengaktifkan)' }}"
            >
                {{ $isActive ? 'ALEX AI ON' : 'ALEX AI OFF' }}
            </x-filament::button>
        </div>
    </div>
    @endif
</div>
