<?php

namespace App\Services;

use App\Enums\AnomalyKind;
use App\Enums\AnomalyPriority;
use App\Enums\AnomalyStatus;
use App\Enums\SensorStatus;
use App\Models\Anomaly;
use App\Models\AppSetting;
use App\Models\Device;
use App\Models\ProcessingFailure;
use App\Models\ShelfEvent;
use BackedEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class AnomalySync
{
    /** @var Collection<string, Anomaly>|null */
    private ?Collection $indexed = null;

    public function __construct(private readonly StockLedger $stock) {}

    public function sync(): void
    {
        $this->stock->expireAll();
        $this->syncWeight();
        $this->syncOffline();
        $this->syncSensors();
        $this->syncFailures();
    }

    private function syncWeight(): void
    {
        $open = [];

        ShelfEvent::query()
            ->with('device')
            ->whereIn('match_status', ['unmatched', 'needs_review'])
            ->orderBy('id')
            ->each(function (ShelfEvent $event) use (&$open): void {
                $key = 'event:'.$event->id;
                $open[] = $key;
                $needsReview = $event->match_status === 'needs_review';

                $this->ensure($key, [
                    'device_id' => $event->device_id,
                    'shelf_event_id' => $event->id,
                    'processing_failure_id' => null,
                    'kind' => AnomalyKind::WeightReview,
                    'title' => Anomaly::TITLE,
                    'summary' => $needsReview
                        ? 'Perubahan berat memiliki lebih dari satu kemungkinan dan perlu pemeriksaan.'
                        : 'Perubahan berat belum memiliki transaksi yang cocok dan perlu pemeriksaan.',
                    'priority' => $needsReview ? AnomalyPriority::High : AnomalyPriority::Medium,
                ]);
            });

        $this->closeCleared(AnomalyKind::WeightReview, $open);
    }

    private function syncOffline(): void
    {
        $open = [];
        $window = AppSetting::offlineSeconds();

        Device::query()->where('is_active', true)->orderBy('id')->each(function (Device $device) use (&$open, $window): void {
            $offline = $device->last_seen_at === null
                || ! $device->last_seen_at->greaterThan(now()->subSeconds($window));

            if (! $offline) {
                return;
            }

            $key = 'offline:'.$device->id;
            $open[] = $key;
            $never = $device->last_seen_at === null;

            $this->ensure($key, [
                'device_id' => $device->id,
                'shelf_event_id' => null,
                'processing_failure_id' => null,
                'kind' => AnomalyKind::Offline,
                'title' => Anomaly::TITLE,
                'summary' => $never
                    ? 'Perangkat aktif belum pernah mengirim data.'
                    : 'Perangkat melewati batas waktu tanpa data baru.',
                'priority' => $never ? AnomalyPriority::Low : AnomalyPriority::High,
            ]);
        });

        $this->closeCleared(AnomalyKind::Offline, $open);
    }

    private function syncSensors(): void
    {
        $open = [];

        Device::query()
            ->whereIn('sensor_status', [SensorStatus::Unstable->value, SensorStatus::Noisy->value])
            ->orderBy('id')
            ->each(function (Device $device) use (&$open): void {
                $key = 'sensor:'.$device->id;
                $open[] = $key;
                $noisy = $device->sensor_status === SensorStatus::Noisy;

                $this->ensure($key, [
                    'device_id' => $device->id,
                    'shelf_event_id' => null,
                    'processing_failure_id' => null,
                    'kind' => AnomalyKind::Sensor,
                    'title' => Anomaly::TITLE,
                    'summary' => $noisy
                        ? 'Sensor menunjukkan indikasi noise atau pembacaan tidak wajar.'
                        : 'Sensor ditandai tidak stabil.',
                    'priority' => $noisy ? AnomalyPriority::High : AnomalyPriority::Medium,
                ]);
            });

        $this->closeCleared(AnomalyKind::Sensor, $open);
    }

    private function syncFailures(): void
    {
        ProcessingFailure::query()->latest('id')->limit(100)->get()->each(function (ProcessingFailure $failure): void {
            $this->ensure('failure:'.$failure->id, [
                'device_id' => $failure->device_id,
                'shelf_event_id' => null,
                'processing_failure_id' => $failure->id,
                'kind' => AnomalyKind::Delivery,
                'title' => Anomaly::TITLE,
                'summary' => 'Peristiwa gagal diproses: '.$failure->summary,
                'priority' => AnomalyPriority::High,
            ]);
        });
    }

    /**
     * @param  list<string>  $openKeys
     */
    private function closeCleared(AnomalyKind $kind, array $openKeys): void
    {
        $query = Anomaly::query()
            ->where('kind', $kind->value)
            ->whereIn('status', [AnomalyStatus::New->value, AnomalyStatus::Reviewing->value]);

        if ($openKeys !== []) {
            $query->whereNotIn('dedupe_key', $openKeys);
        }

        $query->update(['status' => AnomalyStatus::Resolved->value]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function ensure(string $key, array $attributes): void
    {
        $existing = $this->index()->get($key);

        if ($existing === null) {
            $created = Anomaly::query()->create($attributes + [
                'dedupe_key' => $key,
                'status' => AnomalyStatus::New,
            ]);
            $this->index()->put($key, $created);

            return;
        }

        $changes = Arr::except($attributes, ['status', 'note', 'ignore_reason']);
        if ($this->unchanged($existing, $changes)) {
            return;
        }

        $existing->fill($changes)->save();
    }

    /**
     * @return Collection<string, Anomaly>
     */
    private function index(): Collection
    {
        return $this->indexed ??= Anomaly::query()->get()->keyBy('dedupe_key');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function unchanged(Anomaly $existing, array $attributes): bool
    {
        foreach ($attributes as $key => $value) {
            $current = $existing->getAttribute($key);
            if ($current instanceof BackedEnum) {
                $current = $current->value;
            }
            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }
            if ($current != $value) {
                return false;
            }
        }

        return true;
    }
}
