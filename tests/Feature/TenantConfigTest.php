<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenantTestCase;

class TenantConfigTest extends TenantTestCase
{
    use RefreshDatabase;

    public function test_can_retrieve_tenant_config()
    {
        $this->tenant->update([
            'company_name' => 'Test Company',
            'code_length' => 3
        ]);

        $domain = $this->tenant->domains->first()->domain;
        $response = $this->getJson("http://{$domain}/api/config");

        $response->assertStatus(200)
            ->assertJson([
                'company_name' => 'Test Company',
                'code_length' => 3
            ]);
    }
}
