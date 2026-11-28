<?php

namespace App\Filament\Widgets;

use App\Models\CalendarNote;
use App\Models\User;
use App\Services\IndonesianHolidayService;
use Filament\Notifications\Notification;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class DashboardCalendarClockWidget extends Widget
{
    use InteractsWithPageFilters;

    protected string $view = 'filament.widgets.dashboard-calendar-clock-widget';

    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 4,
    ];

    public int $viewYear;

    public int $viewMonth;

    public ?string $selectedDate = null;

    public bool $showNoteModal = false;

    public ?int $editingNoteId = null;

    public ?int $noteUserId = null;

    public string $noteTitle = '';

    public string $noteCategory = CalendarNote::CATEGORY_DINAS;

    public string $noteStartDate = '';

    public string $noteEndDate = '';

    public string $noteDescription = '';

    public function mount(): void
    {
        $now = now()->timezone(config('app.timezone', 'Asia/Makassar'));
        $this->viewYear = (int) $now->year;
        $this->viewMonth = (int) $now->month;
    }

    public function previousMonth(): void
    {
        if ($this->viewMonth <= 1) {
            $this->viewMonth = 12;
            $this->viewYear--;
        } else {
            $this->viewMonth--;
        }

        $this->selectedDate = null;
    }

    public function nextMonth(): void
    {
        if ($this->viewMonth >= 12) {
            $this->viewMonth = 1;
            $this->viewYear++;
        } else {
            $this->viewMonth++;
        }

        $this->selectedDate = null;
    }

    public function goToToday(): void
    {
        $now = now()->timezone(config('app.timezone', 'Asia/Makassar'));
        $this->viewYear = (int) $now->year;
        $this->viewMonth = (int) $now->month;
        $this->selectedDate = null;
    }

    public function selectDate(string $date): void
    {
        $clean = trim($date);
        if ($clean === '') {
            return;
        }

        $this->selectedDate = $this->selectedDate === $clean ? null : $clean;
    }

    public function clearSelectedDate(): void
    {
        $this->selectedDate = null;
    }

    public function openNoteModal(?string $prefillDate = null): void
    {
        if (! $this->canManageNotes()) {
            return;
        }

        $defaultDate = $prefillDate
            ?: ($this->selectedDate ?: now()->timezone(config('app.timezone', 'Asia/Makassar'))->toDateString());

        $this->editingNoteId = null;
        $this->noteUserId = null;
        $this->noteTitle = '';
        $this->noteCategory = CalendarNote::CATEGORY_DINAS;
        $this->noteStartDate = $defaultDate;
        $this->noteEndDate = $defaultDate;
        $this->noteDescription = '';
        $this->resetValidation();
        $this->showNoteModal = true;
    }

    public function editNote(int $noteId): void
    {
        if (! $this->canManageNotes() || ! Schema::hasTable('calendar_notes')) {
            return;
        }

        $note = CalendarNote::query()->find($noteId);
        if (! $note) {
            return;
        }

        $this->editingNoteId = $note->id;
        $this->noteUserId = $note->user_id;
        $this->noteTitle = $note->title;
        $this->noteCategory = $note->category;
        $this->noteStartDate = $note->start_date?->toDateString() ?? '';
        $this->noteEndDate = $note->end_date?->toDateString() ?? $this->noteStartDate;
        $this->noteDescription = (string) ($note->notes ?? '');
        $this->resetValidation();
        $this->showNoteModal = true;
    }

    public function closeNoteModal(): void
    {
        $this->showNoteModal = false;
        $this->resetValidation();
    }

    public function saveNote(): void
    {
        if (! $this->canManageNotes()) {
            Notification::make()
                ->title('Akses ditolak')
                ->body('Hanya Sekretaris yang berhak menambah atau mengubah catatan kalender.')
                ->warning()
                ->send();

            return;
        }

        if (! Schema::hasTable('calendar_notes')) {
            return;
        }

        if ($this->noteUserId === 0) {
            $this->noteUserId = null;
        }

        $validated = $this->validate([
            'noteUserId' => ['nullable', 'integer', 'exists:users,id'],
            'noteTitle' => ['required', 'string', 'min:2', 'max:140'],
            'noteCategory' => ['required', Rule::in(array_keys(CalendarNote::categoryOptions()))],
            'noteStartDate' => ['required', 'date'],
            'noteEndDate' => ['required', 'date', 'after_or_equal:noteStartDate'],
            'noteDescription' => ['nullable', 'string', 'max:500'],
        ], [
            'noteTitle.required' => 'Judul catatan wajib diisi.',
            'noteStartDate.required' => 'Tanggal mulai wajib diisi.',
            'noteEndDate.required' => 'Tanggal selesai wajib diisi.',
            'noteEndDate.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
        ]);

        $payload = [
            'user_id' => $validated['noteUserId'] ?: null,
            'title' => trim($validated['noteTitle']),
            'category' => $validated['noteCategory'],
            'start_date' => $validated['noteStartDate'],
            'end_date' => $validated['noteEndDate'],
            'notes' => filled($validated['noteDescription'] ?? null) ? trim((string) $validated['noteDescription']) : null,
        ];

        if ($this->editingNoteId) {
            $note = CalendarNote::query()->find($this->editingNoteId);
            if ($note) {
                $note->update($payload);
            }
            $toastTitle = 'Catatan kalender diperbarui';
        } else {
            $payload['created_by'] = Auth::id();
            CalendarNote::query()->create($payload);
            $toastTitle = 'Catatan kalender ditambahkan';
        }

        $startCarbon = Carbon::parse($validated['noteStartDate']);
        $this->viewYear = (int) $startCarbon->year;
        $this->viewMonth = (int) $startCarbon->month;
        $this->selectedDate = null;
        $this->showNoteModal = false;
        $this->editingNoteId = null;

        Notification::make()
            ->title($toastTitle)
            ->success()
            ->send();
    }

    public function deleteNote(int $noteId): void
    {
        if (! $this->canManageNotes() || ! Schema::hasTable('calendar_notes')) {
            return;
        }

        $note = CalendarNote::query()->find($noteId);
        if (! $note) {
            return;
        }

        $note->delete();

        Notification::make()
            ->title('Catatan kalender dihapus')
            ->success()
            ->send();
    }

    protected function canManageNotes(): bool
    {
        /** @var ?User $user */
        $user = Auth::user();

        return CalendarNote::canUserManage($user);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $tz = config('app.timezone', 'Asia/Makassar');
        $now = now()->timezone($tz);
        $todayStr = $now->toDateString();

        $dayNames = [
            0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu',
        ];

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $shortMonths = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        $viewYear = $this->viewYear ?: (int) $now->year;
        $viewMonth = $this->viewMonth ?: (int) $now->month;

        $monthStart = Carbon::create($viewYear, $viewMonth, 1, 0, 0, 0, $tz)->startOfDay();
        $monthEnd = (clone $monthStart)->endOfMonth();

        // 1. Hari Libur Nasional Indonesia untuk bulan yang sedang dilihat
        $monthHolidays = app(IndonesianHolidayService::class)->getHolidaysForMonth($viewYear, $viewMonth);

        // 2. Catatan kalender dari DB yang beririsan dengan bulan yang sedang dilihat
        $monthNotes = collect();
        try {
            if (Schema::hasTable('calendar_notes')) {
                $monthNotes = CalendarNote::query()
                    ->with(['user:id,name,npk'])
                    ->whereDate('start_date', '<=', $monthEnd->toDateString())
                    ->whereDate('end_date', '>=', $monthStart->toDateString())
                    ->orderBy('start_date')
                    ->orderBy('id')
                    ->get();
            }
        } catch (\Throwable) {
            $monthNotes = collect();
        }

        // Indeks catatan per tanggal untuk pewarnaan rentang di kalender
        /** @var array<string, list<CalendarNote>> $notesByDate */
        $notesByDate = [];
        foreach ($monthNotes as $note) {
            $cursor = $note->start_date->copy()->max($monthStart);
            $limit = ($note->end_date ?? $note->start_date)->copy()->min($monthEnd);

            while ($cursor->lte($limit)) {
                $dateKey = $cursor->toDateString();
                $notesByDate[$dateKey][] = $note;
                $cursor->addDay();
            }
        }

        // 3. Bangun grid kalender bulanan (Senin -> Minggu)
        $leadingOffset = ($monthStart->dayOfWeek + 6) % 7;
        $daysInMonth = $monthStart->daysInMonth;
        $prevMonthEnd = $monthStart->copy()->subDay();
        $daysInPrevMonth = $prevMonthEnd->daysInMonth;

        $calendarCells = [];

        for ($i = $leadingOffset - 1; $i >= 0; $i--) {
            $calendarCells[] = [
                'date' => null,
                'day' => $daysInPrevMonth - $i,
                'in_month' => false,
                'is_today' => false,
                'is_sunday' => false,
                'is_selected' => false,
                'holiday' => null,
                'notes_count' => 0,
                'note_categories' => [],
                'tooltip' => '',
            ];
        }

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateKey = sprintf('%04d-%02d-%02d', $viewYear, $viewMonth, $d);
            $colIdx = ($leadingOffset + $d - 1) % 7;
            $holiday = $monthHolidays[$dateKey] ?? null;
            $dayNotes = $notesByDate[$dateKey] ?? [];
            $noteCategories = array_values(array_unique(array_map(
                static fn (CalendarNote $n): string => $n->category,
                $dayNotes,
            )));

            $tooltipParts = [];
            if ($holiday) {
                $tooltipParts[] = ($holiday['is_joint_leave'] ? 'Cuti Bersama: ' : 'Libur Nasional: ').$holiday['name'];
            }
            foreach ($dayNotes as $n) {
                $personPrefix = $n->user ? $n->user->name.' — ' : '';
                $tooltipParts[] = $personPrefix.$n->title;
            }

            $calendarCells[] = [
                'date' => $dateKey,
                'day' => $d,
                'in_month' => true,
                'is_today' => $dateKey === $todayStr,
                'is_sunday' => $colIdx === 6,
                'is_selected' => $this->selectedDate === $dateKey,
                'holiday' => $holiday,
                'notes_count' => count($dayNotes),
                'note_categories' => $noteCategories,
                'tooltip' => implode(' • ', $tooltipParts),
            ];
        }

        $trailingCount = (7 - (count($calendarCells) % 7)) % 7;
        for ($t = 1; $t <= $trailingCount; $t++) {
            $calendarCells[] = [
                'date' => null,
                'day' => $t,
                'in_month' => false,
                'is_today' => false,
                'is_sunday' => false,
                'is_selected' => false,
                'holiday' => null,
                'notes_count' => 0,
                'note_categories' => [],
                'tooltip' => '',
            ];
        }

        // 4. Filter daftar catatan & hari libur berdasarkan tanggal terpilih (atau tampilkan satu bulan penuh)
        $visibleNotes = $this->selectedDate
            ? collect($notesByDate[$this->selectedDate] ?? [])
            : $monthNotes;

        $visibleHolidays = $this->selectedDate
            ? (isset($monthHolidays[$this->selectedDate]) ? [$this->selectedDate => $monthHolidays[$this->selectedDate]] : [])
            : $monthHolidays;

        $selectedDateLabel = null;
        if ($this->selectedDate) {
            try {
                $selCarbon = Carbon::parse($this->selectedDate);
                $selectedDateLabel = $selCarbon->day.' '.($shortMonths[(int) $selCarbon->month] ?? '').' '.$selCarbon->year;
            } catch (\Throwable) {
                $selectedDateLabel = $this->selectedDate;
            }
        }

        $canManage = $this->canManageNotes();
        $assignableUsers = $canManage
            ? User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'npk'])
            : collect();

        $filterMonth = (int) ($this->filters['month'] ?? $now->month);
        $filterYear = (int) ($this->filters['year'] ?? $now->year);
        $filterPeriodLabel = ($monthNames[$filterMonth] ?? $monthNames[$now->month]).' '.$filterYear;

        return [
            'timezone' => $tz,
            'initialHourMinute' => $now->format('H:i'),
            'initialSecond' => $now->format('s'),
            'initialFullDate' => ($dayNames[(int) $now->dayOfWeek] ?? 'Hari Ini').', '.$now->day.' '.($monthNames[(int) $now->month] ?? '').' '.$now->year,
            'viewMonthLabel' => ($monthNames[$viewMonth] ?? '').' '.$viewYear,
            'isViewingCurrentMonth' => $viewYear === (int) $now->year && $viewMonth === (int) $now->month,
            'filterPeriodLabel' => $filterPeriodLabel,
            'calendarCells' => $calendarCells,
            'visibleNotes' => $visibleNotes,
            'visibleHolidays' => $visibleHolidays,
            'selectedDateLabel' => $selectedDateLabel,
            'canManageNotes' => $canManage,
            'assignableUsers' => $assignableUsers,
            'categoryOptions' => CalendarNote::categoryOptions(),
            'shortMonths' => $shortMonths,
        ];
    }
}
