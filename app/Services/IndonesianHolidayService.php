<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class IndonesianHolidayService
{
    public const PRIMARY_FEED_URL = 'https://raw.githubusercontent.com/guangrei/APIHariLibur_V2/main/calendar.json';

    public const FALLBACK_API_TEMPLATE = 'https://date.nager.at/api/v3/PublicHolidays/%d/ID';

    /**
     * Mengambil seluruh hari libur nasional Indonesia (dan cuti bersama) untuk satu tahun tertentu.
     * Hasil dicache selama 7 hari agar halaman utama tetap cepat.
     *
     * @return array<string, array{date: string, name: string, is_joint_leave: bool}>
     */
    public function getHolidaysForYear(int $year): array
    {
        if ($year < 2000 || $year > 2100) {
            return [];
        }

        return Cache::remember(
            "pkt_id_holidays_v1_{$year}",
            now()->addDays(7),
            fn (): array => $this->fetchHolidaysForYear($year),
        );
    }

    /**
     * Mengambil hari libur nasional Indonesia khusus untuk satu bulan tertentu.
     *
     * @return array<string, array{date: string, name: string, is_joint_leave: bool}>
     */
    public function getHolidaysForMonth(int $year, int $month): array
    {
        $prefix = sprintf('%04d-%02d-', $year, $month);
        $holidays = $this->getHolidaysForYear($year);

        $filtered = [];
        foreach ($holidays as $date => $item) {
            if (str_starts_with($date, $prefix)) {
                $filtered[$date] = $item;
            }
        }

        ksort($filtered);

        return $filtered;
    }

    /**
     * Mengelompokkan hari libur berurutan dengan nama yang sama menjadi satu baris rentang tanggal
     * (mis. 10 Mar + 11 Mar "Hari Idul Fitri" -> "10–11 Mar") agar tampilan kartu kalender tetap ringkas.
     *
     * @param  array<string, array{date: string, name: string, is_joint_leave: bool}>  $holidays
     * @param  array<int, string>  $shortMonths
     * @return list<array{start_date: string, end_date: string, range_label: string, name: string, is_joint_leave: bool}>
     */
    public function groupConsecutiveHolidays(array $holidays, array $shortMonths = []): array
    {
        if ($holidays === []) {
            return [];
        }

        if ($shortMonths === []) {
            $shortMonths = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
                9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
            ];
        }

        ksort($holidays);
        $groups = [];

        foreach ($holidays as $dateStr => $item) {
            $current = Carbon::parse($dateStr);
            $lastIdx = count($groups) - 1;

            if (
                $lastIdx >= 0
                && $groups[$lastIdx]['name'] === $item['name']
                && $groups[$lastIdx]['is_joint_leave'] === $item['is_joint_leave']
                && Carbon::parse($groups[$lastIdx]['end_date'])->addDay()->isSameDay($current)
            ) {
                $groups[$lastIdx]['end_date'] = $dateStr;
            } else {
                $groups[] = [
                    'start_date' => $dateStr,
                    'end_date' => $dateStr,
                    'range_label' => '',
                    'name' => $item['name'],
                    'is_joint_leave' => $item['is_joint_leave'],
                ];
            }
        }

        foreach ($groups as &$group) {
            $start = Carbon::parse($group['start_date']);
            $end = Carbon::parse($group['end_date']);
            $startMonth = $shortMonths[(int) $start->month] ?? $start->format('M');
            $endMonth = $shortMonths[(int) $end->month] ?? $end->format('M');

            if ($start->isSameDay($end)) {
                $group['range_label'] = "{$start->day} {$startMonth}";
            } elseif ($start->month === $end->month && $start->year === $end->year) {
                $group['range_label'] = "{$start->day}–{$end->day} {$startMonth}";
            } else {
                $group['range_label'] = "{$start->day} {$startMonth}–{$end->day} {$endMonth}";
            }
        }
        unset($group);

        return $groups;
    }

    /**
     * @return array<string, array{date: string, name: string, is_joint_leave: bool}>
     */
    protected function fetchHolidaysForYear(int $year): array
    {
        $fromPrimary = $this->fetchFromPrimaryCalendarFeed($year);
        if ($fromPrimary !== []) {
            return $fromPrimary;
        }

        $fromNager = $this->fetchFromNagerApi($year);
        if ($fromNager !== []) {
            return $fromNager;
        }

        return $this->fixedNationalHolidaysFallback($year);
    }

    /**
     * Sumber utama: APIHariLibur_V2 (mencakup seluruh libur nasional SKB 3 Menteri & cuti bersama).
     *
     * @return array<string, array{date: string, name: string, is_joint_leave: bool}>
     */
    protected function fetchFromPrimaryCalendarFeed(int $year): array
    {
        try {
            $response = Http::timeout(4)->acceptJson()->get(self::PRIMARY_FEED_URL);
            if (! $response->successful()) {
                return [];
            }

            $payload = $response->json();
            if (! is_array($payload)) {
                return [];
            }

            $yearPrefix = sprintf('%04d-', $year);
            $holidays = [];

            foreach ($payload as $rawDate => $entry) {
                if (! is_string($rawDate) || ! str_starts_with($rawDate, $yearPrefix) || ! is_array($entry)) {
                    continue;
                }

                if (! ($entry['holiday'] ?? false)) {
                    continue;
                }

                try {
                    $normalizedDate = Carbon::parse($rawDate)->toDateString();
                } catch (\Throwable) {
                    continue;
                }

                $summaries = array_values(array_filter(array_map(
                    static fn ($v): string => trim((string) $v),
                    is_array($entry['summary'] ?? null) ? $entry['summary'] : [(string) ($entry['summary'] ?? '')],
                )));

                if ($summaries === []) {
                    continue;
                }

                $descriptions = is_array($entry['description'] ?? null)
                    ? implode(' ', array_map('strval', $entry['description']))
                    : (string) ($entry['description'] ?? '');

                $name = implode(' / ', array_unique($summaries));
                $isJointLeave = str_contains(strtolower($name.' '.$descriptions), 'cuti bersama');

                $holidays[$normalizedDate] = [
                    'date' => $normalizedDate,
                    'name' => $name,
                    'is_joint_leave' => $isJointLeave,
                ];
            }

            ksort($holidays);

            return $holidays;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Sumber cadangan: Nager.Date Public Holidays API untuk Indonesia (mendukung tahun mendatang).
     *
     * @return array<string, array{date: string, name: string, is_joint_leave: bool}>
     */
    protected function fetchFromNagerApi(int $year): array
    {
        try {
            $url = sprintf(self::FALLBACK_API_TEMPLATE, $year);
            $response = Http::timeout(4)->acceptJson()->get($url);
            if (! $response->successful()) {
                return [];
            }

            $rows = $response->json();
            if (! is_array($rows)) {
                return [];
            }

            $holidays = [];
            foreach ($rows as $row) {
                if (! is_array($row) || empty($row['date'])) {
                    continue;
                }

                try {
                    $normalizedDate = Carbon::parse((string) $row['date'])->toDateString();
                } catch (\Throwable) {
                    continue;
                }

                $name = trim((string) ($row['localName'] ?? $row['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $holidays[$normalizedDate] = [
                    'date' => $normalizedDate,
                    'name' => $name,
                    'is_joint_leave' => false,
                ];
            }

            ksort($holidays);

            return $holidays;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Cadangan lokal libur tetap nasional Indonesia apabila seluruh endpoint eksternal tidak dapat diakses.
     *
     * @return array<string, array{date: string, name: string, is_joint_leave: bool}>
     */
    protected function fixedNationalHolidaysFallback(int $year): array
    {
        $fixed = [
            '01-01' => 'Tahun Baru Masehi',
            '05-01' => 'Hari Buruh Internasional',
            '06-01' => 'Hari Lahir Pancasila',
            '08-17' => 'Hari Kemerdekaan Republik Indonesia',
            '12-25' => 'Hari Raya Natal',
        ];

        $holidays = [];
        foreach ($fixed as $md => $name) {
            $date = sprintf('%04d-%s', $year, $md);
            $holidays[$date] = [
                'date' => $date,
                'name' => $name,
                'is_joint_leave' => false,
            ];
        }

        return $holidays;
    }
}
