<?php

namespace App\Livewire;

use App\Models\Device;
use App\Models\Product;
use App\Models\ShelfEvent;
use App\Services\AnomalySync;
use App\Services\ShelfMatcher;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\Concerns\RefreshesWithDashboard;
use App\Livewire\Concerns\WithPageSize;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Riwayat aktivitas'])]
class Events extends Component
{
    use RefreshesWithDashboard;
    use WithPageSize;
    use WithPagination;
    #[Url]
    public string $date = '';

    #[Url]
    public string $productId = '';

    #[Url]
    public string $deviceId = '';

    #[Url]
    public string $eventType = '';

    public ?int $detailId = null;

    public function mount(ShelfMatcher $matcher, AnomalySync $anomalies): void
    {
        $matcher->reconcileAll();
        $anomalies->sync();
    }

    public function apply(): void
    {
        $this->validate([
            'date' => $this->date === '' ? ['nullable'] : ['date'],
            'productId' => $this->productId === '' ? ['nullable'] : ['exists:products,id'],
            'deviceId' => $this->deviceId === '' ? ['nullable'] : ['exists:devices,id'],
            'eventType' => $this->eventType === '' ? ['nullable'] : [Rule::in(['ITEM_REMOVED', 'ITEM_RETURNED', 'ITEM_ADDED'])],
        ]);
        $this->detailId = null;
    }

    public function resetFilters(): void
    {
        $this->reset('date', 'productId', 'deviceId', 'eventType', 'detailId');
    }

    public function showDetail(int $eventId): void
    {
        $this->detailId = $eventId;
    }

    public function closeDetail(): void
    {
        $this->detailId = null;
    }

    public function render(): View
    {
        $query = ShelfEvent::query()->with(['device.product', 'transaction']);

        if ($this->date !== '') {
            $start = Carbon::parse($this->date, 'Asia/Jakarta')->startOfDay()->utc();
            $end = Carbon::parse($this->date, 'Asia/Jakarta')->endOfDay()->utc();
            $query->whereBetween('occurred_at', [$start, $end]);
        }

        if ($this->deviceId !== '') {
            $query->where('device_id', $this->deviceId);
        }

        if ($this->productId !== '') {
            $query->whereHas('device', fn ($device) => $device->where('product_id', $this->productId));
        }

        if ($this->eventType !== '') {
            $query->where('event_type', $this->eventType);
        }

        $events = $query->orderByDesc('occurred_at')->orderByDesc('id')->paginate($this->pageSize());

        return view('livewire.events', [
            'events' => $events,
            'detail' => $this->detailId === null ? null : ShelfEvent::query()->with(['device.product', 'transaction'])->find($this->detailId),
            'devices' => Device::query()->orderBy('code')->get(),
            'products' => Product::query()->orderBy('name')->get(),
        ]);
    }
}
