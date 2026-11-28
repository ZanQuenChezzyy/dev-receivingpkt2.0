<x-filament-widgets::widget>
    <div class="pkt-bento-card pkt-bento-calendar relative h-full rounded-[24px] bg-white dark:bg-[#151B24] border border-black/[0.06] dark:border-white/[0.08] shadow-xs overflow-hidden p-4 sm:p-5 flex flex-col justify-between gap-3">
        {{-- Top PKT Corporate Accent Strip --}}
        <div class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#0054A6] via-[#0B67C2] to-[#F47920]"></div>

        {{-- Section 1: Live Clock & Full Indonesian Date --}}
        <div class="flex flex-col gap-1.5">
            <div class="flex items-center justify-between gap-2">
                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    Kalender &amp; Waktu
                </span>

                <div class="flex items-center gap-1.5">
                    @if ($canManageNotes)
                        {{ $this->createNoteAction }}
                    @endif
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#0054A6]/10 dark:bg-[#83A2DB]/15 text-[#0054A6] dark:text-[#93C5FD]">
                        WITA
                    </span>
                </div>
            </div>

            <div
                wire:ignore
                x-data="{
                    tz: @js($timezone),
                    hourMinute: @js($initialHourMinute),
                    second: @js($initialSecond),
                    fullDate: @js($initialFullDate),
                    timer: null,
                    monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                    dayNames: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
                    init() {
                        this.tick();
                        this.timer = setInterval(() => this.tick(), 1000);
                    },
                    destroy() {
                        if (this.timer) clearInterval(this.timer);
                    },
                    tick() {
                        try {
                            const now = new Date();
                            const parts = new Intl.DateTimeFormat('en-GB', {
                                timeZone: this.tz,
                                year: 'numeric',
                                month: '2-digit',
                                day: '2-digit',
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit',
                                hour12: false,
                            }).formatToParts(now);

                            const map = {};
                            parts.forEach((p) => { map[p.type] = p.value; });

                            const y = parseInt(map.year, 10);
                            const m = parseInt(map.month, 10);
                            const d = parseInt(map.day, 10);

                            this.hourMinute = `${map.hour}:${map.minute}`;
                            this.second = map.second;

                            const weekdayIdx = new Date(Date.UTC(y, m - 1, d)).getUTCDay();
                            this.fullDate = `${this.dayNames[weekdayIdx]}, ${d} ${this.monthNames[m - 1]} ${y}`;
                        } catch (e) {}
                    }
                }"
                class="flex flex-col gap-0.5"
            >
                <div class="flex items-baseline justify-between gap-2">
                    <div class="flex items-baseline gap-1 font-mono tabular-nums leading-none">
                        <span class="text-2xl sm:text-3xl font-extrabold tracking-tight text-[#10141A] dark:text-white" x-text="hourMinute">{{ $initialHourMinute }}</span>
                        <span class="text-sm sm:text-base font-bold text-[#F47920]" x-text="':' + second">:{{ $initialSecond }}</span>
                    </div>

                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-white/[0.06] text-slate-600 dark:text-slate-300 truncate max-w-[130px]" title="Periode filter aktif">
                        {{ $filterPeriodLabel }}
                    </span>
                </div>

                <div class="text-xs font-semibold text-slate-600 dark:text-slate-300" x-text="fullDate">
                    {{ $initialFullDate }}
                </div>
            </div>
        </div>

        {{-- Section 2: Interactive Calendar Month Grid (Holidays + Scheduled Notes) --}}
        <div class="pt-2.5 border-t border-black/[0.06] dark:border-white/[0.08] flex flex-col gap-1.5">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-bold text-[#10141A] dark:text-white">
                    {{ $viewMonthLabel }}
                </span>

                <div class="flex items-center gap-1">
                    @if (! $isViewingCurrentMonth)
                        <button
                            type="button"
                            wire:click="goToToday"
                            class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#F47920]/12 text-[#F47920] hover:bg-[#F47920] hover:text-white transition-colors cursor-pointer"
                        >
                            Hari Ini
                        </button>
                    @endif
                    <button
                        type="button"
                        wire:click="previousMonth"
                        aria-label="Bulan sebelumnya"
                        class="w-6 h-6 rounded-lg inline-flex items-center justify-center text-slate-500 hover:text-[#10141A] hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/[0.08] transition-colors cursor-pointer"
                    >
                        <x-heroicon-m-chevron-left class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        wire:click="nextMonth"
                        aria-label="Bulan berikutnya"
                        class="w-6 h-6 rounded-lg inline-flex items-center justify-center text-slate-500 hover:text-[#10141A] hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/[0.08] transition-colors cursor-pointer"
                    >
                        <x-heroicon-m-chevron-right class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>

            {{-- Weekday Labels --}}
            <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                <span>Sen</span>
                <span>Sel</span>
                <span>Rab</span>
                <span>Kam</span>
                <span>Jum</span>
                <span>Sab</span>
                <span class="text-rose-500/80 dark:text-rose-400/80">Min</span>
            </div>

            {{-- Calendar Day Grid (Compact h-7 cells keep total card height balanced) --}}
            <div class="grid grid-cols-7 gap-1 text-center">
                @foreach ($calendarCells as $cell)
                    @if (! $cell['in_month'])
                        <span class="h-7 rounded-lg inline-flex items-center justify-center text-[11px] tabular-nums text-slate-300 dark:text-slate-600 font-medium select-none">
                            {{ $cell['day'] }}
                        </span>
                    @else
                        @php
                            $hasHoliday = $cell['holiday'] !== null;
                            $isJointLeave = $hasHoliday && (bool) ($cell['holiday']['is_joint_leave'] ?? false);
                            $hasNotes = $cell['notes_count'] > 0;
                            $primaryCategory = $cell['note_categories'][0] ?? null;

                            $cellClass = match (true) {
                                $cell['is_today'] => 'bg-[#F47920] text-white font-extrabold shadow-xs ring-2 ring-[#F47920]/25',
                                $hasHoliday && $isJointLeave => 'bg-amber-500/14 text-amber-700 dark:bg-amber-500/18 dark:text-amber-300 font-bold hover:bg-amber-500/24',
                                $hasHoliday => 'bg-rose-500/10 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400 font-bold hover:bg-rose-500/20',
                                $hasNotes => 'bg-[#0054A6]/10 dark:bg-[#83A2DB]/15 text-[#0054A6] dark:text-[#93C5FD] font-bold hover:bg-[#0054A6]/18',
                                $cell['is_sunday'] => 'text-rose-500 dark:text-rose-400 font-semibold hover:bg-rose-500/10',
                                default => 'text-slate-700 dark:text-slate-200 font-semibold hover:bg-slate-100 dark:hover:bg-white/[0.06]',
                            };

                            if ($cell['is_selected'] && ! $cell['is_today']) {
                                $cellClass .= ' ring-2 ring-[#0054A6] dark:ring-[#83A2DB]';
                            }

                            $holidayDotClass = $isJointLeave ? 'bg-amber-500' : 'bg-rose-500';
                            $noteDotClass = match ($primaryCategory) {
                                'cuti' => 'bg-amber-500',
                                'kegiatan' => 'bg-emerald-500',
                                'catatan' => 'bg-violet-500',
                                default => 'bg-[#0054A6] dark:bg-[#60A5FA]',
                            };
                        @endphp

                        <button
                            type="button"
                            wire:click="selectDate('{{ $cell['date'] }}')"
                            @if ($cell['tooltip'] !== '') title="{{ $cell['tooltip'] }}" @endif
                            class="relative h-7 rounded-lg inline-flex flex-col items-center justify-center text-[11px] tabular-nums transition-all cursor-pointer select-none {{ $cellClass }}"
                        >
                            <span class="leading-none">{{ $cell['day'] }}</span>

                            @if ($hasHoliday || $hasNotes)
                                <span class="flex items-center justify-center gap-0.5 mt-0.5">
                                    @if ($hasHoliday)
                                        <span class="w-1 h-1 rounded-full {{ $cell['is_today'] ? 'bg-white' : $holidayDotClass }}"></span>
                                    @endif
                                    @if ($hasNotes)
                                        <span class="w-1 h-1 rounded-full {{ $cell['is_today'] ? 'bg-amber-200' : $noteDotClass }}"></span>
                                    @endif
                                </span>
                            @endif
                        </button>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Section 3: Fixed-Height Agenda & Holidays Panel (Zero layout jump even on heavy holiday months) --}}
        <div class="pt-2.5 border-t border-black/[0.06] dark:border-white/[0.08] flex flex-col gap-2">
            <div class="flex flex-wrap items-center justify-between gap-1.5">
                {{-- Compact Segmented Filter Pills --}}
                <div class="inline-flex items-center gap-0.5 p-0.5 rounded-full bg-slate-100 dark:bg-white/[0.05] border border-black/[0.05] dark:border-white/[0.07]">
                    <button
                        type="button"
                        wire:click="setAgendaTab('all')"
                        @class([
                            'px-2 py-0.5 rounded-full text-[10px] font-semibold transition-colors cursor-pointer',
                            'bg-white dark:bg-[#1E2735] text-[#10141A] dark:text-white shadow-2xs' => $agendaTab === 'all',
                            'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' => $agendaTab !== 'all',
                        ])
                    >
                        Semua
                    </button>
                    <button
                        type="button"
                        wire:click="setAgendaTab('notes')"
                        @class([
                            'px-2 py-0.5 rounded-full text-[10px] font-semibold transition-colors cursor-pointer inline-flex items-center gap-1',
                            'bg-white dark:bg-[#1E2735] text-[#0054A6] dark:text-[#93C5FD] shadow-2xs' => $agendaTab === 'notes',
                            'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' => $agendaTab !== 'notes',
                        ])
                    >
                        Catatan
                        @if ($notesCount > 0)
                            <span class="text-[9px] font-bold opacity-80 tabular-nums">({{ $notesCount }})</span>
                        @endif
                    </button>
                    <button
                        type="button"
                        wire:click="setAgendaTab('holidays')"
                        @class([
                            'px-2 py-0.5 rounded-full text-[10px] font-semibold transition-colors cursor-pointer inline-flex items-center gap-1',
                            'bg-white dark:bg-[#1E2735] text-rose-600 dark:text-rose-300 shadow-2xs' => $agendaTab === 'holidays',
                            'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' => $agendaTab !== 'holidays',
                        ])
                    >
                        Libur
                        @if ($holidaysCount > 0)
                            <span class="text-[9px] font-bold opacity-80 tabular-nums">({{ $holidaysCount }})</span>
                        @endif
                    </button>
                </div>

                <div class="flex items-center gap-1.5">
                    @if ($selectedDateLabel)
                        <button
                            type="button"
                            wire:click="clearSelectedDate"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#0054A6]/10 dark:bg-[#83A2DB]/15 text-[#0054A6] dark:text-[#93C5FD] hover:opacity-80 cursor-pointer"
                            title="Hapus filter tanggal"
                        >
                            <span>{{ $selectedDateLabel }}</span>
                            <span aria-hidden="true">&times;</span>
                        </button>
                    @elseif ($manageUrl)
                        <a
                            href="{{ $manageUrl }}"
                            wire:navigate
                            class="text-[10px] font-semibold text-slate-500 hover:text-[#F47920] dark:text-slate-400 dark:hover:text-orange-300 transition-colors"
                            title="Kelola seluruh catatan di Data Master"
                        >
                            Kelola &rarr;
                        </a>
                    @endif
                </div>
            </div>

            {{-- Stable Fixed-Height Scrollable Container with Sleek Thin Scrollbar --}}
            <div class="pkt-thin-scroll h-[112px] overflow-y-auto space-y-1.5 pr-1">
                {{-- Catatan Dinas / Agenda dari Sekretaris --}}
                @foreach ($visibleNotes as $note)
                    @php
                        $catStyle = match ($note->category) {
                            'cuti' => 'bg-amber-500/12 text-amber-700 dark:text-amber-300 border-amber-500/20',
                            'kegiatan' => 'bg-emerald-500/12 text-emerald-700 dark:text-emerald-300 border-emerald-500/20',
                            'catatan' => 'bg-violet-500/12 text-violet-700 dark:text-violet-300 border-violet-500/20',
                            default => 'bg-[#0054A6]/10 text-[#0054A6] dark:bg-[#83A2DB]/15 dark:text-[#93C5FD] border-[#0054A6]/20 dark:border-[#83A2DB]/25',
                        };
                    @endphp
                    <div class="group rounded-xl px-2.5 py-1.5 bg-slate-50/90 dark:bg-white/[0.035] border border-black/[0.05] dark:border-white/[0.07] flex items-start justify-between gap-2">
                        <div class="flex flex-col min-w-0 gap-0.5 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold border tabular-nums {{ $catStyle }}">
                                    {{ $note->formattedDateRange() }}
                                </span>
                                <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400">
                                    {{ $note->categoryLabel() }}
                                </span>
                            </div>
                            <div class="text-[11px] font-bold text-[#10141A] dark:text-white leading-snug">
                                @if ($note->user)
                                    <span class="text-[#0054A6] dark:text-[#93C5FD]">{{ $note->user->name }}:</span>
                                @endif
                                {{ $note->title }}
                            </div>
                            @if (filled($note->notes))
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 leading-snug line-clamp-2">
                                    {{ $note->notes }}
                                </div>
                            @endif
                        </div>

                        @if ($canManageNotes)
                            <div class="flex items-center gap-0.5 shrink-0">
                                <button
                                    type="button"
                                    wire:click="mountAction('editNote', { noteId: {{ $note->id }} })"
                                    aria-label="Ubah catatan"
                                    title="Ubah catatan"
                                    class="w-6 h-6 rounded-lg inline-flex items-center justify-center text-slate-400 hover:text-[#0054A6] hover:bg-slate-200/70 dark:hover:text-[#93C5FD] dark:hover:bg-white/[0.08] transition-colors cursor-pointer"
                                >
                                    <x-heroicon-m-pencil-square class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    type="button"
                                    wire:click="mountAction('deleteNote', { noteId: {{ $note->id }} })"
                                    aria-label="Hapus catatan"
                                    title="Hapus catatan"
                                    class="w-6 h-6 rounded-lg inline-flex items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-500/10 dark:hover:text-rose-400 transition-colors cursor-pointer"
                                >
                                    <x-heroicon-m-trash class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach

                {{-- Hari Libur Nasional (Merah/Rose) & Cuti Bersama (Amber/Kuning Emas) --}}
                @foreach ($visibleHolidays as $holiday)
                    @php
                        $isJoint = (bool) ($holiday['is_joint_leave'] ?? false);
                        $rowClasses = $isJoint
                            ? 'bg-amber-500/[0.07] dark:bg-amber-500/[0.11] border-amber-500/20'
                            : 'bg-rose-500/[0.06] dark:bg-rose-500/[0.09] border-rose-500/15';
                        $dateBadgeClasses = $isJoint
                            ? 'bg-amber-500/15 text-amber-700 dark:text-amber-300'
                            : 'bg-rose-500/15 text-rose-700 dark:text-rose-300';
                        $nameClasses = $isJoint
                            ? 'text-amber-800 dark:text-amber-200'
                            : 'text-rose-800 dark:text-rose-200';
                        $tagClasses = $isJoint
                            ? 'text-amber-700/85 dark:text-amber-300/85'
                            : 'text-rose-600/80 dark:text-rose-300/80';
                    @endphp
                    <div class="rounded-xl px-2.5 py-1.5 border flex items-center justify-between gap-2 {{ $rowClasses }}">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold tabular-nums shrink-0 {{ $dateBadgeClasses }}">
                                {{ $holiday['range_label'] }}
                            </span>
                            <span class="text-[11px] font-semibold truncate {{ $nameClasses }}" title="{{ $holiday['name'] }}">
                                {{ $holiday['name'] }}
                            </span>
                        </div>
                        <span class="text-[9px] font-bold uppercase tracking-wider shrink-0 {{ $tagClasses }}">
                            {{ $isJoint ? 'Cuti Bersama' : 'Libur Nasional' }}
                        </span>
                    </div>
                @endforeach

                @if ($visibleNotes->isEmpty() && $visibleHolidays === [])
                    <div class="h-full rounded-xl px-3 bg-slate-50/80 dark:bg-white/[0.025] border border-dashed border-black/[0.08] dark:border-white/[0.08] text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-center text-center">
                        @if ($selectedDateLabel)
                            Tidak ada agenda pada {{ $selectedDateLabel }}.
                        @elseif ($agendaTab === 'notes')
                            Belum ada catatan agenda pada bulan ini.
                        @elseif ($agendaTab === 'holidays')
                            Tidak ada hari libur nasional pada bulan ini.
                        @else
                            Belum ada catatan agenda maupun hari libur pada bulan ini.
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Native Filament Action Modals (Tambah, Ubah, Hapus Catatan Kalender) --}}
    <x-filament-actions::modals />
</x-filament-widgets::widget>
