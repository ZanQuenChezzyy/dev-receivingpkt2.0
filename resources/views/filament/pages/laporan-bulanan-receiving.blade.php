<x-filament-panels::page>
    <div class="space-y-8">
        {{-- Header & Filter Section --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                    Laporan Bagian Unit Kerja Receiving
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Rekapitulasi Lead Time, Barang Ditolak, dan MRP Type
                </p>
            </div>
            <div class="w-full md:w-64">
                <form wire:submit.prevent="submit">
                    {{ $this->form }}
                </form>
            </div>
        </div>

        @php
            $reportData = $this->reportData;
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            $tahun = $this->tahun ?? date('Y');
            $days = ['1', '2', '3', '4', '5', '6', '7', '8', '>8'];
        @endphp

        {{-- Table 1: Spareparts --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-white/5 flex items-center gap-3">
                <div class="w-2 h-6 bg-amber-400 rounded-full"></div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        Kategori Material: Spareparts & Non Spareparts
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Lead time pelaporan material yang diterima dan sudah terbit GRS.</p>
                </div>
            </div>
            <div class="overflow-x-auto p-4">
                <table class="w-full text-xs text-center border-collapse">
                    <thead class="bg-amber-50 dark:bg-amber-900/20 text-gray-900 dark:text-amber-100 font-semibold border-b border-amber-200 dark:border-amber-900/50">
                        <tr>
                            <th rowspan="2" class="px-3 py-2 border border-amber-200 dark:border-amber-900/50 align-middle">Bulan</th>
                            <th colspan="{{ count($days) * 2 }}" class="px-3 py-2 border border-amber-200 dark:border-amber-900/50">Lead Time (Hari)</th>
                            <th rowspan="2" class="px-3 py-2 border border-amber-200 dark:border-amber-900/50 align-middle">Total<br>Item</th>
                            <th rowspan="2" class="px-3 py-2 border border-amber-200 dark:border-amber-900/50 align-middle">Total<br>%</th>
                        </tr>
                        <tr>
                            @foreach ($days as $day)
                                <th colspan="2" class="px-2 py-2 border border-amber-200 dark:border-amber-900/50 font-medium whitespace-nowrap">{{ $day }} <br><span class="opacity-75 font-normal">Total | %</span></th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @php $gtSparepart = array_fill_keys($days, 0); $gtSparepartTotal = 0; @endphp
                        @foreach ($months as $index => $monthName)
                            @php $m = $index + 1; $rowTotal = $reportData['lead_time']['sparepart'][$m]['total']; @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition text-gray-700 dark:text-gray-300">
                                <td class="px-3 py-2 border border-gray-200 dark:border-white/5 font-medium">{{ $monthName }}-{{ substr($tahun, -2) }}</td>
                                @foreach ($days as $day)
                                    @php 
                                        $val = $reportData['lead_time']['sparepart'][$m]['days'][$day]; 
                                        $pct = $reportData['lead_time']['sparepart'][$m]['percentage'][$day];
                                        $gtSparepart[$day] += $val;
                                    @endphp
                                    <td class="px-2 py-2 border border-gray-200 dark:border-white/5 font-semibold text-gray-900 dark:text-white">{{ $val ?: '-' }}</td>
                                    <td class="px-2 py-2 border border-gray-200 dark:border-white/5 text-gray-500">{{ $val ? number_format($pct, 1).'%' : '' }}</td>
                                @endforeach
                                @php $gtSparepartTotal += $rowTotal; @endphp
                                <td class="px-3 py-2 border border-gray-200 dark:border-white/5 font-bold text-gray-900 dark:text-white">{{ $rowTotal ?: '-' }}</td>
                                <td class="px-3 py-2 border border-gray-200 dark:border-white/5 text-gray-500">{{ $rowTotal ? '100%' : '' }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-100 dark:bg-gray-800 font-bold text-gray-900 dark:text-white">
                            <td class="px-3 py-3 border border-gray-200 dark:border-white/5 text-left">Grand Total</td>
                            @foreach ($days as $day)
                                <td class="px-2 py-3 border border-gray-200 dark:border-white/5">{{ $gtSparepart[$day] ?: '-' }}</td>
                                <td class="px-2 py-3 border border-gray-200 dark:border-white/5"></td>
                            @endforeach
                            <td class="px-3 py-3 border border-gray-200 dark:border-white/5">{{ $gtSparepartTotal ?: '-' }}</td>
                            <td class="px-3 py-3 border border-gray-200 dark:border-white/5"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Table 2: NPK --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-white/5 flex items-center gap-3">
                <div class="w-2 h-6 bg-rose-400 rounded-full"></div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        Kategori Material: Bahan Baku NPK, BBM, Lube Oil, Methanol, Batubara, Nitrogen
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Lead time pelaporan material yang diterima dan sudah terbit GRS.</p>
                </div>
            </div>
            <div class="overflow-x-auto p-4">
                <table class="w-full text-xs text-center border-collapse">
                    <thead class="bg-rose-50 dark:bg-rose-900/20 text-gray-900 dark:text-rose-100 font-semibold border-b border-rose-200 dark:border-rose-900/50">
                        <tr>
                            <th rowspan="2" class="px-3 py-2 border border-rose-200 dark:border-rose-900/50 align-middle">Bulan</th>
                            <th colspan="{{ count($days) * 2 }}" class="px-3 py-2 border border-rose-200 dark:border-rose-900/50">Lead Time (Hari)</th>
                            <th rowspan="2" class="px-3 py-2 border border-rose-200 dark:border-rose-900/50 align-middle">Total<br>Item</th>
                            <th rowspan="2" class="px-3 py-2 border border-rose-200 dark:border-rose-900/50 align-middle">Total<br>%</th>
                        </tr>
                        <tr>
                            @foreach ($days as $day)
                                <th colspan="2" class="px-2 py-2 border border-rose-200 dark:border-rose-900/50 font-medium whitespace-nowrap">{{ $day }} <br><span class="opacity-75 font-normal">Total | %</span></th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @php $gtNpk = array_fill_keys($days, 0); $gtNpkTotal = 0; @endphp
                        @foreach ($months as $index => $monthName)
                            @php $m = $index + 1; $rowTotal = $reportData['lead_time']['npk'][$m]['total']; @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition text-gray-700 dark:text-gray-300">
                                <td class="px-3 py-2 border border-gray-200 dark:border-white/5 font-medium">{{ $monthName }}-{{ substr($tahun, -2) }}</td>
                                @foreach ($days as $day)
                                    @php 
                                        $val = $reportData['lead_time']['npk'][$m]['days'][$day]; 
                                        $pct = $reportData['lead_time']['npk'][$m]['percentage'][$day];
                                        $gtNpk[$day] += $val;
                                    @endphp
                                    <td class="px-2 py-2 border border-gray-200 dark:border-white/5 font-semibold text-gray-900 dark:text-white">{{ $val ?: '-' }}</td>
                                    <td class="px-2 py-2 border border-gray-200 dark:border-white/5 text-gray-500">{{ $val ? number_format($pct, 1).'%' : '' }}</td>
                                @endforeach
                                @php $gtNpkTotal += $rowTotal; @endphp
                                <td class="px-3 py-2 border border-gray-200 dark:border-white/5 font-bold text-gray-900 dark:text-white">{{ $rowTotal ?: '-' }}</td>
                                <td class="px-3 py-2 border border-gray-200 dark:border-white/5 text-gray-500">{{ $rowTotal ? '100%' : '' }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-100 dark:bg-gray-800 font-bold text-gray-900 dark:text-white">
                            <td class="px-3 py-3 border border-gray-200 dark:border-white/5 text-left">Grand Total</td>
                            @foreach ($days as $day)
                                <td class="px-2 py-3 border border-gray-200 dark:border-white/5">{{ $gtNpk[$day] ?: '-' }}</td>
                                <td class="px-2 py-3 border border-gray-200 dark:border-white/5"></td>
                            @endforeach
                            <td class="px-3 py-3 border border-gray-200 dark:border-white/5">{{ $gtNpkTotal ?: '-' }}</td>
                            <td class="px-3 py-3 border border-gray-200 dark:border-white/5"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Table 3: Chemical --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-white/5 flex items-center gap-3">
                <div class="w-2 h-6 bg-sky-400 rounded-full"></div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        Kategori Material: Chemical, Karung & Catalyst
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Lead time pelaporan material yang diterima dan sudah terbit GRS.</p>
                </div>
            </div>
            <div class="overflow-x-auto p-4">
                <table class="w-full text-xs text-center border-collapse">
                    <thead class="bg-sky-50 dark:bg-sky-900/20 text-gray-900 dark:text-sky-100 font-semibold border-b border-sky-200 dark:border-sky-900/50">
                        <tr>
                            <th rowspan="2" class="px-3 py-2 border border-sky-200 dark:border-sky-900/50 align-middle">Bulan</th>
                            <th colspan="{{ count($days) * 2 }}" class="px-3 py-2 border border-sky-200 dark:border-sky-900/50">Lead Time (Hari)</th>
                            <th rowspan="2" class="px-3 py-2 border border-sky-200 dark:border-sky-900/50 align-middle">Total<br>Item</th>
                            <th rowspan="2" class="px-3 py-2 border border-sky-200 dark:border-sky-900/50 align-middle">Total<br>%</th>
                        </tr>
                        <tr>
                            @foreach ($days as $day)
                                <th colspan="2" class="px-2 py-2 border border-sky-200 dark:border-sky-900/50 font-medium whitespace-nowrap">{{ $day }} <br><span class="opacity-75 font-normal">Total | %</span></th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @php $gtChem = array_fill_keys($days, 0); $gtChemTotal = 0; @endphp
                        @foreach ($months as $index => $monthName)
                            @php $m = $index + 1; $rowTotal = $reportData['lead_time']['chemical'][$m]['total']; @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition text-gray-700 dark:text-gray-300">
                                <td class="px-3 py-2 border border-gray-200 dark:border-white/5 font-medium">{{ $monthName }}-{{ substr($tahun, -2) }}</td>
                                @foreach ($days as $day)
                                    @php 
                                        $val = $reportData['lead_time']['chemical'][$m]['days'][$day]; 
                                        $pct = $reportData['lead_time']['chemical'][$m]['percentage'][$day];
                                        $gtChem[$day] += $val;
                                    @endphp
                                    <td class="px-2 py-2 border border-gray-200 dark:border-white/5 font-semibold text-gray-900 dark:text-white">{{ $val ?: '-' }}</td>
                                    <td class="px-2 py-2 border border-gray-200 dark:border-white/5 text-gray-500">{{ $val ? number_format($pct, 1).'%' : '' }}</td>
                                @endforeach
                                @php $gtChemTotal += $rowTotal; @endphp
                                <td class="px-3 py-2 border border-gray-200 dark:border-white/5 font-bold text-gray-900 dark:text-white">{{ $rowTotal ?: '-' }}</td>
                                <td class="px-3 py-2 border border-gray-200 dark:border-white/5 text-gray-500">{{ $rowTotal ? '100%' : '' }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-100 dark:bg-gray-800 font-bold text-gray-900 dark:text-white">
                            <td class="px-3 py-3 border border-gray-200 dark:border-white/5 text-left">Grand Total</td>
                            @foreach ($days as $day)
                                <td class="px-2 py-3 border border-gray-200 dark:border-white/5">{{ $gtChem[$day] ?: '-' }}</td>
                                <td class="px-2 py-3 border border-gray-200 dark:border-white/5"></td>
                            @endforeach
                            <td class="px-3 py-3 border border-gray-200 dark:border-white/5">{{ $gtChemTotal ?: '-' }}</td>
                            <td class="px-3 py-3 border border-gray-200 dark:border-white/5"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Bottom Tables: RDTV & MRP Type --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
            
            {{-- Table 4: RDTV --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-white/5 flex items-center gap-3">
                    <div class="w-2 h-6 bg-emerald-400 rounded-full"></div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            Laporan Barang Ditolak (RDTV)
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Rekapitulasi item yang dikembalikan atau ditolak.</p>
                    </div>
                </div>
                <div class="overflow-x-auto p-4">
                    <table class="w-full text-sm text-center border-collapse">
                        <thead class="bg-emerald-50 dark:bg-emerald-900/20 text-gray-900 dark:text-emerald-100 font-semibold border-b border-emerald-200 dark:border-emerald-900/50">
                            <tr>
                                <th class="px-4 py-3 border border-emerald-200 dark:border-emerald-900/50">Bulan</th>
                                <th class="px-4 py-3 border border-emerald-200 dark:border-emerald-900/50">Item RDTV</th>
                                <th class="px-4 py-3 border border-emerald-200 dark:border-emerald-900/50">Total Item Penerimaan</th>
                                <th class="px-4 py-3 border border-emerald-200 dark:border-emerald-900/50">Persentase</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @php $gtRdtv = 0; $gtRdtvAll = 0; @endphp
                            @foreach ($months as $index => $monthName)
                                @php 
                                    $m = $index + 1; 
                                    $val = $reportData['rdtv'][$m]['total'];
                                    $all = $reportData['mrp'][$m]['total'];
                                    $pct = $reportData['rdtv'][$m]['percentage'];
                                    $gtRdtv += $val;
                                    $gtRdtvAll += $all;
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition text-gray-700 dark:text-gray-300">
                                    <td class="px-4 py-2 border border-gray-200 dark:border-white/5 font-medium">{{ $monthName }}-{{ substr($tahun, -2) }}</td>
                                    <td class="px-4 py-2 border border-gray-200 dark:border-white/5 font-semibold text-gray-900 dark:text-white">{{ $val ?: '-' }}</td>
                                    <td class="px-4 py-2 border border-gray-200 dark:border-white/5">{{ $all ?: '-' }}</td>
                                    <td class="px-4 py-2 border border-gray-200 dark:border-white/5 text-gray-500">{{ $val ? number_format($pct, 1).'%' : '' }}</td>
                                </tr>
                            @endforeach
                            <tr class="bg-gray-100 dark:bg-gray-800 font-bold text-gray-900 dark:text-white">
                                <td class="px-4 py-3 border border-gray-200 dark:border-white/5 text-left">Grand Total</td>
                                <td class="px-4 py-3 border border-gray-200 dark:border-white/5">{{ $gtRdtv ?: '-' }}</td>
                                <td class="px-4 py-3 border border-gray-200 dark:border-white/5">{{ $gtRdtvAll ?: '-' }}</td>
                                <td class="px-4 py-3 border border-gray-200 dark:border-white/5">{{ $gtRdtvAll ? number_format(($gtRdtv/$gtRdtvAll)*100, 1).'%' : '' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Table 5: MRP Type --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-white/5 flex items-center gap-3">
                    <div class="w-2 h-6 bg-emerald-400 rounded-full"></div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            Laporan Berdasarkan MRP Type
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Distribusi kuantitas material menurut tipe MRP.</p>
                    </div>
                </div>
                <div class="overflow-x-auto p-4">
                    <table class="w-full text-sm text-center border-collapse">
                        <thead class="bg-emerald-50 dark:bg-emerald-900/20 text-gray-900 dark:text-emerald-100 font-semibold border-b border-emerald-200 dark:border-emerald-900/50">
                            <tr>
                                <th class="px-4 py-3 border border-emerald-200 dark:border-emerald-900/50">Bulan</th>
                                <th class="px-4 py-3 border border-emerald-200 dark:border-emerald-900/50">Investasi</th>
                                <th class="px-4 py-3 border border-emerald-200 dark:border-emerald-900/50">V1</th>
                                <th class="px-4 py-3 border border-emerald-200 dark:border-emerald-900/50">PD</th>
                                <th class="px-4 py-3 border border-emerald-200 dark:border-emerald-900/50">Nonstock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @php 
                                $gtMrp = ['INVESTASI' => 0, 'V1' => 0, 'PD' => 0, 'NONSTOCK' => 0];
                            @endphp
                            @foreach ($months as $index => $monthName)
                                @php $m = $index + 1; @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition text-gray-700 dark:text-gray-300">
                                    <td class="px-4 py-2 border border-gray-200 dark:border-white/5 font-medium">{{ $monthName }}-{{ substr($tahun, -2) }}</td>
                                    @foreach (['INVESTASI', 'V1', 'PD', 'NONSTOCK'] as $type)
                                        @php 
                                            $val = $reportData['mrp'][$m][$type] ?? 0;
                                            $gtMrp[$type] += $val;
                                        @endphp
                                        <td class="px-4 py-2 border border-gray-200 dark:border-white/5 font-semibold text-gray-900 dark:text-white">{{ $val ?: '-' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            <tr class="bg-gray-100 dark:bg-gray-800 font-bold text-gray-900 dark:text-white">
                                <td class="px-4 py-3 border border-gray-200 dark:border-white/5 text-left">Grand Total</td>
                                @foreach (['INVESTASI', 'V1', 'PD', 'NONSTOCK'] as $type)
                                    <td class="px-4 py-3 border border-gray-200 dark:border-white/5">{{ $gtMrp[$type] ?: '-' }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-filament-panels::page>
