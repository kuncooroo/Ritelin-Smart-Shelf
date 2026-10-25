<?php

namespace App\Livewire;

use App\Enums\PermissionName;
use App\Enums\SensorStatus;
use App\Enums\ShelfStatus;
use App\Livewire\Concerns\AuthorizesShelf;
use App\Livewire\Concerns\ConfirmsActions;
use App\Livewire\Concerns\RefreshesWithDashboard;
use App\Livewire\Concerns\WithPageSize;
use App\Models\Device;
use App\Models\Product;
use App\Services\AnomalySync;
use App\Services\AuditLogger;
use App\Services\ShelfMatcher;
use App\Services\StockLedger;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Perangkat'])]
class Devices extends Component
{
    use AuthorizesShelf;
    use ConfirmsActions;
    use RefreshesWithDashboard;
    use WithPageSize;
    use WithPagination;

    public string $search = '';

    public string $code = '';

    public string $name = '';

    public string $issuedToken = '';

    public bool $showCreate = false;

    public ?int $mappingDeviceId = null;

    public string $deviceName = '';

    public string $storeLocation = '';

    public string $slotLabel = '';

    public string $productId = '';

    public string $message = '';

    public ?int $deletingId = null;

    /** @var array<int|string, string> */
    public array $assignments = [];

    public function mount(): void
    {
        $this->loadAssignments();
    }

    public function openCreate(): void
    {
        $this->authorizeShelf(PermissionName::DevicesManage);
        $this->reset('code', 'name', 'storeLocation', 'slotLabel', 'productId', 'issuedToken');
        $this->resetValidation();
        $this->showCreate = true;
    }

    public function closeCreate(): void
    {
        $this->showCreate = false;
    }

    public function createDevice(): void
    {
        $this->authorizeShelf(PermissionName::DevicesManage);
        $this->validate([
            'code' => ['required', 'regex:/^[A-Z0-9-]{3,32}$/', 'unique:devices,code'],
            'name' => ['required', 'string', 'max:80'],
            'storeLocation' => ['nullable', 'string', 'max:120'],
            'slotLabel' => ['nullable', 'string', 'max:40'],
            'productId' => $this->productId === '' ? ['nullable'] : ['exists:products,id'],
        ], [
            'code.regex' => 'Kode perangkat memakai huruf besar, angka, dan tanda hubung.',
            'code.unique' => 'Kode perangkat sudah dipakai.',
        ]);

        $plain = Str::random(40);
        $device = Device::query()->create([
            'code' => $this->code,
            'name' => $this->name,
            'store_location' => $this->storeLocation !== '' ? $this->storeLocation : null,
            'slot_label' => $this->slotLabel !== '' ? $this->slotLabel : null,
            'product_id' => $this->productId !== '' ? (int) $this->productId : null,
            'token_hash' => hash('sha256', $plain),
            'is_active' => true,
            'shelf_status' => ShelfStatus::Normal,
            'sensor_status' => SensorStatus::Unknown,
        ]);

        $this->issuedToken = $plain;
        $this->assignments[$device->id] = '';
        $this->reset('code', 'name');
        $this->showCreate = true;
        $this->message = 'Perangkat '.$device->code.' disimpan. Salin token sekarang.';
    }

    public function openMapping(int $deviceId): void
    {
        $this->authorizeShelf(PermissionName::DevicesManage);
        $device = Device::query()->findOrFail($deviceId);
        $this->mappingDeviceId = $device->id;
        $this->deviceName = $device->name;
        $this->storeLocation = (string) $device->store_location;
        $this->slotLabel = (string) $device->slot_label;
        $this->productId = $device->product_id === null ? '' : (string) $device->product_id;
        $this->resetValidation();
    }

    public function closeMapping(): void
    {
        $this->mappingDeviceId = null;
        $this->resetValidation();
    }

    public function saveMapping(AuditLogger $audit, StockLedger $stock, ShelfMatcher $matcher, AnomalySync $anomalies): void
    {
        $this->authorizeShelf(PermissionName::DevicesManage);
        $this->validate([
            'deviceName' => ['required', 'string', 'max:80'],
            'storeLocation' => ['nullable', 'string', 'max:120'],
            'slotLabel' => ['nullable', 'string', 'max:40'],
            'productId' => $this->productId === '' ? ['nullable'] : ['exists:products,id'],
        ]);

        $device = Device::query()->find($this->mappingDeviceId);
        if ($device === null) {
            return;
        }

        $device->fill([
            'name' => $this->deviceName,
            'store_location' => $this->storeLocation !== '' ? $this->storeLocation : null,
            'slot_label' => $this->slotLabel !== '' ? $this->slotLabel : null,
            'product_id' => $this->productId !== '' ? (int) $this->productId : null,
        ])->save();
        $matcher->reconcile($device);
        $anomalies->sync();
        $stock->apply($device->fresh());
        $audit->record('device.updated', 'Data '.$device->code.' disimpan. Kode perangkat tidak diubah.', $device);
        $this->closeMapping();
        $this->message = 'Data rak disimpan.';
    }

    public function toggleActive(int $deviceId, AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::DevicesManage);
        $device = Device::query()->findOrFail($deviceId);
        $device->is_active = ! $device->is_active;
        $device->save();
        $audit->record(
            'device.activation',
            $device->code.' '.($device->is_active ? 'diaktifkan' : 'dinonaktifkan').'.',
            $device,
        );
        $this->message = $device->is_active ? 'Perangkat diaktifkan.' : 'Perangkat dinonaktifkan.';
    }

    public function requestTare(int $deviceId, AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::DevicesManage);
        $device = Device::query()->findOrFail($deviceId);
        $device->tare_requested = true;
        $device->save();
        $audit->record('device.tare_requested', 'Perintah tare untuk '.$device->code.' menunggu perangkat.', $device);
        $this->message = 'Tare dikirim. Kosongkan rak, lalu tunggu sampai timbangan selesai menolkan.';
    }

    public function updatedAssignments(string $value, string $deviceId): void
    {
        $this->authorizeShelf(PermissionName::DevicesManage);
        $device = Device::query()->find($deviceId);
        if ($device === null) {
            return;
        }

        if ($value !== '' && ! Product::query()->whereKey($value)->exists()) {
            return;
        }

        $device->product_id = $value === '' ? null : (int) $value;
        $device->save();
        app(ShelfMatcher::class)->reconcile($device);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openDelete(int $deviceId): void
    {
        $this->authorizeShelf(PermissionName::DevicesManage);
        $this->deletingId = $deviceId;
    }

    public function closeDelete(): void
    {
        $this->deletingId = null;
    }

    public function deleteDevice(AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::DevicesManage);
        $device = Device::query()->find($this->deletingId);
        if ($device === null) {
            $this->closeDelete();

            return;
        }

        $code = $device->code;
        $device->delete();
        $audit->record('device.deleted', 'Perangkat '.$code.' dan riwayatnya dihapus.');
        $this->closeDelete();
        $this->message = 'Perangkat '.$code.' dihapus.';
    }

    /**
     * @return list<string>
     */
    protected function confirmableActions(): array
    {
        return ['createDevice', 'saveMapping', 'deleteDevice', 'toggleActive', 'requestTare'];
    }

    public function render(): View
    {
        $term = trim($this->search);
        $devices = Device::query()->with('product')->orderBy('code');
        if ($term !== '') {
            $devices->where(function ($query) use ($term): void {
                $query->where('code', 'like', '%'.$term.'%')
                    ->orWhere('name', 'like', '%'.$term.'%')
                    ->orWhere('store_location', 'like', '%'.$term.'%')
                    ->orWhere('slot_label', 'like', '%'.$term.'%')
                    ->orWhereHas('product', fn ($product) => $product->where('name', 'like', '%'.$term.'%'));
            });
        }

        return view('livewire.devices', [
            'devices' => $devices->paginate($this->pageSize()),
            'products' => Product::query()->orderBy('name')->get(),
            'mappingDevice' => $this->mappingDeviceId === null ? null : Device::query()->find($this->mappingDeviceId),
            'deletingDevice' => $this->deletingId === null ? null : Device::query()->find($this->deletingId),
        ]);
    }

    private function loadAssignments(): void
    {
        $this->assignments = Device::query()
            ->pluck('product_id', 'id')
            ->map(fn (mixed $productId): string => $productId === null ? '' : (string) $productId)
            ->all();
    }
}
