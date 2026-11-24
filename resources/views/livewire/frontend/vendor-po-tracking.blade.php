<div class="min-h-screen text-slate-800 dark:text-slate-200 transition-colors duration-200 pt-28 pb-24 font-sans relative overflow-hidden">

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
                {{ __('Portal Tracking Vendor PKT • Khusus Rekanan') }}
            </div>
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-800 dark:text-white tracking-tight mb-4">
                {{ __('Lacak Pergerakan') }} <br class="hidden sm:inline">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#F47920] via-orange-400 to-[#BE5A27]">
                    {{ __('Dokumen & Fisik Material PO') }}
                </span>
            </h1>
            <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 font-medium max-w-2xl mx-auto">
                {{ __('Pantau status penerimaan surat jalan, Pengajuan QC, hingga penerbitan GRS khusus bagi Rekanan/Vendor PT Pupuk Kalimantan Timur (wajib verifikasi email resmi vendor).') }}
            </p>
            <div class="mt-4 inline-flex flex-wrap items-center justify-center gap-2 px-4 py-2 rounded-2xl bg-blue-500/10 dark:bg-blue-500/15 border border-blue-500/25 text-xs text-slate-700 dark:text-slate-200">
                <span class="font-bold text-[#0A4F86] dark:text-sky-400">{{ __('Info User Internal PKT:') }}</span>
                <span>{{ __('Untuk cek kedatangan barang atau pengambilan material, gunakan') }}</span>
                <a href="{{ route('frontend.list-material') }}" wire:navigate class="font-extrabold text-[#F47920] underline hover:text-[#BE5A27]">{{ __('Daftar Material') }}</a>
                <span>{{ __('atau tanyakan pada asisten ALEX di pojok kanan bawah.') }}</span>
            </div>
        </div>

        <!-- 1. PO SEARCH BOX (Apple HIG Clean Elevated Panel) -->
        <div class="max-w-2xl sm:max-w-3xl mx-auto mb-14">
            <!-- Outer Elevated Card -->
            <div class="glass-panel p-4 sm:p-6 rounded-3xl relative">
                
                <form wire:submit="initiateSearch" class="relative z-10 space-y-3">

                    <!-- Input & Button Row -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <!-- Uiverse Animated Focus Search Shell -->
                        <div class="uiverse-search-shell flex-grow">
                            <div class="uiverse-search-inner relative flex items-center">
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
                                    placeholder="{{ __('Ketik nomor PO (misal: 53000xxxxx)') }}"
                                    class="w-full bg-transparent border-0 ring-0 focus:ring-0 focus:outline-none px-3 py-3.5 sm:py-4 text-base sm:text-lg font-bold font-mono tracking-wider text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 placeholder:font-sans placeholder:font-normal placeholder:tracking-normal placeholder:text-sm sm:placeholder:text-base"
                                    autocomplete="off"
                                    maxlength="15"
                                    autofocus>

                                <!-- Clear Button (when poNumber is filled) -->
                                @if (!empty($poNumber))
                                    <button type="button" wire:click="$set('poNumber', '')"
                                        class="p-2 mr-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors shrink-0 cursor-pointer"
                                        title="{{ __('Hapus input') }}">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                @endif

                                <!-- Keyboard Hint Badge -->
                                <div class="hidden md:flex items-center pr-3.5 select-none pointer-events-none shrink-0">
                                    <kbd class="px-2 py-1 text-[10px] font-bold font-mono text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-lg shadow-xs">
                                        ↵ Enter
                                    </kbd>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button: Uiverse Shimmering CTA -->
                        <button type="submit" wire:loading.attr="disabled"
                            class="uiverse-cta-btn px-7 py-3.5 sm:py-4 rounded-2xl text-sm shrink-0 cursor-pointer disabled:opacity-75">
                            <span wire:loading.remove wire:target="initiateSearch, selectSamplePo" class="flex items-center gap-2">
                                <span>{{ __('Lacak Dokumen') }}</span>
                                <svg class="uiverse-arrow w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </span>
                            <span wire:loading wire:target="initiateSearch, selectSamplePo" class="flex items-center gap-2.5">
                                <span class="uiverse-loader-dots text-white" aria-hidden="true"><span></span><span></span><span></span></span>
                                <span>{{ __('Mengecek PO...') }}</span>
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
                    <span>{{ __('Verifikasi Email Vendor') }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>{{ __('Enkripsi Akses Berkas') }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-[#F47920]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>{{ __('Pembaruan Status Penerimaan (103/105)') }}</span>
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
                            <span class="text-[11px] font-black uppercase tracking-wider text-[#F47920] block">{{ __('Verifikasi Keamanan') }}</span>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                                {{ __('Akses Riwayat Dokumen') }}
                            </h3>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5 font-medium">
                                {{ __('Masukkan nomor PO & email terdaftar untuk melihat riwayat lengkap.') }}
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
                                {{ __('Nomor Purchase Order (PO)') }}
                            </label>
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('10 Digit Nomor PO') }}</span>
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
                                placeholder="{{ __('Ketik 10 digit nomor PO (contoh: 5300065628)...') }}"
                                class="w-full bg-transparent border-0 ring-0 focus:ring-0 focus:outline-none px-3 py-3 text-sm sm:text-base font-mono font-black text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 placeholder:font-sans placeholder:font-normal placeholder:text-xs sm:placeholder:text-sm"
                                autocomplete="off"
                                maxlength="15">
                            @if (!empty($poNumber))
                                <button type="button" wire:click="$set('poNumber', '')"
                                    class="p-1.5 mr-2 rounded-lg text-slate-500 hover:text-slate-800 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors shrink-0 cursor-pointer"
                                    title="{{ __('Hapus input PO') }}">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            @endif
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
                                    <span class="text-[10px] uppercase font-black text-emerald-700 dark:text-emerald-400 block tracking-wider">{{ __('Vendor Teridentifikasi:') }}</span>
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
                                <span class="font-black block text-blue-950 dark:text-blue-100 text-xs">{{ __('Petunjuk Email Terdaftar untuk PO Ini:') }}</span>
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
                                <span class="font-black block mb-1 text-amber-950 dark:text-amber-100 text-xs">{{ __('Informasi Kontak Master:') }}</span>
                                <p class="leading-relaxed text-amber-900 dark:text-amber-200 font-semibold">{{ $vendorInfo['error_message'] }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- 2. Input Alamat Email Vendor -->
                    <div>
                        <label for="verificationEmailInput" class="block text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200 mb-1.5 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            {{ __('Alamat Email Resmi Vendor') }}
                        </label>
                        <div class="relative flex items-center rounded-2xl bg-slate-50 dark:bg-slate-900 border-2 border-slate-300 dark:border-slate-700 focus-within:border-blue-500 dark:focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-500/20 transition-all shadow-sm">
                            <div class="pl-3.5 pr-2 text-blue-500 flex items-center pointer-events-none shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                                </svg>
                            </div>
                            <input id="verificationEmailInput" wire:model="verificationEmail" type="email"
                                placeholder="{{ __('Masukkan email resmi perusahaan vendor...') }}"
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
                            {{ __('Batal') }}
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="px-6 py-2.5 rounded-xl font-black text-xs text-white bg-gradient-to-r from-[#F47920] to-[#BE5A27] hover:shadow-[0_8px_20px_rgba(244,121,32,0.4)] active:scale-95 transition-all flex items-center gap-2 cursor-pointer shadow-md">
                            <span wire:loading.remove wire:target="verifyVendorEmail">{{ __('Verifikasi & Tampilkan Riwayat') }}</span>
                            <span wire:loading wire:target="verifyVendorEmail" class="flex items-center gap-1.5">
                                <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                {{ __('Memverifikasi...') }}
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

                            <h2 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white tracking-tight">
                                {{ __('Purchase Order') }} #{{ $trackingData['po']['number'] ?? '-' }}
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
                                {{ __('Lacak PO Lain') }}
                            </button>
                        </div>
                    </div>

                    <!-- PO Key Attributes Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 pt-6 text-xs sm:text-sm">
                        <div class="p-3.5 rounded-2xl bg-white/40 dark:bg-white/5 border border-white/60 dark:border-white/5">
                            <span class="text-slate-400 dark:text-slate-500 text-[11px] font-bold block mb-1">{{ __('Tanggal Terbit PO') }}</span>
                            <span class="font-bold text-slate-800 dark:text-white">{{ $trackingData['po']['date_create'] ?? '-' }}</span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-white/40 dark:bg-white/5 border border-white/60 dark:border-white/5">
                            <span class="text-slate-400 dark:text-slate-500 text-[11px] font-bold block mb-1">{{ __('Target Delivery PO') }}</span>
                            <span class="font-bold text-slate-800 dark:text-white">{{ $trackingData['po']['delivery_date_po'] ?? '-' }}</span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-white/40 dark:bg-white/5 border border-white/60 dark:border-white/5">
                            <span class="text-slate-400 dark:text-slate-500 text-[11px] font-bold block mb-1">{{ __('Unit Pemohon') }}</span>
                            <span class="font-bold text-slate-800 dark:text-white truncate block" title="{{ $trackingData['po']['requisitioner'] ?? '-' }}">
                                {{ $trackingData['po']['requisitioner'] ?? '-' }}
                            </span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-white/40 dark:bg-white/5 border border-white/60 dark:border-white/5">
                            <span class="text-slate-400 dark:text-slate-500 text-[11px] font-bold block mb-1">{{ __('Total Item Material') }}</span>
                            <span class="font-bold text-slate-800 dark:text-white">{{ $trackingData['po']['total_items'] ?? 0 }} {{ __('Baris Item') }}</span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-white/40 dark:bg-white/5 border border-white/60 dark:border-white/5 col-span-2 sm:col-span-1">
                            <span class="text-slate-400 dark:text-slate-500 text-[11px] font-bold block mb-1">{{ __('Surat Jalan (DO) Masuk') }}</span>
                            <span class="font-bold text-[#F47920]">{{ isset($trackingData['unique_dos']) ? $trackingData['unique_dos']->count() : 0 }} {{ __('Surat Jalan') }}</span>
                        </div>
                    </div>
                </div>

                <!-- 3.2 INTERACTIVE DYNAMIC DOCUMENT JOURNEY (SCROLLABLE SPRING TRACK + INTEGRATED AUDIT TRAIL) -->
                @php
                    $isRdtv = $trackingData['pipeline']['is_rdtv'] ?? false;
                    $stageIdx = $trackingData['pipeline']['current_stage_index'] ?? 1;
                    $timelineEvents = $trackingData['timeline'] ?? [];

                    $journeyNodes = [];
                    $realCount = count($timelineEvents);

                    foreach ($timelineEvents as $evIdx => $ev) {
                        $isLatestReal = ($evIdx === $realCount - 1);
                        $colorKey = match (true) {
                            str_contains($ev['badge_color'] ?? '', 'emerald') => 'emerald',
                            str_contains($ev['badge_color'] ?? '', 'indigo') => 'indigo',
                            str_contains($ev['badge_color'] ?? '', 'sky') => 'sky',
                            str_contains($ev['badge_color'] ?? '', 'purple') => 'purple',
                            str_contains($ev['badge_color'] ?? '', 'rose') || str_contains($ev['badge_color'] ?? '', 'red') => 'rose',
                            str_contains($ev['badge_color'] ?? '', 'amber') => 'amber',
                            str_contains($ev['badge_color'] ?? '', 'teal') => 'teal',
                            str_contains($ev['badge_color'] ?? '', 'cyan') => 'cyan',
                            default => 'orange',
                        };

                        $journeyNodes[] = [
                            'index' => $evIdx + 1,
                            'status' => $isLatestReal ? 'current' : 'done',
                            'color' => $colorKey,
                            'icon' => $ev['icon'] ?? 'check',
                            'title' => $ev['short_title'] ?? __($ev['title'] ?? ''),
                            'full_title' => __($ev['title'] ?? ''),
                            'short_desc' => $ev['short_desc'] ?? ($ev['desc'] ?? ''),
                            'desc' => $ev['desc'] ?? '',
                            'badge' => __($ev['badge'] ?? ''),
                            'date' => $ev['date'] ?? '-',
                            'time' => ($ev['time'] ?? '-') !== '-' ? $ev['time'] : '',
                        ];
                    }

                    // Tambahkan tahapan SOP berikutnya yang belum dilalui sebagai pratinjau (dimmed upcoming nodes)
                    $upcomingSteps = [
                        2 => ['title' => __('DO & Fisik Tiba'), 'sub' => __('Pintu Receiving'), 'desc' => __('Menunggu kedatangan fisik barang & surat jalan di Pintu Gudang Receiving PKT.'), 'icon' => 'truck'],
                        3 => ['title' => __('Posting Penerimaan (103)'), 'sub' => __('Validasi Sistem'), 'desc' => __('Pencatatan MIGO 103 di sistem setelah dokumen kedatangan diterima.'), 'icon' => 'qr-code'],
                        4 => ['title' => __('Inspeksi QC'), 'sub' => __('Uji Mutu ISTEK'), 'desc' => __('Pemeriksaan kesesuaian spesifikasi & mutu barang oleh tim ISTEK.'), 'icon' => 'beaker'],
                        5 => ['title' => $isRdtv ? __('Slip RDTV (Retur)') : __('Slip GRS (105)'), 'sub' => $isRdtv ? __('Ditolak / Retur') : __('Arsip Penagihan'), 'desc' => __('Penerbitan dokumen GRS (105) sebagai dasar penagihan vendor.'), 'icon' => 'clipboard-check'],
                    ];

                    if ($realCount === 0) {
                        $journeyNodes[] = [
                            'index' => 1,
                            'status' => 'current',
                            'color' => 'orange',
                            'icon' => 'document-check',
                            'title' => __('PO Terbit'),
                            'full_title' => __('Purchase Order (PO) Diterbitkan'),
                            'short_desc' => __('Diterbitkan PKT'),
                            'desc' => __('Purchase Order telah terbit dan menunggu pengiriman material dari vendor.'),
                            'badge' => __('PO Issued'),
                            'date' => '-',
                            'time' => '',
                        ];
                    }

                    foreach ($upcomingSteps as $stepNum => $uStep) {
                        if ($stageIdx < $stepNum) {
                            $journeyNodes[] = [
                                'index' => count($journeyNodes) + 1,
                                'status' => 'upcoming',
                                'color' => 'slate',
                                'icon' => $uStep['icon'],
                                'title' => $uStep['title'],
                                'full_title' => $uStep['title'],
                                'short_desc' => $uStep['sub'],
                                'desc' => $uStep['desc'],
                                'badge' => __('Tahap Berikutnya'),
                                'date' => __('Menunggu'),
                                'time' => '',
                            ];
                        }
                    }

                    $defaultActiveIdx = max(0, $realCount - 1);
                    $totalNodes = count($journeyNodes);
                    $completedRatio = $totalNodes > 1
                        ? min(100, max(12, round((($defaultActiveIdx + 0.5) / $totalNodes) * 100)))
                        : 100;
                @endphp

                <div
                    class="glass-panel p-5 sm:p-8 rounded-3xl border border-white/80 dark:border-white/10 shadow-xl relative overflow-hidden"
                    x-data="{
                        nodes: [],
                        pinnedIdx: {{ $defaultActiveIdx }},
                        hoveredIdx: null,
                        spotlightPulse: false,
                        canScrollLeft: false,
                        canScrollRight: false,
                        isDragging: false,
                        startX: 0,
                        scrollStart: 0,
                        dragMoved: false,
                        get activeIdx() {
                            return this.hoveredIdx !== null ? this.hoveredIdx : this.pinnedIdx;
                        },
                        get activeNode() {
                            return this.nodes[this.activeIdx] || this.nodes[0] || null;
                        },
                        init() {
                            try {
                                this.nodes = JSON.parse(this.$refs.journeyData?.textContent || '[]');
                            } catch (e) {
                                this.nodes = [];
                            }
                            this.$nextTick(() => {
                                this.updateArrows();
                                this.scrollToNode(this.pinnedIdx, false);
                            });
                        },
                        updateArrows() {
                            const el = this.$refs.track;
                            if (!el) return;
                            this.canScrollLeft = el.scrollLeft > 8;
                            this.canScrollRight = (el.scrollLeft + el.clientWidth) < (el.scrollWidth - 8);
                        },
                        scrollByAmount(delta) {
                            const el = this.$refs.track;
                            if (!el) return;
                            el.scrollBy({ left: delta, behavior: 'smooth' });
                        },
                        scrollToNode(idx, smooth = true) {
                            const el = this.$refs.track;
                            const card = el?.querySelector(`[data-journey-idx='${idx}']`);
                            if (!el || !card) return;
                            const targetLeft = Math.max(0, card.offsetLeft - (el.clientWidth / 2) + (card.offsetWidth / 2));
                            el.scrollTo({ left: targetLeft, behavior: smooth ? 'smooth' : 'auto' });
                            setTimeout(() => this.updateArrows(), 280);
                        },
                        selectNode(idx) {
                            if (this.dragMoved) return;
                            this.pinnedIdx = idx;
                            this.hoveredIdx = null;
                            this.scrollToNode(idx, true);
                            this.spotlightPulse = false;
                            this.$nextTick(() => {
                                this.spotlightPulse = true;
                                setTimeout(() => { this.spotlightPulse = false; }, 550);
                            });
                        },
                        stepSpotlight(dir) {
                            if (!this.nodes.length) return;
                            const next = Math.min(this.nodes.length - 1, Math.max(0, this.activeIdx + dir));
                            this.pinnedIdx = next;
                            this.hoveredIdx = null;
                            this.scrollToNode(next, true);
                        },
                        onPointerDown(e) {
                            const el = this.$refs.track;
                            if (!el || e.button !== 0) return;
                            this.isDragging = true;
                            this.dragMoved = false;
                            this.startX = e.pageX - el.offsetLeft;
                            this.scrollStart = el.scrollLeft;
                        },
                        onPointerMove(e) {
                            if (!this.isDragging) return;
                            const el = this.$refs.track;
                            if (!el) return;
                            const x = e.pageX - el.offsetLeft;
                            const walk = (x - this.startX) * 1.25;
                            if (Math.abs(walk) > 6) this.dragMoved = true;
                            el.scrollLeft = this.scrollStart - walk;
                            this.updateArrows();
                        },
                        onPointerUp() {
                            this.isDragging = false;
                            setTimeout(() => { this.dragMoved = false; }, 60);
                        }
                    }"
                    @resize.window="updateArrows()"
                >
                    <script type="application/json" x-ref="journeyData">@json($journeyNodes)</script>

                    <!-- Header Row -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-[#F47920]">{{ __('Alur Proses Logistik') }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-white/10 text-slate-600 dark:text-slate-300">
                                    {{ $realCount }} {{ __('Tahap Tercatat') }}
                                </span>
                            </div>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight mt-0.5">
                                {{ __('Posisi Perjalanan Dokumen Saat Ini') }}
                            </h3>
                        </div>

                        <div class="flex items-center gap-2 self-start sm:self-center">
                            <span class="px-3.5 py-1.5 rounded-full text-xs font-black inline-flex items-center gap-1.5 {{ $isRdtv ? 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30' : 'bg-[#F47920]/15 text-[#F47920] border border-[#F47920]/30' }}">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $isRdtv ? 'bg-rose-500' : 'bg-[#F47920]' }} opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 {{ $isRdtv ? 'bg-rose-500' : 'bg-[#F47920]' }}"></span>
                                </span>
                                {{ $trackingData['pipeline']['current_stage_label'] ?? __('Menunggu') }}
                            </span>

                            <!-- Horizontal Scroll Nav Buttons -->
                            <div class="hidden sm:flex items-center gap-1.5 ml-1">
                                <button
                                    type="button"
                                    @click="scrollByAmount(-280)"
                                    :disabled="!canScrollLeft"
                                    aria-label="{{ __('Geser ke kiri') }}"
                                    class="w-9 h-9 rounded-full inline-flex items-center justify-center border border-[#F47920]/40 bg-[#F47920]/15 text-[#F47920] shadow-xs transition-all duration-200 hover:bg-[#F47920] hover:text-white hover:border-[#F47920] hover:shadow-md hover:shadow-[#F47920]/30 hover:scale-105 active:scale-95 disabled:bg-slate-100 dark:disabled:bg-white/5 disabled:text-slate-400 dark:disabled:text-slate-500 disabled:border-slate-200/70 dark:disabled:border-white/10 disabled:shadow-none disabled:hover:scale-100 disabled:opacity-45 disabled:cursor-not-allowed cursor-pointer"
                                >
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    @click="scrollByAmount(280)"
                                    :disabled="!canScrollRight"
                                    aria-label="{{ __('Geser ke kanan') }}"
                                    class="w-9 h-9 rounded-full inline-flex items-center justify-center border border-[#F47920]/40 bg-[#F47920]/15 text-[#F47920] shadow-xs transition-all duration-200 hover:bg-[#F47920] hover:text-white hover:border-[#F47920] hover:shadow-md hover:shadow-[#F47920]/30 hover:scale-105 active:scale-95 disabled:bg-slate-100 dark:disabled:bg-white/5 disabled:text-slate-400 dark:disabled:text-slate-500 disabled:border-slate-200/70 dark:disabled:border-white/10 disabled:shadow-none disabled:hover:scale-100 disabled:opacity-45 disabled:cursor-not-allowed cursor-pointer"
                                >
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Animated Luminous Progress Beam -->
                    <div class="relative mb-5">
                        <div class="h-1.5 w-full bg-slate-200/80 dark:bg-white/10 rounded-full overflow-hidden">
                            <div
                                class="relative h-full {{ $isRdtv ? 'bg-gradient-to-r from-emerald-500 via-[#F47920] to-rose-500' : 'bg-gradient-to-r from-[#0054A6] via-emerald-500 to-[#F47920]' }} rounded-full transition-all duration-700 ease-[cubic-bezier(0.34,1.2,0.64,1)] overflow-hidden"
                                style="width: {{ $completedRatio }}%;"
                            >
                                <span class="journey-beam-shimmer" aria-hidden="true"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Draggable & Scrollable Horizontal Node Rail -->
                    <div class="relative -mx-2 px-2">
                        <!-- Soft Edge Fade Hints -->
                        <div
                            x-show="canScrollLeft"
                            x-transition.opacity.duration.200ms
                            class="pointer-events-none absolute left-0 top-0 bottom-0 w-10 z-20 bg-gradient-to-r from-white/90 dark:from-[#0b1626]/90 to-transparent"
                        ></div>
                        <div
                            x-show="canScrollRight"
                            x-transition.opacity.duration.200ms
                            class="pointer-events-none absolute right-0 top-0 bottom-0 w-10 z-20 bg-gradient-to-l from-white/90 dark:from-[#0b1626]/90 to-transparent"
                        ></div>

                        <div
                            x-ref="track"
                            @scroll.passive="updateArrows()"
                            @mousedown="onPointerDown($event)"
                            @mousemove="onPointerMove($event)"
                            @mouseup="onPointerUp()"
                            @mouseleave="onPointerUp(); hoveredIdx = null"
                            :class="isDragging ? 'is-dragging' : ''"
                            class="journey-scroll-track flex items-stretch gap-3.5 overflow-x-auto pt-3 pb-4 px-1 snap-x snap-proximity"
                        >
                            @foreach ($journeyNodes as $nIdx => $node)
                                @php
                                    $isCurrent = $node['status'] === 'current';
                                    $isDone = $node['status'] === 'done';
                                    $isUpcoming = $node['status'] === 'upcoming';
                                    $c = $node['color'];

                                    $accentCard = match ($c) {
                                        'emerald' => 'border-emerald-500/35 bg-gradient-to-br from-emerald-500/12 via-white/75 to-white/50 dark:from-emerald-500/15 dark:via-white/[0.04] dark:to-white/[0.02] hover:border-emerald-500 hover:shadow-[0_14px_30px_-10px_rgba(16,185,129,0.35)]',
                                        'indigo' => 'border-indigo-500/35 bg-gradient-to-br from-indigo-500/12 via-white/75 to-white/50 dark:from-indigo-500/15 dark:via-white/[0.04] dark:to-white/[0.02] hover:border-indigo-500 hover:shadow-[0_14px_30px_-10px_rgba(99,102,241,0.35)]',
                                        'sky' => 'border-sky-500/35 bg-gradient-to-br from-sky-500/12 via-white/75 to-white/50 dark:from-sky-500/15 dark:via-white/[0.04] dark:to-white/[0.02] hover:border-sky-500 hover:shadow-[0_14px_30px_-10px_rgba(14,165,233,0.35)]',
                                        'purple' => 'border-purple-500/35 bg-gradient-to-br from-purple-500/12 via-white/75 to-white/50 dark:from-purple-500/15 dark:via-white/[0.04] dark:to-white/[0.02] hover:border-purple-500 hover:shadow-[0_14px_30px_-10px_rgba(168,85,247,0.35)]',
                                        'rose' => 'border-rose-500/45 bg-gradient-to-br from-rose-500/15 via-white/75 to-white/50 dark:from-rose-500/20 dark:via-white/[0.04] dark:to-white/[0.02] hover:border-rose-500 hover:shadow-[0_14px_30px_-10px_rgba(244,63,94,0.4)]',
                                        'amber' => 'border-amber-500/40 bg-gradient-to-br from-amber-500/12 via-white/75 to-white/50 dark:from-amber-500/15 dark:via-white/[0.04] dark:to-white/[0.02] hover:border-amber-500 hover:shadow-[0_14px_30px_-10px_rgba(245,158,11,0.35)]',
                                        'teal' => 'border-teal-500/35 bg-gradient-to-br from-teal-500/12 via-white/75 to-white/50 dark:from-teal-500/15 dark:via-white/[0.04] dark:to-white/[0.02] hover:border-teal-500 hover:shadow-[0_14px_30px_-10px_rgba(20,184,166,0.35)]',
                                        'cyan' => 'border-cyan-500/35 bg-gradient-to-br from-cyan-500/12 via-white/75 to-white/50 dark:from-cyan-500/15 dark:via-white/[0.04] dark:to-white/[0.02] hover:border-cyan-500 hover:shadow-[0_14px_30px_-10px_rgba(6,182,212,0.35)]',
                                        'slate' => 'border-dashed border-slate-300/70 dark:border-white/15 bg-white/35 dark:bg-white/[0.02] opacity-65 hover:opacity-95 hover:border-slate-400',
                                        default => 'border-[#F47920]/40 bg-gradient-to-br from-[#F47920]/15 via-white/75 to-white/50 dark:from-[#F47920]/18 dark:via-white/[0.04] dark:to-white/[0.02] hover:border-[#F47920] hover:shadow-[0_14px_30px_-10px_rgba(244,121,32,0.38)]',
                                    };

                                    $iconBg = match ($c) {
                                        'emerald' => 'bg-emerald-500 text-white shadow-emerald-500/30',
                                        'indigo' => 'bg-indigo-500 text-white shadow-indigo-500/30',
                                        'sky' => 'bg-sky-500 text-white shadow-sky-500/30',
                                        'purple' => 'bg-purple-500 text-white shadow-purple-500/30',
                                        'rose' => 'bg-rose-500 text-white shadow-rose-500/35',
                                        'amber' => 'bg-amber-500 text-white shadow-amber-500/30',
                                        'teal' => 'bg-teal-500 text-white shadow-teal-500/30',
                                        'cyan' => 'bg-cyan-500 text-white shadow-cyan-500/30',
                                        'slate' => 'bg-slate-200 dark:bg-white/10 text-slate-500 dark:text-slate-400',
                                        default => 'bg-[#F47920] text-white shadow-orange-500/35',
                                    };
                                @endphp

                                <div
                                    data-journey-idx="{{ $nIdx }}"
                                    role="button"
                                    tabindex="0"
                                    @mouseenter="hoveredIdx = {{ $nIdx }}"
                                    @mouseleave="hoveredIdx = null"
                                    @focus="hoveredIdx = {{ $nIdx }}"
                                    @blur="hoveredIdx = null"
                                    @click="selectNode({{ $nIdx }})"
                                    @keydown.enter.prevent="selectNode({{ $nIdx }})"
                                    style="animation-delay: {{ min($nIdx * 55, 450) }}ms;"
                                    :class="activeIdx === {{ $nIdx }} ? 'is-active-node ring-2 ring-[#F47920]/50 dark:ring-[#F47920]/60' : ''"
                                    class="journey-node-card snap-start shrink-0 w-[215px] sm:w-[230px] p-4 rounded-2xl border {{ $isCurrent ? 'border-2 shadow-lg' : '' }} {{ $accentCard }} relative flex flex-col justify-between text-left cursor-pointer select-none"
                                >
                                    <div>
                                        <!-- Top Row: Icon Badge + Step Number / Live Pulse -->
                                        <div class="flex items-center justify-between gap-2 mb-2.5">
                                            <div class="flex items-center gap-2">
                                                <div class="journey-node-icon w-8 h-8 rounded-xl flex items-center justify-center shadow-md {{ $iconBg }}">
                                                    @if ($node['icon'] === 'truck')
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                                    @elseif ($node['icon'] === 'qr-code')
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                                    @elseif ($node['icon'] === 'beaker')
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                                    @elseif ($node['icon'] === 'clipboard-check')
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                                    @elseif ($node['icon'] === 'clock')
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    @elseif ($node['icon'] === 'currency-dollar' || $node['icon'] === 'banknotes')
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    @elseif ($node['icon'] === 'arrow-right-on-rectangle')
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                                    @else
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                    @endif
                                                </div>
                                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                                    #{{ $node['index'] }}
                                                </span>
                                            </div>

                                            @if ($isCurrent)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wide {{ $c === 'rose' ? 'bg-rose-500 text-white' : 'bg-[#F47920] text-white' }} shadow-xs">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                                    {{ __('Terkini') }}
                                                </span>
                                            @elseif ($isDone)
                                                <span class="inline-flex items-center text-[10px] font-semibold text-slate-400 dark:text-slate-500 tabular-nums">
                                                    {{ $node['date'] }}
                                                </span>
                                            @else
                                                <span class="text-[9px] font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                                    {{ __('Berikutnya') }}
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Concise Title & Brief Summary -->
                                        <div class="font-black text-xs sm:text-sm text-slate-800 dark:text-white leading-snug line-clamp-2">
                                            {{ $node['title'] }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium mt-1 line-clamp-2">
                                            {{ $node['short_desc'] }}
                                        </div>
                                    </div>

                                    <!-- Hover / Active Spring-Reveal Footer (No duplicate date, clear click feedback) -->
                                    <div class="journey-hover-reveal pt-2 border-t border-slate-200/60 dark:border-white/10 flex items-center justify-between gap-2 text-[10px]">
                                        <span class="font-semibold text-slate-500 dark:text-slate-400 truncate">
                                            @if ($node['time'])
                                                {{ __('Pukul') }} {{ $node['time'] }}
                                            @else
                                                {{ $node['badge'] }}
                                            @endif
                                        </span>
                                        <span
                                            class="shrink-0 inline-flex items-center gap-1 font-bold transition-colors"
                                            :class="pinnedIdx === {{ $nIdx }} ? 'text-emerald-600 dark:text-emerald-400' : 'text-[#F47920]'"
                                        >
                                            <template x-if="pinnedIdx === {{ $nIdx }}">
                                                <span class="inline-flex items-center gap-0.5">
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.75"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                    {{ __('Terpilih') }}
                                                </span>
                                            </template>
                                            <template x-if="pinnedIdx !== {{ $nIdx }}">
                                                <span>{{ __('Klik untuk pilih') }}</span>
                                            </template>
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Interactive Hover / Active Detail Spotlight Bar (Morphs smoothly on hover or click) -->
                    <template x-if="activeNode">
                        <div
                            :key="activeIdx"
                            :class="spotlightPulse ? 'ring-2 ring-[#F47920]/60 border-[#F47920]/60 bg-[#F47920]/[0.06] dark:bg-[#F47920]/10' : 'border-slate-200/80 dark:border-white/10 bg-white/75 dark:bg-white/[0.04]'"
                            class="journey-spotlight-panel mt-3 p-4 sm:p-5 rounded-2xl border shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition-all duration-300"
                        >
                            <div class="min-w-0 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                        :class="{
                                            'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300': activeNode.color === 'emerald',
                                            'bg-indigo-500/15 text-indigo-700 dark:text-indigo-300': activeNode.color === 'indigo',
                                            'bg-sky-500/15 text-sky-700 dark:text-sky-300': activeNode.color === 'sky',
                                            'bg-purple-500/15 text-purple-700 dark:text-purple-300': activeNode.color === 'purple',
                                            'bg-rose-500/15 text-rose-700 dark:text-rose-300': activeNode.color === 'rose',
                                            'bg-amber-500/15 text-amber-700 dark:text-amber-300': activeNode.color === 'amber',
                                            'bg-teal-500/15 text-teal-700 dark:text-teal-300': activeNode.color === 'teal',
                                            'bg-cyan-500/15 text-cyan-700 dark:text-cyan-300': activeNode.color === 'cyan',
                                            'bg-slate-200/80 text-slate-600 dark:bg-white/10 dark:text-slate-300': activeNode.color === 'slate',
                                            'bg-[#F47920]/15 text-[#F47920]': activeNode.color === 'orange',
                                        }"
                                        x-text="'Tahap #' + activeNode.index + ' • ' + activeNode.badge"
                                    ></span>
                                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 tabular-nums" x-text="activeNode.date + (activeNode.time ? ' • ' + activeNode.time : '')"></span>
                                </div>
                                <h4 class="text-sm sm:text-base font-black text-slate-800 dark:text-white leading-snug" x-text="activeNode.full_title"></h4>
                                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed" x-text="activeNode.desc"></p>
                            </div>

                            <!-- Quick Stepper Controls -->
                            <div class="flex items-center justify-between sm:justify-end gap-2 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-200/60 dark:border-white/10">
                                <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 sm:hidden">
                                    {{ __('Geser kartu atau gunakan panah') }}
                                </span>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="stepSpotlight(-1)"
                                        :disabled="activeIdx <= 0"
                                        aria-label="{{ __('Tahap sebelumnya') }}"
                                        class="w-9 h-9 rounded-xl inline-flex items-center justify-center border border-[#F47920]/45 bg-[#F47920]/15 text-[#F47920] shadow-xs transition-all duration-200 hover:bg-[#F47920] hover:text-white hover:border-[#F47920] hover:shadow-md hover:shadow-[#F47920]/30 hover:scale-105 active:scale-95 disabled:bg-slate-100 dark:disabled:bg-white/5 disabled:text-slate-400 dark:disabled:text-slate-500 disabled:border-slate-200/70 dark:disabled:border-white/10 disabled:shadow-none disabled:hover:scale-100 disabled:opacity-45 disabled:cursor-not-allowed cursor-pointer"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>
                                    <span class="text-xs font-extrabold text-slate-600 dark:text-slate-300 tabular-nums min-w-[44px] text-center" x-text="(activeIdx + 1) + ' / ' + nodes.length"></span>
                                    <button
                                        type="button"
                                        @click="stepSpotlight(1)"
                                        :disabled="activeIdx >= nodes.length - 1"
                                        aria-label="{{ __('Tahap berikutnya') }}"
                                        class="w-9 h-9 rounded-xl inline-flex items-center justify-center border border-[#F47920]/45 bg-[#F47920]/15 text-[#F47920] shadow-xs transition-all duration-200 hover:bg-[#F47920] hover:text-white hover:border-[#F47920] hover:shadow-md hover:shadow-[#F47920]/30 hover:scale-105 active:scale-95 disabled:bg-slate-100 dark:disabled:bg-white/5 disabled:text-slate-400 dark:disabled:text-slate-500 disabled:border-slate-200/70 dark:disabled:border-white/10 disabled:shadow-none disabled:hover:scale-100 disabled:opacity-45 disabled:cursor-not-allowed cursor-pointer"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- BANNER PERINGATAN RDTV JIKA ADA DOKUMEN RETUR -->
                @if ($trackingData['has_rdtv'] ?? false)
                    <div class="p-4 sm:p-6 lg:p-8 rounded-3xl bg-rose-50/90 dark:bg-[#190d14]/95 border border-rose-200 dark:border-rose-500/30 text-rose-900 dark:text-rose-200 shadow-lg animate-fade-in relative overflow-hidden space-y-4 sm:space-y-5">
                        <!-- Top Header Row: Icon + Title + Status Pill -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-start sm:items-center gap-3.5">
                                <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-rose-500/15 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 border border-rose-500/25">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-rose-600 text-white inline-block mb-1.5 sm:hidden">
                                        {{ __('Ditolak / Retur Material') }}
                                    </span>
                                    <h4 class="font-black text-base sm:text-lg text-rose-800 dark:text-rose-200 leading-snug">
                                        {{ __('Pemberitahuan Retur Material (RDTV - Return Delivery to Vendor)') }}
                                    </h4>
                                </div>
                            </div>
                            <span class="hidden sm:inline-flex px-3.5 py-1 rounded-full text-xs font-black bg-rose-600 text-white shrink-0">
                                {{ __('Ditolak / Retur Material') }}
                            </span>
                        </div>

                        <!-- Full-Width Body Content (No Left Indent on Mobile or Desktop) -->
                        <div class="text-xs sm:text-sm space-y-3.5">
                            <p class="leading-relaxed text-slate-700 dark:text-slate-300">
                                {{ __('Terdapat surat jalan atau item material pada PO ini yang telah diputuskan ditolak / diretur oleh tim Quality Control (QC) dan Gudang Receiving Pupuk Kaltim. Dokumen slip penolakan resmi (RDTV) telah diterbitkan.') }}
                            </p>

                            @if (!empty($trackingData['rdtv_reasons']))
                                <div class="p-3.5 sm:p-4 rounded-2xl bg-white/90 dark:bg-[#0b1626]/90 border border-rose-200/80 dark:border-rose-500/20 text-xs space-y-2 shadow-2xs">
                                    <span class="font-bold text-rose-700 dark:text-rose-300 block">{{ __('Rincian Surat Jalan & Alasan Penolakan:') }}</span>
                                    <div class="space-y-1.5">
                                        @foreach ($trackingData['rdtv_reasons'] as $rItem)
                                            <div class="flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-2 text-slate-800 dark:text-slate-200">
                                                <span class="font-mono font-bold text-rose-600 dark:text-rose-400 shrink-0">[{{ $rItem['do_no'] }}]</span>
                                                <span class="font-medium leading-relaxed">{{ $rItem['reason'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="p-3 sm:p-3.5 rounded-xl bg-rose-500/10 dark:bg-rose-500/10 border border-rose-500/20 text-xs text-slate-700 dark:text-slate-300 flex items-start sm:items-center gap-2.5">
                                <svg class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5 sm:mt-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="leading-relaxed">{{ __('Silakan unduh dokumen slip RDTV di bagian bawah untuk arsip dan segera hubungi Tim Pengadaan / Receiving PKT untuk koordinasi penggantian atau pengiriman ulang material.') }}</span>
                            </div>

                            <!-- Form Pengajuan Ulang / Unggah Softfile Sertifikat Pemenuhan -->
                            <div class="mt-4 p-4 sm:p-6 rounded-2xl bg-white dark:bg-[#0b1626] border border-slate-200/90 dark:border-white/10 shadow-md dark:shadow-xl">
                                <!-- Card Header -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-4 border-b border-slate-100 dark:border-white/10">
                                    <div class="flex items-start sm:items-center gap-3">
                                        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-[#F47920]/10 dark:bg-[#F47920]/15 text-[#F47920] flex items-center justify-center shrink-0 border border-[#F47920]/25">
                                            <svg class="w-5 h-5 text-[#F47920]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-[#F47920]/10 text-[#F47920] border border-[#F47920]/25 inline-block mb-1 sm:hidden">
                                                {{ __('Susulan Dokumen') }}
                                            </span>
                                            <h5 class="font-extrabold text-sm sm:text-base text-slate-900 dark:text-white tracking-tight leading-snug">
                                                {{ $this->getRdtvUploadTitle() }}
                                            </h5>
                                            <span class="text-xs text-slate-500 dark:text-slate-400 block mt-0.5 leading-normal">
                                                {{ __('Pemenuhan persyaratan administrasi mutu QC tanpa perlu retur fisik.') }}
                                            </span>
                                        </div>
                                    </div>
                                    <span class="hidden sm:inline-flex px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-[#F47920]/10 text-[#F47920] border border-[#F47920]/25 shrink-0">
                                        {{ __('Susulan Dokumen') }}
                                    </span>
                                </div>

                                @php
                                    $detectedDoc = $this->getDetectedRdtvDocumentName();
                                @endphp

                                @if ($detectedDoc)
                                    <div class="p-3.5 mb-4 rounded-xl bg-amber-50/90 dark:bg-amber-500/10 border border-amber-200/90 dark:border-amber-500/25 text-slate-700 dark:text-slate-200 text-xs flex items-start gap-2.5">
                                        <svg class="w-4 h-4 text-[#F47920] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <div class="leading-relaxed">
                                            <span>{{ __('Berdasarkan catatan penolakan RDTV, Anda dapat mengunggah softfile') }}</span>
                                            <strong class="font-bold text-slate-900 dark:text-amber-300 underline decoration-[#F47920] decoration-2 underline-offset-2">{{ $detectedDoc }}</strong>
                                            <span>{{ __('agar tim QC dapat memverifikasi dan melanjutkan penerbitan GRS tanpa retur fisik barang.') }}</span>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-xs text-slate-600 dark:text-slate-300 mb-4 leading-relaxed">
                                        {{ __('Jika penolakan RDTV disebabkan kekurangan dokumen teknis (seperti belum melampirkan COO manufacture, COA, atau sertifikat uji mutu), Anda dapat mengunggah berkas sertifikat di bawah ini agar tim QC dapat memverifikasi dan mengajukan ulang tanpa retur fisik barang.') }}
                                    </p>
                                @endif

                                @if (session()->has('rdtv_success'))
                                    <div class="p-3.5 mb-4 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between shadow-xs">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            <span>{{ session('rdtv_success') }}</span>
                                        </div>
                                        <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 text-base">&times;</button>
                                    </div>
                                @endif

                                @if (session()->has('rdtv_error'))
                                    <div class="p-3.5 mb-4 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between shadow-xs">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span>{{ session('rdtv_error') }}</span>
                                        </div>
                                        <button type="button" @click="$el.parentElement.remove()" class="text-rose-600 hover:text-rose-800 text-base">&times;</button>
                                    </div>
                                @endif

                                <form wire:submit.prevent="submitRdtvCompliance" class="space-y-4">
                                    @php
                                        $rdtvDos = isset($trackingData['unique_dos'])
                                            ? $trackingData['unique_dos']->filter(fn ($d) => $d->status === 'RDTV' || $d->grsRdtvItems->where('grsRdtv.category', 'RDTV')->isNotEmpty())
                                            : collect();
                                    @endphp

                                    @if ($rdtvDos->count() > 1)
                                        <div>
                                            <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1.5 flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-[#F47920] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                                {{ __('Pilih Surat Jalan (DO) yang Ditolak:') }}
                                            </label>
                                            <div class="relative">
                                                <select wire:model.live="selectedRdtvDoId" class="w-full min-h-[44px] text-xs font-semibold rounded-xl border border-slate-300 dark:border-slate-700/90 bg-slate-50/90 dark:bg-[#07111e] text-slate-900 dark:text-white px-3.5 py-2.5 focus:bg-white dark:focus:bg-[#0d1b2e] focus:border-[#F47920] focus:ring-2 focus:ring-[#F47920]/20 transition shadow-2xs">
                                                    @foreach ($rdtvDos as $rDo)
                                                        <option value="{{ $rDo->id }}">{{ $rDo->delivery_order_no }} ({{ $rDo->document_code }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('selectedRdtvDoId') <span class="text-rose-600 dark:text-rose-400 text-[11px] font-semibold block mt-1">{{ $message }}</span> @enderror
                                        </div>
                                    @endif

                                    <!-- Interactive Drag & Drop Upload Zone -->
                                    <div x-data="{ isDragging: false }" class="space-y-2">
                                        <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-[#F47920] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                            </svg>
                                            <span>
                                                @if ($detectedDoc)
                                                    {{ __('Berkas :doc (PDF / JPG / PNG, maks. 20 MB):', ['doc' => $detectedDoc]) }}
                                                @else
                                                    {{ __('Berkas Sertifikat Susulan (PDF / JPG / PNG, maks. 20 MB):') }}
                                                @endif
                                            </span>
                                        </label>

                                        @if (!$rdtvComplianceFile)
                                            <div 
                                                @dragover.prevent="isDragging = true"
                                                @dragleave.prevent="isDragging = false"
                                                @drop.prevent="isDragging = false"
                                                :class="isDragging ? 'border-[#F47920] bg-orange-50/90 dark:bg-[#13233a] ring-4 ring-[#F47920]/20 scale-[1.005]' : ''"
                                                class="relative border-2 border-dashed border-slate-300 dark:border-slate-700/90 rounded-2xl p-5 sm:p-7 text-center bg-slate-50/80 dark:bg-[#07111e] hover:border-[#F47920] dark:hover:border-[#F47920] hover:bg-orange-50/40 dark:hover:bg-[#0d1b2e] transition-all duration-200 group cursor-pointer">
                                                
                                                <input type="file" wire:model="rdtvComplianceFile" id="rdtvFileInput" accept=".pdf,.jpg,.jpeg,.png"
                                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />

                                                <!-- Loading indicator while transferring file -->
                                                <div wire:loading wire:target="rdtvComplianceFile" class="py-3 space-y-3">
                                                    <div class="w-12 h-12 mx-auto rounded-2xl bg-[#F47920]/15 text-[#F47920] flex items-center justify-center">
                                                        <svg class="w-6 h-6 animate-spin text-[#F47920]" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                        </svg>
                                                    </div>
                                                    <p class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">{{ __('Mengunggah berkas...') }}</p>
                                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('Mohon tunggu hingga berkas selesai diproses.') }}</p>
                                                </div>

                                                <!-- Default Dropzone Content -->
                                                <div wire:loading.remove wire:target="rdtvComplianceFile" class="space-y-3 pointer-events-none">
                                                    <div class="w-12 h-12 sm:w-14 sm:h-14 mx-auto rounded-2xl bg-white dark:bg-[#112238] text-[#F47920] flex items-center justify-center border border-slate-200 dark:border-white/10 shadow-sm group-hover:scale-105 group-hover:border-[#F47920]/40 transition-all duration-200">
                                                        <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                        </svg>
                                                    </div>
                                                    <div class="space-y-1">
                                                        <h6 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">
                                                            {{ __('Pilih atau Tarik Berkas ke Sini') }}
                                                        </h6>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                                            {{ __('Format yang didukung: PDF, JPG, JPEG, PNG (Maksimal 20 MB)') }}
                                                        </p>
                                                    </div>
                                                    <div class="pt-1">
                                                        <span class="inline-flex items-center justify-center gap-2 min-h-[40px] px-5 py-2 rounded-xl text-xs sm:text-sm font-bold text-white bg-[#F47920] group-hover:bg-[#e06b18] shadow-sm shadow-[#F47920]/25 transition">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                                            {{ __('Pilih Berkas') }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <!-- File Selected Preview Card -->
                                            <div class="p-3.5 sm:p-4 rounded-2xl bg-emerald-50/90 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-500/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-500/25">
                                                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                                        </svg>
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span class="font-mono font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate max-w-[220px] sm:max-w-xs md:max-w-md block">
                                                                {{ $rdtvComplianceFile->getClientOriginalName() }}
                                                            </span>
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 shrink-0">
                                                                {{ __('Siap Diunggah') }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center gap-2 text-[11px] text-slate-600 dark:text-slate-400 mt-0.5">
                                                            <span>{{ strtoupper($rdtvComplianceFile->getClientOriginalExtension()) }}</span>
                                                            <span>&bull;</span>
                                                            <span>{{ number_format($rdtvComplianceFile->getSize() / 1024, 1) }} KB</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="button" wire:click="$set('rdtvComplianceFile', null)" class="w-full sm:w-auto min-h-[38px] px-3.5 py-1.5 rounded-xl text-xs font-bold text-rose-600 dark:text-rose-300 bg-white dark:bg-[#112238] hover:bg-rose-50 dark:hover:bg-rose-950/60 border border-rose-200 dark:border-rose-500/30 transition shrink-0 flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    {{ __('Ganti Berkas') }}
                                                </button>
                                            </div>
                                        @endif

                                        @error('rdtvComplianceFile')
                                            <span class="text-rose-600 dark:text-rose-400 text-[11px] font-semibold block mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1.5 flex items-center justify-between">
                                            <span class="flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                {{ __('Catatan Tambahan Vendor:') }}
                                            </span>
                                            <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500">{{ __('Opsional') }}</span>
                                        </label>
                                        <textarea wire:model="rdtvComplianceNotes" rows="2" placeholder="{{ __('Tuliskan keterangan dokumen untuk tim QC (opsional)...') }}"
                                            class="w-full min-h-[72px] text-xs rounded-xl border border-slate-300 dark:border-slate-700/90 bg-slate-50/90 dark:bg-[#07111e] text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3.5 py-2.5 focus:bg-white dark:focus:bg-[#0d1b2e] focus:border-[#F47920] focus:ring-2 focus:ring-[#F47920]/20 transition shadow-2xs resize-none"></textarea>
                                        @error('rdtvComplianceNotes') <span class="text-rose-600 dark:text-rose-400 text-[11px] font-semibold block mt-1">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="flex items-center justify-end pt-1">
                                        <button type="submit" wire:loading.attr="disabled"
                                            class="min-h-[46px] w-full sm:w-auto px-6 py-3 rounded-xl bg-gradient-to-r from-[#F47920] to-[#d95f16] hover:from-[#e06b18] hover:to-[#c25210] text-white text-xs sm:text-sm font-bold flex items-center justify-center gap-2 cursor-pointer transition-all duration-200 shadow-md shadow-[#F47920]/25 active:scale-[0.98] disabled:opacity-50">
                                            <svg wire:loading wire:target="submitRdtvCompliance" class="animate-spin h-4 w-4 text-white shrink-0" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                            </svg>
                                            <svg wire:loading.remove wire:target="submitRdtvCompliance" class="w-4 h-4 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span wire:loading.remove wire:target="submitRdtvCompliance">
                                                {{ __('Kirim Dokumen Pemenuhan') }}
                                            </span>
                                            <span wire:loading wire:target="submitRdtvCompliance">
                                                {{ __('Mengirim Berkas...') }}
                                            </span>
                                        </button>
                                    </div>
                                </form>

                                <!-- Daftar Riwayat Dokumen Pemenuhan yang Pernah Diunggah -->
                                @if (!empty($trackingData['rdtv_compliances']) && $trackingData['rdtv_compliances']->isNotEmpty())
                                    <div class="mt-5 pt-4 border-t border-slate-200 dark:border-white/10 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-[#F47920]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ __('Riwayat Dokumen Pemenuhan yang Diajukan:') }}
                                            </span>
                                            <span class="text-[10px] font-mono font-bold px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-[#07111e] text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-white/10">
                                                {{ $trackingData['rdtv_compliances']->count() }} {{ __('Berkas') }}
                                            </span>
                                        </div>
                                        <div class="space-y-2.5">
                                            @foreach ($trackingData['rdtv_compliances'] as $comp)
                                                <div class="p-3.5 sm:p-4 rounded-xl bg-slate-50/80 dark:bg-[#07111e] border {{ $comp->status === 'Disetujui' ? 'border-emerald-500/30 ring-1 ring-emerald-500/10' : ($comp->status === 'Ditolak' ? 'border-rose-500/30 ring-1 ring-rose-500/10' : 'border-amber-500/30 ring-1 ring-amber-500/10') }} text-xs space-y-2.5 shadow-2xs">
                                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                        <div class="flex items-center gap-2.5 min-w-0">
                                                            <div class="w-7 h-7 rounded-lg {{ $comp->status === 'Disetujui' ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : ($comp->status === 'Ditolak' ? 'bg-rose-500/15 text-rose-600 dark:text-rose-400' : 'bg-amber-500/15 text-amber-600 dark:text-amber-400') }} flex items-center justify-center shrink-0">
                                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                </svg>
                                                            </div>
                                                            <div class="min-w-0">
                                                                <span class="font-mono font-bold text-slate-900 dark:text-white truncate max-w-[210px] sm:max-w-xs md:max-w-md block">
                                                                    {{ $comp->original_filename }}
                                                                </span>
                                                                <span class="text-[10px] text-slate-500 dark:text-slate-400">
                                                                    {{ __('No. Surat Jalan:') }} {{ $comp->deliveryOrderReceipt?->delivery_order_no ?? '-' }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <span class="self-start sm:self-auto px-2.5 py-0.5 rounded-full text-[10px] font-bold shrink-0
                                                            {{ $comp->status === 'Disetujui' ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30' : ($comp->status === 'Ditolak' ? 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/30' : 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30') }}">
                                                            {{ __($comp->status) }}
                                                        </span>
                                                    </div>
                                                    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-200/70 dark:border-white/10">
                                                        <span>{{ __('Diunggah:') }} {{ $comp->created_at->format('d M Y H:i') }}</span>
                                                        <button type="button" wire:click="downloadRdtvCompliance({{ $comp->id }})"
                                                            class="text-[#F47920] hover:text-[#d36415] hover:underline font-semibold flex items-center gap-1 cursor-pointer">
                                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                            {{ __('Unduh Berkas') }}
                                                        </button>
                                                    </div>
                                                    @if ($comp->vendor_notes)
                                                        <p class="text-[11px] text-slate-600 dark:text-slate-300 italic bg-white dark:bg-[#0b1626] p-2.5 rounded-lg border border-slate-200/60 dark:border-white/10">
                                                            "{{ $comp->vendor_notes }}"
                                                        </p>
                                                    @endif
                                                    @if ($comp->status === 'Ditolak' && !empty($comp->review_notes))
                                                        <div class="p-2.5 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-300 text-[11px]">
                                                            <strong>{{ __('Catatan Tim QC / Receiving:') }}</strong> {{ $comp->review_notes }}
                                                        </div>
                                                    @elseif ($comp->status === 'Disetujui')
                                                        <div class="p-2.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-300 text-[11px]">
                                                            {{ __('Dokumen pemenuhan ini telah disetujui. Petugas Gudang Receiving sedang memproses penerbitan GRS resmi.') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

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
                            <span class="text-xs font-bold uppercase tracking-wider text-[#F47920] block">{{ __('Unduh Berkas Resmi') }}</span>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight">
                                {{ __('Dokumen Resmi dari Receiving & Tim Penerimaan Invoice') }}
                            </h3>
                        </div>
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            {{ __('Dokumen Resmi Terverifikasi Sistem') }}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- CARD 1: BERKAS GRS (RECEIVING GUDANG) -->
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
                                            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400 block">{{ __('Modul Receiving (Gudang)') }}</span>
                                            <h4 class="text-base font-black text-slate-800 dark:text-white">
                                                {{ __('Dokumen GRS / RDTV (Slip Penerimaan)') }}
                                            </h4>
                                        </div>
                                    </div>
                                </div>

                                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
                                    {{ __('Slip bukti penerimaan (GRS) atau retur material (RDTV) di gudang Receiving yang telah diposting ke sistem penerimaan (MIGO 105). Diunggah oleh petugas Receiving untuk keperluan arsip vendor.') }}
                                </p>

                                <!-- List of GRS Items dari tabel GRS/RDTV -->
                                @php
                                    $grsItems = $trackingData['grs_items'] ?? collect();
                                    $invReceipt = $trackingData['invoicing_receipt'] ?? null;
                                    if ($grsItems->isEmpty() && $invReceipt?->grsRdtvItem) {
                                        $grsItems = collect([$invReceipt->grsRdtvItem]);
                                    }
                                    $grsWithFiles = $grsItems->filter(fn ($item) => !empty($item->file_path));
                                @endphp

                                @if ($grsWithFiles->isEmpty())
                                    <div class="p-3.5 rounded-xl bg-slate-100/70 dark:bg-white/5 text-slate-500 dark:text-slate-400 text-xs flex items-center gap-2">
                                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>{{ __('Belum ada dokumen GRS/RDTV yang diunggah untuk PO ini.') }}</span>
                                    </div>
                                @else
                                    <div class="space-y-2.5 mb-4">
                                        @foreach ($grsItems as $gItem)
                                            @php
                                                $itemCat = $gItem->grsRdtv?->category ?? 'GRS';
                                                $isItemRdtv = $itemCat === 'RDTV';
                                            @endphp
                                            <div class="p-3 rounded-xl bg-white/70 dark:bg-slate-900/60 border border-slate-200/60 dark:border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                                                <div class="text-xs">
                                                    <div class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                                        <span>{{ $gItem->document_code ?? ($isItemRdtv ? 'RDTV Document' : 'GRS Document') }}</span>
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $isItemRdtv ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' }}">
                                                            {{ $itemCat }}
                                                        </span>
                                                    </div>
                                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                                        {{ __('DO:') }} {{ $gItem->deliveryOrderReceipt?->delivery_order_no ?? '-' }} • {{ __('Tgl:') }} {{ $gItem->grsRdtv?->transaction_date ? \Carbon\Carbon::parse($gItem->grsRdtv->transaction_date)->locale(app()->getLocale())->translatedFormat('d M Y') : '-' }}
                                                    </div>
                                                </div>

                                                @if (!empty($gItem->file_path))
                                                    <div class="flex items-center gap-2 shrink-0">
                                                        <a href="{{ $gItem->document_url }}" target="_blank"
                                                            class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-white/10 transition-colors flex items-center gap-1.5">
                                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                            {{ __('Lihat') }}
                                                        </a>
                                                        <button type="button" wire:click="downloadGrsReceiving({{ $gItem->id }})"
                                                            class="px-3.5 py-1.5 rounded-lg text-xs font-bold text-white {{ $isItemRdtv ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700' }} shadow-sm transition-all flex items-center gap-1.5">
                                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                            {{ __('Unduh') }}
                                                        </button>
                                                    </div>
                                                @else
                                                    <span class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                                        {{ __('Scan Belum Diunggah') }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="pt-3 border-t border-slate-200/50 dark:border-white/5 flex items-center justify-between text-[11px] text-slate-400">
                                <span>{{ __('Sumber: Master Receiving Gudang (GRS/RDTV)') }}</span>
                                <span>{{ $grsWithFiles->count() }} {{ __('Berkas Siap Diunduh') }}</span>
                            </div>
                        </div>

                        <!-- CARD 2: SURAT VERIFIKASI GRS (INVOICE) -->
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
                                            <span class="text-[10px] font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400 block">{{ __('Modul Invoicing (Verifikasi)') }}</span>
                                            <h4 class="text-base font-black text-slate-800 dark:text-white">
                                                {{ __('Dokumen Verifikasi GRS') }}
                                            </h4>
                                        </div>
                                    </div>
                                    @php
                                        $invReceipt = $trackingData['invoicing_receipt'] ?? null;
                                        $grsPath = $invReceipt?->grsRdtvItem?->file_path;
                                        $hasVerifikasi = !empty($invReceipt?->dokumen_verifikasi_grs)
                                            && ($grsPath ? $invReceipt->dokumen_verifikasi_grs !== $grsPath : true);
                                    @endphp
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold
                                        @if ($hasVerifikasi)
                                            bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20
                                        @else
                                            bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20
                                        @endif">
                                        {{ $hasVerifikasi ? __('Terverifikasi') : ($invReceipt?->status ?? __('Menunggu Verifikasi')) }}
                                    </span>
                                </div>

                                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
                                    {{ __('Surat Verifikasi Penerimaan Barang resmi yang telah disahkan oleh Vice President Planning, Receiving & Warehousing PT Pupuk Kalimantan Timur.') }}
                                </p>

                                @if ($hasVerifikasi)
                                    <div class="p-3.5 rounded-xl bg-white/70 dark:bg-slate-900/60 border border-slate-200/60 dark:border-white/10 mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                        <div class="text-xs">
                                            <div class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                                <span>{{ __('Surat Verifikasi Penerimaan Barang') }}</span>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600">{{ __('Terverifikasi') }}</span>
                                            </div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">
                                                PO: {{ $invReceipt->no_po }} • Status: {{ $invReceipt->status }}
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 shrink-0">
                                            <a href="{{ Storage::disk('public')->url($invReceipt->dokumen_verifikasi_grs) }}" target="_blank"
                                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-white/10 transition-colors flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                {{ __('Lihat') }}
                                            </a>
                                            <button type="button" wire:click="downloadVerifikasiGrs({{ $invReceipt->id }})"
                                                class="px-3.5 py-1.5 rounded-lg text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-all flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                {{ __('Unduh') }}
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="p-3.5 rounded-xl bg-slate-100/70 dark:bg-white/5 text-slate-500 dark:text-slate-400 text-xs mb-4 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <span>{{ __('Surat verifikasi belum diunggah oleh Tim Penerimaan Invoice. Berkas akan tersedia setelah proses verifikasi penerimaan barang disahkan.') }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="pt-3 border-t border-slate-200/50 dark:border-white/5 flex items-center justify-between text-[11px] text-slate-400">
                                <span>{{ __('Persetujuan: VP Planning, Receiving & Warehousing PKT') }}</span>
                                <span>{{ $hasVerifikasi ? __('Berkas Tersedia') : __('Dalam Proses') }}</span>
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
                                {{ __('Berkas Penagihan Invoice') }}
                            </div>
                            <h3 class="text-xl sm:text-2xl font-black text-slate-800 dark:text-white tracking-tight">
                                {{ __('Upload Dokumen Persyaratan Penagihan') }}
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 mt-1 max-w-2xl leading-relaxed">
                                {!! __('Seluruh dokumen persyaratan penagihan invoice wajib digabungkan menjadi <strong>1 file dokumen (PDF/ZIP)</strong> lalu diunggah di bawah ini. Dokumen akan langsung tersinkronisasi dan dapat diverifikasi oleh Tim Penerimaan Invoice PT Pupuk Kalimantan Timur.') !!}
                            </p>
                        </div>

                        <!-- Status Badge -->
                        @php
                            $isRdtvSuspended = !empty($trackingData['active_do_is_rdtv']) || (!empty($trackingData['is_rdtv_only']) && empty($trackingData['has_dossier']));
                        @endphp
                        <div class="shrink-0">
                            @if ($isRdtvSuspended)
                                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-xs font-bold bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    {{ __('Penagihan Ditangguhkan (RDTV)') }}
                                </span>
                            @elseif (!empty($trackingData['has_dossier']))
                                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-xs font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    {{ __('Berkas Lengkap Terunggah') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-xs font-bold bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    {{ __('Menunggu Unggah Berkas') }}
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
                                                <span class="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">{{ __('Berkas Penagihan Resmi') }}</span>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">{{ __('Aktif') }}</span>
                                            </div>
                                            <h4 class="text-base font-black text-slate-800 dark:text-white font-mono break-all">
                                                {{ $dossier['original_name'] ?? 'Berkas_Penagihan_Invoice.pdf' }}
                                            </h4>
                                            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap items-center gap-3">
                                                <span>{{ __('Diunggah:') }} {{ isset($dossier['uploaded_at']) ? \Carbon\Carbon::parse($dossier['uploaded_at'])->locale(app()->getLocale())->translatedFormat('d M Y, H:i') : '-' }}</span>
                                                @if (!empty($dossier['file_size']))
                                                    <span>•</span>
                                                    <span>{{ __('Ukuran:') }} {{ number_format($dossier['file_size'] / (1024 * 1024), 2) }} MB</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex flex-wrap items-center gap-2 shrink-0 pt-2 sm:pt-0">
                                        <a href="{{ Storage::disk('public')->url($dossier['file_path']) }}" target="_blank"
                                            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-white/80 dark:bg-white/10 hover:bg-slate-100 dark:hover:bg-white/20 transition-all flex items-center gap-1.5 shadow-sm">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            {{ __('Lihat Berkas') }}
                                        </a>
                                        <button type="button" wire:click="downloadBerkasPenagihan"
                                            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition-all flex items-center gap-1.5">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            {{ __('Unduh Berkas') }}
                                        </button>
                                        <button type="button" wire:click="deleteBerkasPenagihan"
                                            wire:confirm="{{ __('Yakin ingin menghapus berkas penagihan ini? Anda dapat mengunggah file baru setelahnya.') }}"
                                            wire:loading.attr="disabled" wire:target="deleteBerkasPenagihan"
                                            class="px-3.5 py-2 rounded-xl text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-500/10 transition-colors flex items-center gap-1 disabled:opacity-60 disabled:cursor-wait">
                                            <svg wire:loading.remove wire:target="deleteBerkasPenagihan" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            <svg wire:loading wire:target="deleteBerkasPenagihan" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <span>{{ __('Hapus / Ganti') }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @elseif ($isRdtvSuspended)
                            <!-- RDTV SUSPENDED NOTICE -->
                            <div class="p-8 sm:p-10 rounded-3xl bg-rose-500/5 border-2 border-dashed border-rose-500/30 text-center relative">
                                <div class="max-w-md mx-auto">
                                    <div class="w-16 h-16 rounded-3xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto mb-4 border border-rose-500/20 shadow-sm">
                                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                    </div>
                                    <h4 class="text-base font-black text-rose-700 dark:text-rose-300 mb-2">
                                        {{ __('Pengajuan Penagihan Tidak Tersedia untuk Dokumen RDTV') }}
                                    </h4>
                                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
                                        {{ __('Surat jalan ini memiliki status penolakan / retur material (RDTV). Sesuai ketentuan penerimaan barang dan penagihan invoice PT Pupuk Kaltim, penagihan invoice hanya dapat diproses untuk material yang telah terbit lembar GRS resmi (diterima gudang).') }}
                                    </p>
                                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-rose-500/10 text-rose-700 dark:text-rose-300 text-xs font-semibold">
                                        <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>{{ __('Silakan hubungi Tim Pengadaan PKT untuk tindak lanjut penggantian barang.') }}</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- UPLOAD DROPZONE -->
                            <div class="p-6 sm:p-10 rounded-3xl bg-slate-50/90 dark:bg-[#07111e] border-2 border-dashed border-slate-300 dark:border-slate-700/90 hover:border-[#F47920] dark:hover:border-[#F47920] hover:bg-orange-50/30 dark:hover:bg-[#0d1b2e] transition-all text-center relative">
                                <div class="max-w-md mx-auto">
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-white dark:bg-[#112238] text-[#F47920] flex items-center justify-center mx-auto mb-4 border border-slate-200 dark:border-white/10 shadow-sm">
                                        <svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                    </div>

                                    @if ($berkasPenagihanFile)
                                        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 mb-4 shadow-sm">
                                            <div class="flex items-center justify-between gap-3 text-left">
                                                <div class="min-w-0">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#F47920] block">{{ __('Berkas Terpilih') }}</span>
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

                                        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                                            <button type="button" wire:click="uploadBerkasPenagihan"
                                                wire:loading.attr="disabled"
                                                class="w-full sm:w-auto px-6 py-3 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-[#F47920] to-[#BE5A27] hover:shadow-[0_8px_20px_rgba(244,121,32,0.4)] active:scale-95 transition-all flex items-center justify-center gap-2 cursor-pointer shadow-md">
                                                <span wire:loading.remove wire:target="uploadBerkasPenagihan">{{ __('Unggah Berkas Sekarang') }}</span>
                                                <span wire:loading wire:target="uploadBerkasPenagihan" class="flex items-center gap-2">
                                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                    </svg>
                                                    {{ __('Mengunggah Berkas...') }}
                                                </span>
                                            </button>
                                            <button type="button" wire:click="$set('berkasPenagihanFile', null)"
                                                class="w-full sm:w-auto px-5 py-3 rounded-xl font-bold text-xs text-slate-500 hover:text-slate-800 dark:hover:text-white bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 transition cursor-pointer">
                                                {{ __('Batal') }}
                                            </button>
                                        </div>
                                    @else
                                        <h4 class="text-base font-bold text-slate-800 dark:text-white mb-1">
                                            {{ __('Pilih atau Tarik Berkas Penagihan ke Sini') }}
                                        </h4>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-5">
                                            {!! __('Format: <strong>PDF (Sangat Disarankan)</strong>, ZIP, RAR, JPG, PNG (Maksimal 25 MB).') !!}
                                        </p>

                                        <label class="cursor-pointer inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-bold text-xs text-white bg-gradient-to-r from-[#F47920] to-[#BE5A27] hover:shadow-[0_8px_20px_rgba(244,121,32,0.4)] hover:-translate-y-0.5 active:translate-y-0 transition-all shadow-md w-full sm:w-auto">
                                            <input type="file" wire:model="berkasPenagihanFile" class="hidden" accept=".pdf,.zip,.rar,.jpg,.jpeg,.png">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                            </svg>
                                            {{ __('Pilih Berkas Penagihan') }}
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
                    <div class="pt-6 border-t border-slate-200/70 dark:border-white/10" x-data="{ accordionOpen: true }">
                        <div @click="accordionOpen = !accordionOpen" 
                             class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 cursor-pointer select-none group/acc">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-[#F47920]/10 text-[#F47920] flex items-center justify-center shrink-0 border border-[#F47920]/20 shadow-xs group-hover/acc:scale-105 transition-transform duration-200 ease-[cubic-bezier(0.34,1.4,0.64,1)]">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-slate-200/60 dark:bg-white/10 text-slate-700 dark:text-slate-300 text-[10px] font-bold uppercase tracking-wider mb-0.5">
                                        {{ __('Pedoman Kelengkapan') }}
                                    </div>
                                    <h4 class="text-base sm:text-lg font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
                                        <span>{{ __('16 Persyaratan Kelengkapan Dokumen Penagihan') }}</span>
                                    </h4>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 self-end sm:self-center">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-black/[0.04] dark:bg-white/[0.06] text-slate-600 dark:text-slate-300">
                                    {{ __('Total 16 Dokumen') }}
                                </span>
                                <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-white/10 flex items-center justify-center text-slate-500 group-hover/acc:text-[#F47920] transition-colors">
                                    <svg class="w-4 h-4 transition-transform duration-300 ease-[cubic-bezier(0.34,1.4,0.64,1)]"
                                         :class="accordionOpen ? 'rotate-180' : 'rotate-0'"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Smooth Accordion Content using CSS Grid -->
                        <div class="grid transition-all duration-350 ease-[cubic-bezier(0.4,0,0.2,1)]"
                             :style="accordionOpen ? 'grid-template-rows: 1fr; opacity: 1;' : 'grid-template-rows: 0fr; opacity: 0; pointer-events: none;'">
                            <div class="overflow-hidden min-h-0 pt-2">
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                                    {{ __('Pastikan seluruh dokumen berikut telah tercakup di dalam 1 file bundel penagihan yang Anda unggah:') }}
                                </p>

                                <!-- Grid 16 Items (2 Columns) -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                    @foreach (($trackingData['checklist_definitions'] ?? []) as $key => $item)
                                        <div class="p-4 rounded-2xl bg-white/40 dark:bg-white/5 border border-slate-200/70 dark:border-white/10 hover:border-[#F47920]/30 hover:shadow-xs transition-all duration-200 flex items-start gap-3.5">
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
                                        <strong>{{ __('Ketentuan Urutan Dokumen:') }}</strong>
                                        {!! __('Dokumen No. 01–05 bersifat <strong>Wajib</strong> bagi seluruh pengadaan. Dokumen No. 06–16 bersifat kondisional/pelengkap sesuai spesifikasi komoditas barang Anda. Susun dokumen secara berurutan di dalam file PDF Anda untuk mempercepat proses verifikasi oleh Tim Penerimaan Invoice PKT.') !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- 3.5 SURAT JALAN (DO) MASUK & BANTUAN -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                    <!-- LEFT COLUMN (8 Cols): SURAT JALAN (DO) CARDS -->
                    <div class="lg:col-span-8">

                        <!-- Surat Jalan (DO) Cards with Spring Tab Selector -->
                        <div class="glass-panel p-6 sm:p-7 rounded-3xl border border-white/80 dark:border-white/10 shadow-xl"
                             x-data="{
                                activeDo: 'all',
                                pillLeft: 0,
                                pillWidth: 0,
                                init() {
                                    this.$nextTick(() => {
                                        const first = this.$refs.tabContainer?.querySelector('[data-do-tab]');
                                        if (first) this.setTab(first);
                                    });
                                },
                                setTab(el) {
                                    if (!el || !this.$refs.tabContainer) return;
                                    const containerRect = this.$refs.tabContainer.getBoundingClientRect();
                                    const rect = el.getBoundingClientRect();
                                    this.pillLeft = rect.left - containerRect.left;
                                    this.pillWidth = rect.width;
                                }
                             }">
                            <div class="flex items-center justify-between mb-5 pb-4 border-b border-slate-200/70 dark:border-white/10">
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-[#F47920] block">{{ __('Bukti Pengiriman') }}</span>
                                    <h3 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">
                                        {{ __('Surat Jalan (DO) Masuk') }}
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
                                    <p class="font-bold">{{ __('Belum Ada Surat Jalan Masuk') }}</p>
                                    <p class="mt-1 text-slate-600 dark:text-slate-400">{{ __('Material PO ini belum diserahkan ke Pintu Gudang Receiving PKT.') }}</p>
                                </div>
                            @else
                                @if ($trackingData['unique_dos']->count() > 1)
                                    <!-- Segmented Spring Tab Switcher for Multiple DOs -->
                                    <div x-ref="tabContainer" class="relative p-1 rounded-2xl bg-slate-100 dark:bg-white/5 border border-slate-200/70 dark:border-white/10 flex items-center gap-1 mb-5 overflow-x-auto scrollbar-none">
                                        <!-- Sliding Spring Indicator -->
                                        <div class="absolute inset-y-1 rounded-xl bg-white dark:bg-slate-700/95 border border-black/5 dark:border-white/10 shadow-xs pointer-events-none transition-all duration-300 ease-[cubic-bezier(0.34,1.4,0.64,1)]"
                                             :style="`left: ${pillLeft}px; width: ${pillWidth}px;`">
                                        </div>

                                        <button type="button" 
                                                data-do-tab="all"
                                                @click="activeDo = 'all'; setTab($el)"
                                                class="relative z-10 px-3 py-1.5 rounded-xl text-xs font-bold transition-all duration-200 shrink-0 cursor-pointer"
                                                :class="activeDo === 'all' ? 'text-slate-900 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400'">
                                            {{ __('Semua DO') }} ({{ $trackingData['unique_dos']->count() }})
                                        </button>

                                        @foreach ($trackingData['unique_dos'] as $dIndex => $do)
                                            <button type="button" 
                                                    data-do-tab="{{ $do->id }}"
                                                    @click="activeDo = '{{ $do->id }}'; setTab($el)"
                                                    class="relative z-10 px-3 py-1.5 rounded-xl text-xs font-bold transition-all duration-200 shrink-0 cursor-pointer"
                                                    :class="activeDo == '{{ $do->id }}' ? 'text-[#F47920] dark:text-[#F47920]' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400'">
                                                DO #{{ $dIndex + 1 }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="grid grid-cols-1 {{ $trackingData['unique_dos']->count() > 1 ? 'md:grid-cols-2' : '' }} gap-4">
                                    @foreach ($trackingData['unique_dos'] as $do)
                                        <div x-show="activeDo === 'all' || activeDo == '{{ $do->id }}'"
                                             x-transition:enter="transition duration-300 ease-[cubic-bezier(0.34,1.4,0.64,1)]"
                                             x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]"
                                             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                             class="p-4 sm:p-5 rounded-2xl bg-white/40 dark:bg-white/5 border border-slate-200/70 dark:border-white/10 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5 group/card">
                                            <div class="flex items-start justify-between gap-3 mb-3">
                                                <div>
                                                    <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 block">{{ __('No. Surat Jalan (DO)') }}</span>
                                                    <h4 class="text-base font-black text-slate-800 dark:text-white tracking-tight">
                                                        {{ $do->delivery_order_no }}
                                                    </h4>
                                                </div>
                                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold
                                                    @if ($do->status === 'RDTV')
                                                        bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20
                                                    @elseif ($do->status === 'GRS' || $do->status === 'Diterima')
                                                        bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20
                                                    @elseif ($do->status === 'Pending')
                                                        bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20
                                                    @else
                                                        bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20
                                                    @endif">
                                                    {{ __($do->status ?? 'Diterima') }}
                                                </span>
                                            </div>

                                            <div class="space-y-2 text-xs">
                                                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                                    <span class="text-slate-400 dark:text-slate-500">{{ __('Kode Dokumen:') }}</span>
                                                    <span class="font-mono font-semibold">{{ $do->document_code }}</span>
                                                </div>
                                                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                                    <span class="text-slate-400 dark:text-slate-500">{{ __('Tanggal Tiba:') }}</span>
                                                    <span class="font-semibold">{{ $do->received_date ? \Carbon\Carbon::parse($do->received_date)->locale(app()->getLocale())->translatedFormat('d M Y') : '-' }}</span>
                                                </div>
                                                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                                    <span class="text-slate-400 dark:text-slate-500">{{ __('Mode Kedatangan:') }}</span>
                                                    <span class="font-semibold">{{ __($do->receipt_mode ?? 'Standard') }}</span>
                                                </div>
                                                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                                    <span class="text-slate-400 dark:text-slate-500">{{ __('Status Post 103:') }}</span>
                                                    @if ($do->post_103)
                                                        <span class="font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                            {{ __('Sudah di-Post') }}
                                                        </span>
                                                    @else
                                                        <span class="text-slate-400 font-medium">{{ __('Belum Post 103') }}</span>
                                                    @endif
                                                </div>

                                                <!-- QR 103 Code if exists -->
                                                @if ($do->qr_103_code)
                                                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                                        <span class="text-slate-400 dark:text-slate-500">{{ __('Kode QR 103:') }}</span>
                                                        <span class="font-mono font-bold text-[#F47920]">{{ $do->qr_103_code }}</span>
                                                    </div>
                                                @endif

                                                <!-- GRS/RDTV indicator if exists -->
                                                @if ($do->grsRdtvItems->isNotEmpty())
                                                    <div class="pt-2 border-t border-slate-200/50 dark:border-white/5 space-y-1.5">
                                                        <span class="text-slate-400 dark:text-slate-500 block text-[11px] font-semibold">{{ __('Slip Resmi Diterbitkan:') }}</span>
                                                        @foreach ($do->grsRdtvItems as $gItem)
                                                            @php
                                                                $itemCat = $gItem->grsRdtv?->category ?? 'GRS';
                                                                $isRdtvItem = $itemCat === 'RDTV';
                                                            @endphp
                                                            <div class="flex items-center justify-between {{ $isRdtvItem ? 'bg-rose-500/10 dark:bg-rose-500/15 border border-rose-500/20 text-rose-700 dark:text-rose-300' : 'bg-emerald-500/10 dark:bg-emerald-500/15 border border-emerald-500/20 text-emerald-700 dark:text-emerald-300' }} px-3 py-1.5 rounded-xl text-xs font-bold">
                                                                <span class="flex items-center gap-1.5">
                                                                    @if ($isRdtvItem)
                                                                        <svg class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                    @else
                                                                        <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                                    @endif
                                                                    {{ $itemCat }}
                                                                </span>
                                                                <span class="font-mono text-[11px]">{{ $gItem->grsRdtv?->transaction_date ? \Carbon\Carbon::parse($gItem->grsRdtv->transaction_date)->locale(app()->getLocale())->translatedFormat('d M Y') : '-' }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                <!-- Delay Reason if any -->
                                                @if ($do->delay_reason)
                                                    <div class="mt-2 p-2.5 rounded-xl {{ $do->delay_reason === 'RDTV' ? 'bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-300' : 'bg-amber-500/10 border border-amber-500/20 text-amber-700 dark:text-amber-300' }} text-[11px]">
                                                        <span class="font-bold block">{{ $do->delay_reason === 'RDTV' ? __('Catatan Retur RDTV:') : __('Catatan Penundaan:') }}</span>
                                                        <span>{{ $do->delay_reason }}</span>
                                                        @if ($do->delay_notes)
                                                            <span class="block text-slate-500 dark:text-slate-400 mt-0.5">{{ $do->delay_notes }}</span>
                                                        @endif
                                                    </div>
                                                @endif

                                                <!-- Compliance submissions for this DO -->
                                                @php
                                                    $doCompliances = $do->rdtvCompliances ?? collect();
                                                @endphp
                                                @if ($doCompliances->isNotEmpty())
                                                    <div class="mt-2.5 pt-2.5 border-t border-slate-200/60 dark:border-white/10 space-y-1.5 text-xs">
                                                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 block">{{ __('Dokumen Pemenuhan Diajukan:') }}</span>
                                                        @foreach ($doCompliances as $comp)
                                                            <div class="p-2.5 rounded-xl border {{ $comp->status === 'Disetujui' ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-800 dark:text-emerald-300' : ($comp->status === 'Ditolak' ? 'bg-rose-500/10 border-rose-500/25 text-rose-800 dark:text-rose-300' : 'bg-amber-500/10 border-amber-500/25 text-amber-800 dark:text-amber-300') }} flex items-center justify-between gap-2">
                                                                <div class="min-w-0">
                                                                    <span class="font-semibold truncate block">{{ $comp->original_filename }}</span>
                                                                    <span class="text-[10px] opacity-80">{{ $comp->created_at->format('d M Y H:i') }} &bull; {{ __($comp->status) }}</span>
                                                                </div>
                                                                <button type="button" wire:click="downloadRdtvCompliance({{ $comp->id }})" class="p-1 rounded-lg hover:bg-black/10 dark:hover:bg-white/10 shrink-0" title="{{ __('Unduh Berkas') }}">
                                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                                </button>
                                                            </div>
                                                            @if ($comp->status === 'Ditolak' && !empty($comp->review_notes))
                                                                <div class="p-2 rounded-lg bg-rose-500/15 border border-rose-500/20 text-[11px] text-rose-700 dark:text-rose-300">
                                                                    <strong>{{ __('Catatan QC:') }}</strong> {{ $comp->review_notes }}
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- RIGHT COLUMN (4 Cols): HELP / VENDOR CONTACT CARD -->
                    <div class="lg:col-span-4 lg:sticky lg:top-24">
                        <div class="glass-panel p-6 rounded-3xl border border-white/80 dark:border-white/10 shadow-xl bg-gradient-to-br from-[#0A4F86]/10 via-transparent to-[#F47920]/5 text-xs">
                            <h4 class="font-black text-slate-800 dark:text-white mb-2 flex items-center gap-2 text-sm">
                                <div class="p-1.5 rounded-lg bg-[#F47920]/10 text-[#F47920]">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                {{ __('Butuh Bantuan Terkait Dokumen?') }}
                            </h4>
                            <p class="text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
                                {{ __('Jika terdapat ketidaksesuaian data surat jalan atau dokumen GRS belum terbit melebihi SLA operasional, silakan hubungi Tim Receiving Pupuk Kalimantan Timur.') }}
                            </p>
                            <div class="space-y-2 text-xs">
                                <div class="flex items-center gap-2.5 text-slate-700 dark:text-slate-200">
                                    <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>{{ __('Gudang Pusat Receiving PKT, Bontang') }}</span>
                                </div>
                                <div class="flex items-center gap-2.5 text-slate-700 dark:text-slate-200">
                                    <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    <span>{{ __('Modul: Ekspedisi & Verifikasi Penagihan') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3.4 MATERIAL ITEMS & FULFILLMENT STATUS -->
                <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-white/80 dark:border-white/10 shadow-xl overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-200/70 dark:border-white/10">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-[#F47920] block">{{ __('Daftar Material') }}</span>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight">
                                {{ __('Rincian Item PO & Realisasi Penerimaan Fisik') }}
                            </h3>
                        </div>
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">
                            {{ __('Total') }}: {{ count($trackingData['material_items'] ?? []) }} {{ __('Item Material') }}
                        </span>
                    </div>

                    <!-- Items Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs sm:text-sm">
                            <thead class="text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 border-b border-slate-200/60 dark:border-white/10">
                                <tr>
                                    <th class="pb-3 px-3">{{ __('Item #') }}</th>
                                    <th class="pb-3 px-3">{{ __('Kode Material') }}</th>
                                    <th class="pb-3 px-3">{{ __('Deskripsi Barang') }}</th>
                                    <th class="pb-3 px-3 text-right">{{ __('Target PO') }}</th>
                                    <th class="pb-3 px-3 text-right">{{ __('Realisasi Tiba') }}</th>
                                    <th class="pb-3 px-3 text-center">{{ __('Status') }}</th>
                                    <th class="pb-3 px-3">{{ __('Lokasi Gudang') }}</th>
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
                                                MRP: {{ $item['mrp_type'] }} • {{ __('Tipe:') }} {{ $item['material_type'] }}
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
