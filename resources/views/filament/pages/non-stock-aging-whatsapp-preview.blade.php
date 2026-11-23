<div
    x-data="{
        copied: false,
        copyText() {
            navigator.clipboard.writeText($refs.waText.value).then(() => {
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            });
        }
    }"
    class="space-y-3"
>
    <div class="text-xs text-gray-600 dark:text-gray-300">
        Penerima: <strong>{{ $recipientName ?: 'Belum diisi' }}</strong>
        @if ($phone)
            &bull; Nomor WA: <span class="font-mono font-semibold">{{ $phone }}</span>
        @else
            &bull; <span class="text-amber-600">Nomor HP belum terisi (salin pesan secara manual).</span>
        @endif
    </div>

    <textarea
        x-ref="waText"
        rows="11"
        readonly
        class="w-full rounded-lg border border-gray-300 bg-gray-50 p-3 font-mono text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
    >{{ $message }}</textarea>

    <div class="flex flex-wrap items-center gap-2">
        <button
            type="button"
            @click="copyText()"
            class="inline-flex items-center gap-1.5 rounded-lg bg-gray-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-700 dark:bg-gray-200 dark:text-gray-900"
        >
            <span x-show="!copied">Salin Pesan WhatsApp</span>
            <span x-show="copied" x-cloak>Tersalin ke Clipboard</span>
        </button>

        @if ($waUrl)
            <a
                href="{{ $waUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500"
            >
                Buka WhatsApp (wa.me)
            </a>
        @endif
    </div>
</div>
