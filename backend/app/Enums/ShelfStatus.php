<?php

namespace App\Enums;

enum ShelfStatus: string
{
    case Normal = 'normal';
    case NeedsCalibration = 'needs_calibration';
    case Problem = 'problem';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::NeedsCalibration => 'Perlu kalibrasi',
            self::Problem => 'Bermasalah',
        };
    }
}
