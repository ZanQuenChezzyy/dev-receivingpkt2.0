<x-filament-panels::page>
    @php
        $zimbraStatus = $this->getZimbraStatus();
        $placeholders = $this->getPlaceholders();
    @endphp

    <div class="space-y-6">
        {{-- Status Koneksi Zimbra & Mode Uji Coba --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200/80 bg-white/75 p-4 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-slate-900/60">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Server Zimbra PKT
                        </p>
                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">
                            {{ $zimbraStatus['username'] ?: 'Akun belum dikonfigurasi' }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            Endpoint: <span class="font-mono">{{ $zimbraStatus['base_url'] }}</span>
                        </p>
                    </div>
                    @if ($zimbraStatus['configured'] && $zimbraStatus['enabled'])
                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300">
                            Siap Digunakan
                        </span>
                    @elseif (! $zimbraStatus['configured'])
                        <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-950/70 dark:text-rose-300">
                            Kredensial Belum Diisi
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-950/70 dark:text-amber-300">
                            ZIMBRA_ENABLED=false
                        </span>
                    @endif
                </div>
            </div>

            <div class="rounded-2xl border p-4 shadow-sm backdrop-blur-md {{ $zimbraStatus['test_override_to'] ? 'border-amber-300/80 bg-amber-50/80 dark:border-amber-700/60 dark:bg-amber-950/35' : 'border-slate-200/80 bg-white/75 dark:border-white/10 dark:bg-slate-900/60' }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider {{ $zimbraStatus['test_override_to'] ? 'text-amber-700 dark:text-amber-300' : 'text-slate-500 dark:text-slate-400' }}">
                            Mode Tujuan Pengiriman
                        </p>
                        @if ($zimbraStatus['test_override_to'])
                            <p class="mt-1 text-sm font-semibold text-amber-900 dark:text-amber-200">
                                Mode Uji Coba Aktif
                            </p>
                            <p class="mt-0.5 text-xs text-amber-800 dark:text-amber-300">
                                Seluruh email sementara dialihkan ke: <span class="font-mono font-semibold">{{ $zimbraStatus['test_override_to'] }}</span>
                            </p>
                        @else
                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">
                                Mode Normal (Langsung ke Vendor)
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                Email dikirim ke alamat email vendor yang tercatat pada Invoice Barang / Data Vendor.
                            </p>
                        @endif
                    </div>
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $zimbraStatus['test_override_to'] ? 'bg-amber-200/80 text-amber-900 dark:bg-amber-900/70 dark:text-amber-200' : 'bg-blue-100 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300' }}">
                        {{ $zimbraStatus['test_override_to'] ? 'Uji Coba' : 'Produksi' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Form Editor Template --}}
        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex items-center justify-end gap-3">
                <x-filament::button type="submit" color="primary" icon="heroicon-o-check">
                    Simpan Template & Pengaturan
                </x-filament::button>
            </div>
        </form>

        {{-- Panduan Variabel Dinamis Siap Salin --}}
        <div
            class="rounded-2xl border border-slate-200/80 bg-white/75 p-5 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-slate-900/60"
            x-data="{
                copiedToken: null,
                copyToken(token) {
                    navigator.clipboard.writeText(token);
                    this.copiedToken = token;
                    setTimeout(() => {
                        if (this.copiedToken === token) {
                            this.copiedToken = null;
                        }
                    }, 1500);
                }
            }"
        >
            <div class="mb-3">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                    Panduan Variabel Dinamis (Klik untuk Menyalin)
                </h3>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    Tempelkan kode variabel di bawah ini pada Subjek atau Isi Surat agar otomatis terisi sesuai data Invoice Barang yang sedang diverifikasi.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($placeholders as $token => $label)
                    <button
                        type="button"
                        @click="copyToken('{{ $token }}')"
                        class="flex items-center justify-between gap-2 rounded-xl border border-slate-200/80 bg-slate-50/75 px-3 py-2 text-left transition hover:border-orange-400/70 hover:bg-orange-50/50 dark:border-white/10 dark:bg-white/[0.04] dark:hover:border-orange-500/60 dark:hover:bg-orange-500/10"
                    >
                        <div class="min-w-0">
                            <div class="font-mono text-xs font-bold text-orange-600 dark:text-orange-400">
                                {{ $token }}
                            </div>
                            <div class="truncate text-[11px] text-slate-600 dark:text-slate-300">
                                {{ $label }}
                            </div>
                        </div>
                        <span
                            class="shrink-0 rounded-md px-2 py-0.5 text-[10px] font-semibold"
                            :class="copiedToken === '{{ $token }}'
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                : 'bg-slate-200/80 text-slate-600 dark:bg-white/10 dark:text-slate-300'"
                            x-text="copiedToken === '{{ $token }}' ? 'Disalin' : 'Salin'"
                        ></span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
