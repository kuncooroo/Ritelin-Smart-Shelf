<?php

namespace App\Enums;

enum SensorStatus: string
{
    case Unknown = 'unknown';
    case Stable = 'stable';
    case Unstable = 'unstable';
    case Noisy = 'noisy';

    public function label(): string
    {
        return match ($this) {
            self::Unknown => 'Belum ditentukan',
            self::Stable => 'Stabil',
            self::Unstable => 'Tidak stabil',
            self::Noisy => 'Noise',
        };
    }
}
