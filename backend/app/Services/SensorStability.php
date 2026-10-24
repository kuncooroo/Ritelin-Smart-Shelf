<?php

namespace App\Services;

use App\Enums\SensorStatus;
use App\Models\Device;
use App\Models\WeightReading;

class SensorStability
{
    public function observe(Device $device): void
    {
        $samples = WeightReading::query()
            ->where('device_id', $device->id)
            ->where('source', 'heartbeat')
            ->orderByDesc('recorded_at')
            ->limit(5)
            ->pluck('weight_grams');

        if ($samples->count() < 5) {
            return;
        }

        $span = (float) $samples->max() - (float) $samples->min();
        $threshold = $device->effectiveThreshold();
        $status = match (true) {
            $span <= $threshold => SensorStatus::Stable,
            $span <= ($threshold * 3) => SensorStatus::Unstable,
            default => SensorStatus::Noisy,
        };

        if ($device->sensor_status !== $status) {
            $device->forceFill(['sensor_status' => $status])->save();
        }
    }
}
