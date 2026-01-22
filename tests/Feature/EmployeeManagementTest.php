<?php

use App\Models\Department;
use App\Models\User;
use App\Models\Tenant;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

test('a user can be created with all romanian legal fields', function () {
    // Create a department
    $department = Department::create([
        'name' => 'IT Department',
        'description' => 'Information Technology',
    ]);

    $userData = [
        'name' => 'Ion Popescu',
        'email' => 'ion@example.com',
        'password' => bcrypt('password'),
        'address' => 'Str. Principala, Nr. 1',
        'id_document_type' => 'CI',
        'id_document_number' => 'RX123456',
        'personal_numeric_code' => '1900101123456',
        'workplace_enter_code' => '1234',
        'department_id' => $department->id,
    ];

    $user = User::create($userData);

    expect($user->fresh())
        ->address->toBe('Str. Principala, Nr. 1')
        ->id_document_type->toBe('CI')
        ->id_document_number->toBe('RX123456')
        ->personal_numeric_code->toBe('1900101123456')
        ->workplace_enter_code->toBe('1234')
        ->department_id->toBe($department->id);
});

test('user relationship to department works', function () {
    $department = Department::create([
        'name' => 'HR',
    ]);

    $user = User::create([
        'name' => 'Maria Ionescu',
        'email' => 'maria@example.com',
        'password' => bcrypt('password'),
        'department_id' => $department->id,
    ]);

    expect($user->department)->toBeInstanceOf(Department::class)
        ->and($user->department->name)->toBe('HR');
});

test('data isolation between tenants', function () {
    // Current tenant context is already set up by TenantTestCase (Tenant A)
    $departmentA = Department::create(['name' => 'Tenant A Dept']);

    // Create Tenant B
    $tenantB = Tenant::withoutEvents(function () {
        return Tenant::create(['id' => 'tenant-b']);
    });
    $tenantB->domains()->create(['domain' => 'tenant-b.localhost']);

    // Switch to Tenant B
    tenancy()->initialize($tenantB);

    // Migrate Tenant B
    \Illuminate\Support\Facades\Artisan::call('migrate', [
        '--database' => 'tenant',
        '--path' => 'database/migrations/tenant',
        '--force' => true,
    ]);

    $departmentB = Department::create(['name' => 'Tenant B Dept']);

    // Verify Department A is not visible in Tenant B
    expect(Department::where('name', 'Tenant A Dept')->count())->toBe(0);
    expect(Department::where('name', 'Tenant B Dept')->count())->toBe(1);

    // Switch back to Tenant A (initial tenant)
    tenancy()->initialize($this->tenant);

    // Verify Department B is not visible in Tenant A
    expect(Department::where('name', 'Tenant B Dept')->count())->toBe(0);
    expect(Department::where('name', 'Tenant A Dept')->count())->toBe(1);

    // Cleanup Tenant B
    tenancy()->end(); // End Tenant A (handled by tearDown, but we switched)
    // Actually tearDown handles current tenant. But we created B manually.
    // TenantTestCase tearDown deletes $this->tenant. We need to delete tenantB manually if we don't assign it to this->tenant.

    $tenantB->domains()->delete();
    $tenantB->delete();

    // Re-initialize original tenant to avoid tearDown issues if needed,
    // but tearDown checks isset($this->tenant).
    // We just need to make sure we don't leave mess.
});
