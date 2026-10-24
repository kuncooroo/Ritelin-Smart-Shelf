@extends('layouts.app', ['title' => 'Cetak laporan'])

@section('content')
    <div class="page space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="page-title">Laporan rak</h1>
                <p class="page-desc">{{ $report['from'] }} s.d. {{ $report['to'] }}. Simpan sebagai PDF dari dialog cetak peramban.</p>
            </div>
            <button class="btn btn-primary" type="button" onclick="window.print()">Cetak</button>
        </div>
        <x-shelf-disclaimer />
        <section class="card">
            <p class="text-sm">Peristiwa {{ $report['totals']['events'] }}, transaksi {{ $report['totals']['transactions'] }}, anomali {{ $report['totals']['anomalies'] }}.</p>
            <p class="mt-2 text-sm text-wd-muted">Cocok {{ $report['totals']['matched'] }}, belum cocok {{ $report['totals']['unmatched'] }}, perlu ditinjau {{ $report['totals']['needs_review'] }}.</p>
        </section>
        <section class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Tanggal</th><th>Diambil</th><th>Dikembalikan</th><th>Transaksi</th><th>Anomali</th></tr></thead>
                    <tbody>
                        @foreach ($report['days'] as $day)
                            <tr>
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
        </section>
    </div>
@endsection
