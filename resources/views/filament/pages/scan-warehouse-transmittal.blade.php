<x-filament-panels::page>
    <div x-data="{
        isCameraOpen: false,
        html5QrCode: null,
        itemToDelete: null,
        lastScanned: null,
        lastScanTime: 0,
        isProcessing: false,
        scanStatusMessage: '',
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
        async startCamera() {
            if (!window.Html5Qrcode) {
                this.loadScript(() => this.startCamera());
                return;
            }

            this.isCameraOpen = true;
            await this.$nextTick();

            if (this.html5QrCode) {
                try {
                    if (this.html5QrCode.isScanning) {
                        await this.html5QrCode.stop();
                    }
                    try {
                        this.html5QrCode.clear();
                    } catch (e) {}
                } catch (e) {
                    console.warn('Pre-start cleanup error:', e);
                }
            }

            this.initHtml5Qr();
        },
        initHtml5Qr() {
            const qrBoxFunction = function(viewfinderWidth, viewfinderHeight) {
                // Mendukung QR Code (persegi) maupun Barcode 1D GRS (persegi panjang horizontal)
                const width = Math.min(Math.floor(viewfinderWidth * 0.85), 340);
                const height = Math.min(Math.floor(viewfinderHeight * 0.65), 240);
                return {
                    width: Math.max(width, 220),
                    height: Math.max(height, 160)
                };
            };

            const qrElement = document.getElementById('qr-reader');
            if (!qrElement) return;

            this.html5QrCode = new Html5Qrcode('qr-reader');
            const config = {
                fps: 15,
                qrbox: qrBoxFunction,
                aspectRatio: 1.333333,
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                }
            };

            this.html5QrCode.start(
                { facingMode: 'environment' },
                config,
                (decodedText, decodedResult) => {
                    const now = Date.now();

                    // Kunci proses: cegah frame detection paralel saat request Livewire sedang berjalan
                    if (this.isProcessing) {
                        return;
                    }

                    // Cegah duplicate scan barcode yang sama persis dalam 6 detik
                    if (this.lastScanned === decodedText && (now - this.lastScanTime) < 6000) {
                        return;
                    }

                    // Jeda minimal 1.5 detik antar scan apa pun
                    if ((now - this.lastScanTime) < 1500) {
                        return;
                    }

                    this.isProcessing = true;
                    this.scanStatusMessage = 'Memproses Barcode ' + decodedText + '...';
                    this.lastScanned = decodedText;
                    this.lastScanTime = now;

                    $wire.processScan(decodedText)
                        .then(() => {
                            this.scanStatusMessage = '✓ Berhasil Discan!';
                        })
                        .catch((err) => {
                            console.error('Scan error:', err);
                            this.scanStatusMessage = 'Gagal memproses barcode';
                        })
                        .finally(() => {
                            // Beri waktu visual feedback 1.2 detik sebelum mengizinkan scan berikutnya
                            setTimeout(() => {
                                this.isProcessing = false;
                                this.scanStatusMessage = '';
                                this.lastScanTime = Date.now();
                            }, 1200);
                        });
                },
                (errorMessage) => {
                    // Continuous search frame - abaikan frame read misses
                }
            ).catch(err => {
                console.error('Kamera gagal dimulai:', err);
                this.isCameraOpen = false;
                this.isProcessing = false;
            });
        },
        async stopCamera() {
            if (this.html5QrCode) {
                try {
                    if (this.html5QrCode.isScanning) {
                        await this.html5QrCode.stop();
                    }
                    try {
                        this.html5QrCode.clear();
                    } catch (e) {}
                } catch (err) {
                    console.error('Error stopping camera:', err);
                }
            }
            this.isCameraOpen = false;
            this.isProcessing = false;
            this.scanStatusMessage = '';
        }
    }"
    @play-success-sound.window="playSuccess()"
    @play-error-sound.window="playError()"
    @focus-scanner-input.window="if (!isCameraOpen) { setTimeout(() => { document.getElementById('manual-barcode-input')?.focus(); }, 100); }"
    class="space-y-6">

        <!-- Header Card: Destination & Transmittal Status -->
        <div class="fi-wi-widget p-6 rounded-2xl bg-white/70 dark:bg-gray-900/60 backdrop-blur-xl border border-gray-200/60 dark:border-gray-800/60 shadow-xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <!-- Form Inputs -->
                <div class="flex-1 min-w-0 scan-header-form">
                    <form wire:submit.prevent>
                        {{ $this->form }}
                    </form>
                </div>

                <!-- Transmittal Badge & Control -->
                <div class="lg:w-72 xl:w-80 shrink-0 flex flex-col justify-center items-start lg:items-end gap-2 border-t lg:border-t-0 lg:border-l border-gray-200 dark:border-gray-800 pt-4 lg:pt-0 lg:pl-6">
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
                    <div wire:ignore class="relative w-full overflow-hidden rounded-xl bg-gray-950 min-h-[280px] flex items-center justify-center border border-gray-800">
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
                            <div id="qr-reader" wire:ignore class="w-full"></div>

                            <!-- Processing / Success Overlay -->
                            <div x-show="isProcessing" style="display: none;" class="absolute inset-0 bg-primary-950/60 backdrop-blur-xs flex items-center justify-center z-20 transition-opacity">
                                <span class="inline-flex items-center gap-2.5 px-4 py-2.5 bg-black/85 text-white rounded-full text-xs font-bold shadow-lg border border-white/10">
                                    <svg class="animate-spin h-4 w-4 text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span x-text="scanStatusMessage || 'Memproses Barcode...'"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800/80 flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full" :class="isCameraOpen ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400'"></span>
                        <span x-text="isCameraOpen ? 'Kamera Aktif: Format Barcode 1D / QR Code' : 'Siap digunakan kapan saja'"></span>
                    </span>
                    <span>Continuous Auto-detect</span>
                </div>
            </div>

            <!-- Right: Laser Scanner Gun / Manual & Multi-Code Input (5 cols) -->
            <div
                x-data="{
                    detectedCount: 0,
                    hasInput: false,
                    isInputFocused: false,
                    copiedMissing: false,
                    countCodes(val) {
                        const raw = String(val || '');
                        this.hasInput = raw.trim().length > 0;
                        const tokens = raw.trim().split(/[\r\n,;\t\s]+/).filter(Boolean);
                        this.detectedCount = new Set(tokens.map(t => t.toUpperCase())).size;
                    },
                    async pasteFromClipboard() {
                        const el = document.getElementById('manual-barcode-input');
                        try {
                            if (navigator.clipboard && navigator.clipboard.readText) {
                                const text = await navigator.clipboard.readText();
                                if (text) {
                                    const current = String($wire.manual_input || '').trim();
                                    const next = current ? (current + '\n' + text.trim()) : text.trim();
                                    $wire.set('manual_input', next, false);
                                    if (el) {
                                        el.value = next;
                                        el.focus();
                                    }
                                    this.countCodes(next);
                                    return;
                                }
                            }
                        } catch (e) {}
                        el?.focus();
                    },
                    clearInput() {
                        const el = document.getElementById('manual-barcode-input');
                        $wire.set('manual_input', '', false);
                        if (el) {
                            el.value = '';
                            el.focus();
                        }
                        this.countCodes('');
                    },
                    copyMissingCodes(text) {
                        if (!text) return;
                        navigator.clipboard?.writeText(text).then(() => {
                            this.copiedMissing = true;
                            setTimeout(() => { this.copiedMissing = false; }, 2000);
                        }).catch(() => {});
                    }
                }"
                x-init="countCodes($wire.manual_input)"
                @focus-scanner-input.window="countCodes('')"
                class="lg:col-span-5 fi-wi-widget p-6 rounded-2xl bg-white/75 dark:bg-gray-900/65 backdrop-blur-xl border border-gray-200/70 dark:border-white/[0.08] shadow-xs flex flex-col justify-between gap-4">
                <div class="space-y-4">
                    <!-- Card Header -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="p-2.5 rounded-xl bg-gradient-to-br from-orange-500/15 to-amber-500/10 dark:from-orange-500/20 dark:to-amber-500/10 text-orange-600 dark:text-orange-400 ring-1 ring-inset ring-orange-500/20 shrink-0">
                                <x-heroicon-o-viewfinder-circle class="w-5 h-5" />
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight truncate">
                                    Scanner &amp; Input Multi-Kode
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    Scanner tembak USB/Bluetooth atau tempel daftar kode
                                </p>
                            </div>
                        </div>

                        <!-- Live Status / Count Pill -->
                        <div class="shrink-0 pt-0.5">
                            <span
                                x-show="detectedCount > 1"
                                style="display: none;"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-primary-50 dark:bg-primary-500/15 text-primary-700 dark:text-primary-300 ring-1 ring-inset ring-primary-500/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary-500"></span>
                                <span x-text="detectedCount + ' kode'"></span>
                            </span>
                            <span
                                x-show="detectedCount <= 1"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium ring-1 ring-inset transition-colors"
                                :class="isInputFocused
                                    ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 ring-emerald-500/30'
                                    : 'bg-gray-100 dark:bg-white/[0.05] text-gray-600 dark:text-gray-400 ring-gray-300/60 dark:ring-white/10'">
                                <span class="w-1.5 h-1.5 rounded-full" :class="isInputFocused ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400'"></span>
                                <span x-text="isInputFocused ? 'Siap Scan' : 'Standby'"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Unified Modern Composer Box -->
                    <div>
                        <label for="manual-barcode-input" class="sr-only">
                            Input Kode GRS 105 atau Kode Material
                        </label>
                        <div
                            class="rounded-2xl border bg-gray-50/75 dark:bg-gray-950/70 transition-all overflow-hidden"
                            :class="isInputFocused
                                ? 'border-primary-500/80 dark:border-primary-500/70 ring-4 ring-primary-500/15 shadow-sm'
                                : 'border-gray-200/90 dark:border-white/10 hover:border-gray-300 dark:hover:border-white/20'">
                            <textarea
                                id="manual-barcode-input"
                                rows="4"
                                wire:model="manual_input"
                                x-on:focus="isInputFocused = true"
                                x-on:blur="isInputFocused = false"
                                x-on:input="countCodes($event.target.value)"
                                x-on:keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); detectedCount = 0; hasInput = false; $wire.submitManualScan(); }"
                                placeholder="Arahkan scanner tembak atau tempel daftar kode (1 baris per kode)&#10;Contoh: 5007761422, 5007761420, 5007761419..."
                                class="scanner-composer-scroll block w-full px-4 pt-3.5 pb-2.5 text-[13px] font-mono font-medium bg-transparent border-0 text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 placeholder:font-sans placeholder:text-xs resize-none leading-relaxed"
                                autofocus
                            ></textarea>

                            <!-- Composer Footer Toolbar -->
                            <div class="px-3 py-2 bg-white/75 dark:bg-white/[0.025] border-t border-gray-200/70 dark:border-white/[0.06] flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        x-on:click="pasteFromClipboard()"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white bg-gray-100/80 dark:bg-white/[0.06] hover:bg-gray-200/80 dark:hover:bg-white/[0.11] transition-colors">
                                        <x-heroicon-m-clipboard-document class="w-3.5 h-3.5" />
                                        Tempel
                                    </button>
                                    <button
                                        type="button"
                                        x-show="hasInput"
                                        style="display: none;"
                                        x-on:click="clearInput()"
                                        class="inline-flex items-center gap-1 px-2 py-1.5 rounded-lg text-[11px] font-medium text-gray-500 dark:text-gray-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-colors">
                                        <x-heroicon-m-x-mark class="w-3.5 h-3.5" />
                                        Bersihkan
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    wire:click="submitManualScan"
                                    x-on:click="detectedCount = 0; hasInput = false"
                                    wire:loading.attr="disabled"
                                    wire:target="submitManualScan,processScan"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-primary-600 hover:bg-primary-500 active:scale-[0.98] rounded-xl shadow-xs transition-all disabled:opacity-60">
                                    <svg wire:loading wire:target="submitManualScan,processScan" class="animate-spin w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <x-heroicon-m-arrow-right-circle wire:loading.remove wire:target="submitManualScan,processScan" class="w-4 h-4" />
                                    <span wire:loading.remove wire:target="submitManualScan,processScan" x-text="detectedCount > 1 ? 'Proses ' + detectedCount + ' Kode' : 'Proses'"></span>
                                    <span wire:loading wire:target="submitManualScan,processScan">Memproses...</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    @if($lastBatchSummary)
                        <div class="p-3.5 rounded-xl border text-xs space-y-2.5 {{ ! empty($lastBatchSummary['not_found_codes']) ? 'bg-amber-50/70 dark:bg-amber-500/[0.08] border-amber-200/80 dark:border-amber-500/25' : 'bg-emerald-50/70 dark:bg-emerald-500/[0.08] border-emerald-200/80 dark:border-emerald-500/25' }}">
                            <div class="flex items-center justify-between gap-2">
                                <div class="font-semibold {{ ! empty($lastBatchSummary['not_found_codes']) ? 'text-amber-800 dark:text-amber-300' : 'text-emerald-800 dark:text-emerald-300' }}">
                                    Hasil Multi-Kode ({{ $lastBatchSummary['total_codes'] }} kode)
                                </div>
                                <button
                                    type="button"
                                    wire:click="clearBatchSummary"
                                    class="text-[11px] text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                    Tutup
                                </button>
                            </div>
                            <div class="flex flex-wrap gap-1.5 text-[11px]">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-semibold bg-emerald-100/90 dark:bg-emerald-500/20 text-emerald-800 dark:text-emerald-200">
                                    Berhasil: {{ count($lastBatchSummary['matched_codes']) }} ({{ $lastBatchSummary['added_items'] }} item)
                                </span>
                                @if(! empty($lastBatchSummary['already_exists_codes']))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-semibold bg-sky-100/90 dark:bg-sky-500/20 text-sky-800 dark:text-sky-200">
                                        Sudah ada: {{ count($lastBatchSummary['already_exists_codes']) }}
                                    </span>
                                @endif
                                @if(! empty($lastBatchSummary['not_found_codes']))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-semibold bg-rose-100/90 dark:bg-rose-500/20 text-rose-800 dark:text-rose-200">
                                        Belum ada: {{ count($lastBatchSummary['not_found_codes']) }}
                                    </span>
                                @endif
                            </div>
                            @if(! empty($lastBatchSummary['not_found_codes']))
                                <div class="pt-1 border-t border-amber-200/60 dark:border-amber-500/20 space-y-1.5">
                                    <div class="flex items-center justify-between gap-2 text-[11px]">
                                        <span class="font-medium text-rose-700 dark:text-rose-300">Belum ada di Master MB51:</span>
                                        <button
                                            type="button"
                                            x-on:click="copyMissingCodes(@js(implode("\n", $lastBatchSummary['not_found_codes'])))"
                                            class="text-[11px] font-medium text-primary-600 dark:text-primary-400 hover:underline"
                                            x-text="copiedMissing ? 'Tersalin' : 'Salin daftar'">
                                        </button>
                                    </div>
                                    <div class="font-mono text-[11px] text-rose-700 dark:text-rose-300 break-all leading-relaxed">
                                        {{ implode(', ', $lastBatchSummary['not_found_codes']) }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Compact Supported Format Chips -->
                    <div class="grid grid-cols-2 gap-2 text-[11px]">
                        <div class="px-3 py-2 rounded-xl bg-gray-50/80 dark:bg-white/[0.03] border border-gray-200/70 dark:border-white/[0.06]">
                            <div class="font-semibold text-gray-700 dark:text-gray-200">Kode GRS 105</div>
                            <div class="text-gray-500 dark:text-gray-400 font-mono text-[10px] mt-0.5">10 / 14 digit</div>
                        </div>
                        <div class="px-3 py-2 rounded-xl bg-gray-50/80 dark:bg-white/[0.03] border border-gray-200/70 dark:border-white/[0.06]">
                            <div class="font-semibold text-gray-700 dark:text-gray-200">Kode Material</div>
                            <div class="text-gray-500 dark:text-gray-400 text-[10px] mt-0.5">Tempel dari Excel</div>
                        </div>
                    </div>
                </div>

                <!-- Clean Keyboard Footer -->
                <div class="pt-3 border-t border-gray-100 dark:border-white/[0.06] text-[11px] text-gray-500 dark:text-gray-400 flex items-center justify-between gap-2">
                    <span class="flex items-center gap-1.5 flex-wrap">
                        <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-white/[0.07] border border-gray-200 dark:border-white/10 rounded text-gray-700 dark:text-gray-300 font-mono text-[10px]">Enter</kbd>
                        <span>proses</span>
                        <span class="text-gray-300 dark:text-gray-600">&bull;</span>
                        <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-white/[0.07] border border-gray-200 dark:border-white/10 rounded text-gray-700 dark:text-gray-300 font-mono text-[10px]">Shift+Enter</kbd>
                        <span>baris baru</span>
                    </span>
                    <button
                        type="button"
                        x-on:click="document.getElementById('manual-barcode-input')?.focus()"
                        class="text-primary-600 dark:text-primary-400 hover:underline font-medium shrink-0">
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
        .scan-header-form .fi-select-input-value-label,
        .scan-header-form .fi-select-input-btn {
            white-space: nowrap;
        }
        #manual-barcode-input:focus {
            outline: none !important;
            box-shadow: none !important;
        }
        .scanner-composer-scroll {
            scrollbar-width: thin;
            scrollbar-color: rgba(156, 163, 175, 0.35) transparent;
        }
        .scanner-composer-scroll::-webkit-scrollbar {
            width: 5px;
        }
        .scanner-composer-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .scanner-composer-scroll::-webkit-scrollbar-thumb {
            background-color: rgba(156, 163, 175, 0.35);
            border-radius: 9999px;
        }
        .scanner-composer-scroll::-webkit-scrollbar-thumb:hover {
            background-color: rgba(156, 163, 175, 0.55);
        }
    </style>
    @endpush
</x-filament-panels::page>
