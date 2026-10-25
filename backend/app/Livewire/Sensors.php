<?php

namespace App\Livewire;

use App\Models\Device;
use App\Models\WeightReading;
use App\Services\AnomalySync;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\Concerns\RefreshesWithDashboard;
use App\Livewire\Concerns\WithPageSize;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Monitoring sensor'])]
class Sensors extends Component
{
    use RefreshesWithDashboard;
    use WithPageSize;
    use WithPagination;
    #[Url(as: 'perangkat')]
    public string $code = '';

    public function mount(AnomalySync $anomalies): void
    {
        $anomalies->sync();
        if ($this->code === '') {
            $this->code = (string) Device::query()->orderBy('code')->value('code');
        }
    }

    protected function pageNames(): array
    {
        return ['stock', 'readings'];
    }

    public function render(): View
    {
        $choices = Device::query()->orderBy('code')->get();
        $device = $choices->firstWhere('code', $this->code) ?? $choices->first();
        $latest = $device === null
            ? collect()
            : $device->readings()->orderByDesc('recorded_at')->orderByDesc('id')->limit(40)->get();
        $ascending = $latest->sortBy([
            ['recorded_at', 'asc'],
            ['id', 'asc'],
        ])->values();

        return view('livewire.sensors', [
            'devices' => Device::query()->with('product')->orderBy('code')->paginate($this->pageSize(), ['*'], 'stock'),
            'choices' => $choices,
            'device' => $device,
            'readings' => $device === null
                ? new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10, 1, ['pageName' => 'readings'])
                : $device->readings()->orderByDesc('recorded_at')->orderByDesc('id')->paginate($this->pageSize(), ['*'], 'readings'),
            'chart' => $this->chart($ascending, $device),
        ]);
    }

    /**
     * @param  Collection<int, WeightReading>  $readings
     * @return array{line: string, baseline: float|null}
     */
    private function chart(Collection $readings, ?Device $device): array
    {
        if ($device === null || $readings->isEmpty()) {
            return ['line' => '', 'baseline' => null];
        }

        $weights = $readings->map(fn (WeightReading $reading): float => (float) $reading->weight_grams);
        $min = (float) $weights->min();
        $max = (float) $weights->max();
        if ($device->baseline_weight_grams !== null) {
            $baseline = (float) $device->baseline_weight_grams;
            $min = min($min, $baseline);
            $max = max($max, $baseline);
        }
        if ($max <= $min) {
            $min -= 1;
            $max += 1;
        }

        $last = max($readings->count() - 1, 1);
        $points = [];
        foreach ($readings->values() as $index => $reading) {
            $x = round(($index / $last) * 100, 2);
            $y = round(100 - (((float) $reading->weight_grams - $min) / ($max - $min) * 100), 2);
            $points[] = $x.','.$y;
        }

        $baselineY = null;
        if ($device->baseline_weight_grams !== null) {
            $baselineY = round(100 - (((float) $device->baseline_weight_grams - $min) / ($max - $min) * 100), 2);
        }

        return ['line' => implode(' ', $points), 'baseline' => $baselineY];
    }
}
