<?php

namespace App\Enums;

enum AgingZone: string
{
    case Normal = 'Normal';
    case Attention = 'Attention';
    case Warning = 'Warning';
    case Critical = 'Critical';

    public static function fromDays(int $days): self
    {
        if ($days <= 14) {
            return self::Normal;
        }

        if ($days <= 30) {
            return self::Attention;
        }

        if ($days <= 60) {
            return self::Warning;
        }

        return self::Critical;
    }

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal (0–14 Hari)',
            self::Attention => 'Attention (15–30 Hari)',
            self::Warning => 'Warning (31–60 Hari)',
            self::Critical => 'Critical (> 60 Hari)',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Normal => 'Normal (0–14H)',
            self::Attention => 'Attention (15–30H)',
            self::Warning => 'Warning (31–60H)',
            self::Critical => 'Critical (>60H)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Normal => 'success',
            self::Attention => 'info',
            self::Warning => 'warning',
            self::Critical => 'danger',
        };
    }

    public function escalationOwner(): string
    {
        return match ($this) {
            self::Normal => 'Admin Receiving → PIC Unit',
            self::Attention => 'Koordinator Receiving → PIC Unit',
            self::Warning => 'AVP Receiving → AVP Unit Peminta',
            self::Critical => 'VP PP&P → VP Unit Peminta',
        };
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
