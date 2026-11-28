<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'title',
    'category',
    'start_date',
    'end_date',
    'notes',
    'created_by',
])]
class CalendarNote extends Model
{
    public const CATEGORY_DINAS = 'dinas';

    public const CATEGORY_CUTI = 'cuti';

    public const CATEGORY_KEGIATAN = 'kegiatan';

    public const CATEGORY_CATATAN = 'catatan';

    /**
     * Role yang diizinkan menambah, mengubah, dan menghapus catatan pada kalender Halaman Utama.
     *
     * @var list<string>
     */
    public const MANAGER_ROLES = [
        'Sekretaris',
        'Developer',
    ];

    /**
     * @return array<string, string>
     */
    public static function categoryOptions(): array
    {
        return [
            self::CATEGORY_DINAS => 'Dinas',
            self::CATEGORY_CUTI => 'Cuti / Izin',
            self::CATEGORY_KEGIATAN => 'Kegiatan / Rapat',
            self::CATEGORY_CATATAN => 'Catatan Umum',
        ];
    }

    /**
     * Memeriksa apakah pengguna berhak mengelola catatan kalender (khusus Sekretaris dan Developer).
     */
    public static function canUserManage(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        try {
            return $user->hasAnyRole(self::MANAGER_ROLES);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function categoryLabel(): string
    {
        return self::categoryOptions()[$this->category] ?? 'Catatan';
    }

    public function formattedDateRange(): string
    {
        if (! $this->start_date) {
            return '-';
        }

        $shortMonths = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        $start = $this->start_date;
        $end = $this->end_date ?? $this->start_date;

        $startMonth = $shortMonths[(int) $start->month] ?? $start->format('M');
        $endMonth = $shortMonths[(int) $end->month] ?? $end->format('M');

        if ($start->isSameDay($end)) {
            return "{$start->day} {$startMonth}";
        }

        if ($start->month === $end->month && $start->year === $end->year) {
            return "{$start->day}–{$end->day} {$startMonth}";
        }

        return "{$start->day} {$startMonth}–{$end->day} {$endMonth}";
    }
}
