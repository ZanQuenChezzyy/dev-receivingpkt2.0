<?php

namespace App\Enums;

enum AgingFollowupStage: string
{
    case BelumDitindaklanjuti = 'Belum Ditindaklanjuti';
    case NotifikasiAwal = 'Notifikasi Awal (0-14H)';
    case ReminderTahap1 = 'Reminder Tahap 1 (15-30H)';
    case KonfirmasiAvp = 'Konfirmasi AVP (31-60H)';
    case EskalasiVp = 'Eskalasi VP (>60H)';
    case TerkonfirmasiJadwalAmbil = 'Terkonfirmasi Jadwal Ambil';
    case Selesai = 'Selesai';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match ($this) {
            self::BelumDitindaklanjuti => 'gray',
            self::NotifikasiAwal => 'info',
            self::ReminderTahap1 => 'primary',
            self::KonfirmasiAvp => 'warning',
            self::EskalasiVp => 'danger',
            self::TerkonfirmasiJadwalAmbil => 'success',
            self::Selesai => 'success',
        };
    }

    public function isFollowedUp(): bool
    {
        return $this !== self::BelumDitindaklanjuti;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
