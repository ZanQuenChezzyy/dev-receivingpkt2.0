<footer class="relative z-10 pt-16 pb-12 mt-auto border-t border-black/[0.06] dark:border-white/[0.06] bg-slate-100/60 dark:bg-[#070e18]/85 backdrop-blur-2xl overflow-hidden">
    <!-- PKT Pattern Watermark in Footer -->
    <div class="absolute -right-12 -bottom-12 w-72 h-72 pointer-events-none opacity-10 dark:opacity-5 overflow-hidden select-none">
        <img :src="isDark ? '{{ asset('images/pattern/pkt-pattern-white.png') }}' : '{{ asset('images/pattern/pkt-pattern-dark.png') }}'" 
            alt="PKT Pattern" 
            class="w-full h-full object-contain object-bottom-right">
    </div>

    <div class="container mx-auto px-5 md:px-12 lg:px-24 relative z-10">
        <!-- Top Section: Brand & Navigation -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8 pb-12 border-b border-black/[0.06] dark:border-white/[0.06]">
            <!-- Brand Info (Col 5) -->
            <div class="lg:col-span-5 space-y-4">
                <a href="/" wire:navigate class="inline-block transition-opacity hover:opacity-85">
                    <img :src="isDark ? '{{ asset('images/logo/receiving_white.png') }}' : '{{ asset('images/logo/receiving_dark.png') }}'" 
                        alt="Receiving Pupuk Kalimantan Timur Logo"
                        class="h-8 md:h-9 w-auto object-contain">
                </a>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-sm leading-relaxed font-normal">
                    Sistem Informasi Proses Kerja Bagian Receiving, Departemen Perencanaan, Penerimaan &amp; Pergudangan (Dept. PP&amp;P) PT Pupuk Kalimantan Timur: Penerimaan Barang, Invoicing Barang, serta Penerimaan &amp; Invoicing Jasa.
                </p>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Semua Layanan Sistem Beroperasi Normal</span>
                </div>
            </div>

            <!-- Quick Links (Col 4) -->
            <div class="lg:col-span-4 space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">Navigasi Cepat</h4>
                <div class="grid grid-cols-2 gap-2 text-xs font-medium">
                    @auth
                        <a href="/#alur-proses" class="text-slate-500 dark:text-slate-400 hover:text-[#F47920] dark:hover:text-white transition-colors">Alur Material</a>
                    @endauth
                    <a href="{{ route('frontend.list-material') }}" wire:navigate class="text-slate-500 dark:text-slate-400 hover:text-[#F47920] dark:hover:text-white transition-colors">Material PD</a>
                    @auth
                        <a href="/#fitur-layanan" class="text-slate-500 dark:text-slate-400 hover:text-[#F47920] dark:hover:text-white transition-colors">Ekosistem Layanan</a>
                    @endauth
                    <a href="{{ route('frontend.list-material', ['activeTab' => 'NONSTOCK']) }}" wire:navigate class="text-slate-500 dark:text-slate-400 hover:text-[#F47920] dark:hover:text-white transition-colors">Material Non-Stock</a>
                    <a href="{{ route('frontend.mir.create') }}" wire:navigate class="text-slate-500 dark:text-slate-400 hover:text-[#F47920] dark:hover:text-white transition-colors">Pengambilan (MIR)</a>
                    <a href="{{ auth()->check() ? filament()->getUrl() : filament()->getLoginUrl() }}" class="text-slate-500 dark:text-slate-400 hover:text-[#F47920] dark:hover:text-white transition-colors">{{ auth()->check() ? 'Dashboard Admin' : 'Login Admin' }}</a>
                </div>
            </div>

            <!-- Unit Kerja Info (Col 3) -->
            <div class="lg:col-span-3 space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">Bagian Receiving • Dept. PP&amp;P</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed font-normal">
                    Department of Planning, Receiving &amp; Warehousing<br>
                    PT Pupuk Kalimantan Timur<br>
                    Bontang, Kalimantan Timur
                </p>
                @auth
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 space-y-0.5 pt-1">
                        <div>Konfirmasi SA Jasa: <a href="https://wa.me/6285752451225" target="_blank" rel="noopener" class="font-bold text-slate-700 dark:text-slate-200 hover:text-[#F47920]">+62 857-5245-1225</a></div>
                        <div>Email: <a href="mailto:receiving.jasa@pupukkaltim.com" class="font-bold text-slate-700 dark:text-slate-200 hover:text-[#F47920]">receiving.jasa@pupukkaltim.com</a></div>
                    </div>
                @endauth
                @php
                    $footerAiActive = false;
                    try {
                        $footerSetting = \App\Models\Setting::where('key', 'ai_system_active')->first();
                        $footerAiActive = $footerSetting ? $footerSetting->value === '1' : false;
                    } catch (\Throwable $e) {
                        $footerAiActive = false;
                    }
                @endphp
                <div class="pt-1">
                    @if ($footerAiActive)
                        <button type="button" onclick="startAiTour()" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#F47920] hover:underline cursor-pointer">
                            <span>Panduan ALEX Asisten AI Receiving (Online)</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    @else
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-600 dark:text-amber-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            <span>ALEX Asisten AI Receiving (Offline)</span>
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Bottom Copyright -->
        <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400 dark:text-slate-500">
            <p>
                &copy; {{ date('Y') }} Receiving Pupuk Kalimantan Timur v2.0 • PT Pupuk Kalimantan Timur. Developed by Andereyan Muhammat.
            </p>
            <div class="flex items-center gap-6 font-medium">
                <span>Versi 2.0.4 (Enterprise)</span>
            </div>
        </div>
    </div>
</footer>
