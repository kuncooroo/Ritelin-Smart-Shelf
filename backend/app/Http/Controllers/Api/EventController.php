<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\ShelfEvent;
use App\Models\WeightReading;
use App\Services\AnomalySync;
use App\Services\ProcessingFailureRecorder;
use App\Services\ShelfMatcher;
use App\Services\StockLedger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function store(
        Request $request,
        ShelfMatcher $matcher,
        AnomalySync $anomalies,
        ProcessingFailureRecorder $failures,
        StockLedger $stock,
    ): JsonResponse {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $validator = Validator::make($request->all(), [
            'device_id' => ['required', 'string', 'max:32', 'in:'.$device->code],
            'event_id' => ['required', 'string', 'max:32', 'regex:/^evt-[A-Za-z0-9_-]+$/'],
            'event_type' => ['required', 'in:ITEM_REMOVED,ITEM_RETURNED,ITEM_ADDED'],
            'weight_grams' => ['required', 'numeric', 'between:-5000,5000'],
            'timestamp' => ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/'],
        ]);

        if ($validator->fails()) {
            $failures->record($device, (string) $validator->errors()->first());
            throw new ValidationException($validator);
        }

        $data = $validator->validated();
        $before = $device->last_weight_grams;
        $this->touchDevice($request, $device, $data['weight_grams']);

        $existing = ShelfEvent::query()
            ->where('device_id', $device->id)
            ->where('event_id', $data['event_id'])
            ->first();

        if ($existing !== null) {
            return $this->payload($device, $existing, true, 200);
        }

        try {
            $event = DB::transaction(function () use ($device, $data, $before): ShelfEvent {
                $created = ShelfEvent::query()->create([
                    'device_id' => $device->id,
                    'event_id' => $data['event_id'],
                    'event_type' => $data['event_type'],
                    'weight_grams' => $data['weight_grams'],
                    'weight_before_grams' => $before,
                    'occurred_at' => $data['timestamp'],
                ]);

                WeightReading::query()->create([
                    'device_id' => $device->id,
                    'weight_grams' => $data['weight_grams'],
                    'recorded_at' => $data['timestamp'],
                    'source' => 'event',
                ]);

                return $created;
            });
        } catch (UniqueConstraintViolationException) {
            $event = ShelfEvent::query()
                ->where('device_id', $device->id)
                ->where('event_id', $data['event_id'])
                ->firstOrFail();

            return $this->payload($device, $event, true, 200);
        }

        $stock->apply($device->fresh());
        $matcher->reconcile($device->fresh());
        $anomalies->sync();

        return $this->payload($device->fresh(), $event->fresh(), false, 201);
    }

    private function touchDevice(Request $request, Device $device, mixed $weight): void
    {
        $values = [
            'last_seen_at' => now(),
            'last_weight_grams' => $weight,
        ];

        if ($request->headers->has('X-Firmware-Version')) {
            $values['firmware_version'] = substr((string) $request->header('X-Firmware-Version'), 0, 32);
        }

        $device->forceFill($values)->save();
    }

    private function payload(Device $device, ShelfEvent $event, bool $duplicate, int $status): JsonResponse
    {
        return response()->json([
            'duplicate' => $duplicate,
            'data' => [
                'device_id' => $device->code,
                'event_id' => $event->event_id,
                'event_type' => $event->event_type,
                'weight_grams' => (float) $event->weight_grams,
                'timestamp' => $event->occurred_at->utc()->format('Y-m-d\TH:i:s\Z'),
                'physical_stock' => $device->physical_stock,
                'stock_status' => $device->stock_status,
            ],
        ], $status);
    }
}
