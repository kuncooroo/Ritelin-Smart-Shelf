<div class="page space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="page-title">Laporan & Analisis</h1>
            <p class="page-desc">Rangkuman, diagram aktivitas, dan pencocokan rak. Nilai uang tidak dihitung dari perubahan berat.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="btn" href="{{ route('dashboard.reports.csv', ['from' => $report['from'], 'to' => $report['to']]) }}">Unduh CSV</a>
            <a class="btn" href="{{ route('dashboard.reports.print', ['from' => $report['from'], 'to' => $report['to']]) }}" target="_blank">Tampilan cetak</a>
        </div>
    </div>
    <x-shelf-disclaimer />

    <section class="card">
        <div class="grid gap-3 md:grid-cols-[1fr_1fr_auto] md:items-end">
            <div>
                <label class="field-label" for="report-from">Dari</label>
                <input id="report-from" class="field-input" type="date" wire:model="from">
            </div>
            <div>
                <label class="field-label" for="report-to">Sampai</label>
                <input id="report-to" class="field-input" type="date" wire:model="to">
            </div>
            <button class="btn btn-primary" type="button" wire:click="apply">Tampilkan</button>
        </div>
        @error('from') <div class="alert-error mt-3 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        @error('to') <div class="alert-error mt-3 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
    </section>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            'Peristiwa' => $report['totals']['events'],
            'Diambil' => $report['totals']['removed'],
            'Berat naik' => $report['totals']['returned'],
            'Transaksi' => $report['totals']['transactions'],
            'Cocok' => $report['totals']['matched'],
            'Belum cocok' => $report['totals']['unmatched'],
            'Perlu ditinjau' => $report['totals']['needs_review'],
            'Anomali' => $report['totals']['anomalies'],
        ] as $label => $value)
            <div class="stat-card">
                <div class="stat-label">{{ $label }}</div>
                <div class="stat-value">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <x-report-charts :report="$report" />

    <section class="card">
        <h2 class="mb-3 text-base font-semibold">Aktivitas per hari</h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>No</th><th>Tanggal</th><th>Diambil</th><th>Berat naik</th><th>Transaksi</th><th>Anomali</th></tr></thead>
                <tbody>
                    @foreach ($days as $day)
                        <tr>
                            <td>{{ $days->firstItem() + $loop->index }}</td>
                            <td>{{ $day['date'] }}</td>
                            <td>{{ $day['removed'] }}</td>
                            <td>{{ $day['returned'] }}</td>
                            <td>{{ $day['transactions'] }}</td>
                            <td>{{ $day['anomalies'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-pager :rows="$days" />
    </section>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="card">
            <h2 class="mb-3 text-base font-semibold">Produk paling sering diambil</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>No</th><th>Produk</th><th>SKU</th><th>Diambil</th></tr></thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td>{{ $products->firstItem() + $loop->index }}</td>
                                <td>{{ $product['name'] }}</td>
                                <td>{{ $product['sku'] }}</td>
                                <td>{{ $product['removed'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Belum ada pengambilan pada rentang ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-pager :rows="$products" />
        </section>
        <section class="card">
            <h2 class="mb-3 text-base font-semibold">Anomali per rak</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>No</th><th>Perangkat</th><th>Produk</th><th>Jumlah</th></tr></thead>
                    <tbody>
                        @forelse ($shelves as $shelf)
                            <tr>
                                <td>{{ $shelves->firstItem() + $loop->index }}</td>
                                <td>{{ $shelf['code'] }}</td>
                                <td>{{ $shelf['product'] }}</td>
                                <td>{{ $shelf['count'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Belum ada anomali pada rentang ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-pager :rows="$shelves" />
        </section>
    </div>

    <section class="card">
        <h2 class="mb-3 text-base font-semibold">Performa perangkat</h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>No</th><th>ID</th><th>Nama</th><th>Koneksi</th><th>Terakhir mengirim</th><th>Peristiwa</th><th>Anomali</th></tr></thead>
                <tbody>
                    @forelse ($deviceRows as $device)
                        <tr>
                            <td>{{ $deviceRows->firstItem() + $loop->index }}</td>
                            <td>{{ $device['code'] }}</td>
                            <td>{{ $device['name'] }}</td>
                            <td>{{ $device['connection'] }}</td>
                            <td>{{ $device['last_seen'] }}</td>
                            <td>{{ $device['events'] }}</td>
                            <td>{{ $device['anomalies'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Belum ada perangkat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager :rows="$deviceRows" />
    </section>
</div>
