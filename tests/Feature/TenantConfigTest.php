<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TenantTestCase;

class TenantConfigTest extends TenantTestCase
{
    // refresh database is handled by TenantTestCase for tenant context usually,
    // but let's check TenantTestCase implementation if needed.
    // For now assuming standard behavior.

    public function test_can_retrieve_tenant_config()
    {
        // Tenant is already created and switched to in setUp() of TenantTestCase if it behaves like standard stancl/tenancy tests
        // But let's verify. Usually TenantTestCase sets up the tenant.

        // We might need to manually set attributes on the current tenant.
        $this->tenant->company_name = 'Test Company';
        $this->tenant->logo = 'tenant_logos/test_logo.png';
        $this->tenant->save();

        Storage::fake('public');
        Storage::disk('public')->put('tenant_logos/test_logo.png', 'content');

        $domain = $this->tenant->domains->first()->domain;
        $response = $this->getJson("http://{$domain}/api/config");

        $response->assertStatus(200)
            ->assertJson([
                'company_name' => 'Test Company',
                // We expect a full URL, but exact match depends on app_url env.
                // We can check if it contains the path.
            ]);

        $this->assertStringContainsString('tenant_logos/test_logo.png', $response->json('logo_url'));
    }
}
