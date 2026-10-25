<div class="page space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="page-title">Halo, {{ $user->name }}</h1>
            <p class="page-desc">Ringkasan kondisi rak · {{ now()->timezone('Asia/Jakarta')->format('d-m-Y') }} WIB</p>
        </div>
        @if (count($shortcuts) > 0)
            <div class="flex flex-wrap gap-2">
                @foreach ($shortcuts as $shortcut)
                    <a href="{{ $shortcut['url'] }}" class="btn {{ $shortcut['primary'] ? 'btn-primary' : '' }}">{{ $shortcut['label'] }}</a>
                @endforeach
            </div>
        @endif
    </div>

    <x-shelf-disclaimer />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Perangkat terdaftar', $overview['registered']],
            ['Online', $overview['online']],
            ['Offline', $overview['offline']],
            ['Rak dipantau', $overview['monitored']],
            ['Diambil hari ini', $overview['removed_today']],
            ['Berat naik hari ini', $overview['returned_today']],
            ['Anomali perlu diperiksa', $overview['open_anomalies']],
        ] as [$label, $value])
            <div class="stat-card">
                <div class="stat-label">{{ $label }}</div>
                <div class="stat-value">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <section class="card">
        <h2 class="text-base font-semibold">Aktivitas rak hari ini per jam</h2>
        <p class="page-desc">Jumlah indikasi pengambilan dan berat yang naik. Zona waktu WIB.</p>
        @if ($overview['has_activity'])
            <div class="mt-4 flex gap-4 text-xs text-wd-muted">
                <span>Diambil</span>
                <span>Berat naik</span>
            </div>
            <div class="mt-3 overflow-x-auto">
                <div class="flex h-36 min-w-[48rem] items-end gap-1">
                    @foreach ($overview['hours'] as $hour)
                        <div class="flex h-full min-w-0 flex-1 flex-col justify-end" title="{{ $hour['label'] }}: diambil {{ $hour['removed'] }}, berat naik {{ $hour['returned'] }}">
                            <div class="flex h-24 items-end justify-center gap-0.5">
                                <div class="w-1/2 rounded-sm bg-wd-accent" style="height: {{ $hour['removed_height'] }}%"></div>
                                <div class="w-1/2 rounded-sm bg-wd-accent/40" style="height: {{ $hour['returned_height'] }}%"></div>
                            </div>
                            <div class="pt-1 text-center text-[10px] text-wd-muted">{{ $hour['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <p class="mt-4 text-sm text-wd-muted">Belum ada aktivitas rak pada hari ini.</p>
        @endif
    </section>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="card">
            <h2 class="mb-3 text-base font-semibold">Peristiwa terbaru</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Waktu (WIB)</th>
                            <th>Perangkat</th>
                            <th>Aktivitas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($overview['recent'] as $event)
                            <tr>
                                <td>{{ $overview['recent']->firstItem() + $loop->index }}</td>
                                <td>{{ $event->occurred_at->timezone('Asia/Jakarta')->format('d-m-Y H:i') }}</td>
                                <td>{{ $event->device->code }}</td>
                                <td>{{ $event->activityLabel() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Belum ada peristiwa.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-pager :rows="$overview['recent']" />
        </section>
        <section class="card">
            <h2 class="mb-3 text-base font-semibold">Koneksi perangkat</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>ID</th>
                            <th>Status</th>
                            <th>Terakhir mengirim</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($overview['devices'] as $device)
                            <tr>
                                <td>{{ $overview['devices']->firstItem() + $loop->index }}</td>
                                <td><a class="text-wd-accent" href="{{ route('dashboard.devices.show', $device) }}">{{ $device->code }}</a></td>
                                <td><span class="pill">{{ $device->connectionLabel() }}</span></td>
                                <td>{{ $device->last_seen_at?->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') ?? 'Belum ada' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Belum ada perangkat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-pager :rows="$overview['devices']" />
        </section>
    </div>
</div>
