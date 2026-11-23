<div class="space-y-3">
    @if ($logs->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
            Belum ada catatan tindak lanjut atau log audit untuk item ini.
        </div>
    @else
        <ol class="relative ml-3 space-y-4 border-l border-gray-200 dark:border-gray-700">
            @foreach ($logs as $log)
                <li class="ml-5">
                    <span class="absolute -left-2 flex h-4 w-4 items-center justify-center rounded-full bg-orange-500 ring-4 ring-white dark:ring-gray-900"></span>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/60">
                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                            <span class="font-bold text-gray-900 dark:text-white">
                                {{ strtoupper(str_replace('_', ' ', $log->action_type)) }}
                                &bull; {{ $log->channel }}
                                @if ($log->email_sent)
                                    <span class="ml-1 rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">Email Terkirim</span>
                                @endif
                            </span>
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ $log->created_at?->format('d M Y H:i') }}
                            </span>
                        </div>
                        <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                            Umur: <strong>{{ $log->aging_days_snapshot }} hari ({{ $log->aging_zone_snapshot }})</strong>
                            &bull; Nilai: <strong>Rp {{ number_format($log->outstanding_value_snapshot, 0, ',', '.') }}</strong>
                            @if ($log->recipient_name || $log->recipient_contact)
                                &bull; Tujuan ({{ $log->recipient_level }}): {{ trim(($log->recipient_name ?? '').' '.($log->recipient_contact ? '('.$log->recipient_contact.')' : '')) }}
                            @endif
                        </div>
                        @if ($log->notes)
                            <div class="mt-1.5 text-sm text-gray-800 dark:text-gray-200">
                                {{ $log->notes }}
                            </div>
                        @endif
                        @if ($log->createdBy)
                            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                Dicatat oleh: {{ $log->createdBy->name }}
                            </div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</div>
