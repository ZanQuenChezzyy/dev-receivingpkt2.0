<div x-data="{ tab: 'GRS' }" class="space-y-4">
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3 dark:border-white/10">
        <button
            type="button"
            @click="tab = 'GRS'"
            class="rounded-xl px-3.5 py-2 text-xs font-semibold transition"
            :class="tab === 'GRS'
                ? 'bg-orange-500 text-white shadow-sm'
                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10'"
        >
            Pratinjau GRS (105)
        </button>
        <button
            type="button"
            @click="tab = 'RDTV'"
            class="rounded-xl px-3.5 py-2 text-xs font-semibold transition"
            :class="tab === 'RDTV'
                ? 'bg-rose-600 text-white shadow-sm'
                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10'"
        >
            Pratinjau RDTV (124)
        </button>
    </div>

    {{-- Preview GRS --}}
    <div x-show="tab === 'GRS'" class="space-y-3">
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs dark:border-white/10 dark:bg-white/5">
            <div><span class="font-semibold text-slate-500 dark:text-slate-400">Subjek:</span> <span class="font-semibold text-slate-900 dark:text-white">{{ $grsPreview['subject'] }}</span></div>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="font-semibold text-slate-500 dark:text-slate-400">Lampiran (2 PDF):</span>
                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 font-mono text-[11px] font-medium text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">GRS_5007712345.pdf</span>
                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 font-mono text-[11px] font-medium text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">Verifikasi_GRS_5007712345.pdf</span>
            </div>
        </div>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-white/10">
            <iframe
                srcdoc="{{ $grsPreview['wrapped_html'] }}"
                class="h-[420px] w-full border-0"
                sandbox="allow-same-origin"
            ></iframe>
        </div>
    </div>

    {{-- Preview RDTV --}}
    <div x-show="tab === 'RDTV'" x-cloak class="space-y-3">
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs dark:border-white/10 dark:bg-white/5">
            <div><span class="font-semibold text-slate-500 dark:text-slate-400">Subjek:</span> <span class="font-semibold text-slate-900 dark:text-white">{{ $rdtvPreview['subject'] }}</span></div>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="font-semibold text-slate-500 dark:text-slate-400">Lampiran (2 PDF):</span>
                <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 font-mono text-[11px] font-medium text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">RDTV_5007798765.pdf</span>
                <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 font-mono text-[11px] font-medium text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">Verifikasi_RDTV_5007798765.pdf</span>
            </div>
        </div>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-white/10">
            <iframe
                srcdoc="{{ $rdtvPreview['wrapped_html'] }}"
                class="h-[420px] w-full border-0"
                sandbox="allow-same-origin"
            ></iframe>
        </div>
    </div>
</div>
