<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\AuditLogger;
use App\Services\StockLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TareController extends Controller
{
    public function store(Request $request, StockLedger $stock, AuditLogger $audit): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $data = $request->validate([
            'device_id' => ['required', 'string', 'max:32', 'in:'.$device->code],
            'weight_grams' => ['required', 'numeric', 'between:-5000,5000'],
        ]);

        $stock->acceptTare($device, (float) $data['weight_grams']);
        $device->refresh();
        $audit->record('device.tare', 'Tare '.$device->code.' diterima. Offset tersimpan di perangkat dan berat dasar di server menjadi 0 gram.', $device);

        return response()->json([
            'device_id' => $device->code,
            'baseline_weight_grams' => (float) $device->baseline_weight_grams,
            'physical_stock' => $device->physical_stock,
            'pos_stock' => $device->pos_stock,
        ]);
    }
}
