<x-filament-panels::page>
    @if (count($tabs = $this->getTabs()))
        <x-filament::tabs>
            @foreach ($tabs as $tabKey => $tab)
                <x-filament::tabs.item 
                    :active="$activeTab === $tabKey"
                    wire:click="$set('activeTab', '{{ $tabKey }}')"
                    :badge="$tab->getBadge()"
                >
                    {{ $tab->getLabel() ?? $this->generateTabLabel($tabKey) }}
                </x-filament::tabs.item>
            @endforeach
        </x-filament::tabs>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
