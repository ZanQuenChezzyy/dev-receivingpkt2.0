<div class="space-y-4 animate-pulse" aria-busy="true" aria-label="Memuat ringkasan data">
    {{-- Skeleton 4 Kartu Ringkasan Utama --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @for ($i = 0; $i < 4; $i++)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="h-3 w-28 rounded bg-gray-200 dark:bg-gray-700"></div>
                <div class="mt-3 flex items-center justify-between">
                    <div class="h-7 w-24 rounded bg-gray-200 dark:bg-gray-700"></div>
                    <div class="h-5 w-14 rounded-md bg-gray-100 dark:bg-gray-800"></div>
                </div>
                <div class="mt-2 h-4 w-32 rounded bg-gray-200 dark:bg-gray-700"></div>
                <div class="mt-3 h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800"></div>
            </div>
        @endfor
    </div>

    {{-- Skeleton 4 Kartu Zona Aging --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach (['border-l-emerald-300', 'border-l-sky-300', 'border-l-amber-300', 'border-l-red-300'] as $border)
            <div class="rounded-xl border border-gray-200 border-l-4 {{ $border }} bg-white p-3.5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div class="h-3.5 w-28 rounded bg-gray-200 dark:bg-gray-700"></div>
                    <div class="h-4 w-12 rounded bg-gray-100 dark:bg-gray-800"></div>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <div class="h-6 w-16 rounded bg-gray-200 dark:bg-gray-700"></div>
                    <div class="h-4 w-24 rounded bg-gray-200 dark:bg-gray-700"></div>
                </div>
                <div class="mt-2 h-3 w-20 rounded bg-gray-100 dark:bg-gray-800"></div>
            </div>
        @endforeach
    </div>

    {{-- Skeleton Rekap per Unit Peminta --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="mb-3 flex items-center justify-between">
            <div class="h-4 w-44 rounded bg-gray-200 dark:bg-gray-700"></div>
            <div class="h-3.5 w-52 rounded bg-gray-100 dark:bg-gray-800"></div>
        </div>
        <div class="space-y-2.5">
            @for ($r = 0; $r < 4; $r++)
                <div class="grid grid-cols-6 gap-3">
                    <div class="col-span-2 h-4 rounded bg-gray-200 dark:bg-gray-700"></div>
                    <div class="h-4 rounded bg-gray-100 dark:bg-gray-800"></div>
                    <div class="h-4 rounded bg-gray-100 dark:bg-gray-800"></div>
                    <div class="h-4 rounded bg-gray-200 dark:bg-gray-700"></div>
                    <div class="h-4 rounded bg-gray-100 dark:bg-gray-800"></div>
                </div>
            @endfor
        </div>
    </div>
</div>
