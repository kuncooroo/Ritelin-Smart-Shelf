<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public function record(string $action, string $summary, ?Model $subject = null): void
    {
        AuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'summary' => mb_substr($summary, 0, 500),
        ]);
    }
}
