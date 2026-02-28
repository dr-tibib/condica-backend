<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Notifications\ManagerDailyBriefing;
use App\Services\AIService;
use App\Services\ManagerBriefingService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendManagerBriefing extends Command
{
    protected $signature = 'condica:send-briefing {tenant_id? : ID of a specific tenant to process}';

    protected $description = 'Send daily attendance briefing to all managers via Email and WhatsApp';

    public function __construct(
        private readonly ManagerBriefingService $briefingService,
        private readonly AIService $aiService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantId = $this->argument('tenant_id');

        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found.');

            return 1;
        }

        foreach ($tenants as $tenant) {
            $this->processForTenant($tenant);
        }

        $this->info('Manager briefings dispatched successfully.');

        return 0;
    }

    private function processForTenant(Tenant $tenant): void
    {
        $this->info("Processing tenant: {$tenant->id}");

        tenancy()->initialize($tenant);

        try {
            $managers = $this->briefingService->getAllManagers();
            $today = Carbon::today();

            foreach ($managers as $manager) {
                $this->sendBriefingToManager($manager, $today);
            }

            $this->info("  → {$managers->count()} manager(s) notified.");
        } finally {
            tenancy()->end();
        }
    }

    private function sendBriefingToManager(\App\Models\Employee $manager, Carbon $today): void
    {
        $teamData = $this->briefingService->gatherTeamData($manager, $today);

        $briefingText = $this->aiService->generateBriefing(
            $teamData,
            $manager->name,
            $today->format('d.m.Y')
        );

        $manager->notify(new ManagerDailyBriefing($teamData, $briefingText));

        $this->line("    → Briefing queued for: {$manager->name}");
    }
}
