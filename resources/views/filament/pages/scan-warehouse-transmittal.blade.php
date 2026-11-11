<x-filament-panels::page>
    <div x-data="{
        isCameraOpen: false,
        html5QrCode: null,
        itemToDelete: null,
        lastScanned: null,
        lastScanTime: 0,
        isProcessing: false,
        playSuccess() {
            try {
                let ctx = new (window.AudioContext || window.webkitAudioContext)();
                let osc = ctx.createOscillator();
                let gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.frequency.setValueAtTime(880, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(1320, ctx.currentTime + 0.12);
                gain.gain.setValueAtTime(0.25, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.12);
                osc.start();
                osc.stop(ctx.currentTime + 0.12);
            } catch(e) {
                let audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
                audio.play().catch(err => console.log('Audio error:', err));
            }
        },
        playError() {
            try {
                let ctx = new (window.AudioContext || window.webkitAudioContext)();
                let osc = ctx.createOscillator();
                let gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(320, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(180, ctx.currentTime + 0.2);
                gain.gain.setValueAtTime(0.3, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
                osc.start();
                osc.stop(ctx.currentTime + 0.2);
            } catch(e) {
                let audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2955/2955-preview.mp3');
                audio.play().catch(err => console.log('Audio error:', err));
            }
        },
        initScanner() {
            this.loadScript(() => {
                this.startCamera();
            });
        },
        loadScript(callback) {
            if (window.Html5Qrcode) {
                callback();
                return;
            }
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
            script.onload = callback;
            document.head.appendChild(script);
        },
        startCamera() {
            if (!window.Html5Qrcode) {
                this.loadScript(() => this.startCamera());
                return;
            }

            this.isCameraOpen = true;
            this.$nextTick(() => {
                if (this.html5QrCode) {
                    this.html5QrCode.stop().then(() => {
                        this.initHtml5Qr();
                    }).catch(() => {
                        this.initHtml5Qr();
                    });
                } else {
                    this.initHtml5Qr();
                }
            });
        },
        initHtml5Qr() {
            const qrBoxFunction = function(viewfinderWidth, viewfinderHeight) {
                // Persegi 1:1 khusus QR Code
                const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                const edge = Math.min(Math.max(Math.floor(minEdge * 0.72), 180), 280);
                return {
                    width: edge,
                    height: edge
                };
            };

            this.html5QrCode = new Html5Qrcode('qr-reader');
            const config = {
                fps: 15,
                qrbox: qrBoxFunction,
                aspectRatio: 1.0,
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                }
            };

            this.html5QrCode.start(
                { facingMode: 'environment' },
                config,
                (decodedText, decodedResult) => {
                    const now = Date.now();
                    // Debounce duplicate scans within 2.5 seconds
                    if (this.lastScanned === decodedText && (now - this.lastScanTime) < 2500) {
                        return;
                    }
                    this.lastScanned = decodedText;
                    this.lastScanTime = now;
                    this.isProcessing = true;

                    $wire.processScan(decodedText).then(() => {
                        this.isProcessing = false;
                    });
                },
                (errorMessage) => {
                    // Continuous search frame - ignore frame read misses
                }
            ).catch(err => {
                console.error('Kamera gagal dimulai:', err);
                this.isCameraOpen = false;
            });
        },
        stopCamera() {
            if (this.html5QrCode) {
                this.html5QrCode.stop().then(() => {
                    this.isCameraOpen = false;
                }).catch(err => {
                    console.error('Error stopping camera:', err);
                    this.isCameraOpen = false;
                });
            } else {
                this.isCameraOpen = false;
            }
        }
    }"
    @play-success-sound.window="playSuccess()"
    @play-error-sound.window="playError()"
    @focus-scanner-input.window="setTimeout(() => { document.getElementById('manual-barcode-input')?.focus(); }, 100)"
    class="space-y-6">

        <!-- Header Card: Destination & Transmittal Status -->
        <div class="fi-wi-widget p-6 rounded-2xl bg-white/70 dark:bg-gray-900/60 backdrop-blur-xl border border-gray-200/60 dark:border-gray-800/60 shadow-xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <!-- Form Inputs -->
                <div class="flex-1">
                    <form wire:submit.prevent>
                        {{ $this->form }}
                    </form>
                </div>

                <!-- Transmittal Badge & Control -->
                <div class="lg:w-96 flex flex-col justify-center items-start lg:items-end gap-2 border-t lg:border-t-0 lg:border-l border-gray-200 dark:border-gray-800 pt-4 lg:pt-0 lg:pl-6">
                    <div class="flex items-center justify-between w-full lg:justify-end gap-2">
                        <span class="text-xs font-semibold tracking-wider text-gray-500 dark:text-gray-400 uppercase">
                            Nomor Transmittal
                        </span>
                        @if($this->availableTransmittals->count() > 1)
                            <span class="text-[10px] font-bold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/50 px-2 py-0.5 rounded-full border border-primary-200 dark:border-primary-800">
                                {{ $this->availableTransmittals->count() }} Transmittal Hari Ini
                            </span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-base font-bold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/40 px-3 py-1.5 rounded-lg border border-primary-200 dark:border-primary-800/50">
                            {{ $transmittal_no }}
                        </span>
                        @if($transmittalId && ! $forceNewTransmittal)
                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 px-2.5 py-1 rounded-md border border-emerald-300 dark:border-emerald-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/50 px-2.5 py-1 rounded-md border border-amber-300 dark:border-amber-800">
                                Standby
                            </span>
                        @endif
                    </div>

                    @if($this->availableTransmittals->count() > 1)
                        <!-- Multi-Transmittal Dropdown Selector -->
                        <div class="w-full flex items-center justify-start lg:justify-end gap-2 pt-1.5 border-t border-gray-100 dark:border-gray-800/80">
                            <label class="text-[11px] font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Beralih:</label>
                            <select
                                wire:change="selectTransmittal($event.target.value)"
                                class="text-xs font-mono font-medium py-1 px-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:ring-1 focus:ring-primary-500 focus:border-primary-500">
                                @if($forceNewTransmittal)
                                    <option value="" selected disabled>+ Baru: {{ $transmittal_no }} (Standby)</option>
                                @endif
                                @foreach($this->availableTransmittals as $at)
                                    <option value="{{ $at->id }}" @selected($at->id === $transmittalId && ! $forceNewTransmittal)>
                                        {{ $at->transmittal_no }} ({{ $at->items_count }} item) {{ $at->id === $transmittalId && ! $forceNewTransmittal ? '✓ Aktif' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="flex items-center gap-2 mt-1">
                        <button
                            type="button"
                            wire:click="startNewTransmittal"
                            class="text-xs text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 font-medium underline flex items-center gap-1 transition-colors">
                            <x-heroicon-m-plus-circle class="w-4 h-4" />
                            Buat Transmittal Baru
                        </button>

                        @if($transmittalId && count($this->scannedItems) > 0)
                            <a
                                href="{{ route('filament.admin.resources.warehouse-transmittals.print', ['transmittal' => $transmittal_no]) }}"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition-all hover:scale-[1.02]">
                                <x-heroicon-m-printer class="w-4 h-4" />
                                Cetak PDF
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Scanner Card Area -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left: Continuous Camera Scanner (7 cols) -->
            <div class="lg:col-span-7 fi-wi-widget p-6 rounded-2xl bg-white/70 dark:bg-gray-900/60 backdrop-blur-xl border border-gray-200/60 dark:border-gray-800/60 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2 bg-primary-100 dark:bg-primary-950/60 text-primary-600 dark:text-primary-400 rounded-xl">
                                <x-heroicon-o-camera class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                    Pemindai Kamera Continuous
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Arahkan kamera ke QR Code GRS kertas tanpa klik berulang
                                </p>
                            </div>
                        </div>

                        <!-- Camera Toggle Button -->
                        <div>
                            <button
                                type="button"
                                x-show="!isCameraOpen"
                                x-on:click="startCamera()"
                                class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-xl shadow-xs transition-transform active:scale-95">
                                <x-heroicon-m-video-camera class="w-4 h-4" />
                                Buka Kamera
                            </button>

                            <button
                                type="button"
                                x-show="isCameraOpen"
                                style="display: none;"
                                x-on:click="stopCamera()"
                                class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-xs transition-transform active:scale-95">
                                <x-heroicon-m-video-camera-slash class="w-4 h-4" />
                                Tutup Kamera
                            </button>
                        </div>
                    </div>

                    <!-- Camera Viewport Container -->
                    <div class="relative w-full overflow-hidden rounded-xl bg-gray-950 min-h-[260px] flex items-center justify-center border border-gray-800">
                        <!-- Placeholder when camera closed -->
                        <div x-show="!isCameraOpen" class="flex flex-col items-center justify-center p-8 text-center text-gray-400">
                            <div class="w-14 h-14 rounded-full bg-gray-900 flex items-center justify-center mb-3 text-gray-500">
                                <x-heroicon-o-qr-code class="w-8 h-8" />
                            </div>
                            <p class="text-sm font-medium text-gray-300">Kamera Sedang Tidak Aktif</p>
                            <p class="text-xs text-gray-500 mt-1 max-w-xs">
                                Klik tombol <strong>Buka Kamera</strong> untuk mengarahkan kamera HP langsung ke lembar GRS Anda.
                            </p>
                        </div>

                        <!-- Video Container -->
                        <div x-show="isCameraOpen" style="display: none;" class="w-full h-full relative">
                            <div id="qr-reader" class="w-full"></div>

                            <!-- Processing Overlay -->
                            <div x-show="isProcessing" style="display: none;" class="absolute inset-0 bg-primary-900/40 backdrop-blur-xs flex items-center justify-center z-20">
                                <span class="inline-flex items-center gap-2 px-4 py-2 bg-black/80 text-white rounded-full text-xs font-bold shadow-lg">
                                    <svg class="animate-spin h-4 w-4 text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    Memproses Barcode...
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800/80 flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full" :class="isCameraOpen ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400'"></span>
                        <span x-text="isCameraOpen ? 'Kamera Aktif: Format QR Code 1:1' : 'Siap digunakan kapan saja'"></span>
                    </span>
                    <span>Continuous Auto-detect</span>
                </div>
            </div>

            <!-- Right: Laser Scanner Gun / Manual Input (5 cols) -->
            <div class="lg:col-span-5 fi-wi-widget p-6 rounded-2xl bg-white/70 dark:bg-gray-900/60 backdrop-blur-xl border border-gray-200/60 dark:border-gray-800/60 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2.5 mb-4">
                        <div class="p-2 bg-orange-100 dark:bg-orange-950/60 text-orange-600 dark:text-orange-400 rounded-xl">
                            <x-heroicon-o-viewfinder-circle class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                Scanner Tembak (USB/Bluetooth) / Input Manual
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Cocok untuk handheld terminal atau scanner gun meja
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="manual-barcode-input" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                Arahkan Scanner Tembak atau Ketik Kode 105:
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                    <x-heroicon-o-bars-3-bottom-left class="w-5 h-5" />
                                </div>
                                <input
                                    type="text"
                                    id="manual-barcode-input"
                                    wire:model="manual_input"
                                    wire:keydown.enter="submitManualScan"
                                    placeholder="Contoh: 50074772742026 atau 5007477274..."
                                    class="w-full pl-10 pr-24 py-2.5 text-sm font-mono font-medium rounded-xl border border-gray-300 dark:border-gray-700 bg-white/80 dark:bg-gray-800/80 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all uppercase placeholder:normal-case placeholder:text-gray-400"
                                    autofocus
                                />
                                <div class="absolute inset-y-1 right-1 flex items-center">
                                    <button
                                        type="button"
                                        wire:click="submitManualScan"
                                        class="px-3 py-1.5 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-lg shadow-xs transition-colors">
                                        Scan / OK
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Guide Banner -->
                        <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-200/70 dark:border-gray-700/60 text-xs space-y-1.5">
                            <div class="font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                                <x-heroicon-m-information-circle class="w-4 h-4 text-primary-500" />
                                Informasi Format Barcode GRS
                            </div>
                            <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 space-y-1 pl-1 text-[11px]">
                                <li>Barcode 1D pada kertas GRS biasanya berformat 14 digit: <code class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-primary-600 dark:text-primary-400 font-mono">50074772742026</code>.</li>
                                <li>Sistem secara otomatis mengekstrak 10 digit pertama (<code class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-primary-600 dark:text-primary-400 font-mono">5007477274</code>).</li>
                                <li>Jika satu nomor 105 berisi lebih dari 1 item, seluruh item akan <strong>masuk otomatis sekaligus</strong>.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800/80 text-[11px] text-gray-500 dark:text-gray-400 flex items-center justify-between">
                    <span>Tekan <kbd class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-gray-700 dark:text-gray-300 font-mono text-[10px]">Enter</kbd> untuk memproses</span>
                    <button
                        type="button"
                        x-on:click="document.getElementById('manual-barcode-input')?.focus()"
                        class="text-primary-600 dark:text-primary-400 hover:underline">
                        Fokus Input
                    </button>
                </div>
            </div>
        </div>

        <!-- Table: Scanned Items in Current Transmittal -->
        <div class="fi-wi-widget rounded-2xl bg-white/70 dark:bg-gray-900/60 backdrop-blur-xl border border-gray-200/60 dark:border-gray-800/60 shadow-xs overflow-hidden">
            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200/60 dark:border-gray-800/60 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 rounded-xl">
                        <x-heroicon-o-clipboard-document-check class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            Daftar Material Transmittal Ini
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Transmittal No: <span class="font-mono font-bold text-gray-700 dark:text-gray-300">{{ $transmittal_no }}</span>
                            @if($this->activeTransmittal?->destination)
                                &bull; Tujuan: <span class="font-semibold text-primary-600 dark:text-primary-400">{{ $this->activeTransmittal->destination->name }}</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-primary-50 dark:bg-primary-950/40 text-primary-700 dark:text-primary-300 border border-primary-200 dark:border-primary-800/50">
                        Total Material: {{ count($this->scannedItems) }} Item
                    </span>

                    @if($transmittalId && count($this->scannedItems) > 0)
                        <a
                            href="{{ route('filament.admin.resources.warehouse-transmittals.print', ['transmittal' => $transmittal_no]) }}"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition-all hover:scale-[1.02]">
                            <x-heroicon-m-printer class="w-4 h-4" />
                            Cetak Transmittal PDF
                        </a>
                    @endif
                </div>
            </div>

            <!-- Table Body -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="bg-gray-50/80 dark:bg-gray-800/50 border-b border-gray-200/60 dark:border-gray-800/60 text-gray-600 dark:text-gray-300 uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="px-5 py-3.5 text-center w-12">No.</th>
                            <th class="px-5 py-3.5">MatDoc 105 (GRS)</th>
                            <th class="px-5 py-3.5">Purchase Order</th>
                            <th class="px-5 py-3.5 text-center">Item</th>
                            <th class="px-5 py-3.5">Kode Material & Deskripsi</th>
                            <th class="px-5 py-3.5 text-right">Quantity</th>
                            <th class="px-5 py-3.5">Hubungan Penerimaan DO</th>
                            <th class="px-5 py-3.5 text-center">Waktu Scan</th>
                            <th class="px-5 py-3.5 text-center w-16">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200/50 dark:divide-gray-800/50">
                        @forelse ($this->scannedItems as $index => $item)
                            @php
                                $batchItem = $item->batchItem;
                                $detail = $item->detail;
                                $poNo = $batchItem?->po_number ?? ($detail?->purchaseOrderIssued?->purchase_order_no ?? '-');
                                $itemNo = $batchItem?->item_no ?? ($detail?->item_no ?? '-');
                                $matDoc105 = $batchItem?->mat_doc_105 ?? '-';
                                $matCode = $batchItem?->material_code ?? ($detail?->material_code ?? '-');
                                $description = $batchItem?->description ?? ($detail?->description ?? '-');
                                $qty = $batchItem?->quantity ?? ($detail?->quantity ?? 0);
                                $uom = $batchItem?->uom ?? ($detail?->uoi ?? '');
                                $do = $batchItem?->deliveryOrderReceipt ?? ($detail?->deliveryOrderReceipt ?? null);
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="px-5 py-3.5 text-center font-bold text-gray-500">
                                    {{ $index + 1 }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 font-mono font-bold text-primary-600 dark:text-primary-400 bg-primary-50/70 dark:bg-primary-950/40 px-2.5 py-1 rounded-md border border-primary-200/60 dark:border-primary-800/40">
                                        <x-heroicon-m-document-text class="w-3.5 h-3.5 text-primary-500" />
                                        {{ $matDoc105 }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="font-bold text-gray-900 dark:text-white font-mono">
                                        {{ $poNo }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center font-mono text-gray-700 dark:text-gray-300">
                                    {{ $itemNo }}
                                </td>
                                <td class="px-5 py-3.5 max-w-sm">
                                    <div class="truncate">
                                        <span class="font-mono font-semibold text-gray-800 dark:text-gray-200">{{ $matCode }}</span>
                                        <span class="text-gray-400 mx-1">&bull;</span>
                                        <span class="text-gray-600 dark:text-gray-400" title="{{ $description }}">{{ $description }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-right font-mono font-bold text-gray-900 dark:text-white">
                                    {{ rtrim(rtrim(number_format((float) $qty, 3, ',', '.'), '0'), ',') }}
                                    <span class="text-[10px] font-normal text-gray-500">{{ $uom }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    @if ($do)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded border border-emerald-200 dark:border-emerald-800">
                                            <x-heroicon-m-check-badge class="w-3.5 h-3.5 text-emerald-500" />
                                            DO: {{ $do->delivery_order_no ?? $do->document_code }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">
                                            Belum Terhubung
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center text-gray-500 font-mono text-[11px]">
                                    {{ $item->created_at->format('H:i:s') }}
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <button
                                        type="button"
                                        x-on:click="itemToDelete = {{ $item->id }}; $dispatch('open-modal', { id: 'delete-item-confirmation' })"
                                        class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/50 rounded-lg transition-colors"
                                        title="Hapus dari transmittal">
                                        <x-heroicon-o-trash class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                                        <div class="p-3 bg-gray-100 dark:bg-gray-800 rounded-full mb-2">
                                            <x-heroicon-o-inbox class="w-7 h-7" />
                                        </div>
                                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Belum ada material yang discan</p>
                                        <p class="text-xs text-gray-400 max-w-sm mt-0.5">
                                            Arahkan kamera ke barcode lembar GRS atau masukkan kode menggunakan scanner tembak di atas.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <x-filament::modal id="delete-item-confirmation" width="sm">
            <x-slot name="heading">
                Hapus Material dari Transmittal
            </x-slot>

            <x-slot name="description">
                Apakah Anda yakin ingin mengeluarkan material ini dari transmittal? Status material di Master MB51 akan otomatis dikembalikan ke <strong>Pending</strong>.
            </x-slot>

            <x-slot name="footer">
                <div class="flex justify-end gap-x-3">
                    <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'delete-item-confirmation' })">
                        Batal
                    </x-filament::button>
                    <x-filament::button color="danger" x-on:click="$wire.deleteItem(itemToDelete); $dispatch('close-modal', { id: 'delete-item-confirmation' })">
                        Hapus Item
                    </x-filament::button>
                </div>
            </x-slot>
        </x-filament::modal>

    </div>

    @push('styles')
    <style>
        #qr-reader {
            border: none !important;
        }
        #qr-reader video {
            border-radius: 0.75rem;
            object-fit: cover;
        }
        #qr-reader__scan_region {
            border-radius: 0.75rem;
        }
    </style>
    @endpush
</x-filament-panels::page>
