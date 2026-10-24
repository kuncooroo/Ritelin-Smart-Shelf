<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['external_id', 'product_id', 'quantity', 'occurred_at', 'source'])]
class PosTransaction extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'quantity' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function shelfEvent(): HasOne
    {
        return $this->hasOne(ShelfEvent::class);
    }

    public function shelfLinkLabel(): string
    {
        if ($this->shelfEvent === null) {
            return 'Belum ada peristiwa rak';
        }

        return 'Terhubung ke '.$this->shelfEvent->event_id;
    }

    public function sourceLabel(): string
    {
        return $this->source === 'simulator' ? 'Simulator' : 'Webhook';
    }
}
