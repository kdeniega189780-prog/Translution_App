<?php

namespace Tests\Feature;

use App\Application\BookingWorkflow;
use App\Application\InventoryWorkflow;
use App\Application\ReportBuilder;
use App\Domain\Bookings\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Part;
use App\Models\PartsOrder;
use App\Models\Service;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class WorkshopTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_draft_is_editable_but_ordered_items_are_locked(): void
    {
        $flow = app(InventoryWorkflow::class);
        $data = ['supplier' => 'Singapore', 'items' => [['part_id' => $this->part->id, 'quantity' => 2, 'unit_cost' => 100]]];
        $order = $flow->createOrder($this->buyer, $data);
        $this->loginAs($this->buyer)->get(route('orders.edit', $order))->assertOk();
        $data['items'][0]['quantity'] = 7;
        $this->put(route('orders.update', $order), $data)->assertRedirect(route('orders.index'));
        $this->assertSame(7, $order->fresh()->items->first()->quantity);
        $flow->advance($this->buyer, $order, 'draft');
        $this->put(route('orders.update', $order), $data)->assertSessionHasErrors();
        $this->assertSame(0, $this->part->fresh()->quantity);
    }

    public function test_registration_enforces_all_password_rules(): void
    {
        foreach (['short', 'lowercase!123', 'UppercaseOnly!', 'NoSymbol123'] as $password) {
            $this->post('/register', ['name' => 'New user', 'email' => 'new@example.test', 'mobile' => '09171234567', 'password' => $password, 'password_confirmation' => $password])->assertSessionHasErrors('password');
        }
        $this->assertDatabaseMissing('users', ['email' => 'new@example.test']);
    }

    private User $admin;

    private User $secretary;

    private User $mechanic;

    private User $buyer;

    private User $customer;

    private Vehicle $vehicle;

    private Service $service;

    private Part $part;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['admin', 'secretary', 'mechanic', 'purchaser', 'customer'] as $role) {
            $users[$role] = User::create(['name' => ucfirst($role), 'email' => $role.'@example.test', 'mobile' => '09171234567', 'role' => $role, 'password' => 'Test!1234']);
        }
        [$this->admin,$this->secretary,$this->mechanic,$this->buyer,$this->customer] = array_values($users);
        $c = Customer::create(['user_id' => $this->customer->id, 'name' => 'Customer', 'mobile' => '09171234567']);
        $this->vehicle = Vehicle::create(['customer_id' => $c->id, 'plate_number' => 'DAV 123', 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2020, 'transmission' => 'Automatic']);
        $this->service = Service::create(['name' => 'Inspection', 'price' => 500]);
        $this->part = Part::create(['name' => 'Filter', 'category' => 'Transmission', 'quantity' => 0, 'unit_cost' => 125.50, 'reorder_level' => 2, 'supplier' => 'Singapore']);
    }

    private function loginAs(User $u): static
    {
        return $this->actingAs($u)->withSession(['session_version' => $u->session_version]);
    }

    private function booking(): Booking
    {
        return app(BookingWorkflow::class)->create($this->customer, ['vehicle_id' => $this->vehicle->id, 'service_id' => $this->service->id, 'preferred_at' => today()->addDay()->setTime(9, 0), 'problem' => 'Slipping transmission']);
    }

    private function processing(): Booking
    {
        $b = $this->booking();
        $f = app(BookingWorkflow::class);
        $f->advance($this->secretary, $b, ['expected_status' => 'pending', 'scheduled_at' => today()->addDay()->setTime(9, 0)]);
        $f->advance($this->secretary, $b, ['expected_status' => 'scheduled', 'assigned_staff_id' => $this->mechanic->id]);
        $f->diagnose($this->mechanic, $b, ['diagnosis' => 'Replace filter']);
        $f->consent($this->customer, $b, ['consent' => 'agreed']);

        return $b->fresh();
    }

    private function receive(int $quantity = 5): PartsOrder
    {
        $f = app(InventoryWorkflow::class);
        $o = $f->createOrder($this->buyer, ['supplier' => 'Singapore', 'items' => [['part_id' => $this->part->id, 'quantity' => $quantity, 'unit_cost' => 125.50]]]);
        $f->advance($this->buyer, $o, 'draft');
        $f->advance($this->buyer, $o, 'ordered');

        return $o->fresh();
    }

    public function test_complete_flow_connects_stock_invoice_notification_history_and_sales(): void
    {
        $this->receive();
        $b = $this->processing();
        $f = app(BookingWorkflow::class);
        $f->usePart($this->mechanic, $b, ['part_id' => $this->part->id, 'quantity' => 2]);
        $f->advance($this->mechanic, $b, ['expected_status' => 'processing']);
        $i = $b->fresh()->invoice;
        $this->assertSame('751.00', $i->total);
        $this->assertCount(2, $i->items);
        $this->assertSame(3, $this->part->fresh()->quantity);
        $this->assertSame(2, StockMovement::count());
        $this->assertSame(1, $this->customer->notifications()->count());
        $f->pay($this->secretary, $i, 'GCash');
        $this->assertSame(BookingStatus::Released, $b->fresh()->status);
        $this->assertEquals(751, app(ReportBuilder::class)->build('daily', today()->toDateString())['total']);
        $this->loginAs($this->customer)->get(route('vehicles.show', $this->vehicle))->assertOk()->assertSee('751.00')->assertSee('Filter');
    }

    public function test_insufficient_stock_is_atomic(): void
    {
        $b = $this->processing();
        $this->loginAs($this->mechanic)->post(route('bookings.parts', $b), ['part_id' => $this->part->id, 'quantity' => 1])->assertSessionHasErrors();
        $this->assertSame(0, $this->part->fresh()->quantity);
        $this->assertSame(0, $b->parts()->count());
        $this->assertSame(0, StockMovement::count());
    }

    public function test_received_order_cannot_add_stock_twice(): void
    {
        $o = $this->receive();
        $this->loginAs($this->buyer)->post(route('orders.advance', $o), ['expected_status' => 'ordered'])->assertSessionHasErrors();
        $this->assertSame(5, $this->part->fresh()->quantity);
        $this->assertSame(1, StockMovement::count());
    }

    public function test_payment_is_idempotent_and_reversal_is_audited(): void
    {
        $b = $this->processing();
        $f = app(BookingWorkflow::class);
        $f->advance($this->mechanic, $b, ['expected_status' => 'processing']);
        $i = $b->fresh()->invoice;
        $f->pay($this->secretary, $i, 'Cash');
        $this->loginAs($this->secretary)->post(route('invoices.pay', $i), ['payment_method' => 'Cash'])->assertSessionHasErrors();
        $this->post(route('bookings.reverse', $b), ['reason' => 'Payment entry was incorrect'])->assertForbidden();
        $f->reverse($this->admin, $b, 'Payment entry was incorrect');
        $this->assertNull($i->fresh()->paid_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'payment.reversed']);
        $this->assertEquals(0, app(ReportBuilder::class)->build('daily', today()->toDateString())['total']);
    }

    public function test_customer_cannot_access_another_customers_records_or_forge_vehicle(): void
    {
        $b = $this->processing();
        app(BookingWorkflow::class)->advance($this->mechanic, $b, ['expected_status' => 'processing']);
        $other = User::create(['name' => 'Other', 'email' => 'other@example.test', 'mobile' => '09171234567', 'password' => 'Test!1234', 'role' => 'customer']);
        Customer::create(['user_id' => $other->id, 'name' => 'Other', 'mobile' => '09171234567']);
        $this->loginAs($other);
        foreach ([route('bookings.show', $b), route('vehicles.show', $this->vehicle), route('invoices.show', $b->fresh()->invoice)] as $url) {
            $this->get($url)->assertForbidden();
        } $this->post(route('bookings.store'), ['vehicle_id' => $this->vehicle->id, 'service_id' => $this->service->id, 'preferred_at' => today()->addDay()->format('Y-m-d H:i'), 'problem' => 'test'])->assertForbidden();
        $this->post(route('bookings.consent', $b), ['consent' => 'agreed'])->assertForbidden();
    }

    public function test_customer_cannot_set_role_status_price_or_owner(): void
    {
        $this->loginAs($this->customer)->post(route('bookings.store'), ['vehicle_id' => $this->vehicle->id, 'service_id' => $this->service->id, 'preferred_at' => today()->addDay()->format('Y-m-d H:i'), 'problem' => 'test', 'status' => 'released', 'service_price' => 0, 'customer_id' => 999, 'role' => 'admin'])->assertRedirect();
        $b = Booking::first();
        $this->assertSame(BookingStatus::Pending, $b->status);
        $this->assertSame('500.00', $b->service_price);
        $this->assertSame($this->vehicle->customer_id, $b->customer_id);
    }

    public function test_roles_are_enforced_server_side(): void
    {
        $this->loginAs($this->customer);
        foreach (['customers.index', 'users.index', 'reports.index', 'inventory.index', 'orders.index', 'jobs.index', 'services.index'] as $route) {
            $this->get(route($route))->assertForbidden();
        } $this->loginAs($this->buyer)->get(route('bookings.index'))->assertForbidden();
        $this->loginAs($this->secretary)->get(route('reports.index'))->assertForbidden();
    }

    public function test_declined_repair_cannot_use_parts_or_complete(): void
    {
        $b = $this->processing();
        $b->update(['consent' => 'declined']);
        $this->receive();
        $this->loginAs($this->mechanic)->post(route('bookings.parts', $b), ['part_id' => $this->part->id, 'quantity' => 1])->assertSessionHasErrors();
        $this->post(route('bookings.advance', $b), ['expected_status' => 'processing'])->assertSessionHasErrors();
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_schedule_capacity_and_stale_status_are_enforced(): void
    {
        $f = app(BookingWorkflow::class);
        for ($n = 0; $n < 4; $n++) {
            $b = $this->booking();
            $f->advance($this->secretary, $b, ['expected_status' => 'pending', 'scheduled_at' => today()->addDay()->setTime(9 + $n, 0)]);
        } $fifth = $this->booking();
        $this->loginAs($this->secretary)->post(route('bookings.advance', $fifth), ['expected_status' => 'pending', 'scheduled_at' => today()->addDay()->setTime(15, 0)->format('Y-m-d H:i')])->assertSessionHasErrors();
        $this->post(route('bookings.advance', $b), ['expected_status' => 'pending', 'scheduled_at' => today()->addDay()->setTime(15, 0)->format('Y-m-d H:i')])->assertSessionHasErrors();
    }

    public function test_profile_cannot_change_role_and_forces_initial_password_change(): void
    {
        $this->admin->update(['must_change_password' => true]);
        $this->loginAs($this->admin)->get(route('dashboard'))->assertRedirect(route('profile.edit'));
        $this->put(route('profile.update'), ['name' => 'Owner', 'mobile' => '09171234567', 'current_password' => 'Test!1234', 'password' => 'Changed!1234', 'password_confirmation' => 'Changed!1234', 'role' => 'customer'])->assertRedirect(route('dashboard'));
        $this->assertSame('admin', $this->admin->fresh()->role);
        $this->assertFalse($this->admin->fresh()->must_change_password);
    }

    public function test_deactivated_and_revoked_sessions_are_rejected(): void
    {
        $this->loginAs($this->secretary);
        $this->secretary->update(['active' => false]);
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_has_generic_errors_and_throttles_five_attempts(): void
    {
        for ($n = 0; $n < 5; $n++) {
            $this->post('/login', ['email' => 'admin@example.test', 'password' => 'wrong'])->assertSessionHasErrors(['email' => 'The email or password is incorrect.']);
        } $this->post('/login', ['email' => 'admin@example.test', 'password' => 'Test!1234'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_regenerates_session_and_registration_always_creates_customer(): void
    {
        $this->post('/register', ['name' => 'New user', 'email' => 'new@example.test', 'mobile' => '09171234567', 'password' => 'Secure!1234', 'password_confirmation' => 'Secure!1234', 'role' => 'admin'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'new@example.test', 'role' => 'customer']);
        $this->get('/dashboard')->assertOk();
    }

    public function test_reset_is_single_use_expires_and_has_generic_email_response(): void
    {
        Notification::fake();
        $this->post('/forgot-password', ['email' => 'missing@example.test'])->assertSessionHas('status');
        $this->post('/forgot-password', ['email' => $this->admin->email])->assertSessionHas('status');
        $token = Password::createToken($this->admin);
        $data = ['token' => $token, 'email' => $this->admin->email, 'password' => 'NewSecret!123', 'password_confirmation' => 'NewSecret!123'];
        $this->post('/reset-password', $data)->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('NewSecret!123', $this->admin->fresh()->password));
        $this->post('/reset-password', $data)->assertSessionHasErrors();
        $token = Password::createToken($this->admin);
        $this->travel(16)->minutes();
        $this->post('/reset-password', [...$data, 'token' => $token])->assertSessionHasErrors();
    }

    public function test_all_role_pages_render_and_exports_work(): void
    {
        $b = $this->processing();
        app(BookingWorkflow::class)->advance($this->mechanic, $b, ['expected_status' => 'processing']);
        $o = $this->receive();
        $this->loginAs($this->admin);
        $urls = ['/dashboard', '/bookings', '/bookings/create', '/bookings/'.$b->id, '/customers', '/customers/create', '/customers/'.$this->vehicle->customer_id.'/edit', '/vehicles', '/vehicles/create', '/vehicles/'.$this->vehicle->id, '/vehicles/'.$this->vehicle->id.'/edit', '/services', '/services/create', '/services/'.$this->service->id.'/edit', '/inventory', '/inventory/create', '/inventory/'.$this->part->id.'/edit', '/orders', '/orders/create', '/invoices', '/invoices/'.$b->fresh()->invoice->id, '/reports', '/users', '/users/create', '/users/'.$this->secretary->id.'/edit', '/profile'];
        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        } $this->get('/reports?format=csv')->assertOk()->assertDownload('transolutions-report.csv');
        $this->get('/reports?format=pdf')->assertOk()->assertDownload('transolutions-report.pdf');
        foreach ([$this->secretary, $this->mechanic, $this->buyer, $this->customer] as $u) {
            $this->loginAs($u)->get('/dashboard')->assertOk();
        }
    }

    public function test_paid_invoice_is_preserved_when_attempting_job_edits(): void
    {
        $b = $this->processing();
        $f = app(BookingWorkflow::class);
        $f->advance($this->mechanic, $b, ['expected_status' => 'processing']);
        $i = $b->fresh()->invoice;
        $f->pay($this->secretary, $i, 'Cash');
        $this->loginAs($this->admin)->post(route('bookings.diagnose', $b), ['diagnosis' => 'Changed', 'service_price' => 1])->assertSessionHasErrors();
        $this->assertSame('500.00', $i->fresh()->total);
    }

    public function test_pending_booking_archive_preserves_record(): void
    {
        $b = $this->booking();
        $this->loginAs($this->admin)->delete(route('bookings.destroy', $b))->assertRedirect();
        $this->assertSoftDeleted($b);
        $this->assertDatabaseHas('activity_logs', ['action' => 'booking.archived']);
    }

    public function test_public_landing_page_leads_to_login_and_registration(): void
    {
        $this->get('/')->assertOk()->assertSee('A smoother ride')->assertSee(route('login'))->assertSee(route('register'));
        $this->get('/bookings/create')->assertRedirect(route('login'));
        $this->get('/login')->assertOk()->assertSee('Back to home');
        $this->loginAs($this->mechanic)->get('/')->assertOk()->assertSee('My workspace');
    }

    public function test_secretary_and_mechanic_have_distinct_interfaces_and_permissions(): void
    {
        $b = $this->processing();
        $this->loginAs($this->secretary)->get('/dashboard')->assertOk()->assertSee('Booking & assignment queue')->assertDontSee('On your repair bench');
        $this->get(route('bookings.show', $b))->assertOk()->assertDontSee('Save diagnosis')->assertDontSee('Record parts used')->assertDontSee('Next step: Complete');
        $this->post(route('bookings.diagnose', $b), ['diagnosis' => 'Not permitted'])->assertForbidden();
        $this->post(route('bookings.parts', $b), ['part_id' => $this->part->id, 'quantity' => 1])->assertForbidden();
        $this->post(route('bookings.advance', $b), ['expected_status' => 'processing'])->assertForbidden();

        $this->loginAs($this->mechanic)->get('/dashboard')->assertOk()->assertSee('On your repair bench')->assertDontSee('New booking')->assertDontSee('Invoices & payments', false);
        $this->get(route('bookings.show', $b))->assertOk()->assertSee('Save diagnosis')->assertSee('Record parts used')->assertDontSee('Agree to repair')->assertDontSee('View vehicle service history');
        foreach (['customers.index', 'vehicles.index', 'invoices.index', 'bookings.create', 'users.index', 'reports.index', 'inventory.index'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->post(route('bookings.store'), [])->assertForbidden();
        $this->post(route('bookings.consent', $b), ['consent' => 'agreed'])->assertForbidden();
        app(BookingWorkflow::class)->advance($this->mechanic, $b, ['expected_status' => 'processing']);
        $this->get(route('invoices.show', $b->fresh()->invoice))->assertForbidden();
        $this->post(route('invoices.pay', $b->fresh()->invoice), ['payment_method' => 'Cash'])->assertForbidden();
    }

    public function test_mechanics_cannot_access_or_mutate_unassigned_jobs(): void
    {
        $b = $this->processing();
        $other = User::create(['name' => 'Other mechanic', 'email' => 'other-mechanic@example.test', 'mobile' => '09171234567', 'password' => 'Test!1234', 'role' => 'mechanic']);
        $this->loginAs($other);
        foreach (['/dashboard', '/bookings', '/repair-jobs'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('DAV 123');
        }
        $this->get(route('bookings.show', $b))->assertForbidden();
        $this->post(route('bookings.diagnose', $b), ['diagnosis' => 'Changed'])->assertForbidden();
        $this->post(route('bookings.parts', $b), ['part_id' => $this->part->id, 'quantity' => 1])->assertForbidden();
        $this->post(route('bookings.advance', $b), ['expected_status' => 'processing'])->assertForbidden();
        $this->assertSame('Replace filter', $b->fresh()->diagnosis);
        $this->assertSame(BookingStatus::Processing, $b->fresh()->status);
    }

    public function test_secretary_assigns_only_active_mechanics_and_records_customer_decisions(): void
    {
        $b = $this->booking();
        $this->loginAs($this->mechanic)->post(route('bookings.advance', $b), ['expected_status' => 'pending', 'scheduled_at' => today()->addDay()->setTime(9, 0)->format('Y-m-d H:i')])->assertForbidden();
        $this->loginAs($this->secretary)->post(route('bookings.advance', $b), ['expected_status' => 'pending', 'scheduled_at' => today()->addDay()->setTime(9, 0)->format('Y-m-d H:i')])->assertRedirect();
        $this->post(route('bookings.advance', $b), ['expected_status' => 'scheduled', 'assigned_staff_id' => $this->secretary->id])->assertSessionHasErrors();
        $this->mechanic->update(['active' => false]);
        $this->post(route('bookings.advance', $b), ['expected_status' => 'scheduled', 'assigned_staff_id' => $this->mechanic->id])->assertSessionHasErrors();
        $this->mechanic->update(['active' => true]);
        $this->post(route('bookings.advance', $b), ['expected_status' => 'scheduled', 'assigned_staff_id' => $this->mechanic->id])->assertRedirect();
        $this->loginAs($this->mechanic)->post(route('bookings.diagnose', $b), ['diagnosis' => 'Replace filter'])->assertRedirect();
        $this->loginAs($this->secretary)->get(route('bookings.show', $b))->assertSee('Agree to repair');
        $this->post(route('bookings.consent', $b), ['consent' => 'agreed', 'consent_note' => 'Customer confirmed by phone'])->assertRedirect();
        $this->assertSame('agreed', $b->fresh()->consent);
    }

    public function test_admin_can_create_new_roles_but_cannot_create_legacy_staff(): void
    {
        $this->loginAs($this->admin);
        foreach (['secretary', 'mechanic'] as $role) {
            $this->post(route('users.store'), ['name' => 'New '.$role, 'email' => 'new-'.$role.'@example.test', 'mobile' => '09171234567', 'role' => $role, 'active' => 1, 'password' => 'Temporary!123', 'password_confirmation' => 'Temporary!123'])->assertRedirect(route('users.index'));
            $this->assertDatabaseHas('users', ['email' => 'new-'.$role.'@example.test', 'role' => $role, 'must_change_password' => true]);
        }
        $this->post(route('users.store'), ['name' => 'Old role', 'email' => 'legacy@example.test', 'mobile' => '09171234567', 'role' => 'staff', 'active' => 1, 'password' => 'Temporary!123', 'password_confirmation' => 'Temporary!123'])->assertSessionHasErrors('role');
    }

    public function test_staff_migration_preserves_accounts_and_job_assignments(): void
    {
        $b = $this->processing();
        $this->mechanic->update(['role' => 'staff']);
        $password = $this->mechanic->password;
        $migration = require database_path('migrations/2026_10_08_000000_split_staff_roles.php');
        $migration->up();
        $this->assertSame('mechanic', $this->mechanic->fresh()->role);
        $this->assertSame($password, $this->mechanic->fresh()->password);
        $this->assertSame($this->mechanic->id, $b->fresh()->assigned_staff_id);
        $this->assertSame(2, $this->mechanic->fresh()->session_version);
        $this->assertSame('secretary', $this->secretary->fresh()->role);
    }
}
