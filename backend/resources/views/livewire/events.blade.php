<div class="page space-y-6">
    <div>
        <h1 class="page-title">Riwayat aktivitas</h1>
        <p class="page-desc">Setiap peristiwa punya ID unik. Pengiriman ulang ID yang sama tidak menambah baris.</p>
    </div>
    <x-shelf-disclaimer />

    <section class="card">
        <div class="grid gap-3 md:grid-cols-4">
            <div>
                <label class="field-label" for="filter-date">Tanggal (WIB)</label>
                <input id="filter-date" class="field-input" type="date" wire:model="date">
            </div>
            <div>
                <label class="field-label" for="filter-product">Produk</label>
                <select id="filter-product" class="field-select" wire:model="productId">
                    <option value="">Semua produk</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="filter-device">Perangkat</label>
                <select id="filter-device" class="field-select" wire:model="deviceId">
                    <option value="">Semua perangkat</option>
                    @foreach ($devices as $device)
                        <option value="{{ $device->id }}">{{ $device->code }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="filter-type">Jenis kejadian</label>
                <select id="filter-type" class="field-select" wire:model="eventType">
                    <option value="">Semua jenis</option>
                    <option value="ITEM_REMOVED">Barang diambil</option>
                    <option value="ITEM_RETURNED">Barang dikembalikan</option>
                </select>
            </div>
        </div>
        @error('date') <div class="alert-error mt-3 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <div class="mt-3 flex gap-2">
            <button class="btn btn-primary" type="button" wire:click="apply">Terapkan</button>
            <button class="btn" type="button" wire:click="resetFilters">Reset</button>
        </div>
    </section>

    @if ($detail)
        <x-modal :show="true" :title="'Detail '.$detail->event_id" close="closeDetail">
            <p class="text-sm">{{ $detail->activityLabel() }} · {{ $detail->occurred_at->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }} WIB</p>
            <p class="mt-2 text-sm">Perangkat {{ $detail->device->code }}, slot {{ $detail->device->slot_label ?: '—' }}, produk {{ $detail->device->product->name ?? 'Belum dipetakan' }}.</p>
            <p class="mt-2 text-sm">Berat sebelum {{ $detail->weight_before_grams === null ? 'belum ada' : $detail->weight_before_grams.' g' }}, berat sesudah {{ $detail->weight_grams }} g.</p>
            <p class="mt-2 text-sm">Pencocokan: {{ $detail->matchLabel() }}. {{ $detail->match_note }}</p>
            <p class="mt-2 text-sm text-wd-muted">Catatan verifikasi: {{ $detail->staff_note ?: 'Belum ada' }}</p>
        </x-modal>
    @endif

    <section class="card">
        <div class="table-wrap">
            <table class="table table-wide">
                <thead>
                    <tr>
                        <th>Waktu (WIB)</th>
                        <th>ID peristiwa</th>
                        <th>Perangkat</th>
                        <th>Produk</th>
                        <th>Aktivitas</th>
                        <th>Sebelum</th>
                        <th>Sesudah</th>
                        <th>Pencocokan</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td>{{ $event->occurred_at->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }}</td>
                            <td>{{ $event->event_id }}</td>
                            <td>{{ $event->device->code }}<div class="text-xs text-wd-muted">{{ $event->device->slot_label ?: 'Tanpa slot' }}</div></td>
                            <td>{{ $event->device->product->name ?? 'Belum dipetakan' }}</td>
                            <td>{{ $event->activityLabel() }}</td>
                            <td>{{ $event->weight_before_grams === null ? '—' : $event->weight_before_grams.' g' }}</td>
                            <td>{{ $event->weight_grams }} g</td>
                            <td>
                                <span class="pill">{{ $event->matchLabel() }}</span>
                                @if ($event->match_note)
                                    <div class="text-xs text-wd-muted">{{ $event->match_note }}</div>
                                @endif
                            </td>
                            <td>
                                <button class="icon-btn" type="button" wire:click="showDetail({{ $event->id }})" title="Detail" aria-label="Detail {{ $event->event_id }}">
                                    <x-icon name="eye" />
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9">Belum ada aktivitas rak.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
