<?php

namespace App\Models;

use App\Enums\AnomalyKind;
use App\Enums\AnomalyPriority;
use App\Enums\AnomalyStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'dedupe_key',
    'device_id',
    'shelf_event_id',
    'processing_failure_id',
    'kind',
    'title',
    'summary',
    'priority',
    'status',
    'note',
    'ignore_reason',
])]
class Anomaly extends Model
{
    public const TITLE = 'Anomali — perlu pemeriksaan';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => AnomalyKind::class,
            'priority' => AnomalyPriority::class,
            'status' => AnomalyStatus::class,
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(ShelfEvent::class, 'shelf_event_id');
    }

    public function failure(): BelongsTo
    {
        return $this->belongsTo(ProcessingFailure::class, 'processing_failure_id');
    }
}
