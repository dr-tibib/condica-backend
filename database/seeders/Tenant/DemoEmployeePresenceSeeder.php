<?php

namespace Database\Seeders\Tenant;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PresenceEvent;
use App\Models\PublicHoliday;
use App\Models\User;
use App\Models\Workplace;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoEmployeePresenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure a Workplace exists
        $workplace = Workplace::firstOrCreate(
            ['name' => 'Main Office'],
            [
                'city' => 'Bucharest',
                'county' => 'Bucharest',
                'street_address' => 'Demo Street 123',
                'country' => 'Romania',
                'latitude' => 44.4268,
                'longitude' => 26.1025,
                'radius' => 100,
                'timezone' => 'Europe/Bucharest',
                'is_active' => true,
            ]
        );

        // 2. Get Leave Type (Concediu Odihna)
        $leaveType = LeaveType::where('name', 'like', '%Concediu%Odihn%')
            ->orWhere('id', 1)
            ->first();

        if (! $leaveType) {
            $this->command->error("Leave Type 'Concediu Odihna' not found. Please run LeaveManagementSeeder first.");
            return;
        }

        // 3. Create 20 Employees
        $password = Hash::make('qazwsx');
        $startDate = now()->subMonths(3)->startOfDay();
        $endDate = now()->endOfDay();

        // Pre-fetch holidays to avoid queries in loop
        $holidays = PublicHoliday::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->pluck('date')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->toArray();

        for ($i = 0; $i < 20; $i++) {
            $user = User::factory()->create([
                'password' => $password,
                'default_workplace_id' => $workplace->id,
            ]);

            // Create Leave Balances for current and previous year (to cover 3 months ago)
            $years = array_unique([$startDate->year, $endDate->year]);
            foreach ($years as $year) {
                LeaveBalance::firstOrCreate(
                    ['user_id' => $user->id, 'year' => $year],
                    [
                        'total_entitlement' => 50, // Generous amount for demo
                        'carried_over' => 0,
                        'taken' => 0,
                    ]
                );
            }

            // 4. Generate Presence/Leaves
            $currentDate = $startDate->copy();

            while ($currentDate <= $endDate) {
                // Skip future days if any (though loop checks endDate)
                if ($currentDate->isFuture()) {
                    break;
                }

                // Skip Weekends
                if ($currentDate->isWeekend()) {
                    $currentDate->addDay();
                    continue;
                }

                // Skip Public Holidays
                if (in_array($currentDate->format('Y-m-d'), $holidays)) {
                    $currentDate->addDay();
                    continue;
                }

                // Random decision: 10% Leave, 90% Presence
                $rand = rand(1, 100);

                if ($rand <= 10) {
                    // Create Leave Request
                    LeaveRequest::create([
                        'user_id' => $user->id,
                        'leave_type_id' => $leaveType->id,
                        'start_date' => $currentDate->format('Y-m-d'),
                        'end_date' => $currentDate->format('Y-m-d'),
                        'total_days' => 1,
                        'status' => 'APPROVED',
                    ]);
                } else {
                    // Create Presence
                    // Determine Hours
                    $scenario = rand(1, 100);
                    $durationMinutes = 480; // 8 hours default

                    if ($scenario <= 60) {
                        // Standard: ~8h (plus minus random minutes)
                        $durationMinutes = 480 + rand(-10, 10);
                    } elseif ($scenario <= 80) {
                        // Overtime: 9-10h
                        $durationMinutes = rand(540, 600);
                    } else {
                        // Undertime: 4-7h
                        $durationMinutes = rand(240, 420);
                    }

                    // Start Time: 08:00 - 10:00
                    $startHour = rand(8, 10);
                    $startMinute = rand(0, 59);
                    $checkInTime = $currentDate->copy()->setTime($startHour, $startMinute);
                    $checkOutTime = $checkInTime->copy()->addMinutes($durationMinutes);

                    // Jitter location
                    $lat = $workplace->latitude + (rand(-100, 100) / 100000);
                    $lng = $workplace->longitude + (rand(-100, 100) / 100000);

                    $checkIn = PresenceEvent::create([
                        'user_id' => $user->id,
                        'workplace_id' => $workplace->id,
                        'event_type' => 'check_in',
                        'event_time' => $checkInTime,
                        'method' => 'manual',
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'accuracy' => rand(10, 50),
                        'device_info' => ['agent' => 'Seeder'],
                    ]);

                    $checkOut = PresenceEvent::create([
                        'user_id' => $user->id,
                        'workplace_id' => $workplace->id,
                        'event_type' => 'check_out',
                        'event_time' => $checkOutTime,
                        'method' => 'manual',
                        'latitude' => $lat, // Assuming didn't move much
                        'longitude' => $lng,
                        'accuracy' => rand(10, 50),
                        'device_info' => ['agent' => 'Seeder'],
                        'pair_event_id' => $checkIn->id,
                    ]);

                    // Link checkin to checkout
                    $checkIn->update(['pair_event_id' => $checkOut->id]);
                }

                $currentDate->addDay();
            }
        }
    }
}
