<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\PresenceEvent;
use Carbon\Carbon;
use Tests\TenantTestCase;

class TeamCommandCenterExportTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['backpack.base.guard' => 'web']);
    }

    public function test_export_attendance_sheet_excel()
    {
        $admin = User::factory()->create();
        $domain = $this->tenant->domains->first()->domain;
        $url = 'http://' . $domain . '/admin/team-command-center/export?format=excel';

        $response = $this->actingAs($admin)->get($url);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_export_attendance_sheet_pdf()
    {
        $admin = User::factory()->create();
        $domain = $this->tenant->domains->first()->domain;
        $url = 'http://' . $domain . '/admin/team-command-center/export?format=pdf';

        $response = $this->actingAs($admin)->get($url);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
