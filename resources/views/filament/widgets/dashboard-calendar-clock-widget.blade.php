<x-filament-widgets::widget>
    <div
        x-data="{
            tz: @js($timezone),
            hourMinute: @js($initialHourMinute),
            second: @js($initialSecond),
            fullDate: @js($initialFullDate),
            todayYear: {{ $initialYear }},
            todayMonth: {{ $initialMonth }},
            todayDay: {{ $initialDay }},
            viewYear: {{ $initialYear }},
            viewMonth: {{ $initialMonth }},
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
                        weekday: 'short',
                        hour12: false,
                    }).formatToParts(now);

                    const map = {};
                    parts.forEach((p) => { map[p.type] = p.value; });

                    const y = parseInt(map.year, 10);
                    const m = parseInt(map.month, 10);
                    const d = parseInt(map.day, 10);

                    this.hourMinute = `${map.hour}:${map.minute}`;
                    this.second = map.second;
                    this.todayYear = y;
                    this.todayMonth = m;
                    this.todayDay = d;

                    const weekdayIdx = new Date(Date.UTC(y, m - 1, d)).getUTCDay();
                    this.fullDate = `${this.dayNames[weekdayIdx]}, ${d} ${this.monthNames[m - 1]} ${y}`;
                } catch (e) {}
            },
            prevMonth() {
                if (this.viewMonth === 1) {
                    this.viewMonth = 12;
                    this.viewYear -= 1;
                } else {
                    this.viewMonth -= 1;
                }
            },
            nextMonth() {
                if (this.viewMonth === 12) {
                    this.viewMonth = 1;
                    this.viewYear += 1;
                } else {
                    this.viewMonth += 1;
                }
            },
            goToday() {
                this.viewYear = this.todayYear;
                this.viewMonth = this.todayMonth;
            },
            get isViewingCurrentMonth() {
                return this.viewYear === this.todayYear && this.viewMonth === this.todayMonth;
            },
            get viewMonthLabel() {
                return `${this.monthNames[this.viewMonth - 1]} ${this.viewYear}`;
            },
            get calendarCells() {
                const year = this.viewYear;
                const month = this.viewMonth;
                const firstDayJs = new Date(Date.UTC(year, month - 1, 1)).getUTCDay();
                // Mulai hari Senin (Sen = 0 ... Min = 6)
                const leadingOffset = (firstDayJs + 6) % 7;
                const daysInMonth = new Date(Date.UTC(year, month, 0)).getUTCDate();
                const daysInPrevMonth = new Date(Date.UTC(year, month - 1, 0)).getUTCDate();

                const cells = [];

                for (let i = leadingOffset - 1; i >= 0; i--) {
                    cells.push({
                        day: daysInPrevMonth - i,
                        inMonth: false,
                        isToday: false,
                        isSunday: false,
                    });
                }

                for (let d = 1; d <= daysInMonth; d++) {
                    const colIdx = (leadingOffset + d - 1) % 7;
                    cells.push({
                        day: d,
                        inMonth: true,
                        isToday: year === this.todayYear && month === this.todayMonth && d === this.todayDay,
                        isSunday: colIdx === 6,
                    });
                }

                const trailingCount = (7 - (cells.length % 7)) % 7;
                for (let t = 1; t <= trailingCount; t++) {
                    cells.push({
                        day: t,
                        inMonth: false,
                        isToday: false,
                        isSunday: false,
                    });
                }

                return cells;
            }
        }"
        class="pkt-bento-card pkt-bento-calendar relative h-full rounded-[24px] bg-white dark:bg-[#151B24] border border-black/[0.06] dark:border-white/[0.08] shadow-xs overflow-hidden p-4 sm:p-5 flex flex-col justify-between gap-3.5"
    >
        {{-- Top PKT Corporate Accent Strip --}}
        <div class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#0054A6] via-[#0B67C2] to-[#F47920]"></div>

        {{-- Live Clock & Full Indonesian Date --}}
        <div class="flex flex-col gap-2">
            <div class="flex items-center justify-between gap-2">
                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    Kalender &amp; Waktu
                </span>

                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#0054A6]/10 dark:bg-[#83A2DB]/15 text-[#0054A6] dark:text-[#93C5FD]">
                    WITA
                </span>
            </div>

            <div class="flex items-baseline justify-between gap-2">
                <div class="flex items-baseline gap-1 font-mono tabular-nums leading-none">
                    <span class="text-3xl sm:text-4xl font-extrabold tracking-tight text-[#10141A] dark:text-white" x-text="hourMinute">{{ $initialHourMinute }}</span>
                    <span class="text-lg font-bold text-[#F47920]" x-text="':' + second">:{{ $initialSecond }}</span>
                </div>

                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-white/[0.06] text-slate-600 dark:text-slate-300 truncate max-w-[130px]" title="Periode filter aktif">
                    {{ $filterPeriodLabel }}
                </span>
            </div>

            <div class="text-xs font-semibold text-slate-600 dark:text-slate-300" x-text="fullDate">
                {{ $initialFullDate }}
            </div>
        </div>

        {{-- Interactive Mini Calendar Grid --}}
        <div class="pt-3 border-t border-black/[0.06] dark:border-white/[0.08] flex flex-col gap-2">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-bold text-[#10141A] dark:text-white" x-text="viewMonthLabel">
                    {{ $filterPeriodLabel }}
                </span>

                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        x-show="!isViewingCurrentMonth"
                        x-cloak
                        x-on:click="goToday()"
                        class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#F47920]/12 text-[#F47920] hover:bg-[#F47920] hover:text-white transition-colors cursor-pointer"
                    >
                        Hari Ini
                    </button>
                    <button
                        type="button"
                        x-on:click="prevMonth()"
                        aria-label="Bulan sebelumnya"
                        class="w-6 h-6 rounded-lg inline-flex items-center justify-center text-slate-500 hover:text-[#10141A] hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/[0.08] transition-colors cursor-pointer"
                    >
                        <x-heroicon-m-chevron-left class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        x-on:click="nextMonth()"
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

            {{-- Calendar Day Grid --}}
            <div class="grid grid-cols-7 gap-1 text-center">
                <template x-for="(cell, idx) in calendarCells" :key="idx">
                    <span
                        x-text="cell.day"
                        :class="{
                            'bg-[#F47920] text-white font-extrabold shadow-xs ring-2 ring-[#F47920]/25': cell.isToday,
                            'text-slate-700 dark:text-slate-200 font-semibold hover:bg-slate-100 dark:hover:bg-white/[0.06]': cell.inMonth && !cell.isToday && !cell.isSunday,
                            'text-rose-500 dark:text-rose-400 font-semibold hover:bg-rose-500/10': cell.inMonth && !cell.isToday && cell.isSunday,
                            'text-slate-300 dark:text-slate-600 font-medium': !cell.inMonth,
                        }"
                        class="h-7 rounded-lg inline-flex items-center justify-center text-[11px] tabular-nums transition-colors select-none"
                    ></span>
                </template>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
