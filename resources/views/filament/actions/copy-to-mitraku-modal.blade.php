@php
    $formatSubtotalForCopy = function ($val): string {
        if ($val === null || $val === '') {
            return '';
        }
        $num = (float) $val;

        return floor($num) == $num
            ? number_format($num, 0, '', '')
            : rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.');
    };

    $formatMoneyDisplay = function ($val): string {
        if ($val === null || $val === '') {
            return '-';
        }
        $num = (float) $val;

        return 'Rp ' . (floor($num) == $num ? number_format($num, 0, ',', '.') : number_format($num, 2, ',', '.'));
    };

    $subtotalExcludeCopy = $formatSubtotalForCopy($record->subtotal_exclude_ppn ?? null);
    $subtotalIncludeCopy = $formatSubtotalForCopy($record->subtotal_include_ppn ?? null);

    $subtotalExcludeDisplay = $formatMoneyDisplay($record->subtotal_exclude_ppn ?? null);
    $subtotalIncludeDisplay = $formatMoneyDisplay($record->subtotal_include_ppn ?? null);

    $materialName = $record->nama_barang ?: ($record->deskripsi ?: '-');
    $materialCode = $record->purchaseOrderIssued?->material_code ?? $record->deliveryOrderReceipt?->deliveryOrderReceiptDetails?->first()?->material_code;

    $rawDeliveryDate = $record->delivery_date ?? $record->delivery_date_po ?? $record->purchaseOrderIssued?->delivery_date_po;
    $deliveryDatePoFormatted = $rawDeliveryDate ? \Illuminate\Support\Carbon::parse($rawDeliveryDate)->format('d-m-Y') : '';

    $rawDeliveryDateGrs = $record->delivery_date;
    $deliveryDateGrsFormatted = $rawDeliveryDateGrs ? \Illuminate\Support\Carbon::parse($rawDeliveryDateGrs)->format('d-m-Y') : '';

    $rawTanggalPenerimaan = $record->tanggal_penerimaan;
    $tanggalPenerimaanFormatted = $rawTanggalPenerimaan ? \Illuminate\Support\Carbon::parse($rawTanggalPenerimaan)->format('d-m-Y') : '';

    $rawTanggalGrs = $record->tanggal_terima_grs;
    $tanggalGrsFormatted = $rawTanggalGrs ? \Illuminate\Support\Carbon::parse($rawTanggalGrs)->format('d-m-Y') : '';

    $rawTanggalInvoice = $record->tanggal_invoice;
    $tanggalInvoiceFormatted = $rawTanggalInvoice ? \Illuminate\Support\Carbon::parse($rawTanggalInvoice)->format('d-m-Y') : '';

    $rawTanggalFakturPajak = $record->tanggal_faktur_pajak;
    $tanggalFakturPajakFormatted = $rawTanggalFakturPajak ? \Illuminate\Support\Carbon::parse($rawTanggalFakturPajak)->format('d-m-Y') : '';

    $totalStepCount = $materialCode ? 18 : 17;
    $materialTypeLabel = \App\Models\InvoicingReceipt::normalizeMaterialType($record->material_type);
@endphp

<div class="space-y-5" x-data="{
    copiedField: null,
    copiedHistory: [],
    totalSteps: {{ $totalStepCount }},

    copyText(text, fieldName) {
        if (!text || text === '-' || text === '') {
            if (typeof $wire !== 'undefined' && $wire.dispatch) {
                $wire.dispatch('notify', {
                    status: 'warning',
                    message: 'Nilai data kosong atau belum tersedia'
                });
            }
            return;
        }

        navigator.clipboard.writeText(text).then(() => {
            this.copiedField = fieldName;
            if (!this.copiedHistory.includes(fieldName)) {
                this.copiedHistory.push(fieldName);
            }
            setTimeout(() => {
                if (this.copiedField === fieldName) {
                    this.copiedField = null;
                }
            }, 1800);
        });
    },

    isCopied(fieldName) {
        return this.copiedHistory.includes(fieldName);
    },

    resetCopiedHistory() {
        this.copiedHistory = [];
        this.copiedField = null;
    },

    copyAllTsv() {
        const row = [
            '{{ addslashes($record->no_po ?? '') }}',
            '{{ $deliveryDatePoFormatted }}',
            '{{ addslashes($record->no_do ?? '') }}',
            '{{ $deliveryDateGrsFormatted }}',
            '{{ addslashes($record->no_grs ?? ($record->no_rdtv ?? '')) }}',
            '{{ $tanggalGrsFormatted }}',
            '{{ $tanggalPenerimaanFormatted }}',
            '{{ addslashes($record->no_invoice ?? '') }}',
            '{{ $tanggalInvoiceFormatted }}',
            '{{ addslashes($record->faktur_pajak ?? '') }}',
            '{{ $tanggalFakturPajakFormatted }}',
            '{{ $subtotalExcludeCopy }}',
            '{{ $subtotalIncludeCopy }}',
            '{{ $record->qty_penagihan ?? '' }}',
            '{{ $record->qty_po ?? '' }}',
            '{{ addslashes($record->vendor_name ?? '') }}',
            '{{ addslashes($materialName !== '-' ? $materialName : '') }}'
            @if ($materialCode)
                , '{{ addslashes($materialCode) }}'
            @endif
        ].join('\t');

        navigator.clipboard.writeText(row).then(() => {
            this.copiedField = '__ALL__';
            setTimeout(() => { this.copiedField = null; }, 2500);
        });
    }
}">
    <!-- =========================================================
         1. HERO WORKFLOW BANNER (Apple Glass Card with Progress)
         ========================================================= -->
    <div class="relative overflow-hidden p-4 sm:p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-linear-to-br from-white via-slate-50/70 to-orange-50/40 dark:from-slate-900/90 dark:via-slate-900/80 dark:to-orange-950/20 shadow-xs backdrop-blur-xl">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Left Info & Step Tracker -->
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-[#F47920]/15 text-[#F47920] dark:bg-[#F47920]/25 dark:text-orange-400">
                        <x-filament::icon icon="heroicon-m-clipboard-document-check" class="w-4 h-4" />
                    </span>
                    <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white tracking-tight">
                        Alur Salin Data ke Portal Mitraku
                    </h3>
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 leading-relaxed">
                    Klik kartu atau tombol untuk menyalin data. Ikuti nomor urut <strong class="text-slate-800 dark:text-slate-100 font-semibold">01 s.d. {{ sprintf('%02d', $totalStepCount) }}</strong> agar pengisian di portal Mitraku berurutan dan tidak ada yang terlewat.
                </p>

                <!-- Visual Progress Bar -->
                <div class="mt-3 flex items-center gap-3">
                    <div class="w-36 sm:w-48 h-2 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden shrink-0">
                        <div class="h-full bg-linear-to-r from-[#F47920] to-emerald-500 rounded-full transition-all duration-300"
                             :style="'width: ' + ((copiedHistory.length / totalSteps) * 100) + '%'"></div>
                    </div>
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 font-mono">
                        <span x-text="copiedHistory.length" class="text-[#F47920] dark:text-orange-400 font-bold"></span>/{{ $totalStepCount }} Terisi
                    </span>
                    <template x-if="copiedHistory.length > 0">
                        <button type="button" 
                                @click="resetCopiedHistory()"
                                class="text-[11px] text-slate-500 hover:text-danger-600 dark:text-slate-400 dark:hover:text-danger-400 underline underline-offset-2 ml-1 transition-colors">
                            Reset Urutan
                        </button>
                    </template>
                </div>
            </div>

            <!-- Right Quick Bulk Action -->
            <div class="shrink-0 flex items-center sm:self-center gap-2">
                <button
                    type="button"
                    @click="copyAllTsv()"
                    :class="copiedField === '__ALL__' ? 'bg-emerald-600 text-white shadow-sm ring-2 ring-emerald-400/50' : 'bg-white dark:bg-slate-800/90 hover:bg-slate-50 dark:hover:bg-slate-700/80 text-slate-800 dark:text-slate-200 border border-slate-300/80 dark:border-slate-700 shadow-xs'"
                    class="px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 flex items-center gap-2 cursor-pointer hover:scale-[1.02] active:scale-[0.98]">
                    <x-filament::icon icon="heroicon-m-check" class="w-4 h-4 text-white" x-show="copiedField === '__ALL__'" x-cloak />
                    <x-filament::icon icon="heroicon-m-table-cells" class="w-4 h-4 text-[#F47920]" x-show="copiedField !== '__ALL__'" />
                    <span x-text="copiedField === '__ALL__' ? 'Semua Baris Disalin!' : 'Salin Semua (TSV/Excel)'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- =========================================================
         2. GRUP 1: DOKUMEN PEMESANAN (PO & DO)
         ========================================================= -->
    <div class="space-y-2.5">
        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-blue-700 dark:text-blue-400">
            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            <span>1. Referensi Pemesanan & Pengiriman (PO & DO)</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <!-- Step 01: Nomor PO -->
            <div @click="copyText('{{ addslashes($record->no_po ?? '') }}', 'no_po')"
                 :class="{
                     'border-emerald-500/80 ring-2 ring-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/20': copiedField === 'no_po',
                     'border-emerald-300/60 dark:border-emerald-800/50 bg-emerald-50/10 dark:bg-emerald-950/10': isCopied('no_po') && copiedField !== 'no_po',
                     'border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/90': !isCopied('no_po') && copiedField !== 'no_po'
                 }"
                 class="group relative p-3.5 rounded-xl border transition-all duration-200 hover:border-[#F47920]/60 hover:shadow-md cursor-pointer flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span :class="isCopied('no_po') ? 'bg-emerald-600 text-white' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300'"
                          class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center font-mono font-bold text-xs transition-colors">
                        <template x-if="isCopied('no_po')">
                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                        </template>
                        <template x-if="!isCopied('no_po')">
                            <span>01</span>
                        </template>
                    </span>
                    <div class="min-w-0">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Nomor PO</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-white truncate block text-sm sm:text-base mt-0.5">{{ $record->no_po ?? '-' }}</span>
                    </div>
                </div>
                <button type="button" 
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                        :class="copiedField === 'no_po' ? 'bg-emerald-600 text-white' : (isCopied('no_po') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 group-hover:bg-[#F47920] group-hover:text-white text-slate-700 dark:bg-slate-800 dark:text-slate-200')">
                    <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" x-show="isCopied('no_po')" />
                    <x-filament::icon icon="heroicon-m-clipboard" class="w-3.5 h-3.5" x-show="!isCopied('no_po')" />
                    <span x-text="copiedField === 'no_po' ? 'Disalin!' : (isCopied('no_po') ? 'Tersalin' : 'Salin')"></span>
                </button>
            </div>

            <!-- Step 02: Delivery Date PO -->
            <div @click="copyText('{{ $deliveryDatePoFormatted }}', 'delivery_date_po')"
                 :class="{
                     'border-emerald-500/80 ring-2 ring-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/20': copiedField === 'delivery_date_po',
                     'border-emerald-300/60 dark:border-emerald-800/50 bg-emerald-50/10 dark:bg-emerald-950/10': isCopied('delivery_date_po') && copiedField !== 'delivery_date_po',
                     'border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/90': !isCopied('delivery_date_po') && copiedField !== 'delivery_date_po'
                 }"
                 class="group relative p-3.5 rounded-xl border transition-all duration-200 hover:border-[#F47920]/60 hover:shadow-md cursor-pointer flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span :class="isCopied('delivery_date_po') ? 'bg-emerald-600 text-white' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300'"
                          class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center font-mono font-bold text-xs transition-colors">
                        <template x-if="isCopied('delivery_date_po')">
                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                        </template>
                        <template x-if="!isCopied('delivery_date_po')">
                            <span>02</span>
                        </template>
                    </span>
                    <div class="min-w-0">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Delivery Date PO</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-white truncate block text-sm sm:text-base mt-0.5">{{ $deliveryDatePoFormatted ?: '-' }}</span>
                    </div>
                </div>
                <button type="button" 
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                        :class="copiedField === 'delivery_date_po' ? 'bg-emerald-600 text-white' : (isCopied('delivery_date_po') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 group-hover:bg-[#F47920] group-hover:text-white text-slate-700 dark:bg-slate-800 dark:text-slate-200')">
                    <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" x-show="isCopied('delivery_date_po')" />
                    <x-filament::icon icon="heroicon-m-clipboard" class="w-3.5 h-3.5" x-show="!isCopied('delivery_date_po')" />
                    <span x-text="copiedField === 'delivery_date_po' ? 'Disalin!' : (isCopied('delivery_date_po') ? 'Tersalin' : 'Salin')"></span>
                </button>
            </div>

            <!-- Step 03: Nomor DO -->
            <div @click="copyText('{{ addslashes($record->no_do ?? '') }}', 'no_do')"
                 :class="{
                     'border-emerald-500/80 ring-2 ring-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/20': copiedField === 'no_do',
                     'border-emerald-300/60 dark:border-emerald-800/50 bg-emerald-50/10 dark:bg-emerald-950/10': isCopied('no_do') && copiedField !== 'no_do',
                     'border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/90': !isCopied('no_do') && copiedField !== 'no_do'
                 }"
                 class="group relative p-3.5 rounded-xl border transition-all duration-200 hover:border-[#F47920]/60 hover:shadow-md cursor-pointer flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span :class="isCopied('no_do') ? 'bg-emerald-600 text-white' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300'"
                          class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center font-mono font-bold text-xs transition-colors">
                        <template x-if="isCopied('no_do')">
                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                        </template>
                        <template x-if="!isCopied('no_do')">
                            <span>03</span>
                        </template>
                    </span>
                    <div class="min-w-0">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Nomor DO</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-white truncate block text-sm sm:text-base mt-0.5">{{ $record->no_do ?? '-' }}</span>
                    </div>
                </div>
                <button type="button" 
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                        :class="copiedField === 'no_do' ? 'bg-emerald-600 text-white' : (isCopied('no_do') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 group-hover:bg-[#F47920] group-hover:text-white text-slate-700 dark:bg-slate-800 dark:text-slate-200')">
                    <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" x-show="isCopied('no_do')" />
                    <x-filament::icon icon="heroicon-m-clipboard" class="w-3.5 h-3.5" x-show="!isCopied('no_do')" />
                    <span x-text="copiedField === 'no_do' ? 'Disalin!' : (isCopied('no_do') ? 'Tersalin' : 'Salin')"></span>
                </button>
            </div>

            <!-- Step 04: Delivery Date GRS -->
            <div @click="copyText('{{ $deliveryDateGrsFormatted }}', 'delivery_date_grs')"
                 :class="{
                     'border-emerald-500/80 ring-2 ring-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/20': copiedField === 'delivery_date_grs',
                     'border-emerald-300/60 dark:border-emerald-800/50 bg-emerald-50/10 dark:bg-emerald-950/10': isCopied('delivery_date_grs') && copiedField !== 'delivery_date_grs',
                     'border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/90': !isCopied('delivery_date_grs') && copiedField !== 'delivery_date_grs'
                 }"
                 class="group relative p-3.5 rounded-xl border transition-all duration-200 hover:border-[#F47920]/60 hover:shadow-md cursor-pointer flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span :class="isCopied('delivery_date_grs') ? 'bg-emerald-600 text-white' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300'"
                          class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center font-mono font-bold text-xs transition-colors">
                        <template x-if="isCopied('delivery_date_grs')">
                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                        </template>
                        <template x-if="!isCopied('delivery_date_grs')">
                            <span>04</span>
                        </template>
                    </span>
                    <div class="min-w-0">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Delivery Date (GRS)</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-white truncate block text-sm sm:text-base mt-0.5">{{ $deliveryDateGrsFormatted ?: '-' }}</span>
                    </div>
                </div>
                <button type="button" 
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                        :class="copiedField === 'delivery_date_grs' ? 'bg-emerald-600 text-white' : (isCopied('delivery_date_grs') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 group-hover:bg-[#F47920] group-hover:text-white text-slate-700 dark:bg-slate-800 dark:text-slate-200')">
                    <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" x-show="isCopied('delivery_date_grs')" />
                    <x-filament::icon icon="heroicon-m-clipboard" class="w-3.5 h-3.5" x-show="!isCopied('delivery_date_grs')" />
                    <span x-text="copiedField === 'delivery_date_grs' ? 'Disalin!' : (isCopied('delivery_date_grs') ? 'Tersalin' : 'Salin')"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- =========================================================
         3. GRUP 2: BUKTI PENERIMAAN BARANG (GRS / SAP)
         ========================================================= -->
    <div class="space-y-2.5">
        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>2. Verifikasi Penerimaan Barang (GRS / SAP)</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <!-- Step 05: No. GRS / RDTV -->
            <div @click="copyText('{{ addslashes($record->no_grs ?? ($record->no_rdtv ?? '')) }}', 'no_grs')"
                 :class="{
                     'border-emerald-500/80 ring-2 ring-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/20': copiedField === 'no_grs',
                     'border-emerald-300/60 dark:border-emerald-800/50 bg-emerald-50/10 dark:bg-emerald-950/10': isCopied('no_grs') && copiedField !== 'no_grs',
                     'border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/90': !isCopied('no_grs') && copiedField !== 'no_grs'
                 }"
                 class="group relative p-3.5 rounded-xl border transition-all duration-200 hover:border-[#F47920]/60 hover:shadow-md cursor-pointer flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span :class="isCopied('no_grs') ? 'bg-emerald-600 text-white' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300'"
                          class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center font-mono font-bold text-xs transition-colors">
                        <template x-if="isCopied('no_grs')">
                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                        </template>
                        <template x-if="!isCopied('no_grs')">
                            <span>05</span>
                        </template>
                    </span>
                    <div class="min-w-0">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">No. {{ $record->jenis ?? 'GRS' }}</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-white truncate block text-sm mt-0.5">{{ $record->no_grs ?? ($record->no_rdtv ?? '-') }}</span>
                    </div>
                </div>
                <button type="button" 
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                        :class="copiedField === 'no_grs' ? 'bg-emerald-600 text-white' : (isCopied('no_grs') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 group-hover:bg-[#F47920] group-hover:text-white text-slate-700 dark:bg-slate-800 dark:text-slate-200')">
                    <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" x-show="isCopied('no_grs')" />
                    <x-filament::icon icon="heroicon-m-clipboard" class="w-3.5 h-3.5" x-show="!isCopied('no_grs')" />
                    <span x-text="copiedField === 'no_grs' ? 'Disalin!' : (isCopied('no_grs') ? 'Tersalin' : 'Salin')"></span>
                </button>
            </div>

            <!-- Step 06: Tanggal GRS / Release GR -->
            <div @click="copyText('{{ $tanggalGrsFormatted }}', 'tanggal_grs')"
                 :class="{
                     'border-emerald-500/80 ring-2 ring-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/20': copiedField === 'tanggal_grs',
                     'border-emerald-300/60 dark:border-emerald-800/50 bg-emerald-50/10 dark:bg-emerald-950/10': isCopied('tanggal_grs') && copiedField !== 'tanggal_grs',
                     'border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/90': !isCopied('tanggal_grs') && copiedField !== 'tanggal_grs'
                 }"
                 class="group relative p-3.5 rounded-xl border transition-all duration-200 hover:border-[#F47920]/60 hover:shadow-md cursor-pointer flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span :class="isCopied('tanggal_grs') ? 'bg-emerald-600 text-white' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300'"
                          class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center font-mono font-bold text-xs transition-colors">
                        <template x-if="isCopied('tanggal_grs')">
                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                        </template>
                        <template x-if="!isCopied('tanggal_grs')">
                            <span>06</span>
                        </template>
                    </span>
                    <div class="min-w-0">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Tgl Release GR</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-white truncate block text-sm mt-0.5">{{ $tanggalGrsFormatted ?: '-' }}</span>
                    </div>
                </div>
                <button type="button" 
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                        :class="copiedField === 'tanggal_grs' ? 'bg-emerald-600 text-white' : (isCopied('tanggal_grs') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 group-hover:bg-[#F47920] group-hover:text-white text-slate-700 dark:bg-slate-800 dark:text-slate-200')">
                    <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" x-show="isCopied('tanggal_grs')" />
                    <x-filament::icon icon="heroicon-m-clipboard" class="w-3.5 h-3.5" x-show="!isCopied('tanggal_grs')" />
                    <span x-text="copiedField === 'tanggal_grs' ? 'Disalin!' : (isCopied('tanggal_grs') ? 'Tersalin' : 'Salin')"></span>
                </button>
            </div>

            <!-- Step 07: Tanggal Penerimaan Fisik -->
            <div @click="copyText('{{ $tanggalPenerimaanFormatted }}', 'tanggal_penerimaan')"
                 :class="{
                     'border-emerald-500/80 ring-2 ring-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/20': copiedField === 'tanggal_penerimaan',
                     'border-emerald-300/60 dark:border-emerald-800/50 bg-emerald-50/10 dark:bg-emerald-950/10': isCopied('tanggal_penerimaan') && copiedField !== 'tanggal_penerimaan',
                     'border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/90': !isCopied('tanggal_penerimaan') && copiedField !== 'tanggal_penerimaan'
                 }"
                 class="group relative p-3.5 rounded-xl border transition-all duration-200 hover:border-[#F47920]/60 hover:shadow-md cursor-pointer flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span :class="isCopied('tanggal_penerimaan') ? 'bg-emerald-600 text-white' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300'"
                          class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center font-mono font-bold text-xs transition-colors">
                        <template x-if="isCopied('tanggal_penerimaan')">
                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                        </template>
                        <template x-if="!isCopied('tanggal_penerimaan')">
                            <span>07</span>
                        </template>
                    </span>
                    <div class="min-w-0">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Tgl Terima Fisik</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-white truncate block text-sm mt-0.5">{{ $tanggalPenerimaanFormatted ?: '-' }}</span>
                    </div>
                </div>
                <button type="button" 
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                        :class="copiedField === 'tanggal_penerimaan' ? 'bg-emerald-600 text-white' : (isCopied('tanggal_penerimaan') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 group-hover:bg-[#F47920] group-hover:text-white text-slate-700 dark:bg-slate-800 dark:text-slate-200')">
                    <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" x-show="isCopied('tanggal_penerimaan')" />
                    <x-filament::icon icon="heroicon-m-clipboard" class="w-3.5 h-3.5" x-show="!isCopied('tanggal_penerimaan')" />
                    <span x-text="copiedField === 'tanggal_penerimaan' ? 'Disalin!' : (isCopied('tanggal_penerimaan') ? 'Tersalin' : 'Salin')"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- =========================================================
         4. GRUP 3: DOKUMEN INVOICE & FAKTUR PAJAK
         ========================================================= -->
    <div class="space-y-2.5">
        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-violet-700 dark:text-violet-400">
            <span class="w-2 h-2 rounded-full bg-violet-500"></span>
            <span>3. Tagihan Vendor & Dokumen Pajak</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <!-- Step 08: Nomor Invoice -->
            <div @click="copyText('{{ addslashes($record->no_invoice ?? '') }}', 'no_invoice')"
                 :class="{
                     'border-emerald-500/80 ring-2 ring-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/20': copiedField === 'no_invoice',
                     'border-emerald-300/60 dark:border-emerald-800/50 bg-emerald-50/10 dark:bg-emerald-950/10': isCopied('no_invoice') && copiedField !== 'no_invoice',
                     'border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/90': !isCopied('no_invoice') && copiedField !== 'no_invoice'
                 }"
                 class="group relative p-3.5 rounded-xl border transition-all duration-200 hover:border-[#F47920]/60 hover:shadow-md cursor-pointer flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span :class="isCopied('no_invoice') ? 'bg-emerald-600 text-white' : 'bg-violet-100 text-violet-800 dark:bg-violet-900/50 dark:text-violet-300'"
                          class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center font-mono font-bold text-xs transition-colors">
                        <template x-if="isCopied('no_invoice')">
                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                        </template>
                        <template x-if="!isCopied('no_invoice')">
                            <span>08</span>
                        </template>
                    </span>
                    <div class="min-w-0">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Nomor Invoice</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-white truncate block text-sm sm:text-base mt-0.5">{{ $record->no_invoice ?: '-' }}</span>
                    </div>
                </div>
                <button type="button" 
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                        :class="copiedField === 'no_invoice' ? 'bg-emerald-600 text-white' : (isCopied('no_invoice') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 group-hover:bg-[#F47920] group-hover:text-white text-slate-700 dark:bg-slate-800 dark:text-slate-200')">
                    <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" x-show="isCopied('no_invoice')" />
                    <x-filament::icon icon="heroicon-m-clipboard" class="w-3.5 h-3.5" x-show="!isCopied('no_invoice')" />
                    <span x-text="copiedField === 'no_invoice' ? 'Disalin!' : (isCopied('no_invoice') ? 'Tersalin' : 'Salin')"></span>
                </button>
            </div>

            <!-- Step 09: Tanggal Invoice -->
            <div @click="copyText('{{ $tanggalInvoiceFormatted }}', 'tanggal_invoice')"
                 :class="{
                     'border-emerald-500/80 ring-2 ring-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/20': copiedField === 'tanggal_invoice',
                     'border-emerald-300/60 dark:border-emerald-800/50 bg-emerald-50/10 dark:bg-emerald-950/10': isCopied('tanggal_invoice') && copiedField !== 'tanggal_invoice',
                     'border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/90': !isCopied('tanggal_invoice') && copiedField !== 'tanggal_invoice'
                 }"
                 class="group relative p-3.5 rounded-xl border transition-all duration-200 hover:border-[#F47920]/60 hover:shadow-md cursor-pointer flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span :class="isCopied('tanggal_invoice') ? 'bg-emerald-600 text-white' : 'bg-violet-100 text-violet-800 dark:bg-violet-900/50 dark:text-violet-300'"
                          class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center font-mono font-bold text-xs transition-colors">
                        <template x-if="isCopied('tanggal_invoice')">
                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                        </template>
                        <template x-if="!isCopied('tanggal_invoice')">
                            <span>09</span>
                        </template>
                    </span>
                    <div class="min-w-0">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Tanggal Invoice</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-white truncate block text-sm sm:text-base mt-0.5">{{ $tanggalInvoiceFormatted ?: '-' }}</span>
                    </div>
                </div>
                <button type="button" 
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                        :class="copiedField === 'tanggal_invoice' ? 'bg-emerald-600 text-white' : (isCopied('tanggal_invoice') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 group-hover:bg-[#F47920] group-hover:text-white text-slate-700 dark:bg-slate-800 dark:text-slate-200')">
                    <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" x-show="isCopied('tanggal_invoice')" />
                    <x-filament::icon icon="heroicon-m-clipboard" class="w-3.5 h-3.5" x-show="!isCopied('tanggal_invoice')" />
                    <span x-text="copiedField === 'tanggal_invoice' ? 'Disalin!' : (isCopied('tanggal_invoice') ? 'Tersalin' : 'Salin')"></span>
                </button>
            </div>

            <!-- Step 10: Nomor Faktur Pajak -->
            <div @click="copyText('{{ addslashes($record->faktur_pajak ?? '') }}', 'faktur_pajak')"
                 :class="{
                     'border-emerald-500/80 ring-2 ring-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/20': copiedField === 'faktur_pajak',
                     'border-emerald-300/60 dark:border-emerald-800/50 bg-emerald-50/10 dark:bg-emerald-950/10': isCopied('faktur_pajak') && copiedField !== 'faktur_pajak',
                     'border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/90': !isCopied('faktur_pajak') && copiedField !== 'faktur_pajak'
                 }"
                 class="group relative p-3.5 rounded-xl border transition-all duration-200 hover:border-[#F47920]/60 hover:shadow-md cursor-pointer flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span :class="isCopied('faktur_pajak') ? 'bg-emerald-600 text-white' : 'bg-violet-100 text-violet-800 dark:bg-violet-900/50 dark:text-violet-300'"
                          class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center font-mono font-bold text-xs transition-colors">
                        <template x-if="isCopied('faktur_pajak')">
                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                        </template>
                        <template x-if="!isCopied('faktur_pajak')">
                            <span>10</span>
                        </template>
                    </span>
                    <div class="min-w-0">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Nomor Faktur Pajak</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-white truncate block text-sm sm:text-base mt-0.5">{{ $record->faktur_pajak ?: '-' }}</span>
                    </div>
                </div>
                <button type="button" 
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                        :class="copiedField === 'faktur_pajak' ? 'bg-emerald-600 text-white' : (isCopied('faktur_pajak') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 group-hover:bg-[#F47920] group-hover:text-white text-slate-700 dark:bg-slate-800 dark:text-slate-200')">
                    <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" x-show="isCopied('faktur_pajak')" />
                    <x-filament::icon icon="heroicon-m-clipboard" class="w-3.5 h-3.5" x-show="!isCopied('faktur_pajak')" />
                    <span x-text="copiedField === 'faktur_pajak' ? 'Disalin!' : (isCopied('faktur_pajak') ? 'Tersalin' : 'Salin')"></span>
                </button>
            </div>

            <!-- Step 11: Tanggal Faktur Pajak -->
            <div @click="copyText('{{ $tanggalFakturPajakFormatted }}', 'tanggal_faktur_pajak')"
                 :class="{
                     'border-emerald-500/80 ring-2 ring-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/20': copiedField === 'tanggal_faktur_pajak',
                     'border-emerald-300/60 dark:border-emerald-800/50 bg-emerald-50/10 dark:bg-emerald-950/10': isCopied('tanggal_faktur_pajak') && copiedField !== 'tanggal_faktur_pajak',
                     'border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/90': !isCopied('tanggal_faktur_pajak') && copiedField !== 'tanggal_faktur_pajak'
                 }"
                 class="group relative p-3.5 rounded-xl border transition-all duration-200 hover:border-[#F47920]/60 hover:shadow-md cursor-pointer flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span :class="isCopied('tanggal_faktur_pajak') ? 'bg-emerald-600 text-white' : 'bg-violet-100 text-violet-800 dark:bg-violet-900/50 dark:text-violet-300'"
                          class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center font-mono font-bold text-xs transition-colors">
                        <template x-if="isCopied('tanggal_faktur_pajak')">
                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                        </template>
                        <template x-if="!isCopied('tanggal_faktur_pajak')">
                            <span>11</span>
                        </template>
                    </span>
                    <div class="min-w-0">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Tanggal Faktur Pajak</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-white truncate block text-sm sm:text-base mt-0.5">{{ $tanggalFakturPajakFormatted ?: '-' }}</span>
                    </div>
                </div>
                <button type="button" 
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                        :class="copiedField === 'tanggal_faktur_pajak' ? 'bg-emerald-600 text-white' : (isCopied('tanggal_faktur_pajak') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 group-hover:bg-[#F47920] group-hover:text-white text-slate-700 dark:bg-slate-800 dark:text-slate-200')">
                    <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" x-show="isCopied('tanggal_faktur_pajak')" />
                    <x-filament::icon icon="heroicon-m-clipboard" class="w-3.5 h-3.5" x-show="!isCopied('tanggal_faktur_pajak')" />
                    <span x-text="copiedField === 'tanggal_faktur_pajak' ? 'Disalin!' : (isCopied('tanggal_faktur_pajak') ? 'Tersalin' : 'Salin')"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- =========================================================
         5. GRUP 4: NILAI FINANSIAL & KUANTITAS
         ========================================================= -->
    <div class="space-y-2.5">
        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            <span>4. Nilai Finansial & Volume Fisik</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <!-- Step 12: Subtotal Exclude PPN (DPP) -->
            <div @click="copyText('{{ $subtotalExcludeCopy }}', 'subtotal_exclude')"
                 :class="{
                     'border-emerald-500/80 ring-2 ring-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/20': copiedField === 'subtotal_exclude',
                     'border-emerald-300/60 dark:border-emerald-800/50 bg-emerald-50/10 dark:bg-emerald-950/10': isCopied('subtotal_exclude') && copiedField !== 'subtotal_exclude',
                     'border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/90': !isCopied('subtotal_exclude') && copiedField !== 'subtotal_exclude'
                 }"
                 class="group relative p-3.5 rounded-xl border transition-all duration-200 hover:border-[#F47920]/60 hover:shadow-md cursor-pointer flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span :class="isCopied('subtotal_exclude') ? 'bg-emerald-600 text-white' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300'"
                          class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center font-mono font-bold text-xs transition-colors">
                        <template x-if="isCopied('subtotal_exclude')">
                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                        </template>
                        <template x-if="!isCopied('subtotal_exclude')">
                            <span>12</span>
                        </template>
                    </span>
                    <div class="min-w-0">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Subtotal (Exclude PPN)</span>
                        <span class="font-bold font-mono text-primary-600 dark:text-primary-400 truncate block text-sm sm:text-base mt-0.5">{{ $subtotalExcludeDisplay }}</span>
                        @if ($subtotalExcludeCopy)
                            <span class="text-[11px] text-slate-400 dark:text-slate-500 font-mono block">Copy value: {{ $subtotalExcludeCopy }}</span>
                        @endif
                    </div>
                </div>
                <button type="button" 
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                        :class="copiedField === 'subtotal_exclude' ? 'bg-emerald-600 text-white' : (isCopied('subtotal_exclude') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 group-hover:bg-[#F47920] group-hover:text-white text-slate-700 dark:bg-slate-800 dark:text-slate-200')">
                    <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" x-show="isCopied('subtotal_exclude')" />
                    <x-filament::icon icon="heroicon-m-clipboard" class="w-3.5 h-3.5" x-show="!isCopied('subtotal_exclude')" />
                    <span x-text="copiedField === 'subtotal_exclude' ? 'Disalin!' : (isCopied('subtotal_exclude') ? 'Tersalin' : 'Salin')"></span>
                </button>
            </div>

            <!-- Step 13: Subtotal Include PPN (Total) -->
            <div @click="copyText('{{ $subtotalIncludeCopy }}', 'subtotal_include')"
                 :class="{
                     'border-emerald-500/80 ring-2 ring-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/20': copiedField === 'subtotal_include',
                     'border-emerald-300/60 dark:border-emerald-800/50 bg-emerald-50/10 dark:bg-emerald-950/10': isCopied('subtotal_include') && copiedField !== 'subtotal_include',
                     'border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/90': !isCopied('subtotal_include') && copiedField !== 'subtotal_include'
                 }"
                 class="group relative p-3.5 rounded-xl border transition-all duration-200 hover:border-[#F47920]/60 hover:shadow-md cursor-pointer flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span :class="isCopied('subtotal_include') ? 'bg-emerald-600 text-white' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300'"
                          class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center font-mono font-bold text-xs transition-colors">
                        <template x-if="isCopied('subtotal_include')">
                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                        </template>
                        <template x-if="!isCopied('subtotal_include')">
                            <span>13</span>
                        </template>
                    </span>
                    <div class="min-w-0">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Total Tagihan (Include PPN)</span>
                        <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400 truncate block text-sm sm:text-base mt-0.5">{{ $subtotalIncludeDisplay }}</span>
                        @if ($subtotalIncludeCopy)
                            <span class="text-[11px] text-slate-400 dark:text-slate-500 font-mono block">Copy value: {{ $subtotalIncludeCopy }}</span>
                        @endif
                    </div>
                </div>
                <button type="button" 
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                        :class="copiedField === 'subtotal_include' ? 'bg-emerald-600 text-white' : (isCopied('subtotal_include') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 group-hover:bg-[#F47920] group-hover:text-white text-slate-700 dark:bg-slate-800 dark:text-slate-200')">
                    <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" x-show="isCopied('subtotal_include')" />
                    <x-filament::icon icon="heroicon-m-clipboard" class="w-3.5 h-3.5" x-show="!isCopied('subtotal_include')" />
                    <span x-text="copiedField === 'subtotal_include' ? 'Disalin!' : (isCopied('subtotal_include') ? 'Tersalin' : 'Salin')"></span>
                </button>
            </div>

            <!-- Step 14: Quantity Penagihan -->
            <div @click="copyText('{{ $record->qty_penagihan ?? '' }}', 'qty_penagihan')"
                 :class="{
                     'border-emerald-500/80 ring-2 ring-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/20': copiedField === 'qty_penagihan',
                     'border-emerald-300/60 dark:border-emerald-800/50 bg-emerald-50/10 dark:bg-emerald-950/10': isCopied('qty_penagihan') && copiedField !== 'qty_penagihan',
                     'border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/90': !isCopied('qty_penagihan') && copiedField !== 'qty_penagihan'
                 }"
                 class="group relative p-3.5 rounded-xl border transition-all duration-200 hover:border-[#F47920]/60 hover:shadow-md cursor-pointer flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span :class="isCopied('qty_penagihan') ? 'bg-emerald-600 text-white' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300'"
                          class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center font-mono font-bold text-xs transition-colors">
                        <template x-if="isCopied('qty_penagihan')">
                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                        </template>
                        <template x-if="!isCopied('qty_penagihan')">
                            <span>14</span>
                        </template>
                    </span>
                    <div class="min-w-0">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Quantity Penagihan</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-white truncate block text-sm sm:text-base mt-0.5">
                            {{ $record->qty_penagihan ?? '-' }} <span class="text-xs text-slate-500 font-sans font-semibold">{{ $record->uoi ?? '' }}</span>
                        </span>
                    </div>
                </div>
                <button type="button" 
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                        :class="copiedField === 'qty_penagihan' ? 'bg-emerald-600 text-white' : (isCopied('qty_penagihan') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 group-hover:bg-[#F47920] group-hover:text-white text-slate-700 dark:bg-slate-800 dark:text-slate-200')">
                    <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" x-show="isCopied('qty_penagihan')" />
                    <x-filament::icon icon="heroicon-m-clipboard" class="w-3.5 h-3.5" x-show="!isCopied('qty_penagihan')" />
                    <span x-text="copiedField === 'qty_penagihan' ? 'Disalin!' : (isCopied('qty_penagihan') ? 'Tersalin' : 'Salin')"></span>
                </button>
            </div>

            <!-- Step 15: Quantity di PO -->
            <div @click="copyText('{{ $record->qty_po ?? '' }}', 'qty_po')"
                 :class="{
                     'border-emerald-500/80 ring-2 ring-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/20': copiedField === 'qty_po',
                     'border-emerald-300/60 dark:border-emerald-800/50 bg-emerald-50/10 dark:bg-emerald-950/10': isCopied('qty_po') && copiedField !== 'qty_po',
                     'border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/90': !isCopied('qty_po') && copiedField !== 'qty_po'
                 }"
                 class="group relative p-3.5 rounded-xl border transition-all duration-200 hover:border-[#F47920]/60 hover:shadow-md cursor-pointer flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span :class="isCopied('qty_po') ? 'bg-emerald-600 text-white' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300'"
                          class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center font-mono font-bold text-xs transition-colors">
                        <template x-if="isCopied('qty_po')">
                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                        </template>
                        <template x-if="!isCopied('qty_po')">
                            <span>15</span>
                        </template>
                    </span>
                    <div class="min-w-0">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Quantity di PO</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-white truncate block text-sm sm:text-base mt-0.5">
                            {{ $record->qty_po ?? '-' }} <span class="text-xs text-slate-500 font-sans font-semibold">{{ $record->uoi ?? '' }}</span>
                        </span>
                    </div>
                </div>
                <button type="button" 
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                        :class="copiedField === 'qty_po' ? 'bg-emerald-600 text-white' : (isCopied('qty_po') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 group-hover:bg-[#F47920] group-hover:text-white text-slate-700 dark:bg-slate-800 dark:text-slate-200')">
                    <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" x-show="isCopied('qty_po')" />
                    <x-filament::icon icon="heroicon-m-clipboard" class="w-3.5 h-3.5" x-show="!isCopied('qty_po')" />
                    <span x-text="copiedField === 'qty_po' ? 'Disalin!' : (isCopied('qty_po') ? 'Tersalin' : 'Salin')"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- =========================================================
         6. GRUP 5: INFORMASI VENDOR & IDENTITAS MATERIAL
         ========================================================= -->
    <div class="space-y-2.5">
        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
            <span class="w-2 h-2 rounded-full bg-slate-500"></span>
            <span>5. Informasi Rekanan & Material Barang</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <!-- Step 16: Nama Vendor -->
            <div @click="copyText('{{ addslashes($record->vendor_name ?? '') }}', 'vendor_name')"
                 :class="{
                     'border-emerald-500/80 ring-2 ring-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/20': copiedField === 'vendor_name',
                     'border-emerald-300/60 dark:border-emerald-800/50 bg-emerald-50/10 dark:bg-emerald-950/10': isCopied('vendor_name') && copiedField !== 'vendor_name',
                     'border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/90': !isCopied('vendor_name') && copiedField !== 'vendor_name'
                 }"
                 class="group relative p-3.5 rounded-xl border transition-all duration-200 hover:border-[#F47920]/60 hover:shadow-md cursor-pointer flex items-center justify-between gap-3 {{ $materialCode ? '' : 'md:col-span-2' }}">
                <div class="flex items-center gap-3 min-w-0">
                    <span :class="isCopied('vendor_name') ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300'"
                          class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center font-mono font-bold text-xs transition-colors">
                        <template x-if="isCopied('vendor_name')">
                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                        </template>
                        <template x-if="!isCopied('vendor_name')">
                            <span>16</span>
                        </template>
                    </span>
                    <div class="min-w-0">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Nama Vendor</span>
                        <span class="font-bold text-slate-900 dark:text-white truncate block text-sm sm:text-base mt-0.5">{{ $record->vendor_name ?? '-' }}</span>
                    </div>
                </div>
                <button type="button" 
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                        :class="copiedField === 'vendor_name' ? 'bg-emerald-600 text-white' : (isCopied('vendor_name') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 group-hover:bg-[#F47920] group-hover:text-white text-slate-700 dark:bg-slate-800 dark:text-slate-200')">
                    <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" x-show="isCopied('vendor_name')" />
                    <x-filament::icon icon="heroicon-m-clipboard" class="w-3.5 h-3.5" x-show="!isCopied('vendor_name')" />
                    <span x-text="copiedField === 'vendor_name' ? 'Disalin!' : (isCopied('vendor_name') ? 'Tersalin' : 'Salin')"></span>
                </button>
            </div>

            <!-- Step 17: Nama Barang / Material (Col span 2) -->
            <div @click="copyText('{{ addslashes($materialName !== '-' ? $materialName : '') }}', 'nama_barang')"
                 :class="{
                     'border-emerald-500/80 ring-2 ring-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/20': copiedField === 'nama_barang',
                     'border-emerald-300/60 dark:border-emerald-800/50 bg-emerald-50/10 dark:bg-emerald-950/10': isCopied('nama_barang') && copiedField !== 'nama_barang',
                     'border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/90': !isCopied('nama_barang') && copiedField !== 'nama_barang'
                 }"
                 class="group relative p-3.5 rounded-xl border transition-all duration-200 hover:border-[#F47920]/60 hover:shadow-md cursor-pointer flex items-center justify-between gap-3 {{ $materialCode ? '' : 'md:col-span-2' }}">
                <div class="flex items-center gap-3 min-w-0">
                    <span :class="isCopied('nama_barang') ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300'"
                          class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center font-mono font-bold text-xs transition-colors">
                        <template x-if="isCopied('nama_barang')">
                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                        </template>
                        <template x-if="!isCopied('nama_barang')">
                            <span>17</span>
                        </template>
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Material / Nama Barang</span>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $materialTypeLabel === \App\Models\InvoicingReceipt::DEFAULT_MATERIAL_TYPE ? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' }}">
                                {{ $materialTypeLabel }}
                            </span>
                        </div>
                        <span class="font-bold text-slate-900 dark:text-white truncate block text-sm mt-0.5" title="{{ $materialName }}">{{ $materialName }}</span>
                    </div>
                </div>
                <button type="button" 
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                        :class="copiedField === 'nama_barang' ? 'bg-emerald-600 text-white' : (isCopied('nama_barang') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 group-hover:bg-[#F47920] group-hover:text-white text-slate-700 dark:bg-slate-800 dark:text-slate-200')">
                    <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" x-show="isCopied('nama_barang')" />
                    <x-filament::icon icon="heroicon-m-clipboard" class="w-3.5 h-3.5" x-show="!isCopied('nama_barang')" />
                    <span x-text="copiedField === 'nama_barang' ? 'Disalin!' : (isCopied('nama_barang') ? 'Tersalin' : 'Salin')"></span>
                </button>
            </div>

            @if ($materialCode)
                <!-- Step 18: Kode Material (Optional) -->
                <div @click="copyText('{{ addslashes($materialCode) }}', 'material_code')"
                     :class="{
                         'border-emerald-500/80 ring-2 ring-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/20': copiedField === 'material_code',
                         'border-emerald-300/60 dark:border-emerald-800/50 bg-emerald-50/10 dark:bg-emerald-950/10': isCopied('material_code') && copiedField !== 'material_code',
                         'border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/90': !isCopied('material_code') && copiedField !== 'material_code'
                     }"
                     class="group relative p-3.5 rounded-xl border transition-all duration-200 hover:border-[#F47920]/60 hover:shadow-md cursor-pointer flex items-center justify-between gap-3 md:col-span-2">
                    <div class="flex items-center gap-3 min-w-0">
                        <span :class="isCopied('material_code') ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300'"
                              class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center font-mono font-bold text-xs transition-colors">
                            <template x-if="isCopied('material_code')">
                                <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                            </template>
                            <template x-if="!isCopied('material_code')">
                                <span>18</span>
                            </template>
                        </span>
                        <div class="min-w-0">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Kode Material</span>
                            <span class="font-bold font-mono text-slate-900 dark:text-white truncate block text-sm sm:text-base mt-0.5">{{ $materialCode }}</span>
                        </div>
                    </div>
                    <button type="button" 
                            class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                            :class="copiedField === 'material_code' ? 'bg-emerald-600 text-white' : (isCopied('material_code') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 group-hover:bg-[#F47920] group-hover:text-white text-slate-700 dark:bg-slate-800 dark:text-slate-200')">
                        <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" x-show="isCopied('material_code')" />
                        <x-filament::icon icon="heroicon-m-clipboard" class="w-3.5 h-3.5" x-show="!isCopied('material_code')" />
                        <span x-text="copiedField === 'material_code' ? 'Disalin!' : (isCopied('material_code') ? 'Tersalin' : 'Salin')"></span>
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
