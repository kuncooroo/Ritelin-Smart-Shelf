<div class="page space-y-6">
    <div>
        <h1 class="page-title">Monitoring sensor</h1>
        <p class="page-desc">Perubahan fisik pada rak tampak sebagai perubahan angka berat di halaman ini.</p>
    </div>
    <x-shelf-disclaimer />

    <section class="card">
        <label class="field-label" for="sensor-device">Perangkat</label>
        <select id="sensor-device" class="field-select max-w-md" wire:model.live="code">
            @foreach ($devices as $option)
                <option value="{{ $option->code }}">{{ $option->code }} — {{ $option->name }}</option>
            @endforeach
        </select>
    </section>

    @if ($device)
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="stat-card">
                <div class="stat-label">Berat saat ini</div>
                <div class="stat-value">{{ $device->last_weight_grams === null ? '—' : $device->last_weight_grams.' g' }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Berat dasar</div>
                <div class="stat-value">{{ $device->baseline_weight_grams === null ? '—' : $device->baseline_weight_grams.' g' }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Selisih</div>
                <div class="stat-value">{{ $device->weightDeltaGrams() === null ? '—' : $device->weightDeltaGrams().' g' }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Kestabilan</div>
                <div class="stat-value text-base">{{ $device->sensor_status?->label() ?? 'Belum ditentukan' }}</div>
            </div>
        </div>

        <section class="card space-y-3">
            <h2 class="text-base font-semibold">Pembacaan berat</h2>
            <p class="text-sm text-wd-muted">
                @if ($device->sensor_status === \App\Enums\SensorStatus::Noisy)
                    Ada indikasi noise atau pembacaan tidak wajar.
                @elseif ($device->sensor_status === \App\Enums\SensorStatus::Unstable)
                    Sensor ditandai tidak stabil.
                @elseif ($device->sensor_status === \App\Enums\SensorStatus::Stable)
                    Tidak ada indikasi noise pada status sensor saat ini.
                @else
                    Status kestabilan belum ditentukan. Perubahan karena peristiwa rak tidak otomatis menjadi noise.
                @endif
            </p>
            <p class="text-sm text-wd-muted">Kalibrasi terakhir: {{ $device->last_calibrated_at?->timezone('Asia/Jakarta')->format('d-m-Y H:i') ?? 'Belum ada' }}. Ambang efektif {{ $device->effectiveThreshold() }} g. Status rak: {{ $device->shelf_status?->label() ?? 'Normal' }}.</p>
            @if ($chart['line'] !== '')
                <svg viewBox="0 0 100 100" class="h-48 w-full text-wd-accent" role="img" aria-label="Grafik pembacaan berat">
                    @if ($chart['baseline'] !== null)
                        <line x1="0" x2="100" y1="{{ $chart['baseline'] }}" y2="{{ $chart['baseline'] }}" stroke="currentColor" stroke-opacity="0.35" stroke-dasharray="2 2" vector-effect="non-scaling-stroke" />
                    @endif
                    <polyline fill="none" stroke="currentColor" stroke-width="1.5" vector-effect="non-scaling-stroke" points="{{ $chart['line'] }}" />
                </svg>
                <p class="text-xs text-wd-muted">Garis putus-putus adalah berat dasar, jika sudah dicatat.</p>
            @else
                <p class="text-sm text-wd-muted">Belum ada riwayat pembacaan untuk perangkat ini.</p>
            @endif
        </section>

        <section class="card">
            <h2 class="mb-3 text-base font-semibold">Riwayat perubahan pembacaan</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Waktu (WIB)</th><th>Berat</th><th>Selisih dari sebelumnya</th><th>Sumber</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($readings as $index => $reading)
                            @php $older = $readings[$index + 1] ?? null; @endphp
                            <tr>
                                <td>{{ $reading->recorded_at->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }}</td>
                                <td>{{ $reading->weight_grams }} g</td>
                                <td>{{ $older === null ? '—' : round((float) $reading->weight_grams - (float) $older->weight_grams, 1).' g' }}</td>
                                <td>{{ $reading->sourceLabel() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Belum ada pembacaan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <section class="card">Belum ada perangkat.</section>
    @endif
</div>
