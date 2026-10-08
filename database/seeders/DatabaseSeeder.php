<?php

namespace Database\Seeders;

use App\Application\BookingWorkflow;
use App\Application\InventoryWorkflow;
use App\Models\Customer;
use App\Models\Part;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo data is only allowed in local or testing environments.');
        }
        if (User::exists()) {
            throw new \RuntimeException('Seed only a fresh database; existing users were found.');
        }
        $credentials = [];
        DB::transaction(function () use (&$credentials) {
            foreach (['admin' => 'Alex Santos', 'secretary' => 'Sam Rivera', 'mechanic' => 'Marco Reyes', 'purchaser' => 'Jamie Cruz', 'customer' => 'Andrea Dela Cruz'] as $role => $name) {
                $password = Str::password(18);
                $email = $role.'@transolutions.test';
                $users[$role] = User::create(['name' => $name, 'email' => $email, 'mobile' => '09171234567', 'role' => $role, 'password' => $password, 'must_change_password' => true]);
                $credentials[] = $role.' | '.$email.' | '.$password;
            }
            $customer = Customer::create(['user_id' => $users['customer']->id, 'name' => $users['customer']->name, 'email' => $users['customer']->email, 'mobile' => $users['customer']->mobile]);
            foreach (['Automatic Transmission Overhaul' => 18000, 'Transmission Rebuild' => 22000, 'Engine Troubleshooting' => 1500, 'Oil Change' => 600, 'Aircon Repair' => 2500, 'Brake Repair' => 1200, 'Tire Rotation' => 400, 'Inspection' => 500, 'Detailing' => 2500] as $name => $price) {
                Service::create(compact('name', 'price'));
            }
            $names = ['ATF Fluid (1 L)', 'Transmission Filter', 'Overhaul Gasket Kit', 'Clutch Pack', 'Valve Body Solenoid', 'Torque Converter', 'Oil Seal', 'Engine Oil (1 L)', 'Oil Filter', 'Brake Pads', 'Brake Fluid', 'Aircon Refrigerant', 'Drive Belt', 'Spark Plug', 'Transmission Mount'];
            $items = [];
            foreach ($names as $n => $name) {
                $p = Part::create(['name' => $name, 'category' => $n < 7 ? 'Transmission' : 'Maintenance', 'quantity' => 0, 'unit_cost' => 150 + ($n * 125), 'reorder_level' => 5, 'supplier' => config('workshop.supplier')]);
                $items[] = ['part_id' => $p->id, 'quantity' => $n % 4 === 0 ? 4 : 20, 'unit_cost' => $p->unit_cost];
            }
            $inventory = app(InventoryWorkflow::class);
            $order = $inventory->createOrder($users['purchaser'], ['supplier' => config('workshop.supplier'), 'items' => $items]);
            $inventory->advance($users['purchaser'], $order, 'draft');
            $inventory->advance($users['purchaser'], $order->fresh(), 'ordered');
            $inventory->createOrder($users['purchaser'], ['supplier' => config('workshop.supplier'), 'items' => array_slice($items, 0, 3)]);
            $flow = app(BookingWorkflow::class);
            foreach (['Toyota', 'Honda', 'Mitsubishi', 'Ford', 'Suzuki'] as $n => $make) {
                $v = Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'DAV '.(2101 + $n), 'make' => $make, 'model' => ['Vios', 'City', 'Montero', 'Everest', 'Ertiga'][$n], 'year' => 2019 + $n, 'transmission' => 'Automatic']);
                $b = $flow->create($users['secretary'], ['vehicle_id' => $v->id, 'service_id' => 1 + $n, 'preferred_at' => today()->setTime(8 + $n, 0), 'problem' => ['Transmission slips when shifting.', 'Grinding sound during gear changes.', 'Engine is hard to start.', 'Due for an oil change.', 'Air conditioning does not cool.'][$n], 'source' => 'walk-in']);
                if ($n >= 1) {
                    $flow->advance($users['secretary'], $b, ['expected_status' => 'pending', 'scheduled_at' => today()->setTime(8 + $n, 0)]);
                }
                if ($n >= 2) {
                    $flow->advance($users['secretary'], $b, ['expected_status' => 'scheduled', 'assigned_staff_id' => $users['mechanic']->id]);
                    $flow->diagnose($users['mechanic'], $b, ['diagnosis' => 'Inspected vehicle; replace worn consumables and test before release.']);
                    $flow->consent($users['customer'], $b, ['consent' => 'agreed']);
                    $flow->usePart($users['mechanic'], $b, ['part_id' => 2, 'quantity' => 1]);
                }
                if ($n >= 3) {
                    $flow->advance($users['mechanic'], $b, ['expected_status' => 'processing']);
                }
                if ($n === 4) {
                    $flow->pay($users['secretary'], $b->fresh()->invoice, 'GCash');
                }
            }
        });
        if (! app()->environment('testing')) {
            Storage::disk('local')->put('demo-accounts.txt', "LOCAL DEMO ONLY — change all passwords on first login. Never commit or publish this file.\n".implode("\n", $credentials)."\n");
            $this->command?->info('Demo credentials: storage/app/private/demo-accounts.txt (all accounts require a password change).');
        }
    }
}
