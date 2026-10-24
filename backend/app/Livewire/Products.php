<?php

namespace App\Livewire;

use App\Enums\PermissionName;
use App\Enums\ShelfStatus;
use App\Livewire\Concerns\AuthorizesShelf;
use App\Models\Device;
use App\Models\Product;
use App\Services\AnomalySync;
use App\Services\AuditLogger;
use App\Services\ShelfMatcher;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app', ['title' => 'Produk dan rak'])]
class Products extends Component
{
    use AuthorizesShelf;
    use WithFileUploads;

    public ?int $editingId = null;

    public string $name = '';

    public string $sku = '';

    public string $category = '';

    public string $salePrice = '';

    public string $unitWeight = '';

    public bool $isActive = true;

    public $image = null;

    public string $message = '';

    public bool $showProductForm = false;

    public ?int $mappingDeviceId = null;

    /** @var array<int|string, array<string, string>> */
    public array $mappings = [];

    public function mount(): void
    {
        $this->loadSlots();
    }

    public function openCreate(): void
    {
        $this->authorizeShelf(PermissionName::ProductsManage);
        $this->clearProductForm();
        $this->showProductForm = true;
    }

    public function edit(int $productId): void
    {
        $this->authorizeShelf(PermissionName::ProductsManage);
        $product = Product::query()->findOrFail($productId);
        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->category = (string) $product->category;
        $this->salePrice = $product->sale_price === null ? '' : (string) $product->sale_price;
        $this->unitWeight = $product->unit_weight_grams === null ? '' : (string) $product->unit_weight_grams;
        $this->isActive = $product->is_active;
        $this->image = null;
        $this->resetValidation();
        $this->showProductForm = true;
    }

    public function cancelEdit(): void
    {
        $this->clearProductForm();
        $this->showProductForm = false;
    }

    public function saveProduct(AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::ProductsManage);
        $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'sku' => ['required', 'regex:/^[A-Z0-9-]{2,32}$/', Rule::unique('products', 'sku')->ignore($this->editingId)],
            'category' => ['nullable', 'string', 'max:80'],
            'salePrice' => $this->salePrice === '' ? ['nullable'] : ['numeric', 'min:0'],
            'unitWeight' => $this->unitWeight === '' ? ['nullable'] : ['numeric', 'between:0,5000'],
            'image' => ['nullable', 'image', 'max:2048'],
        ], [
            'sku.regex' => 'SKU memakai huruf besar, angka, dan tanda hubung.',
            'sku.unique' => 'SKU sudah dipakai.',
        ]);

        $product = $this->editingId === null
            ? new Product
            : Product::query()->findOrFail($this->editingId);

        $product->fill([
            'name' => $this->name,
            'sku' => $this->sku,
            'category' => $this->category !== '' ? $this->category : null,
            'sale_price' => $this->salePrice !== '' ? $this->salePrice : null,
            'unit_weight_grams' => $this->unitWeight !== '' ? $this->unitWeight : null,
            'is_active' => $this->isActive,
        ]);

        if ($this->image !== null) {
            $product->image_path = $this->image->store('products', 'uploads');
        }

        $product->save();
        $audit->record(
            $this->editingId === null ? 'product.created' : 'product.updated',
            'Produk '.$product->sku.' disimpan.',
            $product,
        );
        $this->cancelEdit();
        $this->message = 'Produk disimpan.';
    }

    public function toggleProduct(int $productId, AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::ProductsManage);
        $product = Product::query()->findOrFail($productId);
        $product->is_active = ! $product->is_active;
        $product->save();
        $audit->record('product.activation', $product->sku.' '.($product->is_active ? 'diaktifkan' : 'dinonaktifkan').'.', $product);
        $this->message = $product->is_active ? 'Produk diaktifkan.' : 'Produk dinonaktifkan.';
    }

    public function openMapping(int $deviceId): void
    {
        $this->authorizeShelf(PermissionName::DevicesManage);
        $this->mappingDeviceId = $deviceId;
        $this->resetValidation();
    }

    public function closeMapping(): void
    {
        $this->mappingDeviceId = null;
        $this->resetValidation();
    }

    public function saveMapping(ShelfMatcher $matcher, AnomalySync $anomalies, AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::DevicesManage);
        $deviceId = $this->mappingDeviceId;
        $slot = $deviceId === null
            ? null
            : ($this->mappings[$deviceId] ?? $this->mappings[(string) $deviceId] ?? null);

        if (! is_array($slot)) {
            return;
        }

        $validator = validator($slot, [
            'product_id' => ($slot['product_id'] ?? '') === '' ? ['nullable'] : ['exists:products,id'],
            'slot_label' => ['nullable', 'string', 'max:40'],
            'store_location' => ['nullable', 'string', 'max:80'],
            'baseline_weight_grams' => ($slot['baseline_weight_grams'] ?? '') === '' ? ['nullable'] : ['numeric', 'between:-5000,5000'],
            'threshold_grams' => ($slot['threshold_grams'] ?? '') === '' ? ['nullable'] : ['numeric', 'between:1,5000'],
            'shelf_status' => ['required', Rule::in(array_column(ShelfStatus::cases(), 'value'))],
        ]);

        if ($validator->fails()) {
            $this->addError('mappings', (string) $validator->errors()->first());

            return;
        }

        $device = Device::query()->find($deviceId);
        if ($device === null) {
            return;
        }

        $device->fill([
            'product_id' => ($slot['product_id'] ?? '') === '' ? null : (int) $slot['product_id'],
            'slot_label' => ($slot['slot_label'] ?? '') !== '' ? $slot['slot_label'] : null,
            'store_location' => ($slot['store_location'] ?? '') !== '' ? $slot['store_location'] : null,
            'baseline_weight_grams' => ($slot['baseline_weight_grams'] ?? '') !== '' ? $slot['baseline_weight_grams'] : null,
            'threshold_grams' => ($slot['threshold_grams'] ?? '') !== '' ? $slot['threshold_grams'] : null,
            'shelf_status' => $slot['shelf_status'],
        ])->save();
        $matcher->reconcile($device);
        $anomalies->sync();
        $audit->record('shelf.mapping', 'Pemetaan '.$device->code.' disimpan.', $device);
        $this->loadSlots();
        $this->closeMapping();
        $this->message = 'Pemetaan rak disimpan. Satu slot tetap untuk satu jenis produk.';
    }

    private function clearProductForm(): void
    {
        $this->reset('editingId', 'name', 'sku', 'category', 'salePrice', 'unitWeight', 'image');
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.products', [
            'products' => Product::query()->withCount('devices')->orderBy('name')->get(),
            'devices' => Device::query()->with('product')->orderBy('code')->get(),
            'shelfStatuses' => ShelfStatus::cases(),
            'editing' => $this->editingId === null ? null : Product::query()->find($this->editingId),
        ]);
    }

    private function loadSlots(): void
    {
        $this->mappings = Device::query()->orderBy('code')->get()->mapWithKeys(function (Device $device): array {
            return [$device->id => [
                'product_id' => $device->product_id === null ? '' : (string) $device->product_id,
                'slot_label' => (string) $device->slot_label,
                'store_location' => (string) $device->store_location,
                'baseline_weight_grams' => $device->baseline_weight_grams === null ? '' : (string) $device->baseline_weight_grams,
                'threshold_grams' => $device->threshold_grams === null ? '' : (string) $device->threshold_grams,
                'shelf_status' => $device->shelf_status?->value ?? ShelfStatus::Normal->value,
            ]];
        })->all();
    }
}
