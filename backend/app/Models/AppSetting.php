<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class AppSetting extends Model
{
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $value = static::query()->where('key', $key)->value('value');

        if ($value === null || $value === '') {
            return $default;
        }

        return $value;
    }

    public static function putValue(string $key, string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function offlineSeconds(): int
    {
        return (int) static::getValue('offline_seconds', config('ritelin.online_seconds'));
    }

    public static function thresholdGrams(): float
    {
        return (float) static::getValue('threshold_grams', config('ritelin.threshold_grams'));
    }
}
