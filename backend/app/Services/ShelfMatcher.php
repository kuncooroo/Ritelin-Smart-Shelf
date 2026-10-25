<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Device;
use App\Models\PosTransaction;
use App\Models\ShelfEvent;
use Illuminate\Support\Collection;

class ShelfMatcher
{
    public function reconcileAll(): void
    {
        Device::query()->orderBy('id')->each(function (Device $device): void {
            $this->reconcile($device);
        });
    }

    public function reconcile(Device $device): void
    {
        $window = AppSetting::matchWindowMinutes();
        $lead = AppSetting::matchLeadMinutes();

        $removals = $device->events()
            ->where('event_type', 'ITEM_REMOVED')
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        foreach ($removals as $removal) {
            if ($removal->match_status === 'matched' || $removal->resolution === 'returned') {
                continue;
            }

            $start = $removal->occurred_at->copy()->subMinutes($lead);
            $end = $removal->occurred_at->copy()->addMinutes($window);

            $returned = $device->events()
                ->whereIn('event_type', ['ITEM_RETURNED', 'ITEM_ADDED'])
                ->where('occurred_at', '>=', $removal->occurred_at)
                ->where('occurred_at', '<=', $end)
                ->exists();

            $candidates = $this->candidates($device, $removal, $start, $end);

            if ($returned && $candidates->isEmpty()) {
                $this->store($removal, null, 'returned', 'Berat naik lagi di dalam jendela waktu.', null);

                continue;
            }

            if ($device->product_id === null) {
                $this->store($removal, 'needs_review', null, 'Perangkat belum dipetakan ke satu jenis produk.', null);

                continue;
            }

            $exact = $candidates
                ->filter(fn (PosTransaction $transaction): bool => $transaction->quantity === 1)
                ->values();

            if ($returned || $candidates->count() !== $exact->count() || $exact->count() > 1) {
                $this->store($removal, 'needs_review', null, 'Ada lebih dari satu kemungkinan atau jumlah transaksi bukan satu.', null);

                continue;
            }

            if ($exact->count() === 1) {
                $this->store(
                    $removal,
                    'matched',
                    null,
                    'Satu transaksi produk yang sama berada di jendela waktu.',
                    $exact->first()->id,
                );

                continue;
            }

            $closed = now()->greaterThan($end);
            $this->store(
                $removal,
                $closed ? 'unmatched' : 'pending',
                null,
                $closed
                    ? 'Jendela waktu berakhir tanpa transaksi yang cocok.'
                    : 'Menunggu transaksi di dalam jendela waktu.',
                null,
            );
        }
    }

    /**
     * @return Collection<int, PosTransaction>
     */
    private function candidates(Device $device, ShelfEvent $removal, mixed $start, mixed $end): Collection
    {
        if ($device->product_id === null) {
            return collect();
        }

        $used = ShelfEvent::query()
            ->whereNotNull('pos_transaction_id')
            ->where('id', '!=', $removal->id)
            ->pluck('pos_transaction_id');

        return PosTransaction::query()
            ->where('product_id', $device->product_id)
            ->whereBetween('occurred_at', [$start, $end])
            ->whereNotIn('id', $used)
            ->orderBy('occurred_at')
            ->get();
    }

    private function store(
        ShelfEvent $removal,
        ?string $status,
        ?string $resolution,
        string $note,
        ?int $transactionId,
    ): void {
        $removal->forceFill([
            'match_status' => $status,
            'resolution' => $resolution,
            'match_note' => $note,
            'pos_transaction_id' => $transactionId,
        ])->save();
    }
}
