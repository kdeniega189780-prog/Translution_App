<?php

namespace Database\Seeders;

use App\Application\BookingWorkflow;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkshopDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo data is only allowed in local or testing environments.');
        }

        $actor = User::where('role', 'admin')->where('active', true)->firstOrFail();
        $mechanic = User::where('role', 'mechanic')->where('active', true)->firstOrFail();
        $services = Service::where('active', true)->orderBy('id')->get();
        if ($services->isEmpty()) {
            throw new \RuntimeException('Add active services before loading sample workshop records.');
        }

        $today = today();
        $originalNow = Carbon::getTestNow();
        $added = ['customers' => 0, 'paid' => 0, 'active' => 0];
        try {
            DB::transaction(function () use ($actor, $mechanic, $services, $today, &$added) {
                // Lock an existing row so simultaneous runs cannot duplicate the sample pack.
                User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
                $names = ['Miguel Santos', 'Isabella Reyes', 'Gabriel Cruz', 'Sofia Mendoza',
                    'Rafael Garcia', 'Camila Torres', 'Daniel Lim', 'Lucia Tan',
                    'Adrian Ramos', 'Elena Castillo', 'Paolo Navarro', 'Nina Flores',
                    'Carlo Bautista', 'Mia Villanueva', 'Luis Mercado', 'Anna Rosales',
                    'Enzo Santiago', 'Clara Dizon', 'Marco Valdez', 'Julia Aquino',
                    'Noel Salazar', 'Bea Manalo', 'Anton Rivera', 'Lara Fernandez',
                    'Joaquin Alvarez', 'Patricia Soriano', 'Emilio Velasco', 'Diana Pascual',
                    'Vincent Tolentino', 'Mariel Cabrera', 'Francis Aguilar', 'Celina Padilla',
                    'Oscar Pineda', 'Theresa Gonzales', 'Felix Robles', 'Bianca Estrada',
                    'Alfredo De Leon', 'Mara Alonzo', 'Diego Fuentes', 'Rina Cordero',
                    'Lorenzo Del Rosario', 'Alyssa Ocampo', 'Benito Mendoza', 'Erika Fajardo',
                    'Ramon Delos Santos', 'Celine Espinosa', 'Tomas Villanueva', 'Hannah Sison'];
                $cars = [['Toyota', 'Vios'], ['Honda', 'City'], ['Mitsubishi', 'Mirage'],
                    ['Ford', 'Everest'], ['Suzuki', 'Ertiga'], ['Nissan', 'Navara'],
                    ['Hyundai', 'Accent'], ['Kia', 'Soluto']];
                $flow = app(BookingWorkflow::class);

                foreach ($names as $index => $name) {
                    $email = 'workshop-demo-'.($index + 1).'@transolutions.test';
                    // An existing or archived sample customer marks a completed import.
                    if (Customer::withTrashed()->where('email', $email)->exists()) {
                        continue;
                    }
                    Carbon::setTestNow($today->copy()->subDays($index < 24 ? 60 : 120));
                    $customer = Customer::create([
                        'name' => $name.' (Demo)', 'email' => $email, 'mobile' => 'Not provided',
                    ]);
                    [$make, $model] = $cars[$index % count($cars)];
                    $vehicle = Vehicle::create([
                        'customer_id' => $customer->id, 'plate_number' => 'DEMO '.(1001 + $index),
                        'make' => $make, 'model' => $model, 'year' => 2017 + $index % 9,
                        'transmission' => 'Automatic',
                    ]);
                    $added['customers']++;

                    // Preserve the first pack; additional customers have three visits across recent months.
                    $days = $index < 24
                        ? [45 - $index, 1 + $index % 12]
                        : [1 + intdiv($index - 24, 3), 20 + intdiv($index - 24, 2), 45 + $index - 24];
                    foreach ($days as $visit => $daysAgo) {
                        $at = $this->availableSlot($today->copy()->subDays($daysAgo), -1);
                        $service = $services[($index + $visit * 3) % $services->count()];
                        $this->addBooking($flow, $actor, $mechanic, $vehicle, $service, $at, 'released', $index < 24 ? $index : $index + $visit);
                        $added['paid']++;
                    }

                    if ($index < 8 || ($index >= 24 && $index < 32)) {
                        $stage = ['pending', 'scheduled', 'processing', 'completed'][$index % 4];
                        $at = $this->availableSlot($today->copy()->addDays(in_array($stage, ['pending', 'scheduled']) ? 1 : -1), in_array($stage, ['pending', 'scheduled']) ? 1 : -1);
                        $this->addBooking($flow, $actor, $mechanic, $vehicle, $services[($index + 2) % $services->count()], $at, $stage, $index);
                        $added['active']++;
                    }
                }
            });
        } finally {
            Carbon::setTestNow($originalNow);
        }

        $this->command?->info("Added {$added['customers']} sample customers and vehicles, {$added['paid']} paid visits and {$added['active']} active bookings. Existing sample customers were skipped.");
    }

    private function availableSlot(Carbon $day, int $direction): Carbon
    {
        for ($attempt = 0; $attempt < 366; $attempt++, $day->addDays($direction)) {
            $booked = Booking::whereDate('scheduled_at', $day)->pluck('scheduled_at');
            if ($booked->count() >= config('workshop.daily_capacity')) {
                continue;
            }
            foreach ([8, 10, 13, 15] as $hour) {
                $at = $day->copy()->setTime($hour, 0);
                if (! $booked->contains(fn ($value) => Carbon::parse($value)->equalTo($at))) {
                    return $at;
                }
            }
        }
        throw new \RuntimeException('No available sample booking slot within one year.');
    }

    private function addBooking(BookingWorkflow $flow, User $actor, User $mechanic, Vehicle $vehicle, Service $service, Carbon $at, string $stage, int $index): void
    {
        $createdAt = $at->copy()->subDays(2);
        Carbon::setTestNow($createdAt);
        $booking = $flow->create($actor, [
            'vehicle_id' => $vehicle->id, 'service_id' => $service->id, 'preferred_at' => $at,
            'source' => 'walk-in', 'problem' => 'Sample visit for '.$service->name.'. Inspect vehicle and advise on service.',
            'notes' => 'DEMO DATA: fictional workshop record for exploring the application.',
        ]);
        if ($stage === 'pending') {
            return;
        }
        $flow->advance($actor, $booking, ['expected_status' => 'pending', 'scheduled_at' => $at]);
        if ($stage === 'scheduled') {
            return;
        }
        Carbon::setTestNow($at);
        $flow->advance($actor, $booking, ['expected_status' => 'scheduled', 'assigned_staff_id' => $mechanic->id]);
        $flow->diagnose($mechanic, $booking, ['diagnosis' => 'Sample inspection: '.$service->name.' recommended. Verify operation and road-test after service.']);
        $flow->consent($actor, $booking->fresh(), ['consent' => 'agreed', 'consent_note' => 'Demo customer approval recorded at the workshop.']);
        if ($stage === 'processing') {
            return;
        }
        Carbon::setTestNow($at->copy()->addHour());
        $flow->advance($mechanic, $booking, ['expected_status' => 'processing']);
        if ($stage === 'released') {
            Carbon::setTestNow($at->copy()->addHours(2));
            $flow->pay($actor, $booking->fresh()->invoice, ['Cash', 'GCash', 'Bank Transfer', 'PayMaya'][$index % 4]);
        }
    }
}
