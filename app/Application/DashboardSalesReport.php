<?php

namespace App\Application;

use App\Models\Invoice;
use Carbon\CarbonImmutable;

class DashboardSalesReport
{
    /** Build all three chart views from recorded payments in one database read. */
    public function build(string $date): array
    {
        $anchor = CarbonImmutable::parse($date, config('app.timezone'))->startOfDay();
        $windows = [
            'daily' => [$anchor, $anchor->endOfDay()],
            'weekly' => [$anchor->startOfWeek(CarbonImmutable::MONDAY), $anchor->endOfWeek(CarbonImmutable::SUNDAY)],
            'monthly' => [$anchor->startOfMonth(), $anchor->endOfMonth()],
        ];
        $first = min(array_column($windows, 0));
        $last = max(array_column($windows, 1));
        $payments = Invoice::query()->select(['paid_at', 'total'])
            ->whereBetween('paid_at', [$first, $last])->get();
        $charts = [];

        foreach ($windows as $period => [$start, $end]) {
            $points = [];
            $count = $period === 'daily' ? 24 : (int) $start->diffInDays($end->startOfDay()) + 1;
            for ($index = 0; $index < $count; $index++) {
                $at = $period === 'daily' ? $start->addHours($index) : $start->addDays($index);
                $points[] = [
                    'label' => $at->format($period === 'daily' ? 'g A' : ($period === 'weekly' ? 'D' : 'j')),
                    'description' => $at->format($period === 'daily' ? 'M j, Y · g A' : 'D, M j, Y'),
                    'amount' => '0.00',
                    'payments' => 0,
                ];
            }

            $total = '0.00';
            $paymentCount = 0;
            foreach ($payments as $payment) {
                $at = $payment->paid_at->toImmutable()->timezone(config('app.timezone'));
                if ($at->lt($start) || $at->gt($end)) {
                    continue;
                }
                $index = $period === 'daily' ? $at->hour : (int) $start->diffInDays($at->startOfDay());
                $points[$index]['amount'] = bcadd($points[$index]['amount'], $payment->total, 2);
                $points[$index]['payments']++;
                $total = bcadd($total, $payment->total, 2);
                $paymentCount++;
            }

            $charts[$period] = [
                'points' => $points,
                'total' => $total,
                'payments' => $paymentCount,
                'average' => $paymentCount ? bcdiv($total, (string) $paymentCount, 2) : '0.00',
                'range' => $period === 'daily' ? $start->format('F j, Y') : $start->format('M j').' – '.$end->format('M j, Y'),
                'grouping' => $period === 'daily' ? 'Sales by hour' : 'Sales by day',
            ];
        }

        return $charts;
    }
}
