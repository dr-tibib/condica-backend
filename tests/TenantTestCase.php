<?php

namespace Tests;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

abstract class TenantTestCase extends TestCase
{
    use RefreshDatabase;

    protected $tenancy = true;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->tenancy) {
            $this->initializeTenancy();
        }
    }

    public function initializeTenancy(): void
    {
        // Create a unique tenant for this test (without events to avoid job dispatches)
        $this->tenant = Tenant::withoutEvents(function () {
            return Tenant::forceCreate([
                'id' => 'test-'.uniqid(),
            ]);
        });

        $this->tenant->domains()->create([
            'domain' => $this->tenant->id.'.localhost',
        ]);

        // Initialize tenancy
        tenancy()->initialize($this->tenant);

        // Run tenant migrations
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->tenancy && isset($this->tenant)) {
            tenancy()->end();

            // Clean up tenant
            $this->tenant->domains()->delete();
            $this->tenant->delete();
        }

        parent::tearDown();
    }
}
