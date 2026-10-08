<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_has_all_stages_and_paid_history(): void
    {
        $this->seed();
        $this->assertDatabaseCount('users', 5);
        $this->assertDatabaseCount('services', 9);
        $this->assertDatabaseCount('parts', 15);
        $this->assertDatabaseCount('bookings', 5);
        $this->assertSame(1, Invoice::whereNotNull('paid_at')->count());
        $this->assertSame(5, Booking::distinct()->count('status'));
    }
}
