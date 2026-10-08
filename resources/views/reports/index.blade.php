@extends('layouts.app')
@section('title', 'Reports')
@section('content')<div class="page-heading">
        <div><span class="eyebrow">A CLEARER PICTURE OF YOUR SHOP</span>
            <h1>Reports</h1>
            <p>{{ $start->format('M j, Y') }} – {{ $end->format('M j, Y') }} · Sales are based on recorded payments.</p>
        </div>
        <div class="inline-form"><a class="button"
                href="{{ route('reports.index', array_merge(request()->only('period', 'date'), ['format' => 'csv'])) }}">Export
                CSV</a><a class="button primary"
                href="{{ route('reports.index', array_merge(request()->only('period', 'date'), ['format' => 'pdf'])) }}">Export
                PDF</a></div>
    </div>
    <section class="card" id="report-filters">
        <form method="get" class="filters">
            <div><label for="period">Show</label><select name="period" id="period">
                    @foreach (['daily', 'weekly', 'monthly'] as $p)
                        <option value="{{ $p }}" @selected($period === $p)>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div><label for="date">Date in period</label><input type="date" name="date" id="date"
                    value="{{ request('date', today()->toDateString()) }}"></div><button class="button">View
                report</button><strong class="sales-total">₱{{ number_format($total, 2) }} <small>total
                    sales</small></strong>
        </form>
    </section>
    <div class="report-charts">
        <section class="card">
            <h2>Bookings by status</h2>
            @foreach ($statuses as $label => $count)
                <div class="bar-row"><span>{{ $label }}</span>
                    <div class="bar-track"><i
                            style="width:{{ array_sum($statuses) ? ($count / array_sum($statuses)) * 100 : 0 }}%"></i></div>
                    <strong>{{ $count }}</strong>
                </div>
            @endforeach
        </section>
        <section class="card">
            <h2>Most-used parts</h2>
            @forelse($usage->take(5) as $p)
                <div class="bar-row"><span>{{ $p['name'] }}</span>
                    <div class="bar-track blue"><i
                            style="width:{{ ($p['quantity'] / max(1, $usage->max('quantity'))) * 100 }}%"></i></div>
                    <strong>{{ $p['quantity'] }}</strong>
            </div>@empty<p class="empty">No parts used in this period.</p>
            @endforelse
        </section>
    </div>
    <section class="card">@include('reports.tables')</section>
    <section class="card" id="report-activity">
        <h2>Recent activity</h2>
        <p>Changes to bookings, stock, accounts, and payments.</p>
        {{ $logs->links('pagination.simple', ['label' => 'Activity']) }}
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Who</th>
                        <th>Action</th>
                        <th>Record</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('M j, h:i A') }}</td>
                            <td>{{ $log->user?->name ?? 'System' }}</td>
                            <td>{{ str_replace('.', ' · ', $log->action) }}</td>
                            <td>{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</td>
                            <td>
                                <details>
                                    <summary>View</summary>
                                    @foreach ($log->details ?? [] as $key => $value)
                                        <p>{{ str_replace('_', ' ', $key) }}:
                                            {{ is_scalar($value) ? $value : json_encode($value) }}</p>
                                    @endforeach
                                </details>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $logs->links('pagination.simple', ['label' => 'Activity']) }}
</section>@endsection
