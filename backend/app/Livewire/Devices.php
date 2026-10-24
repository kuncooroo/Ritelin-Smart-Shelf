<?php

namespace App\Livewire;

use App\Enums\PermissionName;
use App\Enums\SensorStatus;
use App\Enums\ShelfStatus;
use App\Livewire\Concerns\AuthorizesShelf;
use App\Models\Device;
use App\Models\Product;
use App\Services\ShelfMatcher;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Perangkat'])]
class Devices extends Component
{
    use AuthorizesShelf;

    public string $code = '';

    public string $name = '';

    public string $issuedToken = '';

    public bool $showCreate = false;

    /** @var array<int|string, string> */
    public array $assignments = [];

    public function mount(): void
    {
        $this->loadAssignments();
    }

    public function openCreate(): void
    {
        $this->authorizeShelf(PermissionName::DevicesManage);
        $this->reset('code', 'name');
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
        ], [
            'code.regex' => 'Kode perangkat memakai huruf besar, angka, dan tanda hubung.',
            'code.unique' => 'Kode perangkat sudah dipakai.',
        ]);

        $plain = Str::random(40);
        $device = Device::query()->create([
            'code' => $this->code,
            'name' => $this->name,
            'token_hash' => hash('sha256', $plain),
            'is_active' => true,
            'shelf_status' => ShelfStatus::Normal,
            'sensor_status' => SensorStatus::Unknown,
        ]);

        $this->issuedToken = $plain;
        $this->assignments[$device->id] = '';
        $this->reset('code', 'name');
        $this->showCreate = true;
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

    public function render(): View
    {
        return view('livewire.devices', [
            'devices' => Device::query()->with('product')->orderBy('code')->get(),
            'products' => Product::query()->orderBy('name')->get(),
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
