<?php

namespace App\Http\Controllers;

use App\Application\DashboardSalesReport;
use App\Http\Requests\DashboardRequest;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\PartsOrder;

class DashboardController extends Controller
{
    public function __invoke(DashboardRequest $r, DashboardSalesReport $salesReport)
    {
        $u = $r->user();
        $date = $r->input('date', today()->toDateString());
        $base = Booking::visibleTo($u);
        $data = ['date' => $date, 'role' => $u->role, 'notifications' => $u->unreadNotifications()->latest()->limit(5)->get()];
        if ($u->role === 'admin') {
            $data['salesDate'] = $r->input('sales_date') ?: today()->toDateString();
            $data['salesPeriod'] = $r->input('sales_period') ?: 'daily';
            $data['salesCharts'] = $salesReport->build($data['salesDate']);
        }
        if ($u->role === 'purchaser') {
            return view('dashboard.index', $data + ['lowStock' => Part::whereColumn('quantity', '<=', 'reorder_level')->get(), 'openOrders' => PartsOrder::where('status', 'ordered')->count(), 'draftOrders' => PartsOrder::where('status', 'draft')->count(), 'partCount' => Part::count(), 'orders' => PartsOrder::with('items.part')->latest()->orderByDesc('id')->paginate(5, ['*'], 'recent_page')->withQueryString()->fragment('recent-orders')]);
        }

        $view = in_array($u->role, ['secretary', 'mechanic'], true) ? 'dashboard.team' : 'dashboard.index';

        return view($view, $data + [
            'approvalCount' => (clone $base)->where('status', 'processing')->whereNotNull('diagnosis')->where('consent', 'pending')->count(),
            'readyCount' => (clone $base)->where('status', 'completed')->count(),
            'workQueue' => (clone $base)->with('customer', 'vehicle', 'service')->whereIn('status', $u->role === 'mechanic' ? ['processing'] : ['pending', 'scheduled'])->orderBy('preferred_at')->paginate(6, ['*'], 'queue_page')->withQueryString()->fragment('work-queue'),
            'todayCount' => (clone $base)->where(fn ($q) => $q->whereDate('scheduled_at', today())->orWhere(fn ($q) => $q->whereNull('scheduled_at')->whereDate('preferred_at', today())))->count(),
            'processingCount' => (clone $base)->where('status', 'processing')->count(),
            'pendingCount' => (clone $base)->where('status', 'pending')->count(),
            'completedCount' => (clone $base)->whereIn('status', ['completed', 'released'])->count(),
            'totalCount' => (clone $base)->count(),
            'salesToday' => $u->role === 'admin' ? Invoice::whereDate('paid_at', today())->sum('total') : null,
            'lowStock' => $u->role === 'admin' ? Part::whereColumn('quantity', '<=', 'reorder_level')->get() : collect(),
            'schedule' => (clone $base)->with('customer', 'vehicle', 'service', 'staff')->whereDate('scheduled_at', $date)->orderBy('scheduled_at')->get(),
            'recent' => (clone $base)->with('customer', 'vehicle', 'service')->latest()->orderByDesc('id')->paginate(5, ['*'], 'recent_page')->withQueryString()->fragment('recent-bookings'),
        ]);
    }
}
