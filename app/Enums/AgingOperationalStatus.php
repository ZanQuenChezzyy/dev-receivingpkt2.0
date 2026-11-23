<?php

namespace App\Enums;

enum AgingOperationalStatus: string
{
    case BelumDiambil = 'Belum Diambil';
    case OnCheck = 'On Check';
    case ScheduledHold = 'Scheduled Hold';
    case Dipindah = 'Dipindah';
    case SelesaiDiambil = 'Selesai Diambil';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match ($this) {
            self::BelumDiambil => 'warning',
            self::OnCheck => 'info',
            self::ScheduledHold => 'primary',
            self::Dipindah => 'gray',
            self::SelesaiDiambil => 'success',
        };
    }

    public function isOutstanding(): bool
    {
        return in_array($this, [self::BelumDiambil, self::ScheduledHold], true);
    }

    public function isActiveAtReceiving(): bool
    {
        return in_array($this, [self::BelumDiambil, self::OnCheck, self::ScheduledHold], true);
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
