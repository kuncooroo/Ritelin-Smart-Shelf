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

    <section class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
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
                                <a class="icon-btn" href="{{ route('dashboard.devices.show', $device) }}" title="Detail" aria-label="Detail {{ $device->code }}">
                                    <x-icon name="eye" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9">Belum ada perangkat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <x-modal :show="$showCreate" title="Perangkat baru" close="closeCreate">
        <p class="page-desc">Contoh ID: SHELF-001, SHELF-002.</p>
        <label class="field-label" for="code">Kode</label>
        <input id="code" class="field-input" wire:model="code" placeholder="SHELF-002">
        @error('code') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <label class="field-label mt-3" for="name">Nama rak</label>
        <input id="name" class="field-input" wire:model="name" placeholder="Rak skincare">
        @error('name') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <button class="btn btn-primary mt-3" type="button" wire:click="createDevice">Simpan perangkat</button>
        @if ($issuedToken !== '')
            <p class="mt-3 text-sm text-wd-muted">Salin token ini sekarang. Token tidak ditampilkan lagi.</p>
            <div class="code-box mt-2">{{ $issuedToken }}</div>
        @endif
    </x-modal>
</div>
