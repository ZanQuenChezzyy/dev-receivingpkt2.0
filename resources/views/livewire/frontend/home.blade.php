<div class="relative w-full min-h-screen selection:bg-[#F47920] selection:text-white overflow-hidden">

    <!-- 1. HERO SECTION -->
    <x-frontend.hero />



    <!-- 3. CORE SOLUTIONS & BENTO GRID SECTION -->
    <section id="fitur-layanan" class="relative z-10 py-24 px-5 md:px-12 lg:px-24">


        <div class="container mx-auto max-w-7xl relative z-10">
            <!-- Section Header -->
            <div class="text-center max-w-3xl mx-auto mb-12">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-[#F47920]/10 border border-[#F47920]/20 text-[#F47920] text-xs font-bold tracking-widest uppercase mb-4">
                    Bagian Receiving • Dept. PP&amp;P
                </div>
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-800 dark:text-white tracking-tight mb-5">
                    3 Pilar Utama &amp; Ekosistem <br class="hidden sm:inline">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#F47920] via-orange-400 to-[#BE5A27]">Receiving Pupuk Kalimantan Timur</span>
                </h2>
                <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 font-medium">
                    Mengintegrasikan tiga fungsi utama Bagian Receiving (Dept. PP&amp;P): <strong>Penerimaan Barang</strong>, <strong>Invoicing Barang</strong>, serta <strong>Penerimaan &amp; Invoicing Jasa (Service Acceptance)</strong> dalam satu ekosistem digital.
                </p>
            </div>

            <!-- 3 PILAR UTAMA PROSES KERJA RECEIVING (Sesuai Presentasi Resmi Receiving Dept. PP&P) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-10">
                <!-- Pilar 1: Penerimaan Barang -->
                <a href="#alur-proses" onclick="window.dispatchEvent(new CustomEvent('select-pillar', { detail: 'barang' }))"
                    class="glass-panel p-6 rounded-3xl border border-emerald-500/30 dark:border-emerald-500/20 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group relative overflow-hidden">
                    <div class="absolute -right-8 -top-8 w-28 h-28 rounded-full bg-emerald-500/15 blur-2xl group-hover:scale-125 transition-transform"></div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/25">
                            Pilar 01 • Fisik &amp; Mutu
                        </span>
                        <svg class="w-5 h-5 text-emerald-500 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </div>
                    <h3 class="text-lg sm:text-xl font-black text-slate-800 dark:text-white mb-1.5">Penerimaan Barang</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Terima barang sesuai PO, pengajuan QC (ISTEK 2 / Lab &amp; PPE), penerbitan <strong>GRS</strong> (lolos QC), penerbitan <strong>RDTV</strong> (barang tolak), hingga pengiriman ke Gudang Penyimpanan.
                    </p>
                </a>

                <!-- Pilar 2: Invoicing Barang -->
                <a href="#alur-proses" onclick="window.dispatchEvent(new CustomEvent('select-pillar', { detail: 'invoicing_barang' }))"
                    class="glass-panel p-6 rounded-3xl border border-blue-500/30 dark:border-blue-500/20 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group relative overflow-hidden">
                    <div class="absolute -right-8 -top-8 w-28 h-28 rounded-full bg-[#0A4F86]/20 blur-2xl group-hover:scale-125 transition-transform"></div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-blue-500/15 text-[#0A4F86] dark:text-blue-300 border border-blue-500/25">
                            Pilar 02 • Tagihan Material
                        </span>
                        <svg class="w-5 h-5 text-[#0A4F86] dark:text-blue-400 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </div>
                    <h3 class="text-lg sm:text-xl font-black text-slate-800 dark:text-white mb-1.5">Invoicing Barang</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Penyampaian GRS ke vendor, penerimaan &amp; verifikasi 16 berkas kelengkapan tagihan (<strong>sebelum Tgl 20</strong>), input &amp; approval sistem <strong>Mitraku</strong> hingga pembayaran di Keuangan PKT.
                    </p>
                </a>

                <!-- Pilar 3: Penerimaan & Invoicing Jasa -->
                <a href="#alur-proses" onclick="window.dispatchEvent(new CustomEvent('select-pillar', { detail: 'jasa' }))"
                    class="glass-panel p-6 rounded-3xl border border-teal-500/30 dark:border-teal-500/20 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group relative overflow-hidden">
                    <div class="absolute -right-8 -top-8 w-28 h-28 rounded-full bg-teal-500/15 blur-2xl group-hover:scale-125 transition-transform"></div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-teal-500/15 text-teal-700 dark:text-teal-300 border border-teal-500/25">
                            Pilar 03 • Pekerjaan Jasa
                        </span>
                        <svg class="w-5 h-5 text-teal-500 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </div>
                    <h3 class="text-lg sm:text-xl font-black text-slate-800 dark:text-white mb-1.5">Penerimaan &amp; Invoicing Jasa</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Verifikasi berkas pekerjaan jasa (BAPP, SIK, BATE, Order Kerja, Invoice), proses input penagihan via sistem SAP, dan penerbitan <strong>Service Acceptance (SA)</strong>.
                    </p>
                </a>
            </div>

            <!-- Bento Grid Layout (3x3 Balanced Grid) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- Card 1 (Large - Spans 2 Cols): Pelacakan PO & Dokumen Vendor Mandiri -->
                <div class="lg:col-span-2 glass-panel p-8 sm:p-10 rounded-3xl relative overflow-hidden group hover:shadow-2xl transition-all duration-500 border border-white/80 dark:border-white/10 flex flex-col justify-between">
                    <div class="absolute -top-12 -right-12 w-56 h-56 bg-gradient-to-br from-[#F47920]/20 via-[#0A4F86]/15 to-transparent rounded-full blur-3xl group-hover:scale-125 transition-transform duration-700"></div>

                    <div class="relative z-10 flex flex-col justify-between h-full">
                        <div>
                            <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#F47920] to-[#BE5A27] text-white flex items-center justify-center shadow-lg shadow-orange-500/30 shrink-0">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#F47920]/10 text-[#F47920] dark:text-orange-400 border border-[#F47920]/20">
                                        <span class="w-2 h-2 rounded-full bg-[#F47920] animate-ping"></span>
                                        Portal Mandiri Rekanan
                                    </span>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                    Live PO Tracking 24/7
                                </span>
                            </div>

                            <h3 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white mb-3 tracking-tight">
                                Pelacakan PO &amp; Dokumen Vendor Mandiri
                            </h3>
                            <p class="text-slate-600 dark:text-slate-300 leading-relaxed text-sm sm:text-base max-w-2xl mb-6">
                                Portal monitoring terpadu bagi vendor rekanan PT Pupuk Kaltim untuk melacak status Purchase Order (PO) secara transparan. Pantau status kedatangan surat jalan (DO), progres pengujian laboratorium (COA), checklist 16 berkas kelengkapan penagihan, hingga status penerbitan nomor GRS resmi SAP tanpa harus datang ke gudang.
                            </p>

                            <!-- Quick Stats / Feature Highlights -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                                <div class="p-3 rounded-2xl bg-slate-900/5 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                                    <span class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 block">Status Dokumen</span>
                                    <span class="text-xs font-bold text-slate-800 dark:text-white mt-0.5 block">PO &amp; DO Realtime</span>
                                </div>
                                <div class="p-3 rounded-2xl bg-slate-900/5 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                                    <span class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 block">Batas Tagihan</span>
                                    <span class="text-xs font-bold text-slate-800 dark:text-white mt-0.5 block">Invoice Sblm Tgl 20</span>
                                </div>
                                <div class="p-3 rounded-2xl bg-slate-900/5 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                                    <span class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 block">Syarat Tagihan</span>
                                    <span class="text-xs font-bold text-slate-800 dark:text-white mt-0.5 block">Checklist 16 Berkas</span>
                                </div>
                                <div class="p-3 rounded-2xl bg-slate-900/5 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                                    <span class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 block">GRS &amp; RDTV</span>
                                    <span class="text-xs font-bold text-slate-800 dark:text-white mt-0.5 block">Unduh atau Lihat</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Button & Link -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-4 border-t border-slate-200/70 dark:border-white/10">
                            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Akses publik terbuka tanpa login — cukup gunakan Nomor PO</span>
                            </div>
                            <a href="{{ route('frontend.vendor.tracking') }}" wire:navigate
                                class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-xs sm:text-sm font-bold text-white bg-gradient-to-r from-[#F47920] to-[#BE5A27] shadow-md hover:shadow-xl hover:scale-105 active:scale-95 transition-all shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <span>Buka Pelacakan PO Vendor</span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Verifikasi DO & Kedatangan Fisik (1 Col) -->
                <div class="glass-panel p-8 rounded-3xl relative overflow-hidden group hover:shadow-2xl transition-all duration-500 flex flex-col justify-between border border-white/80 dark:border-white/10">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-[#0A4F86]/15 dark:bg-[#0A4F86]/30 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-700"></div>

                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#0A4F86] to-[#052b49] text-white flex items-center justify-center shadow-lg shadow-blue-900/30">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                </svg>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-500/10 text-blue-600 dark:text-blue-300 border border-blue-500/20">
                                QR Code Validated
                            </span>
                        </div>
                        <h3 class="text-xl font-black text-slate-800 dark:text-white mb-2 tracking-tight">
                            Verifikasi DO &amp; Pintu Receiving
                        </h3>
                        <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed mb-4">
                            Penerimaan barang sesuai PO, pemeriksaan lampiran vendor, dan inspeksi fisik di pos gudang receiving. Dilengkapi validasi toleransi kuantitas, cetak QR slip kedatangan, dan penandaan staging lokasi penyimpanan.
                        </p>
                    </div>

                    <div class="relative z-10 flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400 pt-3 border-t border-slate-200/70 dark:border-white/10">
                        <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Validasi Toleransi Qty PO &amp; QR 103</span>
                    </div>
                </div>

                <!-- Card 3: Invoicing Barang & Jasa (Mitraku & SA) -->
                <div class="glass-panel p-8 rounded-3xl relative overflow-hidden group hover:shadow-2xl transition-all duration-500 flex flex-col justify-between border border-white/80 dark:border-white/10">
                    <div class="absolute -top-10 -left-10 w-40 h-40 bg-amber-500/10 dark:bg-amber-500/20 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-700"></div>

                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-700 text-white flex items-center justify-center shadow-lg shadow-amber-900/30">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-600 dark:text-amber-300 border border-amber-500/20">
                                Mitraku &amp; SAP SA
                            </span>
                        </div>
                        <h3 class="text-xl font-black text-slate-800 dark:text-white mb-2 tracking-tight">
                            Invoicing Barang &amp; Jasa (SA)
                        </h3>
                        <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed mb-4">
                            Verifikasi kelengkapan dokumen penagihan barang (<strong>sebelum Tgl 20</strong>) ke sistem <strong>Mitraku</strong>, serta penginputan penagihan pekerjaan jasa melalui SAP hingga terbit <strong>Service Acceptance (SA)</strong>.
                        </p>
                    </div>

                    <div class="relative z-10 flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400 pt-3 border-t border-slate-200/70 dark:border-white/10">
                        <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Integrasi Mitraku, SAP SA &amp; Keuangan PKT</span>
                    </div>
                </div>

                <!-- Card 4: Quality Control & Transmittal Lab -->
                <div class="glass-panel p-8 rounded-3xl relative overflow-hidden group hover:shadow-2xl transition-all duration-500 flex flex-col justify-between border border-white/80 dark:border-white/10">
                    <div class="absolute -top-10 -left-10 w-40 h-40 bg-purple-500/10 dark:bg-purple-500/20 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-700"></div>

                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-600 to-indigo-800 text-white flex items-center justify-center shadow-lg shadow-purple-900/30">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-purple-500/10 text-purple-600 dark:text-purple-300 border border-purple-500/20">
                                ISTEK 2 • Lab • PPE
                            </span>
                        </div>
                        <h3 class="text-xl font-black text-slate-800 dark:text-white mb-2 tracking-tight">
                            Permintaan QC &amp; Uji Lab (COA)
                        </h3>
                        <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed mb-4">
                            Pengajuan QC sparepart ke <strong>ISTEK 2</strong> serta pengajuan QC/JOR chemical &amp; bahan baku ke <strong>LAB</strong> (penerbitan COA) dan evaluasi kualitas oleh <strong>PPE</strong>.
                        </p>
                    </div>

                    <div class="relative z-10 flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400 pt-3 border-t border-slate-200/70 dark:border-white/10">
                        <svg class="w-4 h-4 text-purple-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Monitoring Chemical, Karung, NPK &amp; Sparepart</span>
                    </div>
                </div>

                <!-- Card 5: Distribusi & Transmittal Gudang Tujuan -->
                <div class="glass-panel p-8 rounded-3xl relative overflow-hidden group hover:shadow-2xl transition-all duration-500 flex flex-col justify-between border border-white/80 dark:border-white/10">
                    <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-cyan-500/10 dark:bg-cyan-500/20 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-700"></div>

                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-cyan-600 to-blue-800 text-white flex items-center justify-center shadow-lg shadow-cyan-900/30">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                                </svg>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-cyan-500/10 text-cyan-600 dark:text-cyan-300 border border-cyan-500/20">
                                28+ Gudang Tujuan
                            </span>
                        </div>
                        <h3 class="text-xl font-black text-slate-800 dark:text-white mb-2 tracking-tight">
                            Pengiriman Gudang Penyimpanan
                        </h3>
                        <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed mb-4">
                            Pengiriman barang yang telah lolos QC/GRS ke 28+ gudang penyimpanan (Gudang SP 1, SP 2, Laydown dan Lainnya). Terintegrasi dengan berita acara serah terima dan nomor transmittal.
                        </p>
                    </div>

                    <div class="relative z-10 flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400 pt-3 border-t border-slate-200/70 dark:border-white/10">
                        <svg class="w-4 h-4 text-cyan-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Serah Terima Fisik Antargudang</span>
                    </div>
                </div>

                <!-- Card 6: Pengambilan Barang MIR Online -->
                <div class="glass-panel p-8 rounded-3xl relative overflow-hidden group hover:shadow-2xl transition-all duration-500 flex flex-col justify-between border border-white/80 dark:border-white/10">
                    <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-[#F47920]/15 dark:bg-[#F47920]/25 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-700"></div>

                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#F47920] to-[#BE5A27] text-white flex items-center justify-center shadow-lg shadow-orange-500/30">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-[#F47920]/10 text-[#F47920] border border-[#F47920]/20">
                                Paperless MIR
                            </span>
                        </div>
                        <h3 class="text-xl font-black text-slate-800 dark:text-white mb-2 tracking-tight">
                            Permohonan Pengambilan (MIR)
                        </h3>
                        <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed mb-6">
                            Unit kerja pemohon dapat mengajukan Material Issue Request secara mandiri, lengkap dengan validasi stok BOH gudang dan tanda tangan digital terverifikasi.
                        </p>
                    </div>

                    <a href="{{ route('frontend.mir.create') }}" wire:navigate
                        class="relative z-10 inline-flex items-center justify-between w-full p-3.5 rounded-xl bg-slate-900/5 dark:bg-white/5 hover:bg-[#F47920] dark:hover:bg-[#F47920] text-slate-800 dark:text-white hover:text-white transition-all duration-300 font-bold text-xs shadow-sm">
                        <span>Buat Permohonan MIR Sekarang</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                <!-- Card 7: Good Receipt Slip (GRS / RDTV) -->
                <div class="glass-panel p-8 rounded-3xl relative overflow-hidden group hover:shadow-2xl transition-all duration-500 flex flex-col justify-between border border-white/80 dark:border-white/10">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-emerald-500/10 dark:bg-emerald-500/20 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-700"></div>

                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-800 text-white flex items-center justify-center shadow-lg shadow-emerald-900/30">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-300 border border-emerald-500/20">
                                GRS 105 &amp; RDTV
                            </span>
                        </div>
                        <h3 class="text-xl font-black text-slate-800 dark:text-white mb-2 tracking-tight">
                            Good Receive Slip (GRS / RDTV)
                        </h3>
                        <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed mb-4">
                            Penerbitan GRS untuk barang lolos QC (rasio kelulusan ~95,9%) serta penerbitan RDTV untuk barang QC tolak lengkap dengan mekanisme Surat Klarifikasi Kebenaran Barang.
                        </p>
                    </div>

                    <div class="relative z-10 flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400 pt-3 border-t border-slate-200/70 dark:border-white/10">
                        <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Sinkron dengan Approval AVP &amp; VP PP&amp;P</span>
                    </div>
                </div>

                <!-- Card 8: Asisten Pintar ALEX (AI) -->
                <div class="glass-panel p-8 rounded-3xl relative overflow-hidden group hover:shadow-2xl transition-all duration-500 flex flex-col justify-between border border-white/80 dark:border-white/10">
                    <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-[#F47920]/20 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-700"></div>

                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#F47920] to-[#BE5A27] text-white flex items-center justify-center shadow-lg shadow-orange-500/30">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                </svg>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $aiSettingActive ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20' }}">
                                {{ $aiSettingActive ? 'AI Online' : 'AI Training' }}
                            </span>
                        </div>
                        <h3 class="text-xl font-black text-slate-800 dark:text-white mb-2 tracking-tight">
                            Asisten Pintar ALEX (AI)
                        </h3>
                        <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed mb-6">
                            Layanan chatbot 24/7 untuk menjawab status nomor PO, surat jalan DO, dan memandu alur kerja pengambilan barang secara otomatis.
                        </p>
                    </div>

                    <button type="button" onclick="startAiTour()"
                        class="relative z-10 inline-flex items-center justify-between w-full p-3.5 rounded-xl bg-slate-900/5 dark:bg-white/5 hover:bg-[#F47920] dark:hover:bg-[#F47920] text-slate-800 dark:text-white hover:text-white transition-all duration-300 font-bold text-xs shadow-sm">
                        <span>Ikuti Tur Interaktif AI</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>

            </div>
        </div>
    </section>

    <!-- 4. INTERACTIVE RECEIVING WORKFLOWS & DOCUMENT REQUIREMENTS (SESUAI PRESENTASI RESMI RECEIVING DEPT. PP&P) -->
    <section id="alur-proses"
        x-data="{ activePillar: 'barang', viewMode: 'flowchart', filterBranch: 'all', rdtvBranch: 'all', activeStep: 1 }"
        @select-pillar.window="activePillar = $event.detail"
        class="relative z-10 py-24 px-5 md:px-12 lg:px-24 bg-slate-100/60 dark:bg-slate-900/40 border-y border-slate-200/70 dark:border-white/5">
        <div class="container mx-auto max-w-7xl">
            <!-- Section Header -->
            <div class="text-center max-w-3xl mx-auto mb-10">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-[#0A4F86]/10 dark:bg-[#0A4F86]/30 border border-[#0A4F86]/20 text-[#0A4F86] dark:text-blue-300 text-xs font-bold tracking-widest uppercase mb-4">
                    SOP Resmi Bagian Receiving • Dept. PP&amp;P
                </div>
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-800 dark:text-white tracking-tight mb-5">
                    Proses Kerja &amp; Kelengkapan Dokumen <br class="hidden sm:inline">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#0A4F86] via-blue-500 to-[#F47920]">Receiving Pupuk Kalimantan Timur</span>
                </h2>
                <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 font-medium">
                    Pilih salah satu dari 3 pilar proses kerja Bagian Receiving di bawah ini untuk melihat bagan alur SOP lintas unit kerja serta daftar kelengkapan dokumen penagihan resmi.
                </p>
            </div>

            <!-- TOP-LEVEL 3 PILLAR TABS (Slide 2: Penerimaan Barang | Invoicing Barang | Penerimaan & Invoicing Jasa) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4 max-w-4xl mx-auto mb-10">
                <button type="button" @click="activePillar = 'barang'"
                    :class="activePillar === 'barang'
                        ? 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-xl shadow-emerald-900/25 ring-2 ring-emerald-400/40 scale-[1.01]'
                        : 'glass-panel text-slate-700 dark:text-slate-200 hover:border-emerald-500/40'"
                    class="p-4 sm:p-5 rounded-2xl flex items-center gap-3.5 text-left transition-all duration-300 cursor-pointer">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 font-black text-sm"
                        :class="activePillar === 'barang' ? 'bg-white/20 text-white' : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'">
                        01
                    </div>
                    <div class="min-w-0">
                        <span class="text-[10px] uppercase tracking-wider font-extrabold block opacity-80">Pilar Pertama</span>
                        <span class="text-sm sm:text-base font-black block truncate">Penerimaan Barang &amp; RDTV</span>
                    </div>
                </button>

                <button type="button" @click="activePillar = 'invoicing_barang'"
                    :class="activePillar === 'invoicing_barang'
                        ? 'bg-gradient-to-r from-[#0A4F86] to-blue-700 text-white shadow-xl shadow-blue-900/25 ring-2 ring-blue-400/40 scale-[1.01]'
                        : 'glass-panel text-slate-700 dark:text-slate-200 hover:border-blue-500/40'"
                    class="p-4 sm:p-5 rounded-2xl flex items-center gap-3.5 text-left transition-all duration-300 cursor-pointer">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 font-black text-sm"
                        :class="activePillar === 'invoicing_barang' ? 'bg-white/20 text-white' : 'bg-blue-500/15 text-[#0A4F86] dark:text-blue-400'">
                        02
                    </div>
                    <div class="min-w-0">
                        <span class="text-[10px] uppercase tracking-wider font-extrabold block opacity-80">Pilar Kedua</span>
                        <span class="text-sm sm:text-base font-black block truncate">Invoicing Barang (Mitraku)</span>
                    </div>
                </button>

                <button type="button" @click="activePillar = 'jasa'"
                    :class="activePillar === 'jasa'
                        ? 'bg-gradient-to-r from-[#F47920] to-[#BE5A27] text-white shadow-xl shadow-orange-900/25 ring-2 ring-orange-400/40 scale-[1.01]'
                        : 'glass-panel text-slate-700 dark:text-slate-200 hover:border-orange-500/40'"
                    class="p-4 sm:p-5 rounded-2xl flex items-center gap-3.5 text-left transition-all duration-300 cursor-pointer">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 font-black text-sm"
                        :class="activePillar === 'jasa' ? 'bg-white/20 text-white' : 'bg-[#F47920]/15 text-[#F47920]'">
                        03
                    </div>
                    <div class="min-w-0">
                        <span class="text-[10px] uppercase tracking-wider font-extrabold block opacity-80">Pilar Ketiga</span>
                        <span class="text-sm sm:text-base font-black block truncate">Penerimaan &amp; Invoicing Jasa</span>
                    </div>
                </button>
            </div>

            <!-- =========================================================================
                 PILAR 1: PENERIMAAN BARANG & ALUR BARANG TOLAK (RDTV) (SLIDE 3, 4, 5, 6)
                 ========================================================================= -->
            <div x-show="activePillar === 'barang'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

                <!-- 5 Aktivitas Utama Penerimaan Barang (Slide 3) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-8">
                    <div class="glass-panel p-4 rounded-2xl border-l-4 border-l-[#F47920] flex items-center gap-3">
                        <span class="w-7 h-7 rounded-full bg-[#F47920]/15 text-[#F47920] font-black text-xs flex items-center justify-center shrink-0">1</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-white leading-snug">Menerima Barang sesuai PO</span>
                    </div>
                    <div class="glass-panel p-4 rounded-2xl border-l-4 border-l-lime-500 flex items-center gap-3">
                        <span class="w-7 h-7 rounded-full bg-lime-500/15 text-lime-600 dark:text-lime-400 font-black text-xs flex items-center justify-center shrink-0">2</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-white leading-snug">Mengajukan Permintaan QC</span>
                    </div>
                    <div class="glass-panel p-4 rounded-2xl border-l-4 border-l-emerald-500 flex items-center gap-3">
                        <span class="w-7 h-7 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-black text-xs flex items-center justify-center shrink-0">3</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-white leading-snug">Menerbitkan GRS untuk Barang Lolos QC</span>
                    </div>
                    <button type="button" @click="viewMode = 'rdtv'; document.getElementById('alur-barang-tolak')?.scrollIntoView({ behavior: 'smooth', block: 'start' })" class="glass-panel p-4 rounded-2xl border-l-4 border-l-rose-500 flex items-center gap-3 text-left hover:border-rose-500/50 transition-all cursor-pointer">
                        <span class="w-7 h-7 rounded-full bg-rose-500/15 text-rose-600 dark:text-rose-400 font-black text-xs flex items-center justify-center shrink-0">4</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-white leading-snug">Menerbitkan RDTV untuk Barang QC Tolak &rarr;</span>
                    </button>
                    <div class="glass-panel p-4 rounded-2xl border-l-4 border-l-[#0A4F86] flex items-center gap-3">
                        <span class="w-7 h-7 rounded-full bg-[#0A4F86]/15 text-[#0A4F86] dark:text-blue-400 font-black text-xs flex items-center justify-center shrink-0">5</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-white leading-snug">Pengiriman Barang Lolos QC/GRS ke Gudang</span>
                    </div>
                </div>

                <!-- Sub-View Switcher Tabs for Pilar 1 -->
                <div class="flex flex-wrap items-center justify-center gap-3 mb-8">
                    <button @click="viewMode = 'flowchart'"
                        :class="viewMode === 'flowchart' ? 'bg-[#0A4F86] text-white shadow-lg shadow-blue-900/30 scale-102 ring-2 ring-blue-400/30' : 'glass-btn text-slate-700 dark:text-slate-300 hover:text-[#0A4F86]'"
                        class="px-5 py-2.5 rounded-2xl font-black text-xs sm:text-sm inline-flex items-center gap-2 transition-all duration-300 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                        </svg>
                        <span>Bagan Swimlane Penerimaan Barang (7 Unit)</span>
                    </button>
                    <button @click="viewMode = 'rdtv'"
                        :class="viewMode === 'rdtv' ? 'bg-red-600 text-white shadow-lg shadow-red-900/30 scale-102 ring-2 ring-red-400/30' : 'glass-btn text-slate-700 dark:text-slate-300 hover:text-red-500'"
                        class="px-5 py-2.5 rounded-2xl font-black text-xs sm:text-sm inline-flex items-center gap-2 transition-all duration-300 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Alur Proses Barang Tolak / RDTV (4 Unit)</span>
                    </button>
                    <button @click="viewMode = 'steps'"
                        :class="viewMode === 'steps' ? 'bg-[#F47920] text-white shadow-lg shadow-orange-500/30 scale-102 ring-2 ring-orange-400/30' : 'glass-btn text-slate-700 dark:text-slate-300 hover:text-[#F47920]'"
                        class="px-5 py-2.5 rounded-2xl font-black text-xs sm:text-sm inline-flex items-center gap-2 transition-all duration-300 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        <span>Panduan Tahapan Detail &amp; Matriks 7 Unit</span>
                    </button>
                </div>

            <!-- =========================================================
                 VIEW 1: BAGAN DIAGRAM SWIMLANE SOP RESMI (DIAGRAM DESIGN SPEC)
                 ========================================================= -->
            <div x-show="viewMode === 'flowchart'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">

                <!-- Interactive Highlight Filter & Style Legend -->
                <div class="glass-panel p-4 sm:p-5 rounded-2xl border border-white/80 dark:border-white/10 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mr-1 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#0A4F86]"></span>
                            Fokus Jalur:
                        </span>
                        <button @click="filterBranch = 'all'"
                            :class="filterBranch === 'all' ? 'bg-[#0A4F86] text-white shadow-sm ring-1 ring-blue-400/40' : 'bg-slate-200/70 dark:bg-white/5 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-white/10'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer">
                            Semua Alur
                        </button>
                        <button @click="filterBranch = 'chemical'"
                            :class="filterBranch === 'chemical' ? 'bg-red-600 text-white shadow-sm ring-1 ring-red-400/40' : 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 hover:bg-red-500/20'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            Chemical / Bahan Baku (Lab &amp; PPE)
                        </button>
                        <button @click="filterBranch = 'sparepart'"
                            :class="filterBranch === 'sparepart' ? 'bg-blue-600 text-white shadow-sm ring-1 ring-blue-400/40' : 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 hover:bg-blue-500/20'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            Sparepart Teknis (ISTEK 2)
                        </button>
                        <button @click="filterBranch = 'diterima'"
                            :class="filterBranch === 'diterima' ? 'bg-emerald-600 text-white shadow-sm ring-1 ring-emerald-400/40' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Diterima (GRS &amp; Gudang)
                        </button>
                        <button @click="filterBranch = 'ditolak'"
                            :class="filterBranch === 'ditolak' ? 'bg-amber-600 text-white shadow-sm ring-1 ring-amber-400/40' : 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20 hover:bg-amber-500/20'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Ditolak (RDTV &amp; Retur)
                        </button>
                    </div>

                    <div class="flex items-center gap-4 text-[11px] font-semibold text-slate-500 dark:text-slate-400 shrink-0">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded bg-[#0A4F86] inline-block"></span>
                            Unit Penerimaan (Focal Hub)
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded bg-emerald-500 inline-block"></span>
                            Lolos QC (GRS)
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded bg-red-500 inline-block"></span>
                            Ditolak (RDTV)
                        </span>
                    </div>
                </div>

                <!-- Editorial Diagram Container (cathrynlavery/diagram-design) -->
                <div class="glass-panel p-4 sm:p-6 rounded-3xl border border-white/80 dark:border-white/10 shadow-2xl relative overflow-hidden">
                    
                    <!-- Mobile Scroll Hint -->
                    <div class="flex lg:hidden items-center justify-between text-xs font-bold text-slate-500 dark:text-slate-400 mb-3 px-1">
                        <span class="inline-flex items-center gap-1.5 text-[#0A4F86] dark:text-blue-300">
                            <svg class="w-4 h-4 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            Geser horizontal untuk melihat bagan diagram 7 unit lengkap
                        </span>
                    </div>

                    <!-- Scoped Diagram-Design CSS & Tokens -->
                    <style>
                        #receiving-diagram,
                        #rdtv-diagram,
                        #invoicing-diagram {
                            --diagram-paper: #ffffff;
                            --diagram-ink: #0f172a;
                            --diagram-muted: #475569;
                            --diagram-soft: #64748b;
                            --diagram-rule: rgba(15, 23, 42, 0.08);
                            --diagram-rule-strong: rgba(15, 23, 42, 0.20);
                            --diagram-lane-even: rgba(248, 250, 252, 0.85);
                            --diagram-lane-odd: #ffffff;
                            --diagram-lane-focal: rgba(10, 79, 134, 0.04);
                            --diagram-dot: rgba(15, 23, 42, 0.08);
                            --diagram-node: #ffffff;
                            --diagram-node-stroke: rgba(15, 23, 42, 0.16);
                            --diagram-blue: #0A4F86;
                            --diagram-blue-tint: rgba(10, 79, 134, 0.08);
                            --diagram-tangerine: #F47920;
                            --diagram-green: #059669;
                            --diagram-green-tint: rgba(16, 185, 129, 0.09);
                            --diagram-red: #DC2626;
                            --diagram-red-tint: rgba(239, 68, 68, 0.08);
                            --diagram-chip: rgba(15, 23, 42, 0.06);
                        }

                        .dark #receiving-diagram,
                        .dark #rdtv-diagram,
                        .dark #invoicing-diagram {
                            --diagram-paper: #071321;
                            --diagram-ink: #f8fafc;
                            --diagram-muted: #94a3b8;
                            --diagram-soft: #64748b;
                            --diagram-rule: rgba(255, 255, 255, 0.08);
                            --diagram-rule-strong: rgba(255, 255, 255, 0.22);
                            --diagram-lane-even: rgba(13, 27, 46, 0.7);
                            --diagram-lane-odd: rgba(8, 19, 34, 0.9);
                            --diagram-lane-focal: rgba(10, 79, 134, 0.22);
                            --diagram-dot: rgba(255, 255, 255, 0.08);
                            --diagram-node: #0d1e33;
                            --diagram-node-stroke: rgba(255, 255, 255, 0.16);
                            --diagram-blue: #38bdf8;
                            --diagram-blue-tint: rgba(56, 189, 248, 0.12);
                            --diagram-tangerine: #fb923c;
                            --diagram-green: #34d399;
                            --diagram-green-tint: rgba(52, 211, 153, 0.12);
                            --diagram-red: #f87171;
                            --diagram-red-tint: rgba(248, 113, 113, 0.12);
                            --diagram-chip: rgba(255, 255, 255, 0.1);
                        }

                        #receiving-diagram text,
                        #rdtv-diagram text,
                        #invoicing-diagram text {
                            font-family: 'Inter', system-ui, -apple-system, sans-serif;
                        }
                        #receiving-diagram .mono,
                        #rdtv-diagram .mono,
                        #invoicing-diagram .mono {
                            font-family: 'Geist Mono', 'SF Mono', 'Cascadia Code', ui-monospace, Menlo, Consolas, monospace;
                        }
                        #receiving-diagram g,
                        #rdtv-diagram g,
                        #invoicing-diagram g {
                            transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        }
                        #receiving-diagram .node-group,
                        #rdtv-diagram .node-group,
                        #invoicing-diagram .node-group {
                            transition: transform 0.2s ease, opacity 0.3s ease;
                        }
                        #receiving-diagram .node-group:hover,
                        #rdtv-diagram .node-group:hover,
                        #invoicing-diagram .node-group:hover {
                            transform: translateY(-1.5px);
                        }
                    </style>

                    <div class="overflow-x-auto pb-4 custom-scrollbar">
                        <div class="min-w-[1100px] select-none">
                            <svg id="receiving-diagram" viewBox="0 0 1260 660" xmlns="http://www.w3.org/2000/svg" role="img" aria-labelledby="diagram-title diagram-desc" class="w-full h-auto block rounded-2xl border border-slate-200/80 dark:border-white/10">
                                <title id="diagram-title">Alur Proses Penerimaan Barang Gudang Receiving Pupuk Kaltim</title>
                                <desc id="diagram-desc">Diagram Swimlane SOP Resmi Penerimaan Barang Lintas 7 Unit Kerja Pupuk Kalimantan Timur</desc>

                                <defs>
                                    <!-- Editorial Dot Grid Background -->
                                    <pattern id="dot-grid" width="22" height="22" patternUnits="userSpaceOnUse">
                                        <circle cx="11" cy="11" r="0.85" fill="var(--diagram-dot)"/>
                                    </pattern>

                                    <!-- Directional Arrow Markers -->
                                    <marker id="arrow" markerWidth="8" markerHeight="6" refX="7" refY="3" orient="auto">
                                        <polygon points="0 0, 8 3, 0 6" fill="var(--diagram-muted)"/>
                                    </marker>
                                    <marker id="arrow-blue" markerWidth="8" markerHeight="6" refX="7" refY="3" orient="auto">
                                        <polygon points="0 0, 8 3, 0 6" fill="var(--diagram-blue)"/>
                                    </marker>
                                    <marker id="arrow-green" markerWidth="8" markerHeight="6" refX="7" refY="3" orient="auto">
                                        <polygon points="0 0, 8 3, 0 6" fill="var(--diagram-green)"/>
                                    </marker>
                                    <marker id="arrow-red" markerWidth="8" markerHeight="6" refX="7" refY="3" orient="auto">
                                        <polygon points="0 0, 8 3, 0 6" fill="var(--diagram-red)"/>
                                    </marker>
                                    <marker id="arrow-tangerine" markerWidth="8" markerHeight="6" refX="7" refY="3" orient="auto">
                                        <polygon points="0 0, 8 3, 0 6" fill="var(--diagram-tangerine)"/>
                                    </marker>
                                </defs>

                                <!-- Base Canvas Background -->
                                <rect width="100%" height="100%" fill="var(--diagram-paper)"/>
                                <rect width="100%" height="100%" fill="url(#dot-grid)" opacity="0.6"/>

                                <!-- 7 Vertical Swimlane Column Backgrounds -->
                                <rect x="0" y="44" width="180" height="556" fill="var(--diagram-lane-odd)"/>
                                <rect x="180" y="44" width="180" height="556" fill="var(--diagram-lane-even)"/>
                                <rect x="360" y="44" width="180" height="556" fill="var(--diagram-lane-focal)"/>
                                <rect x="540" y="44" width="180" height="556" fill="var(--diagram-lane-even)"/>
                                <rect x="720" y="44" width="180" height="556" fill="var(--diagram-lane-odd)"/>
                                <rect x="900" y="44" width="180" height="556" fill="var(--diagram-lane-even)"/>
                                <rect x="1080" y="44" width="180" height="556" fill="var(--diagram-lane-odd)"/>

                                <!-- Vertical Column Dividers (Hairline 0.8px) -->
                                <g stroke="var(--diagram-rule)" stroke-width="0.8">
                                    <line x1="180" y1="0" x2="180" y2="600"/>
                                    <line x1="360" y1="0" x2="360" y2="600" stroke="var(--diagram-blue)" stroke-opacity="0.35" stroke-width="1"/>
                                    <line x1="540" y1="0" x2="540" y2="600" stroke="var(--diagram-blue)" stroke-opacity="0.35" stroke-width="1"/>
                                    <line x1="720" y1="0" x2="720" y2="600"/>
                                    <line x1="900" y1="0" x2="900" y2="600"/>
                                    <line x1="1080" y1="0" x2="1080" y2="600"/>
                                </g>

                                <!-- Horizontal Divider below Header -->
                                <line x1="0" y1="44" x2="1260" y2="44" stroke="var(--diagram-rule-strong)" stroke-width="1"/>

                                <!-- ===================================================
                                     COLUMN HEADERS (7 UNIT KERJA)
                                     =================================================== -->
                                <g text-anchor="middle">
                                    <!-- 01 VENDOR -->
                                    <rect x="15" y="8" width="150" height="28" rx="4" fill="var(--diagram-chip)"/>
                                    <text x="90" y="21" class="mono" font-size="8.5" font-weight="700" letter-spacing="0.10em" fill="var(--diagram-ink)">01 · VENDOR</text>
                                    <text x="90" y="31.5" font-size="7" fill="var(--diagram-muted)">Rekanan / Supplier</text>

                                    <!-- 02 EXPEDITING -->
                                    <rect x="195" y="8" width="150" height="28" rx="4" fill="var(--diagram-chip)"/>
                                    <text x="270" y="21" class="mono" font-size="8.5" font-weight="700" letter-spacing="0.10em" fill="var(--diagram-ink)">02 · EXPEDITING</text>
                                    <text x="270" y="31.5" font-size="7" fill="var(--diagram-muted)">Pengendalian Logistik</text>

                                    <!-- 03 RECEIVING (Focal Hub) -->
                                    <rect x="375" y="6" width="150" height="32" rx="5" fill="var(--diagram-blue-tint)" stroke="var(--diagram-blue)" stroke-opacity="0.4" stroke-width="1"/>
                                    <text x="450" y="20" class="mono" font-size="9" font-weight="800" letter-spacing="0.10em" fill="var(--diagram-blue)">03 · RECEIVING</text>
                                    <text x="450" y="32.5" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-tangerine)">FOCAL HUB / SAP 103</text>

                                    <!-- 04 ISTEK 2 -->
                                    <rect x="555" y="8" width="150" height="28" rx="4" fill="var(--diagram-chip)"/>
                                    <text x="630" y="21" class="mono" font-size="8.5" font-weight="700" letter-spacing="0.10em" fill="var(--diagram-ink)">04 · ISTEK 2</text>
                                    <text x="630" y="31.5" font-size="7" fill="var(--diagram-muted)">QC Sparepart Teknis</text>

                                    <!-- 05 LAB -->
                                    <rect x="735" y="8" width="150" height="28" rx="4" fill="var(--diagram-chip)"/>
                                    <text x="810" y="21" class="mono" font-size="8.5" font-weight="700" letter-spacing="0.10em" fill="var(--diagram-ink)">05 · LAB</text>
                                    <text x="810" y="31.5" font-size="7" fill="var(--diagram-muted)">Uji Kimia &amp; COA</text>

                                    <!-- 06 PPE -->
                                    <rect x="915" y="8" width="150" height="28" rx="4" fill="var(--diagram-chip)"/>
                                    <text x="990" y="21" class="mono" font-size="8.5" font-weight="700" letter-spacing="0.10em" fill="var(--diagram-ink)">06 · PPE</text>
                                    <text x="990" y="31.5" font-size="7" fill="var(--diagram-muted)">Evaluasi Mutu Bahan</text>

                                    <!-- 07 GUDANG -->
                                    <rect x="1095" y="8" width="150" height="28" rx="4" fill="var(--diagram-chip)"/>
                                    <text x="1170" y="21" class="mono" font-size="8.5" font-weight="700" letter-spacing="0.10em" fill="var(--diagram-ink)">07 · GUDANG</text>
                                    <text x="1170" y="31.5" font-size="7" fill="var(--diagram-muted)">Penyimpanan Fisik</text>
                                                               <!-- ===================================================
                                     CONNECTORS (DRAWN FIRST - BEFORE BOXES)
                                     =================================================== -->
                                
                                <!-- 1. INTAKE CHAIN: VENDOR -> EXPEDITING -> RECEIVING -->
                                <g id="intake-chain-connectors">
                                    <!-- Vendor (Mengirim Barang) -> Expediting (Monitor Pengiriman) -->
                                    <line x1="148" y1="182" x2="212" y2="182" stroke="var(--diagram-muted)" stroke-width="1.4" marker-end="url(#arrow)"/>
                                    <rect x="154" y="167" width="52" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-node-stroke)" stroke-width="0.8"/>
                                    <text x="180" y="176.5" text-anchor="middle" class="mono" font-size="6" font-weight="700" fill="var(--diagram-muted)">FISIK &amp; DO</text>

                                    <!-- Expediting (Monitor Pengiriman) -> Receiving (Menerima & Cek PO) -->
                                    <line x1="340" y1="182" x2="396" y2="182" stroke="var(--diagram-muted)" stroke-width="1.4" marker-end="url(#arrow)"/>
                                    <rect x="345" y="167" width="46" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-node-stroke)" stroke-width="0.8"/>
                                    <text x="368" y="176.5" text-anchor="middle" class="mono" font-size="6" font-weight="700" fill="var(--diagram-muted)">ETA VALID</text>
                                </g>

                                <!-- 2. RECEIVING INTERNAL REQUEST DISPATCH -->
                                <g id="receiving-dispatch-connectors">
                                    <!-- Menerima PO -> Permintaan QC/JOR (Chemical, UP) -->
                                    <line x1="463" y1="154" x2="463" y2="114" stroke="var(--diagram-red)" stroke-width="1.4" marker-end="url(#arrow-red)"
                                        :class="{'opacity-15': filterBranch === 'sparepart'}"/>
                                    <rect x="471" y="127" width="86" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-red)" stroke-width="0.8"
                                        :class="{'opacity-15': filterBranch === 'sparepart'}"/>
                                    <text x="514" y="136.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)"
                                        :class="{'opacity-15': filterBranch === 'sparepart'}">CHEMICAL / RAW MAT</text>

                                    <!-- Menerima PO -> Permintaan QC (Sparepart, DOWN) -->
                                    <line x1="463" y1="210" x2="463" y2="250" stroke="var(--diagram-blue)" stroke-width="1.4" marker-end="url(#arrow-blue)"
                                        :class="{'opacity-15': filterBranch === 'chemical'}"/>
                                    <rect x="471" y="222" width="60" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-blue)" stroke-width="0.8"
                                        :class="{'opacity-15': filterBranch === 'chemical'}"/>
                                    <text x="501" y="231.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-blue)"
                                        :class="{'opacity-15': filterBranch === 'chemical'}">SPAREPART</text>
                                </g>

                                <!-- 3. CHEMICAL TRACK: RECEIVING -> LAB -> PPE -> DECISION -->
                                <g id="chemical-track-connectors" :class="{'opacity-15': filterBranch === 'sparepart'}">
                                    <!-- Permintaan QC/JOR -> Lab: Melakukan Uji Lab -->
                                    <line x1="530" y1="90" x2="736" y2="90" stroke="var(--diagram-red)" stroke-width="1.4" marker-end="url(#arrow-red)"/>
                                    <rect x="600" y="76" width="86" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-red)" stroke-width="0.8"/>
                                    <text x="643" y="85.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">PERMINTAAN JOR LAB</text>

                                    <!-- Lab (Uji Lab) -> Lab (Menerbitkan COA) -->
                                    <line x1="800" y1="114" x2="800" y2="158" stroke="var(--diagram-red)" stroke-width="1.4" marker-end="url(#arrow-red)"/>
                                    <rect x="808" y="128" width="56" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-red)" stroke-width="0.8"/>
                                    <text x="836" y="137.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">HASIL UJI</text>

                                    <!-- Lab (Menerbitkan COA) -> PPE (Evaluasi Mutu) -->
                                    <line x1="864" y1="182" x2="924" y2="182" stroke="var(--diagram-red)" stroke-width="1.4" marker-end="url(#arrow-red)"/>
                                    <rect x="869" y="167" width="50" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-red)" stroke-width="0.8"/>
                                    <text x="894" y="176.5" text-anchor="middle" class="mono" font-size="6" font-weight="700" fill="var(--diagram-red)">TERBIT COA</text>

                                    <!-- PPE (Evaluasi Mutu) -> PPE Decision Diamond -->
                                    <line x1="988" y1="206" x2="988" y2="258" stroke="var(--diagram-red)" stroke-width="1.4" marker-end="url(#arrow-red)"/>

                                    <!-- PPE Diamond TIDAK -> RDTV (Col 3) -->
                                    <path d="M 956 274 H 718 Q 710 274 710 282 V 420 Q 710 428 702 428 H 530" fill="none" stroke="var(--diagram-red)" stroke-width="1.2" marker-end="url(#arrow-red)"
                                        :class="{'opacity-15': filterBranch === 'diterima'}"/>
                                    <rect x="780" y="261" width="66" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-red)" stroke-width="0.8"
                                        :class="{'opacity-15': filterBranch === 'diterima'}"/>
                                    <text x="813" y="270.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)"
                                        :class="{'opacity-15': filterBranch === 'diterima'}">TIDAK · GAGAL</text>

                                    <!-- PPE Diamond YA -> GRS (Col 3) -->
                                    <path d="M 988 290 V 544 Q 988 552 980 552 H 530" fill="none" stroke="var(--diagram-green)" stroke-width="1.4" marker-end="url(#arrow-green)"
                                        :class="{'opacity-15': filterBranch === 'ditolak'}"/>
                                    <rect x="996" y="360" width="56" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-green)" stroke-width="0.8"
                                        :class="{'opacity-15': filterBranch === 'ditolak'}"/>
                                    <text x="1024" y="369.5" text-anchor="middle" class="mono" font-size="7" font-weight="700" fill="var(--diagram-green)"
                                        :class="{'opacity-15': filterBranch === 'ditolak'}">YA · LOLOS</text>
                                </g>

                                <!-- 4. SPAREPART TRACK: RECEIVING -> ISTEK 2 -> DECISION -->
                                <g id="sparepart-track-connectors" :class="{'opacity-15': filterBranch === 'chemical'}">
                                    <!-- Permintaan QC -> ISTEK 2: Melakukan QC -->
                                    <line x1="526" y1="274" x2="572" y2="274" stroke="var(--diagram-blue)" stroke-width="1.4" marker-end="url(#arrow-blue)"/>
                                    <rect x="531" y="267" width="36" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-blue)" stroke-width="0.8"/>
                                    <text x="549" y="276.5" text-anchor="middle" class="mono" font-size="6" font-weight="700" fill="var(--diagram-blue)">QC PO</text>

                                    <!-- ISTEK 2 (Melakukan QC) -> ISTEK 2 Decision Diamond -->
                                    <line x1="634" y1="298" x2="634" y2="332" stroke="var(--diagram-blue)" stroke-width="1.4" marker-end="url(#arrow-blue)"/>

                                    <!-- ISTEK 2 Diamond TIDAK -> RDTV (Col 3) -->
                                    <path d="M 602 348 H 553 Q 545 348 545 356 V 440 Q 545 448 537 448 H 530" fill="none" stroke="var(--diagram-red)" stroke-width="1.2" marker-end="url(#arrow-red)"
                                        :class="{'opacity-15': filterBranch === 'diterima'}"/>
                                    <rect x="548" y="335" width="60" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-red)" stroke-width="0.8"
                                        :class="{'opacity-15': filterBranch === 'diterima'}"/>
                                    <text x="578" y="344.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)"
                                        :class="{'opacity-15': filterBranch === 'diterima'}">TIDAK · GAGAL</text>

                                    <!-- ISTEK 2 Diamond YA -> GRS (Col 3) -->
                                    <path d="M 634 364 V 520 Q 634 528 626 528 H 530" fill="none" stroke="var(--diagram-green)" stroke-width="1.4" marker-end="url(#arrow-green)"
                                        :class="{'opacity-15': filterBranch === 'ditolak'}"/>
                                    <rect x="642" y="440" width="56" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-green)" stroke-width="0.8"
                                        :class="{'opacity-15': filterBranch === 'ditolak'}"/>
                                    <text x="670" y="449.5" text-anchor="middle" class="mono" font-size="7" font-weight="700" fill="var(--diagram-green)"
                                        :class="{'opacity-15': filterBranch === 'ditolak'}">YA · LOLOS</text>
                                </g>

                                <!-- 5. REJECTION RETURN FLOW (RDTV -> EXPEDITING -> VENDOR) -->
                                <g id="rejection-flow-connectors" :class="{'opacity-15': filterBranch === 'diterima'}">
                                    <!-- Receiving (RDTV) -> Expediting (Monitor Penolakan) -->
                                    <line x1="396" y1="438" x2="340" y2="438" stroke="var(--diagram-red)" stroke-width="1.4" marker-end="url(#arrow-red)"/>
                                    <rect x="345" y="423" width="46" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-red)" stroke-width="0.8"/>
                                    <text x="368" y="432.5" text-anchor="middle" class="mono" font-size="6" font-weight="700" fill="var(--diagram-red)">RETUR ERP</text>

                                    <!-- Expediting (Monitor Penolakan) -> Vendor (Pemberitahuan Retur) -->
                                    <line x1="212" y1="438" x2="148" y2="438" stroke="var(--diagram-red)" stroke-width="1.4" marker-end="url(#arrow-red)"/>
                                    <rect x="154" y="423" width="52" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-red)" stroke-width="0.8"/>
                                    <text x="180" y="432.5" text-anchor="middle" class="mono" font-size="6" font-weight="700" fill="var(--diagram-red)">KLAIM RETUR</text>
                                </g>

                                <!-- 6. ACCEPTANCE FLOW (GRS -> GUDANG) -->
                                <g id="acceptance-flow-connectors" :class="{'opacity-15': filterBranch === 'ditolak'}">
                                    <line x1="530" y1="540" x2="1096" y2="540" stroke="var(--diagram-green)" stroke-width="1.6" marker-end="url(#arrow-green)"/>
                                    <rect x="730" y="527" width="160" height="15" rx="3" fill="var(--diagram-paper)" stroke="var(--diagram-green)" stroke-width="0.8"/>
                                    <text x="810" y="537.5" text-anchor="middle" class="mono" font-size="7" font-weight="700" fill="var(--diagram-green)">SERAH TERIMA FISIK &amp; GRS (SAP 105)</text>
                                </g>

                                <!-- ===================================================
                                     NODES (PROCESS BOXES & DECISION DIAMONDS)
                                     =================================================== -->

                                <!-- 1. VENDOR: Mengirim Barang (Col 1) -->
                                <g class="node-group" id="node-vendor">
                                    <rect x="20" y="158" width="128" height="48" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-node-stroke)" stroke-width="1"/>
                                    <rect x="26" y="164" width="24" height="11" rx="2" fill="var(--diagram-chip)"/>
                                    <text x="38" y="172.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-ink)">VND</text>
                                    <text x="88" y="176" text-anchor="middle" font-size="10" font-weight="700" fill="var(--diagram-ink)">Mengirim Barang</text>
                                    <text x="88" y="190" text-anchor="middle" class="mono" font-size="7.5" fill="var(--diagram-muted)">Kirim Fisik &amp; DO</text>
                                </g>

                                <!-- 2. EXPEDITING: Monitor Pengiriman (Col 2) -->
                                <g class="node-group" id="node-expediting">
                                    <rect x="212" y="158" width="128" height="48" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-node-stroke)" stroke-width="1"/>
                                    <rect x="218" y="164" width="24" height="11" rx="2" fill="var(--diagram-chip)"/>
                                    <text x="230" y="172.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-ink)">EXP</text>
                                    <text x="280" y="176" text-anchor="middle" font-size="10" font-weight="700" fill="var(--diagram-ink)">Monitor Pengiriman</text>
                                    <text x="280" y="190" text-anchor="middle" class="mono" font-size="7.5" fill="var(--diagram-muted)">Tracking Jadwal PO</text>
                                </g>

                                <!-- 3. RECEIVING: Menerima & Memeriksa PO (Col 3 - Central Focal Hub) -->
                                <g class="node-group" id="node-receiving-main">
                                    <rect x="396" y="154" width="134" height="56" rx="6" fill="var(--diagram-blue-tint)" stroke="var(--diagram-blue)" stroke-width="1.5"/>
                                    <rect x="402" y="160" width="26" height="11" rx="2" fill="var(--diagram-blue)" fill-opacity="0.25"/>
                                    <text x="415" y="168.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-blue)">RCV</text>
                                    <text x="466" y="175" text-anchor="middle" font-size="10.5" font-weight="800" fill="var(--diagram-ink)">Menerima &amp; Cek PO</text>
                                    <text x="466" y="188" text-anchor="middle" class="mono" font-size="7" fill="var(--diagram-muted)">Cek fisik &amp; berkas vendor</text>
                                    <rect x="419" y="192" width="94" height="12" rx="2" fill="var(--diagram-chip)"/>
                                    <text x="466" y="200.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-blue)">MIGO SAP 103 (KARANTINA)</text>
                                </g>

                                <!-- 4. RECEIVING: Mengajukan Permintaan QC/JOR Chemical (Col 3 - Top) -->
                                <g class="node-group" id="node-receiving-jor" :class="{'opacity-15': filterBranch === 'sparepart'}">
                                    <rect x="396" y="66" width="134" height="48" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-red)" stroke-width="1"/>
                                    <rect x="402" y="72" width="26" height="11" rx="2" fill="var(--diagram-red)" fill-opacity="0.15"/>
                                    <text x="415" y="80.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">RCV</text>
                                    <text x="466" y="84" text-anchor="middle" font-size="10" font-weight="700" fill="var(--diagram-ink)">Permintaan QC/JOR</text>
                                    <text x="466" y="98" text-anchor="middle" class="mono" font-size="7.5" fill="var(--diagram-muted)">Chemical / Bahan Baku</text>
                                </g>

                                <!-- 5. RECEIVING: Mengajukan Permintaan QC Sparepart (Col 3 - Bottom) -->
                                <g class="node-group" id="node-receiving-qc-req" :class="{'opacity-15': filterBranch === 'chemical'}">
                                    <rect x="396" y="250" width="130" height="48" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-blue)" stroke-width="1"/>
                                    <rect x="402" y="256" width="26" height="11" rx="2" fill="var(--diagram-blue)" fill-opacity="0.15"/>
                                    <text x="415" y="264.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-blue)">RCV</text>
                                    <text x="464" y="268" text-anchor="middle" font-size="10" font-weight="700" fill="var(--diagram-ink)">Permintaan QC</text>
                                    <text x="464" y="282" text-anchor="middle" class="mono" font-size="7.5" fill="var(--diagram-muted)">Sparepart &amp; Material Teknis</text>
                                </g>

                                <!-- 6. LAB: Melakukan Uji Lab (Col 5 - Top) -->
                                <g class="node-group" id="node-lab-uji" :class="{'opacity-15': filterBranch === 'sparepart'}">
                                    <rect x="736" y="66" width="128" height="48" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-red)" stroke-width="1"/>
                                    <rect x="742" y="72" width="24" height="11" rx="2" fill="var(--diagram-red)" fill-opacity="0.15"/>
                                    <text x="754" y="80.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">LAB</text>
                                    <text x="804" y="84" text-anchor="middle" font-size="10" font-weight="700" fill="var(--diagram-ink)">Melakukan Uji Lab</text>
                                    <text x="804" y="98" text-anchor="middle" class="mono" font-size="7.5" fill="var(--diagram-muted)">Sampling &amp; Uji Kimiawi</text>
                                </g>

                                <!-- 7. LAB: Menerbitkan COA (Col 5 - Middle) -->
                                <g class="node-group" id="node-lab-coa" :class="{'opacity-15': filterBranch === 'sparepart'}">
                                    <rect x="736" y="158" width="128" height="48" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-red)" stroke-width="1"/>
                                    <rect x="742" y="164" width="24" height="11" rx="2" fill="var(--diagram-red)" fill-opacity="0.15"/>
                                    <text x="754" y="172.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">LAB</text>
                                    <text x="804" y="176" text-anchor="middle" font-size="10" font-weight="700" fill="var(--diagram-ink)">Menerbitkan COA</text>
                                    <text x="804" y="190" text-anchor="middle" class="mono" font-size="7.5" fill="var(--diagram-muted)">Certificate of Analysis</text>
                                </g>

                                <!-- 8. PPE: Melakukan Evaluasi Mutu (Col 6) -->
                                <g class="node-group" id="node-ppe-eval" :class="{'opacity-15': filterBranch === 'sparepart'}">
                                    <rect x="924" y="158" width="128" height="48" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-red)" stroke-width="1"/>
                                    <rect x="930" y="164" width="24" height="11" rx="2" fill="var(--diagram-red)" fill-opacity="0.15"/>
                                    <text x="942" y="172.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">PPE</text>
                                    <text x="992" y="176" text-anchor="middle" font-size="10" font-weight="700" fill="var(--diagram-ink)">Evaluasi Mutu</text>
                                    <text x="992" y="190" text-anchor="middle" class="mono" font-size="7.5" fill="var(--diagram-muted)">Verifikasi COA vs Standar</text>
                                </g>

                                <!-- 8b. PPE DECISION DIAMOND (Col 6) -->
                                <g id="diamond-ppe" :class="{'opacity-15': filterBranch === 'sparepart'}">
                                    <polygon points="988,258 1020,274 988,290 956,274" fill="var(--diagram-node)" stroke="var(--diagram-ink)" stroke-width="1.2"/>
                                    <text x="988" y="277" text-anchor="middle" font-size="8" font-weight="700" fill="var(--diagram-ink)">Diterima?</text>
                                </g>

                                <!-- 9. ISTEK 2: Melakukan QC (Col 4) -->
                                <g class="node-group" id="node-istek-qc" :class="{'opacity-15': filterBranch === 'chemical'}">
                                    <rect x="572" y="250" width="124" height="48" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-blue)" stroke-width="1"/>
                                    <rect x="578" y="256" width="24" height="11" rx="2" fill="var(--diagram-blue)" fill-opacity="0.15"/>
                                    <text x="590" y="264.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-blue)">IST</text>
                                    <text x="638" y="268" text-anchor="middle" font-size="10" font-weight="700" fill="var(--diagram-ink)">Melakukan QC</text>
                                    <text x="638" y="282" text-anchor="middle" class="mono" font-size="7.5" fill="var(--diagram-muted)">Dimensi &amp; Part Number</text>
                                </g>

                                <!-- 9b. ISTEK 2 DECISION DIAMOND (Col 4) -->
                                <g id="diamond-istek" :class="{'opacity-15': filterBranch === 'chemical'}">
                                    <polygon points="634,332 666,348 634,364 602,348" fill="var(--diagram-node)" stroke="var(--diagram-ink)" stroke-width="1.2"/>
                                    <text x="634" y="351" text-anchor="middle" font-size="8" font-weight="700" fill="var(--diagram-ink)">Diterima?</text>
                                </g>

                                <!-- 10. RECEIVING: Menerbitkan RDTV (Col 3) -->
                                <g class="node-group" id="node-rdtv" :class="{'opacity-15': filterBranch === 'diterima'}">
                                    <rect x="396" y="410" width="134" height="56" rx="6" fill="var(--diagram-red-tint)" stroke="var(--diagram-red)" stroke-width="1.5"/>
                                    <rect x="402" y="416" width="26" height="11" rx="2" fill="var(--diagram-red)" fill-opacity="0.25"/>
                                    <text x="415" y="424.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">RCV</text>
                                    <text x="466" y="431" text-anchor="middle" font-size="10.5" font-weight="800" fill="var(--diagram-red)">Menerbitkan RDTV</text>
                                    <text x="466" y="443" text-anchor="middle" class="mono" font-size="7" font-weight="600" fill="var(--diagram-ink)">RETURN TO VENDOR</text>
                                    <rect x="419" y="448" width="94" height="12" rx="2" fill="var(--diagram-red)" fill-opacity="0.15"/>
                                    <text x="466" y="456.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">MATERIAL DITOLAK</text>
                                </g>

                                <!-- 11. EXPEDITING: Monitor Penolakan (Col 2) -->
                                <g class="node-group" id="node-exp-retur" :class="{'opacity-15': filterBranch === 'diterima'}">
                                    <rect x="212" y="414" width="128" height="48" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-red)" stroke-width="1.2"/>
                                    <rect x="218" y="420" width="24" height="11" rx="2" fill="var(--diagram-red)" fill-opacity="0.2"/>
                                    <text x="230" y="428.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">EXP</text>
                                    <text x="280" y="432" text-anchor="middle" font-size="10" font-weight="700" fill="var(--diagram-ink)">Monitor Penolakan</text>
                                    <text x="280" y="446" text-anchor="middle" class="mono" font-size="7.5" fill="var(--diagram-muted)">Update Retur ERP</text>
                                </g>

                                <!-- 12. VENDOR: Menerima Pemberitahuan Barang Ditolak (Col 1) -->
                                <g class="node-group" id="node-vendor-retur" :class="{'opacity-15': filterBranch === 'diterima'}">
                                    <rect x="20" y="414" width="128" height="48" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-red)" stroke-width="1.2"/>
                                    <rect x="26" y="420" width="24" height="11" rx="2" fill="var(--diagram-red)" fill-opacity="0.2"/>
                                    <text x="38" y="428.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">VND</text>
                                    <text x="88" y="431" text-anchor="middle" font-size="9.5" font-weight="700" fill="var(--diagram-ink)">Pemberitahuan Retur</text>
                                    <text x="88" y="444" text-anchor="middle" class="mono" font-size="7" fill="var(--diagram-muted)">Klaim / Penggantian</text>
                                    <text x="88" y="454" text-anchor="middle" font-size="6.5" font-weight="600" fill="var(--diagram-red)">Barang Ditolak</text>
                                </g>

                                <!-- 13. RECEIVING: Menerbitkan GRS (Col 3) -->
                                <g class="node-group" id="node-grs" :class="{'opacity-15': filterBranch === 'ditolak'}">
                                    <rect x="396" y="512" width="134" height="56" rx="6" fill="var(--diagram-green-tint)" stroke="var(--diagram-green)" stroke-width="1.5"/>
                                    <rect x="402" y="518" width="26" height="11" rx="2" fill="var(--diagram-green)" fill-opacity="0.25"/>
                                    <text x="415" y="526.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-green)">RCV</text>
                                    <text x="466" y="533" text-anchor="middle" font-size="10.5" font-weight="800" fill="var(--diagram-green)">Menerbitkan GRS</text>
                                    <text x="466" y="546" text-anchor="middle" class="mono" font-size="7" font-weight="600" fill="var(--diagram-ink)">MIGO SAP 105</text>
                                    <rect x="419" y="550" width="94" height="12" rx="2" fill="var(--diagram-green)" fill-opacity="0.15"/>
                                    <text x="466" y="558.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-green)">UNRESTRICTED STOCK</text>
                                </g>

                                <!-- 14. GUDANG: Melakukan Penyimpanan Barang (Col 7) -->
                                <g class="node-group" id="node-gudang" :class="{'opacity-15': filterBranch === 'ditolak'}">
                                    <rect x="1096" y="516" width="134" height="48" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-green)" stroke-width="1.3"/>
                                    <rect x="1102" y="522" width="24" height="11" rx="2" fill="var(--diagram-green)" fill-opacity="0.2"/>
                                    <text x="1114" y="530.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-green)">GDG</text>
                                    <text x="1166" y="534" text-anchor="middle" font-size="10" font-weight="700" fill="var(--diagram-ink)">Penyimpanan Fisik</text>
                                    <text x="1166" y="548" text-anchor="middle" class="mono" font-size="7.5" fill="var(--diagram-muted)">Staging &amp; Rak Gudang</text>
                                </g>       </g>

                                <!-- ===================================================
                                     BOTTOM LEGEND & NOTATION STRIP
                                     =================================================== -->
                                <g id="diagram-legend">
                                    <!-- Legend Hairline -->
                                    <line x1="20" y1="600" x2="1240" y2="600" stroke="var(--diagram-rule-strong)" stroke-width="0.8"/>
                                    
                                    <!-- Legend Title -->
                                    <text x="24" y="618" class="mono" font-size="7.5" font-weight="700" letter-spacing="0.14em" fill="var(--diagram-soft)">NOTASI DIAGRAM</text>

                                    <!-- Legend Items Row -->
                                    <!-- 1. Step -->
                                    <rect x="24" y="628" width="14" height="10" rx="2" fill="var(--diagram-node)" stroke="var(--diagram-node-stroke)" stroke-width="1"/>
                                    <text x="44" y="636" font-size="8.5" fill="var(--diagram-muted)">Tahap Aktivitas SOP</text>

                                    <!-- 2. Decision Diamond -->
                                    <polygon points="172,633 178,627 184,633 178,639" fill="var(--diagram-node)" stroke="var(--diagram-ink)" stroke-width="1"/>
                                    <text x="190" y="636" font-size="8.5" fill="var(--diagram-muted)">Keputusan Mutu (Diterima?)</text>

                                    <!-- 3. GRS Accepted -->
                                    <rect x="336" y="628" width="14" height="10" rx="2" fill="var(--diagram-green-tint)" stroke="var(--diagram-green)" stroke-width="1.2"/>
                                    <text x="356" y="636" font-size="8.5" font-weight="600" fill="var(--diagram-green)">Diterima (GRS SAP 105)</text>

                                    <!-- 4. RDTV Rejected -->
                                    <rect x="516" y="628" width="14" height="10" rx="2" fill="var(--diagram-red-tint)" stroke="var(--diagram-red)" stroke-width="1.2"/>
                                    <text x="536" y="636" font-size="8.5" font-weight="600" fill="var(--diagram-red)">Ditolak (RDTV Retur)</text>

                                    <!-- 5. Handoff Flow -->
                                    <line x1="700" y1="633" x2="726" y2="633" stroke="var(--diagram-muted)" stroke-width="1.2" marker-end="url(#arrow)"/>
                                    <text x="736" y="636" font-size="8.5" fill="var(--diagram-muted)">Alur Lintas Unit</text>

                                    <!-- 6. Focal Hub Unit -->
                                    <rect x="850" y="628" width="14" height="10" rx="2" fill="var(--diagram-blue-tint)" stroke="var(--diagram-blue)" stroke-width="1.2"/>
                                    <text x="870" y="636" font-size="8.5" font-weight="700" fill="var(--diagram-blue)">Receiving (Focal Hub)</text>
                                </g>

                            </svg>
                        </div>
                    </div>
                </div>

            </div>

            <!-- =========================================================
                 VIEW 2: PANDUAN TAHAPAN DETAIL (STEP-BY-STEP BREAKDOWN)
                 ========================================================= -->
            <div x-show="viewMode === 'steps'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-8">

                <!-- Steps Navigation Buttons -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    <button @click="activeStep = 1"
                        :class="activeStep === 1 ? 'bg-gradient-to-r from-[#0A4F86] to-blue-600 text-white shadow-lg shadow-blue-900/30 scale-102 ring-2 ring-blue-400/30' : 'glass-btn text-slate-700 dark:text-slate-300 hover:text-[#0A4F86]'"
                        class="p-4 rounded-2xl flex flex-col items-start text-left transition-all duration-300 cursor-pointer">
                        <span class="text-[10px] uppercase tracking-wider font-extrabold opacity-80">Tahap 01</span>
                        <span class="text-xs sm:text-sm font-black mt-1">Kedatangan &amp; PO</span>
                    </button>

                    <button @click="activeStep = 2"
                        :class="activeStep === 2 ? 'bg-gradient-to-r from-red-600 to-rose-700 text-white shadow-lg shadow-red-900/30 scale-102 ring-2 ring-red-400/30' : 'glass-btn text-slate-700 dark:text-slate-300 hover:text-red-500'"
                        class="p-4 rounded-2xl flex flex-col items-start text-left transition-all duration-300 cursor-pointer">
                        <span class="text-[10px] uppercase tracking-wider font-extrabold opacity-80">Tahap 02</span>
                        <span class="text-xs sm:text-sm font-black mt-1">Chemical (Lab &amp; PPE)</span>
                    </button>

                    <button @click="activeStep = 3"
                        :class="activeStep === 3 ? 'bg-gradient-to-r from-blue-600 to-indigo-700 text-white shadow-lg shadow-blue-900/30 scale-102 ring-2 ring-blue-400/30' : 'glass-btn text-slate-700 dark:text-slate-300 hover:text-blue-500'"
                        class="p-4 rounded-2xl flex flex-col items-start text-left transition-all duration-300 cursor-pointer">
                        <span class="text-[10px] uppercase tracking-wider font-extrabold opacity-80">Tahap 03</span>
                        <span class="text-xs sm:text-sm font-black mt-1">Sparepart (ISTEK 2)</span>
                    </button>

                    <button @click="activeStep = 4"
                        :class="activeStep === 4 ? 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-lg shadow-emerald-900/30 scale-102 ring-2 ring-emerald-400/30' : 'glass-btn text-slate-700 dark:text-slate-300 hover:text-emerald-500'"
                        class="p-4 rounded-2xl flex flex-col items-start text-left transition-all duration-300 cursor-pointer">
                        <span class="text-[10px] uppercase tracking-wider font-extrabold opacity-80">Tahap 04</span>
                        <span class="text-xs sm:text-sm font-black mt-1">Diterima (GRS)</span>
                    </button>

                    <button @click="activeStep = 5"
                        :class="activeStep === 5 ? 'bg-gradient-to-r from-amber-600 to-[#F47920] text-white shadow-lg shadow-orange-500/30 scale-102 ring-2 ring-orange-400/30' : 'glass-btn text-slate-700 dark:text-slate-300 hover:text-amber-500'"
                        class="p-4 rounded-2xl flex flex-col items-start text-left transition-all duration-300 cursor-pointer">
                        <span class="text-[10px] uppercase tracking-wider font-extrabold opacity-80">Tahap 05</span>
                        <span class="text-xs sm:text-sm font-black mt-1">Ditolak (RDTV)</span>
                    </button>
                </div>

                <!-- Dynamic Content Card for Steps -->
                <div class="glass-panel p-8 sm:p-12 rounded-3xl border border-white/80 dark:border-white/10 shadow-xl relative overflow-hidden">

                    <!-- STEP 1: Kedatangan & Pemeriksaan PO -->
                    <div x-show="activeStep === 1" x-transition.opacity.duration.400ms class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center relative z-10">
                        <div class="lg:col-span-7">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-300 text-xs font-bold mb-4">
                                Tahap 1: Pengiriman, Ekspedisi, &amp; Verifikasi Awal
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white mb-4">
                                Penerimaan Fisik &amp; Verifikasi Kesesuaian PO
                            </h3>
                            <p class="text-slate-600 dark:text-slate-300 leading-relaxed mb-6">
                                Vendor mengirimkan barang fisik ke area penerimaan Gudang Pupuk Kalimantan Timur. Tim <strong>Expediting</strong> memonitor proses pengiriman logistik. Selanjutnya petugas <strong>Receiving</strong> menerima, mencocokkan nomor PO SAP, memeriksa kondisi fisik kemasan, surat jalan (DO), serta kelengkapan dokumen lampiran vendor sebelum diproses lebih lanjut.
                            </p>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-3.5 rounded-xl bg-white/60 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                                    <span class="text-[11px] font-bold text-slate-400 uppercase">Dokumen Utama</span>
                                    <p class="text-sm font-black text-slate-800 dark:text-white mt-0.5">PO SAP, Surat Jalan (DO), Lampiran Vendor</p>
                                </div>
                                <div class="p-3.5 rounded-xl bg-white/60 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                                    <span class="text-[11px] font-bold text-slate-400 uppercase">Unit Terlibat</span>
                                    <p class="text-sm font-black text-slate-800 dark:text-white mt-0.5">Vendor &rarr; Expediting &rarr; Receiving</p>
                                </div>
                            </div>
                        </div>
                        <div class="lg:col-span-5 p-6 rounded-2xl bg-gradient-to-br from-[#0A4F86]/10 to-transparent border border-[#0A4F86]/20 flex flex-col justify-center text-center">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-[#0A4F86] text-white flex items-center justify-center mb-4 shadow-lg shadow-blue-900/30">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <h4 class="text-lg font-bold text-slate-800 dark:text-white mb-1">Cek Dokumen &amp; Entry SAP 103</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Verifikasi kuantitas, spesifikasi awal, dan kesesuaian data kontrak PO.</p>
                        </div>
                    </div>

                    <!-- STEP 2: Jalur Chemical / Bahan Baku (Lab & PPE) -->
                    <div x-show="activeStep === 2" x-transition.opacity.duration.400ms style="display: none;" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center relative z-10">
                        <div class="lg:col-span-7">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-500/10 text-red-600 dark:text-red-400 text-xs font-bold mb-4">
                                Tahap 2: Pengujian Mutu Chemical &amp; Bahan Baku
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white mb-4">
                                Uji Laboratorium (COA) &amp; Evaluasi Kualitas (PPE)
                            </h3>
                            <p class="text-slate-600 dark:text-slate-300 leading-relaxed mb-6">
                                Khusus komoditas <strong>Chemical &amp; Bahan Baku</strong> (misal: bahan kimia proses, bahan baku NPK, karung kemasan):
                                Petugas Receiving mengajukan permintaan <strong>QC/JOR</strong>. Unit <strong>LAB</strong> mengambil sampel dan melakukan uji kimiawi lalu menerbitkan <strong>COA (Certificate of Analysis)</strong>. Selanjutnya unit <strong>PPE</strong> mengevaluasi kesesuaian mutu hasil uji terhadap standar teknis pabrik untuk memutuskan apakah barang <strong>Diterima</strong> atau <strong>Ditolak</strong>.
                            </p>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-3.5 rounded-xl bg-white/60 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                                    <span class="text-[11px] font-bold text-slate-400 uppercase">Dokumen Utama</span>
                                    <p class="text-sm font-black text-slate-800 dark:text-white mt-0.5">Surat Permintaan QC/JOR &amp; Dokumen COA</p>
                                </div>
                                <div class="p-3.5 rounded-xl bg-white/60 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                                    <span class="text-[11px] font-bold text-slate-400 uppercase">Unit Terlibat</span>
                                    <p class="text-sm font-black text-slate-800 dark:text-white mt-0.5">Receiving &rarr; LAB &rarr; PPE</p>
                                </div>
                            </div>
                        </div>
                        <div class="lg:col-span-5 p-6 rounded-2xl bg-gradient-to-br from-red-500/10 to-transparent border border-red-500/20 flex flex-col justify-center text-center">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-red-600 text-white flex items-center justify-center mb-4 shadow-lg shadow-red-900/30">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                            </div>
                            <h4 class="text-lg font-bold text-slate-800 dark:text-white mb-1">Analisa Laboratorium Terstandar</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Parameter kemurnian, kelembapan, dan komposisi senyawa kimia diuji secara presisi.</p>
                        </div>
                    </div>

                    <!-- STEP 3: Jalur Sparepart (ISTEK 2) -->
                    <div x-show="activeStep === 3" x-transition.opacity.duration.400ms style="display: none;" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center relative z-10">
                        <div class="lg:col-span-7">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 text-xs font-bold mb-4">
                                Tahap 3: Pengujian Mutu Suku Cadang &amp; Komponen
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white mb-4">
                                Inspeksi Teknis &amp; Spesifikasi Sparepart (ISTEK 2)
                            </h3>
                            <p class="text-slate-600 dark:text-slate-300 leading-relaxed mb-6">
                                Khusus kategori <strong>Sparepart</strong> (suku cadang mekanikal, elektrikal, instrumentasi):
                                Petugas Receiving menerbitkan pengajuan <strong>Permintaan QC</strong> melalui dokumen transmittal ke unit <strong>ISTEK 2 (Inspeksi Teknik 2)</strong>. Tim ISTEK 2 melakukan pemeriksaan langsung ke lapangan (cek visual, dimensi, part number, mill certificate, dan toleransi operasi) untuk menetapkan keputusan apakah barang <strong>Diterima</strong> atau <strong>Ditolak</strong>.
                            </p>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-3.5 rounded-xl bg-white/60 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                                    <span class="text-[11px] font-bold text-slate-400 uppercase">Dokumen Utama</span>
                                    <p class="text-sm font-black text-slate-800 dark:text-white mt-0.5">Permintaan QC &amp; Transmittal ISTEK</p>
                                </div>
                                <div class="p-3.5 rounded-xl bg-white/60 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                                    <span class="text-[11px] font-bold text-slate-400 uppercase">Unit Terlibat</span>
                                    <p class="text-sm font-black text-slate-800 dark:text-white mt-0.5">Receiving &rarr; ISTEK 2</p>
                                </div>
                            </div>
                        </div>
                        <div class="lg:col-span-5 p-6 rounded-2xl bg-gradient-to-br from-blue-500/10 to-transparent border border-blue-500/20 flex flex-col justify-center text-center">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-blue-600 text-white flex items-center justify-center mb-4 shadow-lg shadow-blue-900/30">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <h4 class="text-lg font-bold text-slate-800 dark:text-white mb-1">Verifikasi Spesifikasi Presisi</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Pengecekan kesesuaian part number, material metalurgi, dan sertifikat pabrikan.</p>
                        </div>
                    </div>

                    <!-- STEP 4: Hasil Diterima (Penerbitan GRS & Penyimpanan Gudang) -->
                    <div x-show="activeStep === 4" x-transition.opacity.duration.400ms style="display: none;" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center relative z-10">
                        <div class="lg:col-span-7">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-300 text-xs font-bold mb-4">
                                Tahap 4: Hasil Lolos QC (Diterima)
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white mb-4">
                                Penerbitan GRS &amp; Penyimpanan Fisik di Gudang
                            </h3>
                            <p class="text-slate-600 dark:text-slate-300 leading-relaxed mb-6">
                                Apabila hasil pemeriksaan (dari ISTEK 2 untuk sparepart atau PPE untuk chemical) menyatakan <strong>DITERIMA</strong>:
                                Petugas Receiving menerbitkan dokumen resmi <strong>Goods Receipt Slip (GRS / MIGO SAP 105)</strong>. Material resmi diakui dalam persediaan sistem perusahaan dan fisik barang diserahkan ke unit <strong>Gudang</strong> untuk dilakukan penyimpanan dan penataan di rak/lokasi staging yang sesuai.
                            </p>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-3.5 rounded-xl bg-white/60 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                                    <span class="text-[11px] font-bold text-slate-400 uppercase">Dokumen Utama</span>
                                    <p class="text-sm font-black text-slate-800 dark:text-white mt-0.5">Goods Receipt Slip (GRS / MIGO 105)</p>
                                </div>
                                <div class="p-3.5 rounded-xl bg-white/60 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                                    <span class="text-[11px] font-bold text-slate-400 uppercase">Unit Terlibat</span>
                                    <p class="text-sm font-black text-slate-800 dark:text-white mt-0.5">Receiving &rarr; Unit Gudang Penyimpanan</p>
                                </div>
                            </div>
                        </div>
                        <div class="lg:col-span-5 p-6 rounded-2xl bg-gradient-to-br from-emerald-500/10 to-transparent border border-emerald-500/20 flex flex-col justify-center text-center">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-600 text-white flex items-center justify-center mb-4 shadow-lg shadow-emerald-900/30">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <h4 class="text-lg font-bold text-slate-800 dark:text-white mb-1">MIGO SAP 105 &amp; Stock In</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Material sah tersimpan di gudang persediaan dan siap disalurkan ke unit kerja pemohon.</p>
                        </div>
                    </div>

                    <!-- STEP 5: Hasil Ditolak (Penerbitan RDTV & Alur Retur Vendor) -->
                    <div x-show="activeStep === 5" x-transition.opacity.duration.400ms style="display: none;" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center relative z-10">
                        <div class="lg:col-span-7">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-500/10 text-red-600 dark:text-red-400 text-xs font-bold mb-4">
                                Tahap 5: Hasil Tidak Lolos QC (Ditolak)
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white mb-4">
                                Penerbitan RDTV &amp; Notifikasi Penolakan ke Vendor
                            </h3>
                            <p class="text-slate-600 dark:text-slate-300 leading-relaxed mb-6">
                                Apabila hasil pemeriksaan mutu menyatakan <strong>TIDAK DITERIMA</strong> (spesifikasi tidak cocok, dokumen tidak lengkap, atau hasil uji lab di bawah ambang batas):
                                Petugas Receiving menerbitkan dokumen <strong>RDTV (Return Delivery to Vendor)</strong>. Unit <strong>Expediting</strong> memonitor status penolakan, dan <strong>Vendor</strong> menerima surat pemberitahuan resmi penolakan untuk segera menindaklanjuti penggantian item, revisi sertifikat, atau retur fisik barang.
                            </p>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-3.5 rounded-xl bg-white/60 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                                    <span class="text-[11px] font-bold text-slate-400 uppercase">Dokumen Utama</span>
                                    <p class="text-sm font-black text-slate-800 dark:text-white mt-0.5">Return Delivery to Vendor (RDTV)</p>
                                </div>
                                <div class="p-3.5 rounded-xl bg-white/60 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
                                    <span class="text-[11px] font-bold text-slate-400 uppercase">Unit Terlibat</span>
                                    <p class="text-sm font-black text-slate-800 dark:text-white mt-0.5">Receiving &rarr; Expediting &rarr; Vendor</p>
                                </div>
                            </div>
                        </div>
                        <div class="lg:col-span-5 p-6 rounded-2xl bg-gradient-to-br from-amber-500/10 to-transparent border border-amber-500/20 flex flex-col justify-center text-center">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-600 text-white flex items-center justify-center mb-4 shadow-lg shadow-amber-900/30">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <h4 class="text-lg font-bold text-slate-800 dark:text-white mb-1">Proteksi Kualitas Terjamin</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Material yang tidak memenuhi syarat langsung dihentikan sebelum masuk ke rantai produksi pabrik.</p>
                        </div>
                    </div>

                </div>

                <!-- 7 ENTITIES SUMMARY TABLE -->
                <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-white/80 dark:border-white/10 shadow-lg">
                    <h4 class="text-lg font-black text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#0A4F86] dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Matriks Peran 7 Unit Kerja dalam SOP Penerimaan Barang
                    </h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 font-extrabold uppercase tracking-wider">
                                <tr>
                                    <th class="py-3 px-4 rounded-l-xl">Unit Kerja</th>
                                    <th class="py-3 px-4">Tanggung Jawab Utama</th>
                                    <th class="py-3 px-4">Dokumen Terkait</th>
                                    <th class="py-3 px-4 rounded-r-xl">Hasil / Output</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/70 dark:divide-white/5 text-slate-600 dark:text-slate-300">
                                <tr>
                                    <td class="py-3 px-4 font-bold text-slate-800 dark:text-white">1. VENDOR</td>
                                    <td class="py-3 px-4">Mengirim barang ke gudang dan menerima notifikasi bila barang ditolak</td>
                                    <td class="py-3 px-4">Surat Jalan (DO), Lampiran Spesifikasi</td>
                                    <td class="py-3 px-4">Fisik barang terkirim / Tindak lanjut retur</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-4 font-bold text-slate-800 dark:text-white">2. EXPEDITING</td>
                                    <td class="py-3 px-4">Memantau jadwal pengiriman logistik serta memonitor status penolakan barang</td>
                                    <td class="py-3 px-4">Manifest Pengiriman &amp; Log Penolakan</td>
                                    <td class="py-3 px-4">Monitoring pengiriman &amp; status retur</td>
                                </tr>
                                <tr class="bg-amber-500/5 dark:bg-amber-500/10">
                                    <td class="py-3 px-4 font-black text-amber-700 dark:text-amber-400">3. RECEIVING</td>
                                    <td class="py-3 px-4 font-medium">Menerima barang, memeriksa PO, mengajukan QC/JOR &amp; QC, menerbitkan GRS / RDTV</td>
                                    <td class="py-3 px-4">PO SAP, MIGO 103, GRS (Accepted), RDTV (Rejected)</td>
                                    <td class="py-3 px-4 font-bold">Penerbitan GRS atau RDTV</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-4 font-bold text-slate-800 dark:text-white">4. ISTEK 2</td>
                                    <td class="py-3 px-4">Melakukan pengujian teknis sparepart (dimensi, visual, sertifikat) &amp; keputusan diterima/tidak</td>
                                    <td class="py-3 px-4">Transmittal QC, Laporan Inspeksi Teknik</td>
                                    <td class="py-3 px-4">Rekomendasi Mutu Sparepart (Diterima / Tidak)</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-4 font-bold text-slate-800 dark:text-white">5. LAB</td>
                                    <td class="py-3 px-4">Melakukan pengujian sampel kimia / bahan baku dan menerbitkan Certificate of Analysis</td>
                                    <td class="py-3 px-4">Surat QC/JOR &amp; Dokumen COA</td>
                                    <td class="py-3 px-4">Sertifikat Analisa (COA)</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-4 font-bold text-slate-800 dark:text-white">6. PPE</td>
                                    <td class="py-3 px-4">Mengevaluasi hasil COA dari Lab terhadap standar mutu &amp; keputusan diterima/tidak</td>
                                    <td class="py-3 px-4">Laporan Evaluasi Kualitas Mutu Bahan</td>
                                    <td class="py-3 px-4">Rekomendasi Mutu Chemical (Diterima / Tidak)</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-4 font-bold text-slate-800 dark:text-white">7. GUDANG</td>
                                    <td class="py-3 px-4">Melakukan penyimpanan fisik material ke rak/staging setelah GRS terbit</td>
                                    <td class="py-3 px-4">Good Receipt Slip (GRS / MIGO 105) &amp; Lokasi Rak</td>
                                    <td class="py-3 px-4 font-bold text-emerald-600 dark:text-emerald-400">Penyimpanan Fisik Aman di Gudang</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> <!-- END VIEW 2: PANDUAN TAHAPAN DETAIL -->

            <!-- =========================================================
                 VIEW 3: BAGAN DIAGRAM SWIMLANE BARANG TOLAK / RDTV (4 UNIT - SLIDE 5 & 6)
                 ========================================================= -->
            <div id="alur-barang-tolak"
                x-show="viewMode === 'rdtv'"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                style="display: none;"
                class="space-y-6">

                <!-- Interactive Highlight Filter & Style Legend (Konsisten dengan Bagan 7 Unit) -->
                <div class="glass-panel p-4 sm:p-5 rounded-2xl border border-white/80 dark:border-white/10 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mr-1 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#0A4F86]"></span>
                            Fokus Jalur:
                        </span>
                        <button @click="rdtvBranch = 'all'"
                            :class="rdtvBranch === 'all' ? 'bg-[#0A4F86] text-white shadow-sm ring-1 ring-blue-400/40' : 'bg-slate-200/70 dark:bg-white/5 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-white/10'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer">
                            Semua Alur RDTV
                        </button>
                        <button @click="rdtvBranch = 'retur'"
                            :class="rdtvBranch === 'retur' ? 'bg-red-600 text-white shadow-sm ring-1 ring-red-400/40' : 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 hover:bg-red-500/20'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            Vendor Setuju RDTV (Pengambilan Barang)
                        </button>
                        <button @click="rdtvBranch = 'klarifikasi'"
                            :class="rdtvBranch === 'klarifikasi' ? 'bg-blue-600 text-white shadow-sm ring-1 ring-blue-400/40' : 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 hover:bg-blue-500/20'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            Tidak Setuju (Surat Klarifikasi &amp; ISTEK)
                        </button>
                        <button @click="rdtvBranch = 'grs'"
                            :class="rdtvBranch === 'grs' ? 'bg-emerald-600 text-white shadow-sm ring-1 ring-emerald-400/40' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Disetujui ISTEK (Terbit GRS)
                        </button>
                    </div>

                    <div class="flex items-center gap-4 text-[11px] font-semibold text-slate-500 dark:text-slate-400 shrink-0">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded bg-[#0A4F86] inline-block"></span>
                            Receiving (Focal Hub)
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded bg-emerald-500 inline-block"></span>
                            Lolos Klarifikasi (GRS)
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded bg-red-500 inline-block"></span>
                            Retur Fisik (RDTV)
                        </span>
                    </div>
                </div>

                <!-- Editorial Swimlane Diagram Container (Konsisten dengan #receiving-diagram) -->
                <div class="glass-panel p-4 sm:p-6 rounded-3xl border border-white/80 dark:border-white/10 shadow-2xl relative overflow-hidden">

                    <!-- Mobile Scroll Hint -->
                    <div class="flex lg:hidden items-center justify-between text-xs font-bold text-slate-500 dark:text-slate-400 mb-3 px-1">
                        <span class="inline-flex items-center gap-1.5 text-[#0A4F86] dark:text-blue-300">
                            <svg class="w-4 h-4 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            Geser horizontal untuk melihat bagan diagram 4 unit lengkap
                        </span>
                    </div>

                    <div class="overflow-x-auto pb-4 custom-scrollbar">
                        <div class="min-w-[1100px] select-none">
                            <svg id="rdtv-diagram" viewBox="0 0 1260 660" xmlns="http://www.w3.org/2000/svg" role="img" aria-labelledby="rdtv-diagram-title rdtv-diagram-desc" class="w-full h-auto block rounded-2xl border border-slate-200/80 dark:border-white/10">
                                <title id="rdtv-diagram-title">Alur Proses Barang Tolak (RDTV) Gudang Receiving Pupuk Kaltim</title>
                                <desc id="rdtv-diagram-desc">Diagram Swimlane SOP Resmi Penanganan Barang QC Tolak (RDTV) Lintas 4 Unit Kerja Pupuk Kalimantan Timur</desc>

                                <defs>
                                    <pattern id="dot-grid-rdtv" width="22" height="22" patternUnits="userSpaceOnUse">
                                        <circle cx="11" cy="11" r="0.85" fill="var(--diagram-dot)"/>
                                    </pattern>
                                    <marker id="rdtv-arr" markerWidth="8" markerHeight="6" refX="7" refY="3" orient="auto">
                                        <polygon points="0 0, 8 3, 0 6" fill="var(--diagram-muted)"/>
                                    </marker>
                                    <marker id="rdtv-arr-blue" markerWidth="8" markerHeight="6" refX="7" refY="3" orient="auto">
                                        <polygon points="0 0, 8 3, 0 6" fill="var(--diagram-blue)"/>
                                    </marker>
                                    <marker id="rdtv-arr-green" markerWidth="8" markerHeight="6" refX="7" refY="3" orient="auto">
                                        <polygon points="0 0, 8 3, 0 6" fill="var(--diagram-green)"/>
                                    </marker>
                                    <marker id="rdtv-arr-red" markerWidth="8" markerHeight="6" refX="7" refY="3" orient="auto">
                                        <polygon points="0 0, 8 3, 0 6" fill="var(--diagram-red)"/>
                                    </marker>
                                </defs>

                                <!-- Base Canvas Background -->
                                <rect width="100%" height="100%" fill="var(--diagram-paper)"/>
                                <rect width="100%" height="100%" fill="url(#dot-grid-rdtv)" opacity="0.6"/>

                                <!-- 4 Vertical Swimlane Column Backgrounds (315px each) -->
                                <rect x="0" y="44" width="315" height="556" fill="var(--diagram-lane-focal)"/>
                                <rect x="315" y="44" width="315" height="556" fill="var(--diagram-lane-even)"/>
                                <rect x="630" y="44" width="315" height="556" fill="var(--diagram-lane-odd)"/>
                                <rect x="945" y="44" width="315" height="556" fill="var(--diagram-lane-even)"/>

                                <!-- Vertical Column Dividers -->
                                <g stroke="var(--diagram-rule)" stroke-width="0.8">
                                    <line x1="315" y1="0" x2="315" y2="600" stroke="var(--diagram-blue)" stroke-opacity="0.35" stroke-width="1"/>
                                    <line x1="630" y1="0" x2="630" y2="600"/>
                                    <line x1="945" y1="0" x2="945" y2="600"/>
                                </g>

                                <!-- Horizontal Divider below Header -->
                                <line x1="0" y1="44" x2="1260" y2="44" stroke="var(--diagram-rule-strong)" stroke-width="1"/>

                                <!-- ===================================================
                                     COLUMN HEADERS (4 UNIT KERJA - SLIDE 5)
                                     =================================================== -->
                                <g text-anchor="middle">
                                    <!-- 01 RECEIVING (Focal Hub) -->
                                    <rect x="62" y="6" width="190" height="32" rx="5" fill="var(--diagram-blue-tint)" stroke="var(--diagram-blue)" stroke-opacity="0.4" stroke-width="1"/>
                                    <text x="157.5" y="20" class="mono" font-size="9" font-weight="800" letter-spacing="0.10em" fill="var(--diagram-blue)">01 · RECEIVING</text>
                                    <text x="157.5" y="32.5" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-tangerine)">FOCAL HUB · RDTV &amp; GRS</text>

                                    <!-- 02 EKSPEDITING -->
                                    <rect x="377" y="8" width="190" height="28" rx="4" fill="var(--diagram-chip)"/>
                                    <text x="472.5" y="21" class="mono" font-size="8.5" font-weight="700" letter-spacing="0.10em" fill="var(--diagram-ink)">02 · EKSPEDITING</text>
                                    <text x="472.5" y="31.5" font-size="7" fill="var(--diagram-muted)">Koordinasi Vendor &amp; Retur</text>

                                    <!-- 03 VENDOR -->
                                    <rect x="692" y="8" width="190" height="28" rx="4" fill="var(--diagram-chip)"/>
                                    <text x="787.5" y="21" class="mono" font-size="8.5" font-weight="700" letter-spacing="0.10em" fill="var(--diagram-ink)">03 · VENDOR</text>
                                    <text x="787.5" y="31.5" font-size="7" fill="var(--diagram-muted)">Rekanan / Pemasok Barang</text>

                                    <!-- 04 ISTEK -->
                                    <rect x="1007" y="8" width="190" height="28" rx="4" fill="var(--diagram-chip)"/>
                                    <text x="1102.5" y="21" class="mono" font-size="8.5" font-weight="700" letter-spacing="0.10em" fill="var(--diagram-ink)">04 · ISTEK</text>
                                    <text x="1102.5" y="31.5" font-size="7" fill="var(--diagram-muted)">Verifikasi Klarifikasi Teknis</text>
                                </g>

                                <!-- ===================================================
                                     CONNECTORS (DRAWN FIRST)
                                     =================================================== -->

                                <!-- 1. INTAKE RDTV CHAIN: Mulai -> Menerbitkan RDTV -> Ekspediting -> Vendor -> Setuju? -->
                                <g id="rdtv-intake-connectors">
                                    <!-- Mulai -> Menerbitkan RDTV (Col 1) -->
                                    <line x1="157.5" y1="90" x2="157.5" y2="112" stroke="var(--diagram-red)" stroke-width="1.4" marker-end="url(#rdtv-arr-red)"/>

                                    <!-- Receiving (Menerbitkan RDTV) -> Ekspediting (Menerima RDTV & Info Vendor) -->
                                    <line x1="252" y1="140" x2="378" y2="140" stroke="var(--diagram-red)" stroke-width="1.4" marker-end="url(#rdtv-arr-red)"/>
                                    <rect x="275" y="125" width="80" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-red)" stroke-width="0.8"/>
                                    <text x="315" y="134.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">DOKUMEN RDTV</text>

                                    <!-- Ekspediting -> Vendor (Menerima RDTV) -->
                                    <line x1="567" y1="140" x2="693" y2="140" stroke="var(--diagram-red)" stroke-width="1.4" marker-end="url(#rdtv-arr-red)"/>
                                    <rect x="586" y="125" width="88" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-red)" stroke-width="0.8"/>
                                    <text x="630" y="134.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">INFORMASI VENDOR</text>

                                    <!-- Vendor (Menerima RDTV) -> Vendor Decision Diamond (Setuju?) -->
                                    <line x1="787.5" y1="168" x2="787.5" y2="218" stroke="var(--diagram-muted)" stroke-width="1.4" marker-end="url(#rdtv-arr)"/>
                                </g>

                                <!-- 2. JALUR YA (VENDOR SETUJU RDTV -> PENGAMBILAN BARANG -> SELESAI) -->
                                <g id="rdtv-retur-connectors" :class="{'opacity-15': rdtvBranch === 'klarifikasi' || rdtvBranch === 'grs'}">
                                    <!-- Vendor Diamond (Setuju? = YA) -> Ekspediting (Memerintahkan Pengambilan Barang) -->
                                    <line x1="745" y1="238" x2="567" y2="238" stroke="var(--diagram-red)" stroke-width="1.4" marker-end="url(#rdtv-arr-red)"/>
                                    <rect x="612" y="223" width="90" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-red)" stroke-width="0.8"/>
                                    <text x="657" y="232.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">YA · SETUJU RETUR</text>

                                    <!-- Ekspediting (Memerintahkan Pengambilan Barang) -> Receiving (Selesai Retur) -->
                                    <line x1="378" y1="238" x2="252" y2="238" stroke="var(--diagram-red)" stroke-width="1.4" marker-end="url(#rdtv-arr-red)"/>
                                    <rect x="273" y="223" width="84" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-red)" stroke-width="0.8"/>
                                    <text x="315" y="232.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">AMBIL BARANG</text>
                                </g>

                                <!-- 3. JALUR TIDAK (SURAT KLARIFIKASI KEBENARAN BARANG -> EKSPEDITING -> ISTEK) -->
                                <g id="rdtv-klarifikasi-connectors" :class="{'opacity-15': rdtvBranch === 'retur'}">
                                    <!-- Vendor Diamond (Setuju? = TIDAK) -> Vendor (Menerbitkan Surat Klarifikasi) -->
                                    <line x1="787.5" y1="258" x2="787.5" y2="322" stroke="var(--diagram-blue)" stroke-width="1.4" marker-end="url(#rdtv-arr-blue)"/>
                                    <rect x="796" y="280" width="98" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-blue)" stroke-width="0.8"/>
                                    <text x="845" y="289.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-blue)">TIDAK · KLARIFIKASI</text>

                                    <!-- Vendor (Menerbitkan Surat Klarifikasi) -> Ekspediting (Menerima Surat Klarifikasi) -->
                                    <line x1="693" y1="350" x2="567" y2="350" stroke="var(--diagram-blue)" stroke-width="1.4" marker-end="url(#rdtv-arr-blue)"/>
                                    <rect x="582" y="335" width="96" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-blue)" stroke-width="0.8"/>
                                    <text x="630" y="344.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-blue)">SURAT KLARIFIKASI</text>

                                    <!-- Ekspediting (Menerima Surat Klarifikasi) -> ISTEK Decision Diamond (Setuju?) -->
                                    <path d="M 472.5 378 V 434 Q 472.5 442 480.5 442 H 1058" fill="none" stroke="var(--diagram-blue)" stroke-width="1.4" marker-end="url(#rdtv-arr-blue)"/>
                                    <rect x="705" y="428" width="136" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-blue)" stroke-width="0.8"/>
                                    <text x="773" y="437.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-blue)">REVIEW TEKNIS SURAT KLARIFIKASI</text>

                                    <!-- ISTEK Diamond (Setuju? = TIDAK) -> Loop Kembali ke Vendor Decision Diamond (Setuju?) -->
                                    <path d="M 1102.5 422 V 238 Q 1102.5 230 1094.5 230 H 832" fill="none" stroke="var(--diagram-red)" stroke-width="1.2" stroke-dasharray="5,4" marker-end="url(#rdtv-arr-red)"
                                        :class="{'opacity-15': rdtvBranch === 'grs'}"/>
                                    <rect x="935" y="216" width="124" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-red)" stroke-width="0.8"
                                        :class="{'opacity-15': rdtvBranch === 'grs'}"/>
                                    <text x="997" y="225.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)"
                                        :class="{'opacity-15': rdtvBranch === 'grs'}">TIDAK · TOLAK KLARIFIKASI</text>
                                </g>

                                <!-- 4. JALUR DISETUJUI ISTEK (YA -> RECEIVING MENERBITKAN GRS -> SELESAI) -->
                                <g id="rdtv-grs-connectors" :class="{'opacity-15': rdtvBranch === 'retur'}">
                                    <!-- ISTEK Diamond (Setuju? = YA) -> Receiving (Menerbitkan GRS) -->
                                    <path d="M 1102.5 462 V 526 Q 1102.5 534 1094.5 534 H 252" fill="none" stroke="var(--diagram-green)" stroke-width="1.6" marker-end="url(#rdtv-arr-green)"/>
                                    <rect x="570" y="520" width="190" height="15" rx="3" fill="var(--diagram-paper)" stroke="var(--diagram-green)" stroke-width="0.8"/>
                                    <text x="665" y="530.5" text-anchor="middle" class="mono" font-size="7" font-weight="700" fill="var(--diagram-green)">YA · KLARIFIKASI DISETUJUI ISTEK (TERBIT GRS)</text>
                                </g>

                                <!-- ===================================================
                                     NODES (PROCESS BOXES & DECISION DIAMONDS - SLIDE 5)
                                     =================================================== -->

                                <!-- 0. START PILL: Mulai (Col 1) -->
                                <g class="node-group">
                                    <rect x="112.5" y="62" width="90" height="26" rx="13" fill="var(--diagram-blue)" stroke="var(--diagram-blue)" stroke-width="1"/>
                                    <text x="157.5" y="78" text-anchor="middle" class="mono" font-size="8.5" font-weight="800" letter-spacing="0.08em" fill="#ffffff">MULAI</text>
                                </g>

                                <!-- 1. RECEIVING: Menerbitkan RDTV (Col 1) -->
                                <g class="node-group">
                                    <rect x="63" y="112" width="189" height="56" rx="6" fill="var(--diagram-red-tint)" stroke="var(--diagram-red)" stroke-width="1.5"/>
                                    <rect x="69" y="118" width="26" height="11" rx="2" fill="var(--diagram-red)" fill-opacity="0.25"/>
                                    <text x="82" y="126.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">RCV</text>
                                    <text x="162" y="133" text-anchor="middle" font-size="10.5" font-weight="800" fill="var(--diagram-red)">Menerbitkan RDTV</text>
                                    <text x="162" y="146" text-anchor="middle" class="mono" font-size="7" font-weight="600" fill="var(--diagram-ink)">RETURN DELIVERY TO VENDOR</text>
                                    <rect x="98" y="150" width="124" height="12" rx="2" fill="var(--diagram-red)" fill-opacity="0.15"/>
                                    <text x="160" y="158.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">BARANG TIDAK LOLOS QC</text>
                                </g>

                                <!-- 2. EKSPEDITING: Menerima RDTV & Menginformasikan Vendor (Col 2) -->
                                <g class="node-group">
                                    <rect x="378" y="112" width="189" height="56" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-red)" stroke-width="1.2"/>
                                    <rect x="384" y="118" width="26" height="11" rx="2" fill="var(--diagram-red)" fill-opacity="0.2"/>
                                    <text x="397" y="126.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">EXP</text>
                                    <text x="477" y="133" text-anchor="middle" font-size="10" font-weight="700" fill="var(--diagram-ink)">Menerima RDTV &amp;</text>
                                    <text x="477" y="146" text-anchor="middle" font-size="10" font-weight="700" fill="var(--diagram-ink)">Menginformasikan Vendor</text>
                                    <text x="477" y="159" text-anchor="middle" class="mono" font-size="7" fill="var(--diagram-muted)">Penyampaian Resmi ke Pemasok</text>
                                </g>

                                <!-- 3. VENDOR: Menerima RDTV (Col 3) -->
                                <g class="node-group">
                                    <rect x="693" y="116" width="189" height="52" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-node-stroke)" stroke-width="1.2"/>
                                    <rect x="699" y="122" width="26" height="11" rx="2" fill="var(--diagram-chip)"/>
                                    <text x="712" y="130.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-ink)">VND</text>
                                    <text x="792" y="139" text-anchor="middle" font-size="10.5" font-weight="700" fill="var(--diagram-ink)">Menerima RDTV</text>
                                    <text x="792" y="154" text-anchor="middle" class="mono" font-size="7.5" fill="var(--diagram-muted)">Review Temuan Penolakan QC</text>
                                </g>

                                <!-- 4. VENDOR DECISION DIAMOND: Setuju? (Col 3) -->
                                <g id="rdtv-diamond-vendor">
                                    <polygon points="787.5,218 830,238 787.5,258 745,238" fill="var(--diagram-node)" stroke="var(--diagram-ink)" stroke-width="1.3"/>
                                    <text x="787.5" y="241" text-anchor="middle" font-size="8.5" font-weight="800" fill="var(--diagram-ink)">Setuju?</text>
                                </g>

                                <!-- 5. EKSPEDITING: Memerintahkan Pengambilan Barang (Col 2 - Jalur YA) -->
                                <g class="node-group" :class="{'opacity-15': rdtvBranch === 'klarifikasi' || rdtvBranch === 'grs'}">
                                    <rect x="378" y="212" width="189" height="52" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-red)" stroke-width="1.3"/>
                                    <rect x="384" y="218" width="26" height="11" rx="2" fill="var(--diagram-red)" fill-opacity="0.2"/>
                                    <text x="397" y="226.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">EXP</text>
                                    <text x="477" y="233" text-anchor="middle" font-size="9.5" font-weight="700" fill="var(--diagram-ink)">Memerintahkan</text>
                                    <text x="477" y="246" text-anchor="middle" font-size="9.5" font-weight="700" fill="var(--diagram-ink)">Pengambilan Barang</text>
                                    <text x="477" y="258" text-anchor="middle" class="mono" font-size="7" fill="var(--diagram-red)">Instruksi Retur Fisik</text>
                                </g>

                                <!-- 6. RECEIVING: Selesai (Retur Barang) (Col 1 - Jalur YA) -->
                                <g class="node-group" :class="{'opacity-15': rdtvBranch === 'klarifikasi' || rdtvBranch === 'grs'}">
                                    <rect x="63" y="212" width="189" height="52" rx="6" fill="var(--diagram-red-tint)" stroke="var(--diagram-red)" stroke-width="1.5"/>
                                    <rect x="69" y="218" width="26" height="11" rx="2" fill="var(--diagram-red)" fill-opacity="0.25"/>
                                    <text x="82" y="226.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">RCV</text>
                                    <text x="162" y="236" text-anchor="middle" font-size="10.5" font-weight="800" fill="var(--diagram-red)">Selesai (Retur Vendor)</text>
                                    <text x="162" y="251" text-anchor="middle" class="mono" font-size="7" font-weight="700" fill="var(--diagram-ink)">BARANG DIAMBIL VENDOR</text>
                                </g>

                                <!-- 7. VENDOR: Menerbitkan Surat Klarifikasi Kebenaran Barang (Col 3 - Jalur TIDAK) -->
                                <g class="node-group" :class="{'opacity-15': rdtvBranch === 'retur'}">
                                    <rect x="693" y="322" width="189" height="56" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-blue)" stroke-width="1.3"/>
                                    <rect x="699" y="328" width="26" height="11" rx="2" fill="var(--diagram-blue)" fill-opacity="0.18"/>
                                    <text x="712" y="336.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-blue)">VND</text>
                                    <text x="792" y="343" text-anchor="middle" font-size="9.5" font-weight="700" fill="var(--diagram-ink)">Menerbitkan Surat</text>
                                    <text x="792" y="356" text-anchor="middle" font-size="9.5" font-weight="700" fill="var(--diagram-ink)">Klarifikasi Kebenaran Barang</text>
                                    <text x="792" y="369" text-anchor="middle" class="mono" font-size="7" fill="var(--diagram-blue)">Sanggahan &amp; Bukti Teknis</text>
                                </g>

                                <!-- 8. EKSPEDITING: Menerima Surat Klarifikasi Kebenaran Barang (Col 2 - Jalur TIDAK) -->
                                <g class="node-group" :class="{'opacity-15': rdtvBranch === 'retur'}">
                                    <rect x="378" y="322" width="189" height="56" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-blue)" stroke-width="1.3"/>
                                    <rect x="384" y="328" width="26" height="11" rx="2" fill="var(--diagram-blue)" fill-opacity="0.18"/>
                                    <text x="397" y="336.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-blue)">EXP</text>
                                    <text x="477" y="343" text-anchor="middle" font-size="9.5" font-weight="700" fill="var(--diagram-ink)">Menerima Surat</text>
                                    <text x="477" y="356" text-anchor="middle" font-size="9.5" font-weight="700" fill="var(--diagram-ink)">Klarifikasi Kebenaran Barang</text>
                                    <text x="477" y="369" text-anchor="middle" class="mono" font-size="7" fill="var(--diagram-muted)">Diteruskan ke Unit ISTEK</text>
                                </g>

                                <!-- 9. ISTEK DECISION DIAMOND: Setuju? (Col 4) -->
                                <g id="rdtv-diamond-istek" :class="{'opacity-15': rdtvBranch === 'retur'}">
                                    <polygon points="1102.5,422 1145,442 1102.5,462 1060,442" fill="var(--diagram-node)" stroke="var(--diagram-ink)" stroke-width="1.3"/>
                                    <text x="1102.5" y="445" text-anchor="middle" font-size="8.5" font-weight="800" fill="var(--diagram-ink)">Setuju?</text>
                                </g>

                                <!-- 10. RECEIVING: Menerbitkan GRS & Selesai (Col 1 - Jalur YA ISTEK) -->
                                <g class="node-group" :class="{'opacity-15': rdtvBranch === 'retur'}">
                                    <rect x="63" y="506" width="189" height="56" rx="6" fill="var(--diagram-green-tint)" stroke="var(--diagram-green)" stroke-width="1.5"/>
                                    <rect x="69" y="512" width="26" height="11" rx="2" fill="var(--diagram-green)" fill-opacity="0.25"/>
                                    <text x="82" y="520.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-green)">RCV</text>
                                    <text x="162" y="527" text-anchor="middle" font-size="10.5" font-weight="800" fill="var(--diagram-green)">Menerbitkan GRS &rarr; Selesai</text>
                                    <text x="162" y="540" text-anchor="middle" class="mono" font-size="7" font-weight="600" fill="var(--diagram-ink)">GOOD RECEIVE SLIP (SAP 105)</text>
                                    <rect x="98" y="544" width="124" height="12" rx="2" fill="var(--diagram-green)" fill-opacity="0.15"/>
                                    <text x="160" y="552.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-green)">KLARIFIKASI DISETUJUI ISTEK</text>
                                </g>

                                <!-- ===================================================
                                     BOTTOM LEGEND & NOTATION STRIP (KONSISTEN)
                                     =================================================== -->
                                <g id="rdtv-diagram-legend">
                                    <line x1="20" y1="600" x2="1240" y2="600" stroke="var(--diagram-rule-strong)" stroke-width="0.8"/>
                                    <text x="24" y="618" class="mono" font-size="7.5" font-weight="700" letter-spacing="0.14em" fill="var(--diagram-soft)">NOTASI DIAGRAM ALUR BARANG TOLAK (RDTV)</text>

                                    <rect x="24" y="628" width="14" height="10" rx="2" fill="var(--diagram-node)" stroke="var(--diagram-node-stroke)" stroke-width="1"/>
                                    <text x="44" y="636" font-size="8.5" fill="var(--diagram-muted)">Tahap Aktivitas SOP</text>

                                    <polygon points="172,633 178,627 184,633 178,639" fill="var(--diagram-node)" stroke="var(--diagram-ink)" stroke-width="1"/>
                                    <text x="190" y="636" font-size="8.5" fill="var(--diagram-muted)">Keputusan (Setuju?)</text>

                                    <rect x="336" y="628" width="14" height="10" rx="2" fill="var(--diagram-green-tint)" stroke="var(--diagram-green)" stroke-width="1.2"/>
                                    <text x="356" y="636" font-size="8.5" font-weight="600" fill="var(--diagram-green)">Disetujui ISTEK (Terbit GRS)</text>

                                    <rect x="536" y="628" width="14" height="10" rx="2" fill="var(--diagram-red-tint)" stroke="var(--diagram-red)" stroke-width="1.2"/>
                                    <text x="556" y="636" font-size="8.5" font-weight="600" fill="var(--diagram-red)">Barang Tolak / Retur Vendor (RDTV)</text>

                                    <rect x="760" y="628" width="14" height="10" rx="2" fill="var(--diagram-node)" stroke="var(--diagram-blue)" stroke-width="1.2"/>
                                    <text x="780" y="636" font-size="8.5" font-weight="600" fill="var(--diagram-blue)">Surat Klarifikasi Kebenaran Barang</text>

                                    <rect x="990" y="628" width="14" height="10" rx="2" fill="var(--diagram-blue-tint)" stroke="var(--diagram-blue)" stroke-width="1.2"/>
                                    <text x="1010" y="636" font-size="8.5" font-weight="700" fill="var(--diagram-blue)">Receiving (Focal Hub)</text>
                                </g>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

                </div> <!-- END PILAR 1: PENERIMAAN BARANG -->

                <!-- ===================================================================== -->
                <!-- PILAR 2: INVOICING BARANG (SLIDE 7, 8, 9)                             -->
                <!-- ===================================================================== -->
                <div x-show="activePillar === 'invoicing_barang'"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    style="display: none;"
                    class="space-y-8">

                    <!-- Alur Proses Invoicing Barang (5 Swimlanes - Slide 8) -->
                    <div class="glass-panel rounded-3xl p-6 sm:p-8 border border-[#00529B]/25">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-8 pb-6 border-b border-slate-200/60 dark:border-white/10">
                            <div>
                                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#00529B]/10 border border-[#00529B]/20 text-[#00529B] dark:text-blue-300 text-[11px] font-black uppercase tracking-wider mb-2">
                                    Pilar 2 • Invoicing Barang
                                </div>
                                <h3 class="text-xl sm:text-2xl font-black text-slate-800 dark:text-white">
                                    Alur Proses Invoicing Barang (Sistem Mitraku &rarr; Keuangan PKT)
                                </h3>
                                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
                                    Tahapan verifikasi tagihan barang setelah GRS terbit hingga proses pembayaran oleh Departemen Keuangan PT Pupuk Kalimantan Timur.
                                </p>
                            </div>
                            <div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-amber-500/15 border border-amber-500/30 text-amber-700 dark:text-amber-300 text-xs font-black shrink-0">
                                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Batas Pengiriman Invoice Vendor: SEBELUM TANGGAL 20</span>
                            </div>
                        </div>

                        <!-- BAGAN DIAGRAM SWIMLANE 5 UNIT: ALUR PROSES INVOICING BARANG - SLIDE 8 (KONSISTEN DENGAN DIAGRAM DESIGN) -->
                        <div class="overflow-x-auto pb-4 mb-8 custom-scrollbar">
                            <div class="min-w-[1100px] select-none">
                                <svg id="invoicing-diagram" viewBox="0 0 1260 660" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Bagan Swimlane Alur Proses Invoicing Barang" class="w-full h-auto block rounded-2xl border border-slate-200/80 dark:border-white/10">
                                    <defs>
                                        <pattern id="dot-grid-inv" width="22" height="22" patternUnits="userSpaceOnUse">
                                            <circle cx="11" cy="11" r="0.85" fill="var(--diagram-dot)"/>
                                        </pattern>
                                        <marker id="inv-arr" markerWidth="8" markerHeight="6" refX="7" refY="3" orient="auto">
                                            <polygon points="0 0, 8 3, 0 6" fill="var(--diagram-muted)"/>
                                        </marker>
                                        <marker id="inv-arr-blue" markerWidth="8" markerHeight="6" refX="7" refY="3" orient="auto">
                                            <polygon points="0 0, 8 3, 0 6" fill="var(--diagram-blue)"/>
                                        </marker>
                                        <marker id="inv-arr-green" markerWidth="8" markerHeight="6" refX="7" refY="3" orient="auto">
                                            <polygon points="0 0, 8 3, 0 6" fill="var(--diagram-green)"/>
                                        </marker>
                                        <marker id="inv-arr-red" markerWidth="8" markerHeight="6" refX="7" refY="3" orient="auto">
                                            <polygon points="0 0, 8 3, 0 6" fill="var(--diagram-red)"/>
                                        </marker>
                                    </defs>

                                    <!-- Base Canvas Background -->
                                    <rect width="100%" height="100%" fill="var(--diagram-paper)"/>
                                    <rect width="100%" height="100%" fill="url(#dot-grid-inv)" opacity="0.6"/>

                                    <!-- 5 Vertical Swimlane Columns (252px each) -->
                                    <rect x="0" y="44" width="252" height="556" fill="var(--diagram-lane-odd)"/>
                                    <rect x="252" y="44" width="252" height="556" fill="var(--diagram-lane-even)"/>
                                    <rect x="504" y="44" width="252" height="556" fill="var(--diagram-lane-focal)"/>
                                    <rect x="756" y="44" width="252" height="556" fill="var(--diagram-lane-even)"/>
                                    <rect x="1008" y="44" width="252" height="556" fill="var(--diagram-lane-odd)"/>

                                    <!-- Vertical Dividers -->
                                    <g stroke="var(--diagram-rule)" stroke-width="0.8">
                                        <line x1="252" y1="0" x2="252" y2="600"/>
                                        <line x1="504" y1="0" x2="504" y2="600" stroke="var(--diagram-blue)" stroke-opacity="0.35" stroke-width="1"/>
                                        <line x1="756" y1="0" x2="756" y2="600" stroke="var(--diagram-blue)" stroke-opacity="0.35" stroke-width="1"/>
                                        <line x1="1008" y1="0" x2="1008" y2="600"/>
                                    </g>

                                    <line x1="0" y1="44" x2="1260" y2="44" stroke="var(--diagram-rule-strong)" stroke-width="1"/>

                                    <!-- COLUMN HEADERS (5 UNIT - SLIDE 8) -->
                                    <g text-anchor="middle">
                                        <rect x="26" y="8" width="200" height="28" rx="4" fill="var(--diagram-chip)"/>
                                        <text x="126" y="21" class="mono" font-size="8.5" font-weight="700" letter-spacing="0.08em" fill="var(--diagram-ink)">01 · STAF RECEIVING</text>
                                        <text x="126" y="31.5" font-size="7" fill="var(--diagram-muted)">Penerbitan GRS Barang</text>

                                        <rect x="278" y="8" width="200" height="28" rx="4" fill="var(--diagram-chip)"/>
                                        <text x="378" y="21" class="mono" font-size="8.5" font-weight="700" letter-spacing="0.08em" fill="var(--diagram-ink)">02 · AVP RCV &amp; VP PP&amp;P</text>
                                        <text x="378" y="31.5" font-size="7" fill="var(--diagram-muted)">Otorisasi GRS &amp; Mitraku</text>

                                        <rect x="530" y="6" width="200" height="32" rx="5" fill="var(--diagram-blue-tint)" stroke="var(--diagram-blue)" stroke-opacity="0.4" stroke-width="1"/>
                                        <text x="630" y="20" class="mono" font-size="8.5" font-weight="800" letter-spacing="0.08em" fill="var(--diagram-blue)">03 · STAF INVOICING</text>
                                        <text x="630" y="32.5" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-tangerine)">FOCAL HUB · SISTEM MITRAKU</text>

                                        <rect x="782" y="8" width="200" height="28" rx="4" fill="var(--diagram-chip)"/>
                                        <text x="882" y="21" class="mono" font-size="8.5" font-weight="700" letter-spacing="0.08em" fill="var(--diagram-ink)">04 · PENYEDIA BARANG</text>
                                        <text x="882" y="31.5" font-size="7" fill="var(--diagram-muted)">Pengiriman Tagihan (&lt; Tgl 20)</text>

                                        <rect x="1034" y="8" width="200" height="28" rx="4" fill="var(--diagram-chip)"/>
                                        <text x="1134" y="21" class="mono" font-size="8.5" font-weight="700" letter-spacing="0.08em" fill="var(--diagram-ink)">05 · KEUANGAN PKT</text>
                                        <text x="1134" y="31.5" font-size="7" fill="var(--diagram-muted)">Tindaklanjut &amp; Pembayaran</text>
                                    </g>

                                    <!-- CONNECTORS -->
                                    <!-- Mulai -> Penerbitan GRS -->
                                    <line x1="126" y1="90" x2="126" y2="112" stroke="var(--diagram-muted)" stroke-width="1.4" marker-end="url(#inv-arr)"/>
                                    <!-- Penerbitan GRS -> Approval GRS -->
                                    <line x1="210" y1="140" x2="294" y2="140" stroke="var(--diagram-muted)" stroke-width="1.4" marker-end="url(#inv-arr)"/>
                                    <!-- Approval GRS -> Penyampaian GRS ke Vendor -->
                                    <line x1="462" y1="140" x2="546" y2="140" stroke="var(--diagram-blue)" stroke-width="1.4" marker-end="url(#inv-arr-blue)"/>
                                    <!-- Penyampaian GRS -> Menerima GRS (Penyedia Barang) -->
                                    <line x1="714" y1="140" x2="798" y2="140" stroke="var(--diagram-blue)" stroke-width="1.4" marker-end="url(#inv-arr-blue)"/>
                                    <!-- Menerima GRS -> Mengirim Invoice (< Tgl 20) -->
                                    <line x1="882" y1="168" x2="882" y2="212" stroke="var(--diagram-muted)" stroke-width="1.4" marker-end="url(#inv-arr)"/>
                                    <!-- Mengirim Invoice -> Verifikasi Kelengkapan Dokumen (Staf Invoicing) -->
                                    <line x1="798" y1="240" x2="714" y2="240" stroke="var(--diagram-blue)" stroke-width="1.4" marker-end="url(#inv-arr-blue)"/>
                                    <!-- Verifikasi -> Lengkap? Diamond -->
                                    <line x1="630" y1="268" x2="630" y2="306" stroke="var(--diagram-blue)" stroke-width="1.4" marker-end="url(#inv-arr-blue)"/>
                                    <!-- Lengkap? = TIDAK -> Kembali ke Mengirim Invoice (Penyedia Barang) -->
                                    <path d="M 672 326 H 882 V 270" fill="none" stroke="var(--diagram-red)" stroke-width="1.2" stroke-dasharray="5,4" marker-end="url(#inv-arr-red)"/>
                                    <rect x="735" y="313" width="90" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-red)" stroke-width="0.8"/>
                                    <text x="780" y="322.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">TIDAK · REVISI</text>

                                    <!-- Lengkap? = YA -> Input Sistem Mitraku -->
                                    <line x1="630" y1="346" x2="630" y2="386" stroke="var(--diagram-green)" stroke-width="1.4" marker-end="url(#inv-arr-green)"/>
                                    <rect x="638" y="358" width="64" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-green)" stroke-width="0.8"/>
                                    <text x="670" y="367.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-green)">YA · LENGKAP</text>

                                    <!-- Input Sistem Mitraku -> Submit, Review & Approval Mitraku (Col 2) -->
                                    <line x1="546" y1="414" x2="462" y2="414" stroke="var(--diagram-blue)" stroke-width="1.4" marker-end="url(#inv-arr-blue)"/>
                                    <!-- Submit Mitraku -> Approve? Diamond (Col 2) -->
                                    <line x1="378" y1="442" x2="378" y2="480" stroke="var(--diagram-blue)" stroke-width="1.4" marker-end="url(#inv-arr-blue)"/>
                                    <!-- Approve? = TIDAK -> Kembali ke Input Sistem Mitraku -->
                                    <path d="M 420 500 H 525 V 428 H 546" fill="none" stroke="var(--diagram-red)" stroke-width="1.2" stroke-dasharray="5,4" marker-end="url(#inv-arr-red)"/>
                                    <rect x="432" y="487" width="84" height="13" rx="2" fill="var(--diagram-paper)" stroke="var(--diagram-red)" stroke-width="0.8"/>
                                    <text x="474" y="496.5" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-red)">TIDAK · REVISI</text>

                                    <!-- Approve? = YA -> Tindaklanjut di Keuangan s.d. Pembayaran (Col 5) -->
                                    <path d="M 378 520 V 544 H 1050" fill="none" stroke="var(--diagram-green)" stroke-width="1.6" marker-end="url(#inv-arr-green)"/>
                                    <rect x="620" y="531" width="160" height="15" rx="3" fill="var(--diagram-paper)" stroke="var(--diagram-green)" stroke-width="0.8"/>
                                    <text x="700" y="541.5" text-anchor="middle" class="mono" font-size="7" font-weight="700" fill="var(--diagram-green)">YA · APPROVED MITRAKU &rarr; KEUANGAN</text>

                                    <!-- NODES -->
                                    <g class="node-group">
                                        <rect x="81" y="62" width="90" height="26" rx="13" fill="var(--diagram-blue)"/>
                                        <text x="126" y="78" text-anchor="middle" class="mono" font-size="8.5" font-weight="800" fill="#ffffff">MULAI</text>
                                    </g>

                                    <g class="node-group">
                                        <rect x="42" y="112" width="168" height="56" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-node-stroke)" stroke-width="1.2"/>
                                        <text x="126" y="136" text-anchor="middle" font-size="10.5" font-weight="800" fill="var(--diagram-ink)">Penerbitan GRS</text>
                                        <text x="126" y="151" text-anchor="middle" class="mono" font-size="7" fill="var(--diagram-muted)">Good Receive Slip</text>
                                    </g>

                                    <g class="node-group">
                                        <rect x="294" y="112" width="168" height="56" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-blue)" stroke-width="1.2"/>
                                        <text x="378" y="136" text-anchor="middle" font-size="10.5" font-weight="800" fill="var(--diagram-ink)">Approval GRS</text>
                                        <text x="378" y="151" text-anchor="middle" class="mono" font-size="7" fill="var(--diagram-muted)">Otorisasi AVP &amp; VP PP&amp;P</text>
                                    </g>

                                    <g class="node-group">
                                        <rect x="546" y="112" width="168" height="56" rx="6" fill="var(--diagram-blue-tint)" stroke="var(--diagram-blue)" stroke-width="1.4"/>
                                        <text x="630" y="134" text-anchor="middle" font-size="10" font-weight="800" fill="var(--diagram-ink)">Penyampaian GRS</text>
                                        <text x="630" y="149" text-anchor="middle" class="mono" font-size="7" fill="var(--diagram-blue)">Distribusi Resmi ke Vendor</text>
                                    </g>

                                    <g class="node-group">
                                        <rect x="798" y="112" width="168" height="56" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-node-stroke)" stroke-width="1.2"/>
                                        <text x="882" y="136" text-anchor="middle" font-size="10.5" font-weight="800" fill="var(--diagram-ink)">Menerima GRS</text>
                                        <text x="882" y="151" text-anchor="middle" class="mono" font-size="7" fill="var(--diagram-muted)">Dasar Pengajuan Tagihan</text>
                                    </g>

                                    <g class="node-group">
                                        <rect x="798" y="212" width="168" height="56" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-tangerine)" stroke-width="1.4"/>
                                        <text x="882" y="232" text-anchor="middle" font-size="9.5" font-weight="800" fill="var(--diagram-ink)">Kirim Invoice &amp; Berkas</text>
                                        <text x="882" y="246" text-anchor="middle" class="mono" font-size="7" font-weight="700" fill="var(--diagram-tangerine)">WAJIB SEBELUM TGL 20</text>
                                        <text x="882" y="259" text-anchor="middle" class="mono" font-size="6.5" fill="var(--diagram-muted)">Sesuai GRS &amp; 16 Dokumen</text>
                                    </g>

                                    <g class="node-group">
                                        <rect x="546" y="212" width="168" height="56" rx="6" fill="var(--diagram-blue-tint)" stroke="var(--diagram-blue)" stroke-width="1.4"/>
                                        <text x="630" y="233" text-anchor="middle" font-size="9.5" font-weight="800" fill="var(--diagram-ink)">Terima &amp; Verifikasi</text>
                                        <text x="630" y="247" text-anchor="middle" font-size="9.5" font-weight="800" fill="var(--diagram-ink)">Kelengkapan Dokumen</text>
                                        <text x="630" y="260" text-anchor="middle" class="mono" font-size="6.5" fill="var(--diagram-blue)">Cek 16 Dokumen Tagihan</text>
                                    </g>

                                    <g>
                                        <polygon points="630,306 672,326 630,346 588,326" fill="var(--diagram-node)" stroke="var(--diagram-ink)" stroke-width="1.3"/>
                                        <text x="630" y="329" text-anchor="middle" font-size="8" font-weight="800" fill="var(--diagram-ink)">Lengkap?</text>
                                    </g>

                                    <g class="node-group">
                                        <rect x="546" y="386" width="168" height="56" rx="6" fill="var(--diagram-blue-tint)" stroke="var(--diagram-blue)" stroke-width="1.5"/>
                                        <text x="630" y="409" text-anchor="middle" font-size="10.5" font-weight="800" fill="var(--diagram-blue)">Input Sistem Mitraku</text>
                                        <text x="630" y="425" text-anchor="middle" class="mono" font-size="7" font-weight="700" fill="var(--diagram-ink)">REGISTRASI TAGIHAN DIGITAL</text>
                                    </g>

                                    <g class="node-group">
                                        <rect x="294" y="386" width="168" height="56" rx="6" fill="var(--diagram-node)" stroke="var(--diagram-blue)" stroke-width="1.3"/>
                                        <text x="378" y="408" text-anchor="middle" font-size="9.5" font-weight="800" fill="var(--diagram-ink)">Submit, Review &amp;</text>
                                        <text x="378" y="422" text-anchor="middle" font-size="9.5" font-weight="800" fill="var(--diagram-ink)">Approval Mitraku</text>
                                        <text x="378" y="435" text-anchor="middle" class="mono" font-size="6.5" fill="var(--diagram-muted)">AVP Receiving &amp; VP PP&amp;P</text>
                                    </g>

                                    <g>
                                        <polygon points="378,480 420,500 378,520 336,500" fill="var(--diagram-node)" stroke="var(--diagram-ink)" stroke-width="1.3"/>
                                        <text x="378" y="503" text-anchor="middle" font-size="8" font-weight="800" fill="var(--diagram-ink)">Approve?</text>
                                    </g>

                                    <g class="node-group">
                                        <rect x="1050" y="512" width="176" height="56" rx="6" fill="var(--diagram-green-tint)" stroke="var(--diagram-green)" stroke-width="1.5"/>
                                        <text x="1138" y="532" text-anchor="middle" font-size="9.5" font-weight="800" fill="var(--diagram-green)">Tindaklanjut Keuangan</text>
                                        <text x="1138" y="545" text-anchor="middle" font-size="9.5" font-weight="800" fill="var(--diagram-green)">s.d. Pembayaran &rarr; Selesai</text>
                                        <text x="1138" y="559" text-anchor="middle" class="mono" font-size="6.5" font-weight="700" fill="var(--diagram-ink)">PEMBAYARAN VENDOR PKT</text>
                                    </g>

                                    <!-- BOTTOM LEGEND STRIP -->
                                    <g id="inv-diagram-legend">
                                        <line x1="20" y1="600" x2="1240" y2="600" stroke="var(--diagram-rule-strong)" stroke-width="0.8"/>
                                        <text x="24" y="618" class="mono" font-size="7.5" font-weight="700" letter-spacing="0.14em" fill="var(--diagram-soft)">NOTASI DIAGRAM ALUR INVOICING BARANG</text>

                                        <rect x="24" y="628" width="14" height="10" rx="2" fill="var(--diagram-node)" stroke="var(--diagram-node-stroke)" stroke-width="1"/>
                                        <text x="44" y="636" font-size="8.5" fill="var(--diagram-muted)">Tahap Aktivitas Invoicing</text>

                                        <polygon points="186,633 192,627 198,633 192,639" fill="var(--diagram-node)" stroke="var(--diagram-ink)" stroke-width="1"/>
                                        <text x="204" y="636" font-size="8.5" fill="var(--diagram-muted)">Verifikasi (Lengkap? / Approve?)</text>

                                        <rect x="380" y="628" width="14" height="10" rx="2" fill="var(--diagram-blue-tint)" stroke="var(--diagram-blue)" stroke-width="1.2"/>
                                        <text x="400" y="636" font-size="8.5" font-weight="700" fill="var(--diagram-blue)">Staf Invoicing (Sistem Mitraku)</text>

                                        <rect x="595" y="628" width="14" height="10" rx="2" fill="var(--diagram-green-tint)" stroke="var(--diagram-green)" stroke-width="1.2"/>
                                        <text x="615" y="636" font-size="8.5" font-weight="600" fill="var(--diagram-green)">Disetujui &amp; Lanjut Pembayaran Keuangan</text>

                                        <rect x="860" y="628" width="14" height="10" rx="2" fill="var(--diagram-node)" stroke="var(--diagram-tangerine)" stroke-width="1.2"/>
                                        <text x="880" y="636" font-size="8.5" font-weight="700" fill="var(--diagram-tangerine)">Batas Kirim Invoice: Sebelum Tanggal 20</text>
                                    </g>
                                </svg>
                            </div>
                        </div>

                        <!-- 5 Swimlanes Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                            <!-- 1. STAF RECEIVING BARANG -->
                            <div class="rounded-2xl p-4 bg-amber-500/5 dark:bg-amber-500/10 border border-amber-500/25">
                                <div class="text-[11px] font-black uppercase tracking-wider px-2.5 py-1 rounded-lg bg-amber-500 text-white inline-block mb-3">
                                    1. Staf Receiving Barang
                                </div>
                                <div class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-amber-500/20">
                                    <div class="text-[10px] font-black text-amber-600 dark:text-amber-400 uppercase">Tahap Awal</div>
                                    <div class="text-xs font-bold text-slate-800 dark:text-white mt-0.5">Mulai &rarr; Penerbitan GRS</div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Menerbitkan dokumen Good Receive Slip (GRS) atas barang yang telah lolos QC.</p>
                                </div>
                            </div>

                            <!-- 2. AVP RECEIVING & VP PP&P -->
                            <div class="rounded-2xl p-4 bg-purple-500/5 dark:bg-purple-500/10 border border-purple-500/25">
                                <div class="text-[11px] font-black uppercase tracking-wider px-2.5 py-1 rounded-lg bg-purple-600 text-white inline-block mb-3">
                                    2. AVP Receiving &amp; VP PP&amp;P
                                </div>
                                <div class="space-y-2.5">
                                    <div class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-purple-500/20">
                                        <div class="text-[10px] font-black text-purple-600 dark:text-purple-400 uppercase">Otorisasi GRS</div>
                                        <div class="text-xs font-bold text-slate-800 dark:text-white mt-0.5">Approval GRS</div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Pengesahan GRS sebelum disampaikan ke Vendor.</p>
                                    </div>
                                    <div class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-purple-500/20">
                                        <div class="text-[10px] font-black text-purple-600 dark:text-purple-400 uppercase">Otorisasi Tagihan</div>
                                        <div class="text-xs font-bold text-slate-800 dark:text-white mt-0.5">Submit, Review &amp; Approval Mitraku</div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Jika <strong>Approve (YA)</strong> diteruskan ke Keuangan; jika <strong>TIDAK</strong> kembali ke Input Mitraku.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. STAF INVOICING BARANG -->
                            <div class="rounded-2xl p-4 bg-[#00529B]/5 dark:bg-[#00529B]/15 border border-[#00529B]/25">
                                <div class="text-[11px] font-black uppercase tracking-wider px-2.5 py-1 rounded-lg bg-[#00529B] text-white inline-block mb-3">
                                    3. Staf Invoicing Barang
                                </div>
                                <div class="space-y-2.5">
                                    <div class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-[#00529B]/20">
                                        <div class="text-[10px] font-black text-[#00529B] dark:text-blue-400 uppercase">Distribusi GRS</div>
                                        <div class="text-xs font-bold text-slate-800 dark:text-white mt-0.5">Penyampaian GRS ke Vendor</div>
                                    </div>
                                    <div class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-[#00529B]/20">
                                        <div class="text-[10px] font-black text-[#00529B] dark:text-blue-400 uppercase">Verifikasi Berkas</div>
                                        <div class="text-xs font-bold text-slate-800 dark:text-white mt-0.5">Menerima &amp; Verifikasi Dokumen Penagihan</div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Jika <strong>Lengkap (YA)</strong> &rarr; <strong>Input Sistem Mitraku</strong>. Jika <strong>TIDAK</strong> &rarr; dikembalikan ke Penyedia Barang.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. PENYEDIA BARANG -->
                            <div class="rounded-2xl p-4 bg-sky-500/5 dark:bg-sky-500/10 border border-sky-500/25">
                                <div class="text-[11px] font-black uppercase tracking-wider px-2.5 py-1 rounded-lg bg-sky-600 text-white inline-block mb-3">
                                    4. Penyedia Barang (Vendor)
                                </div>
                                <div class="space-y-2.5">
                                    <div class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-sky-500/20">
                                        <div class="text-[10px] font-black text-sky-600 dark:text-sky-400 uppercase">Penerimaan GRS</div>
                                        <div class="text-xs font-bold text-slate-800 dark:text-white mt-0.5">Menerima GRS Resmi</div>
                                    </div>
                                    <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/25">
                                        <div class="text-[10px] font-black text-amber-600 dark:text-amber-400 uppercase">Penagihan (&lt; Tgl 20)</div>
                                        <div class="text-xs font-bold text-slate-800 dark:text-white mt-0.5">Mengirim Invoice / Tagihan Sesuai GRS</div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Wajib dilengkapi seluruh dokumen persyaratan penagihan sebelum tanggal 20.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- 5. KEUANGAN PKT -->
                            <div class="rounded-2xl p-4 bg-emerald-500/5 dark:bg-emerald-500/10 border border-emerald-500/25">
                                <div class="text-[11px] font-black uppercase tracking-wider px-2.5 py-1 rounded-lg bg-emerald-600 text-white inline-block mb-3">
                                    5. Keuangan PKT
                                </div>
                                <div class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-emerald-500/20">
                                    <div class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase">Tahap Akhir</div>
                                    <div class="text-xs font-bold text-slate-800 dark:text-white mt-0.5">Tindaklanjut Proses di Keuangan s.d. Pembayaran &rarr; Selesai</div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Eksekusi pembayaran tagihan kepada Penyedia Barang sesuai persetujuan Mitraku.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Checklist 16 Kelengkapan Dokumen Invoicing Barang (Slide 9) -->
                    <div class="glass-panel rounded-3xl p-6 sm:p-8 border border-slate-200/70 dark:border-white/10">
                        <div class="mb-6">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-[11px] font-black uppercase tracking-wider mb-2">
                                Standar Verifikasi Berkas Tagihan
                            </div>
                            <h4 class="text-lg sm:text-xl font-black text-slate-800 dark:text-white">
                                16 Kelengkapan Dokumen Invoicing Barang
                            </h4>
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
                                Daftar dokumen wajib umum dan dokumen khusus komoditas yang diverifikasi oleh Staf Invoicing Barang sebelum diinput ke Sistem Mitraku.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                            <!-- 10 Dokumen Umum (Col 7) -->
                            <div class="lg:col-span-7 rounded-2xl p-5 bg-white/60 dark:bg-slate-900/50 border border-slate-200/70 dark:border-white/10">
                                <h5 class="text-xs font-black uppercase tracking-wider text-[#00529B] dark:text-blue-400 mb-4 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-[#00529B]"></span>
                                    A. Dokumen Kelengkapan Umum (10 Dokumen)
                                </h5>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-100/80 dark:bg-white/5">
                                        <span class="w-6 h-6 rounded-lg bg-[#00529B] text-white font-black flex items-center justify-center shrink-0 text-[11px]">1</span>
                                        <span class="font-bold text-slate-800 dark:text-white">PO Asli (Purchase Order)</span>
                                    </div>
                                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-100/80 dark:bg-white/5">
                                        <span class="w-6 h-6 rounded-lg bg-[#00529B] text-white font-black flex items-center justify-center shrink-0 text-[11px]">2</span>
                                        <span class="font-bold text-slate-800 dark:text-white">Invoice (Nota Penjualan)</span>
                                    </div>
                                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-100/80 dark:bg-white/5">
                                        <span class="w-6 h-6 rounded-lg bg-[#00529B] text-white font-black flex items-center justify-center shrink-0 text-[11px]">3</span>
                                        <span class="font-bold text-slate-800 dark:text-white">GRS (Good Receive Slip)</span>
                                    </div>
                                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-100/80 dark:bg-white/5">
                                        <span class="w-6 h-6 rounded-lg bg-[#00529B] text-white font-black flex items-center justify-center shrink-0 text-[11px]">4</span>
                                        <span class="font-bold text-slate-800 dark:text-white">DO (Delivery Order)</span>
                                    </div>
                                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-100/80 dark:bg-white/5">
                                        <span class="w-6 h-6 rounded-lg bg-[#00529B] text-white font-black flex items-center justify-center shrink-0 text-[11px]">5</span>
                                        <span class="font-bold text-slate-800 dark:text-white">Faktur Pajak</span>
                                    </div>
                                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-100/80 dark:bg-white/5">
                                        <span class="w-6 h-6 rounded-lg bg-[#00529B] text-white font-black flex items-center justify-center shrink-0 text-[11px]">6</span>
                                        <span class="font-bold text-slate-800 dark:text-white">ENOFA (No. Seri Faktur Pajak)</span>
                                    </div>
                                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-100/80 dark:bg-white/5">
                                        <span class="w-6 h-6 rounded-lg bg-[#00529B] text-white font-black flex items-center justify-center shrink-0 text-[11px]">7</span>
                                        <span class="font-bold text-slate-800 dark:text-white">SPPKP (Pengukuhan PKP)</span>
                                    </div>
                                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-100/80 dark:bg-white/5">
                                        <span class="w-6 h-6 rounded-lg bg-[#00529B] text-white font-black flex items-center justify-center shrink-0 text-[11px]">8</span>
                                        <span class="font-bold text-slate-800 dark:text-white">Bank Garansi (Jaminan Pelaksana)</span>
                                    </div>
                                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-100/80 dark:bg-white/5">
                                        <span class="w-6 h-6 rounded-lg bg-[#00529B] text-white font-black flex items-center justify-center shrink-0 text-[11px]">9</span>
                                        <span class="font-bold text-slate-800 dark:text-white">SKF (Surat Keterangan Fiskal)</span>
                                    </div>
                                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-100/80 dark:bg-white/5">
                                        <span class="w-6 h-6 rounded-lg bg-[#00529B] text-white font-black flex items-center justify-center shrink-0 text-[11px]">10</span>
                                        <span class="font-bold text-slate-800 dark:text-white">Surat Pernyataan</span>
                                    </div>
                                </div>
                            </div>

                            <!-- 6 Dokumen Khusus Komoditas (Col 5) -->
                            <div class="lg:col-span-5 rounded-2xl p-5 bg-amber-500/5 dark:bg-amber-500/10 border border-amber-500/25">
                                <h5 class="text-xs font-black uppercase tracking-wider text-amber-700 dark:text-amber-300 mb-4 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-[#F47920]"></span>
                                    B. Dokumen Khusus Komoditas (6 Dokumen)
                                </h5>
                                <div class="space-y-2.5 text-xs">
                                    <div class="p-2.5 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-amber-500/20 flex items-start justify-between gap-2">
                                        <div>
                                            <span class="font-black text-slate-800 dark:text-white">11. LPKB (Laporan Pemeriksaan Kualitas Barang)</span>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Khusus Bahan Baku &amp; Chemical</p>
                                        </div>
                                        <span class="px-2 py-0.5 rounded bg-amber-500/15 text-amber-700 dark:text-amber-300 text-[10px] font-bold shrink-0">Chemical</span>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-amber-500/20 flex items-start justify-between gap-2">
                                        <div>
                                            <span class="font-black text-slate-800 dark:text-white">12. COA (Certificate of Analysis)</span>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Batubara, Bahan Baku Impor, Methanol, Karung</p>
                                        </div>
                                        <span class="px-2 py-0.5 rounded bg-amber-500/15 text-amber-700 dark:text-amber-300 text-[10px] font-bold shrink-0">Uji Mutu</span>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-amber-500/20 flex items-start justify-between gap-2">
                                        <div>
                                            <span class="font-black text-slate-800 dark:text-white">13. Bill of Lading (B/L)</span>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Khusus Pengiriman Batubara</p>
                                        </div>
                                        <span class="px-2 py-0.5 rounded bg-amber-500/15 text-amber-700 dark:text-amber-300 text-[10px] font-bold shrink-0">Batubara</span>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-amber-500/20 flex items-start justify-between gap-2">
                                        <div>
                                            <span class="font-black text-slate-800 dark:text-white">14. Keterangan Penyesuaian Harga</span>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Khusus Batubara &amp; BBM</p>
                                        </div>
                                        <span class="px-2 py-0.5 rounded bg-amber-500/15 text-amber-700 dark:text-amber-300 text-[10px] font-bold shrink-0">Batubara/BBM</span>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-amber-500/20 flex items-start justify-between gap-2">
                                        <div>
                                            <span class="font-black text-slate-800 dark:text-white">15. Jadwal Pengiriman</span>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Khusus Komoditas Batubara</p>
                                        </div>
                                        <span class="px-2 py-0.5 rounded bg-amber-500/15 text-amber-700 dark:text-amber-300 text-[10px] font-bold shrink-0">Batubara</span>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-amber-500/20 flex items-start justify-between gap-2">
                                        <div>
                                            <span class="font-black text-slate-800 dark:text-white">16. BA Transfering (Berita Acara)</span>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Khusus Komoditas Methanol</p>
                                        </div>
                                        <span class="px-2 py-0.5 rounded bg-amber-500/15 text-amber-700 dark:text-amber-300 text-[10px] font-bold shrink-0">Methanol</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- END PILAR 2: INVOICING BARANG -->

                <!-- ===================================================================== -->
                <!-- PILAR 3: PENERIMAAN & INVOICING JASA (SLIDE 10, 11, 12, 13, 14)       -->
                <!-- ===================================================================== -->
                <div x-show="activePillar === 'jasa'"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    style="display: none;"
                    class="space-y-8">

                    <!-- 3 Tahap Inti Penerimaan Jasa (Slide 11) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="glass-panel rounded-2xl p-5 border border-emerald-500/25">
                            <div class="text-[11px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400 mb-1">Tahap Inti 01</div>
                            <h4 class="text-base font-black text-slate-800 dark:text-white mb-1">Dokumen Penagihan Lengkap</h4>
                            <p class="text-xs text-slate-600 dark:text-slate-400">Verifikasi kelengkapan BAPP, SIK, BATE, Order Kerja/PO, Cost Code &amp; Cost Center, serta Surat Pengantar User.</p>
                        </div>
                        <div class="glass-panel rounded-2xl p-5 border border-[#00529B]/25">
                            <div class="text-[11px] font-black uppercase tracking-wider text-[#00529B] dark:text-blue-400 mb-1">Tahap Inti 02</div>
                            <h4 class="text-base font-black text-slate-800 dark:text-white mb-1">Input Penagihan Sistem SAP</h4>
                            <p class="text-xs text-slate-600 dark:text-slate-400">Pengecekan status PO di sistem SAP, penginputan nilai realisasi pekerjaan jasa, dan pencetakan dokumen SA.</p>
                        </div>
                        <div class="glass-panel rounded-2xl p-5 border border-amber-500/25">
                            <div class="text-[11px] font-black uppercase tracking-wider text-[#F47920] mb-1">Tahap Inti 03</div>
                            <h4 class="text-base font-black text-slate-800 dark:text-white mb-1">Service Acceptance (SA)</h4>
                            <p class="text-xs text-slate-600 dark:text-slate-400">Penerbitan &amp; pengesahan <strong>Berita Acara Penerimaan Pekerjaan (SA)</strong> untuk proses pembayaran di Kasir/Keuangan.</p>
                        </div>
                    </div>

                    <!-- Alur Proses Penagihan & Invoice Pekerjaan Jasa (Slide 12 & 13) -->
                    <div class="glass-panel rounded-3xl p-6 sm:p-8 border border-emerald-500/25">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-8 pb-6 border-b border-slate-200/60 dark:border-white/10">
                            <div>
                                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-[11px] font-black uppercase tracking-wider mb-2">
                                    Pilar 3 • Penerimaan &amp; Invoicing Jasa
                                </div>
                                <h3 class="text-xl sm:text-2xl font-black text-slate-800 dark:text-white">
                                    Alur Proses Penagihan &amp; Penerbitan Service Acceptance (SA)
                                </h3>
                                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
                                    Sinergi antara Penyedia Jasa, Unit Kerja Pengguna (User), Staf Penerimaan Jasa Dept. PP&amp;P, dan Departemen Keuangan.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                            <!-- Fase Awal & 6 Dokumen User (Col 5) -->
                            <div class="lg:col-span-5 space-y-4">
                                <div class="rounded-2xl p-5 bg-white/70 dark:bg-slate-900/60 border border-slate-200/80 dark:border-white/10">
                                    <div class="text-xs font-black uppercase tracking-wider text-[#00529B] dark:text-blue-400 mb-3">
                                        Fase 1 • Pelaksanaan &amp; Verifikasi User
                                    </div>
                                    <ol class="space-y-2.5 text-xs text-slate-600 dark:text-slate-300">
                                        <li class="flex items-start gap-2.5">
                                            <span class="w-5 h-5 rounded-full bg-[#00529B] text-white font-black text-[10px] flex items-center justify-center shrink-0 mt-0.5">1</span>
                                            <span><strong>START:</strong> Dokumen <strong>BATE</strong> &amp; <strong>SIK</strong> terbit, Penyedia Jasa memulai dan menyelesaikan pekerjaan.</span>
                                        </li>
                                        <li class="flex items-start gap-2.5">
                                            <span class="w-5 h-5 rounded-full bg-[#00529B] text-white font-black text-[10px] flex items-center justify-center shrink-0 mt-0.5">2</span>
                                            <span><strong>BAPP Diterbitkan User:</strong> Penyedia Jasa mengajukan penagihan dengan membawa Invoice &amp; Copy Order Kerja.</span>
                                        </li>
                                        <li class="flex items-start gap-2.5">
                                            <span class="w-5 h-5 rounded-full bg-[#00529B] text-white font-black text-[10px] flex items-center justify-center shrink-0 mt-0.5">3</span>
                                            <span><strong>Verifikasi User:</strong> Unit Kerja (User) memverifikasi dokumen penagihan dan menerbitkan 6 berkas pengantar ke Dept. PP&amp;P:</span>
                                        </li>
                                    </ol>
                                    <div class="mt-3 pt-3 border-t border-slate-200/60 dark:border-white/10 grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px]">
                                        <div class="p-2 rounded-lg bg-slate-100 dark:bg-white/5 font-bold text-slate-700 dark:text-slate-200">1. Copy Invoice</div>
                                        <div class="p-2 rounded-lg bg-slate-100 dark:bg-white/5 font-bold text-slate-700 dark:text-slate-200">2. BAPP Resmi</div>
                                        <div class="p-2 rounded-lg bg-slate-100 dark:bg-white/5 font-bold text-slate-700 dark:text-slate-200">3. SIK (Instruksi Kerja)</div>
                                        <div class="p-2 rounded-lg bg-slate-100 dark:bg-white/5 font-bold text-slate-700 dark:text-slate-200">4. BATE (Tanggal Efektif)</div>
                                        <div class="p-2 rounded-lg bg-slate-100 dark:bg-white/5 font-bold text-slate-700 dark:text-slate-200">5. Cost Code &amp; Cost Center</div>
                                        <div class="p-2 rounded-lg bg-slate-100 dark:bg-white/5 font-bold text-slate-700 dark:text-slate-200">6. Surat Pengantar Keuangan</div>
                                    </div>
                                </div>

                                <!-- 5 Dokumen Utama Invoicing Jasa (Slide 14) -->
                                <div class="rounded-2xl p-5 bg-amber-500/5 dark:bg-amber-500/10 border border-amber-500/25">
                                    <div class="text-xs font-black uppercase tracking-wider text-amber-700 dark:text-amber-300 mb-2">
                                        5 Dokumen Utama Invoicing Jasa
                                    </div>
                                    <div class="flex flex-wrap gap-2 text-xs font-bold">
                                        <span class="px-3 py-1.5 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-amber-500/20 text-slate-800 dark:text-white">1. BAPP</span>
                                        <span class="px-3 py-1.5 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-amber-500/20 text-slate-800 dark:text-white">2. SIK</span>
                                        <span class="px-3 py-1.5 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-amber-500/20 text-slate-800 dark:text-white">3. Lampiran PO / Order Kerja</span>
                                        <span class="px-3 py-1.5 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-amber-500/20 text-slate-800 dark:text-white">4. BATE</span>
                                        <span class="px-3 py-1.5 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-amber-500/20 text-slate-800 dark:text-white">5. Invoice</span>
                                    </div>
                                </div>
                            </div>

                            <!-- 7 Langkah Proses Dept. PP&P & Penyelesaian Keuangan (Col 7) -->
                            <div class="lg:col-span-7 space-y-4">
                                <div class="rounded-2xl p-5 bg-emerald-500/5 dark:bg-emerald-500/10 border border-emerald-500/25">
                                    <div class="flex items-center justify-between mb-4">
                                        <span class="text-xs font-black uppercase tracking-wider text-emerald-700 dark:text-emerald-300">
                                            Fase 2 • 7 Langkah Proses Dept. PP&amp;P (Staf Penerimaan Jasa)
                                        </span>
                                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 text-[10px] font-black">SAP Service Entry</span>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                                        <div class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-emerald-500/20">
                                            <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase">Langkah 1</span>
                                            <div class="font-bold text-slate-800 dark:text-white mt-0.5">Pengecekan Kelengkapan Dokumen</div>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Jika tidak lengkap, dikembalikan ke User / Penyedia Jasa.</p>
                                        </div>
                                        <div class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-emerald-500/20">
                                            <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase">Langkah 2</span>
                                            <div class="font-bold text-slate-800 dark:text-white mt-0.5">Pengecekan Status PO di Sistem SAP</div>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Memastikan alokasi anggaran &amp; line item jasa aktif.</p>
                                        </div>
                                        <div class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-emerald-500/20">
                                            <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase">Langkah 3</span>
                                            <div class="font-bold text-slate-800 dark:text-white mt-0.5">Penginputan ke Sistem SAP</div>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Pembuatan Service Entry Sheet / Service Acceptance.</p>
                                        </div>
                                        <div class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-emerald-500/20">
                                            <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase">Langkah 4</span>
                                            <div class="font-bold text-slate-800 dark:text-white mt-0.5">Pencetakan SA yang Telah Diinput</div>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Mencetak lembar resmi Service Acceptance (SA).</p>
                                        </div>
                                        <div class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-emerald-500/20">
                                            <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase">Langkah 5</span>
                                            <div class="font-bold text-slate-800 dark:text-white mt-0.5">Verifikasi oleh Pejabat Berwenang</div>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Review &amp; tanda tangan pengesahan pejabat terkait.</p>
                                        </div>
                                        <div class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-emerald-500/20">
                                            <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase">Langkah 6 &amp; 7</span>
                                            <div class="font-bold text-slate-800 dark:text-white mt-0.5">Dokumentasi, Monitoring &amp; Penyerahan SA</div>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Dokumen SA siap diserahkan kembali ke User / Penyedia Jasa untuk dilengkapi Faktur Pajak &amp; dibawa ke Kasir / Dept. Keuangan (END).</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Kontak Resmi Konfirmasi SA (Slide 12) -->
                                <div class="rounded-2xl p-5 bg-gradient-to-r from-[#00529B]/10 via-emerald-500/10 to-[#F47920]/10 border border-[#00529B]/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <div class="text-[11px] font-black uppercase tracking-wider text-[#00529B] dark:text-blue-300">
                                            Layanan Konfirmasi Proses Input SA ke Sistem
                                        </div>
                                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">
                                            Hubungi kanal resmi Penerimaan Jasa Dept. PP&amp;P untuk konfirmasi status penerbitan Service Acceptance (SA):
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                                        <a href="https://wa.me/6285752451225" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-sm transition-colors">
                                            <span>+62 857-5245-1225</span>
                                        </a>
                                        <a href="mailto:receiving.jasa@pupukkaltim.com" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#00529B] hover:bg-blue-700 text-white text-xs font-bold shadow-sm transition-colors">
                                            <span>receiving.jasa@pupukkaltim.com</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- END PILAR 3: PENERIMAAN & INVOICING JASA -->

        </div>
    </section>

    <!-- 5. KATEGORI MATERIAL GUDANG (ZSP, ZRM, ZPM, ZSM, ZFP) -->
    <section class="relative z-10 py-24 px-5 md:px-12 lg:px-24">


        <div class="container mx-auto max-w-7xl relative z-10">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-[#F47920]/10 border border-[#F47920]/20 text-[#F47920] text-xs font-bold tracking-widest uppercase mb-4">
                    Standar SAP Material Type (MTART)
                </div>
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-800 dark:text-white tracking-tight mb-5">
                    Klasifikasi Tipe Material di Gudang Pupuk Kalimantan Timur
                </h2>
                <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 font-medium">
                    Sistem Receiving Pupuk Kalimantan Timur mengelola 5 kategori material utama terstandarisasi SAP untuk kelancaran operasional pabrik.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-5">
                <!-- 1. ZSP: Sparepart -->
                <div class="glass-panel p-6 rounded-3xl hover:-translate-y-1.5 transition-all duration-300 group flex flex-col justify-between border border-white/80 dark:border-white/10 relative overflow-hidden shadow-sm hover:shadow-lg">

                    <div class="relative z-10">
                        <div class="w-12 h-12 rounded-2xl bg-orange-500/10 dark:bg-orange-400/20 text-[#F47920] flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <span class="text-[11px] font-extrabold text-[#F47920] uppercase tracking-widest">Tipe ZSP</span>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white mt-1 mb-2">Sparepart</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs leading-relaxed">
                            Suku cadang mesin, instrumen, dan komponen pabrik berpresisi tinggi untuk turn-around & pemeliharaan rutin.
                        </p>
                    </div>
                    <div class="relative z-10 mt-4 pt-3 border-t border-slate-200/60 dark:border-white/5 text-[11px] font-semibold text-slate-400">
                        Mesin & Instrumen
                    </div>
                </div>

                <!-- 2. ZRM: Raw Material -->
                <div class="glass-panel p-6 rounded-3xl hover:-translate-y-1.5 transition-all duration-300 group flex flex-col justify-between border border-white/80 dark:border-white/10 relative overflow-hidden shadow-sm hover:shadow-lg">

                    <div class="relative z-10">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 dark:bg-emerald-400/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                        </div>
                        <span class="text-[11px] font-extrabold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Tipe ZRM</span>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white mt-1 mb-2">Raw Material</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs leading-relaxed">
                            Bahan baku primer proses manufaktur kimia dan pupuk (Urea, Amoniak, dan NPK).
                        </p>
                    </div>
                    <div class="relative z-10 mt-4 pt-3 border-t border-slate-200/60 dark:border-white/5 text-[11px] font-semibold text-slate-400">
                        Bahan Baku Utama
                    </div>
                </div>

                <!-- 3. ZPM: Packaging Material -->
                <div class="glass-panel p-6 rounded-3xl hover:-translate-y-1.5 transition-all duration-300 group flex flex-col justify-between border border-white/80 dark:border-white/10 relative overflow-hidden shadow-sm hover:shadow-lg">

                    <div class="relative z-10">
                        <div class="w-12 h-12 rounded-2xl bg-purple-500/10 dark:bg-purple-400/20 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <span class="text-[11px] font-extrabold text-purple-600 dark:text-purple-400 uppercase tracking-widest">Tipe ZPM</span>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white mt-1 mb-2">Packaging Material</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs leading-relaxed">
                            Material kemasan kantong/karung pupuk 50kg, jumbo bag 1 ton, inner bag, dan benang jahit industri.
                        </p>
                    </div>
                    <div class="relative z-10 mt-4 pt-3 border-t border-slate-200/60 dark:border-white/5 text-[11px] font-semibold text-slate-400">
                        Karung & Kemasan
                    </div>
                </div>

                <!-- 4. ZSM: Support Material -->
                <div class="glass-panel p-6 rounded-3xl hover:-translate-y-1.5 transition-all duration-300 group flex flex-col justify-between border border-white/80 dark:border-white/10 relative overflow-hidden shadow-sm hover:shadow-lg">

                    <div class="relative z-10">
                        <div class="w-12 h-12 rounded-2xl bg-blue-500/10 dark:bg-blue-400/20 text-[#0A4F86] dark:text-blue-300 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                        <span class="text-[11px] font-extrabold text-[#0A4F86] dark:text-blue-400 uppercase tracking-widest">Tipe ZSM</span>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white mt-1 mb-2">Support Material</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs leading-relaxed">
                            Material penolong operasional, bahan kimia pembantu (water treatment, catalyst), dan sarana utilitas.
                        </p>
                    </div>
                    <div class="relative z-10 mt-4 pt-3 border-t border-slate-200/60 dark:border-white/5 text-[11px] font-semibold text-slate-400">
                        Utilitas & Pembantu
                    </div>
                </div>

                <!-- 5. ZFP: Finish Product -->
                <div class="glass-panel p-6 rounded-3xl hover:-translate-y-1.5 transition-all duration-300 group flex flex-col justify-between border border-white/80 dark:border-white/10 relative overflow-hidden shadow-sm hover:shadow-lg">

                    <div class="relative z-10">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 dark:bg-amber-400/20 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <span class="text-[11px] font-extrabold text-amber-600 dark:text-amber-400 uppercase tracking-widest">Tipe ZFP</span>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white mt-1 mb-2">Finish Product</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs leading-relaxed">
                            Produk akhir/pupuk siap jual (Urea Prill, Urea Granul, NPK) yang disalurkan ke gudang pengantongan.
                        </p>
                    </div>
                    <div class="relative z-10 mt-4 pt-3 border-t border-slate-200/60 dark:border-white/5 text-[11px] font-semibold text-slate-400">
                        Produk Jadi Pupuk
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. AI SPOTLIGHT & SIMULATION SHOWCASE -->
    <section class="relative z-10 py-24 px-5 md:px-12 lg:px-24">
        <div class="container mx-auto max-w-7xl">
            <div class="glass-panel p-8 sm:p-14 rounded-3xl border border-white/80 dark:border-white/10 shadow-2xl relative overflow-hidden bg-gradient-to-br from-white/70 via-white/40 to-[#F47920]/10 dark:from-slate-900/60 dark:via-[#031525]/80 dark:to-[#0A4F86]/20">


                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center relative z-10">
                    <!-- Left: Description & Action -->
                    <div class="lg:col-span-7">
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#F47920]/10 border border-[#F47920]/30 text-[#F47920] text-xs font-black tracking-widest uppercase mb-6">
                            <span class="w-2 h-2 rounded-full bg-[#F47920] animate-ping"></span>
                            Asisten Digital Cerdas
                        </div>

                        <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-800 dark:text-white tracking-tight mb-6">
                            Temui <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#F47920] to-[#BE5A27]">ALEX</span>, Asisten Logistik AI Anda
                        </h2>

                        <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed mb-8">
                            Tidak perlu login untuk mengecek apakah material Anda sudah sampai di gudang. Cukup tanyakan nomor PO atau DO Anda pada ALEX di pojok kanan bawah layar.
                        </p>

                        <div class="flex flex-col sm:flex-row items-center gap-4">
                            <button type="button" onclick="startAiTour()"
                                class="w-full sm:w-auto flex items-center justify-center gap-2 px-8 py-4 text-sm font-bold text-white bg-gradient-to-r from-[#F47920] to-[#BE5A27] rounded-2xl shadow-lg shadow-orange-500/25 hover:shadow-orange-500/40 hover:-translate-y-1 transition-all duration-300">
                                <span>Mulai Panduan Tur AI</span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                            <a href="{{ route('frontend.list-material') }}" wire:navigate
                                class="w-full sm:w-auto flex items-center justify-center px-8 py-4 text-sm font-bold text-slate-700 dark:text-slate-200 glass-btn rounded-2xl">
                                Cek Daftar Manual
                            </a>
                        </div>
                    </div>

                    <!-- Right: Simulated Chat Bubble -->
                    <div class="lg:col-span-5">
                        <div class="glass-panel p-5 sm:p-6 rounded-3xl border border-white/80 dark:border-white/10 shadow-2xl space-y-4">
                            <!-- Header Chat -->
                            <div class="flex items-center justify-between pb-3 border-b border-slate-200/70 dark:border-white/10">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#F47920] to-[#BE5A27] text-white flex items-center justify-center font-black text-xs shadow-md">
                                        AI
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-800 dark:text-white">ALEX Assistant</h4>
                                        <p class="text-[10px] text-emerald-500 font-bold flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Siap Menjawab
                                        </p>
                                    </div>
                                </div>
                                <span class="text-[11px] font-mono text-slate-400">v2.0</span>
                            </div>

                            <!-- Bubble 1 (User) -->
                            <div class="flex items-end justify-end gap-2">
                                <div class="bg-[#F47920] text-white text-xs sm:text-sm font-medium py-2.5 px-4 rounded-2xl rounded-tr-none max-w-[85%] shadow-md">
                                    Halo ALEX, apakah nomor PO <span class="font-bold underline">5300065922</span> sudah tiba di gudang?
                                </div>
                            </div>

                            <!-- Bubble 2 (ALEX) -->
                            <div class="flex items-start gap-2.5">
                                <div class="w-6 h-6 rounded-full bg-[#0A4F86] text-white flex items-center justify-center text-[10px] font-bold shrink-0 mt-1">
                                    A
                                </div>
                                <div class="bg-white/80 dark:bg-white/10 border border-slate-200/80 dark:border-white/10 text-slate-800 dark:text-slate-100 text-xs sm:text-sm py-3 px-4 rounded-2xl rounded-tl-none max-w-[90%] shadow-sm leading-relaxed">
                                    <p class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1 mb-1">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Material Ditemukan & Selesai GRS!
                                    </p>
                                    <p class="text-xs text-slate-600 dark:text-slate-300">
                                        Item: <strong>PEWARNA UREA; RED 3% ORG</strong> (Qty: 50.000). Dokumen GRS telah terbit dan barang berada di <strong>Depan Gudang Chemical</strong>. Anda dapat langsung mengajukan MIR.
                                    </p>
                                </div>
                            </div>

                            <!-- Interactive Prompt Suggestions -->
                            <div class="pt-2 flex flex-wrap gap-1.5">
                                <button type="button" onclick="startAiTour()" class="px-2.5 py-1 rounded-lg bg-[#F47920]/10 hover:bg-[#F47920]/20 text-[#F47920] text-[11px] font-bold transition-colors">
                                    Ajukan MIR Online
                                </button>
                                <button type="button" onclick="startAiTour()" class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-600 dark:text-slate-300 text-[11px] font-bold transition-colors">
                                    Lihat Lokasi Rak
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 7. FAST ACTION / CALL TO ACTION (CTA) HUB -->
    <section class="relative z-10 py-16 px-5 md:px-12 lg:px-24">
        <div class="container mx-auto max-w-7xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Left CTA Banner: Pengambilan Barang -->
                <div class="glass-panel p-8 sm:p-10 rounded-3xl border border-white/80 dark:border-white/10 shadow-xl relative overflow-hidden group">
                    <div class="absolute -top-10 -right-10 w-44 h-44 bg-[#F47920]/20 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-500"></div>



                    <div class="relative z-10">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#F47920]">Layanan Mandiri</span>
                        <h3 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white mt-1 mb-3">
                            Siap Mengambil Material?
                        </h3>
                        <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed mb-6">
                            Isi formulir Material Issue Request (MIR) secara digital tanpa perlu membawa berkas fisik ke gudang. Cepat, transparan, dan terlacak.
                        </p>
                        <a href="{{ route('frontend.mir.create') }}" wire:navigate
                            class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-[#F47920] to-[#BE5A27] shadow-lg shadow-orange-500/25 hover:shadow-orange-500/40 hover:-translate-y-0.5 transition-all">
                            <span>Formulir Pengambilan MIR</span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Right CTA Banner: Katalog PD & Nonstock -->
                <div class="glass-panel p-8 sm:p-10 rounded-3xl border border-white/80 dark:border-white/10 shadow-xl relative overflow-hidden group">
                    <div class="absolute -top-10 -right-10 w-44 h-44 bg-[#0A4F86]/20 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-500"></div>



                    <div class="relative z-10">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#0A4F86] dark:text-blue-400">Katalog Gudang</span>
                        <h3 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white mt-1 mb-3">
                            Cari Barang PD & Non-Stock
                        </h3>
                        <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed mb-6">
                            Periksa ketersediaan barang Pembelian Langsung (PD) dan Non-Stock yang sudah berstatus tiba di Gudang Receiving Pupuk Kalimantan Timur.
                        </p>
                        <a href="{{ route('frontend.list-material') }}" wire:navigate
                            class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl font-bold text-sm text-slate-800 dark:text-white glass-btn hover:-translate-y-0.5 transition-all">
                            <span>Buka Daftar Material</span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

</div>
