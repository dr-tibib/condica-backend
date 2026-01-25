<?php

namespace Tests\Feature\Admin;

use Tests\TenantTestCase;

class AttendanceSheetViewTest extends TenantTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        config(['backpack.base.guard' => 'web']);
    }

    public function test_attendance_sheet_view_has_truncation_styles()
    {
        $daysInMonth = 31;
        $longName = 'This Is A Very Long Name That Should Be Truncated To Fit The Column';
        $longRole = 'This Is A Very Long Role Name That Should Also Be Truncated';

        $users = [
            [
                'name' => $longName,
                'role' => $longRole,
                'days' => array_fill(0, $daysInMonth, ['val' => '', 'is_weekend' => false, 'bg_color' => '']),
                'totals' => ['worked' => 160, 'co' => 0, 'cm' => 0, 'cfs' => 0, 'abs' => 0]
            ]
        ];

        $viewData = [
            'users' => $users,
            'monthLabel' => 'January 2026',
            'daysInMonth' => $daysInMonth,
            'companyName' => 'Test Company',
        ];

        $view = view('admin.reports.attendance_sheet', $viewData);
        $html = $view->render();

        // Check for table-layout: fixed
        $this->assertStringContainsString('table-layout: fixed', $html, 'Table should have fixed layout');

        // Check for truncation class definition
        $this->assertStringContainsString('.truncate', $html, 'CSS should define .truncate class');
        $this->assertStringContainsString('white-space: nowrap', $html, 'Truncate class should have white-space: nowrap');
        $this->assertStringContainsString('overflow: hidden', $html, 'Truncate class should have overflow: hidden');
        $this->assertStringContainsString('text-overflow: ellipsis', $html, 'Truncate class should have text-overflow: ellipsis');

        // Check that the class is applied to the name and role columns
        // We look for the cell containing the long name
        // The view currently has: <td class="text-left">{{ $user['name'] }}</td>
        // I expect it to become: <td class="text-left truncate"><div>{{ $user['name'] }}</div></td> or similar.

        // Use a broader regex to capture the intent
        // Check Name
        $this->assertMatchesRegularExpression(
            '/<td[^>]*class="[^"]*truncate[^"]*"[^>]*>\s*<div[^>]*>\s*' . preg_quote($longName, '/') . '/s',
            $html,
            'Name cell should have truncate class and div wrapper'
        );

        // Check Role
        $this->assertMatchesRegularExpression(
            '/<td[^>]*class="[^"]*truncate[^"]*"[^>]*>\s*<div[^>]*>\s*' . preg_quote($longRole, '/') . '/s',
            $html,
            'Role cell should have truncate class and div wrapper'
        );
    }
}
