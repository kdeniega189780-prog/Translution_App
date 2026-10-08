<?php

namespace App\Http\Controllers;

use App\Application\ReportBuilder;
use App\Http\Requests\ReportRequest;
use App\Models\ActivityLog;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index(ReportRequest $r, ReportBuilder $builder)
    {
        $exporting = in_array($r->format, ['pdf', 'csv'], true);
        $data = $builder->build($r->input('period', 'daily'), $r->input('date', today()->toDateString()), ! $exporting);
        if ($r->format === 'pdf') {
            return Pdf::loadView('reports.pdf', $data)->download('transolutions-report.pdf');
        }
        if ($r->format === 'csv') {
            return response()->streamDownload(function () use ($data) {
                $out = fopen('php://output', 'w');
                $write = function ($row) use ($out) {
                    fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+@\-\t\r]/', $v) ? "'".$v : $v, $row));
                };
                $write(['Sales', 'Date', 'Customer', 'Vehicle', 'Payment method', 'PHP']);
                foreach ($data['sales'] as $i) {
                    $write([$i->number, $i->paid_at->format('Y-m-d'), $i->booking->customer->name, $i->booking->vehicle->plate_number, $i->payment_method, $i->total]);
                }
                $write(['Total', '', '', '', '', $data['total']]);
                $write([]);
                $write(['Bookings created in period', 'Count']);
                foreach ($data['statuses'] as $label => $count) {
                    $write([$label, $count]);
                }
                $write([]);
                $write(['Most-used parts', 'Quantity']);
                foreach ($data['usage'] as $p) {
                    $write([$p['name'], $p['quantity']]);
                }
                $write([]);
                $write(['Current low stock', 'Quantity', 'Reorder level']);
                foreach ($data['lowStock'] as $p) {
                    $write([$p->name, $p->quantity, $p->reorder_level]);
                } fclose($out);
            }, 'transolutions-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return view('reports.index', $data + ['logs' => ActivityLog::with('user')->latest()->orderByDesc('id')->paginate(config('workshop.table_page_size'), ['*'], 'activity_page')->withQueryString()->fragment('report-activity')]);
    }
}
