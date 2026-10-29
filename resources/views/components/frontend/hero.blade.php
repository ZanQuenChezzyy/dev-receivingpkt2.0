<!-- Background Texture -->
    <div class="absolute inset-0 z-0 bg-[radial-gradient(#1e293b_1px,transparent_1px)] bg-size-[24px_24px] opacity-15 dark:opacity-10"></div>


    <!-- Animated Glassmorphism Blobs -->
    <div class="absolute top-0 right-0 z-0 w-full h-full overflow-hidden pointer-events-none">
        <!-- Top Right Navy Glow -->
        <div class="absolute -top-[10%] -right-[5%] w-[50%] h-[60%] rounded-full bg-gradient-to-bl from-[#0A4F86]/35 via-[#0A4F86]/10 to-transparent blur-[130px] animate-pulse" style="animation-duration: 4s;"></div>
        <!-- Bottom Left Orange Glow -->
        <div class="absolute -bottom-[10%] -left-[10%] w-[40%] h-[50%] rounded-full bg-gradient-to-tr from-[#F47920]/25 via-[#F47920]/5 to-transparent blur-[130px] animate-pulse" style="animation-duration: 5s; animation-delay: 2s;"></div>
        <!-- Center Subtle Blue -->
        <div class="absolute top-[30%] left-[30%] w-[30%] h-[40%] rounded-full bg-[#0A4F86]/15 blur-[160px]"></div>
    </div>
<section class="relative z-10 flex items-center justify-center min-h-[85vh] pt-28 pb-10">
        <div class="container mx-auto px-5 md:px-12 lg:px-24 flex flex-col-reverse lg:flex-row items-center gap-12 lg:gap-12">

            <!-- Left Column: Text & CTA -->
            <div class="w-full lg:w-5/12 flex flex-col items-start text-left mt-8 lg:mt-0">
                <!-- Glowing Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-slate-50 dark:bg-white/5 backdrop-blur-md border border-[#F47920]/30 shadow-[0_0_15px_rgba(244,121,32,0.15)] text-[#F47920] text-xs font-black tracking-widest uppercase mb-6 sm:mb-8">
                    <span class="relative flex w-2 h-2 sm:w-2.5 sm:h-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#F47920] opacity-75"></span>
                        <span class="relative inline-flex rounded-full w-2 h-2 sm:w-2.5 sm:h-2.5 bg-[#F47920]"></span>
                    </span>
                    Bagian Receiving • Dept. PP&amp;P Pupuk Kaltim
                </div>

                <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-black leading-[1.1] text-slate-800 dark:text-white mb-6 tracking-tight">
                    Pusat Layanan <br class="hidden sm:block">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#F47920] to-[#ff9b52] relative inline-block">
                        Unit Receiving
                        <svg class="absolute w-full h-2 sm:h-3 -bottom-2 left-0 text-[#0A4F86]/80 dark:text-[#F47920]/80" viewBox="0 0 100 10" preserveAspectRatio="none">
                            <path d="M0 5 Q 50 10 100 5" stroke="currentColor" stroke-width="4" fill="none" stroke-linecap="round" />
                        </svg>
                    </span>
                </h1>

                <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 mb-6 max-w-lg leading-relaxed font-medium">
                    Platform resmi <span class="text-[#0A4F86] dark:text-blue-400 font-bold">Department Perencanaan, Penerimaan &amp; Pergudangan (PP&amp;P)</span> PT Pupuk Kalimantan Timur untuk integrasi <strong>Penerimaan Barang</strong>, <strong>Invoicing Barang</strong>, serta <strong>Penerimaan &amp; Invoicing Jasa (SA)</strong>.
                </p>

                <!-- 3 Pilar Proses Kerja Receiving Pills -->
                <div class="flex flex-wrap items-center gap-2 mb-8">
                    <a href="#alur-proses" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-extrabold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20 hover:bg-emerald-500/20 transition-colors">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        1. Penerimaan Barang (GRS / RDTV)
                    </a>
                    <a href="#alur-proses" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-extrabold bg-blue-500/10 text-[#0A4F86] dark:text-blue-300 border border-blue-500/20 hover:bg-blue-500/20 transition-colors">
                        <span class="w-2 h-2 rounded-full bg-[#0A4F86] dark:bg-blue-400"></span>
                        2. Invoicing Barang (Mitraku)
                    </a>
                    <a href="#alur-proses" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-extrabold bg-teal-500/10 text-teal-700 dark:text-teal-300 border border-teal-500/20 hover:bg-teal-500/20 transition-colors">
                        <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                        3. Penerimaan &amp; Invoicing Jasa (SA)
                    </a>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto">
                    <a href="{{ filament()->getLoginUrl() }}"
                        class="w-full sm:w-auto flex items-center justify-center gap-2 px-8 py-4 text-sm sm:text-base font-bold text-white bg-gradient-to-r from-[#F47920] to-[#BE5A27] rounded-2xl shadow-[0_10px_30px_-10px_rgba(244,121,32,0.8)] hover:shadow-[0_15px_40px_-10px_rgba(244,121,32,1)] hover:-translate-y-1 transition-all duration-300">
                        Masuk Dashboard
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                    </a>
                    <a href="{{ route('frontend.list-material') }}" wire:navigate class="w-full sm:w-auto flex items-center justify-center px-8 py-4 text-sm sm:text-base font-bold text-slate-700 dark:text-slate-200 glass-btn rounded-2xl">
                        Daftar PD & Non-Stock
                    </a>
                </div>

                <!-- Glass Badges: 5 Material Types -->
                <div class="mt-8 sm:mt-10 w-full max-w-lg">
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2.5">
                        Tipe Material SAP (MTART)
                    </p>
                    <div class="grid grid-cols-5 gap-2 sm:gap-2.5">
                        <!-- ZSP -->
                        <div class="glass-panel p-2.5 sm:p-3 text-center rounded-2xl hover:-translate-y-1 transition-transform">
                            <span class="text-xs sm:text-sm font-black text-[#F47920] block">ZSP</span>
                            <span class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-bold block truncate">Sparepart</span>
                        </div>
                        <!-- ZRM -->
                        <div class="glass-panel p-2.5 sm:p-3 text-center rounded-2xl hover:-translate-y-1 transition-transform">
                            <span class="text-xs sm:text-sm font-black text-emerald-600 dark:text-emerald-400 block">ZRM</span>
                            <span class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-bold block truncate">Raw Mat.</span>
                        </div>
                        <!-- ZPM -->
                        <div class="glass-panel p-2.5 sm:p-3 text-center rounded-2xl hover:-translate-y-1 transition-transform">
                            <span class="text-xs sm:text-sm font-black text-purple-600 dark:text-purple-400 block">ZPM</span>
                            <span class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-bold block truncate">Packaging</span>
                        </div>
                        <!-- ZSM -->
                        <div class="glass-panel p-2.5 sm:p-3 text-center rounded-2xl hover:-translate-y-1 transition-transform">
                            <span class="text-xs sm:text-sm font-black text-[#0A4F86] dark:text-blue-400 block">ZSM</span>
                            <span class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-bold block truncate">Support</span>
                        </div>
                        <!-- ZFP -->
                        <div class="glass-panel p-2.5 sm:p-3 text-center rounded-2xl hover:-translate-y-1 transition-transform">
                            <span class="text-xs sm:text-sm font-black text-amber-600 dark:text-amber-400 block">ZFP</span>
                            <span class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-bold block truncate">Finish Prod.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Dashboard Illustration -->
            <div class="w-full lg:w-7/12 relative mt-4 md:mt-0">
                <div class="relative w-full max-w-full sm:max-w-2xl mx-auto px-2 sm:px-0">
                    
                    <!-- Dark Glass Dashboard Frame -->
                    <div class="relative glass-panel sm:rounded-[2rem] overflow-hidden flex flex-col transform perspective-1000 rotate-y-[-5deg] rotate-x-[2deg] transition-transform hover:rotate-y-0 hover:rotate-x-0 duration-700">
                        <!-- Header Bar -->
                        <div class="h-10 sm:h-14 bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10 flex items-center px-4 sm:px-6 gap-2">
                            <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-red-500/80"></div>
                            <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-amber-500/80"></div>
                            <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-green-500/80"></div>
                            <div class="hidden sm:flex ml-4 px-4 py-1.5 rounded-lg bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 items-center gap-3 w-56">
                                <svg class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                <div class="w-full h-1.5 bg-white/10 rounded-full"></div>
                            </div>
                        </div>

                        <!-- Dashboard Content -->
                        <div class="p-5 sm:p-8 flex-1 flex flex-col gap-5 sm:gap-6 bg-linear-to-b from-transparent to-black/20">
                            <!-- Top Stats -->
                            <div class="grid grid-cols-3 gap-3 sm:gap-5">
                                <div class="h-24 sm:h-32 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 p-4 sm:p-5 flex flex-col justify-between relative overflow-hidden group">
                                    <div class="absolute -top-10 -right-10 w-24 h-24 bg-[#0A4F86]/40 blur-xl rounded-full group-hover:bg-[#0A4F86]/60 transition-colors"></div>
                                    <div>
                                        <div class="text-xl sm:text-3xl font-black text-slate-800 dark:text-white">1,284</div>
                                        <div class="w-12 sm:w-20 h-1.5 mt-2 rounded-full bg-[#0A4F86]"></div>
                                    </div>
                                    <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 font-medium">Total Item</p>
                                </div>
                                <div class="h-24 sm:h-32 rounded-2xl bg-[#F47920]/10 border border-[#F47920]/30 p-4 sm:p-5 flex flex-col justify-between relative overflow-hidden group shadow-[inset_0_0_20px_rgba(244,121,32,0.1)]">
                                    <div class="absolute -top-10 -right-10 w-24 h-24 bg-[#F47920]/30 blur-xl rounded-full group-hover:bg-[#F47920]/50 transition-colors"></div>
                                    <div>
                                        <div class="text-xl sm:text-3xl font-black text-slate-800 dark:text-white">856</div>
                                        <div class="w-12 sm:w-20 h-1.5 mt-2 rounded-full bg-[#F47920]"></div>
                                    </div>
                                    <p class="text-[10px] sm:text-xs text-[#F47920] font-medium">Sudah GRS</p>
                                </div>
                                <div class="h-24 sm:h-32 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 p-4 sm:p-5 flex flex-col justify-between shadow-sm">
                                    <div>
                                        <div class="text-xl sm:text-3xl font-black text-slate-800 dark:text-white">24</div>
                                        <div class="w-10 sm:w-16 h-1.5 mt-2 rounded-full bg-slate-600"></div>
                                    </div>
                                    <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 font-medium">Pending QC</p>
                                </div>
                            </div>

                            <!-- Main Chart Area -->
                            <div class="flex-1 glass-panel p-5 sm:p-6 flex flex-col min-h-36 sm:min-h-0 relative overflow-hidden">
                                <div class="absolute bottom-0 left-0 w-full h-1/2 bg-linear-to-t from-[#0A4F86]/20 to-transparent"></div>
                                
                                <div class="flex justify-between items-center mb-6 relative z-10">
                                    <div class="w-24 sm:w-32 h-3 sm:h-4 bg-white/10 rounded-full"></div>
                                    <div class="w-12 sm:w-16 h-5 sm:h-6 bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-md"></div>
                                </div>
                                
                                <!-- Mock Bars -->
                                <div class="flex items-end gap-2 sm:gap-4 h-24 sm:h-36 mt-auto relative z-10">
                                    <div class="w-full bg-white/10 rounded-t-md h-[40%] hover:bg-white/20 transition-colors"></div>
                                    <div class="w-full bg-white/20 rounded-t-md h-[70%] hover:bg-white/30 transition-colors"></div>
                                    <div class="w-full bg-[#0A4F86]/80 rounded-t-md h-[50%] hover:bg-[#0A4F86] transition-colors border-t border-[#0A4F86]/50"></div>
                                    <div class="w-full bg-linear-to-t from-[#BE5A27] to-[#F47920] rounded-t-md h-[90%] shadow-[0_0_15px_rgba(244,121,32,0.6)] relative group cursor-pointer">
                                        <div class="absolute -top-10 left-1/2 -translate-x-1/2 bg-white text-[#051F34] text-[10px] sm:text-xs font-black px-2.5 py-1.5 rounded-md shadow-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                            Puncak MIGO
                                        </div>
                                    </div>
                                    <div class="w-full bg-[#0A4F86]/60 rounded-t-md h-[60%] hover:bg-[#0A4F86]/80 transition-colors"></div>
                                    <div class="w-full bg-white/10 rounded-t-md h-[30%] hover:bg-white/20 transition-colors"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Floating Glass Widget 1 -->
                    <div class="absolute -bottom-5 left-0 sm:-bottom-10 sm:-left-12 glass-panel p-3 sm:p-5 sm:rounded-3xl flex items-center gap-3 sm:gap-4 animate-bounce hover:scale-105 transition-transform" style="animation-duration: 4s;">
                        <div class="relative flex h-10 w-10 sm:h-14 sm:w-14 items-center justify-center rounded-xl bg-[#F47920]/10 dark:bg-[#F47920]/20 border border-[#F47920]/30 dark:border-[#F47920]/50">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-xl bg-[#F47920] opacity-30"></span>
                            <svg class="w-5 h-5 sm:w-7 sm:h-7 text-[#F47920]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div class="pr-2 sm:pr-4">
                            <p class="text-sm sm:text-base font-black text-slate-800 dark:text-white tracking-wide">Integrasi SAP</p>
                            <p class="text-[10px] sm:text-xs font-medium text-slate-500 dark:text-slate-400 mt-0.5">Update data real-time</p>
                        </div>
                    </div>

                    <!-- Floating Glass Widget 2 -->
                    <div class="absolute -top-6 right-2 sm:top-10 sm:-right-10 glass-panel px-4 py-3 sm:px-6 sm:py-4 sm:rounded-2xl flex items-center gap-3 transform hover:-rotate-3 transition-transform z-20">
                        <div class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-green-400 animate-pulse shadow-[0_0_10px_rgba(74,222,128,0.8)]"></div>
                        <p class="text-xs sm:text-sm font-bold text-slate-800 dark:text-white tracking-wider">Data Receiving Terpusat</p>
                    </div>

                </div>
            </div>
        </div>

        @php
            $aiSettingActive = false;
            try {
                $setting = \App\Models\Setting::where('key', 'ai_system_active')->first();
                $aiSettingActive = $setting ? $setting->value === '1' : false;
            } catch (\Exception $e) {
                $aiSettingActive = false;
            }
        @endphp

        <!-- AI Notification Modal -->
        <div x-data="{ 
                open: false,
                currentStatus: '{{ $aiSettingActive ? 'active' : 'maintenance' }}',
                init() {
                    if (localStorage.getItem('aiModalStatusSeen') !== this.currentStatus) {
                        this.open = true;
                    }
                },
                closeModal() {
                    this.open = false;
                    localStorage.setItem('aiModalStatusSeen', this.currentStatus);
                }
             }" 
             x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 sm:p-0" x-transition.opacity style="display: none;">
            <div @click.away="closeModal()" class="bg-white dark:bg-slate-800 rounded-3xl p-4 sm:p-8 max-w-lg w-full mx-auto shadow-2xl relative border border-slate-200 dark:border-slate-700 max-h-[85vh] sm:max-h-[90vh] overflow-y-auto scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-600" x-transition.scale.origin.bottom>
                <!-- Close Button -->
                <button @click="closeModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                
                <div class="flex items-center gap-3 sm:gap-4 mb-4 sm:mb-6">
                    @if($aiSettingActive)
                        <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-800 dark:text-white">Sistem AI Aktif</h3>
                            <p class="text-slate-500 dark:text-slate-400 text-sm">Sistem AI sudah dapat digunakan</p>
                        </div>
                    @else
                        <div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-800 dark:text-white">Sistem AI Sedang Maintenance</h3>
                            <p class="text-slate-500 dark:text-slate-400 text-sm">Sedang dalam masa training</p>
                        </div>
                    @endif
                </div>

                <div class="text-slate-600 dark:text-slate-300 mb-6 sm:mb-8 leading-relaxed">
                    @if($aiSettingActive)
                        <p class="text-sm sm:text-base">Mokondo AI Receiving Pupuk Kalimantan Timur v2.0 saat ini telah aktif! Asisten chatbot cerdas kami siap membantu Anda mengecek status barang dan memandu proses pengambilan barang di gudang dengan cepat dan mudah.</p>

                        <div class="mt-4 sm:mt-6 relative group overflow-hidden rounded-2xl">
                            <!-- Animated Glow Background -->
                            <div class="absolute inset-0 bg-gradient-to-r from-[#F47920]/20 via-amber-400/20 to-[#0A4F86]/20 blur-xl group-hover:blur-2xl transition-all duration-500"></div>
                            
                            <div class="relative p-5 sm:p-6 bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl border border-slate-200/80 dark:border-white/10 flex flex-col items-center justify-center text-center rounded-2xl shadow-xl">
                                <div class="w-12 h-12 sm:w-14 sm:h-14 bg-gradient-to-br from-[#F47920]/20 to-orange-500/5 border border-[#F47920]/30 rounded-2xl flex items-center justify-center mb-3 transform group-hover:scale-110 group-hover:rotate-6 transition-all duration-300 shadow-inner">
                                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-[#F47920]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <h4 class="text-base sm:text-lg font-black text-slate-800 dark:text-white mb-1.5 tracking-tight">Baru Pertama Kali Menggunakan Sistem?</h4>
                                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 mb-4 max-w-sm leading-relaxed font-medium">
                                    Ikuti panduan interaktif 4 langkah cepat untuk mengetahui cara melacak PO, surat jalan DO, dan status material bersama asisten AI ALEX.
                                </p>
                                
                                <div class="grid grid-cols-2 gap-2 w-full max-w-xs mb-4 text-[11px] font-bold text-slate-600 dark:text-slate-300">
                                    <div class="flex items-center gap-1.5 p-2 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                                        <span>🤖</span> <span>Buka Chatbot</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 p-2 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                                        <span>⚡</span> <span>Saran Cepat</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 p-2 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                                        <span>🔎</span> <span>Ketik No. PO</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 p-2 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                                        <span>🎯</span> <span>Status Gudang</span>
                                    </div>
                                </div>

                                <button @click="closeModal(); startAiTour()" class="flex items-center justify-center w-full sm:w-auto gap-2.5 px-6 py-3 bg-gradient-to-r from-[#F47920] to-[#BE5A27] hover:from-orange-500 hover:to-orange-700 text-white text-xs sm:text-sm font-black rounded-xl shadow-[0_8px_20px_-6px_rgba(244,121,32,0.6)] hover:shadow-[0_12px_25px_-6px_rgba(244,121,32,0.8)] transition-all hover:-translate-y-0.5 active:scale-95 cursor-pointer">
                                    <span>Mulai Tur Panduan AI (4 Langkah)</span>
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </button>
                            </div>
                        </div>
                    @else
                        <p class="text-sm sm:text-base">Mokondo AI Receiving Pupuk Kalimantan Timur v2.0 saat ini sedang dalam masa pemeliharaan dan pelatihan Model (Training). Layanan chatbot cerdas untuk pengecekan status dan pengambilan barang sementara tidak dapat digunakan. Kami mohon maaf atas ketidaknyamanan ini.</p>
                    @endif
                </div>

                <button @click="closeModal()" class="w-full py-3 px-4 bg-slate-800 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 text-white font-bold rounded-xl transition-colors">
                    Mengerti
                </button>
            </div>
        </div>
    </section>

    <!-- Shepherd.js for Interactive Tour -->
    <link rel="stylesheet" href="https://unpkg.com/shepherd.js@11.0.1/dist/css/shepherd.css"/>
    <script src="https://unpkg.com/shepherd.js@11.0.1/dist/js/shepherd.min.js"></script>
    <style>
        /* Custom styling untuk Shepherd.js konsisten dengan form */
        @keyframes shepherdPop {
            0% { opacity: 0; transform: scale(0.92) translateY(12px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }

        @keyframes shepherdAiGlow {
            0%, 100% {
                box-shadow: 0 0 0 4px rgba(244, 121, 32, 0.45), 0 0 25px rgba(244, 121, 32, 0.25) !important;
            }
            50% {
                box-shadow: 0 0 0 6px rgba(244, 121, 32, 0.75), 0 0 40px rgba(244, 121, 32, 0.45) !important;
            }
        }

        .shepherd-theme,
        .shepherd-element {
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            border-radius: 1.5rem !important;
            border: 1px solid rgba(244, 121, 32, 0.3) !important;
            box-shadow: 0 25px 50px -12px rgba(10, 79, 134, 0.25), 0 0 30px rgba(244, 121, 32, 0.12) !important;
            font-family: inherit !important;
            color: #334155 !important;
            max-width: 370px !important;
            z-index: 2147483647 !important;
            overflow: hidden !important;
            animation: shepherdPop 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
        }

        .dark .shepherd-theme,
        .dark .shepherd-element {
            background: rgba(15, 23, 42, 0.96) !important;
            border: 1px solid rgba(244, 121, 32, 0.35) !important;
            color: #f8fafc !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.75), 0 0 25px rgba(244, 121, 32, 0.2) !important;
        }

        .shepherd-theme .shepherd-arrow:before,
        .shepherd-element .shepherd-arrow:before {
            background: #ffffff !important;
            border: 1px solid rgba(244, 121, 32, 0.3) !important;
        }
        
        .dark .shepherd-theme .shepherd-arrow:before,
        .dark .shepherd-element .shepherd-arrow:before {
            background: #0f172a !important;
            border-color: rgba(244, 121, 32, 0.35) !important;
        }

        .shepherd-theme .shepherd-header,
        .shepherd-element .shepherd-header {
            background: transparent !important;
            border-bottom: none !important;
            padding: 1.25rem 1.25rem 0.5rem !important;
            display: flex !important;
            align-items: flex-start !important;
            justify-content: space-between !important;
        }

        .shepherd-theme .shepherd-title,
        .shepherd-element .shepherd-title {
            font-size: 1rem !important;
            font-weight: 900 !important;
            color: #0f172a !important;
            width: 100% !important;
            margin: 0 !important;
            line-height: 1.3 !important;
        }
        
        .dark .shepherd-theme .shepherd-title,
        .dark .shepherd-element .shepherd-title {
            color: #f8fafc !important;
        }

        .shepherd-theme .shepherd-cancel-icon,
        .shepherd-element .shepherd-cancel-icon {
            color: #94a3b8 !important;
            font-size: 1.4rem !important;
            font-weight: 400 !important;
            transition: all 0.2s ease !important;
            padding: 0.25rem !important;
            border-radius: 0.5rem !important;
            line-height: 1 !important;
        }
        
        .shepherd-theme .shepherd-cancel-icon:hover,
        .shepherd-element .shepherd-cancel-icon:hover {
            color: #ef4444 !important;
            transform: rotate(90deg) scale(1.15) !important;
        }

        .shepherd-theme .shepherd-text,
        .shepherd-element .shepherd-text {
            padding: 0.5rem 1.25rem 0.75rem !important;
            font-size: 0.875rem !important;
            line-height: 1.6 !important;
            color: #475569 !important;
        }

        .dark .shepherd-theme .shepherd-text,
        .dark .shepherd-element .shepherd-text {
            color: #cbd5e1 !important;
        }

        .shepherd-theme .shepherd-footer,
        .shepherd-element .shepherd-footer {
            padding: 0.75rem 1.25rem 1.25rem !important;
            background: transparent !important;
            border-top: 1px solid rgba(226, 232, 240, 0.6) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 0.5rem !important;
        }

        .dark .shepherd-theme .shepherd-footer,
        .dark .shepherd-element .shepherd-footer {
            border-top-color: rgba(255, 255, 255, 0.08) !important;
        }

        /* Tombol */
        .shepherd-button {
            border-radius: 0.875rem !important;
            font-weight: 800 !important;
            font-size: 0.8125rem !important;
            padding: 0.55rem 1.15rem !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            cursor: pointer !important;
        }

        .shepherd-button:active {
            transform: scale(0.96) !important;
        }

        .shepherd-button-primary {
            background: linear-gradient(135deg, #F47920 0%, #BE5A27 100%) !important;
            color: #ffffff !important;
            border: none !important;
            box-shadow: 0 4px 14px rgba(244, 121, 32, 0.35) !important;
        }

        .shepherd-button-primary:hover {
            box-shadow: 0 6px 20px rgba(244, 121, 32, 0.5) !important;
            transform: translateY(-1px) !important;
        }

        .shepherd-button-secondary {
            background-color: #f1f5f9 !important;
            color: #475569 !important;
            border: 1px solid #e2e8f0 !important;
        }
        
        .shepherd-button-secondary:hover {
            background-color: #e2e8f0 !important;
            color: #1e293b !important;
        }

        .dark .shepherd-button-secondary {
            background-color: #1e293b !important;
            color: #cbd5e1 !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        .dark .shepherd-button-secondary:hover {
            background-color: #334155 !important;
            color: #f8fafc !important;
        }

        /* Highlight Target */
        .shepherd-target.shepherd-enabled {
            animation: shepherdAiGlow 2s infinite ease-in-out !important;
            transition: all 0.3s ease;
            border-radius: 1rem !important;
            z-index: 10000 !important;
            position: relative;
        }

        @media (max-width: 640px) {
            .shepherd-theme,
            .shepherd-element {
                max-width: calc(100vw - 32px) !important; 
                margin: 0 auto !important;
            }
            .shepherd-theme .shepherd-header,
            .shepherd-element .shepherd-header { 
                padding: 1rem 1rem 0.5rem !important; 
            }
            .shepherd-theme .shepherd-text,
            .shepherd-element .shepherd-text { 
                padding: 0.5rem 1rem !important; 
            }
            .shepherd-theme .shepherd-footer,
            .shepherd-element .shepherd-footer { 
                padding: 0.5rem 1rem 1rem !important; 
            }
        }
    </style>
    <script>
        function startAiTour() {
            if (typeof Shepherd === 'undefined') return;
            
            const tour = new Shepherd.Tour({
                useModalOverlay: true,
                defaultStepOptions: {
                    classes: 'shepherd-element shepherd-theme',
                    scrollTo: { behavior: 'smooth', block: 'center' },
                    cancelIcon: {
                        enabled: true
                    }
                }
            });

            const aiSteps = [
                {
                    id: 'step-1',
                    num: 1,
                    icon: '🤖',
                    title: 'Buka Chatbot ALEX',
                    text: 'Klik tombol oranye ini di pojok kanan bawah untuk membuka jendela Asisten AI interaktif Receiving.',
                    attachTo: { element: '#tour-chatbot-button', on: 'left' }
                },
                {
                    id: 'step-2',
                    num: 2,
                    icon: '💡',
                    title: 'Rekomendasi Pertanyaan Cepat',
                    text: 'Pilih salah satu saran pertanyaan ini untuk memeriksa status PO, stok barang, atau panduan secara instan tanpa perlu mengetik.',
                    attachTo: { element: '#tour-chatbot-suggestions', on: 'top' }
                },
                {
                    id: 'step-3',
                    num: 3,
                    icon: '💬',
                    title: 'Ketik Pertanyaan Bebas',
                    text: 'Atau ketikkan nomor Purchase Order (PO), nomor Surat Jalan (DO), Stock Number, atau MIR di sini lalu tekan Enter.',
                    attachTo: { element: '#tour-chatbot-input', on: 'top' }
                },
                {
                    id: 'step-4',
                    num: 4,
                    icon: '⚡',
                    title: 'Jawaban Cerdas & Real-Time',
                    text: 'ALEX akan mencari data langsung di sistem Receiving dan menyajikan status lengkap dokumen Anda secara otomatis di sini.',
                    attachTo: { element: '#tour-chatbot-window', on: 'left' }
                }
            ];

            const totalSteps = aiSteps.length;

            const makeHeaderHtml = (step) => {
                const percent = Math.round((step.num / totalSteps) * 100);
                return `
                    <div class="flex items-center justify-between w-full mb-1">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-orange-100 dark:bg-orange-500/20 text-[#F47920] text-[11px] font-black uppercase tracking-wider">
                            Langkah ${step.num} dari ${totalSteps}
                        </span>
                        <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500">${percent}%</span>
                    </div>
                    <div class="flex items-center gap-2 text-slate-900 dark:text-white font-black text-sm sm:text-base mt-1">
                        <span class="text-base sm:text-lg">${step.icon}</span>
                        <span>${step.title}</span>
                    </div>
                `;
            };

            const makeTextHtml = (step) => {
                const percent = Math.round((step.num / totalSteps) * 100);
                return `
                    <div class="w-full h-1 bg-slate-100 dark:bg-slate-800 rounded-full mb-3 overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-[#F47920] to-[#BE5A27] rounded-full transition-all duration-300" style="width: ${percent}%"></div>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed font-medium m-0">${step.text}</p>
                `;
            };

            // Step 1
            tour.addStep({
                id: aiSteps[0].id,
                title: makeHeaderHtml(aiSteps[0]),
                text: makeTextHtml(aiSteps[0]),
                attachTo: aiSteps[0].attachTo,
                buttons: [
                    {
                        text: 'Buka Chat & Lanjut →',
                        classes: 'shepherd-button-primary',
                        action: function() {
                            const chatBtn = document.querySelector('#tour-chatbot-button button');
                            const chatWindow = document.querySelector('#tour-chatbot-window');
                            
                            if (chatBtn && chatWindow && chatWindow.style.display === 'none') {
                                chatBtn.click(); // Memicu pembukaan jendela
                                
                                let attempts = 0;
                                let checkExist = setInterval(() => {
                                    attempts++;
                                    if (chatWindow.style.display !== 'none') {
                                        clearInterval(checkExist);
                                        setTimeout(() => tour.next(), 450);
                                    } else if (attempts > 50) {
                                        clearInterval(checkExist);
                                        tour.next();
                                    }
                                }, 100);
                            } else {
                                tour.next();
                            }
                        }
                    }
                ]
            });

            // Step 2
            tour.addStep({
                id: aiSteps[1].id,
                title: makeHeaderHtml(aiSteps[1]),
                text: makeTextHtml(aiSteps[1]),
                attachTo: aiSteps[1].attachTo,
                buttons: [
                    {
                        text: '← Kembali',
                        classes: 'shepherd-button-secondary',
                        action: function() {
                            const chatBtn = document.querySelector('#tour-chatbot-button button');
                            const chatWindow = document.querySelector('#tour-chatbot-window');
                            
                            if (chatBtn && chatWindow && chatWindow.style.display !== 'none') {
                                chatBtn.click(); // Tutup jendela
                                
                                let attempts = 0;
                                let checkExist = setInterval(() => {
                                    attempts++;
                                    if (chatWindow.style.display === 'none') {
                                        clearInterval(checkExist);
                                        setTimeout(() => tour.back(), 450);
                                    } else if (attempts > 50) {
                                        clearInterval(checkExist);
                                        tour.back();
                                    }
                                }, 100);
                            } else {
                                tour.back();
                            }
                        }
                    },
                    {
                        text: 'Lanjut →',
                        classes: 'shepherd-button-primary',
                        action: tour.next
                    }
                ]
            });

            // Step 3
            tour.addStep({
                id: aiSteps[2].id,
                title: makeHeaderHtml(aiSteps[2]),
                text: makeTextHtml(aiSteps[2]),
                attachTo: aiSteps[2].attachTo,
                buttons: [
                    {
                        text: '← Kembali',
                        classes: 'shepherd-button-secondary',
                        action: tour.back
                    },
                    {
                        text: 'Lanjut →',
                        classes: 'shepherd-button-primary',
                        action: tour.next
                    }
                ]
            });

            // Step 4
            tour.addStep({
                id: aiSteps[3].id,
                title: makeHeaderHtml(aiSteps[3]),
                text: makeTextHtml(aiSteps[3]),
                attachTo: aiSteps[3].attachTo,
                buttons: [
                    {
                        text: '← Kembali',
                        classes: 'shepherd-button-secondary',
                        action: tour.back
                    },
                    {
                        text: 'Selesai ✨',
                        classes: 'shepherd-button-primary !bg-emerald-600 hover:!bg-emerald-700',
                        action: tour.complete
                    }
                ]
            });
            
            // Handle cleanup saat ditutup atau selesai
            const closeTour = () => {
                const chatBtn = document.querySelector('#tour-chatbot-button button');
                const chatWindow = document.querySelector('#tour-chatbot-window');
                if (chatBtn && chatWindow && chatWindow.style.display !== 'none') {
                    // Chatbot dibiarkan terbuka agar user bisa langsung menggunakan
                }
            };
            
            tour.on('cancel', closeTour);
            tour.on('complete', closeTour);

            setTimeout(() => {
                tour.start();
            }, 300);
        }
        window.startAiTour = startAiTour;
    </script>