<x-filament-panels::page>
    {{-- Selector Cakupan MRP Type --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-1.5">
            @foreach ([
                'NONSTOCK' => 'NONSTOCK',
                'PD' => 'PD',
                'NONSTOCK_PD' => 'NONSTOCK + PD',
                'INVESTASI' => 'INVESTASI',
                'ALL' => 'Semua',
            ] as $scopeKey => $scopeLabel)
                <button
                    type="button"
                    wire:click="setMrpScope('{{ $scopeKey }}')"
                    wire:loading.attr="disabled"
                    class="rounded-lg px-3 py-1.5 text-xs font-semibold transition disabled:opacity-60 {{ $mrpScope === $scopeKey ? 'bg-orange-600 text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800' }}"
                >
                    {{ $scopeLabel }}
                </button>
            @endforeach
        </div>

        <div
            wire:loading.flex
            wire:target="setMrpScope, activeTab"
            class="items-center gap-2 text-xs font-medium text-orange-600 dark:text-orange-400"
        >
            <x-filament::loading-indicator class="h-4 w-4" />
            <span>Memuat data...</span>
        </div>
    </div>

    {{-- Ringkasan (Lazy Loaded dengan Skeleton Shimmer) --}}
    @livewire(\App\Filament\Widgets\NonStockAgingOverviewWidget::class, ['mrpScope' => $mrpScope, 'lazy' => true], key('aging-overview-'.$mrpScope.'-'.$refreshCounter))

    {{-- Tab Cepat Operasional --}}
    @if (count($tabs = $this->getTabs()))
        <x-filament::tabs>
            @foreach ($tabs as $tabKey => $tab)
                <x-filament::tabs.item
                    :active="$activeTab === $tabKey"
                    wire:click="$set('activeTab', '{{ $tabKey }}')"
                    :badge="$tab->getBadge()"
                    :badge-color="$tab->getBadgeColor()"
                >
                    {{ $tab->getLabel() ?? $this->generateTabLabel($tabKey) }}
                </x-filament::tabs.item>
            @endforeach
        </x-filament::tabs>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
