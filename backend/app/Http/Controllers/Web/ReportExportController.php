<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ShelfReport;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function csv(Request $request, ShelfReport $report): StreamedResponse
    {
        [$start, $end] = $report->range($request->query('from'), $request->query('to'));
        $data = $report->build($start, $end);
        $filename = 'laporan-rak-'.$data['from'].'-'.$data['to'].'.csv';

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['jenis', 'tanggal', 'kode', 'nama', 'diambil', 'berat_naik', 'transaksi', 'anomali', 'keterangan']);
            fputcsv($out, [
                'ringkasan',
                $data['from'].' s.d. '.$data['to'],
                '',
                '',
                $data['totals']['removed'],
                $data['totals']['returned'],
                $data['totals']['transactions'],
                $data['totals']['anomalies'],
                'Cocok '.$data['totals']['matched'].'; belum cocok '.$data['totals']['unmatched'].'; perlu ditinjau '.$data['totals']['needs_review'],
            ]);

            foreach ($data['days'] as $day) {
                fputcsv($out, ['harian', $day['date'], '', '', $day['removed'], $day['returned'], $day['transactions'], $day['anomalies'], '']);
            }

            foreach ($data['products'] as $product) {
                fputcsv($out, ['produk', '', $product['sku'], $product['name'], $product['removed'], '', '', '', 'Frekuensi pengambilan']);
            }

            foreach ($data['devices'] as $device) {
                fputcsv($out, ['perangkat', '', $device['code'], $device['name'], '', '', '', $device['anomalies'], $device['connection'].'; peristiwa '.$device['events'].'; terakhir '.$device['last_seen']]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function print(Request $request, ShelfReport $report): View
    {
        [$start, $end] = $report->range($request->query('from'), $request->query('to'));

        return view('dashboard.reports-print', [
            'report' => $report->build($start, $end),
        ]);
    }
}
