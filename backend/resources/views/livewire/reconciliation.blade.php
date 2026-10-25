<div class="page space-y-6">
    <div>
        <h1 class="page-title">Rekonsiliasi rak dan kasir</h1>
        <p class="page-desc">Pencocokan memakai SKU, jumlah, dan jendela waktu. Satu perubahan berat tidak selalu sama dengan satu transaksi.</p>
    </div>
    <x-shelf-disclaimer />
    @if ($message !== '')
        <div class="alert alert-info">{{ $message }}</div>
    @endif
    @include('components.action-confirm')

    @if ($noteEvent)
        <x-modal :show="true" :title="'Catatan '.$noteEvent->event_id" close="closeNote">
            <textarea class="field-input" rows="3" wire:model="staffNote"></textarea>
            @error('staffNote') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
            <button class="btn btn-primary mt-3" type="button" wire:click="ask('saveNote', 'Simpan catatan', 'Setujui catatan verifikasi sebelum disimpan?')">Simpan catatan</button>
        </x-modal>
    @endif

    @foreach ([
        'Cocok dengan transaksi' => $matched,
        'Perlu ditinjau' => $review,
        'Belum cocok' => $unmatched,
        'Menunggu jendela waktu' => $pending,
    ] as $heading => $rows)
        <section class="card">
            <h2 class="mb-3 text-base font-semibold">{{ $heading }}</h2>
            <div class="table-wrap">
                <table class="table table-wide">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Peristiwa</th>
                            <th>Waktu rak (WIB)</th>
                            <th>Transaksi</th>
                            <th>Selisih waktu</th>
                            <th>Catatan staf</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $event)
                            <tr @class(['row-unread' => $event->isUnread()])>
                                <td>{{ $rows->firstItem() + $loop->index }}</td>
                                <td>
                                    {{ $event->event_id }}
                                    <div class="text-xs text-wd-muted">{{ $event->device->code }} · {{ $event->device->product->name ?? 'Belum dipetakan' }} · {{ $event->device->slot_label ?: 'tanpa slot' }}</div>
                                </td>
                                <td>{{ $event->occurred_at->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }}</td>
                                <td>
                                    @if ($event->transaction)
                                        {{ $event->transaction->external_id }}
                                        <div class="text-xs text-wd-muted">{{ $event->transaction->occurred_at->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }} · jumlah {{ $event->transaction->quantity }}</div>
                                    @else
                                        Belum ada transaksi
                                    @endif
                                </td>
                                <td>{{ $event->transactionGapLabel() ?: '—' }}</td>
                                <td>{{ $event->staff_note ?: 'Belum ada' }}</td>
                                <td>
                                    <button class="icon-btn" type="button" wire:click="editNote({{ $event->id }})" title="Catatan" aria-label="Catatan {{ $event->event_id }}">
                                        <x-icon name="note" />
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7">Tidak ada peristiwa pada kelompok ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-pager :rows="$rows" />
        </section>
    @endforeach

    <section class="card">
        <h2 class="mb-3 text-base font-semibold">Transaksi tanpa peristiwa rak</h2>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>No</th><th>ID transaksi</th><th>SKU</th><th>Jumlah</th><th>Waktu (WIB)</th><th>Sumber</th></tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>{{ $transactions->firstItem() + $loop->index }}</td>
                            <td>{{ $transaction->external_id }}</td>
                            <td>{{ $transaction->product->sku }}</td>
                            <td>{{ $transaction->quantity }}</td>
                            <td>{{ $transaction->occurred_at->timezone('Asia/Jakarta')->format('d-m-Y H:i') }}</td>
                            <td>{{ $transaction->sourceLabel() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Semua transaksi yang tampil sudah terhubung, atau belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager :rows="$transactions" />
    </section>
</div>
