<div class="page space-y-6">
    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="page-title">Perangkat rak</h1>
            <p class="page-desc">Perubahan berat adalah indikasi aktivitas rak, bukan bukti transaksi.</p>
        </div>
        @if (auth()->user()->hasPermission(\App\Enums\PermissionName::DevicesManage))
            <button class="icon-btn" type="button" wire:click="openCreate" title="Perangkat baru" aria-label="Perangkat baru">
                <x-icon name="plus" />
            </button>
        @endif
    </div>
    <div class="alert alert-info">Satu platform load cell dipetakan ke satu jenis produk. Token tiap perangkat berbeda dan hanya ditampilkan saat perangkat dibuat.</div>
    @if ($message !== '')
        <div class="alert alert-info">{{ $message }}</div>
    @endif
    @include('components.action-confirm')

    <section class="card" @if ($devices->contains('tare_requested', true)) wire:poll.3s @endif>
        <label class="field-label" for="device-search">Cari</label>
        <input id="device-search" class="field-input mb-4 max-w-md" wire:model.live.debounce.300ms="search" placeholder="Kode, nama, lokasi, slot, atau produk">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID</th>
                        <th>Lokasi</th>
                        <th>Produk</th>
                        <th>Firmware</th>
                        <th>Sensor</th>
                        <th>Koneksi</th>
                        <th>Terakhir aktif</th>
                        <th>Berat</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($devices as $device)
                        <tr>
                            <td>{{ $devices->firstItem() + $loop->index }}</td>
                            <td>
                                {{ $device->code }}
                                <div class="text-xs text-wd-muted">{{ $device->name }}</div>
                            </td>
                            <td>
                                {{ $device->store_location ?: 'Belum diisi' }}
                                <div class="text-xs text-wd-muted">Slot {{ $device->slot_label ?: '—' }}</div>
                            </td>
                            <td>{{ $device->product->name ?? 'Belum dipetakan' }}</td>
                            <td>{{ $device->firmware_version ?: 'Belum ada' }}</td>
                            <td>{{ $device->sensor_status?->label() ?? 'Belum ditentukan' }}</td>
                            <td><span class="pill">{{ $device->connectionLabel() }}</span></td>
                            <td>{{ $device->last_seen_at?->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') ?? 'Belum ada' }}</td>
                            <td>{{ $device->last_weight_grams === null ? '—' : $device->last_weight_grams.' g' }}</td>
                            <td>
                                <div class="row-actions">
                                    @if (auth()->user()->hasPermission(\App\Enums\PermissionName::DevicesManage))
                                        <button class="icon-btn" type="button" wire:click="openMapping({{ $device->id }})" title="Ubah" aria-label="Ubah {{ $device->code }}">
                                            <x-icon name="pencil" />
                                        </button>
                                        <button class="icon-btn" type="button" wire:click="ask('toggleActive', 'Ubah status perangkat', 'Setujui perubahan status {{ $device->code }} sebelum status diubah.', { deviceId: {{ $device->id }} })" title="{{ $device->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $device->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ $device->code }}">
                                            <x-icon name="{{ $device->is_active ? 'power' : 'check' }}" />
                                        </button>
                                    @endif
                                    <a class="icon-btn" href="{{ route('dashboard.devices.show', $device) }}" title="Lihat" aria-label="Lihat {{ $device->code }}">
                                        <x-icon name="eye" />
                                    </a>
                                    @if (auth()->user()->hasPermission(\App\Enums\PermissionName::DevicesManage))
                                        <button class="icon-btn" type="button" wire:click="openDelete({{ $device->id }})" title="Hapus" aria-label="Hapus {{ $device->code }}">
                                            <x-icon name="trash" />
                                        </button>
                                    @endif
                                    @if (auth()->user()->hasPermission(\App\Enums\PermissionName::DevicesManage))
                                        <button class="btn" type="button" wire:click="ask('requestTare', 'Tare rak', 'Setujui perintah tare untuk {{ $device->code }}. Rak harus kosong saat timbangan menolkan.', { deviceId: {{ $device->id }} })" @disabled($device->tare_requested) title="{{ $device->tare_requested ? 'Tare sedang berjalan' : 'Tare' }}" aria-label="Tare {{ $device->code }}">
                                            Tare
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10">{{ $search === '' ? 'Belum ada perangkat.' : 'Tidak ada perangkat yang cocok.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager :rows="$devices" />
    </section>

    <x-modal :show="$showCreate" title="Perangkat baru" close="closeCreate">
        <p class="page-desc">Contoh ID: SHELF-001, SHELF-002.</p>
        <label class="field-label" for="code">Kode</label>
        <input id="code" class="field-input" wire:model="code" placeholder="SHELF-002">
        @error('code') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <label class="field-label mt-3" for="name">Nama rak</label>
        <input id="name" class="field-input" wire:model="name" placeholder="Rak skincare">
        @error('name') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <label class="field-label mt-3" for="create-location">Lokasi toko</label>
        <input id="create-location" class="field-input" wire:model="storeLocation" placeholder="Contoh: Toko cabang utama">
        @error('storeLocation') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <label class="field-label mt-3" for="create-slot">Slot rak</label>
        <input id="create-slot" class="field-input" wire:model="slotLabel" placeholder="Contoh: A1">
        <p class="mt-1 text-xs text-wd-muted">Kode posisi di rak, misalnya A1 atau B2. Satu rak untuk satu jenis produk.</p>
        @error('slotLabel') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <label class="field-label mt-3" for="create-product">Produk</label>
        <select id="create-product" class="field-select" wire:model="productId">
            <option value="">Belum dipilih</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}">{{ $product->name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-wd-muted">Produk yang biasa diletakkan di rak ini.</p>
        @error('productId') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <button class="btn btn-primary mt-3" type="button" wire:click="ask('createDevice', 'Simpan perangkat', 'Setujui penyimpanan perangkat baru? Token hanya ditampilkan sekali.')">Simpan perangkat</button>
        @if ($issuedToken !== '')
            <p class="mt-3 text-sm text-wd-muted">Salin token ini sekarang. Token tidak ditampilkan lagi.</p>
            <div class="code-box mt-2">{{ $issuedToken }}</div>
        @endif
    </x-modal>

    <x-modal :show="$mappingDevice !== null" :title="$mappingDevice ? 'Ubah '.$mappingDevice->code : 'Ubah perangkat'" close="closeMapping">
        @if ($mappingDevice)
            <p class="text-sm">Kode <span class="font-mono">{{ $mappingDevice->code }}</span></p>
            <p class="mt-1 text-xs text-wd-muted">Kode mengikuti firmware dan tidak diubah dari dashboard.</p>
            <label class="field-label mt-3" for="map-name">Nama rak</label>
            <input id="map-name" class="field-input" wire:model="deviceName">
            @error('deviceName') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
            <label class="field-label mt-3" for="map-location">Lokasi toko</label>
            <input id="map-location" class="field-input" wire:model="storeLocation" placeholder="Contoh: Toko cabang utama">
            @error('storeLocation') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
            <label class="field-label mt-3" for="map-slot">Slot rak</label>
            <input id="map-slot" class="field-input" wire:model="slotLabel" placeholder="Contoh: A1">
            <p class="mt-1 text-xs text-wd-muted">Kode posisi di rak, misalnya A1 atau B2. Satu rak untuk satu jenis produk.</p>
            @error('slotLabel') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
            <label class="field-label mt-3" for="map-product">Produk</label>
            <select id="map-product" class="field-select" wire:model="productId">
                <option value="">Belum dipilih</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-wd-muted">Produk yang biasa diletakkan di rak ini.</p>
            @error('productId') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
            <button class="btn btn-primary mt-4" type="button" wire:click="ask('saveMapping', 'Simpan perangkat', 'Setujui perubahan data rak ini?')">Simpan</button>
        @endif
    </x-modal>

    <x-modal :show="$deletingDevice !== null" :title="$deletingDevice ? 'Hapus '.$deletingDevice->code : 'Hapus perangkat'" close="closeDelete">
        @if ($deletingDevice)
            <p class="text-sm">Setujui penghapusan sebelum data dihapus. Perangkat {{ $deletingDevice->code }} dan riwayat aktivitasnya akan dihapus.</p>
            <div wire:loading wire:target="deleteDevice" class="alert alert-info mt-3">Penghapusan sedang dijalankan.</div>
            <div class="mt-4 flex gap-2">
                <button class="btn btn-primary" type="button" wire:click="deleteDevice" wire:loading.attr="disabled" wire:target="deleteDevice">
                    <span wire:loading.remove wire:target="deleteDevice">Hapus</span>
                    <span wire:loading wire:target="deleteDevice">Menghapus...</span>
                </button>
                <button class="btn" type="button" wire:click="closeDelete">Batal</button>
            </div>
        @endif
    </x-modal>
</div>
