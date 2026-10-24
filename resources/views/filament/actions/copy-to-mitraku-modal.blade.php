<div class="space-y-4" x-data="{
    copiedField: null,
    copyText(text, fieldName) {
        if (!text || text === '-') {
            $wire.dispatch('notify', {
                status: 'warning',
                message: 'Nilai data kosong'
            });
            return;
        }
        navigator.clipboard.writeText(text).then(() => {
            this.copiedField = fieldName;
            setTimeout(() => { this.copiedField = null; }, 2000);
        });
    },
    copyAllTsv() {
        const row = [
            '{{ addslashes($record->no_po ?? '') }}',
            '{{ addslashes($record->no_do ?? '') }}',
            '{{ addslashes($record->no_grs ?? ($record->no_rdtv ?? '')) }}',
            '{{ addslashes($record->no_invoice ?? '') }}',
            '{{ addslashes($record->faktur_pajak ?? '') }}',
            '{{ $record->subtotal_exclude_ppn ? number_format($record->subtotal_exclude_ppn, 2, '.', '') : '' }}',
            '{{ $record->subtotal_include_ppn ? number_format($record->subtotal_include_ppn, 2, '.', '') : '' }}',
            '{{ $record->qty_penagihan ?? '' }}',
            '{{ addslashes($record->uoi ?? '') }}',
            '{{ addslashes($record->vendor_name ?? '') }}',
            '{{ addslashes($record->nama_barang ?? '') }}'
        ].join('\t');

        navigator.clipboard.writeText(row).then(() => {
            this.copiedField = '__ALL__';
            setTimeout(() => { this.copiedField = null; }, 2500);
        });
    }
}">
    <!-- Banner Utama: Salin Format Excel / Mitraku -->
    <div class="p-4 rounded-xl border border-primary-200 dark:border-primary-800 bg-primary-50/70 dark:bg-primary-950/40 flex flex-col md:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-lg bg-primary-600 text-white shadow-sm">
                <x-filament::icon icon="heroicon-m-clipboard-document-check" class="w-6 h-6" />
            </div>
            <div>
                <h4 class="font-semibold text-gray-900 dark:text-white text-base">Salin Cepat Format Mitraku PKT</h4>
                <p class="text-xs text-gray-600 dark:text-gray-400">Salin seluruh data baris ini (Tab-Separated) untuk langsung di-paste ke tabel Mitraku / Excel.</p>
            </div>
        </div>
        <button
            type="button"
            @click="copyAllTsv()"
            :class="copiedField === '__ALL__' ? 'bg-success-600 text-white' : 'bg-primary-600 hover:bg-primary-700 text-white'"
            class="px-4 py-2.5 rounded-lg font-medium text-sm transition-all duration-200 shadow-sm flex items-center gap-2 shrink-0">
            <template x-if="copiedField === '__ALL__'">
                <div class="flex items-center gap-1.5 font-bold">
                    <x-filament::icon icon="heroicon-m-check" class="w-5 h-5" />
                    <span>Semua Data Disalin!</span>
                </div>
            </template>
            <template x-if="copiedField !== '__ALL__'">
                <div class="flex items-center gap-1.5">
                    <x-filament::icon icon="heroicon-m-document-duplicate" class="w-5 h-5" />
                    <span>Salin Semua Baris (TSV)</span>
                </div>
            </template>
        </button>
    </div>

    <!-- Grid Kartu Data Satuan (Single-click Copy) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <!-- No. PO -->
        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between shadow-xs">
            <div class="min-w-0 pr-2">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Nomor PO</span>
                <span class="font-bold text-gray-900 dark:text-white truncate block text-sm">{{ $record->no_po ?? '-' }}</span>
            </div>
            <button
                type="button"
                @click="copyText('{{ addslashes($record->no_po ?? '') }}', 'no_po')"
                :class="copiedField === 'no_po' ? 'bg-success-100 text-success-700 dark:bg-success-900/60 dark:text-success-300' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200'"
                class="px-2.5 py-1.5 rounded-md text-xs font-medium transition shrink-0 flex items-center gap-1">
                <x-filament::icon :icon="copiedField === 'no_po' ? 'heroicon-m-check' : 'heroicon-m-clipboard'" class="w-4 h-4" />
                <span x-text="copiedField === 'no_po' ? 'Disalin' : 'Salin'"></span>
            </button>
        </div>

        <!-- No. DO -->
        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between shadow-xs">
            <div class="min-w-0 pr-2">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Nomor DO</span>
                <span class="font-bold text-gray-900 dark:text-white truncate block text-sm">{{ $record->no_do ?? '-' }}</span>
            </div>
            <button
                type="button"
                @click="copyText('{{ addslashes($record->no_do ?? '') }}', 'no_do')"
                :class="copiedField === 'no_do' ? 'bg-success-100 text-success-700 dark:bg-success-900/60 dark:text-success-300' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200'"
                class="px-2.5 py-1.5 rounded-md text-xs font-medium transition shrink-0 flex items-center gap-1">
                <x-filament::icon :icon="copiedField === 'no_do' ? 'heroicon-m-check' : 'heroicon-m-clipboard'" class="w-4 h-4" />
                <span x-text="copiedField === 'no_do' ? 'Disalin' : 'Salin'"></span>
            </button>
        </div>

        <!-- No. GRS / RDTV -->
        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between shadow-xs">
            <div class="min-w-0 pr-2">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">No. {{ $record->jenis ?? 'GRS' }}</span>
                <span class="font-bold text-gray-900 dark:text-white truncate block text-sm">{{ $record->no_grs ?? ($record->no_rdtv ?? '-') }}</span>
            </div>
            <button
                type="button"
                @click="copyText('{{ addslashes($record->no_grs ?? ($record->no_rdtv ?? '')) }}', 'no_grs')"
                :class="copiedField === 'no_grs' ? 'bg-success-100 text-success-700 dark:bg-success-900/60 dark:text-success-300' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200'"
                class="px-2.5 py-1.5 rounded-md text-xs font-medium transition shrink-0 flex items-center gap-1">
                <x-filament::icon :icon="copiedField === 'no_grs' ? 'heroicon-m-check' : 'heroicon-m-clipboard'" class="w-4 h-4" />
                <span x-text="copiedField === 'no_grs' ? 'Disalin' : 'Salin'"></span>
            </button>
        </div>

        <!-- No. Invoice -->
        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between shadow-xs">
            <div class="min-w-0 pr-2">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Nomor Invoice</span>
                <span class="font-bold text-gray-900 dark:text-white truncate block text-sm">{{ $record->no_invoice ?: '-' }}</span>
            </div>
            <button
                type="button"
                @click="copyText('{{ addslashes($record->no_invoice ?? '') }}', 'no_invoice')"
                :class="copiedField === 'no_invoice' ? 'bg-success-100 text-success-700 dark:bg-success-900/60 dark:text-success-300' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200'"
                class="px-2.5 py-1.5 rounded-md text-xs font-medium transition shrink-0 flex items-center gap-1">
                <x-filament::icon :icon="copiedField === 'no_invoice' ? 'heroicon-m-check' : 'heroicon-m-clipboard'" class="w-4 h-4" />
                <span x-text="copiedField === 'no_invoice' ? 'Disalin' : 'Salin'"></span>
            </button>
        </div>

        <!-- Faktur Pajak -->
        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between shadow-xs">
            <div class="min-w-0 pr-2">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Faktur Pajak</span>
                <span class="font-bold text-gray-900 dark:text-white truncate block text-sm">{{ $record->faktur_pajak ?: '-' }}</span>
            </div>
            <button
                type="button"
                @click="copyText('{{ addslashes($record->faktur_pajak ?? '') }}', 'faktur_pajak')"
                :class="copiedField === 'faktur_pajak' ? 'bg-success-100 text-success-700 dark:bg-success-900/60 dark:text-success-300' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200'"
                class="px-2.5 py-1.5 rounded-md text-xs font-medium transition shrink-0 flex items-center gap-1">
                <x-filament::icon :icon="copiedField === 'faktur_pajak' ? 'heroicon-m-check' : 'heroicon-m-clipboard'" class="w-4 h-4" />
                <span x-text="copiedField === 'faktur_pajak' ? 'Disalin' : 'Salin'"></span>
            </button>
        </div>

        <!-- Sub Total (Exclude PPN) -->
        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between shadow-xs">
            <div class="min-w-0 pr-2">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Subtotal (Exclude PPN)</span>
                <span class="font-bold text-primary-600 dark:text-primary-400 truncate block text-sm">
                    {{ $record->subtotal_exclude_ppn !== null ? 'Rp ' . number_format($record->subtotal_exclude_ppn, 2, ',', '.') : '-' }}
                </span>
            </div>
            <button
                type="button"
                @click="copyText('{{ $record->subtotal_exclude_ppn ? number_format($record->subtotal_exclude_ppn, 2, '.', '') : '' }}', 'subtotal_exclude')"
                :class="copiedField === 'subtotal_exclude' ? 'bg-success-100 text-success-700 dark:bg-success-900/60 dark:text-success-300' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200'"
                class="px-2.5 py-1.5 rounded-md text-xs font-medium transition shrink-0 flex items-center gap-1">
                <x-filament::icon :icon="copiedField === 'subtotal_exclude' ? 'heroicon-m-check' : 'heroicon-m-clipboard'" class="w-4 h-4" />
                <span x-text="copiedField === 'subtotal_exclude' ? 'Disalin' : 'Salin'"></span>
            </button>
        </div>

        <!-- Sub Total (Include PPN) -->
        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between shadow-xs">
            <div class="min-w-0 pr-2">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Subtotal (Include PPN)</span>
                <span class="font-bold text-success-600 dark:text-success-400 truncate block text-sm">
                    {{ $record->subtotal_include_ppn !== null ? 'Rp ' . number_format($record->subtotal_include_ppn, 2, ',', '.') : '-' }}
                </span>
            </div>
            <button
                type="button"
                @click="copyText('{{ $record->subtotal_include_ppn ? number_format($record->subtotal_include_ppn, 2, '.', '') : '' }}', 'subtotal_include')"
                :class="copiedField === 'subtotal_include' ? 'bg-success-100 text-success-700 dark:bg-success-900/60 dark:text-success-300' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200'"
                class="px-2.5 py-1.5 rounded-md text-xs font-medium transition shrink-0 flex items-center gap-1">
                <x-filament::icon :icon="copiedField === 'subtotal_include' ? 'heroicon-m-check' : 'heroicon-m-clipboard'" class="w-4 h-4" />
                <span x-text="copiedField === 'subtotal_include' ? 'Disalin' : 'Salin'"></span>
            </button>
        </div>

        <!-- Quantity & UOI -->
        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between shadow-xs">
            <div class="min-w-0 pr-2">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Quantity Penagihan</span>
                <span class="font-bold text-gray-900 dark:text-white truncate block text-sm">
                    {{ $record->qty_penagihan ?? '-' }} {{ $record->uoi ?? '' }}
                </span>
            </div>
            <button
                type="button"
                @click="copyText('{{ $record->qty_penagihan ?? '' }}', 'qty')"
                :class="copiedField === 'qty' ? 'bg-success-100 text-success-700 dark:bg-success-900/60 dark:text-success-300' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200'"
                class="px-2.5 py-1.5 rounded-md text-xs font-medium transition shrink-0 flex items-center gap-1">
                <x-filament::icon :icon="copiedField === 'qty' ? 'heroicon-m-check' : 'heroicon-m-clipboard'" class="w-4 h-4" />
                <span x-text="copiedField === 'qty' ? 'Disalin' : 'Salin'"></span>
            </button>
        </div>

        <!-- Nama Vendor -->
        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between shadow-xs sm:col-span-2 lg:col-span-1">
            <div class="min-w-0 pr-2">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Nama Vendor</span>
                <span class="font-bold text-gray-900 dark:text-white truncate block text-sm">{{ $record->vendor_name ?? '-' }}</span>
            </div>
            <button
                type="button"
                @click="copyText('{{ addslashes($record->vendor_name ?? '') }}', 'vendor_name')"
                :class="copiedField === 'vendor_name' ? 'bg-success-100 text-success-700 dark:bg-success-900/60 dark:text-success-300' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200'"
                class="px-2.5 py-1.5 rounded-md text-xs font-medium transition shrink-0 flex items-center gap-1">
                <x-filament::icon :icon="copiedField === 'vendor_name' ? 'heroicon-m-check' : 'heroicon-m-clipboard'" class="w-4 h-4" />
                <span x-text="copiedField === 'vendor_name' ? 'Disalin' : 'Salin'"></span>
            </button>
        </div>
    </div>
</div>
