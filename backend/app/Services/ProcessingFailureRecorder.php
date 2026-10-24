<?php

namespace App\Services;

use App\Models\Device;
use App\Models\ProcessingFailure;

class ProcessingFailureRecorder
{
    public function record(Device $device, string $summary): void
    {
        ProcessingFailure::query()->create([
            'device_id' => $device->id,
            'summary' => mb_substr($summary, 0, 255),
        ]);
    }
}
