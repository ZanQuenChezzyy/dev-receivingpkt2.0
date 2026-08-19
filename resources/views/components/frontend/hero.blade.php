<!-- Background Texture -->
    <div class="absolute inset-0 z-0 bg-[radial-gradient(#1e293b_1px,transparent_1px)] bg-size-[24px_24px] opacity-20"></div>

    <!-- Animated Glassmorphism Blobs -->
    <div class="absolute top-0 right-0 z-0 w-full h-full overflow-hidden pointer-events-none">
        <!-- Top Right Navy Glow -->
        <div class="absolute -top-[10%] -right-[5%] w-[50%] h-[60%] rounded-full bg-linear-to-bl from-[#0A4F86]/40 via-[#0A4F86]/10 to-transparent blur-[120px] animate-pulse duration-3000"></div>
        <!-- Bottom Left Orange Glow -->
        <div class="absolute -bottom-[10%] -left-[10%] w-[40%] h-[50%] rounded-full bg-linear-to-tr from-[#F47920]/30 via-[#F47920]/5 to-transparent blur-[120px] animate-pulse duration-3000" style="animation-delay: 2s;"></div>
        <!-- Center Subtle Blue -->
        <div class="absolute top-[30%] left-[30%] w-[30%] h-[40%] rounded-full bg-[#0A4F86]/20 blur-[150px]"></div>
    </div>
<main class="relative z-10 flex items-center justify-center min-h-screen pt-28 pb-12">
        <div class="container mx-auto px-5 md:px-12 lg:px-24 flex flex-col-reverse lg:flex-row items-center gap-12 lg:gap-12">

            <!-- Left Column: Text & CTA -->
            <div class="w-full lg:w-5/12 flex flex-col items-start text-left mt-8 lg:mt-0">
                <!-- Glowing Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-slate-50 dark:bg-white/5 backdrop-blur-md border border-[#F47920]/30 shadow-[0_0_15px_rgba(244,121,32,0.15)] text-[#F47920] text-xs font-black tracking-widest uppercase mb-6 sm:mb-8">
                    <span class="relative flex w-2 h-2 sm:w-2.5 sm:h-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#F47920] opacity-75"></span>
                        <span class="relative inline-flex rounded-full w-2 h-2 sm:w-2.5 sm:h-2.5 bg-[#F47920]"></span>
                    </span>
                    ReceivingPKT v2.0
                </div>

                <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-black leading-[1.1] text-slate-800 dark:text-white mb-6 tracking-tight">
                    Otomasi Logistik <br class="hidden sm:block">
                    <span class="text-transparent bg-clip-text bg-linear-to-r from-[#F47920] to-[#ff9b52] relative inline-block">
                        Inventaris
                        <svg class="absolute w-full h-2 sm:h-3 -bottom-2 left-0 text-[#0A4F86]/80 dark:text-[#F47920]/80" viewBox="0 0 100 10" preserveAspectRatio="none">
                            <path d="M0 5 Q 50 10 100 5" stroke="currentColor" stroke-width="4" fill="none" stroke-linecap="round" />
                        </svg>
                    </span>
                </h1>

                <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 mb-10 max-w-lg leading-relaxed font-medium">
                    Platform terpadu untuk memantau pergerakan material, mempercepat proses administrasi di <span class="text-[#F47920] font-bold">receiving</span>, dan memastikan akurasi data gudang secara presisi.
                </p>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto">
                    <a href="{{ filament()->getLoginUrl() }}"
                        class="w-full sm:w-auto flex items-center justify-center gap-2 px-8 py-4 text-sm sm:text-base font-bold text-white bg-linear-to-r from-[#F47920] to-[#BE5A27] rounded-2xl shadow-[0_10px_30px_-10px_rgba(244,121,32,0.8)] hover:shadow-[0_15px_40px_-10px_rgba(244,121,32,1)] hover:-translate-y-1 transition-all duration-300">
                        Masuk Dashboard
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                    </a>
                    <a href="{{ route('frontend.list-material') }}" wire:navigate class="w-full sm:w-auto flex items-center justify-center px-8 py-4 text-sm sm:text-base font-bold text-slate-700 dark:text-slate-200 glass-btn rounded-2xl">
                        Daftar PD & Non-Stock
                    </a>
                </div>

                <!-- Glass Cards -->
                <div class="mt-12 sm:mt-16 w-full grid grid-cols-3 gap-3 sm:gap-5 max-w-md">
                    <!-- Service Card 1 -->
                    <div class="glass-panel p-4 sm:p-5">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-[#0A4F86]/10 dark:bg-[#0A4F86]/50 border border-[#0A4F86]/20 dark:border-[#0A4F86] flex items-center justify-center mb-3">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-[#0A4F86] dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        </div>
                        <p class="text-lg sm:text-xl font-black text-slate-800 dark:text-white">ZSM</p>
                        <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest mt-1">Support</p>
                    </div>

                    <!-- Service Card 2 -->
                    <div class="glass-panel p-4 sm:p-5">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-[#F47920]/10 dark:bg-[#F47920]/20 border border-[#F47920]/30 dark:border-[#F47920]/50 flex items-center justify-center mb-3">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-[#F47920]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <p class="text-lg sm:text-xl font-black text-slate-800 dark:text-white">ZSP</p>
                        <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest mt-1">Sparepart</p>
                    </div>

                    <!-- Service Card 3 -->
                    <div class="glass-panel p-4 sm:p-5">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-[#0A4F86]/10 dark:bg-[#0A4F86]/50 border border-[#0A4F86]/20 dark:border-[#0A4F86] flex items-center justify-center mb-3">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-[#0A4F86] dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                        </div>
                        <p class="text-lg sm:text-xl font-black text-slate-800 dark:text-white">ZRM</p>
                        <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest mt-1">Raw Mat.</p>
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
        <div x-data="{ open: true }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 sm:p-0" x-transition.opacity style="display: none;">
            <div @click.away="open = false" class="bg-white dark:bg-slate-800 rounded-3xl p-4 sm:p-8 max-w-lg w-full mx-auto shadow-2xl relative border border-slate-200 dark:border-slate-700 max-h-[85vh] sm:max-h-[90vh] overflow-y-auto scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-600" x-transition.scale.origin.bottom>
                <!-- Close Button -->
                <button @click="open = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
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
                        <p class="text-sm sm:text-base">Mokondo AI Receiving PKT v2.0 saat ini telah aktif! Asisten chatbot cerdas kami siap membantu Anda mengecek status barang dan memandu proses pengambilan barang di gudang dengan cepat dan mudah.</p>

                        <div class="mt-4 sm:mt-6 relative group overflow-hidden rounded-2xl">
                            <!-- Animated Glow Background -->
                            <div class="absolute inset-0 bg-gradient-to-r from-[#F47920]/20 via-amber-400/20 to-[#0A4F86]/20 blur-xl group-hover:blur-2xl transition-all duration-500"></div>
                            
                            <div class="relative p-4 sm:p-6 bg-white/60 dark:bg-slate-800/60 backdrop-blur-md border border-slate-200/50 dark:border-white/10 flex flex-col items-center justify-center text-center rounded-2xl shadow-lg">
                                <div class="w-10 h-10 sm:w-14 sm:h-14 bg-gradient-to-br from-[#F47920]/20 to-transparent border border-[#F47920]/30 rounded-2xl flex items-center justify-center mb-2 sm:mb-4 transform group-hover:scale-110 transition-transform duration-300 shadow-inner">
                                    <svg class="w-5 h-5 sm:w-7 sm:h-7 text-[#F47920]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                                </div>
                                <h4 class="text-sm sm:text-lg font-black text-slate-800 dark:text-white mb-1.5 sm:mb-2 tracking-tight">Baru Pertama Kali?</h4>
                                <p class="text-[11px] sm:text-sm text-slate-600 dark:text-slate-300 mb-4 sm:mb-6 max-w-sm">
                                    Ikuti panduan interaktif singkat untuk mengetahui cara mudah melacak material Anda dengan asisten cerdas ALEX.
                                </p>
                                <button @click="open = false; startAiTour()" class="flex items-center justify-center w-full sm:w-auto gap-2 px-4 py-2 sm:px-6 sm:py-3 bg-gradient-to-r from-[#F47920] to-[#BE5A27] text-white text-xs sm:text-sm font-bold rounded-xl shadow-[0_8px_20px_-6px_rgba(244,121,32,0.6)] hover:shadow-[0_12px_25px_-6px_rgba(244,121,32,0.8)] transition-all hover:-translate-y-1">
                                    <span>Mulai Panduan Interaktif</span>
                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"></path></svg>
                                </button>
                            </div>
                        </div>
                    @else
                        <p class="text-sm sm:text-base">Mokondo AI Receiving PKT v2.0 saat ini sedang dalam masa pemeliharaan dan pelatihan Model (Training). Layanan chatbot cerdas untuk pengecekan status dan pengambilan barang sementara tidak dapat digunakan. Kami mohon maaf atas ketidaknyamanan ini.</p>
                    @endif
                </div>

                <button @click="open = false" class="w-full py-3 px-4 bg-slate-800 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 text-white font-bold rounded-xl transition-colors">
                    Mengerti
                </button>
            </div>
        </div>
    </main>

    <!-- Driver.js for Interactive Tour -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css"/>
    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>
    <style>
        /* Custom styling untuk Driver.js */
        .driverjs-theme {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            border-radius: 1rem !important;
            border: 1px solid rgba(255,255,255,0.2) !important;
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1), 0 0 20px rgba(244, 121, 32, 0.1) !important;
            font-family: inherit !important;
            color: #334155 !important;
            padding: 1.25rem !important;
            max-width: 320px !important;
            z-index: 2147483647 !important;
            pointer-events: auto !important;
            transform: translateZ(0) !important; /* Force iOS Safari compositing layer */
            -webkit-transform: translateZ(0) !important;
        }

        .dark .driverjs-theme {
            background: rgba(30, 41, 59, 0.95) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #f8fafc !important;
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.5), 0 0 20px rgba(244, 121, 32, 0.15) !important;
        }

        .driverjs-theme .driver-popover-title {
            font-size: 1.125rem !important;
            font-weight: 800 !important;
            color: #F47920 !important;
            margin-bottom: 0.5rem !important;
        }

        .driverjs-theme .driver-popover-description {
            font-size: 0.875rem !important;
            line-height: 1.5 !important;
            color: #475569 !important;
            margin-bottom: 1rem !important;
        }

        .dark .driverjs-theme .driver-popover-description {
            color: #cbd5e1 !important;
        }

        .driverjs-theme .driver-popover-footer {
            margin-top: 1rem !important;
        }

        .driverjs-theme .driver-popover-progress-text {
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            color: #94a3b8 !important;
        }

        .driverjs-theme button.driver-popover-next-btn, 
        .driverjs-theme button.driver-popover-next-btn:focus,
        .driverjs-theme button.driver-popover-done-btn,
        .driverjs-theme button.driver-popover-done-btn:focus {
            background: linear-gradient(to right, #F47920, #BE5A27) !important;
            color: white !important;
            border: none !important;
            border-radius: 0.75rem !important;
            padding: 0.5rem 1rem !important;
            font-size: 0.875rem !important;
            font-weight: 700 !important;
            text-shadow: none !important;
            box-shadow: 0 4px 10px rgba(244, 121, 32, 0.3) !important;
            transition: all 0.3s ease !important;
            cursor: pointer !important;
            touch-action: manipulation !important;
            -webkit-appearance: none !important;
            -webkit-user-select: none !important;
            user-select: none !important;
            -webkit-tap-highlight-color: transparent !important;
            pointer-events: auto !important;
            z-index: 2147483647 !important;
            position: relative !important;
        }

        .driverjs-theme button.driver-popover-next-btn:hover,
        .driverjs-theme button.driver-popover-done-btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 15px rgba(244, 121, 32, 0.4) !important;
        }

        .driverjs-theme button.driver-popover-prev-btn,
        .driverjs-theme button.driver-popover-prev-btn:focus {
            background: transparent !important;
            color: #64748b !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 0.75rem !important;
            padding: 0.5rem 1rem !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            text-shadow: none !important;
            transition: all 0.3s ease !important;
            cursor: pointer !important;
            touch-action: manipulation !important;
            -webkit-appearance: none !important;
            -webkit-user-select: none !important;
            user-select: none !important;
            -webkit-tap-highlight-color: transparent !important;
            pointer-events: auto !important;
            z-index: 2147483647 !important;
            position: relative !important;
        }

        .dark .driverjs-theme button.driver-popover-prev-btn {
            color: #cbd5e1 !important;
            border-color: #475569 !important;
        }

        .driverjs-theme button.driver-popover-prev-btn:hover {
            background: #f1f5f9 !important;
            color: #1e293b !important;
        }

        .dark .driverjs-theme button.driver-popover-prev-btn:hover {
            background: rgba(255,255,255,0.1) !important;
            color: white !important;
        }
        
        /* Driver.js close button */
        .driverjs-theme button.driver-popover-close-btn {
            color: #94a3b8 !important;
            transition: color 0.3s !important;
            cursor: pointer !important;
            touch-action: manipulation !important;
            z-index: 2147483647 !important;
        }
        
        .driverjs-theme button.driver-popover-close-btn:hover {
            color: #F47920 !important;
        }

        /* Mobile Adjustments for Driver.js */
        @media (max-width: 640px) {
            .driverjs-theme {
                padding: 1rem !important;
                max-width: calc(100vw - 2rem) !important;
            }
            .driverjs-theme .driver-popover-title {
                font-size: 1rem !important;
            }
            .driverjs-theme .driver-popover-description {
                font-size: 0.8125rem !important;
            }
            .driverjs-theme .driver-popover-footer {
                margin-top: 0.75rem !important;
            }
            .driverjs-theme button.driver-popover-next-btn,
            .driverjs-theme button.driver-popover-done-btn,
            .driverjs-theme button.driver-popover-prev-btn {
                padding: 0.5rem 0.8rem !important;
                font-size: 0.75rem !important;
            }
        }
    </style>
    <script>
        function startAiTour() {
            const driver = window.driver.js.driver;
            const driverObj = driver({
                popoverClass: 'driverjs-theme',
                showProgress: true,
                animate: true,
                nextBtnText: 'Lanjut',
                prevBtnText: 'Kembali',
                doneBtnText: 'Selesai',
                steps: [
                    {
                        element: '#tour-chatbot-button',
                        popover: {
                            title: '1. Buka Chatbot',
                            description: 'Klik area mana saja untuk membuka jendela Asisten AI.',
                            side: 'left',
                            align: 'end',
                            onNextClick: () => {
                                const chatBtn = document.querySelector('#tour-chatbot-button button');
                                const chatWindow = document.querySelector('#tour-chatbot-window');
                                if (chatBtn && chatWindow && chatWindow.style.display === 'none') {
                                    chatBtn.click(); // Memicu request Livewire ke server
                                    
                                    // Polling untuk menunggu sampai Livewire selesai dan elemen muncul
                                    let attempts = 0;
                                    let checkExist = setInterval(() => {
                                        attempts++;
                                        if (chatWindow.style.display !== 'none') {
                                            clearInterval(checkExist);
                                            // Beri jeda 450ms untuk animasi slide-up AlpineJS selesai bergerak
                                            setTimeout(() => {
                                                driverObj.moveNext();
                                            }, 450);
                                        } else if (attempts > 50) { // Maksimal tunggu 5 detik
                                            clearInterval(checkExist);
                                            driverObj.moveNext();
                                        }
                                    }, 100);
                                } else {
                                    driverObj.moveNext();
                                }
                            }
                        },
                        onHighlighted: (element) => {
                            // Tangkap klik KHUSUS pada elemen yang disorot (tombol chatbot)
                            if (element) {
                                const advanceStep = () => {
                                    element.removeEventListener('click', advanceStep);
                                    element.removeEventListener('touchstart', advanceStep);
                                    const nextBtn = document.querySelector('.driver-popover-next-btn');
                                    if (nextBtn) nextBtn.click();
                                };
                                setTimeout(() => {
                                    element.addEventListener('click', advanceStep);
                                    element.addEventListener('touchstart', advanceStep, { passive: true });
                                }, 100);
                            }
                        }
                    },
                    {
                        element: '#tour-chatbot-input',
                        popover: {
                            title: '2. Ketik Pertanyaan',
                            description: 'Ketikkan nomor PO, DO, atau MIR di sini, lalu tekan Enter atau klik tombol kirim.',
                            side: 'top',
                            align: 'center',
                            onPrevClick: () => {
                                const chatBtn = document.querySelector('#tour-chatbot-button button');
                                const chatWindow = document.querySelector('#tour-chatbot-window');
                                if (chatBtn && chatWindow && chatWindow.style.display !== 'none') {
                                    chatBtn.click();
                                    
                                    let attempts = 0;
                                    let checkExist = setInterval(() => {
                                        attempts++;
                                        if (chatWindow.style.display === 'none') {
                                            clearInterval(checkExist);
                                            setTimeout(() => {
                                                driverObj.movePrevious();
                                            }, 450);
                                        } else if (attempts > 50) {
                                            clearInterval(checkExist);
                                            driverObj.movePrevious();
                                        }
                                    }, 100);
                                } else {
                                    driverObj.movePrevious();
                                }
                            }
                        }
                    },
                    {
                        element: '#tour-chatbot-window',
                        popover: {
                            title: '3. Jawaban AI',
                            description: 'AI akan mencari data di sistem SAP/Gudang dan memberikan status material secara real-time di sini.',
                            side: 'left',
                            align: 'start'
                        }
                    }
                ],
                onDestroyStarted: () => {
                    // Jika user klik area overlay (di luar popover) pada Step 1, paksa lanjut!
                    if (driverObj.hasNextStep() && driverObj.getActiveIndex() === 0) {
                        const nextBtn = document.querySelector('.driver-popover-next-btn');
                        if (nextBtn) {
                            nextBtn.click();
                            return; // Batalkan proses penutupan tour
                        }
                    }

                    const chatBtn = document.querySelector('#tour-chatbot-button button');
                    const chatWindow = document.querySelector('#tour-chatbot-window');
                    if (chatBtn && chatWindow && chatWindow.style.display !== 'none') {
                        chatBtn.click();
                    }
                    driverObj.destroy();
                }
            });
            
            setTimeout(() => {
                driverObj.drive();
            }, 300);
        }
    </script>