<div class="page space-y-6">
    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="page-title">Produk</h1>
            <p class="page-desc">Nama, SKU, dan berat tiap produk. Pemetaan ke rak ada di halaman Perangkat.</p>
        </div>
        @if (auth()->user()->hasPermission(\App\Enums\PermissionName::ProductsManage))
            <button class="icon-btn" type="button" wire:click="openCreate" title="Produk baru" aria-label="Produk baru">
                <x-icon name="plus" />
            </button>
        @endif
    </div>
    <x-shelf-disclaimer />
    @if ($message !== '')
        <div class="alert alert-info">{{ $message }}</div>
    @endif
    @include('components.action-confirm')

    <section class="card">
        @error('products') <div class="alert-error mb-3 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <label class="field-label" for="product-search">Cari</label>
        <input id="product-search" class="field-input mb-4 max-w-md" wire:model.live.debounce.300ms="search" placeholder="Nama, SKU, atau kategori">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Produk</th>
                        <th>SKU</th>
                        <th>Harga referensi</th>
                        <th>Berat unit</th>
                        <th>Status</th>
                        @if (auth()->user()->hasPermission(\App\Enums\PermissionName::ProductsManage))
                            <th></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>{{ $products->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="flex items-center gap-3">
                                    @if ($product->imageUrl())
                                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" width="40" height="40" loading="lazy" decoding="async" class="h-10 w-10 rounded object-cover">
                                    @endif
                                    <div>
                                        {{ $product->name }}
                                        <div class="text-xs text-wd-muted">{{ $product->category ?: 'Tanpa kategori' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->priceLabel() }}</td>
                            <td>{{ $product->unit_weight_grams === null ? '—' : $product->unit_weight_grams.' g' }}</td>
                            <td><span class="pill">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            @if (auth()->user()->hasPermission(\App\Enums\PermissionName::ProductsManage))
                                <td>
                                    <div class="row-actions">
                                        <button class="icon-btn" type="button" wire:click="edit({{ $product->id }})" title="Ubah" aria-label="Ubah {{ $product->name }}">
                                            <x-icon name="pencil" />
                                        </button>
                                        <button class="icon-btn" type="button" wire:click="ask('toggleProduct', 'Ubah status produk', 'Setujui perubahan status {{ $product->name }} sebelum status diubah.', { productId: {{ $product->id }} })" title="{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ $product->name }}">
                                            <x-icon name="{{ $product->is_active ? 'power' : 'check' }}" />
                                        </button>
                                        <button class="icon-btn" type="button" wire:click="openDelete({{ $product->id }})" title="Hapus" aria-label="Hapus {{ $product->name }}">
                                            <x-icon name="trash" />
                                        </button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="7">{{ $search === '' ? 'Belum ada produk.' : 'Tidak ada produk yang cocok.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager :rows="$products" />
    </section>

    <x-modal :show="$showProductForm" :title="$editingId ? 'Ubah produk' : 'Produk baru'" close="cancelEdit">
        <label class="field-label" for="product-name">Nama</label>
        <input id="product-name" class="field-input" wire:model="name">
        @error('name') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <label class="field-label mt-3" for="product-sku">SKU</label>
        <input id="product-sku" class="field-input" wire:model="sku" placeholder="SKIN-01">
        @error('sku') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <label class="field-label mt-3" for="product-category">Kategori</label>
        <input id="product-category" class="field-input" wire:model="category">
        <label class="field-label mt-3" for="product-price">Harga jual referensi</label>
        <input id="product-price" class="field-input" wire:model="salePrice" inputmode="decimal">
        @error('salePrice') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <label class="field-label mt-3" for="product-weight">Berat referensi per unit (gram)</label>
        <input id="product-weight" class="field-input" wire:model="unitWeight" inputmode="decimal">
        @error('unitWeight') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <label class="field-label mt-3" for="product-image">Gambar</label>
        <input id="product-image" class="field-input" type="file" wire:model="image" accept="image/*">
        @error('image') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        @if ($editing?->imageUrl())
            <img src="{{ $editing->imageUrl() }}" alt="{{ $editing->name }}" width="64" height="64" decoding="async" class="mt-3 h-16 w-16 rounded object-cover">
        @endif
        <label class="mt-3 flex items-center gap-2 text-sm text-wd-ink">
            <input type="checkbox" wire:model="isActive"> Produk aktif
        </label>
        <button class="btn btn-primary mt-3" type="button" wire:click="ask('saveProduct', 'Simpan produk', 'Setujui penyimpanan produk ini?')">Simpan produk</button>
    </x-modal>

    <x-modal :show="$deletingProduct !== null" :title="$deletingProduct ? 'Hapus '.$deletingProduct->name : 'Hapus produk'" close="closeDelete">
        @if ($deletingProduct)
            <p class="text-sm">Setujui penghapusan sebelum data dihapus. Produk {{ $deletingProduct->name }} akan dihapus. Pemetaan rak ke produk ini ikut dilepas.</p>
            <div wire:loading wire:target="deleteProduct" class="alert alert-info mt-3">Penghapusan sedang dijalankan.</div>
            <div class="mt-4 flex gap-2">
                <button class="btn btn-primary" type="button" wire:click="deleteProduct" wire:loading.attr="disabled" wire:target="deleteProduct">
                    <span wire:loading.remove wire:target="deleteProduct">Hapus</span>
                    <span wire:loading wire:target="deleteProduct">Menghapus...</span>
                </button>
                <button class="btn" type="button" wire:click="closeDelete">Batal</button>
            </div>
        @endif
    </x-modal>
</div>
