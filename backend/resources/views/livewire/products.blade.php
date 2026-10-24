<div class="page space-y-6">
    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="page-title">Produk & rak</h1>
            <p class="page-desc">Satu slot untuk satu jenis produk agar perubahan berat lebih mudah ditafsirkan.</p>
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

    <section class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
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
                            <td>
                                <div class="flex items-center gap-3">
                                    @if ($product->imageUrl())
                                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="h-10 w-10 rounded object-cover">
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
                                        <button class="icon-btn" type="button" wire:click="toggleProduct({{ $product->id }})" title="{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ $product->name }}">
                                            <x-icon name="{{ $product->is_active ? 'power' : 'check' }}" />
                                        </button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="6">Belum ada produk.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2 class="mb-3 text-base font-semibold">Pemetaan slot</h2>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Perangkat</th>
                        <th>Lokasi</th>
                        <th>Slot</th>
                        <th>Produk</th>
                        <th>Status rak</th>
                        @if (auth()->user()->hasPermission(\App\Enums\PermissionName::DevicesManage))
                            <th></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($devices as $device)
                        <tr>
                            <td>{{ $device->code }}</td>
                            <td>{{ $device->store_location ?: 'Belum diisi' }}</td>
                            <td>{{ $device->slot_label ?: '—' }}</td>
                            <td>{{ $device->product->name ?? 'Belum dipetakan' }}</td>
                            <td>{{ $device->shelf_status?->label() ?? 'Normal' }}</td>
                            @if (auth()->user()->hasPermission(\App\Enums\PermissionName::DevicesManage))
                                <td>
                                    <button class="icon-btn" type="button" wire:click="openMapping({{ $device->id }})" title="Ubah pemetaan" aria-label="Ubah pemetaan {{ $device->code }}">
                                        <x-icon name="pencil" />
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="6">Belum ada perangkat untuk dipetakan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
            <img src="{{ $editing->imageUrl() }}" alt="{{ $editing->name }}" class="mt-3 h-16 w-16 rounded object-cover">
        @endif
        <label class="mt-3 flex items-center gap-2 text-sm text-wd-ink">
            <input type="checkbox" wire:model="isActive"> Produk aktif
        </label>
        <button class="btn btn-primary mt-3" type="button" wire:click="saveProduct">Simpan produk</button>
    </x-modal>

    @php $mappingDevice = $devices->firstWhere('id', $mappingDeviceId); @endphp
    <x-modal :show="$mappingDevice !== null" :title="$mappingDevice ? 'Pemetaan '.$mappingDevice->code : 'Pemetaan'" close="closeMapping">
        @if ($mappingDevice)
            @error('mappings') <div class="alert-error mb-3 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
            <label class="field-label" for="map-location">Lokasi</label>
            <input id="map-location" class="field-input" wire:model="mappings.{{ $mappingDevice->id }}.store_location">
            <label class="field-label mt-3" for="map-slot">Slot</label>
            <input id="map-slot" class="field-input" wire:model="mappings.{{ $mappingDevice->id }}.slot_label">
            <label class="field-label mt-3" for="map-product">Produk</label>
            <select id="map-product" class="field-select" wire:model="mappings.{{ $mappingDevice->id }}.product_id">
                <option value="">Belum dipetakan</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
            <label class="field-label mt-3" for="map-baseline">Berat dasar (gram)</label>
            <input id="map-baseline" class="field-input" wire:model="mappings.{{ $mappingDevice->id }}.baseline_weight_grams" inputmode="decimal">
            <label class="field-label mt-3" for="map-threshold">Ambang (gram)</label>
            <input id="map-threshold" class="field-input" wire:model="mappings.{{ $mappingDevice->id }}.threshold_grams" inputmode="decimal">
            <label class="field-label mt-3" for="map-status">Status rak</label>
            <select id="map-status" class="field-select" wire:model="mappings.{{ $mappingDevice->id }}.shelf_status">
                @foreach ($shelfStatuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary mt-3" type="button" wire:click="saveMapping">Simpan pemetaan</button>
        @endif
    </x-modal>
</div>
