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

    <section class="card">
        <h2 class="mb-3 text-base font-semibold">Spesifikasi</h2>
        <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-wd-muted">Slot</dt><dd>{{ $device->slot_label ?: 'Belum diisi' }}</dd></div>
            <div><dt class="text-wd-muted">Produk</dt><dd>{{ $device->product->name ?? 'Belum dipetakan' }}</dd></div>
            <div><dt class="text-wd-muted">Lokasi</dt><dd>{{ $device->store_location ?: 'Belum diisi' }}</dd></div>
            <div><dt class="text-wd-muted">Berat terakhir</dt><dd>{{ $device->last_weight_grams === null ? 'Belum ada' : $device->last_weight_grams.' g' }}</dd></div>
            <div><dt class="text-wd-muted">Stok di rak</dt><dd>{{ $device->physical_stock === null ? 'Belum dihitung' : $device->physical_stock.' unit' }}</dd></div>
            <div><dt class="text-wd-muted">Stok di kasir</dt><dd>{{ $device->pos_stock === null ? 'Belum dihitung' : $device->pos_stock.' unit' }}</dd></div>
            <div><dt class="text-wd-muted">Status stok</dt><dd>{{ $device->stockStatusLabel() }}</dd></div>
            <div><dt class="text-wd-muted">Timbangan</dt><dd>{{ $device->tare_requested ? 'Tare sedang berjalan' : ($device->last_calibrated_at ? 'Sudah dinolkan' : 'Belum dinolkan') }}</dd></div>
        </dl>
    </section>

    <section class="card">
        <h2 class="mb-3 text-base font-semibold">Riwayat aktivitas</h2>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>No</th><th>Waktu (WIB)</th><th>ID peristiwa</th><th>Aktivitas</th><th>Berat</th></tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td>{{ $events->firstItem() + $loop->index }}</td>
                            <td>{{ $event->occurred_at->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }}</td>
                            <td>{{ $event->event_id }}</td>
                            <td>{{ $event->activityLabel() }}</td>
                            <td>{{ $event->weight_grams }} g</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Belum ada aktivitas pada perangkat ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager :rows="$events" />
    </section>
</div>
