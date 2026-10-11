<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WorkshopDemoAccountsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo accounts are only allowed in local or testing environments.');
        }

        $created = DB::transaction(function () {
            User::where('role', 'admin')->where('active', true)->lockForUpdate()->firstOrFail();
            $emails = array_map(fn ($index) => 'workshop-demo-'.$index.'@transolutions.test', range(1, 48));
            $customers = Customer::whereIn('email', $emails)->whereNull('user_id')->lockForUpdate()->get();
            $credentials = [];
            foreach ($customers as $customer) {
                if (! str_ends_with($customer->name, ' (Demo)')) {
                    throw new \RuntimeException('A sample email belongs to a non-demo customer. No accounts were added.');
                }
                if (User::where('email', $customer->email)->exists()) {
                    throw new \RuntimeException('A sample email already has an unlinked login. No accounts were added.');
                }

                $password = Str::password(18);
                $user = User::create([
                    'name' => $customer->name, 'email' => $customer->email,
                    'mobile' => $customer->mobile, 'role' => 'customer',
                    'password' => $password, 'must_change_password' => true,
                ]);
                $customer->update(['user_id' => $user->id]);
                $credentials[] = 'customer | '.$user->email.' | '.$password;
            }

            if ($credentials !== []) {
                $disk = Storage::disk('local');
                $path = 'demo-accounts.txt';
                $existing = $disk->exists($path) ? $disk->get($path) : 'LOCAL DEMO ONLY — change all passwords on first login. Never commit or publish this file.';
                $contents = rtrim($existing)."\n\nSample customer accounts — linked to their existing vehicles and bookings.\n"
                    ."On first login, set a new password and enter a valid mobile number.\n"
                    .implode("\n", $credentials)."\n";
                if (! $disk->put($path, $contents)) {
                    throw new \RuntimeException('Could not save demo credentials. Account creation was rolled back.');
                }
            }

            return count($credentials);
        });

        $this->command?->info("Added {$created} linked customer logins. Credentials: storage/app/private/demo-accounts.txt. Existing logins and passwords were preserved.");
    }
}
