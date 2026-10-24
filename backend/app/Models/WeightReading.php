<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['device_id', 'weight_grams', 'recorded_at', 'source'])]
class WeightReading extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight_grams' => 'decimal:1',
            'recorded_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            'event' => 'Peristiwa rak',
            'heartbeat' => 'Pembacaan berkala',
            default => 'Catatan demo',
        };
    }
}
