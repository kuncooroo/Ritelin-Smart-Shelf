<?php

namespace App\Enums;

enum AnomalyStatus: string
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case Resolved = 'resolved';
    case Ignored = 'ignored';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Baru',
            self::Reviewing => 'Sedang diperiksa',
            self::Resolved => 'Selesai',
            self::Ignored => 'Diabaikan',
        };
    }
}
