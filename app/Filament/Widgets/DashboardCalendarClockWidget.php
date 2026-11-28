<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CalendarNotes\CalendarNoteResource;
use App\Models\CalendarNote;
use App\Models\User;
use App\Services\IndonesianHolidayService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Reactive;

class DashboardCalendarClockWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    /**
     * @var array<string, mixed>|null
     */
    #[Reactive]
    public ?array $pageFilters = null;

    protected string $view = 'filament.widgets.dashboard-calendar-clock-widget';

    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 4,
    ];

    public int $viewYear;

    public int $viewMonth;

    public ?string $selectedDate = null;

    public string $agendaTab = 'all';

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

    public function setAgendaTab(string $tab): void
    {
        if (in_array($tab, ['all', 'notes', 'holidays'], true)) {
            $this->agendaTab = $tab;
        }
    }

    public function editNoteAction(): Action
    {
        return Action::make('editNote')
            ->label('Ubah Catatan')
            ->icon('heroicon-m-pencil-square')
            ->color('gray')
            ->visible(fn (): bool => $this->canManageNotes())
            ->modalHeading('Ubah Catatan Kalender')
            ->modalWidth('lg')
            ->modalSubmitActionLabel('Simpan Perubahan')
            ->fillForm(function (array $arguments): array {
                $noteId = (int) ($arguments['noteId'] ?? 0);
                $note = ($noteId > 0 && Schema::hasTable('calendar_notes'))
                    ? CalendarNote::query()->find($noteId)
                    : null;

                if (! $note) {
                    return [];
                }

                return [
                    'user_id' => $note->user_id,
                    'category' => $note->category,
                    'title' => $note->title,
                    'start_date' => $note->start_date?->toDateString(),
                    'end_date' => $note->end_date?->toDateString(),
                    'notes' => $note->notes,
                ];
            })
            ->schema(fn (): array => CalendarNoteResource::formComponents())
            ->action(function (array $data, array $arguments): void {
                if (! $this->canManageNotes() || ! Schema::hasTable('calendar_notes')) {
                    return;
                }

                $noteId = (int) ($arguments['noteId'] ?? 0);
                $note = CalendarNote::query()->find($noteId);
                if (! $note) {
                    return;
                }

                $note->update([
                    'user_id' => $data['user_id'] ?: null,
                    'title' => trim((string) $data['title']),
                    'category' => (string) $data['category'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
                ]);

                $startCarbon = Carbon::parse($data['start_date']);
                $this->viewYear = (int) $startCarbon->year;
                $this->viewMonth = (int) $startCarbon->month;

                Notification::make()
                    ->title('Catatan kalender diperbarui')
                    ->success()
                    ->send();
            });
    }

    public function deleteNoteAction(): Action
    {
        return Action::make('deleteNote')
            ->label('Hapus')
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->visible(fn (): bool => $this->canManageNotes())
            ->requiresConfirmation()
            ->modalHeading('Hapus Catatan Kalender')
            ->modalDescription('Apakah Anda yakin ingin menghapus catatan agenda ini?')
            ->modalSubmitActionLabel('Ya, Hapus')
            ->action(function (array $arguments): void {
                if (! $this->canManageNotes() || ! Schema::hasTable('calendar_notes')) {
                    return;
                }

                $noteId = (int) ($arguments['noteId'] ?? 0);
                $note = CalendarNote::query()->find($noteId);
                if (! $note) {
                    return;
                }

                $note->delete();

                Notification::make()
                    ->title('Catatan kalender dihapus')
                    ->success()
                    ->send();
            });
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
        $holidayService = app(IndonesianHolidayService::class);
        $monthHolidays = $holidayService->getHolidaysForMonth($viewYear, $viewMonth);

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

        // 4. Filter & kelompokkan catatan + libur berurutan agar daftar tetap ringkas dan stabil tingginya
        $scopedNotes = $this->selectedDate
            ? collect($notesByDate[$this->selectedDate] ?? [])
            : $monthNotes;

        $scopedHolidayMap = $this->selectedDate
            ? (isset($monthHolidays[$this->selectedDate]) ? [$this->selectedDate => $monthHolidays[$this->selectedDate]] : [])
            : $monthHolidays;

        $groupedHolidays = $holidayService->groupConsecutiveHolidays($scopedHolidayMap, $shortMonths);

        $visibleNotes = $this->agendaTab === 'holidays' ? collect() : $scopedNotes;
        $visibleHolidays = $this->agendaTab === 'notes' ? [] : $groupedHolidays;

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
        $manageUrl = null;
        if ($canManage) {
            try {
                $manageUrl = CalendarNoteResource::getUrl('index');
            } catch (\Throwable) {
                $manageUrl = null;
            }
        }

        $filterMonth = (int) ($this->pageFilters['month'] ?? $now->month);
        $filterYear = (int) ($this->pageFilters['year'] ?? $now->year);
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
            'notesCount' => $scopedNotes->count(),
            'holidaysCount' => count($groupedHolidays),
            'selectedDateLabel' => $selectedDateLabel,
            'canManageNotes' => $canManage,
            'manageUrl' => $manageUrl,
        ];
    }
}
