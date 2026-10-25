<?php

namespace App\Livewire;

use App\Enums\PermissionName;
use App\Enums\ShelfStatus;
use App\Livewire\Concerns\AuthorizesShelf;
use App\Livewire\Concerns\RefreshesWithDashboard;
use App\Livewire\Concerns\WithPageSize;
use App\Models\Device;
use App\Models\Product;
use App\Services\AnomalySync;
use App\Services\AuditLogger;
use App\Services\ShelfMatcher;
use App\Services\StockLedger;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Detail perangkat'])]
class DeviceDetail extends Component
{
    use AuthorizesShelf;
    use RefreshesWithDashboard;
    use WithPageSize;
    use WithPagination;

    public Device $device;

    public string $slotLabel = '';

    public string $productId = '';

    public string $message = '';

    public function mount(Device $device): void
    {
        $this->device = $device->load('product');
        $this->fillForm();
    }

    public function save(AuditLogger $audit, StockLedger $stock, ShelfMatcher $matcher, AnomalySync $anomalies): void
    {
        $this->authorizeShelf(PermissionName::DevicesManage);
        $this->validate($this->rules());

        $this->device->fill([
            'slot_label' => $this->slotLabel !== '' ? $this->slotLabel : null,
            'product_id' => $this->productId !== '' ? (int) $this->productId : null,
        ])->save();

        $matcher->reconcile($this->device);
        $anomalies->sync();
        $stock->apply($this->device->fresh());
        $audit->record('device.updated', 'Pemetaan '.$this->device->code.' disimpan.', $this->device);
        $this->device->refresh();
        $this->message = 'Pemetaan rak disimpan.';
    }

    public function toggleActive(AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::DevicesManage);
        $this->device->is_active = ! $this->device->is_active;
        $this->device->save();
        $audit->record(
            'device.activation',
            $this->device->code.' '.($this->device->is_active ? 'diaktifkan' : 'dinonaktifkan').'.',
            $this->device,
        );
        $this->message = $this->device->is_active ? 'Perangkat diaktifkan.' : 'Perangkat dinonaktifkan.';
    }

    public function calibrate(AuditLogger $audit, StockLedger $stock): void
    {
        $this->authorizeShelf(PermissionName::DevicesManage);
        if ($this->device->last_weight_grams === null) {
            $this->addError('tare', 'Belum ada pembacaan untuk dijadikan titik nol.');

            return;
        }

        $this->device->forceFill([
            'baseline_weight_grams' => $this->device->last_weight_grams,
            'last_calibrated_at' => now(),
            'shelf_status' => ShelfStatus::Normal,
        ])->save();
        $stock->apply($this->device->fresh());
        $audit->record('device.calibrated', 'Berat dasar '.$this->device->code.' dicatat dari pembacaan terakhir.', $this->device);
        $this->device->refresh();
        $this->fillForm();
        $this->message = 'Kalibrasi dicatat. Berat dasar mengikuti pembacaan terakhir.';
    }

    public function requestTare(AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::DevicesManage);
        $this->device->tare_requested = true;
        $this->device->save();
        $audit->record('device.tare_requested', 'Perintah tare untuk '.$this->device->code.' menunggu perangkat.', $this->device);
        $this->device->refresh();
        $this->message = 'Tare dikirim. Kosongkan rak, lalu tunggu sampai timbangan selesai menolkan.';
    }

    public function refreshStatus(): void
    {
        $this->device->refresh();
    }

    protected function pageNames(): array
    {
        return ['activity'];
    }

    public function render(): View
    {
        return view('livewire.device-detail', [
            'events' => $this->device->events()->orderByDesc('occurred_at')->orderByDesc('id')->paginate($this->pageSize(), ['*'], 'activity'),
            'products' => Product::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'slotLabel' => ['nullable', 'string', 'max:40'],
            'productId' => $this->productId === '' ? ['nullable'] : ['exists:products,id'],
        ];
    }

    private function fillForm(): void
    {
        $this->slotLabel = (string) $this->device->slot_label;
        $this->productId = $this->device->product_id === null ? '' : (string) $this->device->product_id;
    }
}
