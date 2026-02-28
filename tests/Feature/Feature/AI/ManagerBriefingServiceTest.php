<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PresenceEvent;
use App\Services\ManagerBriefingService;
use Carbon\Carbon;

it('gathers correct team data for a manager', function () {
    $manager = Employee::factory()->create();
    $presentEmployee = Employee::factory()->create(['manager_id' => $manager->id]);
    $absentEmployee = Employee::factory()->create(['manager_id' => $manager->id]);
    $leaveEmployee = Employee::factory()->create(['manager_id' => $manager->id]);
    $delegationEmployee = Employee::factory()->create(['manager_id' => $manager->id]);

    $today = Carbon::today();

    PresenceEvent::create([
        'employee_id' => $presentEmployee->id,
        'workplace_id' => $presentEmployee->workplace_id,
        'type' => 'presence',
        'start_at' => $today->copy()->setTime(8, 0),
        'start_method' => 'kiosk',
    ]);

    PresenceEvent::create([
        'employee_id' => $delegationEmployee->id,
        'workplace_id' => $delegationEmployee->workplace_id,
        'type' => 'delegation',
        'start_at' => $today->copy()->setTime(8, 0),
        'start_method' => 'kiosk',
    ]);

    $leaveType = LeaveType::create([
        'name' => 'Concediu odihnă',
        'is_paid' => true,
        'requires_document' => false,
        'affects_annual_quota' => true,
        'medical_code_required' => false,
    ]);

    LeaveRequest::create([
        'employee_id' => $leaveEmployee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => $today->copy()->subDay(),
        'end_date' => $today->copy()->addDay(),
        'total_days' => 3,
        'status' => 'APPROVED',
    ]);

    $service = new ManagerBriefingService;
    $result = $service->gatherTeamData($manager, $today);

    expect($result['present'])->toContain($presentEmployee->name);
    expect($result['absent'])->toContain($absentEmployee->name);
    expect($result['on_leave'])->toContain($leaveEmployee->name);
    expect($result['on_delegation'])->toContain($delegationEmployee->name);
    expect($result['date'])->toBe($today->format('d.m.Y'));
});

it('detects unclosed presence events from yesterday', function () {
    $manager = Employee::factory()->create();
    $employee = Employee::factory()->create(['manager_id' => $manager->id]);

    $yesterday = Carbon::yesterday();

    PresenceEvent::create([
        'employee_id' => $employee->id,
        'workplace_id' => $employee->workplace_id,
        'type' => 'presence',
        'start_at' => $yesterday->copy()->setTime(8, 0),
        'start_method' => 'kiosk',
        'end_at' => null,
    ]);

    $service = new ManagerBriefingService;
    $result = $service->gatherTeamData($manager, Carbon::today());

    expect($result['unclosed_yesterday'])->toContain($employee->name);
});

it('returns all managers with subordinates', function () {
    $manager1 = Employee::factory()->create();
    $manager2 = Employee::factory()->create();
    $nonManager = Employee::factory()->create();

    Employee::factory()->create(['manager_id' => $manager1->id]);
    Employee::factory()->create(['manager_id' => $manager2->id]);

    $service = new ManagerBriefingService;
    $managers = $service->getAllManagers();

    expect($managers->pluck('id'))->toContain($manager1->id);
    expect($managers->pluck('id'))->toContain($manager2->id);
    expect($managers->pluck('id'))->not->toContain($nonManager->id);
});
