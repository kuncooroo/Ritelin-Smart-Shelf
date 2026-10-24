<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['device_id', 'summary'])]
class ProcessingFailure extends Model
{
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
