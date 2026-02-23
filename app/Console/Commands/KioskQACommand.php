<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PresenceEvent;
use App\Models\Workplace;
use Carbon\Carbon;
use Illuminate\Console\Command;

class KioskQACommand extends Command
{
    protected $signature = 'kiosk:qa {employee_code} {scenario} {tenant_id}';

    protected $description = 'Setup specific scenarios for Kiosk QA testing';

    public function handle(): int
    {
        $code = $this->argument('employee_code');
        $scenario = $this->argument('scenario');
        $tenantId = $this->argument('tenant_id');

        $tenant = \App\Models\Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant {$tenantId} not found.");
            return 1;
        }

        tenancy()->initialize($tenant);

        $employee = Employee::where('workplace_enter_code', $code)->first();

        if (!$employee) {
            $this->error("Employee with code {$code} not found in tenant " . tenant('id'));
            return 1;
        }

        $this->info("Setting up scenario '{$scenario}' for {$employee->first_name} {$employee->last_name}...");

        switch ($scenario) {
            case 'reset':
                $this->resetEmployee($employee);
                break;

            case 'shift-active':
                $this->resetEmployee($employee);
                PresenceEvent::create([
                    'employee_id' => $employee->id,
                    'workplace_id' => $employee->workplace_id,
                    'type' => 'presence',
                    'start_at' => now()->setHour(8)->setMinute(0),
                    'start_method' => 'kiosk',
                ]);
                break;

            case 'shift-forgot-checkout':
                $this->resetEmployee($employee);
                PresenceEvent::create([
                    'employee_id' => $employee->id,
                    'workplace_id' => $employee->workplace_id,
                    'type' => 'presence',
                    'start_at' => now()->subDays(2)->setHour(8)->setMinute(0),
                    'start_method' => 'kiosk',
                ]);
                break;

            case 'delegation-active':
                $this->resetEmployee($employee);
                PresenceEvent::create([
                    'employee_id' => $employee->id,
                    'workplace_id' => $employee->workplace_id,
                    'type' => 'delegation',
                    'start_at' => now()->setHour(9)->setMinute(0),
                    'start_method' => 'kiosk',
                    'notes' => 'QA Delegation',
                ]);
                break;

            case 'delegation-multi':
                $this->resetEmployee($employee);
                PresenceEvent::create([
                    'employee_id' => $employee->id,
                    'workplace_id' => $employee->workplace_id,
                    'type' => 'delegation',
                    'start_at' => now()->subDays(3)->setHour(9)->setMinute(0),
                    'start_method' => 'kiosk',
                    'notes' => 'QA Multi-day Delegation',
                ]);
                break;

            case 'leave-active':
                $this->resetEmployee($employee);
                $type = LeaveType::first() ?? LeaveType::create(['name' => 'Vacation', 'code' => 'V']);
                LeaveRequest::create([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $type->id,
                    'start_date' => now()->startOfDay(),
                    'end_date' => now()->addDays(5)->endOfDay(),
                    'total_days' => 5,
                    'status' => 'APPROVED',
                ]);
                break;

            case 'threshold-low':
                $workplace = Workplace::find($employee->workplace_id);
                if ($workplace) {
                    $workplace->update(['late_start_threshold' => '00:00']);
                    $this->info("Late start threshold set to 00:00 for Workplace {$workplace->name}");
                }
                break;

            case 'threshold-reset':
                $workplace = Workplace::find($employee->workplace_id);
                if ($workplace) {
                    $workplace->update(['late_start_threshold' => '16:00']);
                    $this->info("Late start threshold reset to 16:00 for Workplace {$workplace->name}");
                }
                break;

            default:
                $this->error("Unknown scenario: {$scenario}");
                return 1;
        }

        $this->info("Scenario setup complete.");
        return 0;
    }

    private function resetEmployee(Employee $employee): void
    {
        PresenceEvent::where('employee_id', $employee->id)->delete();
        LeaveRequest::where('employee_id', $employee->id)->delete();
        $this->info("Cleared all events/leaves for employee.");
    }
}
