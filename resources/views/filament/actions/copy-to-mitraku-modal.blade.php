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

    $subtotalExcludeCopy = $formatSubtotalForCopy($record->subtotal_exclude_ppn ?? null);
    $subtotalIncludeCopy = $formatSubtotalForCopy($record->subtotal_include_ppn ?? null);

    $materialName = $record->nama_barang ?: ($record->deskripsi ?: '-');
    $materialCode = $record->purchaseOrderIssued?->material_code ?? $record->deliveryOrderReceipt?->deliveryOrderReceiptDetails?->first()?->material_code;

    $rawDeliveryDate = $record->delivery_date_po ?? $record->purchaseOrderIssued?->delivery_date_po;
    $deliveryDatePoFormatted = $rawDeliveryDate ? \Illuminate\Support\Carbon::parse($rawDeliveryDate)->format('d-m-Y') : '';
@endphp

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
            '{{ $deliveryDatePoFormatted }}',
            '{{ addslashes($record->no_do ?? '') }}',
            '{{ addslashes($record->no_grs ?? ($record->no_rdtv ?? '')) }}',
            '{{ addslashes($record->no_invoice ?? '') }}',
            '{{ addslashes($record->faktur_pajak ?? '') }}',
            '{{ $subtotalExcludeCopy }}',
            '{{ $subtotalIncludeCopy }}',
            '{{ $record->qty_penagihan ?? '' }}',
            '{{ addslashes($record->uoi ?? '') }}',
            '{{ addslashes($record->vendor_name ?? '') }}',
            '{{ addslashes($materialName !== '-' ? $materialName : '') }}'
        ].join('\t');

        navigator.clipboard.writeText(row).then(() => {
            this.copiedField = '__ALL__';
            setTimeout(() => { this.copiedField = null; }, 2500);
        });
    }
}">
    <!-- Baris Aksi Header Cepat -->
    <div class="flex items-center justify-between gap-2 pb-1 border-b border-gray-100 dark:border-gray-800">
        <span class="text-xs text-gray-500 dark:text-gray-400">Klik <strong>Salin</strong> pada data yang ingin di-paste ke portal Mitraku:</span>
        <button
            type="button"
            @click="copyAllTsv()"
            :class="copiedField === '__ALL__' ? 'bg-success-600 text-white' : 'bg-primary-50 dark:bg-primary-950/50 text-primary-700 dark:text-primary-300 hover:bg-primary-100 dark:hover:bg-primary-900/60 border border-primary-200 dark:border-primary-800'"
            class="px-2.5 py-1 rounded-md text-xs font-medium transition flex items-center gap-1.5 shrink-0 shadow-xs">
            <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" x-show="copiedField === '__ALL__'" x-cloak />
            <x-filament::icon icon="heroicon-m-document-duplicate" class="w-3.5 h-3.5" x-show="copiedField !== '__ALL__'" />
            <span x-text="copiedField === '__ALL__' ? 'Semua Baris Disalin!' : 'Salin Semua (TSV)'"></span>
        </button>
    </div>

    <!-- Grid Kartu Data Satuan (Single-Click Copy) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <!-- Nomor PO -->
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
                <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" x-show="copiedField === 'no_po'" x-cloak />
                <x-filament::icon icon="heroicon-m-clipboard" class="w-4 h-4" x-show="copiedField !== 'no_po'" />
                <span x-text="copiedField === 'no_po' ? 'Disalin' : 'Salin'"></span>
            </button>
        </div>

        <!-- Delivery Date PO -->
        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between shadow-xs">
            <div class="min-w-0 pr-2">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Delivery Date PO</span>
                <span class="font-bold text-gray-900 dark:text-white truncate block text-sm">{{ $deliveryDatePoFormatted ?: '-' }}</span>
            </div>
            <button
                type="button"
                @click="copyText('{{ $deliveryDatePoFormatted }}', 'delivery_date_po')"
                :class="copiedField === 'delivery_date_po' ? 'bg-success-100 text-success-700 dark:bg-success-900/60 dark:text-success-300' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200'"
                class="px-2.5 py-1.5 rounded-md text-xs font-medium transition shrink-0 flex items-center gap-1">
                <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" x-show="copiedField === 'delivery_date_po'" x-cloak />
                <x-filament::icon icon="heroicon-m-clipboard" class="w-4 h-4" x-show="copiedField !== 'delivery_date_po'" />
                <span x-text="copiedField === 'delivery_date_po' ? 'Disalin' : 'Salin'"></span>
            </button>
        </div>

        <!-- Nomor DO -->
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
                <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" x-show="copiedField === 'no_do'" x-cloak />
                <x-filament::icon icon="heroicon-m-clipboard" class="w-4 h-4" x-show="copiedField !== 'no_do'" />
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
                <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" x-show="copiedField === 'no_grs'" x-cloak />
                <x-filament::icon icon="heroicon-m-clipboard" class="w-4 h-4" x-show="copiedField !== 'no_grs'" />
                <span x-text="copiedField === 'no_grs' ? 'Disalin' : 'Salin'"></span>
            </button>
        </div>

        <!-- Nomor Invoice -->
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
                <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" x-show="copiedField === 'no_invoice'" x-cloak />
                <x-filament::icon icon="heroicon-m-clipboard" class="w-4 h-4" x-show="copiedField !== 'no_invoice'" />
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
                <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" x-show="copiedField === 'faktur_pajak'" x-cloak />
                <x-filament::icon icon="heroicon-m-clipboard" class="w-4 h-4" x-show="copiedField !== 'faktur_pajak'" />
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
                @click="copyText('{{ $subtotalExcludeCopy }}', 'subtotal_exclude')"
                :class="copiedField === 'subtotal_exclude' ? 'bg-success-100 text-success-700 dark:bg-success-900/60 dark:text-success-300' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200'"
                class="px-2.5 py-1.5 rounded-md text-xs font-medium transition shrink-0 flex items-center gap-1">
                <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" x-show="copiedField === 'subtotal_exclude'" x-cloak />
                <x-filament::icon icon="heroicon-m-clipboard" class="w-4 h-4" x-show="copiedField !== 'subtotal_exclude'" />
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
                @click="copyText('{{ $subtotalIncludeCopy }}', 'subtotal_include')"
                :class="copiedField === 'subtotal_include' ? 'bg-success-100 text-success-700 dark:bg-success-900/60 dark:text-success-300' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200'"
                class="px-2.5 py-1.5 rounded-md text-xs font-medium transition shrink-0 flex items-center gap-1">
                <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" x-show="copiedField === 'subtotal_include'" x-cloak />
                <x-filament::icon icon="heroicon-m-clipboard" class="w-4 h-4" x-show="copiedField !== 'subtotal_include'" />
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
                <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" x-show="copiedField === 'qty'" x-cloak />
                <x-filament::icon icon="heroicon-m-clipboard" class="w-4 h-4" x-show="copiedField !== 'qty'" />
                <span x-text="copiedField === 'qty' ? 'Disalin' : 'Salin'"></span>
            </button>
        </div>

        <!-- Quantity di PO -->
        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between shadow-xs">
            <div class="min-w-0 pr-2">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Quantity di PO</span>
                <span class="font-bold text-gray-900 dark:text-white truncate block text-sm">
                    {{ $record->qty_po ?? '-' }} {{ $record->uoi ?? '' }}
                </span>
            </div>
            <button
                type="button"
                @click="copyText('{{ $record->qty_po ?? '' }}', 'qty_po')"
                :class="copiedField === 'qty_po' ? 'bg-success-100 text-success-700 dark:bg-success-900/60 dark:text-success-300' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200'"
                class="px-2.5 py-1.5 rounded-md text-xs font-medium transition shrink-0 flex items-center gap-1">
                <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" x-show="copiedField === 'qty_po'" x-cloak />
                <x-filament::icon icon="heroicon-m-clipboard" class="w-4 h-4" x-show="copiedField !== 'qty_po'" />
                <span x-text="copiedField === 'qty_po' ? 'Disalin' : 'Salin'"></span>
            </button>
        </div>

        <!-- Nama Material / Barang -->
        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between shadow-xs sm:col-span-2">
            <div class="min-w-0 pr-2">
                <div class="flex items-center gap-1.5">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Material / Nama Barang</span>
                    @if ($record->material_type)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                            {{ $record->material_type }}
                        </span>
                    @endif
                </div>
                <span class="font-bold text-gray-900 dark:text-white truncate block text-sm mt-0.5" title="{{ $materialName }}">
                    {{ $materialName }}
                </span>
            </div>
            <button
                type="button"
                @click="copyText('{{ addslashes($materialName !== '-' ? $materialName : '') }}', 'nama_barang')"
                :class="copiedField === 'nama_barang' ? 'bg-success-100 text-success-700 dark:bg-success-900/60 dark:text-success-300' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200'"
                class="px-2.5 py-1.5 rounded-md text-xs font-medium transition shrink-0 flex items-center gap-1">
                <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" x-show="copiedField === 'nama_barang'" x-cloak />
                <x-filament::icon icon="heroicon-m-clipboard" class="w-4 h-4" x-show="copiedField !== 'nama_barang'" />
                <span x-text="copiedField === 'nama_barang' ? 'Disalin' : 'Salin'"></span>
            </button>
        </div>

        @if ($materialCode)
            <!-- Kode Material -->
            <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between shadow-xs">
                <div class="min-w-0 pr-2">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Kode Material</span>
                    <span class="font-bold text-gray-900 dark:text-white truncate block text-sm">{{ $materialCode }}</span>
                </div>
                <button
                    type="button"
                    @click="copyText('{{ addslashes($materialCode) }}', 'material_code')"
                    :class="copiedField === 'material_code' ? 'bg-success-100 text-success-700 dark:bg-success-900/60 dark:text-success-300' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200'"
                    class="px-2.5 py-1.5 rounded-md text-xs font-medium transition shrink-0 flex items-center gap-1">
                    <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" x-show="copiedField === 'material_code'" x-cloak />
                    <x-filament::icon icon="heroicon-m-clipboard" class="w-4 h-4" x-show="copiedField !== 'material_code'" />
                    <span x-text="copiedField === 'material_code' ? 'Disalin' : 'Salin'"></span>
                </button>
            </div>
        @endif

        <!-- Nama Vendor -->
        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between shadow-xs {{ $materialCode ? '' : 'sm:col-span-2' }}">
            <div class="min-w-0 pr-2">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Nama Vendor</span>
                <span class="font-bold text-gray-900 dark:text-white truncate block text-sm">{{ $record->vendor_name ?? '-' }}</span>
            </div>
            <button
                type="button"
                @click="copyText('{{ addslashes($record->vendor_name ?? '') }}', 'vendor_name')"
                :class="copiedField === 'vendor_name' ? 'bg-success-100 text-success-700 dark:bg-success-900/60 dark:text-success-300' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200'"
                class="px-2.5 py-1.5 rounded-md text-xs font-medium transition shrink-0 flex items-center gap-1">
                <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" x-show="copiedField === 'vendor_name'" x-cloak />
                <x-filament::icon icon="heroicon-m-clipboard" class="w-4 h-4" x-show="copiedField !== 'vendor_name'" />
                <span x-text="copiedField === 'vendor_name' ? 'Disalin' : 'Salin'"></span>
            </button>
        </div>
    </div>
</div>
