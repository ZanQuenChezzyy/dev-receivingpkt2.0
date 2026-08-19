<div
    x-data="tourGuide()"
    class="min-h-screen bg-slate-50 dark:bg-[#031525] transition-colors duration-500 pt-28 pb-24 font-sans relative overflow-hidden">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@11.0.1/dist/css/shepherd.css"/>
    <script src="https://cdn.jsdelivr.net/npm/shepherd.js@11.0.1/dist/js/shepherd.min.js"></script>

    <!-- Sophisticated Abstract Background -->
    <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden flex items-center justify-center">
        <div
            class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-blue-100/40 via-transparent to-transparent dark:from-blue-900/20 dark:via-transparent dark:to-transparent">
        </div>
        <div
            class="absolute w-full h-full bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCI+PGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMSIgZmlsbD0icmdiYSgxNDgsIDE2MywgMTg0LCAwLjE1KSIvPjwvc3ZnPg==')] opacity-50 dark:opacity-20">
        </div>

        <!-- Glow Orbs -->
        <div class="absolute top-[-10%] left-[-10%] w-[50rem] h-[50rem] bg-[#F47920] rounded-full mix-blend-multiply filter blur-[140px] opacity-10 dark:opacity-5 animate-pulse"
            style="animation-duration: 15s;"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[50rem] h-[50rem] bg-blue-600 rounded-full mix-blend-multiply filter blur-[140px] opacity-10 dark:opacity-5 animate-pulse"
            style="animation-duration: 20s;"></div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">

        <!-- Header Section -->
        <div class="mb-16 text-center relative z-10">
            <div
                class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/70 dark:bg-[#F47920]/10 border border-white dark:border-[#F47920]/20 shadow-sm backdrop-blur-md mb-6 transition-transform hover:-translate-y-0.5 duration-300">
                <span class="relative flex h-2.5 w-2.5">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#F47920] opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#F47920]"></span>
                </span>
                <span class="text-xs font-bold tracking-widest uppercase text-slate-600 dark:text-[#F47920]">Formulir
                    Pengambilan Barang</span>
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-800 dark:text-white tracking-tight mb-6">
                Material Issue <br class="hidden sm:block" />
                <span
                    class="text-transparent bg-clip-text bg-linear-to-r from-[#F47920] to-[#BE5A27] dark:from-orange-400 dark:to-[#F47920]">Request</span>
            </h1>

            <p
                class="text-base sm:text-lg text-slate-500 dark:text-slate-400 max-w-2xl mx-auto font-medium leading-relaxed">
                Formulir resmi permintaan pengambilan barang. Pastikan kuantitas yang diminta tidak melebihi <span
                    class="text-slate-800 dark:text-slate-200 font-bold border-b border-[#F47920]/30 pb-0.5">stok gudang Receiving
                    (BOH)</span> yang tersedia.
            </p>
        </div>

        <form wire:submit.prevent="confirmSubmit" class="relative" x-data="{
            diminta_oleh: $persist(@entangle('diminta_oleh').live).as('mi_diminta_oleh'),
            npk: $persist(@entangle('npk')).as('mi_npk'),
            no_hp: $persist(@entangle('no_hp')).as('mi_no_hp'),
            departemen: $persist(@entangle('departemen')).as('mi_departemen'),
            bagian: $persist(@entangle('bagian')).as('mi_bagian')
        }"
        @validation-failed.window="setTimeout(() => {
            const firstError = document.querySelector('.text-red-500.text-xs');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 100)">
            <!-- Vertical Connecting Line (Desktop Only) -->
            <div
                class="absolute left-[2.75rem] top-12 bottom-32 w-0.5 bg-gradient-to-b from-slate-200 via-slate-200 to-transparent dark:from-slate-800 dark:via-slate-800 dark:to-transparent hidden md:block z-0">
            </div>

            <!-- STEP 1: Informasi Peminta -->
            <div class="relative z-10 mb-12 group/step">
                <div class="flex items-center gap-6 mb-6">
                    <div
                        class="flex flex-col items-center justify-center w-24 h-24 sm:w-20 sm:h-20 md:w-24 md:h-24 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-white/5 shadow-sm shrink-0 relative overflow-hidden transition-all duration-500 group-hover/step:shadow-md group-hover/step:border-[#F47920]/30">
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-[#F47920]/10 to-transparent opacity-0 group-hover/step:opacity-100 transition-opacity duration-500">
                        </div>
                        <span class="text-3xl font-black text-slate-800 dark:text-white z-10">1</span>
                    </div>
                    <div>
                        <h3 class="text-2xl sm:text-3xl font-bold text-slate-800 dark:text-white tracking-tight">
                            Informasi Peminta</h3>
                        <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 mt-1.5 font-medium">Data diri
                            karyawan yang mengajukan permintaan.</p>
                    </div>
                </div>

                <div class="md:pl-[8.5rem]">
                    <div class="glass-panel p-6 sm:p-8 lg:p-10 hover:-translate-y-0.5">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                            <!-- Nama Peminta -->
                            <div data-tour="nama-peminta">
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2.5">Nama
                                    Peminta <span class="text-[#F47920]">*</span></label>
                                <div class="relative">
                                    <div
                                        class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                            </path>
                                        </svg>
                                    </div>
                                    <input type="text" x-model.debounce.300ms="diminta_oleh"
                                        class="glass-input pl-12 w-full text-slate-900 dark:text-white py-4 placeholder-slate-400"
                                        required placeholder="Contoh: Budi Santoso">
                                </div>
                                @error('diminta_oleh')
                                    <span class="text-red-500 text-xs font-semibold mt-2 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- NPK -->
                            <div data-tour="npk">
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2.5">NPK
                                    <span class="text-[#F47920]">*</span></label>
                                <div class="relative">
                                    <div
                                        class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2">
                                            </path>
                                        </svg>
                                    </div>
                                    <input type="text" x-model="npk"
                                        class="glass-input pl-12 w-full text-slate-900 dark:text-white py-4 placeholder-slate-400"
                                        required placeholder="Contoh: 4095624">
                                </div>
                                @error('npk')
                                    <span class="text-red-500 text-xs font-semibold mt-2 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
                            <div data-tour="no-hp">
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2.5">No. HP
                                    <span class="text-[#F47920]">*</span></label>
                                <div class="relative">
                                    <div
                                        class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                            </path>
                                        </svg>
                                    </div>
                                    <input type="text" x-model="no_hp"
                                        class="glass-input pl-12 w-full text-slate-900 dark:text-white py-4 placeholder-slate-400"
                                        required placeholder="Contoh: 081234567890">
                                </div>
                                @error('no_hp')
                                    <span class="text-red-500 text-xs font-semibold mt-2 block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div data-tour="departemen">
                                <label
                                    class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2.5">Departemen
                                    <span class="text-[#F47920]">*</span></label>
                                <div class="relative">
                                    <div
                                        class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                            </path>
                                        </svg>
                                    </div>
                                    <input type="text" x-model="departemen"
                                        class="glass-input pl-12 w-full text-slate-900 dark:text-white py-4 placeholder-slate-400"
                                        required placeholder="Contoh: PP&P">
                                </div>
                                @error('departemen')
                                    <span class="text-red-500 text-xs font-semibold mt-2 block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div data-tour="bagian">
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2.5">Bagian
                                    <span class="text-[#F47920]">*</span></label>
                                <div class="relative">
                                    <div
                                        class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <input type="text" x-model="bagian"
                                        class="glass-input pl-12 w-full text-slate-900 dark:text-white py-4 placeholder-slate-400"
                                        required placeholder="Contoh: Receiving">
                                </div>
                                @error('bagian')
                                    <span class="text-red-500 text-xs font-semibold mt-2 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Dokumen & Keperluan -->
            <div class="relative z-10 mb-12 group/step">
                <div class="flex items-center gap-6 mb-6">
                    <div
                        class="flex flex-col items-center justify-center w-24 h-24 sm:w-20 sm:h-20 md:w-24 md:h-24 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-white/5 shadow-sm shrink-0 relative overflow-hidden transition-all duration-500 group-hover/step:shadow-md group-hover/step:border-blue-500/30">
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-transparent opacity-0 group-hover/step:opacity-100 transition-opacity duration-500">
                        </div>
                        <span class="text-3xl font-black text-slate-800 dark:text-white z-10">2</span>
                    </div>
                    <div>
                        <h3 class="text-2xl sm:text-3xl font-bold text-slate-800 dark:text-white tracking-tight">
                            Dokumen Referensi</h3>
                        <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 mt-1.5 font-medium">Referensi
                            Purchase Order dan keperluan teknis.</p>
                    </div>
                </div>

                <div class="md:pl-[8.5rem]">
                    <div class="glass-panel p-6 sm:p-8 lg:p-10 hover:-translate-y-0.5">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                            <div class="min-w-0" data-tour="tanggal">
                                <label
                                    class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2.5">Tanggal
                                    Pengajuan <span class="text-[#F47920]">*</span></label>
                                <div class="relative min-w-0">
                                    <div
                                        class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <input type="date" wire:model="tanggal"
                                        class="glass-input appearance-none block box-border min-w-0 max-w-full pl-12 w-full text-slate-900 dark:text-white py-4 [&::-webkit-calendar-picker-indicator]:hidden"
                                        required>
                                </div>
                                @error('tanggal')
                                    <span class="text-red-500 text-xs font-semibold mt-2 block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-col gap-3 mb-4" data-tour="search-mode">
                                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300">Cari Berdasarkan <span class="text-[#F47920]">*</span></label>
                                    <div class="flex w-full items-center bg-slate-100/90 dark:bg-slate-800/90 rounded-2xl p-1.5 backdrop-blur-md border border-slate-200/60 dark:border-white/5 shadow-inner">
                                        <button type="button" wire:click="$set('search_mode', 'po')" 
                                            class="flex-1 flex justify-center items-center gap-2 px-4 py-2.5 text-sm font-bold rounded-xl transition-all duration-300 {{ $search_mode === 'po' ? 'bg-white dark:bg-[#F47920] shadow-[0_2px_10px_rgba(0,0,0,0.08)] dark:shadow-[0_4px_15px_rgba(244,121,32,0.3)] text-[#F47920] dark:text-white scale-[1.02]' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-200/50 dark:hover:bg-slate-700/50' }}">
                                            Nomor PO
                                        </button>
                                        <button type="button" wire:click="$set('search_mode', 'sn')" 
                                            class="flex-1 flex justify-center items-center gap-2 px-4 py-2.5 text-sm font-bold rounded-xl transition-all duration-300 {{ $search_mode === 'sn' ? 'bg-white dark:bg-[#F47920] shadow-[0_2px_10px_rgba(0,0,0,0.08)] dark:shadow-[0_4px_15px_rgba(244,121,32,0.3)] text-[#F47920] dark:text-white scale-[1.02]' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-200/50 dark:hover:bg-slate-700/50' }}">
                                            Stock Number
                                        </button>
                                    </div>
                                </div>
                                <div data-tour="po"
                                    class="relative rounded-2xl shadow-sm overflow-hidden border border-slate-200/80 dark:border-white/10 focus-within:ring-2 focus-within:ring-blue-500/50 focus-within:border-blue-500 transition-all bg-white/50 dark:bg-black/20 backdrop-blur-md">
                                    <div class="absolute top-4 left-4 flex items-center pointer-events-none z-10">
                                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                    <input type="text" wire:model.live.debounce.300ms="po_search"
                                        placeholder="{{ $search_mode === 'po' ? 'Contoh: 5300064524' : 'Contoh: 6000000' }}"
                                        class="pl-12 w-full bg-transparent border-0 border-b border-slate-200/50 dark:border-white/5 text-slate-900 dark:text-white py-4 focus:ring-0 placeholder-slate-400">
                                    <div
                                        class="w-full h-40 overflow-y-auto scrollbar-thin scrollbar-thumb-slate-200 dark:scrollbar-thumb-slate-700 py-2">
                                        @forelse($available_pos as $po)
                                            @php $isSelected = (string)$purchase_order_issued_id === (string)$po->id; @endphp
                                            <div>
                                                <input type="radio" wire:model.live="purchase_order_issued_id"
                                                    id="po_{{ $po->id }}" value="{{ $po->id }}"
                                                    class="hidden">
                                                <label for="po_{{ $po->id }}"
                                                    class="flex items-center justify-between py-2.5 px-4 rounded-lg cursor-pointer mx-2 my-0.5 transition-all font-medium {{ $isSelected ? 'bg-[#F47920] text-white shadow-md dark:bg-[#F47920] dark:text-white' : 'hover:bg-[#F47920]/10 hover:text-[#F47920] dark:hover:bg-[#F47920]/20 dark:hover:text-[#F47920]' }}">
                                                    <span>{{ $po->purchase_order_no }}</span>
                                                    @if ($isSelected)
                                                        <svg class="w-5 h-5 text-white" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor"
                                                            stroke-width="3">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    @endif
                                                </label>
                                            </div>
                                        @empty
                                            <div class="py-2.5 px-4 text-slate-400 italic mx-2">Ketik untuk mencari
                                                {{ $search_mode === 'po' ? 'PO' : 'Stock Number' }}...</div>
                                        @endforelse
                                    </div>
                                </div>
                                @error('purchase_order_issued_id')
                                    <span class="text-red-500 text-xs font-semibold mt-2 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 lg:gap-6 mb-8">
                            <div data-tour="reservasi">
                                <label
                                    class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2.5 uppercase tracking-wider">No.
                                    Reservasi</label>
                                <input type="text" wire:model="no_reservasi" placeholder="Contoh: 578837291"
                                    class="glass-input w-full text-slate-900 dark:text-white py-3.5 px-4 placeholder-slate-400">
                            </div>
                            <div data-tour="jor">
                                <label
                                    class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2.5 uppercase tracking-wider">No.
                                    JOR/WO</label>
                                <input type="text" wire:model="no_jor_wo" placeholder="Contoh: 578837291"
                                    class="glass-input w-full text-slate-900 dark:text-white py-3.5 px-4 placeholder-slate-400">
                            </div>
                            <div data-tour="alat">
                                <label
                                    class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2.5 uppercase tracking-wider">No.
                                    Alat</label>
                                <input type="text" wire:model="no_alat" placeholder="Contoh: PL-01"
                                    class="glass-input w-full text-slate-900 dark:text-white py-3.5 px-4 placeholder-slate-400">
                            </div>
                            <div data-tour="kode-biaya">
                                <label
                                    class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2.5 uppercase tracking-wider">Kode
                                    Biaya</label>
                                <input type="text" wire:model="kode_biaya" placeholder="Contoh: 51000"
                                    class="glass-input w-full text-slate-900 dark:text-white py-3.5 px-4 placeholder-slate-400">
                            </div>
                        </div>

                        <div data-tour="kegunaan">
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2.5">Digunakan
                                Untuk <span class="text-[#F47920]">*</span></label>
                            <textarea wire:model="digunakan_untuk" rows="3"
                                class="glass-input w-full text-slate-900 dark:text-white py-4 px-5 placeholder-slate-400" required
                                placeholder="Jelaskan secara singkat peruntukan material ini..."></textarea>
                            @error('digunakan_untuk')
                                <span class="text-red-500 text-xs font-semibold mt-2 block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Item Material -->
            @if ($purchase_order_issued_id)
                @php
                    $totalBoh = collect($available_po_items)->sum('combined_boh');
                    $selectedPo = collect($available_pos)->firstWhere('id', $purchase_order_issued_id);
                    $poNumber = $selectedPo ? $selectedPo->purchase_order_no : 'terpilih';
                @endphp

                @if ($totalBoh <= 0)
                    <div class="relative z-10 group/step animate-fade-in-up mb-12">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                            <div class="flex items-center gap-6">
                                <div
                                    class="flex flex-col items-center justify-center w-24 h-24 sm:w-20 sm:h-20 md:w-24 md:h-24 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-white/5 shadow-sm shrink-0 relative overflow-hidden transition-all duration-500 group-hover/step:shadow-md group-hover/step:border-blue-500/30">
                                    <div
                                        class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-transparent opacity-0 group-hover/step:opacity-100 transition-opacity duration-500">
                                    </div>
                                    <span class="text-3xl font-black text-slate-800 dark:text-white z-10">3</span>
                                </div>
                                <div>
                                    <h3
                                        class="text-2xl sm:text-3xl font-bold text-slate-800 dark:text-white tracking-tight">
                                        Daftar Material</h3>
                                    <p
                                        class="text-sm sm:text-base text-slate-500 dark:text-slate-400 mt-1.5 font-medium">
                                        Pilih item dari PO dan tentukan jumlah pengambilan.</p>
                                </div>
                            </div>
                        </div>

                        <div class="md:pl-[8.5rem]">
                            <div
                                class="bg-blue-50/80 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 backdrop-blur-xl rounded-[2.5rem] p-8 flex items-center justify-center text-center shadow-sm">
                                <div>
                                    <div
                                        class="w-16 h-16 bg-blue-100 dark:bg-blue-800/50 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600 dark:text-blue-400">
                                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-2">Semua Item Telah
                                        Diambil</h3>
                                    <p class="text-slate-500 dark:text-slate-400">Seluruh material pada PO
                                        <strong>{{ $poNumber }}</strong> sudah habis diambil (Stok BOH 0).
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="relative z-10 group/step animate-fade-in-up mb-12">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                            <div class="flex items-center gap-6">
                                <div
                                    class="flex flex-col items-center justify-center w-24 h-24 sm:w-20 sm:h-20 md:w-24 md:h-24 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-white/5 shadow-sm shrink-0 relative overflow-hidden transition-all duration-500 group-hover/step:shadow-md group-hover/step:border-green-500/30">
                                    <div
                                        class="absolute inset-0 bg-gradient-to-br from-green-500/10 to-transparent opacity-0 group-hover/step:opacity-100 transition-opacity duration-500">
                                    </div>
                                    <span class="text-3xl font-black text-slate-800 dark:text-white z-10">3</span>
                                </div>
                                <div>
                                    <h3
                                        class="text-2xl sm:text-3xl font-bold text-slate-800 dark:text-white tracking-tight">
                                        Daftar Material</h3>
                                    <p
                                        class="text-sm sm:text-base text-slate-500 dark:text-slate-400 mt-1.5 font-medium">
                                        Pilih item dari PO dan tentukan jumlah pengambilan.</p>
                                </div>
                            </div>
                            <div class="md:pl-0 sm:self-center self-start pl-[5.5rem]">
                                @if (count($details) < count($available_po_items))
                                    <button type="button" wire:click="addDetail(true)" data-tour="tambah-item"
                                        class="glass-btn flex items-center gap-2.5 px-5 py-3 bg-slate-50 dark:bg-slate-800 rounded-2xl text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 font-bold transition-all shadow-sm group">
                                        <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4">
                                            </path>
                                        </svg>
                                        Tambah Item
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="md:pl-[8.5rem] space-y-6">
                            @foreach ($details as $index => $detail)
                                <div class="relative glass-panel p-6 sm:p-8 group/card overflow-hidden">

                                    <!-- Item Header -->
                                    <div
                                        class="flex justify-between items-center mb-6 pb-5 border-b border-slate-200/60 dark:border-white/5">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-10 h-10 rounded-2xl bg-slate-100 dark:bg-white/5 flex items-center justify-center text-slate-500 dark:text-slate-400 font-black text-sm border border-slate-200/50 dark:border-white/5">
                                                {{ $index + 1 }}
                                            </div>
                                            <h4 class="text-lg font-bold text-slate-700 dark:text-slate-200">Item
                                                Pengambilan</h4>
                                        </div>
                                        @if (count($details) > 1)
                                            <button type="button" wire:click="removeDetail({{ $index }})"
                                                class="p-2.5 text-slate-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/20 dark:hover:text-red-400 rounded-xl transition-colors"
                                                title="Hapus Item">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                                        <!-- Pilih Item -->
                                        <div class="xl:col-span-3" @if($loop->first) data-tour="pilih-item" @endif>
                                            <label
                                                class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2.5 uppercase tracking-wider">Pilih
                                                Item <span class="text-red-500">*</span></label>
                                            <div class="relative">
                                                <select
                                                    wire:model.live="details.{{ $index }}.delivery_order_receipt_detail_id"
                                                    class="glass-input w-full appearance-none text-slate-900 dark:text-white px-5 py-4 font-semibold"
                                                    required>
                                                    <option value="">-- Item No --</option>
                                                    @foreach ($available_po_items as $item)
                                                        @php
                                                            $itemId = is_array($item) ? $item['id'] : $item->id;
                                                            $itemNo = is_array($item)
                                                                ? $item['item_no']
                                                                : $item->item_no;
                                                            $matCode = is_array($item)
                                                                ? $item['material_code']
                                                                : $item->material_code;

                                                            $isSelectedInOtherRow = collect($details)
                                                                ->reject(fn($val, $key) => $key == $index)
                                                                ->contains('delivery_order_receipt_detail_id', $itemId);
                                                            $itemDesc = is_array($item) ? ($item['description'] ?? '') : ($item->description ?? '');
                                                        @endphp
                                                        @if (!$isSelectedInOtherRow)
                                                            <option value="{{ $itemId }}">
                                                                Item {{ $itemNo }} - {{ \Illuminate\Support\Str::limit($itemDesc, 50) }}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                                <div
                                                    class="absolute inset-y-0 right-0 flex items-center px-5 pointer-events-none text-slate-400">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            @error("details.{$index}.delivery_order_receipt_detail_id")
                                                <span
                                                    class="text-red-500 text-xs font-semibold mt-2 block">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <!-- Deskripsi -->
                                        <div class="xl:col-span-6" @if($loop->first) data-tour="deskripsi" @endif>
                                            <label
                                                class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2.5 uppercase tracking-wider">Deskripsi</label>
                                            <input type="text" value="{{ $details[$index]['description'] }}"
                                                class="w-full bg-slate-100/50 dark:bg-black/40 border border-slate-200/50 dark:border-white/5 text-slate-500 dark:text-slate-400 rounded-2xl px-5 py-4 cursor-not-allowed font-medium"
                                                readonly placeholder="Deskripsi terisi otomatis...">
                                        </div>

                                        <!-- Stock No -->
                                        <div class="xl:col-span-3" @if($loop->first) data-tour="stock-no" @endif>
                                            <label
                                                class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2.5 uppercase tracking-wider">Stock
                                                No.</label>
                                            <input type="text" value="{{ $details[$index]['stock_no'] }}"
                                                class="w-full bg-slate-100/50 dark:bg-black/40 border border-slate-200/50 dark:border-white/5 text-slate-500 dark:text-slate-400 rounded-2xl px-5 py-4 cursor-not-allowed font-semibold tracking-wider text-center"
                                                readonly placeholder="-">
                                        </div>

                                        <!-- Kuantitas Section -->
                                        <div class="xl:col-span-12 mt-2">
                                            <div
                                                class="bg-gradient-to-r from-slate-50 to-white dark:from-white/5 dark:to-transparent border border-slate-200/60 dark:border-white/5 rounded-3xl p-5 sm:p-6 flex flex-col md:flex-row items-center gap-6 md:gap-8 shadow-sm">

                                                <!-- BOH Panel -->
                                                <div @if($loop->first) data-tour="boh" @endif
                                                    class="flex flex-col items-center justify-center p-4 px-8 bg-white dark:bg-[#051F34]/80 rounded-2xl shadow-[0_4px_15px_rgb(0,0,0,0.05)] border border-slate-100 dark:border-white/5 min-w-[160px]">
                                                    <span
                                                        class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Stok
                                                        Gudang Receiving (BOH)</span>
                                                    <div class="flex items-baseline gap-1.5">
                                                        <span
                                                            class="text-3xl font-black text-slate-800 dark:text-slate-200">{{ $details[$index]['boh'] !== '' ? $details[$index]['boh'] : '-' }}</span>
                                                        <span
                                                            class="text-sm font-bold text-slate-400">{{ $details[$index]['uoi'] ?: '' }}</span>
                                                    </div>
                                                </div>

                                                <div class="hidden md:block w-px h-16 bg-slate-200 dark:bg-white/10">
                                                </div>

                                                <!-- Input Qty -->
                                                <div class="flex-1 w-full" @if($loop->first) data-tour="qty" @endif>
                                                    <label
                                                        class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-3">Jumlah
                                                        Pengambilan <span class="text-[#F47920]">*</span></label>
                                                    <div class="relative flex items-stretch h-[3.5rem]">
                                                        <input type="number" step="0.01"
                                                            wire:model.live.debounce.300ms="details.{{ $index }}.diminta"
                                                            class="block w-full h-full text-2xl font-black text-[#F47920] bg-white/50 dark:bg-black/20 backdrop-blur-md border border-slate-200/80 dark:border-white/10 rounded-l-2xl py-0 pl-6 focus:ring-2 focus:ring-[#F47920]/50 focus:border-[#F47920] focus:bg-white dark:focus:bg-slate-900/60 transition-all shadow-sm"
                                                            required placeholder="0">
                                                        <div
                                                            class="flex items-center justify-center min-w-[5rem] h-full px-5 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-[#F47920]/20 dark:to-[#F47920]/10 border border-l-0 border-slate-200/80 dark:border-white/10 rounded-r-2xl text-orange-600 dark:text-[#F47920] font-black text-lg shadow-sm">
                                                            {{ $details[$index]['uoi'] ?: 'UOI' }}
                                                        </div>
                                                    </div>
                                                    @error("details.{$index}.diminta")
                                                        <span
                                                            class="text-red-500 text-xs font-semibold mt-2 block">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- STEP 4: Tanda Tangan -->
                    <div class="relative z-10 mb-12 group/step animate-fade-in-up" style="animation-delay: 100ms;">
                        <div class="flex items-center gap-6 mb-6">
                            <div
                                class="flex flex-col items-center justify-center w-24 h-24 sm:w-20 sm:h-20 md:w-24 md:h-24 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-white/5 shadow-sm shrink-0 relative overflow-hidden transition-all duration-500 group-hover/step:shadow-md group-hover/step:border-purple-500/30">
                                <div
                                    class="absolute inset-0 bg-gradient-to-br from-purple-500/10 to-transparent opacity-0 group-hover/step:opacity-100 transition-opacity duration-500">
                                </div>
                                <span class="text-3xl font-black text-slate-800 dark:text-white z-10">4</span>
                            </div>
                            <div>
                                <h3
                                    class="text-2xl sm:text-3xl font-bold text-slate-800 dark:text-white tracking-tight">
                                    Persetujuan & Tanda Tangan</h3>
                                <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 mt-1.5 font-medium">
                                    Silakan berikan tanda tangan digital sebagai bukti pengambilan material fisik.</p>
                            </div>
                        </div>

                        <div class="md:pl-[8.5rem]">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Tanda Tangan Peminta (Wajib Selalu) -->
                                                <div class="space-y-3" data-tour="ttd-peminta">
                                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300">
                                        Tanda Tangan Peminta <span class="text-red-500">*</span>
                                    </label>
                                    <p class="text-xs text-slate-500 mb-2">Tanda tangan yang akan dicantumkan pada
                                        kolom Diminta dan Diterima.</p>

                                    <x-signature-pad wire:model="diminta_signature" id="diminta_signature"
                                        placeholder="Tanda tangan di sini..." />
                                    @error('diminta_signature')
                                        <span class="text-red-500 text-xs font-bold">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Tanda Tangan ISTEK (Muncul Jika requiresIstekSignature = true) -->
                                @if ($requiresIstekSignature)
                                    <div data-tour="ttd-istek"
                                        class="space-y-3 p-5 rounded-2xl border-2 border-orange-200 dark:border-orange-900/50 bg-orange-50/50 dark:bg-orange-900/10">
                                        <div class="flex items-center gap-2 mb-2 text-orange-600 dark:text-orange-400">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="text-xs font-bold">Barang Belum GRS (Butuh Izin)</span>
                                        </div>

                                        <div class="mb-4">
                                            <label
                                                class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                                                Pilih Pihak ISTEK <span class="text-red-500">*</span>
                                            </label>
                                            <select wire:model.live="pilihan_istek"
                                                class="block w-full text-base font-medium bg-white/50 dark:bg-black/20 backdrop-blur-md border border-slate-200/80 dark:border-white/10 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#F47920]/50 focus:border-[#F47920] focus:bg-white dark:focus:bg-slate-900/60 transition-all shadow-sm">
                                                <option value="">-- Pilih --</option>
                                                <option value="Pasarela">Pasarela</option>
                                                <option value="Joko">Joko</option>
                                                <option value="Lainnya">Lainnya...</option>
                                            </select>
                                        </div>
                                        @if ($pilihan_istek === 'Lainnya')
                                            <div class="mb-4">
                                                <label
                                                    class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                                                    Nama Pihak ISTEK (Penyetuju) <span
                                                        class="text-red-500">*</span>
                                                </label>
                                                <input type="text" wire:model="disetujui_oleh"
                                                    class="block w-full text-base font-medium bg-white/50 dark:bg-black/20 backdrop-blur-md border border-slate-200/80 dark:border-white/10 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#F47920]/50 focus:border-[#F47920] focus:bg-white dark:focus:bg-slate-900/60 transition-all shadow-sm"
                                                    placeholder="Contoh: Joko Susilo">
                                                @error('disetujui_oleh')
                                                    <span
                                                        class="text-red-500 text-xs font-bold">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        @endif

                                        <div class="mb-4">
                                            <label
                                                class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                                                NPK Pihak ISTEK <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" wire:model="disetujui_npk"
                                                class="block w-full text-base font-medium bg-white/50 dark:bg-black/20 backdrop-blur-md border border-slate-200/80 dark:border-white/10 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#F47920]/50 focus:border-[#F47920] focus:bg-white dark:focus:bg-slate-900/60 transition-all shadow-sm disabled:opacity-60 disabled:bg-slate-100/50 dark:disabled:bg-slate-800/50 disabled:cursor-not-allowed"
                                                placeholder="Contoh: 654321" {{ $pilihan_istek !== 'Lainnya' ? 'disabled' : '' }}>
                                            @error('disetujui_npk')
                                                <span class="text-red-500 text-xs font-bold">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mt-4">
                                            Tanda Tangan ISTEK <span class="text-red-500">*</span>
                                        </label>
                                        <x-signature-pad wire:model="disetujui_signature" id="disetujui_signature"
                                            placeholder="Tanda tangan pihak ISTEK di sini..." />
                                        @error('disetujui_signature')
                                            <span class="text-red-500 text-xs font-bold">{{ $message }}</span>
                                        @enderror
                                    </div>
                                @endif

                                <!-- Tanda Tangan Receiving -->
                                <div data-tour="ttd-receiving"
                                    class="space-y-3 p-5 rounded-2xl border border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-slate-900/50 mt-6">
                                    <div class="flex items-center gap-2 mb-2 text-slate-600 dark:text-slate-400">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <span class="text-xs font-bold">Pihak Gudang (Receiving)</span>
                                    </div>

                                    <div class="mb-4">
                                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                                            Pilih Pihak Receiving <span class="text-red-500">*</span>
                                        </label>
                                        <select wire:model.live="pilihan_receiving"
                                            class="block w-full text-base font-medium bg-white/50 dark:bg-black/20 backdrop-blur-md border border-slate-200/80 dark:border-white/10 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#F47920]/50 focus:border-[#F47920] focus:bg-white dark:focus:bg-slate-900/60 transition-all shadow-sm">
                                            <option value="">-- Pilih --</option>
                                            @foreach ($receiving_users as $user)
                                                <option value="{{ $user['id'] }}">{{ $user['name'] }}</option>
                                            @endforeach
                                            <option value="Lainnya">Lainnya...</option>
                                        </select>
                                    </div>
                                    @if ($pilihan_receiving === 'Lainnya')
                                        <div class="mb-4">
                                            <label
                                                class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                                                Nama Pihak Receiving <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" wire:model="diserahkan_oleh"
                                                class="block w-full text-base font-medium bg-white/50 dark:bg-black/20 backdrop-blur-md border border-slate-200/80 dark:border-white/10 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#F47920]/50 focus:border-[#F47920] focus:bg-white dark:focus:bg-slate-900/60 transition-all shadow-sm"
                                                placeholder="Contoh: Agus Pratama">
                                            @error('diserahkan_oleh')
                                                <span
                                                    class="text-red-500 text-xs font-bold">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    @endif

                                    <div class="mb-4">
                                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                                            NPK Pihak Receiving <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" wire:model="diserahkan_npk"
                                            class="block w-full text-base font-medium bg-white/50 dark:bg-black/20 backdrop-blur-md border border-slate-200/80 dark:border-white/10 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#F47920]/50 focus:border-[#F47920] focus:bg-white dark:focus:bg-slate-900/60 transition-all shadow-sm disabled:opacity-60 disabled:bg-slate-100/50 dark:disabled:bg-slate-800/50 disabled:cursor-not-allowed"
                                            placeholder="Contoh: 112233" {{ $pilihan_receiving !== 'Lainnya' ? 'disabled' : '' }}>
                                        @error('diserahkan_npk')
                                            <span class="text-red-500 text-xs font-bold">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mt-4">
                                        Tanda Tangan Receiving <span class="text-red-500">*</span>
                                    </label>
                                    <x-signature-pad wire:model="diserahkan_signature" id="diserahkan_signature"
                                        placeholder="Tanda tangan pihak receiving di sini..." />
                                    @error('diserahkan_signature')
                                        <span class="text-red-500 text-xs font-bold">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Area -->
                    <div class="relative z-10 md:pl-[8.5rem] mt-12">
                        <!-- Agreement Checkbox -->
                        <div class="mb-8">
                            <label class="flex items-start gap-4 cursor-pointer group">
                                <div class="relative flex items-center justify-center shrink-0 mt-1">
                                    <input type="checkbox" wire:model="agreement"
                                        class="peer appearance-none w-6 h-6 border-2 border-slate-300 dark:border-slate-600 rounded-lg bg-white/50 dark:bg-black/20 checked:bg-[#F47920] checked:border-[#F47920] focus:ring-2 focus:ring-offset-2 focus:ring-[#F47920] dark:focus:ring-offset-[#031525] transition-all cursor-pointer shadow-sm">
                                    <svg class="absolute w-4 h-4 text-white opacity-0 peer-checked:opacity-100 pointer-events-none transition-opacity"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7">
                                        </path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <span
                                        class="text-sm font-semibold text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">
                                        Saya menyatakan bahwa saya telah mengisi data di atas dengan sebenar-benarnya
                                        dan setuju untuk menandatangani dokumen serah terima pengambilan secara fisik di
                                        Gudang Receiving.
                                    </span>
                                    @error('agreement')
                                        <span
                                            class="text-red-500 text-xs font-bold mt-1.5 block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </label>
                        </div>

                        <div
                            class="pt-8 border-t border-slate-200 dark:border-white/10 flex flex-col-reverse sm:flex-row justify-between items-center gap-6">
                            <a href="{{ url('/') }}" wire:navigate
                                class="w-full sm:w-auto text-center px-6 py-4 text-sm font-bold text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white hover:bg-slate-200/50 dark:hover:bg-white/5 rounded-2xl transition-all">
                                &larr; Kembali ke Beranda
                            </a>

                            <button type="button" wire:click="confirmSubmit"
                                class="w-full sm:w-auto group relative inline-flex items-center justify-center px-12 py-4 text-lg font-black text-white bg-gradient-to-r from-[#F47920] to-[#BE5A27] rounded-2xl overflow-hidden transition-all duration-300 shadow-[0_10px_20px_rgba(244,121,32,0.2)] hover:shadow-[0_15px_30px_rgba(244,121,32,0.4)] hover:-translate-y-1 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                                <span
                                    class="absolute inset-0 w-0 bg-white/20 transition-all duration-500 ease-out group-hover:w-full"></span>
                                <span class="relative flex items-center gap-3">
                                    <svg wire:loading.remove wire:target="confirmSubmit" class="w-6 h-6"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                        </path>
                                    </svg>
                                    <svg wire:loading wire:target="confirmSubmit"
                                        class="animate-spin -ml-1 h-6 w-6 text-white"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <span wire:loading.remove wire:target="confirmSubmit">Kirim Pengajuan</span>
                                    <span wire:loading wire:target="confirmSubmit">Memproses Data...</span>
                                </span>
                            </button>
                        </div>
                    </div>
                @endif
            @endif
        </form>
    </div>

    <!-- Confirmation Modal -->
    @if ($showConfirmModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6" x-data="{ show: false }"
            x-init="setTimeout(() => show = true, 50)">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-[#031525]/80 backdrop-blur-sm transition-opacity duration-300 ease-out"
                :class="show ? 'opacity-100' : 'opacity-0'" wire:click="$set('showConfirmModal', false)"></div>

            <!-- Modal Dialog -->
            <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl overflow-hidden border border-slate-200 dark:border-white/10 transition-all duration-300 ease-out transform"
                :class="show ? 'opacity-100 translate-y-0 scale-100' : 'opacity-0 translate-y-8 scale-95'">

                <!-- Decorative Header -->
                <div
                    class="absolute top-0 inset-x-0 h-32 bg-gradient-to-br from-[#F47920]/20 to-orange-600/20 dark:from-[#F47920]/10 dark:to-orange-900/10">
                </div>

                <div class="relative p-8 sm:p-10">
                    <div
                        class="w-20 h-20 bg-white dark:bg-slate-800 rounded-3xl shadow-xl flex items-center justify-center mx-auto mb-6 border border-slate-100 dark:border-white/5 transform -rotate-6">
                        <div
                            class="w-16 h-16 bg-gradient-to-br from-[#F47920] to-[#BE5A27] rounded-2xl flex items-center justify-center transform rotate-6">
                            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4">
                                </path>
                            </svg>
                        </div>
                    </div>

                    <div class="text-center">
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white mb-3">Konfirmasi Pengajuan</h3>
                        <p class="text-slate-500 dark:text-slate-400 mb-8 font-medium">
                            Apakah Anda yakin ingin mengirim Material Issue Request ini? Pastikan kembali jumlah
                            pengambilan sudah sesuai dengan yang dibutuhkan.
                        </p>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row gap-4">
                        <button type="button" wire:click="$set('showConfirmModal', false)"
                            class="flex-1 px-6 py-3.5 text-sm font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-colors text-center">
                            Batal
                        </button>
                        <button type="button" wire:click="submit"
                            class="flex-1 px-6 py-3.5 text-sm font-bold text-white bg-gradient-to-r from-[#F47920] to-[#BE5A27] hover:from-orange-500 hover:to-orange-700 rounded-xl transition-all shadow-lg shadow-orange-500/30 flex items-center justify-center gap-2">
                            <span wire:loading.remove wire:target="submit">Ya, Kirim Sekarang</span>
                            <svg wire:loading wire:target="submit" class="animate-spin h-5 w-5 text-white"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span wire:loading wire:target="submit">Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Success Modal -->
    @if ($showSuccessMessage)
        <div class="fixed inset-0 z-[100] flex items-center justify-center px-4 overflow-hidden" x-data="{ show: false }" x-init="setTimeout(() => show = true, 50)">
            <!-- Overlay -->
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                wire:click="$set('showSuccessMessage', false)"></div>

            <!-- Modal Content -->
            <div
                class="relative bg-white dark:bg-slate-900 w-full max-w-sm rounded-[2.5rem] shadow-2xl overflow-hidden animate-fade-in-up border border-slate-200/50 dark:border-white/10 text-center">
                <div class="relative p-8 sm:p-10">
                    <!-- Checkmark Animation -->
                    <div class="w-24 h-24 mx-auto mb-6 relative">
                        <div class="absolute inset-0 bg-green-100 dark:bg-green-900/30 rounded-full animate-ping opacity-75"></div>
                        <div class="relative flex items-center justify-center w-24 h-24 bg-green-500 rounded-full shadow-lg shadow-green-500/30 text-white transform transition-all duration-500 scale-100">
                            <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <path class="animate-[dash_0.5s_ease-out_forwards]" stroke-dasharray="30" stroke-dashoffset="30" d="M5 13l4 4L19 7" style="animation: dash 0.5s ease-out forwards;"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <style>
                        @keyframes dash {
                            to {
                                stroke-dashoffset: 0;
                            }
                        }
                    </style>

                    <h3 class="text-2xl font-black text-slate-800 dark:text-white mb-3">Berhasil!</h3>
                    <p class="text-slate-500 dark:text-slate-400 mb-8 font-medium">
                        Material Issue Request Anda telah direkam ke dalam sistem dan akan segera divalidasi oleh tim gudang.
                    </p>

                    <button type="button" wire:click="$set('showSuccessMessage', false)"
                        class="w-full px-6 py-3.5 text-sm font-bold text-white bg-green-500 hover:bg-green-600 rounded-xl transition-all shadow-lg shadow-green-500/30">
                        Selesai
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Toast Notifications -->
    <div x-data="{ 
            toasts: [],
            addToast(message, type) {
                const id = Date.now();
                this.toasts.push({ id, message, type });
                setTimeout(() => {
                    this.removeToast(id);
                }, 3000);
            },
            removeToast(id) {
                window.dispatchEvent(new CustomEvent('hide-toast', { detail: { id } }));
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }, 200);
            }
        }" 
        @item-added.window="addToast('Berhasil menambah baris item', 'success')"
        @item-removed.window="addToast('Berhasil menghapus item', 'danger')"
        class="fixed top-24 right-4 sm:top-28 sm:right-8 z-[100] flex flex-col gap-3 pointer-events-none">
        
        <template x-for="toast in toasts" :key="toast.id">
            <div x-data="{ show: false }"
                x-init="setTimeout(() => show = true, 50)"
                x-show="show" 
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 translate-x-12 scale-95"
                x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 translate-x-0 scale-100"
                x-transition:leave-end="opacity-0 translate-x-12 scale-95"
                @hide-toast.window="if ($event.detail.id === toast.id) show = false"
                class="pointer-events-auto relative overflow-hidden flex items-center gap-3.5 px-5 py-4 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] backdrop-blur-xl bg-white/70 dark:bg-slate-900/70 border border-white/60 dark:border-white/10"
                >
                
                <!-- Colored accent line -->
                <div class="absolute left-0 top-0 bottom-0 w-1.5"
                    :class="{
                        'bg-gradient-to-b from-green-400 to-green-600': toast.type === 'success',
                        'bg-gradient-to-b from-red-400 to-red-600': toast.type === 'danger'
                    }"></div>

                <!-- Icon Container -->
                <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 shadow-inner"
                    :class="{
                        'bg-green-100/80 dark:bg-green-500/20 text-green-600 dark:text-green-400': toast.type === 'success',
                        'bg-red-100/80 dark:bg-red-500/20 text-red-600 dark:text-red-400': toast.type === 'danger'
                    }">
                    <template x-if="toast.type === 'success'">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                    </template>
                    <template x-if="toast.type === 'danger'">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </template>
                </div>

                <div class="flex flex-col pr-4">
                    <span class="font-bold text-[13px] text-slate-800 dark:text-white" x-text="toast.type === 'success' ? 'Berhasil' : 'Pemberitahuan'"></span>
                    <span class="font-medium text-[13px] text-slate-500 dark:text-slate-400" x-text="toast.message"></span>
                </div>
                
                <!-- Close Button -->
                <button @click="removeToast(toast.id)" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </template>
    </div>

    <!-- Floating Guide Button -->
    <div x-data="{ isChatOpen: false }"
         @chat-toggled.window="isChatOpen = $event.detail.isOpen"
         x-show="!isChatOpen"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-8 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-8 scale-95"
         class="fixed bottom-6 left-6 md:bottom-8 md:left-8 z-[99]" style="transform: translateZ(0);">
        <button type="button" @click.prevent="startTour()" class="flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-[#F47920] to-[#BE5A27] hover:from-orange-500 hover:to-[#cf6935] text-white rounded-full shadow-[0_8px_20px_rgba(244,121,32,0.3)] hover:shadow-[0_12px_25px_rgba(244,121,32,0.4)] active:scale-95 transition-all duration-300 group cursor-pointer" style="-webkit-tap-highlight-color: transparent;">
            <svg class="w-5 h-5 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-bold text-sm tracking-wide">Panduan Pengisian</span>
        </button>
    </div>

    <style>
        .shepherd-element {
            border-radius: 1.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(244, 121, 32, 0.2);
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(12px);
            max-width: 400px;
            animation: shepherd-pop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 9999 !important; /* Fixed z-index to be above overlay */
        }
        @keyframes shepherd-pop {
            0% { opacity: 0; transform: scale(0.9) translateY(10px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        .shepherd-header {
            background: transparent !important;
            border-bottom: 1px solid rgba(244, 121, 32, 0.1);
            padding: 1.25rem 1.5rem 0.75rem;
        }
        .shepherd-title {
            font-size: 1.15rem;
            font-weight: 900;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .shepherd-title::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 20px;
            background: linear-gradient(to bottom, #F47920, #BE5A27);
            border-radius: 4px;
        }
        .shepherd-cancel-icon {
            color: #94a3b8;
            transition: all 0.2s;
        }
        .shepherd-cancel-icon:hover {
            color: #ef4444;
            transform: rotate(90deg);
        }
        .shepherd-text {
            padding: 1rem 1.5rem;
            font-size: 0.95rem;
            color: #475569;
            line-height: 1.6;
        }
        .shepherd-footer {
            padding: 0.5rem 1.5rem 1.5rem;
            background: transparent !important;
            border-top: none;
        }
        /* Dark Mode overrides */
        .dark .shepherd-element {
            background: rgba(15, 23, 42, 0.95) !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 0 20px 0 rgba(244, 121, 32, 0.1);
        }
        .dark .shepherd-title { color: #f8fafc; }
        .dark .shepherd-text { color: #cbd5e1; }
        .dark .shepherd-header { border-bottom-color: rgba(255, 255, 255, 0.05); }
        
        .shepherd-arrow::before {
            background: #fff !important;
            border: 1px solid rgba(244, 121, 32, 0.2) !important;
        }
        .dark .shepherd-arrow::before {
            background: #0f172a !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        
        .shepherd-button {
            border-radius: 0.75rem;
            font-weight: 700;
            padding: 0.6rem 1.25rem;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        .shepherd-button-secondary {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .shepherd-button-secondary:hover {
            background-color: #e2e8f0;
            color: #1e293b;
        }
        .dark .shepherd-button-secondary {
            background-color: #1e293b;
            color: #cbd5e1;
            border-color: rgba(255, 255, 255, 0.05);
        }
        .dark .shepherd-button-secondary:hover {
            background-color: #334155;
            color: #f8fafc;
        }
        
        .shepherd-button-primary {
            background: linear-gradient(to right, #F47920, #BE5A27);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(244, 121, 32, 0.2), 0 2px 4px -1px rgba(244, 121, 32, 0.1);
            border: none;
        }
        .shepherd-button-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 8px -1px rgba(244, 121, 32, 0.3), 0 4px 6px -1px rgba(244, 121, 32, 0.2);
        }
        
        /* Highlight Target */
        .shepherd-target.shepherd-enabled {
            box-shadow: 0 0 0 4px rgba(244, 121, 32, 0.3) !important;
            transition: all 0.3s ease;
            border-radius: 8px; /* fallback just in case */
            z-index: 10000 !important;
            position: relative;
        }
    </style>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('tourGuide', () => ({
                tourObj: null,
                init() {
                    const checkShepherd = setInterval(() => {
                        if (window.Shepherd) {
                            clearInterval(checkShepherd);
                            this.tourObj = new Shepherd.Tour({
                                useModalOverlay: true,
                                defaultStepOptions: {
                                    cancelIcon: { enabled: true },
                                    scrollTo: { behavior: 'smooth', block: 'center' }
                                }
                            });

                            const steps = [
                                { id: 'nama-peminta', title: '1. Nama Peminta', text: 'Isi dengan nama lengkap Anda yang mengajukan.', position: 'bottom' },
                                { id: 'npk', title: '2. NPK', text: 'Masukkan Nomor Pokok Karyawan Anda.', position: 'bottom' },
                                { id: 'no-hp', title: '3. NO. HP', text: 'Masukkan nomor handphone yang aktif agar mudah dihubungi.', position: 'bottom' },
                                { id: 'departemen', title: '4. Departemen', text: 'Isi dengan nama Departemen Anda.', position: 'bottom' },
                                { id: 'bagian', title: '5. Bagian', text: 'Isi dengan Sub-bagian Anda.', position: 'bottom' },
                                { id: 'tanggal', title: '6. Tanggal Pengajuan', text: 'Secara otomatis terisi tanggal hari ini.', position: 'bottom' },
                                { id: 'search-mode', title: '7. Mode Pencarian', text: 'Pilih mode pencarian terlebih dahulu (menggunakan Nomor PO atau Stock Number).', position: 'bottom' },
                                { id: 'po', title: '8. Cari & Pilih', text: 'Ketik Nomor PO atau Stock Number yang dicari, lalu klik hasil yang muncul di bawahnya.', position: 'bottom' },
                                { id: 'reservasi', title: '9. Nomor Reservasi', text: 'Jika ada, isi dengan Nomor Reservasi sebelumnya.', position: 'bottom' },
                                { id: 'jor', title: '10. Nomor JOR/WO', text: 'Jika ada, isi dengan Nomor Job Order atau Work Order.', position: 'bottom' },
                                { id: 'alat', title: '11. No. Alat', text: 'Isi dengan nomor alat yang akan menggunakan sparepart yang diambil.', position: 'bottom' },
                                { id: 'kode-biaya', title: '12. Kode Biaya', text: 'Jika ada, isi dengan kode biaya yang berkesinambungan.', position: 'bottom' },
                                { id: 'kegunaan', title: '13. Digunakan Untuk', text: '<b>Wajib diisi!</b> Alat yang diambil digunakan untuk kebutuhan apa.', position: 'bottom' },
                                
                                { id: 'pilih-item', title: '14. Pilih Nomor Item', text: 'Pilih material/item yang ingin diambil dari PO yang sudah dipilih sebelumnya. <br><br><i>(Jika ini belum muncul, pastikan Anda sudah mengisi langkah ke-8)</i>', position: 'top' },
                                { id: 'deskripsi', title: '15. Deskripsi Item', text: 'Cocokkan deskripsinya dengan material yang akan Anda ambil.', position: 'top' },
                                { id: 'stock-no', title: '16. Stock Number', text: 'Cocokkan Stock Number jika barang yang diambil memilikinya.', position: 'top' },
                                { id: 'boh', title: '17. Stok Gudang (Receiving)', text: 'Sesuaikan jumlah yang akan diambil dengan Sisa Stok yang tersedia di sini.', position: 'top' },
                                { id: 'qty', title: '18. Jumlah Pengambilan', text: 'Isi jumlah pengambilan di sini. Jumlah tidak boleh melebihi Stok Gudang Receiving.', position: 'top' },
                                { id: 'tambah-item', title: 'Item Lebih dari Satu?', text: 'Jika Anda ingin mengambil item lain dalam PO yang sama, klik tombol ini untuk menambah baris.', position: 'top' },
                                
                                { id: 'ttd-peminta', title: '19. Tanda Tangan Peminta', text: 'Tanda tangan orang yang mengambil barang saat itu.', position: 'top' },
                                { id: 'ttd-istek', title: '20. Tanda Tangan Pihak ISTEK', text: 'Tanda tangan pihak ISTEK yang menyetujui barang tersebut boleh diambil.', position: 'top' },
                                { id: 'ttd-receiving', title: '21. Tanda Tangan Receiving', text: 'Terakhir, tanda tangan pihak gudang (Receiving) yang memproses form ini.', position: 'top' }
                            ];

                            steps.forEach((s, index) => {
                                const buttons = [];
                                if (index > 0) {
                                    buttons.push({
                                        text: '&larr; Kembali',
                                        action: this.tourObj.back,
                                        classes: 'shepherd-button-secondary mr-2'
                                    });
                                }
                                if (index < steps.length - 1) {
                                    buttons.push({
                                        text: 'Lanjut &rarr;',
                                        action: this.tourObj.next,
                                        classes: 'shepherd-button-primary'
                                    });
                                } else {
                                    buttons.push({
                                        text: 'Selesai',
                                        action: this.tourObj.complete,
                                        classes: 'shepherd-button-primary bg-green-500 hover:bg-green-600'
                                    });
                                }

                                this.tourObj.addStep({
                                    id: s.id,
                                    title: s.title,
                                    text: s.text,
                                    attachTo: { element: `[data-tour="${s.id}"]`, on: s.position },
                                    buttons: buttons,
                                    showOn: () => {
                                        return document.querySelector(`[data-tour="${s.id}"]`) !== null;
                                    }
                                });
                            });
                        }
                    }, 100);
                },
                startTour() {
                    if(this.tourObj) {
                        this.tourObj.start();
                    } else {
                        alert("Panduan sedang dimuat, mohon tunggu sebentar.");
                    }
                }
            }));
        });
    </script>
</div>
