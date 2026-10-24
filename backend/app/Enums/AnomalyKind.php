<?php

namespace App\Enums;

enum AnomalyKind: string
{
    case WeightReview = 'weight_review';
    case Sensor = 'sensor';
    case Offline = 'offline';
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::WeightReview => 'Perubahan berat',
            self::Sensor => 'Sensor',
            self::Offline => 'Perangkat offline',
            self::Delivery => 'Gagal diproses',
        };
    }
}
