<?php

namespace App\Services;

class PhysicalStock
{
    public static function units(mixed $weight, mixed $unitWeight): ?int
    {
        if ($weight === null || $weight === '' || $unitWeight === null || $unitWeight === '' || (float) $unitWeight <= 0) {
            return null;
        }

        $grams = (float) $weight;
        if ($grams <= 0) {
            return 0;
        }

        return (int) floor($grams / (float) $unitWeight);
    }
}
