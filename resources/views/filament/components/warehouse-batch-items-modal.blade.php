<div x-data="{ 
    search: '', 
    statusFilter: 'all',
    matches(item) {
        const matchesStatus = this.statusFilter === 'all' || item.status === this.statusFilter;
        if (!this.search.trim()) return matchesStatus;
        
        const q = this.search.toLowerCase();
        const searchTarget = (
            (item.po_number || '') + ' ' + 
            (item.mat_doc_105 || '') + ' ' + 
            (item.description || '') + ' ' + 
            (item.material_code || '') + ' ' + 
            (item.storage_location || '') + ' ' + 
            (item.storage_text || '') + ' ' + 
            (item.do_number || '')
        ).toLowerCase();

        return matchesStatus && searchTarget.includes(q);
    }
}" class="space-y-4">

    <!-- Summary Stats Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="p-3 rounded-xl bg-slate-100 dark:bg-white/5 border border-slate-200/80 dark:border-white/10">
            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Total Material</span>
            <span class="text-lg font-bold text-slate-900 dark:text-white">{{ count($items) }} Item</span>
        </div>
        <div class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400">
            <span class="text-[11px] font-semibold uppercase tracking-wider block">Sudah Terkirim</span>
            <span class="text-lg font-bold">{{ $items->where('status', 'delivered')->count() }} Item</span>
        </div>
        <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-700 dark:text-amber-400">
            <span class="text-[11px] font-semibold uppercase tracking-wider block">Belum Terkirim</span>
            <span class="text-lg font-bold">{{ $items->where('status', 'pending')->count() }} Item</span>
        </div>
        <div class="p-3 rounded-xl bg-sky-500/10 border border-sky-500/20 text-sky-700 dark:text-sky-400">
            <span class="text-[11px] font-semibold uppercase tracking-wider block">Terhubung ke DO</span>
            <span class="text-lg font-bold">{{ $items->whereNotNull('delivery_order_receipt_id')->count() }} Item</span>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="flex flex-col sm:flex-row gap-2.5 justify-between items-stretch sm:items-center pt-1">
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input 
                x-model="search" 
                type="text" 
                placeholder="Cari PO, Kode 105, Deskripsi, Material Code, atau Lokasi..."
                class="w-full pl-9 pr-4 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-black/20 rounded-xl border border-slate-200/80 dark:border-white/10 focus:ring-2 focus:ring-[#F47920]/30 focus:border-[#F47920] outline-none transition-all"
            >
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
            <button 
                type="button" 
                @click="statusFilter = 'all'"
                :class="statusFilter === 'all' ? 'bg-[#F47920] text-white shadow-xs' : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-white/10'"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
            >
                Semua
            </button>
            <button 
                type="button" 
                @click="statusFilter = 'pending'"
                :class="statusFilter === 'pending' ? 'bg-amber-500 text-white shadow-xs' : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-white/10'"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
            >
                Belum Kirim
            </button>
            <button 
                type="button" 
                @click="statusFilter = 'delivered'"
                :class="statusFilter === 'delivered' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-white/10'"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
            >
                Terkirim
            </button>
        </div>
    </div>

    <!-- Items Table Container -->
    <div class="rounded-xl border border-slate-200 dark:border-white/10 overflow-hidden bg-white/50 dark:bg-slate-900/40">
        <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10 sticky top-0 z-10 text-slate-600 dark:text-slate-300 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-3 py-2.5">No</th>
                        <th class="px-3 py-2.5">Tgl Posting</th>
                        <th class="px-3 py-2.5">Material Document (105)</th>
                        <th class="px-3 py-2.5">Nomor PO & Item</th>
                        <th class="px-3 py-2.5">Material & Deskripsi</th>
                        <th class="px-3 py-2.5">Jumlah</th>
                        <th class="px-3 py-2.5">Lokasi SAP</th>
                        <th class="px-3 py-2.5">Status Pengiriman</th>
                        <th class="px-3 py-2.5">Status DO</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/5 text-slate-700 dark:text-slate-200">
                    @forelse ($items as $idx => $item)
                        @php
                            $jsItem = json_encode([
                                'status' => $item->status,
                                'po_number' => $item->po_number,
                                'mat_doc_105' => $item->mat_doc_105,
                                'description' => $item->description,
                                'material_code' => $item->material_code,
                                'storage_location' => $item->storage_location,
                                'storage_text' => $item->storage_text,
                                'do_number' => $item->deliveryOrderReceipt?->delivery_order_no,
                            ]);
                        @endphp
                        <tr x-show="matches({{ $jsItem }})" class="hover:bg-slate-50/80 dark:hover:bg-white/[0.02] transition-colors">
                            <td class="px-3 py-2 font-mono text-slate-400">{{ $idx + 1 }}</td>
                            <td class="px-3 py-2">
                                <span class="font-medium text-slate-600 dark:text-slate-400">
                                    {{ $item->posting_date ? \Carbon\Carbon::parse($item->posting_date)->format('d/m/Y') : '-' }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-mono font-bold text-amber-600 dark:text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-md border border-amber-500/20">
                                        {{ $item->mat_doc_105 }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono">Itm {{ $item->item_no }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-900 dark:text-white font-mono">{{ $item->po_number }}</span>
                                    @if ($item->ref_doc_103)
                                        <span class="text-[10px] text-slate-400 font-mono">103: {{ $item->ref_doc_103 }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-2 max-w-xs truncate" title="{{ $item->description }}">
                                <div class="flex flex-col">
                                    <span class="font-semibold text-slate-800 dark:text-slate-100 truncate max-w-xs">{{ $item->description }}</span>
                                    @if ($item->material_code)
                                        <span class="text-[10px] font-mono text-slate-400">{{ $item->material_code }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-2 font-mono font-semibold">
                                {{ rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ''), '0'), '.') }} {{ $item->uom }}
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex flex-col text-[11px]">
                                    <span class="font-mono font-medium text-slate-600 dark:text-slate-300">{{ $item->storage_location ?: '-' }}</span>
                                    @if ($item->storage_text)
                                        <span class="text-[10px] text-slate-400 truncate max-w-[120px]">{{ $item->storage_text }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                @if ($item->status === 'delivered')
                                    <div class="flex flex-col gap-0.5">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/25">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            Terkirim
                                        </span>
                                        @if ($item->warehouseTransmittal)
                                            <span class="text-[9px] font-mono text-slate-400 truncate max-w-[130px]" title="{{ $item->warehouseTransmittal->destination?->name }}">
                                                {{ $item->warehouseTransmittal->transmittal_no }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-medium bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Belum Kirim
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if ($item->deliveryOrderReceipt)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-sky-500/10 text-sky-700 dark:text-sky-300 border border-sky-500/20" title="Nomor DO: {{ $item->deliveryOrderReceipt->delivery_order_no }}">
                                        DO: {{ $item->deliveryOrderReceipt->delivery_order_no ?: 'Ada' }}
                                    </span>
                                @else
                                    <span class="text-[10px] text-slate-400 italic">Belum Ada DO</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-slate-400 italic">
                                Belum ada data material di dalam batch ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

