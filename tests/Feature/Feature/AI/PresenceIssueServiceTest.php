<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PresenceEvent;
use App\Services\Dashboard\PresenceIssueService;
use Carbon\Carbon;

it('detects an unclosed check-in from yesterday', function () {
    $employee = Employee::factory()->create();

    PresenceEvent::create([
        'employee_id' => $employee->id,
        'type' => 'presence',
        'start_at' => Carbon::yesterday()->setTime(8, 0),
        'end_at' => null,
        'start_method' => 'manual',
    ]);

    $service = new PresenceIssueService;
    $issues = $service->getIssues();

    expect($issues['unclosed_checkins']->count())->toBe(1);
    expect($issues['total_issues'])->toBe(1);
});

it('does not flag an active check-in from today as an issue', function () {
    $employee = Employee::factory()->create();

    PresenceEvent::create([
        'employee_id' => $employee->id,
        'type' => 'presence',
        'start_at' => Carbon::today()->setTime(9, 0),
        'end_at' => null,
        'start_method' => 'manual',
    ]);

    $service = new PresenceIssueService;
    $issues = $service->getIssues();

    expect($issues['unclosed_checkins']->count())->toBe(0);
});

it('detects an employee on leave who also has a presence event today', function () {
    $employee = Employee::factory()->create();

    $leaveType = LeaveType::create([
        'name' => 'Concediu odihnă',
        'is_paid' => true,
        'requires_document' => false,
        'affects_annual_quota' => true,
        'medical_code_required' => false,
    ]);

    LeaveRequest::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => Carbon::today()->subDay(),
        'end_date' => Carbon::today()->addDay(),
        'total_days' => 3,
        'status' => 'APPROVED',
    ]);

    PresenceEvent::create([
        'employee_id' => $employee->id,
        'type' => 'presence',
        'start_at' => Carbon::today()->setTime(9, 0),
        'end_at' => Carbon::today()->setTime(10, 0),
        'start_method' => 'manual',
    ]);

    $service = new PresenceIssueService;
    $issues = $service->getIssues();

    expect($issues['leave_while_worked']->count())->toBe(1);
    expect($issues['leave_while_worked']->first()->id)->toBe($employee->id);
});

it('detects a session longer than 14 hours in the current month', function () {
    $employee = Employee::factory()->create();

    PresenceEvent::create([
        'employee_id' => $employee->id,
        'type' => 'presence',
        'start_at' => Carbon::now()->startOfMonth()->addDays(2)->setTime(6, 0),
        'end_at' => Carbon::now()->startOfMonth()->addDays(2)->setTime(22, 0),
        'start_method' => 'manual',
    ]);

    $service = new PresenceIssueService;
    $issues = $service->getIssues();

    expect($issues['long_sessions']->count())->toBe(1);
    expect($issues['total_issues'])->toBe(1);
});

it('does not flag a normal 8-hour session as a long session', function () {
    $employee = Employee::factory()->create();

    PresenceEvent::create([
        'employee_id' => $employee->id,
        'type' => 'presence',
        'start_at' => Carbon::now()->startOfMonth()->addDays(2)->setTime(8, 0),
        'end_at' => Carbon::now()->startOfMonth()->addDays(2)->setTime(16, 30),
        'start_method' => 'manual',
    ]);

    $service = new PresenceIssueService;
    $issues = $service->getIssues();

    expect($issues['long_sessions']->count())->toBe(0);
});

it('returns zero total when data is clean', function () {
    $service = new PresenceIssueService;
    $issues = $service->getIssues();

    expect($issues['unclosed_checkins']->count())->toBe(0);
    expect($issues['leave_while_worked']->count())->toBe(0);
    expect($issues['long_sessions']->count())->toBe(0);
    expect($issues['total_issues'])->toBe(0);
});

it('provides correct unclosed summary for AI tool', function () {
    $employee = Employee::factory()->create();

    PresenceEvent::create([
        'employee_id' => $employee->id,
        'type' => 'presence',
        'start_at' => Carbon::yesterday()->setTime(8, 0),
        'end_at' => null,
        'start_method' => 'manual',
    ]);

    $service = new PresenceIssueService;
    $summary = $service->getUnclosedSummary();

    expect($summary['count'])->toBe(1);
    expect($summary['employees'][0]['name'])->toBe($employee->name);
    expect($summary['employees'][0]['date'])->toBe(Carbon::yesterday()->format('d.m.Y'));
});
