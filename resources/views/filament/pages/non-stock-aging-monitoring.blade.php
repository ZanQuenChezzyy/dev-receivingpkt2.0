<x-filament-panels::page>
    {{-- Selector Cakupan MRP Type --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-1.5">
            @foreach ([
                'NONSTOCK' => 'NONSTOCK (Fokus LEAD 2026)',
                'PD' => 'PD',
                'NONSTOCK_PD' => 'NONSTOCK + PD',
                'INVESTASI' => 'INVESTASI',
                'ALL' => 'Semua MRP',
            ] as $scopeKey => $scopeLabel)
                <button
                    type="button"
                    wire:click="setMrpScope('{{ $scopeKey }}')"
                    class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $mrpScope === $scopeKey ? 'bg-orange-600 text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800' }}"
                >
                    {{ $scopeLabel }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Ringkasan Eksekutif KPI, 4 Zona Aging & Pareto 80/20 --}}
    @livewire(\App\Filament\Widgets\NonStockAgingOverviewWidget::class, ['mrpScope' => $mrpScope], key('aging-overview-'.$mrpScope.'-'.$refreshCounter))

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
