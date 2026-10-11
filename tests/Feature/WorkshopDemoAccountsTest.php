<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\WorkshopDemoAccountsSeeder;
use Database\Seeders\WorkshopDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkshopDemoAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_logins_use_existing_customer_records_and_credentials_are_preserved_on_repeat_runs(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('demo-accounts.txt', "Existing demo credentials\n");
        $this->seed();
        $this->seed(WorkshopDemoSeeder::class);
        $originalPasswords = User::pluck('password', 'id')->all();
        $originalBookings = Booking::all()->toArray();
        $this->seed(WorkshopDemoAccountsSeeder::class);
        $this->assertDatabaseCount('users', 53);
        $this->assertDatabaseCount('customers', 49);
        $this->assertSame($originalBookings, Booking::all()->toArray());
        foreach ($originalPasswords as $id => $hash) {
            $this->assertSame($hash, User::findOrFail($id)->getRawOriginal('password'));
        }
        $contents = Storage::disk('local')->get('demo-accounts.txt');
        $this->assertStringStartsWith("Existing demo credentials\n", $contents);
        $lines = array_values(array_filter(explode("\n", $contents), fn ($line) => str_starts_with($line, 'customer | ')));
        $this->assertCount(48, $lines);
        foreach ($lines as $line) {
            [, $email, $password] = explode(' | ', $line);
            $user = User::where('email', $email)->firstOrFail();
            $this->assertTrue(Hash::check($password, $user->password));
            $this->assertTrue($user->must_change_password);
            $this->assertSame($email, $user->customer->email);
            $this->assertGreaterThan(0, Booking::visibleTo($user)->count());
        }
        [, $email, $password] = explode(' | ', $lines[0]);
        $this->post('/login', compact('email', 'password'))->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect(route('profile.edit'));
        $user = User::where('email', $email)->firstOrFail();
        $user->update(['must_change_password' => false, 'password' => 'Changed!1234']);
        $this->actingAs($user)->withSession(['session_version' => $user->session_version]);
        $this->get('/dashboard')->assertOk();
        $ownBooking = $user->customer->bookings()->firstOrFail();
        $this->get(route('bookings.show', $ownBooking))->assertOk();
        $otherBooking = Booking::where('customer_id', '!=', $user->customer->id)->firstOrFail();
        $this->get(route('bookings.show', $otherBooking))->assertForbidden();
        $this->seed(WorkshopDemoAccountsSeeder::class);
        $this->assertDatabaseCount('users', 53);
        $this->assertSame($contents, Storage::disk('local')->get('demo-accounts.txt'));
        $this->assertTrue(Hash::check('Changed!1234', $user->fresh()->password));
    }

    public function test_email_conflicts_roll_back_accounts_and_keep_the_credentials_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('demo-accounts.txt', 'Original credentials');
        $this->seed();
        foreach ([1, 2] as $index) {
            Customer::create(['name' => 'Sample '.$index.' (Demo)', 'email' => 'workshop-demo-'.$index.'@transolutions.test', 'mobile' => 'Not provided']);
        }
        User::create(['name' => 'Unlinked account', 'email' => 'workshop-demo-2@transolutions.test', 'mobile' => 'Not provided', 'password' => 'Existing!1234']);
        try {
            $this->seed(WorkshopDemoAccountsSeeder::class);
            $this->fail('Expected an email conflict.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('unlinked login', $exception->getMessage());
        }
        $this->assertDatabaseCount('users', 6);
        $this->assertSame(2, Customer::whereNull('user_id')->count());
        $this->assertSame('Original credentials', Storage::disk('local')->get('demo-accounts.txt'));
    }
}
