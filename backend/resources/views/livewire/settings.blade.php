<div class="page space-y-6">
    <div>
        <h1 class="page-title">Pengaturan</h1>
        <p class="page-desc">Batas koneksi, sensitivitas timbangan, jendela rekonsiliasi, dan jejak perubahan konfigurasi.</p>
    </div>
    @if ($message !== '')
        <div class="alert alert-info">{{ $message }}</div>
    @endif
    @include('components.action-confirm')

    <section class="card space-y-4">
        <div>
            <h2 class="text-base font-semibold">Pengaturan alat</h2>
            <p class="mt-1 text-sm text-wd-muted">Berlaku untuk semua rak. Goyangan yang lebih kecil dari sensitivitas tidak dicatat sebagai barang diambil atau ditaruh. Transaksi kasir dicocokkan dari menit sebelum barang diambil sampai menit sesudahnya. Kalau jumlah di rak turun, sistem menunggu kasir selama menit tunggu stok sebelum status menjadi selisih.</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="field-label" for="offline-seconds">Rak dianggap terputus setelah (detik)</label>
                <input id="offline-seconds" class="field-input" type="number" min="30" max="3600" wire:model="offlineSeconds">
                @error('offlineSeconds') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="field-label" for="threshold-grams">Sensitivitas timbangan (gram)</label>
                <input id="threshold-grams" class="field-input" wire:model="thresholdGrams" inputmode="decimal">
                @error('thresholdGrams') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="field-label" for="match-lead">Menit sebelum barang diambil</label>
                <input id="match-lead" class="field-input" type="number" min="0" max="60" wire:model="matchLeadMinutes">
                @error('matchLeadMinutes') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="field-label" for="match-window">Menit sesudah barang diambil</label>
                <input id="match-window" class="field-input" type="number" min="1" max="180" wire:model="matchWindowMinutes">
                @error('matchWindowMinutes') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="field-label" for="stock-window">Menit tunggu sebelum selisih stok</label>
                <input id="stock-window" class="field-input" type="number" min="1" max="180" wire:model="stockWindowMinutes">
                @error('stockWindowMinutes') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
            </div>
        </div>
        <button class="btn btn-primary" type="button" wire:click="ask('save', 'Simpan pengaturan', 'Setujui perubahan pengaturan rak sebelum disimpan?')">Simpan pengaturan</button>
    </section>

    <section class="card space-y-3">
        <h2 class="text-base font-semibold">Integrasi kasir</h2>
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-1 text-sm">
                <p>Webhook: <span class="font-mono text-xs">POST /api/pos/transactions</span></p>
                <p>Token webhook: {{ $posReady ? 'tersedia di lingkungan server' : 'belum diatur' }}</p>
                <p class="text-wd-muted">Token tidak ditampilkan di dashboard. Untuk demo, halaman transaksi kasir mencatat ke backend yang sama.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a class="btn" href="{{ route('dashboard.profile') }}">Profil</a>
                @if (auth()->user()->hasPermission(\App\Enums\PermissionName::UsersView))
                    <a class="btn" href="{{ route('dashboard.users') }}">Pengguna</a>
                @endif
            </div>
        </div>
    </section>

    <section class="card">
        <h2 class="mb-3 text-base font-semibold">Jejak audit</h2>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>No</th><th>Waktu (WIB)</th><th>Akun</th><th>Perubahan</th></tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $logs->firstItem() + $loop->index }}</td>
                            <td>{{ $log->created_at->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }}</td>
                            <td>{{ $log->user->name ?? 'Sistem' }}</td>
                            <td>{{ $log->summary }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">Belum ada perubahan konfigurasi yang dicatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager :rows="$logs" />
    </section>
</div>
