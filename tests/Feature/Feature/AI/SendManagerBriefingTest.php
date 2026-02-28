<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Notifications\ManagerDailyBriefing;
use Illuminate\Support\Facades\Notification;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;

it('dispatches manager briefing notification for each manager', function () {
    Notification::fake();

    Prism::fake([
        TextResponseFake::make()->withText('Rezumat zilnic echipă: totul decurge normal.'),
    ]);

    $manager = Employee::factory()->create(['email' => 'manager@example.com']);
    Employee::factory()->create(['manager_id' => $manager->id]);

    $this->artisan('condica:send-briefing', ['tenant_id' => $this->tenant->id])
        ->assertSuccessful();

    Notification::assertSentTo($manager, ManagerDailyBriefing::class);
});

it('skips notification when manager has no subordinates', function () {
    Notification::fake();

    Prism::fake([
        TextResponseFake::make()->withText('Rezumat zilnic echipă: totul decurge normal.'),
    ]);

    $nonManager = Employee::factory()->create(['email' => 'employee@example.com']);

    $this->artisan('condica:send-briefing', ['tenant_id' => $this->tenant->id])
        ->assertSuccessful();

    Notification::assertNotSentTo($nonManager, ManagerDailyBriefing::class);
});

it('uses whatsapp channel when manager has whatsapp number', function () {
    Notification::fake();

    Prism::fake([
        TextResponseFake::make()->withText('Rezumat zilnic echipă: totul decurge normal.'),
    ]);

    $manager = Employee::factory()->create([
        'email' => 'manager@example.com',
        'whatsapp_number' => '+40721000001',
    ]);
    Employee::factory()->create(['manager_id' => $manager->id]);

    $this->artisan('condica:send-briefing', ['tenant_id' => $this->tenant->id])
        ->assertSuccessful();

    Notification::assertSentTo($manager, function (ManagerDailyBriefing $notification) {
        return in_array(\App\Channels\WhatsAppChannel::class, $notification->via($notification->teamData['manager']));
    });
});
