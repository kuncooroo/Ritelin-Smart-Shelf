<div class="page space-y-6">
    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="page-title">Transaksi kasir</h1>
            <p class="page-desc">Simulator ini menerima transaksi ke backend yang sama. Hasilnya membandingkan waktu, produk, dan jumlah. Sensor tidak membuktikan penjualan.</p>
        </div>
        <button class="icon-btn" type="button" wire:click="openRecord" title="Catat transaksi" aria-label="Catat transaksi">
            <x-icon name="plus" />
        </button>
    </div>
    <section class="card space-y-2">
        <p class="text-sm">Webhook: <span class="font-mono text-xs">POST /api/pos/transactions</span></p>
        <p class="text-sm">Token webhook: {{ $posReady ? 'tersedia di lingkungan server' : 'belum diatur' }}</p>
        <label class="field-label" for="pos-search">Cari transaksi terkait</label>
        <input id="pos-search" class="field-input max-w-md" wire:model.live="search" placeholder="SKU atau ID transaksi">
    </section>
    <section class="card">
        <h2 class="mb-3 text-base font-semibold">Transaksi terbaru</h2>
        <div class="table-wrap">
            <table class="table table-wide">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID transaksi</th>
                        <th>Waktu (WIB)</th>
                        <th>SKU</th>
                        <th>Jumlah</th>
                        <th>Sumber</th>
                        <th>Peristiwa rak</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>{{ $transactions->firstItem() + $loop->index }}</td>
                            <td>{{ $transaction->external_id }}</td>
                            <td>{{ $transaction->occurred_at->timezone('Asia/Jakarta')->format('d-m-Y H:i') }}</td>
                            <td>{{ $transaction->product->sku }}</td>
                            <td>{{ $transaction->quantity }}</td>
                            <td>{{ $transaction->sourceLabel() }}</td>
                            <td>{{ $transaction->shelfLinkLabel() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager :rows="$transactions" />
    </section>

    <x-modal :show="$showRecord" title="Catat transaksi" close="closeRecord">
        <label class="field-label" for="productId">Produk</label>
        <select id="productId" class="field-select" wire:model="productId">
            <option value="">Pilih produk</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
            @endforeach
        </select>
        @error('productId') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <label class="field-label mt-3" for="quantity">Jumlah</label>
        <input id="quantity" class="field-input" type="number" min="1" max="100" wire:model="quantity">
        @error('quantity') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <label class="field-label mt-3" for="occurredAt">Waktu transaksi (WIB)</label>
        <input id="occurredAt" class="field-input" type="datetime-local" wire:model="occurredAt">
        @error('occurredAt') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <button class="btn btn-primary mt-3" type="button" wire:click="record">Simpan transaksi</button>
    </x-modal>
</div>
