<?php

namespace App\Livewire;

use App\Enums\PermissionName;
use App\Livewire\Concerns\AuthorizesShelf;
use App\Livewire\Concerns\ConfirmsActions;
use App\Livewire\Concerns\WithPageSize;
use App\Models\Product;
use App\Services\AuditLogger;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Produk'])]
class Products extends Component
{
    use AuthorizesShelf;
    use ConfirmsActions;
    use WithFileUploads;
    use WithPageSize;
    use WithPagination;

    public string $search = '';

    public ?int $deletingId = null;

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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openDelete(int $productId): void
    {
        $this->authorizeShelf(PermissionName::ProductsManage);
        $this->deletingId = $productId;
    }

    public function closeDelete(): void
    {
        $this->deletingId = null;
        $this->resetErrorBag();
    }

    public function deleteProduct(AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::ProductsManage);
        $product = Product::query()->withCount('transactions')->find($this->deletingId);
        if ($product === null) {
            $this->closeDelete();

            return;
        }

        if ($product->transactions_count > 0) {
            $this->addError('products', 'Produk yang sudah punya transaksi kasir tidak dapat dihapus.');

            return;
        }

        $sku = $product->sku;
        $product->delete();
        $audit->record('product.deleted', 'Produk '.$sku.' dihapus.');
        $this->closeDelete();
        $this->message = 'Produk '.$sku.' dihapus.';
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

    private function clearProductForm(): void
    {
        $this->reset('editingId', 'name', 'sku', 'category', 'salePrice', 'unitWeight', 'image');
        $this->isActive = true;
        $this->resetValidation();
    }

    /**
     * @return list<string>
     */
    protected function confirmableActions(): array
    {
        return ['saveProduct', 'deleteProduct', 'toggleProduct'];
    }

    public function render(): View
    {
        $term = trim($this->search);
        $products = Product::query()->withCount('devices')->orderBy('name');
        if ($term !== '') {
            $products->where(function ($query) use ($term): void {
                $query->where('name', 'like', '%'.$term.'%')
                    ->orWhere('sku', 'like', '%'.$term.'%')
                    ->orWhere('category', 'like', '%'.$term.'%');
            });
        }

        return view('livewire.products', [
            'products' => $products->paginate($this->pageSize()),
            'editing' => $this->editingId === null ? null : Product::query()->find($this->editingId),
            'deletingProduct' => $this->deletingId === null ? null : Product::query()->find($this->deletingId),
        ]);
    }
}
