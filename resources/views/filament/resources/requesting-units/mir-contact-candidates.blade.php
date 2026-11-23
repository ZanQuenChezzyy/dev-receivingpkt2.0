<div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
    @if (empty($candidates))
        <div class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
            Belum ada riwayat kontak PIC yang tercatat pada transaksi MIR.
        </div>
    @else
        <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                <tr>
                    <th class="px-3 py-2">Departemen</th>
                    <th class="px-3 py-2">Bagian</th>
                    <th class="px-3 py-2">Diminta Oleh</th>
                    <th class="px-3 py-2">No. HP</th>
                    <th class="px-3 py-2 text-right">Jumlah MIR</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($candidates as $candidate)
                    <tr>
                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-gray-100">{{ $candidate['departemen'] }}</td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $candidate['bagian'] }}</td>
                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ $candidate['diminta_oleh'] }}</td>
                        <td class="px-3 py-2 font-mono text-gray-700 dark:text-gray-300">{{ $candidate['no_hp'] }}</td>
                        <td class="px-3 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">{{ $candidate['total_mir'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
