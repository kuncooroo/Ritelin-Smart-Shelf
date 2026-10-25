<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Device;
use App\Models\WeightReading;
use App\Services\SensorStability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    public function show(Request $request, SensorStability $stability): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $values = ['last_seen_at' => now()];
        if ($request->headers->has('X-Firmware-Version')) {
            $values['firmware_version'] = substr((string) $request->header('X-Firmware-Version'), 0, 32);
        }

        $weight = $request->header('X-Weight-Grams');
        if (is_numeric($weight) && (float) $weight >= -5000 && (float) $weight <= 5000) {
            $values['last_weight_grams'] = $weight;
            WeightReading::query()->create([
                'device_id' => $device->id,
                'weight_grams' => $weight,
                'recorded_at' => now(),
                'source' => 'heartbeat',
            ]);
        }

        $device->forceFill($values)->save();

        if (array_key_exists('last_weight_grams', $values)) {
            $stability->observe($device->fresh());
        }

        return response()->json([
            'device_id' => $device->code,
            'threshold_grams' => $device->fresh()->effectiveThreshold(),
            'verify_ms' => (int) config('ritelin.verify_ms'),
            'match_window_minutes' => AppSetting::matchWindowMinutes(),
            'stock_window_minutes' => AppSetting::stockWindowMinutes(),
            'tare' => (bool) $device->fresh()->tare_requested,
        ]);
    }
}
