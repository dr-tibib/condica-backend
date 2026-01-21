# Developer Guide

## Project Structure

-   `app/Models/CentralUser.php`: The global user model.
-   `app/Models/Tenant.php`: The tenant model.
-   `app/Models/User.php`: The user model inside tenant context.
-   `database/migrations/`: Central database migrations.
-   `database/migrations/tenant/`: **Tenant database migrations.** All tables that should exist in each tenant's database go here.
-   `routes/tenant.php`: Routes available to tenant applications.
-   `routes/backpack/`: Admin panel routes.

## Key Commands

### Setup
```bash
composer setup   # Full install and setup
composer dev     # Start dev server
```

### Database & Migrations

**Central Migrations:**
```bash
php artisan migrate
```

**Tenant Migrations:**
To run migrations for *all* tenants:
```bash
php artisan tenants:migrate
```
To run for a specific tenant:
```bash
php artisan tenants:migrate --tenants=tenant1
```

**Fresh Migration (Reset):**
```bash
php artisan migrate:fresh
php artisan tenants:migrate-fresh
```

### Creating Features

#### Adding a Tenant Table
1.  Create the migration in the tenant folder:
    ```bash
    php artisan make:migration create_projects_table --path=database/migrations/tenant
    ```
2.  Run the migration:
    ```bash
    php artisan tenants:migrate
    ```

#### Adding a Central Table
1.  Standard Laravel command:
    ```bash
    php artisan make:migration create_plans_table
    ```

### Testing

We use **Pest** for testing.

**Run All Tests:**
```bash
php artisan test
```

**Writing Tenant Tests:**
When writing tests that require a tenant context, use the `Tenancy` traits or manually create a tenant in the test setup.

```php
use App\Models\Tenant;

it('can visit tenant dashboard', function () {
    $tenant = Tenant::factory()->create();
    $tenant->domains()->create(['domain' => 'test.localhost']);

    $response = $this->get('http://test.localhost');
    $response->assertStatus(200);
});
```

### Admin Panel (Backpack)

To generate a new CRUD interface:
```bash
php artisan backpack:crud {ModelName}
```
This will create:
-   Controller in `app/Http/Controllers/Admin`
-   Request for validation
-   Route entry in `routes/backpack/custom.php`

> **Note:** Decide if the CRUD is for the Central Admin (manage global data) or Tenant Admin (manage tenant-specific data) and place the controller/routes accordingly.
