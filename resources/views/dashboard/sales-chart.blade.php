<section class="card sales-card" data-sales-chart aria-labelledby="sales-heading">
    <div class="sales-heading">
        <div>
            <span class="eyebrow">YOUR SHOP, AT A GLANCE</span>
            <h2 id="sales-heading">Sales overview</h2>
            <p>Follow the payments coming into your workshop.</p>
        </div>
        <div class="sales-tabs" role="tablist" aria-label="Sales report period">
            @foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $period => $label)
                <a id="sales-tab-{{ $period }}" class="sales-tab {{ $salesPeriod === $period ? 'selected' : '' }}"
                    href="{{ route('dashboard', ['date' => $date, 'sales_date' => $salesDate, 'sales_period' => $period]) }}"
                    role="tab" aria-controls="sales-panel-{{ $period }}"
                    aria-selected="{{ $salesPeriod === $period ? 'true' : 'false' }}"
                    tabindex="{{ $salesPeriod === $period ? '0' : '-1' }}" data-sales-tab="{{ $period }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <form method="get" action="{{ route('dashboard') }}" class="sales-date-form">
        <input type="hidden" name="date" value="{{ $date }}">
        <input type="hidden" name="sales_period" value="{{ $salesPeriod }}" data-sales-period-field>
        <label for="sales_date">Report date</label>
        <input id="sales_date" type="date" name="sales_date" value="{{ $salesDate }}" required>
        <button class="button compact">View</button>
        <span class="sales-live-label"><i></i>Recorded payments</span>
    </form>

    @foreach ($salesCharts as $period => $series)
        @php
            $maximum = max(array_column($series['points'], 'amount'));
            $rawStep = (float) $maximum > 0 ? max(1, (float) $maximum / 4) : 250;
            $magnitude = pow(10, floor(log10($rawStep)));
            $step = ceil($rawStep / $magnitude) * $magnitude;
            $ceiling = $step * 4;
            $count = count($series['points']);
        @endphp
        <div id="sales-panel-{{ $period }}" class="sales-panel" role="tabpanel"
            aria-labelledby="sales-tab-{{ $period }}" data-sales-panel="{{ $period }}" @if ($salesPeriod !== $period) hidden @endif>
            <div class="sales-summary">
                <div class="sales-main-total">
                    <span>Total sales <small>· {{ $series['range'] }}</small></span>
                    <strong>₱{{ number_format($series['total'], 2) }}</strong>
                </div>
                <div class="sales-secondary-stat"><span class="sales-stat-icon"><x-icon name="receipt" /></span><div><small>Payments received</small><strong>{{ $series['payments'] }}</strong></div></div>
                <div class="sales-secondary-stat"><span class="sales-stat-icon lavender"><x-icon name="chart" /></span><div><small>Average payment</small><strong>₱{{ number_format($series['average'], 2) }}</strong></div></div>
            </div>

            <div class="sales-chart-caption"><span><i class="sales-legend-dot"></i>{{ $series['grouping'] }}</span><small>PHP · Philippine time</small></div>
            <div class="sales-method-legend" aria-label="Payment methods">
                @foreach (\App\Application\DashboardSalesReport::PAYMENT_METHODS as $methodIndex => $method)
                    @if ($method !== 'Other' || collect($series['points'])->contains(fn ($point) => (float) $point['methods']['Other'] > 0))
                        <span><i class="sales-method-{{ $methodIndex }}"></i>{{ $method }}</span>
                    @endif
                @endforeach
            </div>
            <div class="sales-graph-layout">
                <div class="sales-y-axis" aria-hidden="true">
                    @for ($tick = 4; $tick >= 0; $tick--)
                        @php
                            $amount = $step * $tick;
                            $label = $amount >= 1000000 ? number_format($amount / 1000000, 1).'m' : ($amount >= 1000 ? number_format($amount / 1000, 1).'k' : number_format($amount, 0));
                        @endphp
                        <span style="top:{{ 20 + (4 - $tick) * 75 }}px">{{ $label }}</span>
                    @endfor
                </div>
                <div class="sales-plot" data-sales-plot data-points="{{ json_encode($series['points']) }}"
                    tabindex="0" role="group" aria-label="{{ ucfirst($period) }} sales bar chart. Use Left and Right arrow keys to explore amounts."
                    aria-describedby="sales-help-{{ $period }}">
                    <div class="sales-bars-scroll" data-sales-scroll>
                        <div class="sales-bars {{ $period === 'weekly' ? 'sales-bars-weekly' : '' }}" style="--bar-count: {{ $count }}" data-sales-bars>
                            @foreach ($series['points'] as $index => $point)
                                <div class="sales-bar-column {{ (float) $point['amount'] > 0 ? 'has-sales' : '' }}" data-sales-point="{{ $index }}" aria-hidden="true">
                                    <div class="sales-bar-track">
                                        <div class="sales-bar-fill" style="height: {{ (float) $point['amount'] / $ceiling * 100 }}%">
                                            @foreach (\App\Application\DashboardSalesReport::PAYMENT_METHODS as $methodIndex => $method)
                                                @if ((float) $point['methods'][$method] > 0)
                                                    <span class="sales-bar-segment sales-method-{{ $methodIndex }}" style="height: {{ (float) $point['methods'][$method] / (float) $point['amount'] * 100 }}%" data-sales-method="{{ $method }}"></span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    <span class="sales-bar-label">{{ $point['label'] }}</span>
                                    <span class="sales-bar-amount">₱{{ number_format((float) $point['amount'], (float) $point['amount'] == floor((float) $point['amount']) ? 0 : 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="sales-tooltip" data-sales-tooltip hidden>
                        <small data-sales-tooltip-label></small>
                        <strong data-sales-tooltip-amount></strong>
                        <span data-sales-tooltip-count></span>
                        <div class="sales-tooltip-methods" data-sales-tooltip-methods></div>
                    </div>
                    <span class="sr-only" data-sales-announcement aria-live="polite"></span>
                </div>
            </div>

            <div class="sales-chart-footer">
                <p id="sales-help-{{ $period }}">{{ $series['payments'] ? 'Hover or tap a bar for details. Scroll sideways to see more.' : 'No payments recorded for this '.['daily' => 'day', 'weekly' => 'week', 'monthly' => 'month'][$period].'. Paid invoices will appear here.' }}</p>
                <a href="{{ route('reports.index', ['period' => $period, 'date' => $salesDate]) }}">Full report <x-icon name="arrow" /></a>
            </div>
            <details class="sales-data-table">
                <summary>View amounts as a table</summary>
                <div class="table-wrap"><table>
                    <thead><tr><th>{{ $period === 'daily' ? 'Hour' : 'Day' }}</th><th>Payments</th><th>Sales (PHP)</th></tr></thead>
                    <tbody>@foreach ($series['points'] as $point)<tr><td>{{ $point['description'] }}</td><td>{{ $point['payments'] }}</td><td>{{ number_format($point['amount'], 2) }}</td></tr>@endforeach</tbody>
                </table></div>
            </details>
        </div>
    @endforeach
</section>
