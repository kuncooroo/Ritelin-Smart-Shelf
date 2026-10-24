<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'action', 'subject_type', 'subject_id', 'summary'])]
class AuditLog extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
