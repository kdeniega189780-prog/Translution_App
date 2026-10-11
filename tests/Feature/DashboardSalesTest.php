<?php

namespace Tests\Feature;

use App\Application\DashboardSalesReport;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-01 10:00:00', 'Asia/Manila'));
        $this->seed();
        Invoice::query()->update(['paid_at' => null]);
    }

    private function payment(?string $paidAt, string $amount): Invoice
    {
        $booking = Booking::first()->replicate();
        $booking->save();

        return Invoice::create(['booking_id' => $booking->id, 'number' => 'TEST-'.$booking->id, 'total' => $amount, 'paid_at' => $paidAt]);
    }

    private function signIn(string $role): void
    {
        $user = User::where('role', $role)->firstOrFail();
        $user->update(['must_change_password' => false]);
        $this->actingAs($user)->withSession(['session_version' => $user->session_version]);
    }

    public function test_graphs_bucket_paid_sales_at_calendar_boundaries_and_preserve_cents(): void
    {
        $this->payment('2026-09-27 23:59:59', '900.00');
        $this->payment('2026-09-30 12:00:00', '50.00');
        $this->payment('2026-10-01 00:00:00', '0.10');
        $this->payment('2026-10-01 23:59:59', '0.20');
        $this->payment('2026-10-02 12:00:00', '100.00');
        $this->payment('2026-10-31 23:59:59', '200.00');
        $this->payment('2026-11-01 00:00:00', '700.00');
        $this->payment(null, '2000.00');
        $this->payment('2026-10-01 09:00:00', '400.00')->delete();
        $reversed = $this->payment('2026-10-01 11:00:00', '300.00');
        $reversed->update(['paid_at' => null]);

        $charts = app(DashboardSalesReport::class)->build('2026-10-01');

        $this->assertCount(24, $charts['daily']['points']);
        $this->assertSame('0.30', $charts['daily']['total']);
        $this->assertSame('0.15', $charts['daily']['average']);
        $this->assertSame(2, $charts['daily']['payments']);
        $this->assertSame('0.10', $charts['daily']['points'][0]['amount']);
        $this->assertSame('0.20', $charts['daily']['points'][23]['amount']);
        $this->assertSame('0.00', $charts['daily']['points'][1]['amount']);
        $this->assertCount(7, $charts['weekly']['points']);
        $this->assertSame('Mon', $charts['weekly']['points'][0]['label']);
        $this->assertSame('150.30', $charts['weekly']['total']);
        $this->assertSame(4, $charts['weekly']['payments']);
        $this->assertSame('Sep 28 – Oct 4, 2026', $charts['weekly']['range']);
        $this->assertCount(31, $charts['monthly']['points']);
        $this->assertSame('300.30', $charts['monthly']['total']);
        $this->assertSame('200.00', $charts['monthly']['points'][30]['amount']);
        $this->assertSame(4, $charts['monthly']['payments']);
    }

    public function test_empty_periods_and_leap_month_have_complete_zero_filled_points(): void
    {
        $charts = app(DashboardSalesReport::class)->build('2024-02-29');
        $this->assertCount(29, $charts['monthly']['points']);
        foreach ($charts as $chart) {
            $this->assertSame('0.00', $chart['total']);
            $this->assertSame('0.00', $chart['average']);
            $this->assertSame(0, $chart['payments']);
            $this->assertSame(['0.00'], array_values(array_unique(array_column($chart['points'], 'amount'))));
        }
    }

    public function test_admin_can_select_a_date_and_period_and_other_roles_receive_no_sales_data(): void
    {
        $this->payment('2026-10-01 09:00:00', '1250.50');
        $this->signIn('admin');
        $this->get('/dashboard')->assertOk()->assertViewHas('salesPeriod', 'weekly');
        $this->get('/dashboard?sales_period=monthly&sales_date=2026-10-01')->assertOk()
            ->assertSee('Sales overview')->assertViewHas('salesPeriod', 'monthly')
            ->assertViewHas('salesDate', '2026-10-01')
            ->assertViewHas('salesCharts', fn ($charts) => $charts['monthly']['total'] === '1250.50');

        foreach (['secretary', 'mechanic', 'purchaser', 'customer'] as $role) {
            $this->signIn($role);
            $this->get('/dashboard')->assertOk()->assertDontSee('data-sales-chart', false)->assertViewMissing('salesCharts');
        }
    }

    public function test_dashboard_rejects_invalid_sales_filters(): void
    {
        $this->signIn('admin');
        $this->get('/dashboard?sales_period=yearly&sales_date=bad-date')->assertSessionHasErrors(['sales_period', 'sales_date']);
    }

    public function test_payment_method_layers_add_up_to_sales_in_every_view(): void
    {
        foreach (['Cash' => '100.10', 'GCash' => '200.20', 'Bank Transfer' => '300.30', 'PayMaya' => '400.40'] as $method => $amount) {
            $this->payment('2026-10-01 09:00:00', $amount)->update(['payment_method' => $method]);
        }
        $this->payment('2026-10-01 09:00:00', '50.50');
        $this->payment('2026-10-01 09:00:00', '25.25')->update(['payment_method' => 'Legacy']);

        foreach (app(DashboardSalesReport::class)->build('2026-10-01') as $chart) {
            $this->assertSame('1076.75', $chart['total']);
            foreach ($chart['points'] as $point) {
                $sum = array_reduce($point['methods'], fn ($sum, $amount) => bcadd($sum, $amount, 2), '0.00');
                $this->assertSame($point['amount'], $sum);
                if ($point['payments'] > 0) {
                    $this->assertSame(['Cash' => '100.10', 'GCash' => '200.20', 'Bank Transfer' => '300.30', 'PayMaya' => '400.40', 'Other' => '75.75'], $point['methods']);
                }
            }
        }
    }
}
