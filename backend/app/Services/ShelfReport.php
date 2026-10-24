<?php

namespace App\Services;

use App\Models\Anomaly;
use App\Models\Device;
use App\Models\PosTransaction;
use App\Models\ShelfEvent;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ShelfReport
{
    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function range(?string $from, ?string $to): array
    {
        $from = $from === '' ? null : $from;
        $to = $to === '' ? null : $to;

        validator([
            'from' => $from,
            'to' => $to,
        ], [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ])->validate();

        $end = ($to === null || $to === '')
            ? now('Asia/Jakarta')->endOfDay()
            : Carbon::parse($to, 'Asia/Jakarta')->endOfDay();
        $start = ($from === null || $from === '')
            ? $end->copy()->subDays(6)->startOfDay()
            : Carbon::parse($from, 'Asia/Jakarta')->startOfDay();

        if ($start->gt($end)) {
            throw ValidationException::withMessages([
                'to' => 'Tanggal akhir harus sama atau setelah tanggal awal.',
            ]);
        }

        if ($start->diffInDays($end) > 366) {
            throw ValidationException::withMessages([
                'from' => 'Rentang laporan paling lama 366 hari.',
            ]);
        }

        return [$start, $end];
    }

    /**
     * @return array<string, mixed>
     */
    public function build(Carbon $start, Carbon $end): array
    {
        $utcStart = $start->copy()->utc();
        $utcEnd = $end->copy()->utc();

        $events = ShelfEvent::query()
            ->with('device.product')
            ->whereBetween('occurred_at', [$utcStart, $utcEnd])
            ->get();
        $transactions = PosTransaction::query()
            ->with('product')
            ->whereBetween('occurred_at', [$utcStart, $utcEnd])
            ->get();
        $anomalies = Anomaly::query()
            ->with('device.product')
            ->whereBetween('created_at', [$utcStart, $utcEnd])
            ->get();

        $days = [];
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();
        while ($cursor->lte($last)) {
            $days[$cursor->toDateString()] = [
                'date' => $cursor->toDateString(),
                'removed' => 0,
                'returned' => 0,
                'transactions' => 0,
                'anomalies' => 0,
            ];
            $cursor->addDay();
        }

        foreach ($events as $event) {
            $key = $event->occurred_at->timezone('Asia/Jakarta')->toDateString();
            if (! isset($days[$key])) {
                continue;
            }
            $days[$key][$event->event_type === 'ITEM_RETURNED' ? 'returned' : 'removed']++;
        }

        foreach ($transactions as $transaction) {
            $key = $transaction->occurred_at->timezone('Asia/Jakarta')->toDateString();
            if (isset($days[$key])) {
                $days[$key]['transactions']++;
            }
        }

        foreach ($anomalies as $anomaly) {
            $key = $anomaly->created_at->timezone('Asia/Jakarta')->toDateString();
            if (isset($days[$key])) {
                $days[$key]['anomalies']++;
            }
        }

        $products = $events
            ->where('event_type', 'ITEM_REMOVED')
            ->groupBy(fn (ShelfEvent $event): string => (string) ($event->device->product_id ?? 'none'))
            ->map(function ($group): array {
                $product = $group->first()->device->product;

                return [
                    'name' => $product?->name ?? 'Belum dipetakan',
                    'sku' => $product?->sku ?? '—',
                    'removed' => $group->count(),
                ];
            })
            ->sortByDesc('removed')
            ->take(8)
            ->values();

        $byShelf = $anomalies
            ->groupBy(fn (Anomaly $anomaly): string => (string) ($anomaly->device_id ?? 'none'))
            ->map(function ($group): array {
                $device = $group->first()->device;

                return [
                    'code' => $device?->code ?? 'Tanpa perangkat',
                    'product' => $device?->product?->name ?? 'Belum dipetakan',
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('count')
            ->values();

        $devices = Device::query()->with('product')->orderBy('code')->get()->map(function (Device $device) use ($events, $anomalies): array {
            return [
                'code' => $device->code,
                'name' => $device->name,
                'connection' => $device->connectionLabel(),
                'last_seen' => $device->last_seen_at?->timezone('Asia/Jakarta')->format('d-m-Y H:i') ?? 'Belum ada',
                'events' => $events->where('device_id', $device->id)->count(),
                'anomalies' => $anomalies->where('device_id', $device->id)->count(),
            ];
        });

        return [
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'days' => array_values($days),
            'products' => $products->all(),
            'shelves' => $byShelf->all(),
            'devices' => $devices->all(),
            'totals' => [
                'events' => $events->count(),
                'removed' => $events->where('event_type', 'ITEM_REMOVED')->count(),
                'returned' => $events->where('event_type', 'ITEM_RETURNED')->count(),
                'transactions' => $transactions->count(),
                'matched' => $events->where('match_status', 'matched')->count(),
                'unmatched' => $events->where('match_status', 'unmatched')->count(),
                'needs_review' => $events->where('match_status', 'needs_review')->count(),
                'anomalies' => $anomalies->count(),
            ],
        ];
    }
}
