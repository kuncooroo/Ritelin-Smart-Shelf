<?php

namespace App\Models;

use App\Enums\SensorStatus;
use App\Enums\ShelfStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'store_location',
    'slot_label',
    'is_active',
    'token_hash',
    'product_id',
    'firmware_version',
    'last_seen_at',
    'last_weight_grams',
    'baseline_weight_grams',
    'threshold_grams',
    'shelf_status',
    'sensor_status',
    'last_calibrated_at',
])]
class Device extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'last_weight_grams' => 'decimal:1',
            'baseline_weight_grams' => 'decimal:1',
            'threshold_grams' => 'decimal:1',
            'shelf_status' => ShelfStatus::class,
            'sensor_status' => SensorStatus::class,
            'last_calibrated_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShelfEvent::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(WeightReading::class);
    }

    public function anomalies(): HasMany
    {
        return $this->hasMany(Anomaly::class);
    }

    public function effectiveThreshold(): float
    {
        if ($this->threshold_grams !== null && $this->threshold_grams !== '') {
            return (float) $this->threshold_grams;
        }

        return AppSetting::thresholdGrams();
    }

    public function isOnline(): bool
    {
        return $this->is_active
            && $this->last_seen_at !== null
            && $this->last_seen_at->greaterThan(now()->subSeconds(AppSetting::offlineSeconds()));
    }

    public function connectionLabel(): string
    {
        if (! $this->is_active) {
            return 'Nonaktif';
        }

        if ($this->last_seen_at === null) {
            return 'Belum mengirim';
        }

        return $this->isOnline() ? 'Online' : 'Offline';
    }

    public function weightDeltaGrams(): ?float
    {
        if ($this->last_weight_grams === null || $this->baseline_weight_grams === null) {
            return null;
        }

        return round((float) $this->last_weight_grams - (float) $this->baseline_weight_grams, 1);
    }
}
