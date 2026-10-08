<?php

namespace App\Application;

use App\Domain\Bookings\BookingStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\StockMovement;
use Carbon\Carbon;

class ReportBuilder
{
    public function build(string $period, string $date, bool $paginate = false): array
    {
        $start = Carbon::parse($date)->startOfDay();
        $end = $start->copy()->endOfDay();
        if ($period === 'weekly') {
            $start->startOfWeek();
            $end = $start->copy()->endOfWeek();
        }
        if ($period === 'monthly') {
            $start->startOfMonth();
            $end = $start->copy()->endOfMonth();
        }
        $salesQuery = Invoice::with('booking.customer', 'booking.vehicle')->whereBetween('paid_at', [$start, $end])->orderBy('paid_at')->orderBy('id');
        $total = (clone $salesQuery)->sum('total');
        $sales = $paginate
            ? $salesQuery->paginate(config('workshop.table_page_size'), ['*'], 'sales_page')->withQueryString()->fragment('report-sales')
            : $salesQuery->get();
        $statuses = [];
        foreach (BookingStatus::cases() as $status) {
            $statuses[$status->label()] = Booking::whereBetween('created_at', [$start, $end])->where('status', $status->value)->count();
        }
        $usage = StockMovement::with('part')->where('type', 'job_usage')->whereBetween('created_at', [$start, $end])->get()->groupBy('part_id')->map(fn ($rows) => ['name' => $rows->first()->part->name, 'quantity' => abs($rows->sum('quantity'))])->sortByDesc('quantity')->take(10)->values();

        $stockQuery = Part::whereColumn('quantity', '<=', 'reorder_level')->orderBy('quantity')->orderBy('id');
        $lowStock = $paginate
            ? $stockQuery->paginate(config('workshop.table_page_size'), ['*'], 'stock_page')->withQueryString()->fragment('report-low-stock')
            : $stockQuery->get();

        return compact('period', 'start', 'end', 'sales', 'statuses', 'usage', 'lowStock', 'total');
    }
}
