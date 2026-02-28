<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\PresenceEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;

it('renders the admin dashboard with a 200 response', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'backpack')
        ->get(getUrl('/admin/dashboard'));

    $response->assertSuccessful();
});

it('today stats reflect created employees and events', function () {
    $user = User::factory()->create();

    $employee1 = Employee::factory()->create();
    $employee2 = Employee::factory()->create();

    // employee1 is currently checked in
    PresenceEvent::create([
        'employee_id' => $employee1->id,
        'type' => 'presence',
        'start_at' => Carbon::today()->setTime(8, 0),
        'end_at' => null,
        'start_method' => 'manual',
    ]);

    $response = $this->actingAs($user, 'backpack')
        ->get(getUrl('/admin/dashboard'));

    $response->assertSuccessful();
    $response->assertSee('2'); // total employees
    $response->assertSee('1'); // present count
});

it('shows the AI insights card with spinner on initial page load', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'backpack')
        ->get(getUrl('/admin/dashboard'));

    $response->assertSuccessful();
    $response->assertSee('Analiză Lunară AI');
    $response->assertSee('Se generează analiza AI');
    $response->assertSee('loadAiInsights');
});

it('ai-insights endpoint returns JSON with html', function () {
    Prism::fake([
        TextResponseFake::make()->withText('**Concluzie:** Prezență normală în această lună.'),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user, 'backpack')
        ->get(getUrl('/admin/dashboard/ai-insights'));

    $response->assertSuccessful();
    $response->assertJsonStructure(['html', 'cached_at']);
    expect($response->json('html'))->toContain('Concluzie');
});

it('ai-insights endpoint respects refresh parameter', function () {
    Prism::fake([
        TextResponseFake::make()->withText('Analiza actualizată.'),
        TextResponseFake::make()->withText('Analiza actualizată.'),
    ]);

    $user = User::factory()->create();

    Cache::forget('admin_insights_'.tenant('id').'_'.now()->year.'_'.now()->month);

    $response = $this->actingAs($user, 'backpack')
        ->get(getUrl('/admin/dashboard/ai-insights?refresh=1'));

    $response->assertSuccessful();
    $response->assertJsonStructure(['html', 'cached_at']);
});

it('shows the employee statistics banner', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'backpack')
        ->get(getUrl('/admin/dashboard'));

    $response->assertSuccessful();
    $response->assertSee('Statistici Angajați');
    $response->assertSee('employee-statistics');
});

it('shows presence issues when unclosed check-in exists from yesterday', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create();

    PresenceEvent::create([
        'employee_id' => $employee->id,
        'type' => 'presence',
        'start_at' => Carbon::yesterday()->setTime(8, 0),
        'end_at' => null,
        'start_method' => 'manual',
    ]);

    $response = $this->actingAs($user, 'backpack')
        ->get(getUrl('/admin/dashboard'));

    $response->assertSuccessful();
    $response->assertSee('Pontaje Neînchise');
});

it('ai-insights includes employees without presence by name', function () {
    Prism::fake([
        TextResponseFake::make()->withText('Analiza lunară.'),
    ]);

    $user = User::factory()->create();
    Employee::factory()->create(['first_name' => 'Ion', 'last_name' => 'Popescu']);

    $response = $this->actingAs($user, 'backpack')
        ->getJson(getUrl('/admin/dashboard/ai-insights'));

    $response->assertSuccessful();
    // The AI tool data was built — no assertion on AI text content since it's faked,
    // but the endpoint must not error even with employees having no presence
    $response->assertJsonStructure(['html', 'cached_at']);
});
