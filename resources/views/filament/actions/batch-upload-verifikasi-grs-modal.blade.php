@php
    $uploadUrl = route('filament.admin.resources.invoicing-receipts.upload_verifikasi_grs_ocr', absolute: false);
@endphp

<div
    class="space-y-5"
    x-data="{
        uploadUrl: '{{ $uploadUrl }}',
        csrfToken: '{{ csrf_token() }}',
        isDragging: false,
        isProcessing: false,
        overallProgress: 0,
        currentStepMessage: 'Siap',
        items: [],
        tesseractWorker: null,

        get scannedCount() {
            return this.items.filter(i => ['scanned', 'uploading', 'verified'].includes(i.status)).length;
        },
        get verifiedCount() {
            return this.items.filter(i => i.status === 'verified').length;
        },
        get unmatchedCount() {
            return this.items.filter(i => i.status === 'unmatched').length;
        },
        get hasPendingUploads() {
            return this.items.some(i => ['queued', 'scanning', 'scanned'].includes(i.status));
        },

        init() {
            this.ensureLibraries().catch(err => {
                console.warn('Preload OCR libs warning:', err);
            });
        },

        loadScript(src) {
            const scripts = Array.from(document.getElementsByTagName('script'));
            if (scripts.some(s => s.src === src)) {
                return Promise.resolve();
            }
            return new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = src;
                s.onload = () => resolve();
                s.onerror = (err) => reject(err);
                document.head.appendChild(s);
            });
        },

        async ensureLibraries() {
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

        handleDrop(e) {
            this.isDragging = false;
            const files = Array.from(e.dataTransfer.files).filter(f => f.type === 'application/pdf' || f.name.toLowerCase().endsWith('.pdf'));
            this.addFiles(files);
        },

        handleFileSelect(e) {
            const files = Array.from(e.target.files).filter(f => f.type === 'application/pdf' || f.name.toLowerCase().endsWith('.pdf'));
            this.addFiles(files);
            e.target.value = '';
        },

        addFiles(files) {
            for (const file of files) {
                if (this.items.some(i => i.name === file.name && i.size === file.size)) {
                    continue;
                }

                const fnGrsMatch = file.name.match(/(?:grs|105)[^0-9]*(500\d{7})/i) || file.name.match(/(?:^|[^0-9])(500\d{7})(?:[^0-9]|$)/);
                const fnPoMatch = file.name.match(/(?:po)[^0-9]*([345]\d{9})/i) || file.name.match(/(?:^|[^0-9])([345]\d{9})(?:[^0-9]|$)/);

                this.items.push({
                    file: file,
                    name: file.name,
                    size: file.size,
                    status: 'queued',
                    no_grs: fnGrsMatch ? fnGrsMatch[1] : '',
                    no_po: fnPoMatch ? fnPoMatch[1] : '',
                    no_do: '',
                    source: fnGrsMatch ? 'filename' : 'pending',
                    message: fnGrsMatch ? 'Nomor GRS terdeteksi dari nama berkas' : 'Menunggu pemindaian',
                });
            }
        },

        removeItem(index) {
            this.items.splice(index, 1);
        },

        clearAll() {
            if (this.isProcessing) return;
            this.items = [];
            this.overallProgress = 0;
            this.currentStepMessage = 'Siap';
        },

        retryUnmatched() {
            if (this.isProcessing) return;
            this.items.forEach(item => {
                if (item.status === 'unmatched') {
                    item.status = 'scanned';
                    item.message = 'Mengantre untuk pencocokan ulang...';
                }
            });
            this.startProcessAll();
        },

        getStatusLabel(item) {
            switch (item.status) {
                case 'queued': return 'Antrean';
                case 'scanning': return 'Memindai OCR...';
                case 'scanned': return 'OCR Selesai';
                case 'uploading': return 'Mengunggah...';
                case 'verified': return 'Terverifikasi ✅';
                case 'unmatched': return 'Tagihan Belum Cocok ⚠️';
                case 'error': return 'Error ❌';
                default: return item.status;
            }
        },

        formatBytes(bytes) {
            if (!bytes) return '0 B';
            const kb = bytes / 1024;
            if (kb < 1024) return kb.toFixed(1) + ' KB';
            return (kb / 1024).toFixed(2) + ' MB';
        },

        cleanOcrText(raw) {
            if (!raw) return '';
            return raw
                .replace(/\r?\n/g, ' ')
                .replace(/([0-9])O([0-9])/gi, '$10$2')
                .replace(/\bS(00\d{7})\b/gi, '5$1')
                .replace(/\b([5S])([0O])([0O])(\d{7})\b/gi, '500$4')
                .replace(/\b(500)\s+(\d{3,4})\s+(\d{3,4})\b/g, '$1$2$3')
                .replace(/\b([45]\d{2,3})\s+(\d{3})\s+(\d{3,4})\b/g, '$1$2$3');
        },

        extractIdentifiers(rawText) {
            const text = this.cleanOcrText(rawText);
            let grs = null;
            let po = null;
            let doNum = null;

            if (text && text.length > 20) {
                const mGrs = text.match(/(?:GRS(?:\s*\(105\))?|105|Release\s*GR)[\s\S]{1,40}?(500\d{7})/i)
                          || text.match(/No\s*Release\s*GR\s*[:\s1©]+(500\d{7})/i)
                          || text.match(/\b(500\d{7})\b/);

                const mPo = text.match(/(?:PO|Purchase\s*Order|P\.O\.?)[\s\S]{1,35}?([345]\d{9})/i)
                         || text.match(/\b(53\d{8})\b/)
                         || text.match(/\b(5\d{9})\b/)
                         || text.match(/\b(4\d{9})\b/);

                const mDo = text.match(/Nomor\s*DO(?:\/S[J\]])?\s*[:©1]?\s*([A-Za-z0-9\/-]+)/i)
                         || text.match(/DO\s*[:\s1]+([A-Za-z0-9\/-]{4,30})/i);

                if (mGrs) grs = mGrs[1];
                if (mPo && mPo[1] !== grs) po = mPo[1];
                if (mDo) doNum = mDo[1];
            }

            return { grs, po, doNum };
        },

        async getTesseractWorker() {
            await this.ensureLibraries();
            if (!this.tesseractWorker && typeof window.Tesseract !== 'undefined') {
                this.currentStepMessage = 'Memuat engine OCR Tesseract di browser...';
                this.tesseractWorker = await window.Tesseract.createWorker('eng', 1, {
                    workerPath: 'https://cdn.jsdelivr.net/npm/tesseract.js' + '@' + '5/dist/worker.min.js',
                    corePath: 'https://cdn.jsdelivr.net/npm/tesseract-core' + '@' + '5/tesseract-core.wasm.js',
                });
            }
            return this.tesseractWorker;
        },

        async scanFileOcr(item) {
            item.status = 'scanning';
            this.currentStepMessage = 'Memindai teks dokumen: ' + item.name + '...';

            try {
                await this.ensureLibraries();
                const arrayBuffer = await item.file.arrayBuffer();
                const pdf = await window.pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                const page = await pdf.getPage(1);

                // 1. Cek layer teks digital bawaan
                const textContent = await page.getTextContent();
                const digitalText = textContent.items.map(t => t.str).join(' ');
                let detected = this.extractIdentifiers(digitalText);

                if (detected.grs || detected.po) {
                    item.source = 'digital_text';
                }

                // 2. Scan OCR jika tidak ada teks digital atau belum menemukan nomor GRS
                if (!detected.grs) {
                    this.currentStepMessage = 'Menjalankan OCR pada gambar scan: ' + item.name + '...';
                    const worker = await this.getTesseractWorker();

                    if (worker) {
                        const scale = 1.5;
                        const viewport = page.getViewport({ scale });
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');
                        canvas.width = viewport.width;
                        canvas.height = viewport.height; // Render full height

                        await page.render({
                            canvasContext: ctx,
                            viewport: viewport
                        }).promise;

                        const ocrRes = await worker.recognize(canvas);
                        const ocrText = ocrRes?.data?.text || '';
                        const ocrDetected = this.extractIdentifiers(ocrText);

                        if (ocrDetected.grs) detected.grs = ocrDetected.grs;
                        if (ocrDetected.po) detected.po = ocrDetected.po;
                        if (ocrDetected.doNum) detected.doNum = ocrDetected.doNum;

                        if (detected.grs || detected.po) {
                            item.source = 'ocr_image';
                        }
                    }
                }

                if (detected.grs) item.no_grs = detected.grs;
                if (detected.po) item.no_po = detected.po;
                if (detected.doNum) item.no_do = detected.doNum;

                item.status = 'scanned';
                item.message = item.no_grs ? 'Terdeteksi GRS: ' + item.no_grs : (item.no_po ? 'Terdeteksi PO: ' + item.no_po : 'Nomor belum terdeteksi otomatis');
            } catch (err) {
                console.error('Scan error:', err);
                item.status = 'scanned';
                item.message = 'Pemindaian gambar dilewati, mengandalkan nama file';
            }
        },

        async uploadItem(item) {
            item.status = 'uploading';
            this.currentStepMessage = 'Mengunggah & memverifikasi: ' + item.name + '...';

            const formData = new FormData();
            formData.append('file', item.file);
            formData.append('no_grs', item.no_grs || '');
            formData.append('no_po', item.no_po || '');
            formData.append('no_do', item.no_do || '');
            formData.append('original_name', item.name);

            try {
                const res = await fetch(this.uploadUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const data = await res.json();
                if (res.ok && data.success && data.matched) {
                    item.status = 'verified';
                    item.message = data.message || 'Berhasil diverifikasi';
                    if (data.no_grs) item.no_grs = data.no_grs;
                    if (data.no_po) item.no_po = data.no_po;
                } else {
                    item.status = 'unmatched';
                    item.message = data.message || 'Tagihan invoice tidak ditemukan di sistem';
                }
            } catch (err) {
                console.error('Upload error:', err);
                item.status = 'error';
                item.message = 'Gagal menghubungi server';
            }
        },

        async startProcessAll() {
            if (this.isProcessing) return;
            this.isProcessing = true;

            const total = this.items.length;
            for (let i = 0; i < total; i++) {
                const item = this.items[i];
                this.overallProgress = Math.round((i / total) * 100);

                if (item.status === 'queued') {
                    await this.scanFileOcr(item);
                }

                if (['queued', 'scanned'].includes(item.status)) {
                    await this.uploadItem(item);
                }
            }

            this.overallProgress = 100;
            this.currentStepMessage = 'Seluruh berkas selesai diproses!';
            this.isProcessing = false;

            if (this.tesseractWorker) {
                try {
                    await this.tesseractWorker.terminate();
                } catch (e) {}
                this.tesseractWorker = null;
            }
        },

        finishAndReload() {
            window.location.reload();
        }
    }"
>
    <!-- Header Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-500/10 via-sky-500/5 to-emerald-500/10 p-5 border border-indigo-200/50 dark:border-indigo-800/40">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 border border-indigo-500/20">
                        <svg class="w-3.5 h-3.5 animate-pulse text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Client-Side OCR Active
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
                        Zero Server Load &bull; Bebas 503
                    </span>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                    Pemindaian Otomatis PDF Gambar (Scan) & Verifikasi Tagihan
                </h3>
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    Pilih puluhan berkas PDF scan sekaligus dengan nama apa pun. Dokumen dipindai langsung di komputer Anda dengan Tesseract.js &amp; PDF.js untuk mengambil <b>Nomor GRS (105)</b> dan <b>Nomor PO</b>.
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="text-xs font-mono text-gray-500 dark:text-gray-400 bg-white/70 dark:bg-gray-800/70 px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700">
                    v2.1 Smart Matcher
                </span>
            </div>
        </div>
    </div>

    <!-- Dropzone Area -->
    <div
        class="relative rounded-2xl border-2 border-dashed p-6 text-center transition-all duration-200 cursor-pointer"
        :class="isDragging ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20' : 'border-gray-300 dark:border-gray-700 hover:border-indigo-400 dark:hover:border-indigo-600 bg-gray-50/50 dark:bg-gray-900/30'"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop($event)"
        @click="$refs.fileInput.click()"
    >
        <input
            type="file"
            x-ref="fileInput"
            @change="handleFileSelect($event)"
            multiple
            accept="application/pdf"
            class="hidden"
        />

        <div class="flex flex-col items-center justify-center space-y-2 pointer-events-none">
            <div class="p-3 bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 rounded-2xl ring-4 ring-indigo-50 dark:ring-indigo-950/30">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
            </div>
            <div class="space-y-0.5">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                    Tarik &amp; letakkan berkas PDF Verifikasi GRS di sini, atau <span class="text-indigo-600 dark:text-indigo-400 underline">klik untuk memilih</span>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Bisa memilih hingga 100 berkas PDF sekaligus (hasil scan scanner, print e-office, maupun PDF digital).
                </p>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <template x-if="items.length > 0">
        <div class="space-y-4">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="p-3 rounded-xl bg-gray-100/80 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700/60">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Total Berkas</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white" x-text="items.length"></div>
                </div>
                <div class="p-3 rounded-xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/40">
                    <div class="text-xs text-blue-600 dark:text-blue-400">Memindai / Siap</div>
                    <div class="text-lg font-bold text-blue-700 dark:text-blue-300" x-text="scannedCount"></div>
                </div>
                <div class="p-3 rounded-xl bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40">
                    <div class="text-xs text-emerald-600 dark:text-emerald-400">Terverifikasi ✅</div>
                    <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300" x-text="verifiedCount"></div>
                </div>
                <div class="p-3 rounded-xl bg-amber-50/80 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/40">
                    <div class="text-xs text-amber-600 dark:text-amber-400">Belum Cocok ⚠️</div>
                    <div class="text-lg font-bold text-amber-700 dark:text-amber-300" x-text="unmatchedCount"></div>
                </div>
            </div>

            <!-- Overall Progress Bar -->
            <div class="space-y-1.5" x-show="isProcessing">
                <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
                    <span x-text="currentStepMessage"></span>
                    <span class="font-bold font-mono" x-text="overallProgress + '%'"></span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                    <div
                        class="bg-gradient-to-r from-indigo-500 to-emerald-500 h-2.5 rounded-full transition-all duration-300"
                        :style="'width: ' + overallProgress + '%'"
                    ></div>
                </div>
            </div>

            <!-- Queue Table -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-xs">
                <div class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
                    <template x-for="(item, idx) in items" :key="idx">
                        <div class="p-3.5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs bg-white dark:bg-gray-900 hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="shrink-0 p-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-500">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 dark:text-white truncate" x-text="item.name"></p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[11px] text-gray-400" x-text="formatBytes(item.size)"></span>
                                        <span
                                            class="text-[11px] truncate"
                                            :class="{
                                                'text-emerald-600 dark:text-emerald-400 font-medium': item.status === 'verified',
                                                'text-amber-600 dark:text-amber-400 font-semibold': item.status === 'unmatched',
                                                'text-rose-600 dark:text-rose-400 font-semibold': item.status === 'error',
                                                'text-gray-500 dark:text-gray-400': !['verified', 'unmatched', 'error'].includes(item.status)
                                            }"
                                            x-text="item.message"
                                        ></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Detected Data / Editable Inputs -->
                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-gray-800 px-2 py-1 rounded-md border border-gray-200 dark:border-gray-700">
                                    <span class="text-[10px] uppercase font-bold text-gray-500">GRS:</span>
                                    <input
                                        type="text"
                                        x-model="item.no_grs"
                                        @input="if (item.status === 'unmatched') { item.status = 'scanned'; item.message = 'Nomor GRS disesuaikan manual'; }"
                                        placeholder="500xxxxxxx"
                                        class="w-24 text-xs font-mono font-bold bg-transparent border-none p-0 focus:ring-0 text-indigo-600 dark:text-indigo-400 placeholder-gray-400"
                                        :disabled="item.status === 'verified'"
                                    />
                                </div>
                                <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-gray-800 px-2 py-1 rounded-md border border-gray-200 dark:border-gray-700">
                                    <span class="text-[10px] uppercase font-bold text-gray-500">PO:</span>
                                    <input
                                        type="text"
                                        x-model="item.no_po"
                                        @input="if (item.status === 'unmatched') { item.status = 'scanned'; item.message = 'Nomor PO disesuaikan manual'; }"
                                        placeholder="530xxxxxxx"
                                        class="w-24 text-xs font-mono bg-transparent border-none p-0 focus:ring-0 text-gray-800 dark:text-gray-200 placeholder-gray-400"
                                        :disabled="item.status === 'verified'"
                                    />
                                </div>

                                <!-- Status Badge -->
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold"
                                    :class="{
                                        'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400': item.status === 'queued',
                                        'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 animate-pulse': item.status === 'scanning',
                                        'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300': item.status === 'scanned',
                                        'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 animate-pulse': item.status === 'uploading',
                                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300': item.status === 'verified',
                                        'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300': item.status === 'unmatched',
                                        'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300': item.status === 'error',
                                    }"
                                >
                                    <span x-text="getStatusLabel(item)"></span>
                                </span>

                                <button
                                    type="button"
                                    x-show="item.status !== 'uploading'"
                                    @click="removeItem(idx)"
                                    class="p-1 text-gray-400 hover:text-rose-500 dark:hover:text-rose-400"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-gray-200 dark:border-gray-800">
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="clearAll()"
                        class="px-3.5 py-2 text-xs font-semibold rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 border border-gray-300 dark:border-gray-700 transition"
                        :disabled="isProcessing"
                    >
                        Bersihkan Semua
                    </button>
                    <span class="text-xs text-gray-500" x-show="verifiedCount > 0" x-text="verifiedCount + ' tagihan siap diperbarui'"></span>
                </div>

                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                    <!-- Tombol Coba Ulang yang Belum Cocok -->
                    <button
                        type="button"
                        x-show="unmatchedCount > 0 && !hasPendingUploads"
                        @click="retryUnmatched()"
                        class="px-4 py-2 text-xs font-bold rounded-xl text-amber-800 dark:text-amber-200 bg-amber-100 hover:bg-amber-200 dark:bg-amber-950/80 dark:hover:bg-amber-900 border border-amber-300 dark:border-amber-700 transition flex items-center justify-center gap-1.5"
                        :disabled="isProcessing"
                    >
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Coba Cocokkan Ulang (<span x-text="unmatchedCount"></span>)</span>
                    </button>

                    <button
                        type="button"
                        x-show="hasPendingUploads"
                        @click="startProcessAll()"
                        class="w-full sm:w-auto px-5 py-2.5 text-xs font-bold rounded-xl text-white bg-gradient-to-r from-indigo-600 to-sky-600 hover:from-indigo-700 hover:to-sky-700 shadow-md shadow-indigo-500/20 transition flex items-center justify-center gap-2"
                        :disabled="isProcessing"
                    >
                        <svg x-show="isProcessing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isProcessing ? 'Memproses Berkas...' : 'Mulai Pindai OCR & Unggah Otomatis'"></span>
                    </button>

                    <button
                        type="button"
                        x-show="!hasPendingUploads && items.length > 0"
                        @click="finishAndReload()"
                        class="w-full sm:w-auto px-5 py-2.5 text-xs font-bold rounded-xl text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-500/20 transition flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Selesai & Muat Ulang Tabel</span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
