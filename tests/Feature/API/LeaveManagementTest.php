<?php

namespace Tests\Feature\API;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workplace;
use Carbon\Carbon;
use Tests\TenantTestCase;

class LeaveManagementTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Tenant\LeaveManagementSeeder::class);
        $this->seed(\Database\Seeders\Tenant\LeavePermissionSeeder::class);
    }

    private function getDomainUrl(): string
    {
        return "http://{$this->tenant->domains->first()->domain}";
    }

    public function test_get_balance()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson("{$this->getDomainUrl()}/api/v1/leave/balance");

        $response->assertStatus(200)
            ->assertJsonStructure(['total_entitlement', 'carried_over', 'taken']);
    }

    public function test_create_request_success()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $type = LeaveType::where('name', 'Concediu Odihnă')->first();

        $response = $this->actingAs($user)->postJson("{$this->getDomainUrl()}/api/v1/leave/request", [
            'leave_type_id' => $type->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $employee->id,
            'status' => 'PENDING',
        ]);
    }

    public function test_create_request_overlap()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $type = LeaveType::first();

        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(7),
            'total_days' => 3,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($user)->postJson("{$this->getDomainUrl()}/api/v1/leave/request", [
            'leave_type_id' => $type->id,
            'start_date' => now()->addDays(6)->toDateString(),
            'end_date' => now()->addDays(8)->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['dates']);
    }

    public function test_sick_leave_requires_medical_code()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $type = LeaveType::where('name', 'Concediu Medical')->first();

        $response = $this->actingAs($user)->postJson("{$this->getDomainUrl()}/api/v1/leave/request", [
            'leave_type_id' => $type->id,
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            // medical_code missing
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['medical_code']);
    }

    public function test_manager_approval()
    {
        $managerUser = User::factory()->create();
        $managerEmployee = Employee::factory()->create(['user_id' => $managerUser->id]);
        $managerUser->assignRole('manager');

        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $type = LeaveType::first();

        $request = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(12),
            'total_days' => 3,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($managerUser)->postJson("{$this->getDomainUrl()}/api/v1/leave/approve", [
            'request_id' => $request->id,
            'status' => 'APPROVED',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('APPROVED', $request->fresh()->status);
        $this->assertEquals($managerEmployee->id, $request->fresh()->approver_id);

        // Verify balance updated via Observer
        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employee->id,
            'taken' => 3
        ]);
    }

    public function test_approval_unauthorized()
    {
        $user = User::factory()->create(); // No role
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $request = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => LeaveType::first()->id,
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(12),
            'total_days' => 3,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($user)->postJson("{$this->getDomainUrl()}/api/v1/leave/approve", [
            'request_id' => $request->id,
            'status' => 'APPROVED',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_direct_approval_updates_balance()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $type = LeaveType::where('name', 'Concediu Odihnă')->first(); // Ensure it affects quota

        $request = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'start_date' => now()->addDays(20),
            'end_date' => now()->addDays(22),
            'total_days' => 3,
            'status' => 'PENDING',
        ]);

        // Simulate Admin Panel update (Eloquent update)
        $request->status = 'APPROVED';
        $request->save();

        // Check balance
        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employee->id,
            'taken' => 3
        ]);
    }

    public function test_clock_in_blocked_on_leave()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $type = LeaveType::first();
        $workplace = Workplace::create(['name' => 'Office', 'status' => 'active']);
        $employee->workplace_id = $workplace->id;
        $employee->save();

        // Create approved leave for today
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'total_days' => 3,
            'status' => 'APPROVED',
        ]);

        $response = $this->actingAs($user)->postJson("{$this->getDomainUrl()}/api/presence/check-in", [
            'workplace_id' => $workplace->id,
            'method' => 'manual',
            'latitude' => 0,
            'longitude' => 0,
        ]);

        $response->assertStatus(422)
             ->assertJsonValidationErrors(['status']);
    }

    public function test_export_payroll()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $user->assignRole('hr');

        $response = $this->actingAs($user)->get("{$this->getDomainUrl()}/api/v1/admin/export/payroll");

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
    }

    public function test_export_payroll_unauthorized()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("{$this->getDomainUrl()}/api/v1/admin/export/payroll");

        $response->assertStatus(403);
    }
}
