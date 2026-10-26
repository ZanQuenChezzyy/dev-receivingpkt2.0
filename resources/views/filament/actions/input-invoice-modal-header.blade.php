<div class="mb-2 space-y-3">
    <!-- Header Card Ringkasan PO & Vendor -->
    <div class="p-4 rounded-xl border border-primary-200 dark:border-primary-800/60 bg-gradient-to-r from-primary-50/80 via-white to-amber-50/60 dark:from-gray-900/90 dark:via-gray-800/80 dark:to-gray-900/90 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-200/80 dark:border-gray-700/80">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-primary-600 text-white shadow-xs shrink-0">
                    <x-filament::icon icon="heroicon-m-pencil-square" class="w-6 h-6" />
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-semibold uppercase tracking-wider text-primary-700 dark:text-primary-400">
                            PO Terbit
                        </span>
                        <span class="font-bold text-gray-900 dark:text-white text-base font-mono">
                            {{ $record->no_po ?? '-' }}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold {{ ($record->jenis ?? 'GRS') === 'GRS' ? 'bg-success-100 text-success-700 dark:bg-success-900/60 dark:text-success-300' : 'bg-warning-100 text-warning-700 dark:bg-warning-900/60 dark:text-warning-300' }}">
                            {{ $record->jenis ?? 'GRS' }}
                        </span>
                        @if ($record->material_type)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                {{ $record->material_type }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                        Lengkapi dokumen resmi penagihan vendor dan nomor slip GRS fisik dari SAP.
                    </p>
                </div>
            </div>

            <!-- Status Badge -->
            <div class="shrink-0 flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400">Status:</span>
                @php
                    $status = $record->status ?? 'Menunggu Verifikasi';
                    $statusClasses = match ($status) {
                        'Terverifikasi' => 'bg-info-100 text-info-700 dark:bg-info-900/60 dark:text-info-300 border-info-200 dark:border-info-800',
                        'Terkirim Kasir' => 'bg-success-100 text-success-700 dark:bg-success-900/60 dark:text-success-300 border-success-200 dark:border-success-800',
                        default => 'bg-warning-100 text-warning-700 dark:bg-warning-900/60 dark:text-warning-300 border-warning-200 dark:border-warning-800',
                    };
                @endphp
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border {{ $statusClasses }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                    {{ $status }}
                </span>
            </div>
        </div>

        <!-- 4 Kotak Ringkasan Informasi Cepat -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 pt-3">
            <!-- Vendor -->
            <div class="p-2.5 rounded-lg bg-white/80 dark:bg-gray-800/80 border border-gray-200/70 dark:border-gray-700/60">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-1">
                    <x-filament::icon icon="heroicon-m-building-office" class="w-3.5 h-3.5 text-gray-400" />
                    Vendor
                </span>
                <span class="font-semibold text-gray-900 dark:text-gray-100 text-xs truncate block mt-0.5" title="{{ $record->vendor_name ?? '-' }}">
                    {{ $record->vendor_name ?? '-' }}
                </span>
            </div>

            <!-- No. DO -->
            <div class="p-2.5 rounded-lg bg-white/80 dark:bg-gray-800/80 border border-gray-200/70 dark:border-gray-700/60">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-1">
                    <x-filament::icon icon="heroicon-m-truck" class="w-3.5 h-3.5 text-gray-400" />
                    Nomor DO
                </span>
                <span class="font-semibold text-gray-900 dark:text-gray-100 text-xs truncate block mt-0.5">
                    {{ $record->no_do ?? '-' }}
                </span>
            </div>

            <!-- Material / Barang -->
            <div class="p-2.5 rounded-lg bg-white/80 dark:bg-gray-800/80 border border-gray-200/70 dark:border-gray-700/60">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-1">
                    <x-filament::icon icon="heroicon-m-cube" class="w-3.5 h-3.5 text-gray-400" />
                    Nama Material
                </span>
                <span class="font-semibold text-gray-900 dark:text-gray-100 text-xs truncate block mt-0.5" title="{{ $record->nama_barang ?? ($record->deskripsi ?? '-') }}">
                    {{ $record->nama_barang ?? ($record->deskripsi ?? '-') }}
                </span>
            </div>

            <!-- PIC Staff Invoice -->
            <div class="p-2.5 rounded-lg bg-white/80 dark:bg-gray-800/80 border border-gray-200/70 dark:border-gray-700/60">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-1">
                    <x-filament::icon icon="heroicon-m-user" class="w-3.5 h-3.5 text-gray-400" />
                    PIC Staff Invoice
                </span>
                <span class="font-semibold text-gray-900 dark:text-gray-100 text-xs truncate block mt-0.5">
                    {{ $record->pic?->name ?? 'Belum Ditugaskan' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Alert / Petunjuk Kerja Khusus -->
    <div class="p-3 rounded-lg border border-amber-200 dark:border-amber-800/60 bg-amber-50/70 dark:bg-amber-950/30 flex items-start gap-2.5 text-xs text-amber-900 dark:text-amber-200">
        <x-filament::icon icon="heroicon-m-information-circle" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
        <div class="leading-relaxed">
            <span class="font-semibold">Catatan Penting:</span>
            Pastikan <strong>No. GRS</strong> diisi dengan nomor resmi dari sistem SAP/slip fisik GRS (bukan kode dokumen berkas).
            Nilai <strong>Sub Total (Include PPN)</strong> akan terhitung otomatis sebesar <strong>11%</strong> dari nilai Exclude PPN.
        </div>
    </div>
</div>
