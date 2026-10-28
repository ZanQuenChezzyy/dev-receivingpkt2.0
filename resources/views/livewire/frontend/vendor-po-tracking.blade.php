<div class="min-h-screen text-slate-800 dark:text-slate-200 transition-colors duration-500 pt-28 pb-24 font-sans relative overflow-hidden">

    <!-- Sophisticated Ambient Background Glows -->
    <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden select-none">
        <div class="absolute top-0 right-1/4 w-[500px] h-[500px] bg-[#0A4F86]/10 dark:bg-[#0A4F86]/20 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-1/3 left-1/4 w-[450px] h-[450px] bg-[#F47920]/10 dark:bg-[#F47920]/15 rounded-full blur-[140px]"></div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">

        <!-- Header Title Section -->
        <div class="text-center max-w-3xl mx-auto mb-10">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#F47920]/10 border border-[#F47920]/25 text-[#F47920] text-xs font-black tracking-widest uppercase mb-4 shadow-sm backdrop-blur-md">
                <svg class="w-4 h-4 text-[#F47920]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Portal Tracking Vendor PKT
            </div>
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-800 dark:text-white tracking-tight mb-4">
                Lacak Pergerakan <br class="hidden sm:inline">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#F47920] via-orange-400 to-[#BE5A27]">
                    Dokumen & Fisik Material PO
                </span>
            </h1>
            <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 font-medium max-w-2xl mx-auto">
                Pantau status penerimaan surat jalan, Pengajuan QC, hingga GRS secara real-time.
            </p>
        </div>

        <!-- 1. PO SEARCH BOX -->
        <div class="max-w-2xl sm:max-w-3xl mx-auto mb-14">
            <!-- Outer Elevated Card -->
            <div class="p-4 sm:p-6 rounded-3xl bg-white/95 dark:bg-slate-800/95 backdrop-blur-2xl border-2 border-slate-200 dark:border-slate-700 shadow-2xl relative">
                
                <!-- Ambient Subtle Glow -->
                <div class="absolute -top-12 -right-12 w-48 h-48 bg-[#F47920]/15 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-12 -left-12 w-48 h-48 bg-[#0A4F86]/15 rounded-full blur-3xl pointer-events-none"></div>

                <form wire:submit="initiateSearch" class="relative z-10 space-y-3">

                    <!-- Input & Button Row -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <!-- Distinct High-Contrast Input Container -->
                        <div class="relative flex-grow flex items-center rounded-2xl bg-slate-50 dark:bg-slate-900 border-2 border-slate-300 dark:border-slate-600 focus-within:border-[#F47920] dark:focus-within:border-[#F47920] focus-within:ring-4 focus-within:ring-[#F47920]/20 transition-all duration-200 shadow-inner">
                            <!-- Left Search Icon -->
                            <div class="pl-4 pr-2 text-[#F47920] flex items-center pointer-events-none shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div> 

                            <!-- Real Input Element -->
                            <input id="poNumberInput"
                                wire:model="poNumber"
                                type="text"
                                placeholder="Ketik nomor PO"
                                class="w-full bg-transparent border-0 ring-0 focus:ring-0 focus:outline-none px-3 py-3.5 sm:py-4 text-base sm:text-lg font-bold font-mono tracking-wider text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 placeholder:font-sans placeholder:font-normal placeholder:tracking-normal placeholder:text-sm sm:placeholder:text-base"
                                autocomplete="off"
                                maxlength="15"
                                autofocus>

                            <!-- Clear Button (when poNumber is filled) -->
                            @if (!empty($poNumber))
                                <button type="button" wire:click="$set('poNumber', '')"
                                    class="p-2 mr-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors shrink-0 cursor-pointer"
                                    title="Hapus input">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            @endif

                            <!-- Keyboard Hint Badge -->
                            <div class="hidden md:flex items-center pr-3 select-none pointer-events-none shrink-0">
                                <kbd class="px-2 py-1 text-[10px] font-bold font-mono text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-sm">
                                    ↵ Enter
                                </kbd>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" wire:loading.attr="disabled"
                            class="px-8 py-3.5 sm:py-4 rounded-2xl font-black text-sm text-white bg-gradient-to-r from-[#F47920] via-orange-500 to-[#E06714] shadow-[0_4px_16px_rgba(244,121,32,0.35)] hover:shadow-[0_8px_24px_rgba(244,121,32,0.5)] hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2.5 shrink-0 cursor-pointer">
                            <span wire:loading.remove wire:target="initiateSearch, selectSamplePo" class="flex items-center gap-2">
                                <span>Lacak Dokumen</span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </span>
                            <span wire:loading wire:target="initiateSearch, selectSamplePo" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>Mengecek PO...</span>
                            </span>
                        </button>
                    </div>
                </form>

                <!-- Search Error Alert -->
                @if ($searchError)
                    <div class="mt-4 p-4 rounded-2xl bg-rose-500/10 dark:bg-rose-500/20 border-2 border-rose-500/40 text-rose-700 dark:text-rose-300 text-xs sm:text-sm font-semibold flex items-center justify-between gap-3 shadow-sm animate-fade-in relative z-10">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <span>{{ $searchError }}</span>
                        </div>
                        <button type="button" wire:click="$set('searchError', null)" class="text-rose-500 hover:text-rose-700 p-1 rounded-lg">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @endif
            </div>

            <!-- Trust Indicators (Security & Realtime) -->
            <div class="mt-4 flex flex-wrap items-center justify-center gap-6 text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span>Verifikasi Email Vendor</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>Enkripsi Akses Berkas</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-[#F47920]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Sinkronisasi Realtime SAP 103/105</span>
                </div>
            </div>
        </div>

        <!-- 2. POP-UP MODAL: VERIFIKASI IDENTITAS VENDOR (EMAIL) -->
        <div x-data="{ open: @entangle('showVerificationModal') }"
            x-show="open"
            style="display: none;"
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 sm:p-6"
            @keydown.escape.window="$wire.closeModal()">
            
            <!-- Backdrop Overlay with Glass Blur -->
            <div x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/60 dark:bg-black/80 backdrop-blur-md transition-opacity"
                @click="$wire.closeModal()"></div>

            <!-- Modal Dialog Content -->
            <div x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative w-full max-w-lg bg-white dark:bg-[#031525] rounded-3xl p-6 sm:p-8 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.35)] border border-slate-200 dark:border-white/10 z-10 overflow-hidden">
                
                <!-- Ambient Accent Circle -->
                <div class="absolute -top-16 -right-16 w-44 h-44 bg-[#F47920]/15 dark:bg-[#F47920]/20 rounded-full blur-2xl pointer-events-none"></div>

                <!-- Modal Header -->
                <div class="flex items-start justify-between gap-4 mb-6 relative z-10">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#F47920] to-[#BE5A27] text-white flex items-center justify-center shadow-md shadow-orange-500/25 shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-[11px] font-black uppercase tracking-wider text-[#F47920] block">Verifikasi Keamanan</span>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                                Akses Riwayat Dokumen
                            </h3>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5 font-medium">
                                Masukkan nomor PO & email terdaftar untuk melihat riwayat lengkap.
                            </p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeModal"
                        class="p-2 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors cursor-pointer"
                        title="Tutup">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form Verifikasi PO & Email Vendor -->
                <form wire:submit="verifyVendorEmail" class="space-y-4 relative z-10">

                    <!-- 1. Input Nomor Purchase Order (PO) -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="modalPoNumberInput" class="block text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#F47920] animate-pulse"></span>
                                Nomor Purchase Order (PO)
                            </label>
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">10 Digit SAP PO</span>
                        </div>
                        <div class="relative flex items-center rounded-2xl bg-slate-50 dark:bg-slate-900 border-2 border-slate-300 dark:border-slate-700 focus-within:border-[#F47920] dark:focus-within:border-[#F47920] focus-within:ring-4 focus-within:ring-[#F47920]/20 transition-all shadow-sm">
                            <div class="pl-3.5 pr-2 text-[#F47920] flex items-center pointer-events-none shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-mono select-none border border-slate-300 dark:border-slate-700 shrink-0">
                                PO
                            </span>
                            <input id="modalPoNumberInput"
                                wire:model.live.debounce.400ms="poNumber"
                                type="text"
                                placeholder="Ketik 10 digit nomor PO (contoh: 5300065628)..."
                                class="w-full bg-transparent border-0 ring-0 focus:ring-0 focus:outline-none px-3 py-3 text-sm sm:text-base font-mono font-black text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 placeholder:font-sans placeholder:font-normal placeholder:text-xs sm:placeholder:text-sm"
                                autocomplete="off"
                                maxlength="15">
                            @if (!empty($poNumber))
                                <button type="button" wire:click="$set('poNumber', '')"
                                    class="p-1.5 mr-2 rounded-lg text-slate-500 hover:text-slate-800 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors shrink-0 cursor-pointer"
                                    title="Hapus input PO">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            @endif
                        </div>

                        <!-- Quick Sample PO Chips inside modal -->
                        <div class="mt-2.5 flex flex-wrap items-center gap-1.5 text-[11px]">
                            <span class="text-slate-500 dark:text-slate-400 font-bold">Pilih Contoh:</span>
                            <button type="button" wire:click="selectSamplePo('5300065628')"
                                class="px-2.5 py-0.5 rounded-md bg-slate-100 hover:bg-orange-50 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-mono font-bold hover:border-[#F47920] hover:text-[#F47920] transition-colors cursor-pointer">
                                5300065628
                            </button>
                            <button type="button" wire:click="selectSamplePo('5300065629')"
                                class="px-2.5 py-0.5 rounded-md bg-slate-100 hover:bg-orange-50 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-mono font-bold hover:border-[#F47920] hover:text-[#F47920] transition-colors cursor-pointer">
                                5300065629
                            </button>
                            <button type="button" wire:click="selectSamplePo('5300065757')"
                                class="px-2.5 py-0.5 rounded-md bg-slate-100 hover:bg-orange-50 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-mono font-bold hover:border-[#F47920] hover:text-[#F47920] transition-colors cursor-pointer">
                                5300065757
                            </button>
                        </div>
                    </div>

                    <!-- Vendor Context Card (if PO resolved) -->
                    @if (!empty($vendorInfo['name']))
                        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border-2 border-emerald-300 dark:border-emerald-800/70 text-emerald-950 dark:text-emerald-200 text-xs flex items-center justify-between gap-3 animate-fade-in shadow-sm">
                            <div class="flex items-center gap-2.5 truncate">
                                <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="truncate">
                                    <span class="text-[10px] uppercase font-black text-emerald-700 dark:text-emerald-400 block tracking-wider">Vendor Teridentifikasi:</span>
                                    <span class="font-black text-xs sm:text-sm text-slate-900 dark:text-white truncate block">{{ $vendorInfo['name'] }}</span>
                                </div>
                            </div>
                            @if (!empty($vendorInfo['vendor_number']))
                                <span class="px-2.5 py-1 rounded-lg bg-emerald-100 dark:bg-emerald-900/60 font-mono text-[11px] font-black text-emerald-900 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 shrink-0">
                                    {{ $vendorInfo['vendor_number'] }}
                                </span>
                            @endif
                        </div>
                    @endif

                    <!-- Clue / Masked Email Hint -->
                    @if (!empty($vendorInfo['masked_emails']))
                        <div class="p-4 rounded-2xl bg-blue-50 dark:bg-blue-950/40 border-2 border-blue-200 dark:border-blue-800/70 text-xs flex items-start gap-3 shadow-sm">
                            <div class="w-7 h-7 rounded-lg bg-blue-500/15 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="space-y-1">
                                <span class="font-black block text-blue-950 dark:text-blue-100 text-xs">Petunjuk Email Terdaftar untuk PO Ini:</span>
                                <div class="space-y-1 font-mono text-xs text-blue-900 dark:text-blue-300 font-black">
                                    @foreach ($vendorInfo['masked_emails'] as $masked)
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-blue-500">•</span>
                                            <span>{{ $masked }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @elseif (isset($vendorInfo['error_message']) && $vendorInfo['error_message'])
                        <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border-2 border-amber-300 dark:border-amber-800/70 text-xs flex items-start gap-3 shadow-sm">
                            <div class="w-7 h-7 rounded-lg bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <span class="font-black block mb-1 text-amber-950 dark:text-amber-100 text-xs">Informasi Kontak Master:</span>
                                <p class="leading-relaxed text-amber-900 dark:text-amber-200 font-semibold">{{ $vendorInfo['error_message'] }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- 2. Input Alamat Email Vendor -->
                    <div>
                        <label for="verificationEmailInput" class="block text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200 mb-1.5 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            Alamat Email Resmi Vendor
                        </label>
                        <div class="relative flex items-center rounded-2xl bg-slate-50 dark:bg-slate-900 border-2 border-slate-300 dark:border-slate-700 focus-within:border-blue-500 dark:focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-500/20 transition-all shadow-sm">
                            <div class="pl-3.5 pr-2 text-blue-500 flex items-center pointer-events-none shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                                </svg>
                            </div>
                            <input id="verificationEmailInput" wire:model="verificationEmail" type="email"
                                placeholder="Masukkan email resmi perusahaan vendor..."
                                class="w-full bg-transparent border-0 ring-0 focus:ring-0 focus:outline-none px-3 py-3 text-sm font-semibold text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 placeholder:text-xs sm:placeholder:text-sm"
                                autocomplete="email">
                        </div>
                    </div>

                    <!-- Verification Error Alert inside Modal -->
                    @if ($verificationError)
                        <div class="p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border-2 border-rose-300 dark:border-rose-800 text-rose-950 dark:text-rose-200 text-xs font-bold flex items-center gap-2.5 shadow-sm">
                            <svg class="w-4 h-4 shrink-0 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $verificationError }}</span>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-3 pt-3">
                        <button type="button" wire:click="closeModal"
                            class="px-5 py-2.5 rounded-xl font-bold text-xs text-slate-700 dark:text-slate-300 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 transition-colors cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="px-6 py-2.5 rounded-xl font-black text-xs text-white bg-gradient-to-r from-[#F47920] to-[#BE5A27] hover:shadow-[0_8px_20px_rgba(244,121,32,0.4)] active:scale-95 transition-all flex items-center gap-2 cursor-pointer shadow-md">
                            <span wire:loading.remove wire:target="verifyVendorEmail">Verifikasi & Tampilkan Riwayat</span>
                            <span wire:loading wire:target="verifyVendorEmail" class="flex items-center gap-1.5">
                                <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Memverifikasi...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. TRACKING RESULTS (VISIBLE AFTER EMAIL VERIFICATION) -->
        @if ($isVerified && !empty($trackingData))
            <div class="space-y-10 animate-fade-in">

                <!-- 3.1 PO VERIFIED BANNER & OVERVIEW -->
                <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-white/80 dark:border-white/10 shadow-2xl relative overflow-hidden">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 border-b border-slate-200/70 dark:border-white/10">
                        <div>
                            <div class="flex flex-wrap items-center gap-2.5 mb-2">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Identitas Vendor Terverifikasi
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200/60 dark:bg-white/10 text-slate-700 dark:text-slate-300">
                                    Status PO: {{ $trackingData['po']['po_status'] ?? 'Active' }}
                                </span>
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white tracking-tight">
                                Purchase Order #{{ $trackingData['po']['number'] ?? '-' }}
                            </h2>
                            <p class="text-sm font-semibold text-[#F47920] mt-1">
                                {{ $trackingData['po']['vendor_name'] ?? '-' }}
                                @if (!empty($trackingData['po']['vendor_id']))
                                    <span class="text-xs text-slate-400 dark:text-slate-500 font-normal">({{ $trackingData['po']['vendor_id'] }})</span>
                                @endif
                            </p>
                        </div>

                        <!-- Reset / Cari PO Lain -->
                        <div class="flex items-center gap-3">
                            <button type="button" wire:click="resetSearch"
                                class="glass-btn px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-200 hover:text-[#F47920] dark:hover:text-white flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Lacak PO Lain
                            </button>
                        </div>
                    </div>

                    <!-- PO Key Attributes Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 pt-6 text-xs sm:text-sm">
                        <div class="p-3.5 rounded-2xl bg-white/40 dark:bg-white/5 border border-white/60 dark:border-white/5">
                            <span class="text-slate-400 dark:text-slate-500 text-[11px] font-bold block mb-1">Tanggal Terbit PO</span>
                            <span class="font-bold text-slate-800 dark:text-white">{{ $trackingData['po']['date_create'] ?? '-' }}</span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-white/40 dark:bg-white/5 border border-white/60 dark:border-white/5">
                            <span class="text-slate-400 dark:text-slate-500 text-[11px] font-bold block mb-1">Target Delivery PO</span>
                            <span class="font-bold text-slate-800 dark:text-white">{{ $trackingData['po']['delivery_date_po'] ?? '-' }}</span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-white/40 dark:bg-white/5 border border-white/60 dark:border-white/5">
                            <span class="text-slate-400 dark:text-slate-500 text-[11px] font-bold block mb-1">Unit Pemohon</span>
                            <span class="font-bold text-slate-800 dark:text-white truncate block" title="{{ $trackingData['po']['requisitioner'] ?? '-' }}">
                                {{ $trackingData['po']['requisitioner'] ?? '-' }}
                            </span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-white/40 dark:bg-white/5 border border-white/60 dark:border-white/5">
                            <span class="text-slate-400 dark:text-slate-500 text-[11px] font-bold block mb-1">Total Item Material</span>
                            <span class="font-bold text-slate-800 dark:text-white">{{ $trackingData['po']['total_items'] ?? 0 }} Baris Item</span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-white/40 dark:bg-white/5 border border-white/60 dark:border-white/5 col-span-2 sm:col-span-1">
                            <span class="text-slate-400 dark:text-slate-500 text-[11px] font-bold block mb-1">Surat Jalan (DO) Masuk</span>
                            <span class="font-bold text-[#F47920]">{{ isset($trackingData['unique_dos']) ? $trackingData['unique_dos']->count() : 0 }} Surat Jalan</span>
                        </div>
                    </div>
                </div>

                <!-- 3.2 VISUAL PROGRESS PIPELINE STEPPER -->
                <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-white/80 dark:border-white/10 shadow-xl">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-[#F47920] block">Alur Proses Logistik</span>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight">
                                Posisi Perjalanan Dokumen Saat Ini
                            </h3>
                        </div>
                        <span class="px-3.5 py-1.5 rounded-full text-xs font-black bg-[#F47920]/15 text-[#F47920] border border-[#F47920]/30">
                            {{ $trackingData['pipeline']['current_stage_label'] ?? 'Menunggu' }}
                        </span>
                    </div>

                    <!-- Responsive Stepper -->
                    @php
                        $stageIdx = $trackingData['pipeline']['current_stage_index'] ?? 1;
                        $steps = [
                            ['num' => 1, 'label' => 'PO Terbit', 'sub' => 'Diterbitkan PKT'],
                            ['num' => 2, 'label' => 'DO & Fisik Tiba', 'sub' => 'Pintu Receiving'],
                            ['num' => 3, 'label' => 'Post 103 SAP', 'sub' => 'Validasi Sistem'],
                            ['num' => 4, 'label' => 'Inspeksi QC', 'sub' => 'Uji Mutu ISTEK'],
                            ['num' => 5, 'label' => 'Slip GRS (105)', 'sub' => 'Arsip Penagihan'],
                            ['num' => 6, 'label' => 'Serah Terima', 'sub' => 'MIR User / Selesai'],
                        ];
                    @endphp

                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                        @foreach ($steps as $step)
                            @php
                                $isDone = $stageIdx > $step['num'];
                                $isCurrent = $stageIdx === $step['num'];
                                $isPending = $stageIdx < $step['num'];
                            @endphp
                            <div class="p-3.5 rounded-2xl transition-all duration-300 relative overflow-hidden
                                @if ($isCurrent)
                                    bg-gradient-to-br from-[#F47920]/15 to-transparent border-2 border-[#F47920] shadow-[0_0_15px_rgba(244,121,32,0.2)]
                                @elseif ($isDone)
                                    bg-emerald-500/10 border border-emerald-500/25
                                @else
                                    bg-white/30 dark:bg-white/5 border border-slate-200/50 dark:border-white/5 opacity-60
                                @endif">
                                
                                <div class="flex items-center justify-between mb-2">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-black
                                        @if ($isDone)
                                            bg-emerald-500 text-white
                                        @elseif ($isCurrent)
                                            bg-[#F47920] text-white shadow-md shadow-orange-500/40
                                        @else
                                            bg-slate-200 dark:bg-white/10 text-slate-500 dark:text-slate-400
                                        @endif">
                                        @if ($isDone)
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        @else
                                            {{ $step['num'] }}
                                        @endif
                                    </div>
                                    @if ($isCurrent)
                                        <span class="flex h-2 w-2 relative">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#F47920] opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-[#F47920]"></span>
                                        </span>
                                    @endif
                                </div>

                                <div class="font-bold text-xs sm:text-sm text-slate-800 dark:text-white leading-snug">
                                    {{ $step['label'] }}
                                </div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium mt-0.5">
                                    {{ $step['sub'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- ALERTS FOR UPLOAD / DOWNLOAD STATUS -->
                @if (session()->has('upload_success'))
                    <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/25 text-emerald-700 dark:text-emerald-300 text-sm font-semibold flex items-center justify-between gap-3 shadow-sm animate-fade-in">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>{{ session('upload_success') }}</span>
                        </div>
                        <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 p-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @endif

                @if (session()->has('upload_error') || session()->has('download_error'))
                    <div class="p-4 rounded-2xl bg-red-500/10 border border-red-500/25 text-red-600 dark:text-red-400 text-sm font-semibold flex items-center justify-between gap-3 shadow-sm animate-fade-in">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>{{ session('upload_error') ?? session('download_error') }}</span>
                        </div>
                        <button type="button" @click="$el.parentElement.remove()" class="text-red-500 hover:text-red-700 p-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @endif

                <!-- 3.3 DOKUMEN RESMI YANG DAPAT DIUNDUH (BERKAS GRS RECEIVING & SURAT VERIFIKASI GRS INVOICE) -->
                <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-white/80 dark:border-white/10 shadow-xl relative overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-200/70 dark:border-white/10">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-[#F47920] block">Unduh Berkas Resmi</span>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight">
                                Dokumen Resmi dari Receiving & Tim Invoice
                            </h3>
                        </div>
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Dokumen Resmi Terverifikasi Sistem
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- CARD 1: BERKAS GRS (RECEIVING) -->
                        <div class="p-5 sm:p-6 rounded-2xl bg-gradient-to-br from-emerald-500/5 via-white/40 dark:via-white/5 to-transparent border border-emerald-500/20 relative flex flex-col justify-between">
                            <div>
                                <div class="flex items-start justify-between gap-3 mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-11 h-11 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-500/20">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400 block">Modul Receiving (Gudang)</span>
                                            <h4 class="text-base font-black text-slate-800 dark:text-white">
                                                Berkas GRS (Good Receive Slip)
                                            </h4>
                                        </div>
                                    </div>
                                </div>

                                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
                                    Slip bukti penerimaan material di gudang Receiving yang telah diposting ke sistem SAP (MIGO 105). Diunggah oleh petugas Receiving untuk keperluan arsip & penagihan vendor.
                                </p>

                                <!-- List of GRS Items -->
                                @php
                                    $grsItems = $trackingData['grs_items'] ?? collect();
                                    $grsWithFiles = $grsItems->filter(fn ($item) => !empty($item->file_path));
                                @endphp

                                @if ($grsItems->isEmpty())
                                    <div class="p-3.5 rounded-xl bg-slate-100/70 dark:bg-white/5 text-slate-500 dark:text-slate-400 text-xs flex items-center gap-2">
                                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Belum ada dokumen GRS yang diterbitkan untuk PO ini.</span>
                                    </div>
                                @else
                                    <div class="space-y-2.5 mb-4">
                                        @foreach ($grsItems as $gItem)
                                            <div class="p-3 rounded-xl bg-white/70 dark:bg-slate-900/60 border border-slate-200/60 dark:border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                                                <div class="text-xs">
                                                    <div class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                                        <span>{{ $gItem->document_code ?? 'GRS Document' }}</span>
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                                                            {{ $gItem->grsRdtv?->category ?? 'GRS' }}
                                                        </span>
                                                    </div>
                                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                                        DO: {{ $gItem->deliveryOrderReceipt?->delivery_order_no ?? '-' }} • Tgl: {{ $gItem->grsRdtv?->transaction_date ? \Carbon\Carbon::parse($gItem->grsRdtv->transaction_date)->format('d M Y') : '-' }}
                                                    </div>
                                                </div>

                                                @if (!empty($gItem->file_path))
                                                    <div class="flex items-center gap-2 shrink-0">
                                                        <a href="{{ Storage::disk('public')->url($gItem->file_path) }}" target="_blank"
                                                            class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-white/10 transition-colors flex items-center gap-1.5">
                                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                            Lihat
                                                        </a>
                                                        <button type="button" wire:click="downloadGrsReceiving({{ $gItem->id }})"
                                                            class="px-3.5 py-1.5 rounded-lg text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition-all flex items-center gap-1.5">
                                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                            Unduh
                                                        </button>
                                                    </div>
                                                @else
                                                    <span class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                                        Scan Belum Diunggah
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="pt-3 border-t border-slate-200/50 dark:border-white/5 flex items-center justify-between text-[11px] text-slate-400">
                                <span>Sumber: Master Receiving Gudang</span>
                                <span>{{ $grsWithFiles->count() }} Berkas Siap Diunduh</span>
                            </div>
                        </div>

                        <!-- CARD 2: SURAT VERIFIKASI GRS (INVOICE - ACC VP) -->
                        <div class="p-5 sm:p-6 rounded-2xl bg-gradient-to-br from-indigo-500/5 via-white/40 dark:via-white/5 to-transparent border border-indigo-500/20 relative flex flex-col justify-between">
                            <div>
                                <div class="flex items-start justify-between gap-3 mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-11 h-11 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-500/20">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400 block">Modul Invoicing (Keuangan)</span>
                                            <h4 class="text-base font-black text-slate-800 dark:text-white">
                                                Surat Verifikasi GRS (ACC VP)
                                            </h4>
                                        </div>
                                    </div>
                                    @php
                                        $invReceipt = $trackingData['invoicing_receipt'] ?? null;
                                    @endphp
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold
                                        @if ($invReceipt?->status === 'Terverifikasi' || $invReceipt?->status === 'Terkirim Kasir')
                                            bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20
                                        @else
                                            bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20
                                        @endif">
                                        {{ $invReceipt?->status ?? 'Menunggu Verifikasi' }}
                                    </span>
                                </div>

                                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
                                    Surat lembar verifikasi resmi penerbitan GRS yang telah ditandatangani dan disetujui (ACC) oleh Vice President Keuangan & Akuntansi PT Pupuk Kalimantan Timur.
                                </p>

                                @if (!empty($invReceipt?->dokumen_verifikasi_grs))
                                    <div class="p-3.5 rounded-xl bg-white/70 dark:bg-slate-900/60 border border-slate-200/60 dark:border-white/10 mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                        <div class="text-xs">
                                            <div class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                                <span>Surat Verifikasi GRS Resmi</span>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600">ACC VP</span>
                                            </div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">
                                                PO: {{ $invReceipt->no_po }} • Status: {{ $invReceipt->status }}
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 shrink-0">
                                            <a href="{{ Storage::disk('public')->url($invReceipt->dokumen_verifikasi_grs) }}" target="_blank"
                                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-white/10 transition-colors flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                Lihat
                                            </a>
                                            <button type="button" wire:click="downloadVerifikasiGrs({{ $invReceipt->id }})"
                                                class="px-3.5 py-1.5 rounded-lg text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-all flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                Unduh
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="p-3.5 rounded-xl bg-slate-100/70 dark:bg-white/5 text-slate-500 dark:text-slate-400 text-xs mb-4 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <span>Surat verifikasi belum diunggah oleh Tim Invoice. Berkas akan tersedia setelah proses verifikasi berkas tagihan disetujui VP.</span>
                                    </div>
                                @endif
                            </div>

                            <div class="pt-3 border-t border-slate-200/50 dark:border-white/5 flex items-center justify-between text-[11px] text-slate-400">
                                <span>Persetujuan: VP Finance & Accounting PKT</span>
                                <span>{{ !empty($invReceipt?->dokumen_verifikasi_grs) ? 'Berkas Tersedia' : 'Dalam Proses' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3.4 KELENGKAPAN DOKUMEN PENAGIHAN INVOICE (SINGLE FILE UPLOAD DENGAN 16 PERSYARATAN) -->
                <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-white/80 dark:border-white/10 shadow-xl relative overflow-hidden space-y-8">
                    
                    <!-- Header -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 border-b border-slate-200/70 dark:border-white/10">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#F47920]/10 border border-[#F47920]/25 text-[#F47920] text-xs font-black tracking-widest uppercase mb-2">
                                Berkas Penagihan Invoice
                            </div>
                            <h3 class="text-xl sm:text-2xl font-black text-slate-800 dark:text-white tracking-tight">
                                Upload Dokumen Persyaratan Penagihan
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 mt-1 max-w-2xl leading-relaxed">
                                Seluruh dokumen persyaratan penagihan invoice wajib digabungkan menjadi <strong>1 file dokumen (PDF/ZIP)</strong> lalu diunggah di bawah ini. Dokumen akan langsung tersinkronisasi dan dapat diverifikasi oleh Tim Invoice PT Pupuk Kalimantan Timur.
                            </p>
                        </div>

                        <!-- Status Badge -->
                        <div class="shrink-0">
                            @if (!empty($trackingData['has_dossier']))
                                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-xs font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    Berkas Lengkap Terunggah
                                </span>
                            @else
                                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-xs font-bold bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    Menunggu Unggah Berkas
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- AREA UPLOAD 1 BERKAS PENAGIHAN -->
                    <div>
                        @if (!empty($trackingData['has_dossier']) && !empty($trackingData['dossier_file']))
                            <!-- ALREADY UPLOADED CARD -->
                            @php
                                $dossier = $trackingData['dossier_file'];
                            @endphp
                            <div class="p-6 rounded-2xl bg-gradient-to-br from-emerald-500/10 via-white/50 dark:via-white/5 to-transparent border-2 border-emerald-500/30 shadow-md">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5">
                                    <div class="flex items-start gap-4">
                                        <div class="w-12 h-12 rounded-2xl bg-emerald-500 text-white flex items-center justify-center shadow-lg shadow-emerald-500/30 shrink-0">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Berkas Penagihan Resmi</span>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">Aktif</span>
                                            </div>
                                            <h4 class="text-base font-black text-slate-800 dark:text-white font-mono break-all">
                                                {{ $dossier['original_name'] ?? 'Berkas_Penagihan_Invoice.pdf' }}
                                            </h4>
                                            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap items-center gap-3">
                                                <span>Diunggah: {{ isset($dossier['uploaded_at']) ? \Carbon\Carbon::parse($dossier['uploaded_at'])->format('d M Y, H:i') : '-' }}</span>
                                                @if (!empty($dossier['file_size']))
                                                    <span>•</span>
                                                    <span>Ukuran: {{ number_format($dossier['file_size'] / (1024 * 1024), 2) }} MB</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex flex-wrap items-center gap-2 shrink-0 pt-2 sm:pt-0">
                                        <a href="{{ Storage::disk('public')->url($dossier['file_path']) }}" target="_blank"
                                            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-white/80 dark:bg-white/10 hover:bg-slate-100 dark:hover:bg-white/20 transition-all flex items-center gap-1.5 shadow-sm">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Lihat Berkas
                                        </a>
                                        <button type="button" wire:click="downloadBerkasPenagihan"
                                            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition-all flex items-center gap-1.5">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            Unduh Berkas
                                        </button>
                                        <button type="button" wire:click="deleteBerkasPenagihan"
                                            wire:confirm="Yakin ingin menghapus berkas penagihan ini? Anda dapat mengunggah file baru setelahnya."
                                            class="px-3.5 py-2 rounded-xl text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-500/10 transition-colors flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Hapus / Ganti
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- UPLOAD DROPZONE -->
                            <div class="p-8 sm:p-10 rounded-3xl bg-slate-100/50 dark:bg-white/5 border-2 border-dashed border-[#F47920]/40 hover:border-[#F47920] transition-colors text-center relative">
                                <div class="max-w-md mx-auto">
                                    <div class="w-16 h-16 rounded-3xl bg-[#F47920]/10 text-[#F47920] flex items-center justify-center mx-auto mb-4 border border-[#F47920]/20 shadow-sm">
                                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                    </div>

                                    @if ($berkasPenagihanFile)
                                        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 mb-4 shadow-sm">
                                            <div class="flex items-center justify-between gap-3 text-left">
                                                <div class="min-w-0">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#F47920] block">Berkas Terpilih</span>
                                                    <span class="font-bold text-sm text-slate-800 dark:text-white truncate block font-mono">
                                                        {{ $berkasPenagihanFile->getClientOriginalName() }}
                                                    </span>
                                                    <span class="text-[11px] text-slate-400">
                                                        {{ number_format($berkasPenagihanFile->getSize() / (1024 * 1024), 2) }} MB
                                                    </span>
                                                </div>
                                                <button type="button" wire:click="$set('berkasPenagihanFile', null)"
                                                    class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-center gap-3">
                                            <button type="button" wire:click="uploadBerkasPenagihan"
                                                wire:loading.attr="disabled"
                                                class="px-6 py-3 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-[#F47920] to-[#BE5A27] hover:shadow-[0_8px_20px_rgba(244,121,32,0.4)] active:scale-95 transition-all flex items-center gap-2">
                                                <span wire:loading.remove wire:target="uploadBerkasPenagihan">Unggah Berkas Sekarang</span>
                                                <span wire:loading wire:target="uploadBerkasPenagihan" class="flex items-center gap-2">
                                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                    </svg>
                                                    Mengunggah Berkas...
                                                </span>
                                            </button>
                                            <button type="button" wire:click="$set('berkasPenagihanFile', null)"
                                                class="px-4 py-3 rounded-xl font-bold text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                                                Batal
                                            </button>
                                        </div>
                                    @else
                                        <h4 class="text-base font-bold text-slate-800 dark:text-white mb-1">
                                            Pilih atau Tarik Berkas Penagihan ke Sini
                                        </h4>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-5">
                                            Format: <strong>PDF (Sangat Disarankan)</strong>, ZIP, RAR, JPG, PNG (Maksimal 25 MB).
                                        </p>

                                        <label class="cursor-pointer inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold text-xs text-white bg-gradient-to-r from-[#F47920] to-[#BE5A27] hover:shadow-[0_8px_20px_rgba(244,121,32,0.4)] hover:-translate-y-0.5 active:translate-y-0 transition-all shadow-md">
                                            <input type="file" wire:model="berkasPenagihanFile" class="hidden" accept=".pdf,.zip,.rar,.jpg,.jpeg,.png">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Pilih Berkas Penagihan
                                        </label>
                                    @endif

                                    @error('berkasPenagihanFile')
                                        <div class="mt-3 p-2.5 rounded-xl bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-400 text-xs font-medium">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- DAFTAR 16 PERSYARATAN KELENGKAPAN DOKUMEN PENAGIHAN -->
                    <div class="pt-6 border-t border-slate-200/70 dark:border-white/10">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                            <div>
                                <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-slate-200/60 dark:bg-white/10 text-slate-700 dark:text-slate-300 text-[11px] font-bold uppercase tracking-wider mb-1">
                                    Pedoman Kelengkapan
                                </div>
                                <h4 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">
                                    16 Persyaratan Kelengkapan Dokumen Penagihan
                                </h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    Pastikan seluruh dokumen berikut telah tercakup di dalam 1 file bundel penagihan yang Anda unggah:
                                </p>
                            </div>
                            <span class="text-xs font-bold text-slate-400 font-mono">
                                Total 16 Dokumen
                            </span>
                        </div>

                        <!-- Grid 16 Items (2 Columns) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                            @foreach (($trackingData['checklist_definitions'] ?? []) as $key => $item)
                                <div class="p-4 rounded-2xl bg-white/40 dark:bg-white/5 border border-slate-200/70 dark:border-white/10 hover:border-[#F47920]/30 transition-all flex items-start gap-3.5">
                                    <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-white/10 text-slate-700 dark:text-slate-200 flex items-center justify-center text-xs font-black shrink-0 font-mono">
                                        {{ sprintf('%02d', $item['number']) }}
                                    </div>
                                    <div class="min-w-0 flex-grow">
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <h5 class="text-xs font-bold text-slate-800 dark:text-white truncate">
                                                {{ $item['name'] }}
                                            </h5>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold
                                                @if ($item['badge_color'] === 'rose')
                                                    bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20
                                                @elseif ($item['badge_color'] === 'amber')
                                                    bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20
                                                @elseif ($item['badge_color'] === 'indigo')
                                                    bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20
                                                @elseif ($item['badge_color'] === 'teal')
                                                    bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20
                                                @elseif ($item['badge_color'] === 'sky')
                                                    bg-sky-500/10 text-sky-600 dark:text-sky-400 border border-sky-500/20
                                                @elseif ($item['badge_color'] === 'purple')
                                                    bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20
                                                @else
                                                    bg-slate-200 dark:bg-white/10 text-slate-600 dark:text-slate-400
                                                @endif">
                                                {{ $item['badge'] }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                            {{ $item['desc'] }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Important Note Alert -->
                        <div class="mt-5 p-4 rounded-2xl bg-blue-500/10 border border-blue-500/20 text-blue-800 dark:text-blue-300 text-xs flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="leading-relaxed">
                                <strong>Ketentuan Urutan Dokumen:</strong>
                                Dokumen No. 01–05 bersifat <strong>Wajib</strong> bagi seluruh pengadaan. Dokumen No. 06–16 bersifat kondisional/pelengkap sesuai spesifikasi komoditas barang Anda. Susun dokumen secara berurutan di dalam file PDF Anda untuk mempercepat proses verifikasi oleh Tim Invoice PKT.
                            </div>
                        </div>
                    </div>

                </div>

                <!-- 3.5 TWO-COLUMN DETAILS: TIMELINE & SURAT JALAN -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- LEFT COLUMN (7 Cols): VERTICAL CHRONOLOGICAL TIMELINE -->
                    <div class="lg:col-span-7 glass-panel p-6 sm:p-8 rounded-3xl border border-white/80 dark:border-white/10 shadow-xl">
                        <div class="flex items-center justify-between mb-8 pb-4 border-b border-slate-200/70 dark:border-white/10">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-[#F47920] block">Audit Trail</span>
                                <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight">
                                    Riwayat Pergerakan Dokumen
                                </h3>
                            </div>
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">
                                {{ count($trackingData['timeline'] ?? []) }} Peristiwa
                            </span>
                        </div>

                        @if (empty($trackingData['timeline']))
                            <div class="text-center py-12 text-slate-400 dark:text-slate-500 text-sm">
                                Belum ada riwayat pergerakan dokumen tercatat.
                            </div>
                        @else
                            <div class="relative pl-6 sm:pl-8 space-y-8 before:absolute before:left-3 sm:before:left-4 before:top-3 before:bottom-3 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-700/80">
                                @foreach (($trackingData['timeline'] ?? []) as $event)
                                    <div class="relative group">
                                        <!-- Timeline Node Dot -->
                                        <div class="absolute -left-6 sm:-left-8 top-1 w-6 h-6 sm:w-8 sm:h-8 rounded-xl bg-white dark:bg-slate-900 border-2 border-[#F47920] flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                                            <div class="w-2 h-2 rounded-full bg-[#F47920]"></div>
                                        </div>

                                        <!-- Event Card -->
                                        <div class="p-4 sm:p-5 rounded-2xl bg-white/50 dark:bg-white/5 border border-slate-200/60 dark:border-white/5 hover:border-[#F47920]/30 transition-all duration-300">
                                            <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold
                                                    @if (str_contains($event['badge_color'], 'emerald'))
                                                        bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20
                                                    @elseif (str_contains($event['badge_color'], 'indigo'))
                                                        bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20
                                                    @elseif (str_contains($event['badge_color'], 'sky'))
                                                        bg-sky-500/10 text-sky-600 dark:text-sky-400 border border-sky-500/20
                                                    @elseif (str_contains($event['badge_color'], 'purple'))
                                                        bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20
                                                    @elseif (str_contains($event['badge_color'], 'rose') || str_contains($event['badge_color'], 'red'))
                                                        bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20
                                                    @elseif (str_contains($event['badge_color'], 'amber'))
                                                        bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20
                                                    @else
                                                        bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20
                                                    @endif">
                                                    {{ $event['badge'] }}
                                                </span>
                                                <div class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 flex items-center gap-1.5">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span>{{ $event['date'] }}</span>
                                                    @if ($event['time'] && $event['time'] !== '-')
                                                        <span>• {{ $event['time'] }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <h4 class="text-sm sm:text-base font-black text-slate-800 dark:text-white mb-1.5">
                                                {{ $event['title'] }}
                                            </h4>
                                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed font-normal">
                                                {{ $event['desc'] }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- RIGHT COLUMN (5 Cols): SURAT JALAN & GRS RECAP -->
                    <div class="lg:col-span-5 space-y-6">

                        <!-- Surat Jalan (DO) Cards -->
                        <div class="glass-panel p-6 sm:p-7 rounded-3xl border border-white/80 dark:border-white/10 shadow-xl">
                            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200/70 dark:border-white/10">
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-[#F47920] block">Bukti Pengiriman</span>
                                    <h3 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">
                                        Surat Jalan (DO) Masuk
                                    </h3>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-[#F47920]/10 text-[#F47920]">
                                    {{ isset($trackingData['unique_dos']) ? $trackingData['unique_dos']->count() : 0 }} DO
                                </span>
                            </div>

                            @if (!isset($trackingData['unique_dos']) || $trackingData['unique_dos']->isEmpty())
                                <div class="p-6 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-center text-xs text-amber-700 dark:text-amber-300">
                                    <svg class="w-8 h-8 mx-auto mb-2 text-amber-500 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <p class="font-bold">Belum Ada Surat Jalan Masuk</p>
                                    <p class="mt-1 text-slate-600 dark:text-slate-400">Material PO ini belum diserahkan ke Pintu Gudang Receiving PKT.</p>
                                </div>
                            @else
                                <div class="space-y-4">
                                    @foreach ($trackingData['unique_dos'] as $do)
                                        <div class="p-4 sm:p-5 rounded-2xl bg-white/40 dark:bg-white/5 border border-slate-200/70 dark:border-white/10 hover:shadow-md transition-all">
                                            <div class="flex items-start justify-between gap-3 mb-3">
                                                <div>
                                                    <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 block">No. Surat Jalan (DO)</span>
                                                    <h4 class="text-base font-black text-slate-800 dark:text-white tracking-tight">
                                                        {{ $do->delivery_order_no }}
                                                    </h4>
                                                </div>
                                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold
                                                    @if ($do->status === 'GRS' || $do->status === 'Diterima')
                                                        bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20
                                                    @elseif ($do->status === 'Pending')
                                                        bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20
                                                    @else
                                                        bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20
                                                    @endif">
                                                    {{ $do->status ?? 'Diterima' }}
                                                </span>
                                            </div>

                                            <div class="space-y-2 text-xs">
                                                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                                    <span class="text-slate-400 dark:text-slate-500">Kode Dokumen:</span>
                                                    <span class="font-mono font-semibold">{{ $do->document_code }}</span>
                                                </div>
                                                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                                    <span class="text-slate-400 dark:text-slate-500">Tanggal Tiba:</span>
                                                    <span class="font-semibold">{{ $do->received_date ? \Carbon\Carbon::parse($do->received_date)->format('d M Y') : '-' }}</span>
                                                </div>
                                                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                                    <span class="text-slate-400 dark:text-slate-500">Mode Kedatangan:</span>
                                                    <span class="font-semibold">{{ $do->receipt_mode ?? 'Standard' }}</span>
                                                </div>
                                                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                                    <span class="text-slate-400 dark:text-slate-500">Status Post 103:</span>
                                                    @if ($do->post_103)
                                                        <span class="font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                            Sudah di-Post
                                                        </span>
                                                    @else
                                                        <span class="text-slate-400 font-medium">Belum Post 103</span>
                                                    @endif
                                                </div>

                                                <!-- QR 103 Code if exists -->
                                                @if ($do->qr_103_code)
                                                    <div class="mt-2 pt-2 border-t border-slate-200/50 dark:border-white/5 flex items-center justify-between">
                                                        <span class="text-slate-400 dark:text-slate-500">Kode QR 103:</span>
                                                        <span class="font-mono font-bold text-[#F47920]">{{ $do->qr_103_code }}</span>
                                                    </div>
                                                @endif

                                                <!-- GRS/RDTV indicator if exists -->
                                                @if ($do->grsRdtvItems->isNotEmpty())
                                                    <div class="mt-2 pt-2 border-t border-slate-200/50 dark:border-white/5">
                                                        <span class="text-slate-400 dark:text-slate-500 block mb-1">Slip Resmi Diterbitkan:</span>
                                                        @foreach ($do->grsRdtvItems as $gItem)
                                                            <div class="flex items-center justify-between bg-emerald-500/5 p-2 rounded-lg text-[11px] font-semibold text-emerald-700 dark:text-emerald-300">
                                                                <span>{{ $gItem->grsRdtv?->category ?? 'GRS' }}</span>
                                                                <span>{{ $gItem->grsRdtv?->transaction_date ? \Carbon\Carbon::parse($gItem->grsRdtv->transaction_date)->format('d M Y') : '-' }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                <!-- Delay Reason if any -->
                                                @if ($do->delay_reason)
                                                    <div class="mt-2 p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-700 dark:text-amber-300 text-[11px]">
                                                        <span class="font-bold block">Catatan Penundaan:</span>
                                                        <span>{{ $do->delay_reason }}</span>
                                                        @if ($do->delay_notes)
                                                            <span class="block text-slate-500 dark:text-slate-400 mt-0.5">{{ $do->delay_notes }}</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Help / Vendor Contact Card -->
                        <div class="p-5 rounded-3xl bg-gradient-to-br from-[#0A4F86]/10 to-transparent border border-blue-500/20 text-xs">
                            <h4 class="font-black text-slate-800 dark:text-white mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#F47920]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                Butuh Bantuan Terkait Dokumen?
                            </h4>
                            <p class="text-slate-600 dark:text-slate-300 leading-relaxed mb-3">
                                Jika terdapat ketidaksesuaian data surat jalan atau dokumen GRS belum terbit melebihi SLA operasional, silakan hubungi Tim Receiving Pupuk Kalimantan Timur.
                            </p>
                            <div class="font-semibold text-slate-700 dark:text-slate-200 space-y-1 text-[11px]">
                                <div>• Lokasi: Gudang Pusat Receiving PKT, Bontang</div>
                                <div>• Modul: Ekspedisi & Verifikasi Penagihan</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3.4 MATERIAL ITEMS & FULFILLMENT STATUS -->
                <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-white/80 dark:border-white/10 shadow-xl overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-200/70 dark:border-white/10">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-[#F47920] block">Daftar Material</span>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight">
                                Rincian Item PO & Realisasi Penerimaan Fisik
                            </h3>
                        </div>
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">
                            Total: {{ count($trackingData['material_items'] ?? []) }} Item Material
                        </span>
                    </div>

                    <!-- Items Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs sm:text-sm">
                            <thead class="text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 border-b border-slate-200/60 dark:border-white/10">
                                <tr>
                                    <th class="pb-3 px-3">Item #</th>
                                    <th class="pb-3 px-3">Kode Material</th>
                                    <th class="pb-3 px-3">Deskripsi Barang</th>
                                    <th class="pb-3 px-3 text-right">Target PO</th>
                                    <th class="pb-3 px-3 text-right">Realisasi Tiba</th>
                                    <th class="pb-3 px-3 text-center">Status</th>
                                    <th class="pb-3 px-3">Lokasi Gudang</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/50 dark:divide-white/5 font-medium text-slate-700 dark:text-slate-300">
                                @foreach (($trackingData['material_items'] ?? []) as $item)
                                    <tr class="hover:bg-white/40 dark:hover:bg-white/5 transition-colors">
                                        <td class="py-4 px-3 font-mono font-bold text-slate-500 dark:text-slate-400">{{ $item['item_no'] }}</td>
                                        <td class="py-4 px-3 font-mono font-bold text-[#F47920]">{{ $item['material_code'] }}</td>
                                        <td class="py-4 px-3 max-w-xs">
                                            <div class="font-bold text-slate-800 dark:text-white">{{ $item['description'] }}</div>
                                            <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                                MRP: {{ $item['mrp_type'] }} • Tipe: {{ $item['material_type'] }}
                                            </div>
                                        </td>
                                        <td class="py-4 px-3 text-right font-mono font-bold">
                                            {{ rtrim(rtrim(number_format($item['qty_po'], 3, ',', '.'), '0'), ',') }} {{ $item['uoi'] }}
                                        </td>
                                        <td class="py-4 px-3 text-right font-mono font-bold
                                            @if ($item['qty_received'] >= $item['qty_po'] && $item['qty_po'] > 0)
                                                text-emerald-600 dark:text-emerald-400
                                            @elseif ($item['qty_received'] > 0)
                                                text-amber-600 dark:text-amber-400
                                            @else
                                                text-slate-400
                                            @endif">
                                            {{ rtrim(rtrim(number_format($item['qty_received'], 3, ',', '.'), '0'), ',') }} {{ $item['uoi'] }}
                                        </td>
                                        <td class="py-4 px-3 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold
                                                @if ($item['status_color'] === 'emerald')
                                                    bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20
                                                @elseif ($item['status_color'] === 'amber')
                                                    bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20
                                                @else
                                                    bg-slate-200/60 dark:bg-white/10 text-slate-600 dark:text-slate-400
                                                @endif">
                                                {{ $item['status_text'] }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-3 text-xs text-slate-500 dark:text-slate-400">
                                            {{ $item['locations'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        @endif

    </div>

</div>
