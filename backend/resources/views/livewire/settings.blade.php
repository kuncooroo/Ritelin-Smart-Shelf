<div class="page space-y-6">
    <div>
        <h1 class="page-title">Pengaturan</h1>
        <p class="page-desc">Batas koneksi, ambang bawaan, dan jejak perubahan konfigurasi.</p>
    </div>
    @if ($message !== '')
        <div class="alert alert-info">{{ $message }}</div>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="card space-y-3">
            <h2 class="text-base font-semibold">Deteksi dan koneksi</h2>
            <p class="text-sm text-wd-muted">Ambang per rak diatur di halaman perangkat. Nilai di sini dipakai jika rak belum punya ambang sendiri.</p>
            <label class="field-label" for="offline-seconds">Batas waktu perangkat offline (detik)</label>
            <input id="offline-seconds" class="field-input" type="number" min="30" max="3600" wire:model="offlineSeconds">
            @error('offlineSeconds') <div class="alert-error rounded-lg px-3 py-2">{{ $message }}</div> @enderror
            <label class="field-label" for="threshold-grams">Ambang deteksi bawaan (gram)</label>
            <input id="threshold-grams" class="field-input" wire:model="thresholdGrams" inputmode="decimal">
            @error('thresholdGrams') <div class="alert-error rounded-lg px-3 py-2">{{ $message }}</div> @enderror
            <button class="btn btn-primary" type="button" wire:click="save">Simpan pengaturan</button>
        </section>
        <section class="card space-y-3">
            <h2 class="text-base font-semibold">Integrasi kasir</h2>
            <p class="text-sm">Webhook: <span class="font-mono text-xs">POST /api/pos/transactions</span></p>
            <p class="text-sm">Token webhook: {{ $posReady ? 'tersedia di lingkungan server' : 'belum diatur' }}</p>
            <p class="text-sm text-wd-muted">Token tidak ditampilkan di dashboard. Untuk demo, halaman transaksi kasir mencatat ke backend yang sama.</p>
            <div class="flex flex-wrap gap-2">
                <a class="btn" href="{{ route('dashboard.profile') }}">Profil</a>
                @if (auth()->user()->hasPermission(\App\Enums\PermissionName::UsersView))
                    <a class="btn" href="{{ route('dashboard.users') }}">Pengguna</a>
                @endif
                <a class="btn" href="{{ route('dashboard.devices') }}">Ambang per rak</a>
            </div>
        </section>
    </div>

    <section class="card">
        <h2 class="mb-3 text-base font-semibold">Jejak audit</h2>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Waktu (WIB)</th><th>Akun</th><th>Perubahan</th></tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }}</td>
                            <td>{{ $log->user->name ?? 'Sistem' }}</td>
                            <td>{{ $log->summary }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">Belum ada perubahan konfigurasi yang dicatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
