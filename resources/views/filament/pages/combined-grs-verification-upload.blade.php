@php
    $config = $this->getPageConfig();
@endphp

<x-filament-panels::page>
    <style>
        .combined-dropzone {
            border-color: #cbd5e1;
            background-color: #f8fafc;
        }
        .combined-dropzone:hover {
            border-color: #94a3b8;
            background-color: #f1f5f9;
        }
        html.dark .combined-dropzone,
        .dark .combined-dropzone {
            border-color: rgba(255, 255, 255, 0.14);
            background-color: rgba(255, 255, 255, 0.025);
        }
        html.dark .combined-dropzone:hover,
        .dark .combined-dropzone:hover {
            border-color: rgba(255, 255, 255, 0.25);
            background-color: rgba(255, 255, 255, 0.05);
        }
        .combined-dropzone.is-dragging {
            border-color: #0ea5e9 !important;
            background-color: rgba(14, 165, 233, 0.08) !important;
        }
        html.dark .combined-dropzone.is-dragging,
        .dark .combined-dropzone.is-dragging {
            border-color: #38bdf8 !important;
            background-color: rgba(56, 189, 248, 0.14) !important;
        }
    </style>

    <div
        class="space-y-5"
        x-data="combinedGrsVerificationUpload(@js($config))"
        x-init="init()"
    >
        {{-- Baris Pengaturan Sesi --}}
        <div class="rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900/70 p-4 sm:p-5 shadow-xs">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                <div class="md:col-span-4 space-y-1.5">
                    <label for="combined-tx-date" class="block text-xs font-semibold text-gray-700 dark:text-gray-200">
                        Tanggal Transaksi GRS / RDTV
                    </label>
                    <input
                        id="combined-tx-date"
                        type="date"
                        x-model="transactionDate"
                        @change="schedulePrecheck()"
                        :disabled="isCommitting"
                        class="w-full rounded-xl border border-gray-300 dark:border-white/15 bg-white dark:bg-gray-950 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                    />
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        Digunakan sebagai tanggal batch GRS / RDTV dan referensi default pembuatan Invoice Barang.
                    </p>
                </div>

                <div class="md:col-span-5 space-y-1.5">
                    <span class="block text-xs font-semibold text-gray-700 dark:text-gray-200">
                        Pengiriman Email Otomatis ke Vendor
                    </span>
                    <label class="flex items-start gap-3 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50/70 dark:bg-white/[0.03] px-3.5 py-2.5 cursor-pointer">
                        <input
                            type="checkbox"
                            x-model="sendEmail"
                            :disabled="isCommitting"
                            class="mt-0.5 h-4 w-4 rounded border-gray-300 dark:border-white/20 text-primary-600 focus:ring-primary-500"
                        />
                        <div class="min-w-0 text-xs">
                            <span class="font-semibold text-gray-800 dark:text-gray-100 block">
                                Kirim email ke vendor untuk dokumen Terverifikasi (2 lampiran lengkap)
                            </span>
                            <span class="text-[11px] text-gray-500 dark:text-gray-400 block mt-0.5">
                                Baris "Slip Saja" tidak mengirim email hingga Surat Verifikasi diunggah di kemudian hari.
                            </span>
                        </div>
                    </label>
                </div>

                <div class="md:col-span-3 space-y-2">
                    <span class="block text-xs font-semibold text-gray-700 dark:text-gray-200">
                        Status Konfigurasi Email
                    </span>
                    <template x-if="config.emailTestModeEnabled">
                        <div class="rounded-xl border border-amber-300 dark:border-amber-700/70 bg-amber-50 dark:bg-amber-950/50 px-3 py-2 text-[11px] text-amber-800 dark:text-amber-200">
                            <div class="font-bold">Mode Uji Coba Aktif</div>
                            <div class="mt-0.5 break-all">
                                Email dialihkan ke: <span class="font-mono font-semibold" x-text="config.emailTestModeEmail || '(belum diatur)'"></span>
                            </div>
                        </div>
                    </template>
                    <template x-if="!config.emailTestModeEnabled">
                        <div class="rounded-xl border border-emerald-200 dark:border-emerald-800/70 bg-emerald-50/70 dark:bg-emerald-950/30 px-3 py-2 text-[11px] text-emerald-800 dark:text-emerald-200">
                            <div class="font-bold" x-text="config.emailAutoSendEnabled ? 'Mode Produksi (Aktif)' : 'Auto-Kirim Global Nonaktif'"></div>
                            <div class="mt-0.5" x-text="config.emailAutoSendEnabled ? 'Email terkirim ke alamat vendor terdaftar.' : 'Diatur nonaktif pada halaman Template Email GRS/RDTV.'"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Area Dropzone Gabungan --}}
        <div
            class="combined-dropzone relative rounded-2xl border-2 border-dashed p-6 sm:p-8 text-center transition-all duration-150 cursor-pointer"
            :class="isDragging ? 'is-dragging' : ''"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop($event)"
            @click="if (!isCommitting) $refs.fileInput.click()"
        >
            <input
                type="file"
                x-ref="fileInput"
                @change="handleFileSelect($event)"
                multiple
                accept="application/pdf"
                class="hidden"
            />

            <div class="flex flex-col items-center justify-center space-y-2.5 pointer-events-none">
                <div class="p-3.5 rounded-2xl bg-primary-50 dark:bg-primary-950/60 text-primary-600 dark:text-primary-400 border border-primary-100 dark:border-primary-900/50">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <p class="text-sm sm:text-base font-semibold text-gray-900 dark:text-white">
                        Pilih atau seret seluruh berkas PDF (Slip GRS/RDTV dan Surat Verifikasi) ke sini
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 max-w-2xl mx-auto">
                        Anda dapat mencampur berkas Slip GRS (105/101), Verifikasi GRS, Slip RDTV (124), maupun Verifikasi RDTV sekaligus dalam satu unggahan (maks. 25 MB per berkas). Berkas tanpa pasangan tetap dapat diproses.
                    </p>
                </div>
            </div>
        </div>

        {{-- Progress Bar Pindai / Precheck / Simpan --}}
        <div
            x-show="isScanning || isPrechecking || isCommitting"
            x-cloak
            class="rounded-2xl border border-primary-200 dark:border-primary-800/60 bg-primary-50/70 dark:bg-primary-950/40 p-4 space-y-2"
        >
            <div class="flex items-center justify-between gap-2 text-xs text-primary-900 dark:text-primary-200">
                <div class="flex items-center gap-2 min-w-0">
                    <svg class="w-4 h-4 shrink-0 animate-spin text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span class="font-medium truncate" x-text="activityMessage"></span>
                </div>
                <span class="font-mono font-bold shrink-0" x-text="progressPercent + '%'"></span>
            </div>
            <div class="w-full h-2 rounded-full bg-primary-200/70 dark:bg-primary-900/60 overflow-hidden">
                <div
                    class="h-2 rounded-full bg-primary-600 dark:bg-primary-400 transition-all duration-200"
                    :style="'width: ' + progressPercent + '%'"
                ></div>
            </div>
        </div>

        {{-- Ringkasan & Tabel Pemetaan --}}
        <template x-if="files.length > 0">
            <div class="space-y-4">
                {{-- Kartu Ringkasan --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900/70 p-3">
                        <div class="text-[11px] font-medium text-gray-500 dark:text-gray-400">Total Berkas</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-white mt-0.5" x-text="files.length"></div>
                    </div>
                    <div class="rounded-xl border border-emerald-200 dark:border-emerald-900/60 bg-emerald-50/70 dark:bg-emerald-950/30 p-3">
                        <div class="text-[11px] font-medium text-emerald-700 dark:text-emerald-400">Pasangan Lengkap</div>
                        <div class="text-xl font-bold text-emerald-800 dark:text-emerald-300 mt-0.5" x-text="countByMode('pair')"></div>
                    </div>
                    <div class="rounded-xl border border-sky-200 dark:border-sky-900/60 bg-sky-50/70 dark:bg-sky-950/30 p-3">
                        <div class="text-[11px] font-medium text-sky-700 dark:text-sky-400">Slip Saja</div>
                        <div class="text-xl font-bold text-sky-800 dark:text-sky-300 mt-0.5" x-text="countByMode('slip_only')"></div>
                    </div>
                    <div class="rounded-xl border border-indigo-200 dark:border-indigo-900/60 bg-indigo-50/70 dark:bg-indigo-950/30 p-3">
                        <div class="text-[11px] font-medium text-indigo-700 dark:text-indigo-400">Verifikasi Saja</div>
                        <div class="text-xl font-bold text-indigo-800 dark:text-indigo-300 mt-0.5" x-text="countByMode('verif_only')"></div>
                    </div>
                    <div class="rounded-xl border border-amber-200 dark:border-amber-900/60 bg-amber-50/70 dark:bg-amber-950/30 p-3">
                        <div class="text-[11px] font-medium text-amber-700 dark:text-amber-400">Perlu Perhatian</div>
                        <div class="text-xl font-bold text-amber-800 dark:text-amber-300 mt-0.5" x-text="attentionCount"></div>
                    </div>
                    <div class="rounded-xl border border-teal-200 dark:border-teal-900/60 bg-teal-50/70 dark:bg-teal-950/30 p-3">
                        <div class="text-[11px] font-medium text-teal-700 dark:text-teal-400">Berhasil Disimpan</div>
                        <div class="text-xl font-bold text-teal-800 dark:text-teal-300 mt-0.5" x-text="successCount"></div>
                    </div>
                </div>

                {{-- Filter & Toolbar --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <template x-for="tab in filterTabs" :key="tab.key">
                            <button
                                type="button"
                                @click="activeFilter = tab.key"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold border transition"
                                :class="activeFilter === tab.key
                                    ? 'bg-primary-600 text-white border-primary-600'
                                    : 'bg-white dark:bg-gray-900/70 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-white/10 hover:bg-gray-50 dark:hover:bg-white/5'"
                            >
                                <span x-text="tab.label"></span>
                                <span
                                    class="px-1.5 py-0.5 rounded-md text-[10px] font-mono"
                                    :class="activeFilter === tab.key ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-gray-300'"
                                    x-text="getFilterCount(tab.key)"
                                ></span>
                            </button>
                        </template>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            @click="runPrecheck()"
                            :disabled="isScanning || isPrechecking || isCommitting || rows.length === 0"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold border border-gray-300 dark:border-white/15 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 disabled:opacity-50 transition"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Validasi Ulang</span>
                        </button>

                        <button
                            type="button"
                            @click="clearAll()"
                            :disabled="isCommitting"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold border border-rose-200 dark:border-rose-800/60 bg-rose-50/70 dark:bg-rose-950/30 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-950/60 disabled:opacity-50 transition"
                        >
                            <span>Kosongkan Daftar</span>
                        </button>
                    </div>
                </div>

                {{-- Berkas Belum Terklasifikasi (jika ada) --}}
                <template x-if="unknownFiles.length > 0">
                    <div class="rounded-2xl border border-amber-300 dark:border-amber-800 bg-amber-50/80 dark:bg-amber-950/40 p-4 space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wide text-amber-800 dark:text-amber-300">
                                    Berkas Perlu Penentuan Peran (<span x-text="unknownFiles.length"></span>)
                                </h3>
                                <p class="text-xs text-amber-700 dark:text-amber-200/80 mt-0.5">
                                    Sistem belum dapat memastikan apakah berkas berikut merupakan Slip GRS/RDTV atau Surat Verifikasi. Pilih peran berkas agar masuk ke tabel pemetaan.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <template x-for="f in unknownFiles" :key="f.id">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 rounded-xl border border-amber-200 dark:border-amber-800/60 bg-white dark:bg-gray-900/80 p-3 text-xs">
                                    <div class="min-w-0">
                                        <a
                                            :href="f.objectUrl"
                                            target="_blank"
                                            rel="noopener"
                                            class="font-semibold text-primary-600 dark:text-primary-400 hover:underline truncate block"
                                            x-text="f.name"
                                        ></a>
                                        <span class="text-[11px] text-gray-500 dark:text-gray-400" x-text="formatBytes(f.size) + (f.scan_note ? ' • ' + f.scan_note : '')"></span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                                        <button type="button" @click="setFileRole(f.id, 'grs_slip')" class="px-2.5 py-1 rounded-lg bg-emerald-600 text-white font-semibold hover:bg-emerald-700">Slip GRS</button>
                                        <button type="button" @click="setFileRole(f.id, 'verifikasi_grs')" class="px-2.5 py-1 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700">Verifikasi GRS</button>
                                        <button type="button" @click="setFileRole(f.id, 'rdtv_slip')" class="px-2.5 py-1 rounded-lg bg-rose-600 text-white font-semibold hover:bg-rose-700">Slip RDTV</button>
                                        <button type="button" @click="setFileRole(f.id, 'verifikasi_rdtv')" class="px-2.5 py-1 rounded-lg bg-amber-600 text-white font-semibold hover:bg-amber-700">Verifikasi RDTV</button>
                                        <button type="button" @click="removeFile(f.id)" class="px-2 py-1 rounded-lg border border-gray-300 dark:border-white/15 text-gray-600 dark:text-gray-300 hover:text-rose-600">Hapus</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Tampilan Tabel Desktop (lg+) --}}
                <div class="hidden lg:block rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900/70 overflow-hidden shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-white/10 bg-gray-50/90 dark:bg-white/[0.03] text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    <th class="py-3 pl-4 pr-2 w-10">
                                        <input
                                            type="checkbox"
                                            :checked="allSelectableChecked"
                                            @change="toggleSelectAll($event.target.checked)"
                                            :disabled="isCommitting"
                                            class="h-4 w-4 rounded border-gray-300 dark:border-white/20 text-primary-600 focus:ring-primary-500"
                                            title="Pilih semua baris yang siap diproses"
                                        />
                                    </th>
                                    <th class="py-3 px-2.5 w-36">Mode & Jenis</th>
                                    <th class="py-3 px-2.5 min-w-[210px]">Berkas Slip GRS/RDTV</th>
                                    <th class="py-3 px-2.5 min-w-[230px]">Pasangan Surat Verifikasi</th>
                                    <th class="py-3 px-2.5 min-w-[215px]">Nomor Dokumen & PO</th>
                                    <th class="py-3 px-2.5 min-w-[230px]">Status Validasi / Hasil</th>
                                    <th class="py-3 pl-2 pr-4 w-16 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                                <template x-if="filteredRows.length === 0">
                                    <tr>
                                        <td colspan="7" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                            Tidak ada baris pada filter ini.
                                        </td>
                                    </tr>
                                </template>
                                <template x-for="row in filteredRows" :key="row.row_id">
                                    <tr
                                        class="align-top transition"
                                        :class="{
                                            'bg-emerald-50/40 dark:bg-emerald-950/15': row.commit_status === 'success',
                                            'bg-rose-50/40 dark:bg-rose-950/15': row.commit_status === 'error' || row.precheck_status === 'blocked',
                                            'hover:bg-gray-50/70 dark:hover:bg-white/[0.025]': row.commit_status === 'idle' && row.precheck_status !== 'blocked'
                                        }"
                                    >
                                        {{-- Checkbox --}}
                                        <td class="py-3.5 pl-4 pr-2">
                                            <input
                                                type="checkbox"
                                                :checked="row.selected"
                                                @change="toggleRowSelection(row.row_id, $event.target.checked)"
                                                :disabled="isCommitting || row.precheck_status === 'blocked' || row.commit_status === 'success'"
                                                class="mt-0.5 h-4 w-4 rounded border-gray-300 dark:border-white/20 text-primary-600 focus:ring-primary-500 disabled:opacity-40"
                                            />
                                        </td>

                                        {{-- Mode & Jenis --}}
                                        <td class="py-3.5 px-2.5 space-y-1.5">
                                            <div>
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                    :class="{
                                                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300': row.mode === 'pair',
                                                        'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300': row.mode === 'slip_only',
                                                        'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300': row.mode === 'verif_only',
                                                    }"
                                                    x-text="getModeLabel(row.mode)"
                                                ></span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <button
                                                    type="button"
                                                    @click="toggleRowJenis(row)"
                                                    :disabled="isCommitting || row.commit_status === 'success'"
                                                    title="Klik untuk mengganti jenis GRS / RDTV"
                                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border transition cursor-pointer disabled:cursor-default"
                                                    :class="row.jenis === 'RDTV'
                                                        ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800'
                                                        : 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-950/60 dark:text-teal-300 dark:border-teal-800'"
                                                    x-text="row.jenis === 'RDTV' ? 'RDTV (124)' : 'GRS (105/101)'"
                                                ></button>
                                            </div>
                                            <div class="text-[10px] text-gray-500 dark:text-gray-400 leading-snug" x-text="row.pair_reason"></div>
                                        </td>

                                        {{-- Slip GRS/RDTV --}}
                                        <td class="py-3.5 px-2.5 space-y-1.5">
                                            <template x-if="row.slipFile">
                                                <div class="space-y-1">
                                                    <a
                                                        :href="row.slipFile.objectUrl"
                                                        target="_blank"
                                                        rel="noopener"
                                                        class="font-semibold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 hover:underline break-all block"
                                                        x-text="row.slipFile.name"
                                                    ></a>
                                                    <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-gray-500 dark:text-gray-400">
                                                        <span class="font-mono bg-gray-100 dark:bg-white/10 px-1.5 py-0.5 rounded" x-text="row.document_code || '-'"></span>
                                                        <span x-text="formatBytes(row.slipFile.size)"></span>
                                                    </div>
                                                    <div class="flex items-center gap-2 pt-0.5">
                                                        <button
                                                            type="button"
                                                            x-show="row.commit_status !== 'success'"
                                                            @click="swapSingleFileRole(row.slipFile.id)"
                                                            :disabled="isCommitting"
                                                            class="text-[10px] font-medium text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400 underline"
                                                        >
                                                            Ubah ke Surat Verifikasi
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>
                                            <template x-if="!row.slipFile">
                                                <div class="text-[11px] text-gray-400 dark:text-gray-500 italic">
                                                    Tidak ada Slip di unggahan ini (mencocokkan ke Invoice Barang eksisting).
                                                </div>
                                            </template>
                                        </td>

                                        {{-- Pasangan Surat Verifikasi --}}
                                        <td class="py-3.5 px-2.5 space-y-1.5">
                                            <template x-if="row.slipFile">
                                                <div class="space-y-1.5">
                                                    <select
                                                        :value="row.verifFile ? row.verifFile.id : ''"
                                                        @change="setManualPair(row.slipFile.id, $event.target.value)"
                                                        :disabled="isCommitting || row.commit_status === 'success'"
                                                        class="w-full rounded-lg border border-gray-300 dark:border-white/15 bg-white dark:bg-gray-950 px-2.5 py-1.5 text-xs text-gray-900 dark:text-white focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:opacity-60"
                                                    >
                                                        <option value="">— Tanpa Verifikasi (Slip Saja) —</option>
                                                        <template x-for="vf in getSelectableVerifsForSlip(row.slipFile.id)" :key="vf.id">
                                                            <option
                                                                :value="vf.id"
                                                                :selected="row.verifFile && row.verifFile.id === vf.id"
                                                                x-text="formatVerifOption(vf)"
                                                            ></option>
                                                        </template>
                                                    </select>

                                                    <template x-if="row.verifFile">
                                                        <div class="flex items-center justify-between gap-2 text-[11px]">
                                                            <a
                                                                :href="row.verifFile.objectUrl"
                                                                target="_blank"
                                                                rel="noopener"
                                                                class="text-primary-600 dark:text-primary-400 hover:underline truncate"
                                                                x-text="'Pratinjau: ' + row.verifFile.name"
                                                            ></a>
                                                            <span class="text-gray-400 shrink-0" x-text="formatBytes(row.verifFile.size)"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>

                                            <template x-if="!row.slipFile && row.verifFile">
                                                <div class="space-y-1">
                                                    <a
                                                        :href="row.verifFile.objectUrl"
                                                        target="_blank"
                                                        rel="noopener"
                                                        class="font-semibold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 hover:underline break-all block"
                                                        x-text="row.verifFile.name"
                                                    ></a>
                                                    <div class="text-[11px] text-gray-500 dark:text-gray-400" x-text="formatBytes(row.verifFile.size)"></div>
                                                    <button
                                                        type="button"
                                                        x-show="row.commit_status !== 'success'"
                                                        @click="swapSingleFileRole(row.verifFile.id)"
                                                        :disabled="isCommitting"
                                                        class="text-[10px] font-medium text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400 underline"
                                                    >
                                                        Ubah ke Slip GRS/RDTV
                                                    </button>
                                                </div>
                                            </template>
                                        </td>

                                        {{-- Nomor Dokumen, PO, Vendor & Alasan RDTV --}}
                                        <td class="py-3.5 px-2.5 space-y-2">
                                            <div class="grid grid-cols-1 gap-1.5">
                                                <div class="flex items-center gap-1.5 rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/[0.03] px-2 py-1">
                                                    <span
                                                        class="text-[10px] font-bold uppercase w-10 shrink-0"
                                                        :class="row.jenis === 'RDTV' ? 'text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-gray-400'"
                                                        x-text="row.jenis === 'RDTV' ? 'RDTV' : 'GRS'"
                                                    ></span>
                                                    <input
                                                        type="text"
                                                        :value="row.jenis === 'RDTV' ? row.no_rdtv : row.no_grs"
                                                        @input="updateRowDocNumber(row, $event.target.value)"
                                                        :disabled="isCommitting || row.commit_status === 'success'"
                                                        placeholder="500xxxxxxx"
                                                        class="w-full bg-transparent border-0 p-0 text-xs font-mono font-semibold text-gray-900 dark:text-white focus:ring-0 placeholder-gray-400"
                                                    />
                                                </div>

                                                <div class="flex items-center gap-1.5 rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/[0.03] px-2 py-1">
                                                    <span class="text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400 w-10 shrink-0">PO</span>
                                                    <input
                                                        type="text"
                                                        :value="row.no_po"
                                                        @input="updateRowPoNumber(row, $event.target.value)"
                                                        :disabled="isCommitting || row.commit_status === 'success'"
                                                        placeholder="530xxxxxxx"
                                                        class="w-full bg-transparent border-0 p-0 text-xs font-mono text-gray-800 dark:text-gray-200 focus:ring-0 placeholder-gray-400"
                                                    />
                                                </div>
                                            </div>

                                            <div class="text-[11px] text-gray-500 dark:text-gray-400 truncate" x-show="row.vendor_name && row.vendor_name !== '-'">
                                                Vendor: <span class="font-medium text-gray-700 dark:text-gray-200" x-text="row.vendor_name"></span>
                                            </div>

                                            <template x-if="row.will_auto_create_do && row.mode !== 'verif_only'">
                                                <div class="flex items-center gap-1.5 rounded-lg border border-sky-200 dark:border-sky-800/60 bg-sky-50/50 dark:bg-sky-950/25 px-2 py-1">
                                                    <span class="text-[10px] font-bold uppercase text-sky-700 dark:text-sky-400 shrink-0">Lokasi</span>
                                                    <input
                                                        type="text"
                                                        :value="row.lokasi_penerimaan"
                                                        @input="updateRowLokasi(row, $event.target.value)"
                                                        :disabled="isCommitting || row.commit_status === 'success'"
                                                        placeholder="Gudang Banyuwangi..."
                                                        class="w-full bg-transparent border-0 p-0 text-[11px] font-medium text-gray-900 dark:text-white focus:ring-0 placeholder-sky-400"
                                                    />
                                                </div>
                                            </template>

                                            <template x-if="row.jenis === 'RDTV' && row.mode !== 'verif_only'">
                                                <input
                                                    type="text"
                                                    :value="row.reason"
                                                    @input="updateRowReason(row, $event.target.value)"
                                                    :disabled="isCommitting || row.commit_status === 'success'"
                                                    placeholder="Alasan penolakan RDTV (opsional)..."
                                                    class="w-full rounded-lg border border-rose-200 dark:border-rose-800/60 bg-rose-50/40 dark:bg-rose-950/20 px-2.5 py-1 text-[11px] text-gray-900 dark:text-white placeholder-rose-400 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                                />
                                            </template>
                                        </td>

                                        {{-- Status Validasi / Hasil Commit --}}
                                        <td class="py-3.5 px-2.5 space-y-1.5">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                    :class="getRowBadgeClass(row)"
                                                    x-text="getRowBadgeLabel(row)"
                                                ></span>

                                                <template x-if="row.email_status && row.email_status !== 'disabled'">
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                                                        :class="getEmailBadgeClass(row.email_status)"
                                                        x-text="getEmailBadgeLabel(row.email_status)"
                                                    ></span>
                                                </template>
                                            </div>

                                            <p
                                                class="text-[11px] leading-relaxed"
                                                :class="{
                                                    'text-emerald-700 dark:text-emerald-300': row.commit_status === 'success',
                                                    'text-rose-700 dark:text-rose-300 font-medium': row.commit_status === 'error' || row.precheck_status === 'blocked',
                                                    'text-amber-700 dark:text-amber-300': row.commit_status === 'idle' && row.precheck_status === 'warning',
                                                    'text-gray-600 dark:text-gray-400': row.commit_status === 'idle' && (row.precheck_status === 'ready' || row.precheck_status === 'pending'),
                                                }"
                                                x-text="row.commit_status !== 'idle' ? row.commit_message : row.precheck_message"
                                            ></p>
                                        </td>

                                        {{-- Aksi Hapus Baris --}}
                                        <td class="py-3.5 pl-2 pr-4 text-right">
                                            <button
                                                type="button"
                                                x-show="row.commit_status !== 'committing'"
                                                @click="removeRow(row)"
                                                class="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                                title="Hapus baris dari antrean"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Tampilan Kartu Mobile (< lg) --}}
                <div class="lg:hidden space-y-3">
                    <template x-if="filteredRows.length === 0">
                        <div class="rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900/70 p-6 text-center text-xs text-gray-500 dark:text-gray-400">
                            Tidak ada baris pada filter ini.
                        </div>
                    </template>

                    <template x-for="row in filteredRows" :key="row.row_id">
                        <div
                            class="rounded-2xl border bg-white dark:bg-gray-900/80 p-4 space-y-3 text-xs shadow-xs"
                            :class="{
                                'border-emerald-300 dark:border-emerald-800': row.commit_status === 'success',
                                'border-rose-300 dark:border-rose-800': row.commit_status === 'error' || row.precheck_status === 'blocked',
                                'border-gray-200 dark:border-white/10': row.commit_status === 'idle' && row.precheck_status !== 'blocked'
                            }"
                        >
                            {{-- Header Kartu --}}
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <input
                                        type="checkbox"
                                        :checked="row.selected"
                                        @change="toggleRowSelection(row.row_id, $event.target.checked)"
                                        :disabled="isCommitting || row.precheck_status === 'blocked' || row.commit_status === 'success'"
                                        class="h-4 w-4 rounded border-gray-300 dark:border-white/20 text-primary-600 focus:ring-primary-500 disabled:opacity-40"
                                    />
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="{
                                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300': row.mode === 'pair',
                                            'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300': row.mode === 'slip_only',
                                            'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300': row.mode === 'verif_only',
                                        }"
                                        x-text="getModeLabel(row.mode)"
                                    ></span>
                                    <button
                                        type="button"
                                        @click="toggleRowJenis(row)"
                                        :disabled="isCommitting || row.commit_status === 'success'"
                                        class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                        :class="row.jenis === 'RDTV'
                                            ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800'
                                            : 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-950/60 dark:text-teal-300 dark:border-teal-800'"
                                        x-text="row.jenis === 'RDTV' ? 'RDTV (124)' : 'GRS (105/101)'"
                                    ></button>
                                </div>

                                <button
                                    type="button"
                                    x-show="row.commit_status !== 'committing'"
                                    @click="removeRow(row)"
                                    class="p-1 text-gray-400 hover:text-rose-600"
                                    title="Hapus baris"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            {{-- File Slip & Pasangan --}}
                            <div class="space-y-2 rounded-xl bg-gray-50/80 dark:bg-white/[0.03] p-3 border border-gray-100 dark:border-white/5">
                                <template x-if="row.slipFile">
                                    <div class="space-y-1">
                                        <div class="text-[10px] font-semibold uppercase text-gray-400">Slip GRS/RDTV</div>
                                        <a :href="row.slipFile.objectUrl" target="_blank" rel="noopener" class="font-semibold text-primary-600 dark:text-primary-400 hover:underline break-all block" x-text="row.slipFile.name"></a>
                                    </div>
                                </template>

                                <template x-if="row.slipFile">
                                    <div class="space-y-1 pt-1">
                                        <div class="text-[10px] font-semibold uppercase text-gray-400">Pasangan Surat Verifikasi</div>
                                        <select
                                            :value="row.verifFile ? row.verifFile.id : ''"
                                            @change="setManualPair(row.slipFile.id, $event.target.value)"
                                            :disabled="isCommitting || row.commit_status === 'success'"
                                            class="w-full rounded-lg border border-gray-300 dark:border-white/15 bg-white dark:bg-gray-950 px-2.5 py-1.5 text-xs text-gray-900 dark:text-white"
                                        >
                                            <option value="">— Tanpa Verifikasi (Slip Saja) —</option>
                                            <template x-for="vf in getSelectableVerifsForSlip(row.slipFile.id)" :key="vf.id">
                                                <option
                                                    :value="vf.id"
                                                    :selected="row.verifFile && row.verifFile.id === vf.id"
                                                    x-text="formatVerifOption(vf)"
                                                ></option>
                                            </template>
                                        </select>
                                    </div>
                                </template>

                                <template x-if="!row.slipFile && row.verifFile">
                                    <div class="space-y-1">
                                        <div class="text-[10px] font-semibold uppercase text-gray-400">Surat Verifikasi</div>
                                        <a :href="row.verifFile.objectUrl" target="_blank" rel="noopener" class="font-semibold text-primary-600 dark:text-primary-400 hover:underline break-all block" x-text="row.verifFile.name"></a>
                                    </div>
                                </template>
                            </div>

                            {{-- Input Nomor --}}
                            <div class="grid grid-cols-2 gap-2">
                                <div class="rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/[0.03] px-2.5 py-1.5">
                                    <div class="text-[10px] font-bold uppercase text-gray-500" x-text="row.jenis === 'RDTV' ? 'No. RDTV' : 'No. GRS'"></div>
                                    <input
                                        type="text"
                                        :value="row.jenis === 'RDTV' ? row.no_rdtv : row.no_grs"
                                        @input="updateRowDocNumber(row, $event.target.value)"
                                        :disabled="isCommitting || row.commit_status === 'success'"
                                        placeholder="500xxxxxxx"
                                        class="w-full bg-transparent border-0 p-0 text-xs font-mono font-semibold text-gray-900 dark:text-white focus:ring-0"
                                    />
                                </div>
                                <div class="rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/[0.03] px-2.5 py-1.5">
                                    <div class="text-[10px] font-bold uppercase text-gray-500">No. PO</div>
                                    <input
                                        type="text"
                                        :value="row.no_po"
                                        @input="updateRowPoNumber(row, $event.target.value)"
                                        :disabled="isCommitting || row.commit_status === 'success'"
                                        placeholder="530xxxxxxx"
                                        class="w-full bg-transparent border-0 p-0 text-xs font-mono text-gray-900 dark:text-white focus:ring-0"
                                    />
                                </div>
                            </div>

                            <template x-if="row.will_auto_create_do && row.mode !== 'verif_only'">
                                <div class="rounded-lg border border-sky-200 dark:border-sky-800/60 bg-sky-50/40 dark:bg-sky-950/20 px-2.5 py-1.5">
                                    <div class="text-[10px] font-bold uppercase text-sky-700 dark:text-sky-400">Lokasi Penerimaan (Auto DO)</div>
                                    <input
                                        type="text"
                                        :value="row.lokasi_penerimaan"
                                        @input="updateRowLokasi(row, $event.target.value)"
                                        :disabled="isCommitting || row.commit_status === 'success'"
                                        placeholder="Gudang Banyuwangi..."
                                        class="w-full bg-transparent border-0 p-0 text-xs text-gray-900 dark:text-white focus:ring-0"
                                    />
                                </div>
                            </template>

                            <template x-if="row.jenis === 'RDTV' && row.mode !== 'verif_only'">
                                <input
                                    type="text"
                                    :value="row.reason"
                                    @input="updateRowReason(row, $event.target.value)"
                                    :disabled="isCommitting || row.commit_status === 'success'"
                                    placeholder="Alasan penolakan RDTV (opsional)..."
                                    class="w-full rounded-lg border border-rose-200 dark:border-rose-800/60 bg-rose-50/40 dark:bg-rose-950/20 px-3 py-1.5 text-xs text-gray-900 dark:text-white"
                                />
                            </template>

                            {{-- Status --}}
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold" :class="getRowBadgeClass(row)" x-text="getRowBadgeLabel(row)"></span>
                                <template x-if="row.email_status && row.email_status !== 'disabled'">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border" :class="getEmailBadgeClass(row.email_status)" x-text="getEmailBadgeLabel(row.email_status)"></span>
                                </template>
                            </div>
                            <p class="text-[11px] text-gray-600 dark:text-gray-300 leading-relaxed" x-text="row.commit_status !== 'idle' ? row.commit_message : row.precheck_message"></p>
                        </div>
                    </template>
                </div>

                {{-- Footer Action Bar --}}
                <div class="rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900/80 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                    <div class="text-xs text-gray-600 dark:text-gray-300 space-y-0.5">
                        <div>
                            <span class="font-bold text-gray-900 dark:text-white" x-text="readySelectedCount"></span> baris siap diproses
                            <span x-show="successCount > 0">
                                • <span class="font-bold text-emerald-600 dark:text-emerald-400" x-text="successCount"></span> berhasil disimpan
                            </span>
                            <span x-show="errorCount > 0">
                                • <span class="font-bold text-rose-600 dark:text-rose-400" x-text="errorCount"></span> gagal
                            </span>
                        </div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400">
                            Setiap baris disimpan secara atomik. Baris yang gagal dapat diperbaiki dan dicoba ulang tanpa mengulang baris yang sudah berhasil.
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            x-show="errorCount > 0 && !isCommitting"
                            @click="retryFailed()"
                            class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold border border-amber-300 dark:border-amber-700 bg-amber-100 dark:bg-amber-950/70 text-amber-900 dark:text-amber-200 hover:bg-amber-200 dark:hover:bg-amber-900 transition"
                        >
                            <span>Coba Ulang Gagal (<span x-text="errorCount"></span>)</span>
                        </button>

                        <button
                            type="button"
                            @click="commitSelected()"
                            :disabled="isScanning || isPrechecking || isCommitting || readySelectedCount === 0"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-xs transition"
                        >
                            <svg x-show="isCommitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <span x-text="isCommitting ? 'Menyimpan Dokumen...' : ('Proses & Simpan (' + readySelectedCount + ' Baris)')"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <script>
        function combinedGrsVerificationUpload(config) {
            return {
                config,
                transactionDate: config.defaultDate,
                sendEmail: Boolean(config.emailAutoSendEnabled),
                isDragging: false,
                isScanning: false,
                isPrechecking: false,
                isCommitting: false,
                progressPercent: 0,
                activityMessage: '',
                activeFilter: 'all',
                files: [],
                rows: [],
                manualPairs: {},
                manualUnpairedSlips: {},
                rowStates: {},
                fileSeq: 1,
                precheckTimer: null,
                tesseractWorker: null,

                filterTabs: [
                    { key: 'all', label: 'Semua' },
                    { key: 'pair', label: 'Pasangan Lengkap' },
                    { key: 'slip_only', label: 'Slip Saja' },
                    { key: 'verif_only', label: 'Verifikasi Saja' },
                    { key: 'attention', label: 'Perlu Perhatian' },
                ],

                init() {},

                get unknownFiles() {
                    return this.files.filter(f => f.role === 'unknown' && f.scan_status !== 'pending' && f.scan_status !== 'scanning' && f.scan_status !== 'ocr');
                },

                get filteredRows() {
                    if (this.activeFilter === 'all') return this.rows;
                    if (this.activeFilter === 'attention') {
                        return this.rows.filter(r => r.precheck_status === 'blocked' || r.precheck_status === 'warning' || r.commit_status === 'error');
                    }
                    return this.rows.filter(r => r.mode === this.activeFilter);
                },

                get attentionCount() {
                    return this.unknownFiles.length + this.rows.filter(r => r.precheck_status === 'blocked' || r.precheck_status === 'warning' || r.commit_status === 'error').length;
                },

                get successCount() {
                    return this.rows.filter(r => r.commit_status === 'success').length;
                },

                get errorCount() {
                    return this.rows.filter(r => r.commit_status === 'error').length;
                },

                get readySelectedCount() {
                    return this.rows.filter(r => r.selected && r.precheck_status !== 'blocked' && r.commit_status !== 'success').length;
                },

                get allSelectableChecked() {
                    const selectable = this.rows.filter(r => r.precheck_status !== 'blocked' && r.commit_status !== 'success');
                    if (selectable.length === 0) return false;
                    return selectable.every(r => r.selected);
                },

                countByMode(mode) {
                    return this.rows.filter(r => r.mode === mode).length;
                },

                getFilterCount(key) {
                    if (key === 'all') return this.rows.length;
                    if (key === 'attention') {
                        return this.rows.filter(r => r.precheck_status === 'blocked' || r.precheck_status === 'warning' || r.commit_status === 'error').length;
                    }
                    return this.rows.filter(r => r.mode === key).length;
                },

                formatBytes(bytes) {
                    if (!bytes) return '0 B';
                    const kb = bytes / 1024;
                    if (kb < 1024) return kb.toFixed(1) + ' KB';
                    return (kb / 1024).toFixed(2) + ' MB';
                },

                normalizeNum(val) {
                    return String(val || '').replace(/\s+/g, '').trim().toUpperCase();
                },

                handleDrop(e) {
                    this.isDragging = false;
                    if (this.isCommitting) return;
                    const dropped = Array.from(e.dataTransfer.files || []).filter(f => f.type === 'application/pdf' || f.name.toLowerCase().endsWith('.pdf'));
                    this.addFiles(dropped);
                },

                handleFileSelect(e) {
                    const selected = Array.from(e.target.files || []).filter(f => f.type === 'application/pdf' || f.name.toLowerCase().endsWith('.pdf'));
                    this.addFiles(selected);
                    e.target.value = '';
                },

                addFiles(fileList) {
                    let added = 0;
                    for (const file of fileList) {
                        if (file.size > 25 * 1024 * 1024) {
                            alert('Berkas ' + file.name + ' melebihi batas 25 MB.');
                            continue;
                        }
                        if (this.files.some(f => f.name === file.name && f.size === file.size)) {
                            continue;
                        }

                        const id = 'f_' + (this.fileSeq++);
                        this.files.push({
                            id,
                            file,
                            name: file.name,
                            size: file.size,
                            objectUrl: URL.createObjectURL(file),
                            role: 'unknown',
                            jenis: 'GRS',
                            document_code: file.name.replace(/\.pdf$/i, ''),
                            filename_matches_do: false,
                            no_grs: '',
                            no_rdtv: '',
                            no_po: '',
                            no_do: '',
                            vendor_name: '',
                            lokasi_penerimaan: '',
                            tanggal_penerimaan: '',
                            material_code: '',
                            nama_barang: '',
                            reason: '',
                            needs_ocr: false,
                            confidence: 'low',
                            scan_status: 'pending',
                            scan_note: 'Menunggu pembacaan...',
                        });
                        added++;
                    }

                    if (added > 0) {
                        this.processPendingScans();
                    }
                },

                loadScript(src) {
                    window.__combinedOcrScripts = window.__combinedOcrScripts || {};
                    if (window.__combinedOcrScripts[src]) {
                        return window.__combinedOcrScripts[src];
                    }
                    const existing = Array.from(document.getElementsByTagName('script')).find(s => s.src === src);
                    if (existing && existing.dataset.loaded === '1') {
                        return Promise.resolve();
                    }
                    const promise = new Promise((resolve, reject) => {
                        const s = existing || document.createElement('script');
                        s.addEventListener('load', () => {
                            s.dataset.loaded = '1';
                            resolve();
                        }, { once: true });
                        s.addEventListener('error', (err) => {
                            delete window.__combinedOcrScripts[src];
                            reject(err);
                        }, { once: true });
                        if (!existing) {
                            s.src = src;
                            s.async = true;
                            document.head.appendChild(s);
                        }
                    });
                    window.__combinedOcrScripts[src] = promise;
                    return promise;
                },

                async ensureOcrLibraries() {
                    if (typeof window.pdfjsLib === 'undefined') {
                        await this.loadScript('https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js');
                    }
                    if (window.pdfjsLib && !window.pdfjsLib.GlobalWorkerOptions.workerSrc) {
                        window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                    }
                    if (typeof window.Tesseract === 'undefined') {
                        await this.loadScript('https://cdn.jsdelivr.net/npm/tesseract.js' + '@' + '5/dist/tesseract.min.js');
                    }
                },

                async getTesseractWorker() {
                    await this.ensureOcrLibraries();
                    if (!this.tesseractWorker && typeof window.Tesseract !== 'undefined') {
                        this.activityMessage = 'Menyiapkan pembaca teks gambar (OCR)...';
                        this.tesseractWorker = await window.Tesseract.createWorker('eng', 1, {
                            workerPath: 'https://cdn.jsdelivr.net/npm/tesseract.js' + '@' + '5/dist/worker.min.js',
                            corePath: 'https://cdn.jsdelivr.net/npm/tesseract.js-core' + '@' + '5',
                            langPath: 'https://tessdata.projectnaptha.com/4.0.0_fast',
                        });
                    }
                    return this.tesseractWorker;
                },

                cleanOcrText(raw) {
                    if (!raw) return '';
                    let cleaned = raw
                        .replace(/\r?\n/g, ' ')
                        .replace(/[\(\[]\s*[1Il|!][0Oo][5S]\s*[\)\]]/gi, '(105)')
                        .replace(/[\(\[]\s*[1Il|!][0Oo]3\s*[\)\]]/gi, '(103)')
                        .replace(/[\(\[]\s*[1Il|!][0Oo][1Il|!]\s*[\)\]]/gi, '(101)')
                        .replace(/[\(\[]\s*[1Il|!][2Z][4A]\s*[\)\]]/gi, '(124)');
                    cleaned = cleaned
                        .replace(/([0-9])[Oo]([0-9])/g, '$10$2')
                        .replace(/([0-9])[Oo]([0-9])/g, '$10$2')
                        .replace(/\b[5S]([0Oo])([0Oo])(\d{7})\b/gi, '500$3')
                        .replace(/\b[5S]3(\d{8})\b/gi, '53$1')
                        .replace(/\b(500)\s+(\d{3,4})\s+(\d{3,4})\b/g, '$1$2$3')
                        .replace(/\b([45]\d{2,3})\s+(\d{3})\s+(\d{3,4})\b/g, '$1$2$3');
                    return cleaned;
                },

                extractOcrMetadata(rawText) {
                    const text = this.cleanOcrText(rawText);
                    let docType = null;
                    let role = null;
                    let grs = null;
                    let rdtv = null;
                    let gr103 = null;
                    let po = null;
                    let doNum = null;
                    let lokasi = null;

                    if (!text || text.length < 15) {
                        return { docType, role, grs, rdtv, po, doNum, lokasi };
                    }

                    const isVerif = /VERIFIKASI\s+PENERIMAAN\s+BARANG|VERIFIKASI\s+PENOLAKAN\s+BARANG|Berita\s+Acara\s+(?:Penerimaan|Penolakan)|Status\s+Penerimaan\s+Barang|Nomor\s*:\s*(?:GRS|RDTV)-|Nomor\s*GR\s*\(\s*(?:101|103)\s*\)|Lokasi\s+Penerimaan|Dokumen\s+ini\s+telah\s+disetujui/i.test(text);
                    const isRdtv = /VERIFIKASI\s+PENOLAKAN\s+BARANG|Return\s+Delivery\s+To\s+Vendor|Berita\s+Acara\s+Penolakan|Nomor\s*RDTV\s*\(?124\)?|RDTV\s*\(?124\)?|Alasan\s+Pengembalian/i.test(text);
                    const isGrs = /VERIFIKASI\s+PENERIMAAN\s+BARANG|Goods\s+Receipt\s+Slip|Berita\s+Acara\s+Penerimaan|Nomor\s*GRS\s*\(?105\)?|GRS\s*\(?105\)?|Nomor\s*GR\s*\(?101\)?|GR\s*\(?101\)?/i.test(text);

                    if (isRdtv) {
                        docType = 'RDTV';
                        role = isVerif ? 'verifikasi_rdtv' : 'rdtv_slip';
                    } else if (isGrs) {
                        docType = 'GRS';
                        role = isVerif ? 'verifikasi_grs' : 'grs_slip';
                    }

                    const mGr103 = text.match(/(?:Nomor\s*GR\s*\(\s*103\s*\)|No\.?\s*GR\s*\(\s*103\s*\)|GR\s*\(\s*103\s*\)|\(103\))[\s:;|—–-]{0,25}(500\d{7})/i);
                    if (mGr103) gr103 = mGr103[1];

                    if (docType === 'RDTV' || !docType) {
                        const mRdtv = text.match(/(?:Nomor\s*RDTV\s*\(?124\)?|RDTV\s*\(?124\)?|\(124\)|No\.?\s*RDTV)[\s:;|—–-]{0,25}(500\d{7})/i);
                        if (mRdtv) {
                            rdtv = mRdtv[1];
                            docType = 'RDTV';
                        }
                    }

                    if (docType !== 'RDTV') {
                        const mSeq101 = text.match(/Nomor\s*PO[\s\S]{0,120}?Nomor\s*GR\s*\(?101\)?[\s\S]{0,260}?([345]\d{9})(?:\s+Tahap(?:\s+[A-Za-z0-9-]+){1,3})?[\s:;|—–-]+(500\d{7})/i);
                        if (mSeq101) {
                            po = mSeq101[1];
                            grs = mSeq101[2];
                            docType = docType || 'GRS';
                        }

                        if (!grs) {
                            const mGrs = text.match(/(?:Nomor\s*GRS\s*\(?105\)?|GRS\s*\(?105\)?|\(105\)|Nomor\s*GR\s*\(?101\)?|No\.?\s*GR\s*\(?101\)?|GR\s*\(?101\)?|\(101\)|No\.?\s*Release\s*GR|No\.?\s*GRS)[\s:;|—–-]{0,25}(500\d{7})/i);
                            if (mGrs) {
                                grs = mGrs[1];
                                docType = docType || 'GRS';
                            }
                        }
                    }

                    const mPoExplicit = text.match(/(?:Nomor\s*PO|No\.?\s*PO|Purchase\s*Order|P\.O\.?)[\s:;|—–-]{0,25}([345]\d{9})/i);
                    if (mPoExplicit && mPoExplicit[1] !== grs && mPoExplicit[1] !== rdtv && mPoExplicit[1] !== gr103) {
                        po = mPoExplicit[1];
                    }

                    if (!grs && !rdtv) {
                        const all500 = [...new Set(Array.from(text.matchAll(/\b(500\d{7})\b/g), m => m[1]))]
                            .filter(n => n !== po && !/^5000\d{6}$/.test(n));
                        const without103 = gr103 ? all500.filter(n => n !== gr103) : all500;
                        const picked = without103.length >= 2 && /103/i.test(text) && /(?:105|101|124)/i.test(text)
                            ? without103[1]
                            : (without103[0] || all500[all500.length - 1] || null);
                        if (picked) {
                            if (docType === 'RDTV') rdtv = picked;
                            else {
                                grs = picked;
                                docType = docType || 'GRS';
                            }
                        }
                    }

                    const activeDoc = rdtv || grs;
                    if (!po) {
                        const mPo = text.match(/\b(53\d{8}|5000\d{6})\b/);
                        if (mPo && mPo[1] !== activeDoc && mPo[1] !== gr103) {
                            po = mPo[1];
                        } else {
                            const poCandidates = [...new Set(Array.from(text.matchAll(/\b([345]\d{9})\b/g), m => m[1]))]
                                .filter(n => n !== activeDoc && n !== gr103);
                            if (poCandidates.length > 0) po = poCandidates[0];
                        }
                    }

                    const mDo = text.match(/Nomor\s*DO(?:\/S[J\]])?[\s:;|—–-]{0,20}([A-Za-z0-9\/-]{4,30})/i)
                        || text.match(/No\.?\s*Surat\s*Jalan[\s:;|—–-]{0,20}([A-Za-z0-9\/-]{4,30})/i);
                    if (mDo && mDo[1] !== '-' && mDo[1] !== po) {
                        doNum = mDo[1];
                    }

                    const mLoc = text.match(/Lokasi\s*Penerimaan[\s:;|—–-]{0,15}([A-Za-z0-9 \-_\/]{3,60}?)(?=\s+(?:Dapat\s*kami|Status|Diajukan|Disetujui)|$)/i);
                    if (mLoc && mLoc[1] && mLoc[1].trim() !== '-') {
                        lokasi = mLoc[1].trim();
                    }

                    return { docType, role, grs, rdtv, po, doNum, lokasi };
                },

                cropCanvasRegion(sourceCanvas, yStartRatio, yEndRatio) {
                    const sy = Math.floor(sourceCanvas.height * yStartRatio);
                    const sh = Math.max(1, Math.floor(sourceCanvas.height * (yEndRatio - yStartRatio)));
                    const cropped = document.createElement('canvas');
                    cropped.width = sourceCanvas.width;
                    cropped.height = sh;
                    const ctx = cropped.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, cropped.width, cropped.height);
                    ctx.drawImage(sourceCanvas, 0, sy, sourceCanvas.width, sh, 0, 0, cropped.width, sh);
                    return cropped;
                },

                async runClientOcr(fileObj) {
                    await this.ensureOcrLibraries();
                    const arrayBuffer = await fileObj.file.arrayBuffer();
                    const pdf = await window.pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                    const page = await pdf.getPage(1);

                    const textContent = await page.getTextContent();
                    const digitalText = textContent.items.map(t => t.str).join(' ');
                    let detected = this.extractOcrMetadata(digitalText);

                    if ((!detected.grs && !detected.rdtv) || !detected.po || !detected.role) {
                        const worker = await this.getTesseractWorker();
                        if (worker) {
                            const viewport = page.getViewport({ scale: 2.2 });
                            const fullCanvas = document.createElement('canvas');
                            const ctx = fullCanvas.getContext('2d', { willReadFrequently: true });
                            fullCanvas.width = Math.ceil(viewport.width);
                            fullCanvas.height = Math.ceil(viewport.height);
                            ctx.fillStyle = '#ffffff';
                            ctx.fillRect(0, 0, fullCanvas.width, fullCanvas.height);
                            await page.render({ canvasContext: ctx, viewport }).promise;

                            // Tahap 1: Pindai area judul & tabel rincian dokumen (12% - 56% halaman) agar bebas noise header/footer Chrome
                            const metaCanvas = this.cropCanvasRegion(fullCanvas, 0.12, 0.56);
                            const metaRes = await worker.recognize(metaCanvas);
                            const metaDetected = this.extractOcrMetadata(metaRes?.data?.text || '');
                            detected.docType = detected.docType || metaDetected.docType;
                            detected.role = detected.role || metaDetected.role;
                            detected.grs = detected.grs || metaDetected.grs;
                            detected.rdtv = detected.rdtv || metaDetected.rdtv;
                            detected.po = detected.po || metaDetected.po;
                            detected.doNum = detected.doNum || metaDetected.doNum;
                            detected.lokasi = detected.lokasi || metaDetected.lokasi;

                            // Tahap 2: Fallback halaman penuh jika nomor dokumen, PO, atau peran belum lengkap
                            if ((!detected.grs && !detected.rdtv) || !detected.po || !detected.role) {
                                const fullRes = await worker.recognize(fullCanvas);
                                const fullDetected = this.extractOcrMetadata(fullRes?.data?.text || '');
                                detected.docType = detected.docType || fullDetected.docType;
                                detected.role = detected.role || fullDetected.role;
                                detected.grs = detected.grs || fullDetected.grs;
                                detected.rdtv = detected.rdtv || fullDetected.rdtv;
                                detected.po = detected.po || fullDetected.po;
                                detected.doNum = detected.doNum || fullDetected.doNum;
                                detected.lokasi = detected.lokasi || fullDetected.lokasi;
                            }
                        }
                    }

                    return detected;
                },

                async callInspectEndpoint(fileObj, extraMeta = {}) {
                    const formData = new FormData();
                    formData.append('file', fileObj.file);
                    formData.append('original_name', fileObj.name);
                    Object.entries(extraMeta).forEach(([k, v]) => {
                        if (v !== null && v !== undefined && String(v).trim() !== '') {
                            formData.append(k, String(v).trim());
                        }
                    });

                    const res = await fetch(this.config.inspectUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.config.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    if (!res.ok) {
                        throw new Error('HTTP ' + res.status);
                    }
                    const json = await res.json();
                    return json.data || {};
                },

                applyInspectResult(fileObj, data) {
                    fileObj.role = data.role || 'unknown';
                    fileObj.jenis = data.jenis || 'GRS';
                    fileObj.document_code = data.document_code || fileObj.document_code;
                    fileObj.filename_matches_do = Boolean(data.filename_matches_do);
                    fileObj.no_grs = data.no_grs || '';
                    fileObj.no_rdtv = data.no_rdtv || '';
                    fileObj.no_po = data.no_po || '';
                    fileObj.no_do = data.no_do || '';
                    fileObj.vendor_name = data.vendor_name || '';
                    fileObj.lokasi_penerimaan = data.lokasi_penerimaan || '';
                    fileObj.tanggal_penerimaan = data.tanggal_penerimaan || '';
                    fileObj.material_code = data.material_code || '';
                    fileObj.nama_barang = data.nama_barang || '';
                    if (data.alasan_penolakan && !fileObj.reason) {
                        fileObj.reason = data.alasan_penolakan;
                    }
                    fileObj.needs_ocr = Boolean(data.needs_ocr);
                    fileObj.confidence = data.confidence || 'low';
                },

                async processPendingScans() {
                    if (this.isScanning) return;
                    this.isScanning = true;

                    try {
                        while (true) {
                            const pending = this.files.filter(f => f.scan_status === 'pending');
                            if (pending.length === 0) break;

                            const total = this.files.length;
                            let doneCount = this.files.filter(f => f.scan_status === 'done' || f.scan_status === 'error').length;

                            for (const fileObj of pending) {
                                fileObj.scan_status = 'scanning';
                                this.activityMessage = 'Membaca berkas: ' + fileObj.name;
                                this.progressPercent = Math.round((doneCount / Math.max(1, total)) * 100);

                                try {
                                    let data = await this.callInspectEndpoint(fileObj);
                                    this.applyInspectResult(fileObj, data);

                                    if (data.needs_ocr || data.role === 'unknown' || (!data.no_grs && !data.no_rdtv) || !data.no_po) {
                                        fileObj.scan_status = 'ocr';
                                        this.activityMessage = 'Memindai teks gambar (OCR): ' + fileObj.name;
                                        try {
                                            const ocrMeta = await this.runClientOcr(fileObj);
                                            if (ocrMeta.docType || ocrMeta.role || ocrMeta.grs || ocrMeta.rdtv || ocrMeta.po || ocrMeta.lokasi) {
                                                data = await this.callInspectEndpoint(fileObj, {
                                                    ocr_done: '1',
                                                    jenis: ocrMeta.docType,
                                                    role: ocrMeta.role,
                                                    no_grs: ocrMeta.grs,
                                                    no_rdtv: ocrMeta.rdtv,
                                                    no_po: ocrMeta.po,
                                                    no_do: ocrMeta.doNum,
                                                    lokasi_penerimaan: ocrMeta.lokasi,
                                                });
                                                this.applyInspectResult(fileObj, data);
                                            }
                                        } catch (ocrErr) {
                                            console.warn('OCR fallback warning:', ocrErr);
                                        }
                                    }

                                    fileObj.scan_status = 'done';
                                    fileObj.scan_note = fileObj.role === 'unknown'
                                        ? 'Peran berkas belum terdeteksi otomatis.'
                                        : 'Terbaca';
                                } catch (err) {
                                    console.error('Inspect error:', err);
                                    fileObj.scan_status = 'error';
                                    fileObj.scan_note = 'Gagal membaca berkas di server.';
                                }

                                doneCount++;
                                this.progressPercent = Math.round((doneCount / Math.max(1, total)) * 100);
                            }
                        }
                    } finally {
                        this.isScanning = false;
                        if (this.tesseractWorker) {
                            try { await this.tesseractWorker.terminate(); } catch (e) {}
                            this.tesseractWorker = null;
                        }
                    }

                    this.rebuildMappings();
                    await this.runPrecheck();
                },

                getOrCreateRowState(rowId) {
                    if (!this.rowStates[rowId]) {
                        this.rowStates[rowId] = {
                            selected: true,
                            userToggledSelect: false,
                            precheck_status: 'pending',
                            precheck_message: 'Memeriksa kelayakan dokumen...',
                            target_receipt_id: null,
                            will_auto_create_do: false,
                            auto_location_name: '',
                            vendor_name: '-',
                            vendor_emails: [],
                            resolved_no_po: null,
                            resolved_no_do: null,
                            commit_status: 'idle',
                            commit_message: '',
                            email_status: null,
                        };
                    }
                    return this.rowStates[rowId];
                },

                rebuildMappings() {
                    const slips = this.files.filter(f => f.role === 'grs_slip' || f.role === 'rdtv_slip');
                    const verifs = this.files.filter(f => f.role === 'verifikasi_grs' || f.role === 'verifikasi_rdtv');
                    const slipMap = Object.fromEntries(slips.map(s => [s.id, s]));
                    const verifMap = Object.fromEntries(verifs.map(v => [v.id, v]));

                    const assignedVerifForSlip = {};
                    const usedVerifs = new Set();
                    const pairReasons = {};

                    // 1. Terapkan pasangan manual terlebih dahulu
                    for (const [slipId, verifId] of Object.entries(this.manualPairs)) {
                        if (slipMap[slipId] && verifMap[verifId] && !usedVerifs.has(verifId)) {
                            assignedVerifForSlip[slipId] = verifId;
                            usedVerifs.add(verifId);
                            pairReasons[slipId] = 'Dipetakan Manual';
                        }
                    }

                    const autoCandidates = () => slips.filter(s => !assignedVerifForSlip[s.id] && !this.manualUnpairedSlips[s.id]);
                    const freeVerifs = () => verifs.filter(v => !usedVerifs.has(v.id));

                    // 2a. Cocokkan persis No. GRS / No. RDTV dengan jenis sama
                    for (const slip of autoCandidates()) {
                        const slipDoc = this.normalizeNum(slip.jenis === 'RDTV' ? slip.no_rdtv : slip.no_grs);
                        if (!slipDoc) continue;

                        const match = freeVerifs().find(v => {
                            if (v.jenis !== slip.jenis) return false;
                            const vDoc = this.normalizeNum(v.jenis === 'RDTV' ? v.no_rdtv : v.no_grs);
                            return vDoc !== '' && vDoc === slipDoc;
                        });

                        if (match) {
                            assignedVerifForSlip[slip.id] = match.id;
                            usedVerifs.add(match.id);
                            pairReasons[slip.id] = 'Otomatis (No. ' + slip.jenis + ')';
                        }
                    }

                    // 2b. Cocokkan persis No. PO + No. DO dengan jenis sama
                    for (const slip of autoCandidates()) {
                        const sPo = this.normalizeNum(slip.no_po);
                        const sDo = this.normalizeNum(slip.no_do);
                        if (!sPo || !sDo) continue;

                        const match = freeVerifs().find(v => {
                            return v.jenis === slip.jenis
                                && this.normalizeNum(v.no_po) === sPo
                                && this.normalizeNum(v.no_do) === sDo;
                        });

                        if (match) {
                            assignedVerifForSlip[slip.id] = match.id;
                            usedVerifs.add(match.id);
                            pairReasons[slip.id] = 'Otomatis (PO + DO)';
                        }
                    }

                    // 2c. Cocokkan PO tunggal yang tidak ambigu (1 slip & 1 verif pada PO+jenis yang sama)
                    const remSlips = autoCandidates();
                    const remVerifs = freeVerifs();
                    for (const slip of remSlips) {
                        const sPo = this.normalizeNum(slip.no_po);
                        if (!sPo) continue;

                        const samePoSlips = remSlips.filter(s => !assignedVerifForSlip[s.id] && s.jenis === slip.jenis && this.normalizeNum(s.no_po) === sPo);
                        const samePoVerifs = remVerifs.filter(v => !usedVerifs.has(v.id) && v.jenis === slip.jenis && this.normalizeNum(v.no_po) === sPo);

                        if (samePoSlips.length === 1 && samePoVerifs.length === 1) {
                            const match = samePoVerifs[0];
                            assignedVerifForSlip[slip.id] = match.id;
                            usedVerifs.add(match.id);
                            pairReasons[slip.id] = 'Otomatis (PO Tunggal)';
                        }
                    }

                    // 3. Bangun array rows untuk tabel
                    const builtRows = [];

                    for (const slip of slips) {
                        const verifId = assignedVerifForSlip[slip.id] || null;
                        const verif = verifId ? verifMap[verifId] : null;
                        const rowId = 'slip_' + slip.id;
                        const st = this.getOrCreateRowState(rowId);

                        const jenis = slip.jenis === 'RDTV' ? 'RDTV' : 'GRS';
                        const noGrs = slip.no_grs || verif?.no_grs || '';
                        const noRdtv = slip.no_rdtv || verif?.no_rdtv || '';
                        const noPo = slip.no_po || verif?.no_po || st.resolved_no_po || '';
                        const noDo = slip.no_do || verif?.no_do || st.resolved_no_do || '';

                        builtRows.push({
                            row_id: rowId,
                            mode: verif ? 'pair' : 'slip_only',
                            jenis,
                            slipFile: slip,
                            verifFile: verif,
                            document_code: slip.document_code || '',
                            no_grs: noGrs,
                            no_rdtv: noRdtv,
                            no_po: noPo,
                            no_do: noDo,
                            verif_no_grs: verif?.no_grs || '',
                            verif_no_rdtv: verif?.no_rdtv || '',
                            verif_no_po: verif?.no_po || '',
                            lokasi_penerimaan: verif?.lokasi_penerimaan || slip.lokasi_penerimaan || st.auto_location_name || '',
                            tanggal_penerimaan: slip.tanggal_penerimaan || '',
                            material_code: slip.material_code || '',
                            nama_barang: slip.nama_barang || '',
                            will_auto_create_do: Boolean(st.will_auto_create_do),
                            reason: slip.reason || verif?.reason || '',
                            pair_reason: verif ? (pairReasons[slip.id] || 'Dipetakan') : 'Slip Saja — Menunggu Verifikasi',
                            vendor_name: st.vendor_name !== '-' ? st.vendor_name : (slip.vendor_name || verif?.vendor_name || '-'),
                            selected: st.selected,
                            precheck_status: st.precheck_status,
                            precheck_message: st.precheck_message,
                            target_receipt_id: st.target_receipt_id,
                            vendor_emails: st.vendor_emails,
                            commit_status: st.commit_status,
                            commit_message: st.commit_message,
                            email_status: st.email_status,
                        });
                    }

                    for (const verif of verifs) {
                        if (usedVerifs.has(verif.id)) continue;
                        const rowId = 'verif_' + verif.id;
                        const st = this.getOrCreateRowState(rowId);
                        const jenis = verif.jenis === 'RDTV' ? 'RDTV' : 'GRS';

                        builtRows.push({
                            row_id: rowId,
                            mode: 'verif_only',
                            jenis,
                            slipFile: null,
                            verifFile: verif,
                            document_code: '',
                            no_grs: verif.no_grs || '',
                            no_rdtv: verif.no_rdtv || '',
                            no_po: verif.no_po || st.resolved_no_po || '',
                            no_do: verif.no_do || st.resolved_no_do || '',
                            verif_no_grs: verif.no_grs || '',
                            verif_no_rdtv: verif.no_rdtv || '',
                            verif_no_po: verif.no_po || '',
                            reason: verif.reason || '',
                            pair_reason: 'Verifikasi Saja — Cocokkan ke Invoice Eksisting',
                            vendor_name: st.vendor_name !== '-' ? st.vendor_name : (verif.vendor_name || '-'),
                            selected: st.selected,
                            precheck_status: st.precheck_status,
                            precheck_message: st.precheck_message,
                            target_receipt_id: st.target_receipt_id,
                            vendor_emails: st.vendor_emails,
                            commit_status: st.commit_status,
                            commit_message: st.commit_message,
                            email_status: st.email_status,
                        });
                    }

                    this.rows = builtRows;
                },

                getSelectableVerifsForSlip(slipId) {
                    const currentVerifId = this.rows.find(r => r.slipFile && r.slipFile.id === slipId)?.verifFile?.id || null;
                    const pairedByOthers = new Set(
                        this.rows
                            .filter(r => r.slipFile && r.slipFile.id !== slipId && r.verifFile)
                            .map(r => r.verifFile.id)
                    );
                    return this.files.filter(f =>
                        (f.role === 'verifikasi_grs' || f.role === 'verifikasi_rdtv')
                        && (f.id === currentVerifId || !pairedByOthers.has(f.id))
                    );
                },

                formatVerifOption(vf) {
                    const num = vf.jenis === 'RDTV' ? vf.no_rdtv : vf.no_grs;
                    const meta = [vf.jenis, num ? '#' + num : null, vf.no_po ? 'PO ' + vf.no_po : null].filter(Boolean).join(' • ');
                    return vf.name + (meta ? ' (' + meta + ')' : '');
                },

                setManualPair(slipId, verifId) {
                    if (!verifId) {
                        delete this.manualPairs[slipId];
                        this.manualUnpairedSlips[slipId] = true;
                    } else {
                        // Lepaskan jika verifId sebelumnya dipasangkan manual ke slip lain
                        for (const [otherSlipId, assignedV] of Object.entries(this.manualPairs)) {
                            if (assignedV === verifId && otherSlipId !== slipId) {
                                delete this.manualPairs[otherSlipId];
                            }
                        }
                        delete this.manualUnpairedSlips[slipId];
                        this.manualPairs[slipId] = verifId;
                    }

                    const st = this.getOrCreateRowState('slip_' + slipId);
                    st.userToggledSelect = false;
                    this.rebuildMappings();
                    this.runPrecheck();
                },

                setFileRole(fileId, newRole) {
                    const f = this.files.find(item => item.id === fileId);
                    if (!f) return;
                    f.role = newRole;
                    f.jenis = newRole.includes('rdtv') ? 'RDTV' : 'GRS';
                    if (f.jenis === 'RDTV' && !f.no_rdtv && f.no_grs) {
                        f.no_rdtv = f.no_grs;
                        f.no_grs = '';
                    } else if (f.jenis === 'GRS' && !f.no_grs && f.no_rdtv) {
                        f.no_grs = f.no_rdtv;
                        f.no_rdtv = '';
                    }
                    this.rebuildMappings();
                    this.runPrecheck();
                },

                swapSingleFileRole(fileId) {
                    const f = this.files.find(item => item.id === fileId);
                    if (!f) return;
                    delete this.manualPairs[fileId];
                    delete this.manualUnpairedSlips[fileId];
                    for (const [sId, vId] of Object.entries(this.manualPairs)) {
                        if (vId === fileId) delete this.manualPairs[sId];
                    }

                    if (f.role === 'grs_slip') f.role = 'verifikasi_grs';
                    else if (f.role === 'verifikasi_grs') f.role = 'grs_slip';
                    else if (f.role === 'rdtv_slip') f.role = 'verifikasi_rdtv';
                    else if (f.role === 'verifikasi_rdtv') f.role = 'rdtv_slip';

                    this.rebuildMappings();
                    this.runPrecheck();
                },

                toggleRowJenis(row) {
                    if (this.isCommitting || row.commit_status === 'success') return;
                    const nextJenis = row.jenis === 'RDTV' ? 'GRS' : 'RDTV';
                    const targets = [row.slipFile, row.verifFile].filter(Boolean);
                    for (const f of targets) {
                        f.jenis = nextJenis;
                        if (f.role === 'grs_slip' || f.role === 'rdtv_slip') {
                            f.role = nextJenis === 'RDTV' ? 'rdtv_slip' : 'grs_slip';
                        } else if (f.role === 'verifikasi_grs' || f.role === 'verifikasi_rdtv') {
                            f.role = nextJenis === 'RDTV' ? 'verifikasi_rdtv' : 'verifikasi_grs';
                        }

                        if (nextJenis === 'RDTV' && !f.no_rdtv && f.no_grs) {
                            f.no_rdtv = f.no_grs;
                            f.no_grs = '';
                        } else if (nextJenis === 'GRS' && !f.no_grs && f.no_rdtv) {
                            f.no_grs = f.no_rdtv;
                            f.no_rdtv = '';
                        }
                    }

                    const st = this.getOrCreateRowState(row.row_id);
                    st.userToggledSelect = false;
                    this.rebuildMappings();
                    this.runPrecheck();
                },

                updateRowDocNumber(row, value) {
                    const clean = String(value || '').trim();
                    const targets = [row.slipFile, row.verifFile].filter(Boolean);
                    for (const f of targets) {
                        if (row.jenis === 'RDTV') f.no_rdtv = clean;
                        else f.no_grs = clean;
                    }
                    if (row.jenis === 'RDTV') row.no_rdtv = clean;
                    else row.no_grs = clean;
                    this.schedulePrecheck();
                },

                updateRowPoNumber(row, value) {
                    const clean = String(value || '').trim();
                    const targets = [row.slipFile, row.verifFile].filter(Boolean);
                    for (const f of targets) {
                        f.no_po = clean;
                    }
                    row.no_po = clean;
                    this.schedulePrecheck();
                },

                updateRowReason(row, value) {
                    if (row.slipFile) row.slipFile.reason = value;
                    row.reason = value;
                },

                updateRowLokasi(row, value) {
                    const clean = String(value || '');
                    if (row.verifFile) row.verifFile.lokasi_penerimaan = clean;
                    if (row.slipFile) row.slipFile.lokasi_penerimaan = clean;
                    row.lokasi_penerimaan = clean;
                    this.schedulePrecheck();
                },

                toggleRowSelection(rowId, checked) {
                    const st = this.getOrCreateRowState(rowId);
                    st.selected = Boolean(checked);
                    st.userToggledSelect = true;
                    const row = this.rows.find(r => r.row_id === rowId);
                    if (row) row.selected = st.selected;
                },

                toggleSelectAll(checked) {
                    for (const row of this.rows) {
                        if (row.precheck_status === 'blocked' || row.commit_status === 'success') continue;
                        const st = this.getOrCreateRowState(row.row_id);
                        st.selected = Boolean(checked);
                        st.userToggledSelect = true;
                        row.selected = st.selected;
                    }
                },

                removeFile(fileId) {
                    const idx = this.files.findIndex(f => f.id === fileId);
                    if (idx !== -1) {
                        try { URL.revokeObjectURL(this.files[idx].objectUrl); } catch (e) {}
                        this.files.splice(idx, 1);
                    }
                    delete this.manualPairs[fileId];
                    delete this.manualUnpairedSlips[fileId];
                    for (const [sId, vId] of Object.entries(this.manualPairs)) {
                        if (vId === fileId) delete this.manualPairs[sId];
                    }
                    this.rebuildMappings();
                    if (this.rows.length > 0) this.runPrecheck();
                },

                removeRow(row) {
                    if (this.isCommitting) return;
                    const idsToRemove = [row.slipFile?.id, row.verifFile?.id].filter(Boolean);
                    for (const id of idsToRemove) {
                        const idx = this.files.findIndex(f => f.id === id);
                        if (idx !== -1) {
                            try { URL.revokeObjectURL(this.files[idx].objectUrl); } catch (e) {}
                            this.files.splice(idx, 1);
                        }
                        delete this.manualPairs[id];
                        delete this.manualUnpairedSlips[id];
                    }
                    delete this.rowStates[row.row_id];
                    this.rebuildMappings();
                    if (this.rows.length > 0) this.runPrecheck();
                },

                clearAll() {
                    if (this.isCommitting) return;
                    for (const f of this.files) {
                        try { URL.revokeObjectURL(f.objectUrl); } catch (e) {}
                    }
                    this.files = [];
                    this.rows = [];
                    this.manualPairs = {};
                    this.manualUnpairedSlips = {};
                    this.rowStates = {};
                    this.activeFilter = 'all';
                    this.progressPercent = 0;
                    this.activityMessage = '';
                },

                schedulePrecheck() {
                    if (this.precheckTimer) clearTimeout(this.precheckTimer);
                    this.precheckTimer = setTimeout(() => {
                        this.runPrecheck();
                    }, 420);
                },

                async runPrecheck() {
                    const checkable = this.rows.filter(r => r.commit_status !== 'success');
                    if (checkable.length === 0) return;

                    this.isPrechecking = true;
                    this.activityMessage = 'Memvalidasi kelayakan DO dan pencocokan Invoice Barang...';
                    this.progressPercent = 50;

                    try {
                        const payloadRows = checkable.map(r => ({
                            row_id: r.row_id,
                            mode: r.mode,
                            jenis: r.jenis,
                            document_code: r.document_code || null,
                            no_grs: r.no_grs || null,
                            no_rdtv: r.no_rdtv || null,
                            no_po: r.no_po || null,
                            no_do: r.no_do || null,
                            verif_no_grs: r.verifFile?.no_grs || null,
                            verif_no_rdtv: r.verifFile?.no_rdtv || null,
                            verif_no_po: r.verifFile?.no_po || null,
                            lokasi_penerimaan: r.lokasi_penerimaan || null,
                            tanggal_penerimaan: r.tanggal_penerimaan || null,
                            material_code: r.material_code || null,
                            nama_barang: r.nama_barang || null,
                            vendor_name: r.vendor_name && r.vendor_name !== '-' ? r.vendor_name : null,
                        }));

                        const res = await fetch(this.config.precheckUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.config.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                transaction_date: this.transactionDate,
                                rows: payloadRows,
                            }),
                        });

                        const json = await res.json();
                        if (res.ok && json.success && Array.isArray(json.results)) {
                            for (const item of json.results) {
                                const st = this.getOrCreateRowState(item.row_id);
                                st.precheck_status = item.status || 'warning';
                                st.precheck_message = item.message || '';
                                st.target_receipt_id = item.target_receipt_id || null;
                                st.will_auto_create_do = Boolean(item.will_auto_create_do);
                                st.auto_location_name = item.auto_location_name || '';
                                st.vendor_name = item.vendor_name || '-';
                                st.vendor_emails = item.vendor_emails || [];
                                st.resolved_no_po = item.resolved_no_po || null;
                                st.resolved_no_do = item.resolved_no_do || null;

                                if (item.status === 'blocked') {
                                    st.selected = false;
                                } else if (!st.userToggledSelect) {
                                    st.selected = !Boolean(item.already_has_verif);
                                }
                            }
                            this.rebuildMappings();
                        }
                    } catch (err) {
                        console.error('Precheck error:', err);
                    } finally {
                        this.isPrechecking = false;
                        this.progressPercent = 100;
                    }
                },

                async commitRows(targetRows) {
                    if (this.isCommitting || targetRows.length === 0) return;
                    this.isCommitting = true;
                    this.progressPercent = 0;

                    try {
                        for (let i = 0; i < targetRows.length; i++) {
                            const row = targetRows[i];
                            const st = this.getOrCreateRowState(row.row_id);
                            st.commit_status = 'committing';
                            st.commit_message = 'Menyimpan ke server...';
                            row.commit_status = 'committing';
                            row.commit_message = 'Menyimpan ke server...';

                            this.activityMessage = 'Memproses baris ' + (i + 1) + ' dari ' + targetRows.length + ' (' + (row.slipFile?.name || row.verifFile?.name || '') + ')';
                            this.progressPercent = Math.round((i / targetRows.length) * 100);

                            const formData = new FormData();
                            formData.append('mode', row.mode);
                            formData.append('jenis', row.jenis);
                            formData.append('transaction_date', this.transactionDate);
                            formData.append('send_email', this.sendEmail ? '1' : '0');
                            if (row.reason) formData.append('reason', row.reason);
                            if (row.no_grs) formData.append('no_grs', row.no_grs);
                            if (row.no_rdtv) formData.append('no_rdtv', row.no_rdtv);
                            if (row.no_po) formData.append('no_po', row.no_po);
                            if (row.no_do) formData.append('no_do', row.no_do);
                            if (row.lokasi_penerimaan) formData.append('lokasi_penerimaan', row.lokasi_penerimaan);
                            if (row.tanggal_penerimaan) formData.append('tanggal_penerimaan', row.tanggal_penerimaan);
                            if (row.vendor_name && row.vendor_name !== '-') formData.append('vendor_name', row.vendor_name);
                            if (row.material_code) formData.append('material_code', row.material_code);
                            if (row.nama_barang) formData.append('nama_barang', row.nama_barang);
                            if (row.target_receipt_id) formData.append('target_receipt_id', String(row.target_receipt_id));
                            if (row.slipFile) formData.append('slip_file', row.slipFile.file, row.slipFile.name);
                            if (row.verifFile) formData.append('verif_file', row.verifFile.file, row.verifFile.name);

                            try {
                                const res = await fetch(this.config.commitUrl, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': this.config.csrfToken,
                                        'Accept': 'application/json',
                                    },
                                    body: formData,
                                });

                                const json = await res.json();
                                if (res.ok && json.success) {
                                    st.commit_status = 'success';
                                    st.commit_message = json.message || 'Berhasil disimpan.';
                                    st.email_status = json.email_status || null;
                                    st.selected = false;
                                } else {
                                    st.commit_status = 'error';
                                    st.commit_message = json.message || 'Gagal menyimpan baris ini.';
                                    st.email_status = json.email_status || null;
                                }
                            } catch (err) {
                                console.error('Commit error:', err);
                                st.commit_status = 'error';
                                st.commit_message = 'Gagal menghubungi server saat menyimpan.';
                            }

                            row.commit_status = st.commit_status;
                            row.commit_message = st.commit_message;
                            row.email_status = st.email_status;
                            row.selected = st.selected;
                        }
                    } finally {
                        this.progressPercent = 100;
                        this.activityMessage = 'Proses penyimpanan selesai.';
                        this.isCommitting = false;
                    }
                },

                async commitSelected() {
                    const targets = this.rows.filter(r => r.selected && r.precheck_status !== 'blocked' && r.commit_status !== 'success');
                    await this.commitRows(targets);
                },

                async retryFailed() {
                    const failed = this.rows.filter(r => r.commit_status === 'error');
                    for (const r of failed) {
                        const st = this.getOrCreateRowState(r.row_id);
                        st.selected = true;
                        r.selected = true;
                    }
                    await this.commitRows(failed);
                },

                getModeLabel(mode) {
                    if (mode === 'pair') return 'Pasangan Lengkap';
                    if (mode === 'slip_only') return 'Slip Saja';
                    return 'Verifikasi Saja';
                },

                getRowBadgeLabel(row) {
                    if (row.commit_status === 'committing') return 'Menyimpan...';
                    if (row.commit_status === 'success') return 'Tersimpan';
                    if (row.commit_status === 'error') return 'Gagal';
                    if (row.precheck_status === 'ready') return 'Siap';
                    if (row.precheck_status === 'warning') return 'Peringatan';
                    if (row.precheck_status === 'blocked') return 'Terkendala';
                    return 'Memeriksa...';
                },

                getRowBadgeClass(row) {
                    if (row.commit_status === 'committing') return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300';
                    if (row.commit_status === 'success') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300';
                    if (row.commit_status === 'error' || row.precheck_status === 'blocked') return 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300';
                    if (row.precheck_status === 'warning') return 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
                    if (row.precheck_status === 'ready') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300';
                    return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
                },

                getEmailBadgeLabel(status) {
                    if (status === 'sent') return 'Email Terkirim';
                    if (status === 'skipped') return 'Email Ditahan';
                    if (status === 'failed') return 'Email Gagal';
                    if (status === 'waiting_verification') return 'Menunggu Verifikasi';
                    return status;
                },

                getEmailBadgeClass(status) {
                    if (status === 'sent') return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800';
                    if (status === 'skipped' || status === 'waiting_verification') return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800';
                    return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800';
                },
            };
        }
    </script>
</x-filament-panels::page>
