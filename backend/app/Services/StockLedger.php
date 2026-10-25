<?php

namespace App\Services;

use App\Enums\AnomalyKind;
use App\Enums\AnomalyPriority;
use App\Enums\AnomalyStatus;
use App\Models\Anomaly;
use App\Models\AppSetting;
use App\Models\Device;
use App\Models\PosTransaction;

class StockLedger
{
    public function apply(Device $device): void
    {
        $device->loadMissing('product');
        $next = $this->units($device);
        $previous = $device->physical_stock;

        if ($next === null) {
            $device->forceFill([
                'physical_stock' => null,
                'stock_status' => 'unknown',
            ])->save();

            return;
        }

        $device->physical_stock = $next;

        if ($device->pos_stock === null) {
            $device->forceFill([
                'pos_stock' => $next,
                'stock_status' => 'aligned',
                'stock_watch_from' => null,
                'stock_watch_until' => null,
                'stock_drop_units' => null,
            ])->save();

            return;
        }

        if ($device->stock_status === 'awaiting') {
            if ($next >= (int) $device->pos_stock) {
                $this->align($device);

                return;
            }

            $device->save();
            $this->evaluate($device->fresh());

            return;
        }

        if ($previous !== null && $next < $previous) {
            $device->forceFill([
                'stock_drop_units' => $previous - $next,
                'stock_watch_from' => now(),
                'stock_watch_until' => now()->addMinutes(max(1, AppSetting::stockWindowMinutes())),
                'stock_status' => 'awaiting',
            ])->save();
            $this->evaluate($device->fresh());

            return;
        }

        $device->stock_status = $next === (int) $device->pos_stock
            ? 'aligned'
            : ($next > (int) $device->pos_stock ? 'above' : 'review');
        $device->save();

        if ($device->stock_status === 'aligned') {
            $this->resolveGap($device);
        }
    }

    public function acceptTare(Device $device, float $weight): void
    {
        $device->forceFill([
            'tare_requested' => false,
            'baseline_weight_grams' => 0,
            'last_weight_grams' => $weight,
            'last_calibrated_at' => now(),
            'last_seen_at' => now(),
            'physical_stock' => null,
            'pos_stock' => null,
            'stock_status' => 'unknown',
            'stock_watch_from' => null,
            'stock_watch_until' => null,
            'stock_drop_units' => null,
        ])->save();
        $this->resolveGap($device);
        $this->apply($device->fresh());
    }

    public function expireAll(): void
    {
        Device::query()
            ->where('stock_status', 'awaiting')
            ->orderBy('id')
            ->each(function (Device $device): void {
                $this->evaluate($device);
            });
    }

    public function evaluate(Device $device): void
    {
        if ($device->stock_status !== 'awaiting' || $device->stock_watch_from === null || $device->stock_watch_until === null) {
            return;
        }

        $drop = (int) $device->stock_drop_units;
        $matched = $this->matchedQuantity($device);

        if ($drop > 0 && $matched === $drop) {
            $this->align($device->fresh());

            return;
        }

        if (now()->greaterThan($device->stock_watch_until)) {
            $gap = max($drop - $matched, 0);
            $device->forceFill([
                'stock_status' => 'review',
                'stock_watch_from' => null,
                'stock_watch_until' => null,
                'stock_drop_units' => null,
            ])->save();
            if ($gap > 0) {
                $this->openGap($device->fresh(), $gap);
            }
        }
    }

    private function units(Device $device): ?int
    {
        return PhysicalStock::units(
            $device->last_weight_grams,
            $device->product?->unit_weight_grams,
        );
    }

    private function matchedQuantity(Device $device): int
    {
        if ($device->product_id === null || $device->stock_watch_from === null || $device->stock_watch_until === null) {
            return 0;
        }

        $from = $device->stock_watch_from;

        return (int) PosTransaction::query()
            ->where('product_id', $device->product_id)
            ->where('occurred_at', '>=', $from)
            ->where('occurred_at', '<=', $device->stock_watch_until)
            ->sum('quantity');
    }

    private function align(Device $device): void
    {
        $device->forceFill([
            'pos_stock' => $device->physical_stock,
            'stock_status' => 'aligned',
            'stock_watch_from' => null,
            'stock_watch_until' => null,
            'stock_drop_units' => null,
        ])->save();
        $this->resolveGap($device);
    }

    private function openGap(Device $device, int $units): void
    {
        $key = 'stock:'.$device->id;
        $summary = 'Selisih stok '.$units.' unit pada '.$device->code.'. Jendela waktu berakhir tanpa transaksi kasir yang sesuai.';
        $existing = Anomaly::query()->where('dedupe_key', $key)->first();

        if ($existing === null) {
            Anomaly::query()->create([
                'dedupe_key' => $key,
                'device_id' => $device->id,
                'kind' => AnomalyKind::StockGap,
                'title' => Anomaly::TITLE,
                'summary' => $summary,
                'priority' => AnomalyPriority::High,
                'status' => AnomalyStatus::New,
            ]);

            return;
        }

        $existing->summary = $summary;
        $existing->priority = AnomalyPriority::High;
        $existing->title = Anomaly::TITLE;
        if ($existing->status === AnomalyStatus::Resolved) {
            $existing->status = AnomalyStatus::New;
            $existing->checked_at = null;
        }
        $existing->save();
    }

    private function resolveGap(Device $device): void
    {
        Anomaly::query()
            ->where('dedupe_key', 'stock:'.$device->id)
            ->whereIn('status', [AnomalyStatus::New->value, AnomalyStatus::Reviewing->value])
            ->update(['status' => AnomalyStatus::Resolved->value]);
    }
}
