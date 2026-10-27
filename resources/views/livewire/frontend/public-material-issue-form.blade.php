<div
    x-data="tourGuide()"
    class="min-h-screen text-slate-800 dark:text-slate-200 transition-colors duration-500 pt-28 pb-32 font-sans relative overflow-hidden">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@11.0.1/dist/css/shepherd.css"/>
    <script src="https://cdn.jsdelivr.net/npm/shepherd.js@11.0.1/dist/js/shepherd.min.js"></script>

    <!-- Sophisticated Abstract Background Glows (Layout PKT Pattern shines through) -->
    <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden select-none">
        <div class="absolute top-[-5%] left-[-5%] w-[45rem] h-[45rem] bg-[#F47920] rounded-full mix-blend-multiply filter blur-[150px] opacity-10 dark:opacity-5 animate-pulse" style="animation-duration: 15s;"></div>
        <div class="absolute bottom-[-10%] right-[-5%] w-[50rem] h-[50rem] bg-[#0A4F86] rounded-full mix-blend-multiply filter blur-[160px] opacity-10 dark:opacity-10 animate-pulse" style="animation-duration: 20s;"></div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">

        <!-- Header Section -->
        <div class="mb-10 sm:mb-14 text-center relative z-10">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/80 dark:bg-white/5 border border-slate-200/80 dark:border-white/10 shadow-sm backdrop-blur-md mb-5 transition-transform hover:-translate-y-0.5 duration-300">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#F47920] opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#F47920]"></span>
                </span>
                <span class="text-xs font-bold tracking-widest uppercase text-slate-600 dark:text-[#F47920]">
                    Formulir Pengambilan Barang
                </span>
            </div>

            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-slate-800 dark:text-white tracking-tight mb-4">
                Material Issue <br class="hidden sm:inline" />
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#F47920] to-[#BE5A27] dark:from-orange-400 dark:to-[#F47920]">
                    Request (MIR)
                </span>
            </h1>

            <p class="text-sm sm:text-base lg:text-lg text-slate-600 dark:text-slate-300 max-w-2xl mx-auto font-medium leading-relaxed">
                Formulir resmi serah terima material gudang. Pastikan jumlah yang diminta tidak melebihi
                <span class="text-[#F47920] font-bold border-b border-[#F47920]/40 pb-0.5">stok gudang Receiving (BOH)</span>.
            </p>

            <!-- Quick Trust Badges -->
            <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-4 mt-6 text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100/80 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Tersimpan Otomatis di Browser
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100/80 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                    <svg class="w-3.5 h-3.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Validasi Stok Realtime
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100/80 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                    <svg class="w-3.5 h-3.5 text-[#F47920]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
                    Tanda Tangan Digital Terverifikasi
                </span>
            </div>
        </div>

        <form wire:submit.prevent="confirmSubmit" class="relative" x-data="{
            diminta_oleh: $persist(@entangle('diminta_oleh').live).as('mi_diminta_oleh'),
            npk: $persist(@entangle('npk')).as('mi_npk'),
            no_hp: $persist(@entangle('no_hp')).as('mi_no_hp'),
            departemen: $persist(@entangle('departemen')).as('mi_departemen'),
            bagian: $persist(@entangle('bagian')).as('mi_bagian'),
            toastMessage: '',
            toastType: 'success',
            showToast: false,
            toastTimeout: null,
            triggerToast(msg, type = 'success') {
                this.toastMessage = msg;
                this.toastType = type;
                this.showToast = true;
                clearTimeout(this.toastTimeout);
                this.toastTimeout = setTimeout(() => {
                    this.showToast = false;
                }, 2600);
            },
            setKegunaan(val) {
                @this.set('digunakan_untuk', val);
            }
        }"
        @item-added.window="
            triggerToast('✨ Baris material #' + ($event.detail.itemIndex || '') + ' berhasil ditambahkan!', 'success');
            setTimeout(() => {
                const cards = document.querySelectorAll('.material-item-card');
                const lastCard = cards[cards.length - 1];
                if (lastCard) {
                    lastCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    lastCard.classList.add('ring-4', 'ring-emerald-500/50', 'bg-emerald-50/80', 'dark:bg-emerald-950/40');
                    setTimeout(() => {
                        lastCard.classList.remove('ring-4', 'ring-emerald-500/50', 'bg-emerald-50/80', 'dark:bg-emerald-950/40');
                    }, 1800);
                    const selectEl = lastCard.querySelector('select');
                    if (selectEl) {
                        selectEl.focus();
                    }
                }
            }, 80);
        "
        @item-removed.window="
            triggerToast('Baris material berhasil dihapus', 'info');
        "
        @validation-failed.window="setTimeout(() => {
            const firstError = document.querySelector('.text-red-500');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 100)">

            <!-- Floating Toast Notification -->
            <div x-show="showToast"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 -translate-y-4 scale-90"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 -translate-y-4 scale-90"
                 class="fixed top-24 left-1/2 -translate-x-1/2 z-[110] pointer-events-none"
                 style="display: none;">
                <div class="px-5 py-3 rounded-2xl shadow-2xl backdrop-blur-xl border flex items-center gap-3 text-xs sm:text-sm font-bold transition-all duration-300 pointer-events-auto"
                     :class="toastType === 'success' 
                        ? 'bg-emerald-600/95 text-white border-emerald-400/40 shadow-emerald-600/30' 
                        : 'bg-slate-800/95 text-white border-slate-600/40 shadow-black/30'">
                    <template x-if="toastType === 'success'">
                        <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </template>
                    <template x-if="toastType === 'info'">
                        <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                    </template>
                    <span x-text="toastMessage"></span>
                </div>
            </div>

            <!-- STEPPER INDICATOR (Mobile Friendly & Desktop Progress Bar) -->
            <div class="mb-10 relative z-10">
                <div class="grid grid-cols-4 gap-2 sm:gap-4 p-2 sm:p-2.5 rounded-2xl sm:rounded-3xl bg-white/70 dark:bg-slate-900/60 backdrop-blur-xl border border-slate-200/70 dark:border-white/10 shadow-sm">
                    <!-- Step 1 Indicator -->
                    <div class="flex items-center gap-2 sm:gap-3 p-2 rounded-xl transition-all {{ !empty($diminta_oleh) && !empty($npk) ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-[#F47920] font-black' }}">
                        <div class="w-7 h-7 sm:w-9 sm:h-9 rounded-lg sm:rounded-xl flex items-center justify-center text-xs sm:text-sm font-black {{ !empty($diminta_oleh) && !empty($npk) ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-[#F47920]/15 text-[#F47920]' }}">
                            @if (!empty($diminta_oleh) && !empty($npk))
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @else
                                1
                            @endif
                        </div>
                        <div class="min-w-0">
                            <span class="hidden sm:block text-xs uppercase tracking-wider text-slate-400">Langkah 1</span>
                            <span class="block text-xs sm:text-sm truncate font-bold">Peminta</span>
                        </div>
                    </div>

                    <!-- Step 2 Indicator -->
                    <div class="flex items-center gap-2 sm:gap-3 p-2 rounded-xl transition-all {{ !empty($purchase_order_issued_id) ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-500 dark:text-slate-400 font-semibold' }}">
                        <div class="w-7 h-7 sm:w-9 sm:h-9 rounded-lg sm:rounded-xl flex items-center justify-center text-xs sm:text-sm font-black {{ !empty($purchase_order_issued_id) ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400' }}">
                            @if (!empty($purchase_order_issued_id))
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @else
                                2
                            @endif
                        </div>
                        <div class="min-w-0">
                            <span class="hidden sm:block text-xs uppercase tracking-wider text-slate-400">Langkah 2</span>
                            <span class="block text-xs sm:text-sm truncate font-bold">Dokumen PO</span>
                        </div>
                    </div>

                    <!-- Step 3 Indicator -->
                    <div class="flex items-center gap-2 sm:gap-3 p-2 rounded-xl transition-all {{ count($details) > 0 && !empty($details[0]['delivery_order_receipt_detail_id']) ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-500 dark:text-slate-400 font-semibold' }}">
                        <div class="w-7 h-7 sm:w-9 sm:h-9 rounded-lg sm:rounded-xl flex items-center justify-center text-xs sm:text-sm font-black {{ count($details) > 0 && !empty($details[0]['delivery_order_receipt_detail_id']) ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400' }}">
                            @if (count($details) > 0 && !empty($details[0]['delivery_order_receipt_detail_id']))
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @else
                                3
                            @endif
                        </div>
                        <div class="min-w-0">
                            <span class="hidden sm:block text-xs uppercase tracking-wider text-slate-400">Langkah 3</span>
                            <span class="block text-xs sm:text-sm truncate font-bold">Material</span>
                        </div>
                    </div>

                    <!-- Step 4 Indicator -->
                    @php
                        $isStep4Complete = !empty($diminta_signature) && !empty($diserahkan_signature) && (!$requiresIstekSignature || !empty($disetujui_signature));
                    @endphp
                    <div class="flex items-center gap-2 sm:gap-3 p-2 rounded-xl transition-all {{ $isStep4Complete ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-500 dark:text-slate-400 font-semibold' }}">
                        <div class="w-7 h-7 sm:w-9 sm:h-9 rounded-lg sm:rounded-xl flex items-center justify-center text-xs sm:text-sm font-black {{ $isStep4Complete ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400' }}">
                            @if ($isStep4Complete)
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @else
                                4
                            @endif
                        </div>
                        <div class="min-w-0">
                            <span class="hidden sm:block text-xs uppercase tracking-wider text-slate-400">Langkah 4</span>
                            <span class="block text-xs sm:text-sm truncate font-bold">Tanda Tangan</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 1: INFORMASI PEMINTA -->
            <div class="relative z-10 mb-8 sm:mb-12 group/step">
                <div class="flex items-center gap-3 sm:gap-4 mb-4 sm:mb-6">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-gradient-to-br from-[#F47920] to-[#BE5A27] text-white flex items-center justify-center font-black text-lg sm:text-xl shadow-lg shadow-orange-500/20 shrink-0">
                        1
                    </div>
                    <div>
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white tracking-tight">
                            Informasi Peminta
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                            Identitas karyawan Pupuk Kaltim yang mengajukan pengambilan material.
                        </p>
                    </div>
                </div>

                <div class="p-5 sm:p-8 rounded-3xl bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl border border-slate-200/70 dark:border-white/10 shadow-sm hover:shadow-md transition-all">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 mb-4 sm:mb-6">
                        <!-- Nama Peminta -->
                        <div data-tour="nama-peminta">
                            <label class="block text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                                Nama Peminta <span class="text-[#F47920]">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                                <input type="text" x-model.debounce.300ms="diminta_oleh"
                                    class="w-full pl-11 pr-4 py-3 sm:py-3.5 text-sm sm:text-base font-semibold text-slate-900 dark:text-white bg-slate-50/80 dark:bg-black/20 rounded-2xl border border-slate-200/80 dark:border-white/10 focus:ring-2 focus:ring-[#F47920]/40 focus:border-[#F47920] transition-all"
                                    required placeholder="Contoh: Budi Santoso">
                            </div>
                            @error('diminta_oleh')
                                <span class="text-red-500 text-xs font-semibold mt-1.5 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- NPK -->
                        <div data-tour="npk">
                            <label class="block text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                                NPK Karyawan <span class="text-[#F47920]">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                                </div>
                                <input type="text" x-model="npk"
                                    class="w-full pl-11 pr-4 py-3 sm:py-3.5 text-sm sm:text-base font-semibold text-slate-900 dark:text-white bg-slate-50/80 dark:bg-black/20 rounded-2xl border border-slate-200/80 dark:border-white/10 focus:ring-2 focus:ring-[#F47920]/40 focus:border-[#F47920] transition-all"
                                    required placeholder="Contoh: 4095624">
                            </div>
                            @error('npk')
                                <span class="text-red-500 text-xs font-semibold mt-1.5 block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
                        <!-- No HP -->
                        <div data-tour="no-hp">
                            <label class="block text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                                No. Handphone (WhatsApp) <span class="text-[#F47920]">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </div>
                                <input type="text" x-model="no_hp"
                                    class="w-full pl-11 pr-4 py-3 sm:py-3.5 text-sm sm:text-base font-semibold text-slate-900 dark:text-white bg-slate-50/80 dark:bg-black/20 rounded-2xl border border-slate-200/80 dark:border-white/10 focus:ring-2 focus:ring-[#F47920]/40 focus:border-[#F47920] transition-all"
                                    required placeholder="Contoh: 081234567890">
                            </div>
                            @error('no_hp')
                                <span class="text-red-500 text-xs font-semibold mt-1.5 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Departemen -->
                        <div data-tour="departemen">
                            <label class="block text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                                Departemen <span class="text-[#F47920]">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <input type="text" x-model="departemen"
                                    class="w-full pl-11 pr-4 py-3 sm:py-3.5 text-sm sm:text-base font-semibold text-slate-900 dark:text-white bg-slate-50/80 dark:bg-black/20 rounded-2xl border border-slate-200/80 dark:border-white/10 focus:ring-2 focus:ring-[#F47920]/40 focus:border-[#F47920] transition-all"
                                    required placeholder="Contoh: PPGP">
                            </div>
                            @error('departemen')
                                <span class="text-red-500 text-xs font-semibold mt-1.5 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Bagian -->
                        <div data-tour="bagian">
                            <label class="block text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                                Bagian / Seksi <span class="text-[#F47920]">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <input type="text" x-model="bagian"
                                    class="w-full pl-11 pr-4 py-3 sm:py-3.5 text-sm sm:text-base font-semibold text-slate-900 dark:text-white bg-slate-50/80 dark:bg-black/20 rounded-2xl border border-slate-200/80 dark:border-white/10 focus:ring-2 focus:ring-[#F47920]/40 focus:border-[#F47920] transition-all"
                                    required placeholder="Contoh: Operasi Pabrik 1">
                            </div>
                            @error('bagian')
                                <span class="text-red-500 text-xs font-semibold mt-1.5 block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: DOKUMEN & KEPERLUAN -->
            <div class="relative z-10 mb-8 sm:mb-12 group/step">
                <div class="flex items-center gap-3 sm:gap-4 mb-4 sm:mb-6">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-gradient-to-br from-blue-600 to-[#0A4F86] text-white flex items-center justify-center font-black text-lg sm:text-xl shadow-lg shadow-blue-500/20 shrink-0">
                        2
                    </div>
                    <div>
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white tracking-tight">
                            Dokumen & Referensi PO
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                            Pilih Purchase Order yang akan diambil barangnya serta isi rincian keperluan.
                        </p>
                    </div>
                </div>

                <div class="p-5 sm:p-8 rounded-3xl bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl border border-slate-200/70 dark:border-white/10 shadow-sm hover:shadow-md transition-all">
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6">
                        <!-- Tanggal Pengajuan -->
                        <div data-tour="tanggal">
                            <label class="block text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                                Tanggal Pengajuan <span class="text-[#F47920]">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <input type="date" wire:model="tanggal"
                                    class="w-full pl-11 pr-4 py-3 sm:py-3.5 text-sm sm:text-base font-semibold text-slate-900 dark:text-white bg-slate-50/80 dark:bg-black/20 rounded-2xl border border-slate-200/80 dark:border-white/10 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-all"
                                    required>
                            </div>
                            @error('tanggal')
                                <span class="text-red-500 text-xs font-semibold mt-1.5 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Search PO Mode & Input -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300">
                                    Cari & Pilih PO <span class="text-[#F47920]">*</span>
                                </label>
                                <!-- Segmented Mode Switch -->
                                <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl" data-tour="search-mode">
                                    <button type="button" wire:click="$set('search_mode', 'po')"
                                        class="px-2.5 py-1 text-xs font-bold rounded-lg transition-all {{ $search_mode === 'po' ? 'bg-[#F47920] text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400' }}">
                                        No. PO
                                    </button>
                                    <button type="button" wire:click="$set('search_mode', 'sn')"
                                        class="px-2.5 py-1 text-xs font-bold rounded-lg transition-all {{ $search_mode === 'sn' ? 'bg-[#F47920] text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400' }}">
                                        Stock No
                                    </button>
                                </div>
                            </div>

                            @if (!empty($purchase_order_issued_id))
                                <!-- Selected PO Active Badge -->
                                @php
                                    $activePoModel = \App\Models\PurchaseOrderIssued::find($purchase_order_issued_id);
                                    $hasAnyNonGrsInPo = collect($available_po_items)->contains('has_non_grs', true);
                                @endphp
                                <div class="p-3.5 rounded-2xl bg-blue-50/90 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800/50">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="text-xs font-bold text-blue-600 dark:text-blue-400 block uppercase">PO Terpilih</span>
                                                <span class="text-sm font-black text-slate-800 dark:text-white truncate block">
                                                    {{ $activePoModel?->purchase_order_no ?? 'PO #' . $purchase_order_issued_id }}
                                                </span>
                                            </div>
                                        </div>
                                        <button type="button" wire:click="clearSelectedPo"
                                            class="px-3 py-1.5 text-xs font-bold text-red-600 hover:text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-xl transition-all">
                                            Ganti PO
                                        </button>
                                    </div>
                                    @if ($hasAnyNonGrsInPo)
                                        <div class="mt-2.5 pt-2.5 border-t border-blue-200/60 dark:border-blue-800/40 flex items-center gap-2 text-xs font-semibold text-amber-700 dark:text-amber-400">
                                            <svg class="w-4 h-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            <span>PO ini memiliki item material <strong>Belum GRS</strong>. Pengambilan item tersebut mewajibkan tanda tangan ISTEK.</span>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="relative" data-tour="po">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </div>
                                    <input type="text" wire:model.live.debounce.300ms="po_search"
                                        placeholder="{{ $search_mode === 'po' ? 'Ketik nomor PO (misal: 530006...)' : 'Ketik Stock Number (misal: 600...)' }}"
                                        class="w-full pl-11 pr-4 py-3 sm:py-3.5 text-sm sm:text-base font-semibold text-slate-900 dark:text-white bg-slate-50/80 dark:bg-black/20 rounded-2xl border border-slate-200/80 dark:border-white/10 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-all">
                                </div>

                                <!-- PO Dropdown Selection List -->
                                @if (!empty($po_search) && count($available_pos) > 0)
                                    <div class="mt-2 p-2 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 shadow-xl max-h-56 overflow-y-auto space-y-1">
                                        @foreach ($available_pos as $po)
                                            <button type="button" wire:click="$set('purchase_order_issued_id', {{ $po->id }})"
                                                class="w-full text-left p-3 rounded-xl hover:bg-orange-50 dark:hover:bg-slate-700/60 transition-all flex items-center justify-between group">
                                                <div>
                                                    <span class="block text-sm font-black text-slate-800 dark:text-white group-hover:text-[#F47920]">
                                                        {{ $po->purchase_order_no }}
                                                    </span>
                                                    <span class="block text-xs text-slate-500 dark:text-slate-400">
                                                        Vendor: {{ $po->vendor_name ?? 'N/A' }}
                                                    </span>
                                                </div>
                                                <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-orange-100 dark:bg-orange-500/20 text-[#F47920]">
                                                    Pilih
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>
                                @elseif (!empty($po_search) && count($available_pos) === 0)
                                    <p class="text-xs text-slate-400 mt-2 italic px-1">
                                        PO tidak ditemukan atau belum ada penerimaan barang di Receiving.
                                    </p>
                                @endif
                            @endif

                            @error('purchase_order_issued_id')
                                <span class="text-red-500 text-xs font-semibold mt-1.5 block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Additional References Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mb-6 pt-4 border-t border-slate-200/60 dark:border-white/5">
                        <!-- No Reservasi -->
                        <div data-tour="reservasi">
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5">No. Reservasi</label>
                            <input type="text" wire:model="no_reservasi"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium text-slate-900 dark:text-white bg-slate-50/80 dark:bg-black/20 rounded-xl border border-slate-200/80 dark:border-white/10 focus:ring-2 focus:ring-[#F47920]/40 focus:border-[#F47920]"
                                placeholder="Opsional">
                        </div>

                        <!-- No JOR/WO -->
                        <div data-tour="jor">
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5">No. JOR / WO</label>
                            <input type="text" wire:model="no_jor_wo"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium text-slate-900 dark:text-white bg-slate-50/80 dark:bg-black/20 rounded-xl border border-slate-200/80 dark:border-white/10 focus:ring-2 focus:ring-[#F47920]/40 focus:border-[#F47920]"
                                placeholder="Opsional">
                        </div>

                        <!-- No Alat -->
                        <div data-tour="alat">
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5">No. Alat / Unit</label>
                            <input type="text" wire:model="no_alat"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium text-slate-900 dark:text-white bg-slate-50/80 dark:bg-black/20 rounded-xl border border-slate-200/80 dark:border-white/10 focus:ring-2 focus:ring-[#F47920]/40 focus:border-[#F47920]"
                                placeholder="Contoh: K-101">
                        </div>

                        <!-- Kode Biaya -->
                        <div data-tour="kode-biaya">
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5">Kode Biaya</label>
                            <input type="text" wire:model="kode_biaya"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium text-slate-900 dark:text-white bg-slate-50/80 dark:bg-black/20 rounded-xl border border-slate-200/80 dark:border-white/10 focus:ring-2 focus:ring-[#F47920]/40 focus:border-[#F47920]"
                                placeholder="Opsional">
                        </div>
                    </div>

                    <!-- Digunakan Untuk (Wajib) with Quick Chips -->
                    <div data-tour="kegunaan">
                        <label class="block text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            Digunakan Untuk (Peruntukan Material) <span class="text-[#F47920]">*</span>
                        </label>
                        <textarea wire:model="digunakan_untuk" rows="2"
                            class="w-full px-4 py-3 text-sm sm:text-base font-medium text-slate-900 dark:text-white bg-slate-50/80 dark:bg-black/20 rounded-2xl border border-slate-200/80 dark:border-white/10 focus:ring-2 focus:ring-[#F47920]/40 focus:border-[#F47920] transition-all"
                            placeholder="Jelaskan kebutuhan operasional pengambilan material ini secara rinci..."></textarea>
                        
                        <!-- Quick Click Chips -->
                        <div class="flex flex-wrap gap-1.5 sm:gap-2 mt-2">
                            <span class="text-xs text-slate-400 font-semibold self-center mr-1">Rekomendasi:</span>
                            @foreach (['Pemeliharaan Rutin Pabrik', 'Overhaul Mesin', 'Perbaikan Urgent / Breakdown', 'Turn Around (TA)', 'Operasional Bulanan'] as $chip)
                                <button type="button" @click="setKegunaan('{{ $chip }}')"
                                    class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-orange-100 dark:bg-white/5 dark:hover:bg-[#F47920]/20 text-slate-600 hover:text-[#F47920] dark:text-slate-300 dark:hover:text-orange-400 transition-colors">
                                    + {{ $chip }}
                                </button>
                            @endforeach
                        </div>

                        @error('digunakan_untuk')
                            <span class="text-red-500 text-xs font-semibold mt-1.5 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- STEP 3: DAFTAR MATERIAL YANG DIAMBIL -->
            <div class="relative z-10 mb-8 sm:mb-12 group/step">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 sm:mb-6">
                    <div class="flex items-center gap-3 sm:gap-4">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 text-white flex items-center justify-center font-black text-lg sm:text-xl shadow-lg shadow-emerald-500/20 shrink-0">
                            3
                        </div>
                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white tracking-tight">
                                Daftar Material yang Diambil
                            </h2>
                            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                                Pilih item material dari PO dan tentukan jumlah pengambilan sesuai stok BOH.
                            </p>
                        </div>
                    </div>

                    @if (!empty($purchase_order_issued_id) && count($details) < count($available_po_items))
                        <button type="button" wire:click="addDetail(true)" wire:loading.attr="disabled" data-tour="tambah-item"
                            class="group inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl bg-emerald-500 hover:bg-emerald-600 active:scale-95 text-white font-bold text-xs sm:text-sm transition-all duration-200 shadow-md shadow-emerald-500/25 hover:shadow-emerald-500/40 self-start sm:self-auto cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                            <svg wire:loading.remove wire:target="addDetail" class="w-4 h-4 transition-transform group-hover:rotate-90 duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            <svg wire:loading wire:target="addDetail" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="addDetail">Tambah Baris Material</span>
                            <span wire:loading wire:target="addDetail">Menambahkan...</span>
                            <span class="ml-0.5 px-2 py-0.5 rounded-lg bg-white/20 text-[11px] font-black tracking-wide">
                                {{ count($details) }}/{{ count($available_po_items) }}
                            </span>
                        </button>
                    @endif
                </div>

                @if (empty($purchase_order_issued_id))
                    <!-- Empty State Guide -->
                    <div class="p-8 sm:p-12 text-center rounded-3xl bg-white/60 dark:bg-slate-900/60 backdrop-blur-xl border border-dashed border-slate-300 dark:border-white/10">
                        <div class="w-14 h-14 rounded-2xl bg-orange-100 dark:bg-orange-500/20 text-[#F47920] flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-800 dark:text-white mb-1">
                            Belum Ada PO yang Dipilih
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                            Silakan cari dan pilih Nomor PO pada <span class="font-bold text-[#F47920]">Langkah 2</span> di atas untuk memuat daftar material yang tersedia di gudang.
                        </p>
                    </div>
                @else
                    <!-- Items Repeater Cards -->
                    <div class="space-y-4 sm:space-y-6">
                        @foreach ($details as $index => $detail)
                            <div wire:key="detail-row-{{ $index }}"
                                 class="material-item-card p-5 sm:p-7 rounded-3xl bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl border border-slate-200/70 dark:border-white/10 shadow-sm hover:shadow-md transition-all duration-500 relative overflow-hidden group">
                                
                                <!-- Card Header -->
                                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-200/60 dark:border-white/5">
                                    <div class="flex items-center gap-3">
                                        <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-slate-100 dark:bg-white/5 flex items-center justify-center font-black text-xs sm:text-sm text-slate-600 dark:text-slate-300">
                                            #{{ $index + 1 }}
                                        </span>
                                        <span class="text-sm sm:text-base font-bold text-slate-800 dark:text-white">
                                            Item Pengambilan
                                        </span>
                                    </div>
                                    @if (count($details) > 1)
                                        <button type="button" wire:click="removeDetail({{ $index }})"
                                            class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-xl transition-colors"
                                            title="Hapus baris item">
                                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    @endif
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 sm:gap-6">
                                    <!-- Pilih Item (Spans 3 cols on desktop) -->
                                    <div class="md:col-span-4 lg:col-span-3" data-tour="pilih-item">
                                        <label class="block text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                                            Pilih Item <span class="text-[#F47920]">*</span>
                                        </label>
                                        <select wire:model.live="details.{{ $index }}.delivery_order_receipt_detail_id"
                                            class="w-full px-4 py-3 sm:py-3.5 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white bg-slate-50/80 dark:bg-black/20 rounded-2xl border border-slate-200/80 dark:border-white/10 focus:ring-2 focus:ring-[#F47920]/40 focus:border-[#F47920] transition-all">
                                            <option value="">-- Pilih Item --</option>
                                            @foreach ($available_po_items as $item)
                                                @php
                                                    $isAlreadySelected = collect($details)->contains(function($d, $k) use ($item, $index) {
                                                        return $k != $index && ($d['delivery_order_receipt_detail_id'] == $item['id']);
                                                    });
                                                @endphp
                                                <option value="{{ $item['id'] }}" {{ $isAlreadySelected ? 'disabled' : '' }}>
                                                    Item {{ $item['item_no'] }}{{ $isAlreadySelected ? ' (Dipilih)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error("details.{$index}.delivery_order_receipt_detail_id")
                                            <span class="text-red-500 text-xs font-semibold mt-1.5 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Deskripsi Material (Spans 5 cols on desktop) -->
                                    <div class="md:col-span-8 lg:col-span-5" data-tour="deskripsi">
                                        <label class="block text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                                            Deskripsi
                                        </label>
                                        <div class="w-full px-4 py-3 sm:py-3.5 text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-100 bg-slate-50/80 dark:bg-black/20 rounded-2xl border border-slate-200/80 dark:border-white/10 min-h-[48px] sm:min-h-[52px] flex items-center">
                                            @if (!empty($detail['description']))
                                                <span class="line-clamp-2 leading-relaxed" title="{{ $detail['description'] }}">
                                                    {{ $detail['description'] }}
                                                </span>
                                            @else
                                                <span class="text-slate-400 dark:text-slate-500 italic font-normal">
                                                    Pilih item terlebih dahulu...
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Quantity Input & Max BOH (Spans 4 cols on desktop) -->
                                    <div class="md:col-span-12 lg:col-span-4 flex flex-col justify-between">
                                        <div>
                                            <div class="flex items-center justify-between mb-2">
                                                <label class="block text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300">
                                                    Jumlah Diminta <span class="text-[#F47920]">*</span>
                                                </label>
                                                <!-- Available BOH Badge -->
                                                <span class="text-xs font-bold px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400" data-tour="boh">
                                                    Stok BOH: <strong>{{ (float)($detail['boh'] ?? 0) }}</strong> {{ $detail['uoi'] ?? '' }}
                                                </span>
                                            </div>

                                            <div class="relative flex items-center" data-tour="qty">
                                                <input type="number" step="any" min="0.01" max="{{ $detail['boh'] ?? 99999 }}"
                                                    wire:model.live.debounce.300ms="details.{{ $index }}.diminta"
                                                    class="w-full pr-24 pl-4 py-3 sm:py-3.5 text-base sm:text-lg font-black text-slate-900 dark:text-white bg-slate-50/80 dark:bg-black/20 rounded-2xl border border-slate-200/80 dark:border-white/10 focus:ring-2 focus:ring-[#F47920]/40 focus:border-[#F47920] transition-all"
                                                    placeholder="0.00">
                                                
                                                <!-- One-Tap "Ambil Max" Quick Fill Button -->
                                                <button type="button" wire:click="fillMaxBoh({{ $index }})"
                                                    class="absolute right-2 px-3 py-1.5 text-xs font-black uppercase tracking-wider rounded-xl bg-orange-100 hover:bg-orange-200 dark:bg-orange-500/20 dark:hover:bg-orange-500/30 text-[#F47920] transition-all">
                                                    Max
                                                </button>
                                            </div>

                                            @error("details.{$index}.diminta")
                                                <span class="text-red-500 text-xs font-semibold mt-1.5 block">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        @if ((float)($detail['diminta'] ?? 0) > (float)($detail['boh'] ?? 0) && (float)($detail['boh'] ?? 0) > 0)
                                            <div class="mt-2 text-xs font-bold text-red-500 flex items-center gap-1.5">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                Melebihi sisa stok BOH gudang!
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Selected Material Quick Specs Summary (SN, Lokasi, Satuan, Status 105) -->
                                @if (!empty($detail['delivery_order_receipt_detail_id']))
                                    <div class="mt-3.5 pt-3.5 border-t border-slate-200/60 dark:border-white/5 flex flex-wrap items-center justify-between gap-2.5">
                                        <div class="flex flex-wrap items-center gap-2" data-tour="stock-no">
                                            <span class="px-2.5 py-1 rounded-xl bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 text-xs font-bold border border-blue-200/60 dark:border-blue-500/20">
                                                SN: {{ $detail['stock_no'] ?: 'Non-Stock' }}
                                            </span>
                                            <span class="px-2.5 py-1 rounded-xl bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-300 text-xs font-bold border border-purple-200/60 dark:border-purple-500/20">
                                                Lokasi: {{ $detail['location'] ?: 'Receiving' }}
                                            </span>
                                            <span class="px-2.5 py-1 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 text-xs font-bold border border-emerald-200/60 dark:border-emerald-500/20">
                                                Satuan: {{ $detail['uoi'] ?: 'EA' }}
                                            </span>
                                            @if(!empty($detail['has_non_grs']))
                                                <span class="px-2.5 py-1 rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-800 dark:text-amber-300 text-xs font-black flex items-center gap-1.5 border border-amber-300/80 dark:border-amber-500/30">
                                                    <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                                    Belum GRS
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 text-xs font-bold flex items-center gap-1.5 border border-emerald-300/80 dark:border-emerald-500/30">
                                                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                    Sudah GRS
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach

                        @if (!empty($purchase_order_issued_id) && count($details) < count($available_po_items))
                            <!-- Interactive Add Next Item Card Button at Bottom of List -->
                            <button type="button" wire:click="addDetail(true)" wire:loading.attr="disabled"
                                class="w-full py-4 px-6 rounded-3xl border-2 border-dashed border-emerald-300 dark:border-emerald-500/30 hover:border-emerald-500 dark:hover:border-emerald-400 bg-emerald-50/40 hover:bg-emerald-50 dark:bg-emerald-950/10 dark:hover:bg-emerald-950/25 text-emerald-700 dark:text-emerald-300 font-bold text-xs sm:text-sm flex items-center justify-center gap-3 transition-all duration-200 active:scale-98 group cursor-pointer shadow-xs hover:shadow-md">
                                <div class="w-7 h-7 rounded-xl bg-emerald-500 text-white flex items-center justify-center group-hover:scale-110 group-hover:rotate-90 transition-all duration-300 shadow-sm">
                                    <svg wire:loading.remove wire:target="addDetail" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    <svg wire:loading wire:target="addDetail" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                </div>
                                <span wire:loading.remove wire:target="addDetail">Tambah Baris Material Berikutnya (Item #{{ count($details) + 1 }})</span>
                                <span wire:loading wire:target="addDetail">Menambahkan baris material...</span>
                                <span class="px-2 py-0.5 rounded-lg bg-emerald-100 dark:bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 text-xs font-black">
                                    {{ count($details) }}/{{ count($available_po_items) }}
                                </span>
                            </button>
                        @endif
                    </div>
                @endif
            </div>

            <!-- STEP 4: TANDA TANGAN DIGITAL & SERAH TERIMA -->
            <div class="relative z-10 mb-8 sm:mb-12 group/step">
                <div class="flex items-center gap-3 sm:gap-4 mb-4 sm:mb-6">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-gradient-to-br from-purple-600 to-indigo-800 text-white flex items-center justify-center font-black text-lg sm:text-xl shadow-lg shadow-purple-500/20 shrink-0">
                        4
                    </div>
                    <div>
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white tracking-tight">
                            Tanda Tangan Digital
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                            Bubuhkan tanda tangan elektronik pemohon, pihak gudang Receiving{{ $requiresIstekSignature ? ', dan persetujuan ISTEK (karena material belum GRS)' : '' }}.
                        </p>
                    </div>
                </div>

                <div class="p-5 sm:p-8 rounded-3xl bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl border border-slate-200/70 dark:border-white/10 shadow-sm hover:shadow-md transition-all">
                    
                    @if ($requiresIstekSignature)
                        <!-- Alert Banner: Material Belum GRS -->
                        <div class="mb-6 p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <div class="text-xs sm:text-sm">
                                <span class="font-bold text-amber-800 dark:text-amber-300 block mb-0.5">Material Belum GRS (Wajib Persetujuan ISTEK)</span>
                                <p class="text-amber-700/90 dark:text-amber-300/80 leading-relaxed font-medium">
                                    Item yang Anda ambil berstatus <strong>Belum GRS</strong>. Sesuai SOP gudang Pupuk Kaltim, serah terima material yang belum terbit GRS <strong>wajib</strong> mendapatkan persetujuan dan tanda tangan dari pihak ISTEK.
                                </p>
                            </div>
                        </div>
                    @elseif(!empty($purchase_order_issued_id) && count($details) > 0 && !empty($details[0]['delivery_order_receipt_detail_id']))
                        <!-- Info Banner: Material Sudah GRS -->
                        <div class="mb-6 p-3.5 rounded-2xl bg-emerald-50/80 dark:bg-emerald-950/20 border border-emerald-200/80 dark:border-emerald-800/30 flex items-center gap-3 text-xs sm:text-sm text-emerald-800 dark:text-emerald-300 font-semibold">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Seluruh material terpilih <strong>Sudah GRS</strong>. Tanda tangan persetujuan ISTEK tidak diperlukan.</span>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 {{ $requiresIstekSignature ? 'md:grid-cols-3' : 'md:grid-cols-2' }} gap-6 sm:gap-8">
                        
                        <!-- Panel 1: Tanda Tangan Peminta -->
                        <div class="p-5 rounded-2xl bg-slate-50/70 dark:bg-black/20 border border-slate-200/60 dark:border-white/5 flex flex-col justify-between" data-tour="ttd-peminta">
                            <div>
                                <div class="flex items-center justify-between mb-3 pb-3 border-b border-slate-200/60 dark:border-white/5">
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pihak 1</span>
                                    <span class="px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-300 text-xs font-bold">
                                        Peminta (Karyawan)
                                    </span>
                                </div>
                                <div class="mb-3">
                                    <span class="block text-xs text-slate-400 font-medium">Nama Pemohon:</span>
                                    <span class="block text-sm sm:text-base font-bold text-slate-800 dark:text-white truncate">
                                        {{ !empty($diminta_oleh) ? $diminta_oleh : '(Belum diisi di Langkah 1)' }}
                                    </span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">
                                        NPK: {{ !empty($npk) ? $npk : '-' }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                                    Tanda Tangan Peminta <span class="text-red-500">*</span>
                                </label>
                                <x-signature-pad wire:model="diminta_signature" id="diminta_signature"
                                    placeholder="Tanda tangan peminta di sini..." />
                                @error('diminta_signature')
                                    <span class="text-red-500 text-xs font-semibold mt-1.5 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Panel 2: Tanda Tangan ISTEK (Khusus jika ada barang belum GRS) -->
                        @if ($requiresIstekSignature)
                            <div class="p-5 rounded-2xl bg-amber-50/70 dark:bg-amber-950/20 border-2 border-amber-300 dark:border-amber-500/40 flex flex-col justify-between shadow-xs" data-tour="ttd-istek">
                                <div>
                                    <div class="flex items-center justify-between mb-3 pb-3 border-b border-amber-200/80 dark:border-amber-500/20">
                                        <span class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Pihak 2</span>
                                        <span class="px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300 text-xs font-black">
                                            ISTEK
                                        </span>
                                    </div>

                                    <div class="space-y-3 mb-3">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                                Pilih Pihak ISTEK <span class="text-red-500">*</span>
                                            </label>
                                            <select wire:model.live="pilihan_istek"
                                                class="w-full px-3 py-2 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-white/10 focus:ring-2 focus:ring-amber-500/40 focus:border-amber-500">
                                                <option value="">-- Pilih Penyetuju ISTEK --</option>
                                                <option value="Pasarela">Pasarela</option>
                                                <option value="Joko">Joko</option>
                                                <option value="Lainnya">Lainnya...</option>
                                            </select>
                                        </div>

                                        @if ($pilihan_istek === 'Lainnya')
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nama ISTEK</label>
                                                <input type="text" wire:model="disetujui_oleh"
                                                    class="w-full px-3 py-2 text-xs font-semibold text-slate-900 dark:text-white bg-white dark:bg-slate-800 rounded-xl border border-slate-200"
                                                    placeholder="Nama lengkap pejabat ISTEK">
                                            </div>
                                        @endif

                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">NPK ISTEK</label>
                                            <input type="text" wire:model="disetujui_npk"
                                                class="w-full px-3 py-2 text-xs font-semibold text-slate-900 dark:text-white bg-white dark:bg-slate-800 rounded-xl border border-slate-200 disabled:opacity-60"
                                                placeholder="NPK" {{ $pilihan_istek !== 'Lainnya' ? 'disabled' : '' }}>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                                        Tanda Tangan ISTEK <span class="text-red-500">*</span>
                                    </label>
                                    <x-signature-pad wire:model="disetujui_signature" id="disetujui_signature"
                                        placeholder="Tanda tangan pihak ISTEK di sini..." />
                                    @error('disetujui_signature')
                                        <span class="text-red-500 text-xs font-semibold mt-1.5 block">{{ $message }}</span>
                                    @enderror
                                    @error('disetujui_oleh')
                                        <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span>
                                    @enderror
                                    @error('disetujui_npk')
                                        <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        @endif

                        <!-- Panel 3: Tanda Tangan Receiving -->
                        <div class="p-5 rounded-2xl bg-slate-50/70 dark:bg-black/20 border border-slate-200/60 dark:border-white/5 flex flex-col justify-between" data-tour="ttd-receiving">
                            <div>
                                <div class="flex items-center justify-between mb-3 pb-3 border-b border-slate-200/60 dark:border-white/5">
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Petugas</span>
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 text-xs font-bold">
                                        Gudang Receiving
                                    </span>
                                </div>

                                <div class="space-y-3 mb-3">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                            Pilih Petugas Gudang <span class="text-red-500">*</span>
                                        </label>
                                        <select wire:model.live="pilihan_receiving"
                                            class="w-full px-3 py-2 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-white/10">
                                            <option value="">-- Pilih Staf Gudang --</option>
                                            @foreach ($receiving_users as $user)
                                                <option value="{{ $user['id'] }}">{{ $user['name'] }}</option>
                                            @endforeach
                                            <option value="Lainnya">Lainnya...</option>
                                        </select>
                                    </div>

                                    @if ($pilihan_receiving === 'Lainnya')
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Petugas</label>
                                            <input type="text" wire:model="diserahkan_oleh"
                                                class="w-full px-3 py-2 text-xs font-semibold text-slate-900 dark:text-white bg-white dark:bg-slate-800 rounded-xl border border-slate-200"
                                                placeholder="Nama lengkap">
                                        </div>
                                    @endif

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">NPK Petugas</label>
                                        <input type="text" wire:model="diserahkan_npk"
                                            class="w-full px-3 py-2 text-xs font-semibold text-slate-900 dark:text-white bg-white dark:bg-slate-800 rounded-xl border border-slate-200 disabled:opacity-60"
                                            placeholder="NPK" {{ $pilihan_receiving !== 'Lainnya' ? 'disabled' : '' }}>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                                    Tanda Tangan Receiving <span class="text-red-500">*</span>
                                </label>
                                <x-signature-pad wire:model="diserahkan_signature" id="diserahkan_signature"
                                    placeholder="Tanda tangan pihak receiving di sini..." />
                                @error('diserahkan_signature')
                                    <span class="text-red-500 text-xs font-semibold mt-1.5 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- AGREEMENT CHECKBOX & SUBMIT AREA -->
            <div class="relative z-10 p-5 sm:p-8 rounded-3xl bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl border border-slate-200/70 dark:border-white/10 shadow-sm mb-20 md:mb-0">
                
                <!-- Agreement Checkbox -->
                <div class="mb-6">
                    <label class="flex items-start gap-3 sm:gap-4 cursor-pointer group select-none">
                        <div class="relative flex items-center justify-center shrink-0 mt-0.5">
                            <input type="checkbox" wire:model="agreement"
                                class="peer appearance-none w-5 h-5 sm:w-6 sm:h-6 border-2 border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-black/20 checked:bg-[#F47920] checked:border-[#F47920] focus:ring-2 focus:ring-[#F47920]/40 transition-all cursor-pointer shadow-xs">
                            <svg class="absolute w-3.5 h-3.5 sm:w-4 sm:h-4 text-white opacity-0 peer-checked:opacity-100 pointer-events-none transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <span class="text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors leading-relaxed">
                                Saya menyatakan bahwa data di atas diisi dengan sebenar-benarnya dan setuju untuk melakukan serah terima fisik material di Gudang Receiving Pupuk Kalimantan Timur.
                            </span>
                            @error('agreement')
                                <span class="text-red-500 text-xs font-bold mt-1.5 block">{{ $message }}</span>
                            @enderror
                        </div>
                    </label>
                </div>

                <!-- Action Buttons -->
                <div class="pt-6 border-t border-slate-200/60 dark:border-white/10 flex flex-col-reverse md:flex-row items-center justify-between gap-4">
                    <a href="{{ url('/') }}" wire:navigate
                        class="w-full md:w-auto text-center px-6 py-3.5 text-xs sm:text-sm font-bold text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5 rounded-2xl transition-all">
                        &larr; Kembali ke Beranda
                    </a>

                    <button type="submit"
                        class="hidden md:inline-flex items-center justify-center gap-2.5 px-8 sm:px-12 py-3.5 sm:py-4 text-sm sm:text-base font-black text-white bg-gradient-to-r from-[#F47920] to-[#BE5A27] hover:from-orange-500 hover:to-orange-700 rounded-2xl shadow-lg shadow-orange-500/25 hover:shadow-orange-500/40 hover:-translate-y-0.5 active:scale-98 transition-all">
                        <svg wire:loading.remove wire:target="confirmSubmit" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <svg wire:loading wire:target="confirmSubmit" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span wire:loading.remove wire:target="confirmSubmit">Kirim Pengajuan MIR</span>
                        <span wire:loading wire:target="confirmSubmit">Memvalidasi Formulir...</span>
                    </button>
                </div>
            </div>

            <!-- MOBILE STICKY SUBMIT BAR (Mobile UX Supercharged) -->
            <div x-data="{ isChatOpen: false }"
                 @chat-toggled.window="isChatOpen = $event.detail.isOpen"
                 x-show="!isChatOpen"
                 class="mir-mobile-bar fixed bottom-0 inset-x-0 z-40 p-3 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border-t border-slate-200/80 dark:border-white/10 shadow-2xl flex md:hidden items-center justify-between gap-3">
                <div class="min-w-0">
                    <span class="block text-[11px] font-bold uppercase text-slate-400">Total Material</span>
                    <span class="block text-xs font-black text-slate-800 dark:text-white truncate">
                        {{ count($details) }} Item Pengambilan
                    </span>
                </div>
                <button type="submit"
                    wire:loading.attr="disabled"
                    wire:target="confirmSubmit"
                    class="px-5 py-2.5 text-xs font-black text-white bg-gradient-to-r from-[#F47920] to-[#BE5A27] rounded-xl shadow-md shadow-orange-500/30 flex items-center gap-2 active:scale-95 disabled:opacity-60 transition-all">
                    <svg wire:loading.remove wire:target="confirmSubmit" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    <svg wire:loading wire:target="confirmSubmit" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span wire:loading.remove wire:target="confirmSubmit">Kirim MIR</span>
                    <span wire:loading wire:target="confirmSubmit">Memvalidasi...</span>
                </button>
            </div>

        </form>
    </div>

    <!-- Confirmation Modal / Dialog -->
    @if ($showConfirmModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6" x-data="{ show: false }" x-init="setTimeout(() => show = true, 50)">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm transition-opacity"
                :class="show ? 'opacity-100' : 'opacity-0'" wire:click="$set('showConfirmModal', false)"></div>

            <!-- Modal Content -->
            <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl overflow-hidden border border-slate-200 dark:border-white/10 transition-all transform"
                :class="show ? 'opacity-100 translate-y-0 scale-100' : 'opacity-0 translate-y-8 scale-95'">
                
                <div class="p-6 sm:p-8">
                    <div class="w-14 h-14 bg-orange-100 dark:bg-orange-500/20 text-[#F47920] rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>

                    <div class="text-center mb-6">
                        <h3 class="text-xl font-black text-slate-800 dark:text-white mb-2">Konfirmasi Pengajuan MIR</h3>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium">
                            Pastikan data pemohon, nomor PO, dan jumlah material yang diambil sudah sesuai dengan kebutuhan sebelum disimpan ke sistem.
                        </p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-black/20 border border-slate-200/60 dark:border-white/5 space-y-2 mb-6 text-xs sm:text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Peminta:</span>
                            <span class="font-bold text-slate-800 dark:text-white">{{ $diminta_oleh }} (NPK: {{ $npk }})</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Departemen:</span>
                            <span class="font-bold text-slate-800 dark:text-white">{{ $departemen }} / {{ $bagian }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Total Material:</span>
                            <span class="font-bold text-[#F47920]">{{ count($details) }} Item</span>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row gap-3">
                        <button type="button" wire:click="$set('showConfirmModal', false)"
                            class="flex-1 px-5 py-3 text-xs sm:text-sm font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-colors">
                            Batal
                        </button>
                        <button type="button" wire:click="submit"
                            class="flex-1 px-5 py-3 text-xs sm:text-sm font-bold text-white bg-gradient-to-r from-[#F47920] to-[#BE5A27] hover:from-orange-500 hover:to-orange-700 rounded-xl transition-all shadow-md shadow-orange-500/30 flex items-center justify-center gap-2">
                            <span wire:loading.remove wire:target="submit">Ya, Kirim Sekarang</span>
                            <svg wire:loading wire:target="submit" class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span wire:loading wire:target="submit">Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Success Modal -->
    @if ($showSuccessMessage)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4" x-data="{ show: false }" x-init="setTimeout(() => show = true, 50)">
            <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm" wire:click="$set('showSuccessMessage', false)"></div>
            <div class="relative bg-white dark:bg-slate-900 w-full max-w-sm rounded-3xl p-6 sm:p-8 text-center shadow-2xl border border-slate-200 dark:border-white/10">
                <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h3 class="text-xl font-black text-slate-800 dark:text-white mb-2">Pengajuan Berhasil!</h3>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mb-6 font-medium">
                    Dokumen Material Issue Request (MIR) telah berhasil dibuat dan disimpan ke sistem database gudang Receiving.
                </p>
                <button type="button" wire:click="$set('showSuccessMessage', false)"
                    class="w-full py-3 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-all shadow-md shadow-emerald-600/30">
                    Selesai
                </button>
            </div>
        </div>
    @endif

    <!-- Floating Guide Button (Panduan Pengisian Shepherd.js) -->
    <div x-data="{ isChatOpen: false }"
         @chat-toggled.window="isChatOpen = $event.detail.isOpen"
         x-show="!isChatOpen"
         class="fixed bottom-20 md:bottom-8 left-4 md:left-8 z-40">
        <button type="button" @click.prevent="startTour()"
            class="flex items-center gap-2 px-4 py-2.5 sm:px-5 sm:py-3 bg-gradient-to-r from-[#F47920] to-[#BE5A27] hover:from-orange-500 hover:to-[#cf6935] text-white rounded-full shadow-lg shadow-orange-500/30 hover:shadow-orange-500/40 active:scale-95 transition-all duration-300 cursor-pointer">
            <svg class="w-4 h-4 sm:w-5 sm:h-5 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="font-bold text-xs sm:text-sm tracking-wide">Panduan Pengisian</span>
        </button>
    </div>

    <!-- Shepherd CSS Tweaks -->
    <style>
        .shepherd-element {
            border-radius: 1.25rem;
            border: 1px solid rgba(244, 121, 32, 0.2);
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(12px);
            max-width: 360px;
            z-index: 9999 !important;
        }
        .dark .shepherd-element {
            background: rgba(15, 23, 42, 0.95) !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .shepherd-title { font-weight: 900; color: #1e293b; }
        .dark .shepherd-title { color: #f8fafc; }
        .shepherd-text { font-size: 0.9rem; color: #475569; line-height: 1.5; }
        .dark .shepherd-text { color: #cbd5e1; }
        .shepherd-button-primary {
            background: linear-gradient(to right, #F47920, #BE5A27);
            color: white;
            border-radius: 0.75rem;
            font-weight: 700;
        }

        @media (max-width: 767px) {
            #chatbot-widget-container {
                bottom: 5.25rem !important;
                right: 1rem !important;
            }
        }

        @media (min-width: 768px) {
            .mir-mobile-bar {
                display: none !important;
            }
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
                                { id: 'nama-peminta', title: '1. Nama Peminta', text: 'Isi dengan nama lengkap pemohon pengambilan barang.', position: 'bottom' },
                                { id: 'npk', title: '2. NPK Karyawan', text: 'Masukkan Nomor Pokok Karyawan Anda.', position: 'bottom' },
                                { id: 'no-hp', title: '3. No. HP / WA', text: 'Nomor aktif yang dapat dihubungi oleh petugas gudang.', position: 'bottom' },
                                { id: 'departemen', title: '4. Departemen', text: 'Isi dengan nama Departemen Anda.', position: 'bottom' },
                                { id: 'bagian', title: '5. Bagian / Seksi', text: 'Isi dengan unit kerja atau seksi pemohon.', position: 'bottom' },
                                { id: 'tanggal', title: '6. Tanggal', text: 'Otomatis terisi tanggal hari ini.', position: 'bottom' },
                                { id: 'search-mode', title: '7. Mode Cari', text: 'Pilih cari berdasarkan Nomor PO atau Stock Number.', position: 'bottom' },
                                { id: 'po', title: '8. Pilih PO', text: 'Ketik Nomor PO atau Stock Number, lalu klik PO yang sesuai.', position: 'bottom' },
                                { id: 'kegunaan', title: '9. Peruntukan', text: 'Pilih atau ketik tujuan penggunaan material ini.', position: 'bottom' },
                                { id: 'pilih-item', title: '10. Pilih Material', text: 'Pilih item barang yang akan diambil dari PO terpilih.', position: 'top' },
                                { id: 'boh', title: '11. Stok BOH', text: 'Periksa sisa stok gudang (BOH) yang tersedia.', position: 'top' },
                                { id: 'qty', title: '12. Jumlah Pengambilan', text: 'Ketik jumlah atau klik MAX untuk mengambil seluruh stok.', position: 'top' },
                                { id: 'ttd-peminta', title: '13. Tanda Tangan Peminta', text: 'Bubuhkan tanda tangan pemohon di canvas ini.', position: 'top' },
                                { id: 'ttd-istek', title: '14. Tanda Tangan Pihak ISTEK', text: 'Tanda tangan persetujuan pihak ISTEK yang berwenang jika material belum GRS.', position: 'top' },
                                { id: 'ttd-receiving', title: '15. Tanda Tangan Receiving', text: 'Tanda tangan serah terima oleh pihak gudang Receiving.', position: 'top' }
                            ];

                            steps.forEach((s, index) => {
                                const buttons = [];
                                if (index > 0) {
                                    buttons.push({
                                        text: '&larr; Kembali',
                                        action: this.tourObj.back,
                                        classes: 'px-3 py-1.5 text-xs font-bold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300'
                                    });
                                }
                                if (index < steps.length - 1) {
                                    buttons.push({
                                        text: 'Lanjut &rarr;',
                                        action: this.tourObj.next,
                                        classes: 'px-3 py-1.5 text-xs font-bold rounded-lg shepherd-button-primary'
                                    });
                                } else {
                                    buttons.push({
                                        text: 'Selesai',
                                        action: this.tourObj.complete,
                                        classes: 'px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 text-white'
                                    });
                                }

                                this.tourObj.addStep({
                                    id: s.id,
                                    title: s.title,
                                    text: s.text,
                                    attachTo: { element: `[data-tour="${s.id}"]`, on: s.position },
                                    buttons: buttons,
                                    showOn: () => document.querySelector(`[data-tour="${s.id}"]`) !== null
                                });
                            });
                        }
                    }, 100);
                },
                startTour() {
                    if (this.tourObj) {
                        this.tourObj.start();
                    } else {
                        alert("Panduan sedang dimuat, mohon tunggu sebentar.");
                    }
                }
            }));
        });
    </script>
</div>
