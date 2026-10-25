<?php

namespace App\Support;

use App\Enums\AnomalyStatus;
use App\Models\Anomaly;
use App\Models\ShelfEvent;

class Attention
{
    public static function unreadAnomalies(): int
    {
        return Anomaly::query()
            ->whereNull('checked_at')
            ->whereIn('status', [AnomalyStatus::New->value, AnomalyStatus::Reviewing->value])
            ->count();
    }

    public static function unreadReconciliation(): int
    {
        return ShelfEvent::query()
            ->where('event_type', 'ITEM_REMOVED')
            ->whereNull('checked_at')
            ->whereIn('match_status', ['needs_review', 'unmatched', 'pending'])
            ->count();
    }
}
