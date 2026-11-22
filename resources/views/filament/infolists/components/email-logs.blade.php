@php
    $record = $getRecord();
    $logs = $record?->emailLogs()->with('sender')->limit(20)->get() ?? collect();
@endphp

@if ($logs->isEmpty())
    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/70 px-4 py-5 text-center text-xs text-slate-500 dark:border-white/15 dark:bg-white/[0.02] dark:text-slate-400">
        Belum ada riwayat pengiriman email untuk dokumen ini.
    </div>
@else
    <div class="space-y-3">
        @foreach ($logs as $log)
            @php
                $statusBadge = match ($log->status) {
                    'sent' => ['label' => 'Terkirim', 'classes' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300'],
                    'failed' => ['label' => 'Gagal', 'classes' => 'bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300'],
                    default => ['label' => 'Ditahan', 'classes' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300'],
                };

                $triggerLabel = match ($log->trigger_source) {
                    'batch_ocr' => 'Upload Batch OCR',
                    'row_upload' => 'Upload Berkas Baris',
                    'form_edit' => 'Edit Form Invoice',
                    'manual_resend' => 'Kirim / Kirim Ulang Manual',
                    default => $log->trigger_source,
                };

                $recipients = is_array($log->recipients) ? $log->recipients : [];
                $originalRecipients = is_array($log->original_recipients) ? $log->original_recipients : [];
                $attachments = is_array($log->attachments) ? $log->attachments : [];
            @endphp

            <div class="rounded-xl border border-slate-200/80 bg-white/75 p-4 text-xs dark:border-white/10 dark:bg-white/[0.03]">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $statusBadge['classes'] }}">
                            {{ $statusBadge['label'] }}
                        </span>
                        <span class="font-semibold text-slate-800 dark:text-slate-100">
                            {{ $log->subject }}
                        </span>
                    </div>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">
                        {{ ($log->sent_at ?? $log->created_at)?->format('d/m/Y H:i') }} • {{ $triggerLabel }}
                        @if ($log->sender)
                            • oleh {{ $log->sender->name }}
                        @endif
                    </span>
                </div>

                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <div>
                        <span class="font-semibold text-slate-500 dark:text-slate-400">Penerima:</span>
                        <span class="text-slate-800 dark:text-slate-200">
                            {{ $recipients !== [] ? implode(', ', $recipients) : '-' }}
                        </span>
                        @if ($originalRecipients !== [])
                            <div class="mt-0.5 text-[11px] text-amber-700 dark:text-amber-300">
                                (Mode Uji Coba — vendor asli: {{ implode(', ', $originalRecipients) }})
                            </div>
                        @endif
                    </div>

                    <div>
                        <span class="font-semibold text-slate-500 dark:text-slate-400">Lampiran ({{ count($attachments) }}):</span>
                        @if ($attachments !== [])
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                @foreach ($attachments as $att)
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 font-mono text-[10px] text-slate-700 dark:bg-white/10 dark:text-slate-200">
                                        {{ $att['filename'] ?? '-' }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <span class="text-slate-400">-</span>
                        @endif
                    </div>
                </div>

                @if ($log->zimbra_message_id)
                    <div class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">
                        Zimbra Message ID: <span class="font-mono">{{ $log->zimbra_message_id }}</span>
                    </div>
                @endif

                @if ($log->error_message)
                    <div class="mt-2 rounded-lg bg-rose-50 px-3 py-2 text-[11px] text-rose-700 dark:bg-rose-950/50 dark:text-rose-300">
                        {{ $log->error_message }}
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif
