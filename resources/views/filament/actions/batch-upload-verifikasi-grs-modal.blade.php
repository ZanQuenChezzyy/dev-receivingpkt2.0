@php
    $uploadUrl = route('filament.admin.resources.invoicing-receipts.upload_verifikasi_grs_ocr', absolute: false);
@endphp

<style>
    .batch-ocr-dropzone {
        border-color: #d1d5db;
        background-color: #f9fafb;
    }
    .batch-ocr-dropzone:hover {
        border-color: #9ca3af;
        background-color: #f3f4f6;
    }
    html.dark .batch-ocr-dropzone,
    .dark .batch-ocr-dropzone {
        border-color: rgba(255, 255, 255, 0.12) !important;
        background-color: rgba(255, 255, 255, 0.03) !important;
    }
    html.dark .batch-ocr-dropzone:hover,
    .dark .batch-ocr-dropzone:hover {
        border-color: rgba(255, 255, 255, 0.22) !important;
        background-color: rgba(255, 255, 255, 0.06) !important;
    }
    .batch-ocr-dropzone.is-dragging {
        border-color: #6366f1 !important;
        background-color: rgba(99, 102, 241, 0.08) !important;
    }
    html.dark .batch-ocr-dropzone.is-dragging,
    .dark .batch-ocr-dropzone.is-dragging {
        border-color: #818cf8 !important;
        background-color: rgba(99, 102, 241, 0.15) !important;
    }
</style>

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
            window.__batchOcrScriptPromises = window.__batchOcrScriptPromises || {};
            if (window.__batchOcrScriptPromises[src]) {
                return window.__batchOcrScriptPromises[src];
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
                    delete window.__batchOcrScriptPromises[src];
                    reject(err);
                }, { once: true });

                if (!existing) {
                    s.src = src;
                    s.async = true;
                    document.head.appendChild(s);
                }
            });

            window.__batchOcrScriptPromises[src] = promise;
            return promise;
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
                if (file.size > 10 * 1024 * 1024) {
                    alert('Berkas ' + file.name + ' melebihi batas 10 MB.');
                    continue;
                }

                if (this.items.some(i => i.name === file.name && i.size === file.size)) {
                    continue;
                }

                const isRdtvFilename = /(?:^|[^a-z])(rdtv|124)(?:[^a-z]|$)/i.test(file.name);
                const isGrsFilename = !isRdtvFilename && /(?:^|[^a-z])(grs|105)(?:[^a-z]|$)/i.test(file.name);
                const fnDocMatch = file.name.match(/(?:grs|rdtv|105|124)[^0-9]*(500\d{7})/i) || file.name.match(/(?:^|[^0-9])(500\d{7})(?:[^0-9]|$)/);
                const fnPoMatch = file.name.match(/(?:po)[^0-9]*([345]\d{9})/i) || file.name.match(/(?:^|[^0-9])(53\d{8})(?:[^0-9]|$)/);

                this.items.push({
                    file: file,
                    name: file.name,
                    size: file.size,
                    status: 'queued',
                    doc_type: isRdtvFilename ? 'RDTV' : (isGrsFilename ? 'GRS' : ''),
                    no_grs: (!isRdtvFilename && fnDocMatch) ? fnDocMatch[1] : '',
                    no_rdtv: (isRdtvFilename && fnDocMatch) ? fnDocMatch[1] : '',
                    no_po: fnPoMatch ? fnPoMatch[1] : '',
                    no_do: '',
                    source: fnDocMatch ? 'filename' : 'pending',
                    message: fnDocMatch ? (isRdtvFilename ? 'Nomor RDTV ditemukan' : 'Nomor GRS ditemukan') : 'Menunggu proses',
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
                if (item.status === 'unmatched' || item.status === 'error') {
                    if (!item.no_grs && !item.no_rdtv && !item.no_po) {
                        item.status = 'queued';
                        item.message = 'Mengantre untuk pembacaan ulang...';
                    } else {
                        item.status = 'scanned';
                        item.message = 'Mengantre untuk pencocokan ulang...';
                    }
                }
            });
            this.startProcessAll();
        },

        toggleDocType(item) {
            if (item.status === 'verified' || this.isProcessing) return;
            if (item.doc_type === 'RDTV') {
                item.doc_type = 'GRS';
                if (!item.no_grs && item.no_rdtv) {
                    item.no_grs = item.no_rdtv;
                    item.no_rdtv = '';
                }
            } else {
                item.doc_type = 'RDTV';
                if (!item.no_rdtv && item.no_grs) {
                    item.no_rdtv = item.no_grs;
                    item.no_grs = '';
                }
            }
            if (item.status === 'unmatched') {
                item.status = 'scanned';
                item.message = 'Jenis dokumen diubah ke ' + item.doc_type;
            }
        },

        getStatusLabel(item) {
            switch (item.status) {
                case 'queued': return 'Antrean';
                case 'scanning': return 'Membaca...';
                case 'scanned': return 'Siap Upload';
                case 'uploading': return 'Mengunggah...';
                case 'verified': return 'Terverifikasi';
                case 'unmatched': return 'Belum Cocok';
                case 'error': return 'Gagal';
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
            let cleaned = raw
                .replace(/\r?\n/g, ' ')
                .replace(/\(\s*[1Il][0Oo][5S]\s*\)/gi, '(105)')
                .replace(/\(\s*[1Il][0Oo]3\s*\)/gi, '(103)')
                .replace(/\(\s*[1Il][2Z][4A]\s*\)/gi, '(124)');

            // Ulangi 2x agar pola seperti 5OO77 -> 50077 ikut ternormalisasi
            cleaned = cleaned
                .replace(/([0-9])[Oo]([0-9])/g, '$10$2')
                .replace(/([0-9])[Oo]([0-9])/g, '$10$2')
                .replace(/\b[5S]([0Oo])([0Oo])(\d{7})\b/gi, '500$3')
                .replace(/\b[5S]3(\d{8})\b/gi, '53$1')
                .replace(/\b(500)\s+(\d{3,4})\s+(\d{3,4})\b/g, '$1$2$3')
                .replace(/\b([45]\d{2,3})\s+(\d{3})\s+(\d{3,4})\b/g, '$1$2$3');

            return cleaned;
        },

        extractIdentifiers(rawText) {
            const text = this.cleanOcrText(rawText);
            let docType = null;
            let grs = null;
            let rdtv = null;
            let gr103 = null;
            let po = null;
            let doNum = null;

            if (text && text.length > 15) {
                // 0a. Deteksi jenis dokumen: RDTV (Verifikasi Penolakan Barang / 124) vs GRS (Verifikasi Penerimaan Barang / 105)
                const isRdtv = /VERIFIKASI\s+PENOLAKAN\s+BARANG|Return\s+Delivery\s+To\s+Vendor|Berita\s+Acara\s+Penolakan|Nomor\s*:\s*RDTV-|Nomor\s*RDTV\s*\(?124\)?|RDTV\s*\(?124\)?|Alasan\s+Pengembalian|BELUM\s+DITERIMA/i.test(text);
                const isGrs = /VERIFIKASI\s+PENERIMAAN\s+BARANG|Goods\s+Receipt\s+Slip|Berita\s+Acara\s+Penerimaan|Nomor\s*:\s*GRS-|Nomor\s*GRS\s*\(?105\)?|GRS\s*\(?105\)?/i.test(text);

                if (isRdtv) {
                    docType = 'RDTV';
                } else if (isGrs) {
                    docType = 'GRS';
                }

                // 0b. Pola kolom berurutan bila OCR membaca per kolom
                if (docType === 'RDTV') {
                    const mSeqRdtv = text.match(/Nomor\s*PO[\s\S]{0,120}?Nomor\s*GR[\s\S]{0,80}?Nomor\s*RDTV[\s\S]{0,160}?([345]\d{9})[\s\S]{1,40}?(500\d{7})[\s\S]{1,40}?(500\d{7})/i);
                    if (mSeqRdtv) {
                        po = mSeqRdtv[1];
                        gr103 = mSeqRdtv[2];
                        rdtv = mSeqRdtv[3];
                    }
                } else {
                    const mSeqGrs = text.match(/Nomor\s*PO[\s\S]{0,120}?Nomor\s*GR[\s\S]{0,80}?Nomor\s*GRS[\s\S]{0,160}?([345]\d{9})[\s\S]{1,40}?(500\d{7})[\s\S]{1,40}?(500\d{7})/i);
                    if (mSeqGrs) {
                        po = mSeqGrs[1];
                        gr103 = mSeqGrs[2];
                        grs = mSeqGrs[3];
                        docType = docType || 'GRS';
                    }
                }

                // 1. Deteksi GR (103) terlebih dahulu agar tidak tertukar dengan GRS (105) / RDTV (124)
                if (!gr103) {
                    const mGr103 = text.match(/(?:Nomor\s*GR\s*\(?103\)?|GR\s*\(?103\)?|\(103\))[\s:;|—–-]{0,25}(500\d{7})/i);
                    if (mGr103) gr103 = mGr103[1];
                }

                // 2. Deteksi nomor dokumen utama (RDTV 124 atau GRS 105)
                if (docType === 'RDTV' || !docType) {
                    const mRdtvExplicit = text.match(/(?:Nomor\s*RDTV\s*\(?124\)?|RDTV\s*\(?124\)?|\(124\))[\s:;|—–-]{0,25}(500\d{7})/i)
                        || text.match(/(?:Nomor\s*RDTV|No\.?\s*RDTV)[\s:;|—–-]{0,25}(500\d{7})/i);
                    if (mRdtvExplicit) {
                        rdtv = mRdtvExplicit[1];
                        docType = 'RDTV';
                    }
                }

                if (docType !== 'RDTV' && !grs) {
                    const mGrsExplicit = text.match(/(?:Nomor\s*GRS\s*\(?105\)?|GRS\s*\(?105\)?|\(105\)|No\.?\s*Release\s*GR)[\s:;|—–-]{0,25}(500\d{7})/i)
                        || text.match(/(?:Nomor\s*GRS|No\.?\s*GRS)[\s:;|—–-]{0,25}(500\d{7})/i);
                    if (mGrsExplicit) {
                        grs = mGrsExplicit[1];
                        docType = docType || 'GRS';
                    }
                }

                // 3. Fallback angka 500xxxxxxx jika label terpotong OCR
                if (!grs && !rdtv) {
                    const all500 = [...new Set(Array.from(text.matchAll(/\b(500\d{7})\b/g), m => m[1]))];
                    const without103 = gr103 ? all500.filter(n => n !== gr103) : all500;
                    let picked = null;

                    if (without103.length >= 2 && /103/i.test(text) && /(?:105|124)/i.test(text)) {
                        picked = without103[1];
                    } else if (without103.length >= 1) {
                        picked = without103[0];
                    } else if (all500.length >= 1) {
                        picked = all500[all500.length - 1];
                    }

                    if (picked) {
                        if (docType === 'RDTV') {
                            rdtv = picked;
                        } else {
                            grs = picked;
                            docType = docType || 'GRS';
                        }
                    }
                }

                const activeDocNum = rdtv || grs;

                // 4. Deteksi Nomor PO
                if (!po) {
                    const mPo = text.match(/(?:Nomor\s*PO|No\.?\s*PO|Purchase\s*Order|P\.O\.?)[\s:;|—–-]{0,25}([345]\d{9})/i)
                        || text.match(/\b(53\d{8})\b/);
                    if (mPo && mPo[1] !== activeDocNum && mPo[1] !== gr103) {
                        po = mPo[1];
                    } else {
                        const allPoCandidates = [...new Set(Array.from(text.matchAll(/\b([345]\d{9})\b/g), m => m[1]))]
                            .filter(n => n !== activeDocNum && n !== gr103);
                        if (allPoCandidates.length > 0) {
                            po = allPoCandidates[0];
                        }
                    }
                }

                // 5. Deteksi Nomor DO/SJ
                const mDo = text.match(/Nomor\s*DO(?:\/S[J\]])?[\s:;|—–-]{0,20}([A-Za-z0-9\/-]{4,30})/i)
                    || text.match(/No\.?\s*Surat\s*Jalan[\s:;|—–-]{0,20}([A-Za-z0-9\/-]{4,30})/i);
                if (mDo && mDo[1] !== '-' && mDo[1].toLowerCase() !== 'status' && mDo[1] !== po) {
                    doNum = mDo[1];
                }
            }

            return { docType, grs, rdtv, po, doNum };
        },

        async getTesseractWorker() {
            await this.ensureLibraries();
            if (!this.tesseractWorker && typeof window.Tesseract !== 'undefined') {
                this.currentStepMessage = 'Menyiapkan mesin pembaca dokumen (OCR)...';
                this.tesseractWorker = await window.Tesseract.createWorker('eng', 1, {
                    workerPath: 'https://cdn.jsdelivr.net/npm/tesseract.js' + '@' + '5/dist/worker.min.js',
                    corePath: 'https://cdn.jsdelivr.net/npm/tesseract.js-core' + '@' + '5',
                    langPath: 'https://tessdata.projectnaptha.com/4.0.0_fast',
                });
            }
            return this.tesseractWorker;
        },

        async renderPdfPageToCanvas(page, scale = 2.2) {
            const viewport = page.getViewport({ scale });
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d', { willReadFrequently: true });
            canvas.width = Math.ceil(viewport.width);
            canvas.height = Math.ceil(viewport.height);

            // Isi latar putih solid agar OCR jauh lebih akurat pada PDF dengan background transparan
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            await page.render({
                canvasContext: ctx,
                viewport: viewport,
            }).promise;

            return canvas;
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

        async scanFileOcr(item) {
            item.status = 'scanning';
            this.currentStepMessage = 'Membaca dokumen: ' + item.name + '...';

            try {
                await this.ensureLibraries();
                const arrayBuffer = await item.file.arrayBuffer();
                const pdf = await window.pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                const page = await pdf.getPage(1);

                // 1. Cek layer teks digital bawaan
                const textContent = await page.getTextContent();
                const digitalText = textContent.items.map(t => t.str).join(' ');
                let detected = this.extractIdentifiers(digitalText);

                if (detected.grs || detected.rdtv || detected.po) {
                    item.source = 'digital_text';
                }

                // 2. Jalankan OCR jika PDF berupa gambar / teks digital belum menemukan GRS (105) atau RDTV (124)
                if ((!detected.grs && !detected.rdtv) || !detected.po || !detected.docType) {
                    this.currentStepMessage = 'Memindai teks gambar (OCR): ' + item.name + '...';
                    const worker = await this.getTesseractWorker();

                    if (worker) {
                        const fullCanvas = await this.renderPdfPageToCanvas(page, 2.2);

                        // Tahap 2a: Pindai area judul & tabel rincian dokumen (12% - 56% halaman)
                        const metaCanvas = this.cropCanvasRegion(fullCanvas, 0.12, 0.56);
                        const metaRes = await worker.recognize(metaCanvas);
                        const metaDetected = this.extractIdentifiers(metaRes?.data?.text || '');

                        if (!detected.docType && metaDetected.docType) detected.docType = metaDetected.docType;
                        if (!detected.grs && metaDetected.grs) detected.grs = metaDetected.grs;
                        if (!detected.rdtv && metaDetected.rdtv) detected.rdtv = metaDetected.rdtv;
                        if (!detected.po && metaDetected.po) detected.po = metaDetected.po;
                        if (!detected.doNum && metaDetected.doNum) detected.doNum = metaDetected.doNum;

                        // Tahap 2b: Fallback pindai seluruh halaman jika nomor utama atau PO belum ditemukan
                        if ((!detected.grs && !detected.rdtv) || !detected.po) {
                            const fullRes = await worker.recognize(fullCanvas);
                            const fullDetected = this.extractIdentifiers(fullRes?.data?.text || '');

                            if (!detected.docType && fullDetected.docType) detected.docType = fullDetected.docType;
                            if (!detected.grs && fullDetected.grs) detected.grs = fullDetected.grs;
                            if (!detected.rdtv && fullDetected.rdtv) detected.rdtv = fullDetected.rdtv;
                            if (!detected.po && fullDetected.po) detected.po = fullDetected.po;
                            if (!detected.doNum && fullDetected.doNum) detected.doNum = fullDetected.doNum;
                        }

                        if (detected.grs || detected.rdtv || detected.po) {
                            item.source = 'ocr_image';
                        }
                    }
                }

                if (detected.docType) item.doc_type = detected.docType;
                if (item.doc_type === 'RDTV') {
                    if (detected.rdtv) item.no_rdtv = detected.rdtv;
                    item.no_grs = '';
                } else {
                    if (detected.grs) item.no_grs = detected.grs;
                    if (detected.rdtv && !detected.grs) {
                        item.doc_type = 'RDTV';
                        item.no_rdtv = detected.rdtv;
                    }
                }
                if (detected.po) item.no_po = detected.po;
                if (detected.doNum) item.no_do = detected.doNum;

                item.status = 'scanned';
                if (item.doc_type === 'RDTV') {
                    item.message = item.no_rdtv
                        ? 'RDTV: ' + item.no_rdtv + (item.no_po ? ' | PO: ' + item.no_po : '')
                        : (item.no_po ? 'RDTV | PO: ' + item.no_po : 'Nomor RDTV belum ditemukan');
                } else {
                    item.message = item.no_grs
                        ? 'GRS: ' + item.no_grs + (item.no_po ? ' | PO: ' + item.no_po : '')
                        : (item.no_po ? 'PO: ' + item.no_po : 'Nomor belum ditemukan');
                }
            } catch (err) {
                console.error('Scan error:', err);
                item.status = 'scanned';
                item.message = 'Gagal memindai OCR otomatis — melanjutkan pengecekan server';
            }
        },

        async uploadItem(item) {
            item.status = 'uploading';
            this.currentStepMessage = 'Mengunggah & memverifikasi: ' + item.name + '...';

            const formData = new FormData();
            formData.append('file', item.file);
            formData.append('jenis', item.doc_type || '');
            formData.append('no_grs', item.no_grs || '');
            formData.append('no_rdtv', item.no_rdtv || '');
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
                if (data.jenis) item.doc_type = data.jenis;
                if (res.ok && data.success && data.matched) {
                    item.status = 'verified';
                    item.message = data.message || 'Berhasil diverifikasi';
                    if (data.no_grs) item.no_grs = data.no_grs;
                    if (data.no_rdtv) item.no_rdtv = data.no_rdtv;
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
            this.currentStepMessage = 'Selesai diproses.';
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
    <!-- Dropzone Area -->
    <div
        class="batch-ocr-dropzone relative rounded-2xl border-2 border-dashed p-6 text-center transition-all duration-200 cursor-pointer"
        :class="isDragging ? 'is-dragging' : ''"
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
            <div class="p-3 bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-gray-300 rounded-xl">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
            </div>
            <div class="space-y-0.5 text-center">
                <p class="text-sm font-medium text-gray-900 dark:text-white">
                    Pilih atau seret berkas PDF ke sini
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Format PDF hingga 10 MB per berkas
                </p>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <template x-if="items.length > 0">
        <div class="space-y-4">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="p-3 rounded-xl bg-gray-100/80 dark:bg-white/5 border border-gray-200 dark:border-white/10">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Total Berkas</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white" x-text="items.length"></div>
                </div>
                <div class="p-3 rounded-xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900/50">
                    <div class="text-xs text-blue-600 dark:text-blue-400">Diproses</div>
                    <div class="text-lg font-bold text-blue-700 dark:text-blue-300" x-text="scannedCount"></div>
                </div>
                <div class="p-3 rounded-xl bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/50">
                    <div class="text-xs text-emerald-600 dark:text-emerald-400">Terverifikasi</div>
                    <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300" x-text="verifiedCount"></div>
                </div>
                <div class="p-3 rounded-xl bg-amber-50/80 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/50">
                    <div class="text-xs text-amber-600 dark:text-amber-400">Belum Cocok</div>
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
            <div class="rounded-xl border border-gray-200 dark:border-white/10 overflow-hidden shadow-xs">
                <div class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-white/10">
                    <template x-for="(item, idx) in items" :key="idx">
                        <div class="p-3.5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs bg-white dark:bg-white/[0.02] hover:bg-gray-50/80 dark:hover:bg-white/[0.06] transition">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="shrink-0 p-2 rounded-lg bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-gray-300">
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
                                <!-- Badge Jenis Dokumen (GRS / RDTV) — bisa diklik untuk koreksi manual -->
                                <button
                                    type="button"
                                    @click="toggleDocType(item)"
                                    :disabled="item.status === 'verified' || isProcessing"
                                    title="Klik untuk mengganti jenis dokumen (GRS / RDTV)"
                                    class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wide border transition cursor-pointer disabled:cursor-default"
                                    :class="{
                                        'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800': item.doc_type === 'GRS',
                                        'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800': item.doc_type === 'RDTV',
                                        'bg-gray-100 text-gray-500 border-gray-200 dark:bg-white/5 dark:text-gray-400 dark:border-white/10': !item.doc_type,
                                    }"
                                    x-text="item.doc_type || 'Auto'"
                                ></button>

                                <template x-if="item.doc_type === 'RDTV'">
                                    <div class="flex items-center gap-1.5 bg-rose-50/70 dark:bg-rose-950/30 px-2 py-1 rounded-md border border-rose-200 dark:border-rose-800/60">
                                        <span class="text-[10px] uppercase font-bold text-rose-600 dark:text-rose-400">RDTV:</span>
                                        <input
                                            type="text"
                                            x-model="item.no_rdtv"
                                            @input="if (item.status === 'unmatched') { item.status = 'scanned'; item.message = 'Nomor RDTV disesuaikan manual'; }"
                                            placeholder="500xxxxxxx"
                                            class="w-24 text-xs font-mono font-bold bg-transparent border-none p-0 focus:ring-0 text-rose-700 dark:text-rose-300 placeholder-rose-300"
                                            :disabled="item.status === 'verified'"
                                        />
                                    </div>
                                </template>

                                <template x-if="item.doc_type !== 'RDTV'">
                                    <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-white/5 px-2 py-1 rounded-md border border-gray-200 dark:border-white/10">
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
                                </template>
                                <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-white/5 px-2 py-1 rounded-md border border-gray-200 dark:border-white/10">
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
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold"
                                    :class="{
                                        'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400': item.status === 'queued',
                                        'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300': item.status === 'scanning',
                                        'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300': item.status === 'scanned',
                                        'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300': item.status === 'uploading',
                                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300': item.status === 'verified',
                                        'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300': item.status === 'unmatched',
                                        'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300': item.status === 'error',
                                    }"
                                >
                                    <span
                                        x-show="item.status === 'scanning' || item.status === 'uploading'"
                                        class="uiverse-loader-dots scale-75"
                                        aria-hidden="true"
                                    >
                                        <span></span><span></span><span></span>
                                    </span>
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
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-gray-200 dark:border-white/10">
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="clearAll()"
                        class="px-3.5 py-2 text-xs font-semibold rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 border border-gray-300 dark:border-white/15 transition"
                        :disabled="isProcessing"
                    >
                        Bersihkan Semua
                    </button>
                    <span class="text-xs text-gray-500" x-show="verifiedCount > 0" x-text="verifiedCount + ' berkas berhasil diverifikasi'"></span>
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
                        <span>Coba Ulang (<span x-text="unmatchedCount"></span>)</span>
                    </button>

                    <button
                        type="button"
                        x-show="hasPendingUploads"
                        @click="startProcessAll()"
                        class="w-full sm:w-auto px-5 py-2.5 text-xs font-bold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition flex items-center justify-center gap-2"
                        :disabled="isProcessing"
                    >
                        <span x-show="isProcessing" class="uiverse-loader-dots" aria-hidden="true">
                            <span></span><span></span><span></span>
                        </span>
                        <span x-text="isProcessing ? 'Memproses' : 'Mulai Upload'"></span>
                    </button>

                    <button
                        type="button"
                        x-show="!hasPendingUploads && items.length > 0"
                        @click="finishAndReload()"
                        class="w-full sm:w-auto px-5 py-2.5 text-xs font-bold rounded-xl text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Selesai</span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
