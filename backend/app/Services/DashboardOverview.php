<?php

namespace App\Services;

use App\Enums\AnomalyStatus;
use App\Models\Anomaly;
use App\Models\Device;
use App\Models\ShelfEvent;
use Illuminate\Support\Collection;

class DashboardOverview
{
    public function __construct(private readonly AnomalySync $anomalies) {}

    /**
     * @return array<string, mixed>
     */
    public function present(): array
    {
        $this->anomalies->sync();

        $devices = Device::query()->with('product')->orderBy('code')->get();
        $today = $this->todayEvents();
        $removed = $today->where('event_type', 'ITEM_REMOVED')->count();
        $returned = $today->where('event_type', 'ITEM_RETURNED')->count();
        $hours = $this->hours($today);

        return [
            'registered' => $devices->count(),
            'online' => $devices->filter(fn (Device $device): bool => $device->isOnline())->count(),
            'offline' => $devices->filter(fn (Device $device): bool => $device->is_active && ! $device->isOnline())->count(),
            'monitored' => $devices->where('is_active', true)->count(),
            'removed_today' => $removed,
            'returned_today' => $returned,
            'open_anomalies' => Anomaly::query()->whereIn('status', [
                AnomalyStatus::New->value,
                AnomalyStatus::Reviewing->value,
            ])->count(),
            'hours' => $hours,
            'has_activity' => $removed + $returned > 0,
            'recent' => ShelfEvent::query()
                ->with(['device.product', 'transaction'])
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->limit(8)
                ->get(),
            'devices' => $devices,
        ];
    }

    /**
     * @return Collection<int, ShelfEvent>
     */
    private function todayEvents(): Collection
    {
        $start = now('Asia/Jakarta')->startOfDay()->utc();
        $end = now('Asia/Jakarta')->endOfDay()->utc();

        return ShelfEvent::query()
            ->whereBetween('occurred_at', [$start, $end])
            ->get();
    }

    /**
     * @param  Collection<int, ShelfEvent>  $events
     * @return list<array{label: string, removed: int, returned: int, removed_height: int, returned_height: int}>
     */
    private function hours(Collection $events): array
    {
        $buckets = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $buckets[$hour] = ['removed' => 0, 'returned' => 0];
        }

        foreach ($events as $event) {
            $hour = (int) $event->occurred_at->timezone('Asia/Jakarta')->format('G');
            $key = $event->event_type === 'ITEM_RETURNED' ? 'returned' : 'removed';
            $buckets[$hour][$key]++;
        }

        $peak = 1;
        foreach ($buckets as $bucket) {
            $peak = max($peak, $bucket['removed'], $bucket['returned']);
        }

        $rows = [];
        foreach ($buckets as $hour => $bucket) {
            $rows[] = [
                'label' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT),
                'removed' => $bucket['removed'],
                'returned' => $bucket['returned'],
                'removed_height' => (int) round($bucket['removed'] / $peak * 100),
                'returned_height' => (int) round($bucket['returned'] / $peak * 100),
            ];
        }

        return $rows;
    }
}
