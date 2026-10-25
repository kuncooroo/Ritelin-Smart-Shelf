@php
    $days = $report['days'] ?? [];
    $products = $report['products'] ?? [];
    $totals = $report['totals'] ?? [];
    $dayPeak = 1;
    foreach ($days as $day) {
        $dayPeak = max($dayPeak, (int) $day['removed'], (int) $day['returned'], (int) $day['transactions']);
    }
    $productPeak = 1;
    foreach ($products as $product) {
        $productPeak = max($productPeak, (int) $product['removed']);
    }
    $matched = (int) ($totals['matched'] ?? 0);
    $unmatched = (int) ($totals['unmatched'] ?? 0);
    $needsReview = (int) ($totals['needs_review'] ?? 0);
    $matchTotal = max(1, $matched + $unmatched + $needsReview);
@endphp

<section class="card space-y-6">
    <div>
        <h2 class="text-base font-semibold">Aktivitas harian</h2>
        <p class="mt-1 text-sm text-wd-muted">Tinggi batang mengikuti jumlah pada tanggal itu. Perubahan berat tetap indikasi, bukan bukti transaksi.</p>
    </div>
    <div class="flex items-end gap-2 overflow-x-auto pb-2">
        @forelse ($days as $day)
            <div class="flex min-w-16 flex-1 flex-col items-center gap-2">
                <div class="flex h-36 w-full items-end justify-center gap-1">
                    @foreach ([
                        'removed' => 'bg-wd-accent',
                        'returned' => 'bg-wd-ink',
                        'transactions' => 'bg-wd-muted',
                    ] as $key => $color)
                        <div
                            class="w-2 rounded-t {{ $color }}"
                            style="height: {{ max(4, (int) round(((int) $day[$key] / $dayPeak) * 144)) }}px"
                            title="{{ $day[$key] }}"
                        ></div>
                    @endforeach
                </div>
                <span class="text-[11px] text-wd-muted">{{ \Illuminate\Support\Carbon::parse($day['date'])->format('d/m') }}</span>
            </div>
        @empty
            <p class="text-sm text-wd-muted">Belum ada tanggal pada rentang ini.</p>
        @endforelse
    </div>
    <div class="flex flex-wrap gap-4 text-xs text-wd-muted">
        <span class="inline-flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm bg-wd-accent"></span> Diambil</span>
        <span class="inline-flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm bg-wd-ink"></span> Berat naik</span>
        <span class="inline-flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm bg-wd-muted"></span> Transaksi</span>
    </div>
</section>

<section class="grid gap-4 lg:grid-cols-2">
    <div class="card">
        <h2 class="text-base font-semibold">Produk paling sering diambil</h2>
        <div class="mt-4 space-y-3">
            @forelse ($products as $product)
                <div>
                    <div class="mb-1 flex justify-between gap-3 text-sm">
                        <span>{{ $product['name'] }}</span>
                        <span class="text-wd-muted">{{ $product['removed'] }}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-wd-sidebar">
                        <div class="h-full rounded-full bg-wd-accent" style="width: {{ max(4, (int) round(((int) $product['removed'] / $productPeak) * 100)) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-wd-muted">Belum ada pengambilan pada rentang ini.</p>
            @endforelse
        </div>
    </div>
    <div class="card">
        <h2 class="text-base font-semibold">Status pencocokan</h2>
        <div class="mt-4 flex h-3 overflow-hidden rounded-full bg-wd-sidebar">
            <div class="h-full bg-wd-accent" style="width: {{ ($matched / $matchTotal) * 100 }}%"></div>
            <div class="h-full bg-wd-ink" style="width: {{ ($unmatched / $matchTotal) * 100 }}%"></div>
            <div class="h-full bg-wd-muted" style="width: {{ ($needsReview / $matchTotal) * 100 }}%"></div>
        </div>
        <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between gap-3"><dt>Cocok dengan transaksi</dt><dd>{{ $matched }}</dd></div>
            <div class="flex justify-between gap-3"><dt>Belum ada transaksi</dt><dd>{{ $unmatched }}</dd></div>
            <div class="flex justify-between gap-3"><dt>Perlu ditinjau</dt><dd>{{ $needsReview }}</dd></div>
        </dl>
    </div>
</section>
