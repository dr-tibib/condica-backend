<?php

namespace Database\Seeders\Tenant;

use App\Models\Employee;
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

        // 2. Get Leave Type
        $leaveType = LeaveType::where('name', 'like', '%Concediu%Odihn%')
            ->orWhere('id', 1)
            ->first();

        // 3. Create 20 Employees
        $password = Hash::make('qazwsx');
        $startDate = now()->subMonths(3)->startOfDay();
        $endDate = now()->endOfDay();

        $holidays = PublicHoliday::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->pluck('date')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->toArray();

        for ($i = 0; $i < 20; $i++) {
            $user = User::factory()->create(['password' => $password]);
            $employee = Employee::factory()->create([
                'user_id' => $user->id,
                'workplace_id' => $workplace->id
            ]);

            $years = array_unique([$startDate->year, $endDate->year]);
            foreach ($years as $year) {
                LeaveBalance::firstOrCreate(
                    ['employee_id' => $employee->id, 'year' => $year],
                    ['total_entitlement' => 50, 'carried_over' => 0, 'taken' => 0]
                );
            }

            $currentDate = $startDate->copy();

            while ($currentDate <= $endDate) {
                if ($currentDate->isFuture()) break;
                if ($currentDate->isWeekend()) {
                    $currentDate->addDay();
                    continue;
                }
                if (in_array($currentDate->format('Y-m-d'), $holidays)) {
                    $currentDate->addDay();
                    continue;
                }

                $rand = rand(1, 100);

                if ($rand <= 10 && $leaveType) {
                    LeaveRequest::create([
                        'employee_id' => $employee->id,
                        'leave_type_id' => $leaveType->id,
                        'start_date' => $currentDate->format('Y-m-d'),
                        'end_date' => $currentDate->format('Y-m-d'),
                        'total_days' => 1,
                        'status' => 'APPROVED',
                    ]);
                } else {
                    $scenario = rand(1, 100);
                    $durationMinutes = 480;
                    if ($scenario <= 60) $durationMinutes = 480 + rand(-10, 10);
                    elseif ($scenario <= 80) $durationMinutes = rand(540, 600);
                    else $durationMinutes = rand(240, 420);

                    $startHour = rand(8, 10);
                    $startMinute = rand(0, 59);
                    $checkInTime = $currentDate->copy()->setTime($startHour, $startMinute);
                    $checkOutTime = $checkInTime->copy()->addMinutes($durationMinutes);

                    $lat = $workplace->latitude + (rand(-100, 100) / 100000);
                    $lng = $workplace->longitude + (rand(-100, 100) / 100000);

                    PresenceEvent::create([
                        'employee_id' => $employee->id,
                        'workplace_id' => $workplace->id,
                        'type' => 'presence',
                        'start_at' => $checkInTime,
                        'end_at' => $checkOutTime,
                        'start_method' => 'manual',
                        'end_method' => 'manual',
                        'start_latitude' => $lat,
                        'start_longitude' => $lng,
                        'end_latitude' => $lat,
                        'end_longitude' => $lng,
                        'start_accuracy' => rand(10, 50),
                        'end_accuracy' => rand(10, 50),
                        'start_device_info' => ['agent' => 'Seeder'],
                        'end_device_info' => ['agent' => 'Seeder'],
                    ]);
                }
                $currentDate->addDay();
            }
        }
    }
}
