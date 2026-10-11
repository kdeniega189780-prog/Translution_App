<?php

namespace Tests\Feature;

use App\Application\DashboardSalesReport;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\WorkshopDemoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkshopDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_pack_is_additive_repeatable_and_has_consistent_paid_history(): void
    {
        $this->travelTo(Carbon::parse('2026-10-11 11:00:00', 'Asia/Manila'));
        $this->seed();
        $originalUsers = User::all()->toArray();
        $originalBookings = Booking::all()->toArray();
        $this->seed(WorkshopDemoSeeder::class);
        $this->assertDatabaseCount('customers', 49);
        $this->assertDatabaseCount('vehicles', 53);
        $this->assertDatabaseCount('bookings', 141);
        $this->assertSame(121, Invoice::whereNotNull('paid_at')->count());
        $this->assertSame($originalUsers, User::all()->toArray());
        $this->assertSame($originalBookings, Booking::where('id', '<=', 5)->get()->toArray());
        $this->assertSame('2026-10-11 11:00:00', now()->toDateTimeString());
        $this->assertSame(0, Invoice::where('paid_at', '>', now())->count());

        foreach (Invoice::with('items', 'booking')->whereNotNull('paid_at')->get() as $invoice) {
            $this->assertSame('released', $invoice->booking->status->value);
            $this->assertEquals($invoice->total, $invoice->items->sum(fn ($item) => $item->quantity * $item->unit_price));
            $this->assertTrue($invoice->paid_at->greaterThanOrEqualTo($invoice->booking->completed_at));
        }
        $this->assertEmpty(DB::table('bookings')->selectRaw('DATE(scheduled_at) as day, COUNT(*) as total')
            ->whereNotNull('scheduled_at')->groupByRaw('DATE(scheduled_at)')->havingRaw('COUNT(*) > 4')->get());
        $this->assertEmpty(DB::table('bookings')->select('scheduled_at')->whereNotNull('scheduled_at')
            ->groupBy('scheduled_at')->havingRaw('COUNT(*) > 1')->get());
        $monthly = app(DashboardSalesReport::class)->build('2026-10-11')['monthly'];
        $this->assertGreaterThan(5, count(array_filter($monthly['points'], fn ($point) => $point['payments'] > 0)));
        $this->assertEquals(Invoice::whereBetween('paid_at', ['2026-10-01', '2026-10-31 23:59:59'])->sum('total'), $monthly['total']);

        Customer::where('email', 'workshop-demo-1@transolutions.test')->firstOrFail()->delete();
        $snapshot = Booking::all()->toArray();
        $this->seed(WorkshopDemoSeeder::class);
        $this->assertSame(49, Customer::withTrashed()->count());
        $this->assertSame($snapshot, Booking::all()->toArray());
        $this->assertDatabaseCount('invoices', 126);
    }

    public function test_sample_pack_rolls_back_on_conflict_and_restores_the_clock(): void
    {
        $this->travelTo(Carbon::parse('2026-10-11 11:00:00', 'Asia/Manila'));
        $this->seed();
        DB::table('vehicles')->where('id', 1)->update(['plate_number' => 'DEMO 1002']);
        try {
            $this->seed(WorkshopDemoSeeder::class);
            $this->fail('Expected the conflicting plate to abort the sample import.');
        } catch (QueryException $exception) {
            $this->assertDatabaseCount('customers', 1);
            $this->assertDatabaseCount('bookings', 5);
            $this->assertSame('2026-10-11 11:00:00', now()->toDateTimeString());
        }
    }
}
