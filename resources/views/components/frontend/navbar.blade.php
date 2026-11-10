<nav x-data="{ mobileMenuOpen: false }"
        class="glass-nav fixed top-0 w-full z-50 transition-all duration-300 px-5 py-3.5 md:px-12 lg:px-24">

        <div class="flex justify-between items-center w-full max-w-7xl mx-auto">
            <div class="flex items-center">
                <a href="/" wire:navigate class="block transition-opacity hover:opacity-85 active:scale-[0.98] duration-200">
                    <!-- Gunakan logo putih untuk tema gelap -->
                    <img :src="isDark ? '{{ asset('images/logo/receiving_white.png') }}' : '{{ asset('images/logo/receiving_dark.png') }}'" alt="Receiving Pupuk Kalimantan Timur Logo"
                        class="h-7 sm:h-8 md:h-9 w-auto object-contain drop-shadow-sm">
                </a>
            </div>

            <!-- Apple-Style Desktop Navigation Links -->
            <div class="nav-desktop hidden lg:flex items-center gap-1.5 z-50">
                <!-- Dropdown 1: Daftar Material -->
                <div x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" class="relative group py-1">
                    <button @click="open = !open" 
                            class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-[13px] font-semibold text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-black/[0.04] dark:hover:bg-white/[0.08] transition-all duration-200">
                        <span>Daftar Material</span>
                        <svg :class="{'rotate-180': open}" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div x-show="open" style="display: none;" 
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2 scale-95" 
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-150" 
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                        class="absolute left-0 mt-1.5 w-52 bg-white/90 dark:bg-[#0c1626]/95 backdrop-blur-2xl rounded-2xl border border-black/[0.06] dark:border-white/[0.1] shadow-2xl p-1.5 z-[100]">
                        <a href="{{ route('frontend.list-material') }}" wire:navigate 
                           class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:text-[#F47920] dark:hover:text-white hover:bg-black/[0.04] dark:hover:bg-white/[0.08] rounded-xl transition-all duration-150">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#F47920]"></span>
                            <span>Material PD</span>
                        </a>
                        <a href="{{ route('frontend.list-material', ['activeTab' => 'NONSTOCK']) }}" wire:navigate 
                           class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:text-[#F47920] dark:hover:text-white hover:bg-black/[0.04] dark:hover:bg-white/[0.08] rounded-xl transition-all duration-150">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            <span>Material Non-Stock</span>
                        </a>
                    </div>
                </div>

                @auth
                    <!-- Link 1: Fitur -->
                    <a href="/#fitur-layanan" 
                       class="px-3.5 py-1.5 rounded-full text-[13px] font-semibold text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-black/[0.04] dark:hover:bg-white/[0.08] transition-all duration-200">
                        Fitur
                    </a>

                    <!-- Link 2: Alur Proses -->
                    <a href="/#alur-proses" 
                       class="px-3.5 py-1.5 rounded-full text-[13px] font-semibold text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-black/[0.04] dark:hover:bg-white/[0.08] transition-all duration-200">
                        Alur Proses
                    </a>
                @endauth

                <!-- Link 3: Lacak PO Vendor (Khusus Rekanan) -->
                <a href="{{ route('frontend.vendor.tracking') }}" wire:navigate 
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-[13px] font-semibold text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-black/[0.04] dark:hover:bg-white/[0.08] transition-all duration-200">
                    <svg class="w-3.5 h-3.5 text-[#F47920]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Lacak PO Vendor</span>
                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full bg-[#F47920]/12 text-[#F47920] border border-[#F47920]/25">Rekanan</span>
                </a>

                <!-- Link 4: Pengambilan Barang -->
                <a href="{{ route('frontend.mir.create') }}" wire:navigate 
                   class="px-3.5 py-1.5 rounded-full text-[13px] font-semibold text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-black/[0.04] dark:hover:bg-white/[0.08] transition-all duration-200">
                    Pengambilan Barang (MIR)
                </a>
            </div>

            <!-- Action Controls (Desktop & Mobile) -->
            <div class="flex items-center gap-2 sm:gap-2.5">

                <!-- Theme Toggler: Apple Style Pill -->
                <button @click="isDark = !isDark" 
                        title="Ganti Tema"
                        class="p-2 sm:p-2.5 rounded-full bg-black/[0.04] dark:bg-white/[0.08] hover:bg-black/[0.08] dark:hover:bg-white/[0.14] text-slate-700 dark:text-yellow-400 flex items-center justify-center transition-all duration-200 active:scale-95">
                    <svg x-show="isDark" class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <svg x-show="!isDark" style="display: none;" class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                </button>

                <!-- Login / Dashboard: Apple Pill Button -->
                <a href="{{ auth()->check() ? filament()->getUrl() : filament()->getLoginUrl() }}"
                    class="apple-btn-primary px-4 py-2 sm:px-5 sm:py-2 text-xs sm:text-sm font-semibold rounded-full flex items-center gap-1.5 shadow-sm">
                    <span>{{ auth()->check() ? 'Dashboard' : 'Login' }}</span>
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </a>

                <!-- Hamburger Button (Mobile & Tablet) -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" 
                        class="p-2 rounded-full lg:!hidden flex items-center justify-center bg-black/[0.04] dark:bg-white/[0.08] text-slate-700 dark:text-slate-200 active:scale-95 transition-all">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Apple-Style Mobile Drawer & Sheet -->
        <div x-show="mobileMenuOpen" style="display: none;" 
             x-transition.opacity.duration.300ms 
             class="lg:hidden fixed inset-0 z-40 bg-black/40 backdrop-blur-sm" 
             @click="mobileMenuOpen = false"></div>
            
        <div x-show="mobileMenuOpen" style="display: none;"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="lg:hidden fixed top-0 right-0 z-50 w-[85%] sm:w-80 h-screen bg-white/95 dark:bg-[#0c1626]/95 backdrop-blur-2xl border-l border-black/[0.06] dark:border-white/[0.08] shadow-2xl overflow-y-auto flex flex-col">
            
            <div class="px-6 py-5 flex items-center justify-between sticky top-0 bg-white/80 dark:bg-[#0c1626]/80 backdrop-blur-xl border-b border-black/[0.06] dark:border-white/[0.08] z-10">
                <img :src="isDark ? '{{ asset('images/logo/receiving_white.png') }}' : '{{ asset('images/logo/receiving_dark.png') }}'" alt="Receiving Pupuk Kalimantan Timur Logo" class="h-6 w-auto object-contain">
                <button @click="mobileMenuOpen = false" class="p-2 text-slate-400 hover:text-slate-800 dark:hover:text-white rounded-full hover:bg-black/[0.05] dark:hover:bg-white/[0.08] transition-all">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="px-5 py-6 flex flex-col gap-2">
                <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest px-3 mb-1">Navigasi Utama</span>

                <div x-data="{ accOpen: false }" class="rounded-2xl bg-black/[0.03] dark:bg-white/[0.04] overflow-hidden">
                    <button @click="accOpen = !accOpen" class="w-full flex justify-between items-center px-4 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#F47920]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            Daftar Material
                        </span>
                        <svg :class="{'rotate-180': accOpen}" class="w-4 h-4 transition-transform text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="accOpen" style="display: none;" class="px-4 pb-3 flex flex-col gap-1 pt-1 border-t border-black/[0.04] dark:border-white/[0.06]">
                        <a href="{{ route('frontend.list-material') }}" wire:navigate class="block px-3 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-[#F47920] dark:hover:text-white rounded-xl">Material PD</a>
                        <a href="{{ route('frontend.list-material', ['activeTab' => 'NONSTOCK']) }}" wire:navigate class="block px-3 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-[#F47920] dark:hover:text-white rounded-xl">Material Non Stock</a>
                    </div>
                </div>

                <a href="{{ route('frontend.vendor.tracking') }}" wire:navigate class="flex items-center justify-between gap-2 px-4 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200 rounded-2xl bg-black/[0.03] dark:bg-white/[0.04] hover:bg-black/[0.06] dark:hover:bg-white/[0.08] transition-colors">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#F47920]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span>Lacak PO Vendor</span>
                    </span>
                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full bg-[#F47920]/15 text-[#F47920]">Rekanan</span>
                </a>

                @auth
                    <a @click="mobileMenuOpen = false" href="/#fitur-layanan" class="flex items-center gap-2 px-4 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200 rounded-2xl bg-black/[0.03] dark:bg-white/[0.04]">
                        <svg class="w-4 h-4 text-[#F47920]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        Fitur Layanan
                    </a>

                    <a @click="mobileMenuOpen = false" href="/#alur-proses" class="flex items-center gap-2 px-4 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200 rounded-2xl bg-black/[0.03] dark:bg-white/[0.04]">
                        <svg class="w-4 h-4 text-[#F47920]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Alur Perjalanan Material
                    </a>
                @endauth

                <a href="{{ route('frontend.mir.create') }}" wire:navigate class="flex items-center gap-2 px-4 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200 rounded-2xl bg-black/[0.03] dark:bg-white/[0.04]">
                    <svg class="w-4 h-4 text-[#F47920]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Pengambilan Barang (MIR)
                </a>
            </div>

            <div class="mt-auto p-5 border-t border-black/[0.06] dark:border-white/[0.08]">
                <a href="{{ auth()->check() ? filament()->getUrl() : filament()->getLoginUrl() }}"
                    class="apple-btn-primary w-full py-3.5 text-sm font-semibold rounded-2xl flex items-center justify-center gap-2">
                    <span>{{ auth()->check() ? 'Buka Dashboard' : 'Login Admin' }}</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </a>
            </div>
        </div>
    </nav>