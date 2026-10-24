<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'device_id',
    'event_id',
    'event_type',
    'weight_grams',
    'weight_before_grams',
    'occurred_at',
    'match_status',
    'resolution',
    'match_note',
    'staff_note',
    'pos_transaction_id',
])]
class ShelfEvent extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight_grams' => 'decimal:1',
            'weight_before_grams' => 'decimal:1',
            'occurred_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }

    public function activityLabel(): string
    {
        return $this->event_type === 'ITEM_REMOVED' ? 'Barang diambil' : 'Barang dikembalikan';
    }

    public function matchLabel(): string
    {
        if ($this->resolution === 'returned') {
            return 'Kembali ke rak';
        }

        return match ($this->match_status) {
            'matched' => 'Cocok dengan transaksi',
            'unmatched' => 'Belum ada transaksi',
            'needs_review' => 'Perlu ditinjau',
            'pending' => 'Menunggu jendela waktu',
            default => 'Belum dicocokkan',
        };
    }

    public function transactionGapLabel(): ?string
    {
        $transaction = $this->transaction;
        if ($transaction === null) {
            return null;
        }

        $seconds = $transaction->occurred_at->getTimestamp() - $this->occurred_at->getTimestamp();
        $minutes = intdiv(abs($seconds), 60);
        $when = match (true) {
            $seconds === 0 => 'pada waktu yang sama',
            $seconds > 0 => 'setelah peristiwa',
            default => 'sebelum peristiwa',
        };

        return $minutes.' menit '.$when;
    }
}
