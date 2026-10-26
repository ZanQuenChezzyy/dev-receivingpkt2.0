<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @php
        $state = $getState();
        $record = $entry->getRecord();
        $checklist = \App\Livewire\Frontend\VendorPoTracking::INVOICE_CHECKLIST;

        // Check if single dossier file is present (either via dokumen_kelengkapan array or record->dokumen_invoice)
        $hasDossier = false;
        $dossierData = null;

        if (is_array($state) && isset($state['file_path'])) {
            $hasDossier = true;
            $dossierData = $state;
        } elseif (!empty($record?->dokumen_invoice)) {
            $hasDossier = true;
            $dossierData = [
                'file_path' => $record->dokumen_invoice,
                'original_name' => basename($record->dokumen_invoice),
                'file_size' => null,
                'uploaded_at' => $record->updated_at?->format('Y-m-d H:i:s'),
            ];
        }

        // Check if there is legacy per-key kelengkapan
        $isLegacyMulti = is_array($state) && !isset($state['file_path']) && !empty($state);
    @endphp

    <div class="space-y-6">
        <!-- 1. KARTU STATUS BERKAS PENAGIHAN VENDOR (SINGLE FILE) -->
        <div class="p-4 sm:p-5 rounded-2xl border @if ($hasDossier) bg-emerald-500/5 dark:bg-emerald-500/10 border-emerald-500/30 @else bg-gray-50 dark:bg-white/5 border-gray-200 dark:border-white/10 @endif">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-xl @if ($hasDossier) bg-emerald-500 text-white shadow-md shadow-emerald-500/20 @else bg-gray-200 dark:bg-white/10 text-gray-500 dark:text-gray-400 @endif flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider @if ($hasDossier) text-emerald-600 dark:text-emerald-400 @else text-gray-500 @endif">
                                Berkas Penagihan Invoice Vendor (Bundel Lengkap)
                            </span>
                            @if ($hasDossier)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400">
                                    Sudah Diunggah
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">
                                    Belum Diunggah
                                </span>
                            @endif
                        </div>

                        @if ($hasDossier)
                            <div class="font-mono font-bold text-sm text-gray-800 dark:text-gray-100 break-all">
                                {{ $dossierData['original_name'] ?? 'Berkas_Penagihan.pdf' }}
                            </div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 flex flex-wrap items-center gap-2">
                                <span>Diunggah: {{ isset($dossierData['uploaded_at']) ? \Carbon\Carbon::parse($dossierData['uploaded_at'])->format('d M Y, H:i') : '-' }}</span>
                                @if (!empty($dossierData['file_size']))
                                    <span>•</span>
                                    <span>Ukuran: {{ number_format($dossierData['file_size'] / (1024 * 1024), 2) }} MB</span>
                                @endif
                            </div>
                        @else
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Vendor belum mengunggah 1 berkas bundel penagihan invoice melalui portal self-service tracking.
                            </p>
                        @endif
                    </div>
                </div>

                @if ($hasDossier && !empty($dossierData['file_path']))
                    <div class="shrink-0 flex items-center gap-2">
                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($dossierData['file_path']) }}" target="_blank"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 shadow-sm transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            Buka / Unduh Berkas Penagihan
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- 2. TABEL PANDUAN 16 PERSYARATAN KELENGKAPAN UNTUK VERIFIKASI TIM INVOICE -->
        <div class="space-y-2">
            <div class="flex items-center justify-between text-xs font-semibold px-1">
                <span class="text-gray-700 dark:text-gray-300 font-bold">16 Dokumen Persyaratan yang Harus Dipenuhi di Dalam Berkas Penagihan:</span>
                <span class="text-gray-400 text-[11px]">No. 01–05 Wajib • No. 06–16 Kondisional / Pelengkap</span>
            </div>

            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
                <table class="w-full text-left text-xs divide-y divide-gray-200 dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5 text-gray-500 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="py-2.5 px-3 w-12 text-center">#</th>
                            <th class="py-2.5 px-3">Nama Dokumen Persyaratan</th>
                            <th class="py-2.5 px-3">Sifat / Kategori</th>
                            <th class="py-2.5 px-3">Deskripsi & Ketentuan Verifikasi</th>
                            @if ($isLegacyMulti)
                                <th class="py-2.5 px-3 text-right">Berkas Arsip</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5 bg-white dark:bg-transparent">
                        @foreach ($checklist as $key => $item)
                            @php
                                $legacyDoc = $isLegacyMulti ? ($state[$key] ?? null) : null;
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <td class="py-2 px-3 text-center font-mono font-bold text-gray-400">
                                    {{ sprintf('%02d', $item['number']) }}
                                </td>
                                <td class="py-2 px-3">
                                    <div class="font-bold text-gray-800 dark:text-gray-200">{{ $item['name'] }}</div>
                                </td>
                                <td class="py-2 px-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold
                                        @if ($item['badge_color'] === 'rose')
                                            bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400
                                        @elseif ($item['badge_color'] === 'amber')
                                            bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400
                                        @elseif ($item['badge_color'] === 'indigo')
                                            bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-400
                                        @elseif ($item['badge_color'] === 'teal')
                                            bg-teal-100 text-teal-700 dark:bg-teal-500/20 dark:text-teal-400
                                        @elseif ($item['badge_color'] === 'sky')
                                            bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-400
                                        @elseif ($item['badge_color'] === 'purple')
                                            bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-400
                                        @else
                                            bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300
                                        @endif">
                                        {{ $item['badge'] }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-gray-600 dark:text-gray-400 text-[11px] leading-relaxed">
                                    {{ $item['desc'] }}
                                </td>
                                @if ($isLegacyMulti)
                                    <td class="py-2 px-3 text-right">
                                        @if ($legacyDoc && !empty($legacyDoc['file_path']))
                                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($legacyDoc['file_path']) }}" target="_blank"
                                                class="inline-flex items-center gap-1 text-[11px] font-bold text-primary-600 hover:underline">
                                                Lihat
                                            </a>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-dynamic-component>
