<?php

pest()->extend(Tests\DuskTestCase::class)
//  ->use(Illuminate\Foundation\Testing\DatabaseMigrations::class)
    ->in('Browser');

use App\Models\Tenant;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

// API tests use TenantTestCase (no RefreshDatabase for multi-tenant)
pest()->extend(Tests\TenantTestCase::class)
    ->in('Feature/Feature/API');

// AI feature tests use TenantTestCase
pest()->extend(Tests\TenantTestCase::class)
    ->in('Feature/Feature/AI');

// Unit tests use TestCase
pest()->extend(Tests\TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function getUrl(string $path): string
{
    $tenant = Tenant::first();
    if (! $tenant) {
        throw new \Exception('No tenant found for getUrl()');
    }
    $domain = $tenant->domains->first();
    if (! $domain) {
        throw new \Exception('No domain found for tenant in getUrl()');
    }

    return "http://{$domain->domain}{$path}";
}
