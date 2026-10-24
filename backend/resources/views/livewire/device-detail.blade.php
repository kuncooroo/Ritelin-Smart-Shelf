<div class="page space-y-6">
    <div>
        <h1 class="page-title">{{ $device->code }}</h1>
        <p class="page-desc">{{ $device->name }} · indikasi aktivitas rak, bukan bukti transaksi.</p>
    </div>
    <x-shelf-disclaimer />
    @if ($message !== '')
        <div class="alert alert-info">{{ $message }}</div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat-card"><div class="stat-label">Koneksi</div><div class="stat-value">{{ $device->connectionLabel() }}</div></div>
        <div class="stat-card"><div class="stat-label">Terakhir mengirim</div><div class="stat-value text-base">{{ $device->last_seen_at?->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') ?? 'Belum ada' }}</div></div>
        <div class="stat-card"><div class="stat-label">Firmware</div><div class="stat-value text-base">{{ $device->firmware_version ?: 'Belum ada' }}</div></div>
        <div class="stat-card"><div class="stat-label">Sensor</div><div class="stat-value text-base">{{ $device->sensor_status?->label() ?? 'Belum ditentukan' }}</div></div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="card space-y-3">
            <h2 class="text-base font-semibold">Slot dan kalibrasi</h2>
            <p class="text-sm text-wd-muted">Token unik tersimpan di server dan tidak ditampilkan ulang. Ambang kosong memakai ambang bawaan pengaturan.</p>
            <label class="field-label" for="storeLocation">Lokasi toko</label>
            <input id="storeLocation" class="field-input" wire:model="storeLocation" @disabled(! auth()->user()->hasPermission(\App\Enums\PermissionName::DevicesManage))>
            <label class="field-label" for="slotLabel">Slot rak</label>
            <input id="slotLabel" class="field-input" wire:model="slotLabel" @disabled(! auth()->user()->hasPermission(\App\Enums\PermissionName::DevicesManage))>
            <label class="field-label" for="baseline">Berat dasar (gram)</label>
            <input id="baseline" class="field-input" wire:model="baseline" @disabled(! auth()->user()->hasPermission(\App\Enums\PermissionName::DevicesManage))>
            @error('baseline') <div class="alert-error rounded-lg px-3 py-2">{{ $message }}</div> @enderror
            <label class="field-label" for="threshold">Ambang perubahan berat (gram)</label>
            <input id="threshold" class="field-input" wire:model="threshold" placeholder="Kosong = ambang bawaan" @disabled(! auth()->user()->hasPermission(\App\Enums\PermissionName::DevicesManage))>
            <label class="field-label" for="shelfStatus">Status rak</label>
            <select id="shelfStatus" class="field-select" wire:model="shelfStatus" @disabled(! auth()->user()->hasPermission(\App\Enums\PermissionName::DevicesManage))>
                @foreach ($shelfStatuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
            <label class="field-label" for="sensorStatus">Status sensor</label>
            <select id="sensorStatus" class="field-select" wire:model="sensorStatus" @disabled(! auth()->user()->hasPermission(\App\Enums\PermissionName::DevicesManage))>
                @foreach ($sensorStatuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
            <p class="text-sm text-wd-muted">Kalibrasi terakhir: {{ $device->last_calibrated_at?->timezone('Asia/Jakarta')->format('d-m-Y H:i') ?? 'Belum ada' }}</p>
            @if (auth()->user()->hasPermission(\App\Enums\PermissionName::DevicesManage))
                <div class="row-actions">
                    <button class="icon-btn" type="button" wire:click="save" title="Simpan" aria-label="Simpan">
                        <x-icon name="check" />
                    </button>
                    <button class="icon-btn" type="button" wire:click="calibrate" title="Catat kalibrasi" aria-label="Catat kalibrasi">
                        <x-icon name="refresh" />
                    </button>
                    <button class="icon-btn" type="button" wire:click="toggleActive" title="{{ $device->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $device->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                        <x-icon name="power" />
                    </button>
                </div>
            @endif
        </section>
        <section class="card">
            <h2 class="mb-3 text-base font-semibold">Riwayat aktivitas</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Waktu (WIB)</th><th>ID peristiwa</th><th>Aktivitas</th><th>Berat</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($events as $event)
                            <tr>
                                <td>{{ $event->occurred_at->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }}</td>
                                <td>{{ $event->event_id }}</td>
                                <td>{{ $event->activityLabel() }}</td>
                                <td>{{ $event->weight_grams }} g</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Belum ada aktivitas pada perangkat ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
