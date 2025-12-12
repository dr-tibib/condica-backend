# Condica - Laravel Multi-Tenant Project Analysis

**Analysis Date:** 2025-12-12
**Project Type:** Laravel 12 Multi-Tenant Application with Backpack CRUD Admin Panel

---

## 1. Installed Packages (composer.json)

### Core Framework
- **Laravel Framework:** `^12.0`
- **PHP Version:** `^8.2`
- **Laravel Tinker:** `^2.10.1` (REPL for Laravel)

### Multi-Tenancy
- **stancl/tenancy:** `^3.9` - Multi-tenant database architecture with subdomain/domain identification

### Admin Panel (Backpack)
- **backpack/crud:** `^7.0` - Core CRUD functionality
- **backpack/pro:** `^3.0` - Premium features
- **backpack/theme-tabler:** `^2.0` - Tabler-based admin theme
- **backpack/permissionmanager:** `^7.3` - Integration with Spatie Permission

### Development Dependencies
- **backpack/generators:** `^4.1` - Code generators for Backpack
- **laravel/pint:** `^1.24` - PHP code style fixer
- **laravel/sail:** `^1.41` - Docker development environment
- **laravel/pail:** `^1.2.2` - Log tailing
- **laravel/boost:** `^1.8` - Performance optimizer
- **pestphp/pest:** `^4.1` - Testing framework
- **fakerphp/faker:** `^1.23` - Fake data generation

---

## 2. Multi-Tenancy Package

### Package Information
**Package:** `stancl/tenancy` (Version: ^3.9)
**Documentation:** https://tenancyforlaravel.com/

### Configuration (config/tenancy.php)

#### Tenant Identification
- **Method:** Domain/Subdomain-based identification
- **Central Domains:** Configured via `CENTRAL_DOMAINS` env variable (default: `127.0.0.1,localhost`)
- **Tenant Model:** `App\Models\Tenant`
- **Domain Model:** `App\Models\Domain`
- **ID Generator:** `Stancl\Tenancy\UUIDGenerator`

#### Database Configuration
- **Central Connection:** `central` (configured via `DB_CONNECTION`)
- **Database Strategy:** Separate database per tenant
- **Database Prefix:** `tenant`
- **Database Suffix:** (empty)
- **Supported Managers:** SQLite, MySQL, PostgreSQL

#### Bootstrappers (Active)
1. **DatabaseTenancyBootstrapper** - Switches database connections per tenant
2. **CacheTenancyBootstrapper** - Scopes cache by tenant
3. **FilesystemTenancyBootstrapper** - Separates file storage per tenant
4. **QueueTenancyBootstrapper** - Ensures queue jobs run in correct tenant context

#### Filesystem Tenancy
- **Suffix Base:** `tenant`
- **Tenanted Disks:** `local`, `public`
- **Storage Path Suffixing:** Enabled
- **Asset Helper Tenancy:** Enabled

#### Migration Configuration
- **Tenant Migrations Path:** `database/migrations/tenant`
- **Force Migrations:** `true` (allows production migrations)
- **Seeder Class:** `DatabaseSeeder`

---

## 3. Database Schema

### Central Database (Landlord)

#### `users` Table
| Column | Type | Attributes |
|--------|------|------------|
| id | BIGINT UNSIGNED | Primary Key, Auto Increment |
| name | VARCHAR(255) | Required |
| email | VARCHAR(255) | Unique, Required |
| email_verified_at | TIMESTAMP | Nullable |
| password | VARCHAR(255) | Hashed |
| remember_token | VARCHAR(100) | Nullable |
| is_global_superadmin | BOOLEAN | Default: false |
| created_at | TIMESTAMP | Auto |
| updated_at | TIMESTAMP | Auto |

#### `tenants` Table
| Column | Type | Attributes |
|--------|------|------------|
| id | VARCHAR(255) | Primary Key |
| created_at | TIMESTAMP | Auto |
| updated_at | TIMESTAMP | Auto |
| data | JSON | Nullable (custom tenant data) |

#### `domains` Table
| Column | Type | Attributes |
|--------|------|------------|
| id | INT UNSIGNED | Primary Key, Auto Increment |
| domain | VARCHAR(255) | Unique |
| tenant_id | VARCHAR(255) | Foreign Key → tenants.id |
| created_at | TIMESTAMP | Auto |
| updated_at | TIMESTAMP | Auto |

**Relationships:**
- Foreign key: `tenant_id` REFERENCES `tenants(id)` ON DELETE CASCADE ON UPDATE CASCADE

#### `tenant_users` Table (Pivot)
| Column | Type | Attributes |
|--------|------|------------|
| id | BIGINT UNSIGNED | Primary Key, Auto Increment |
| tenant_id | VARCHAR(255) | Foreign Key → tenants.id |
| global_user_id | BIGINT UNSIGNED | Foreign Key → users.id |
| created_at | TIMESTAMP | Auto |
| updated_at | TIMESTAMP | Auto |

**Indexes:**
- Unique: `(tenant_id, global_user_id)`

**Relationships:**
- Foreign key: `tenant_id` REFERENCES `tenants(id)` ON DELETE CASCADE ON UPDATE CASCADE
- Foreign key: `global_user_id` REFERENCES `users(id)` ON DELETE CASCADE ON UPDATE CASCADE

#### Permission Tables (Spatie Permission - Central)
- **permissions** - Stores permission definitions
- **roles** - Stores role definitions
- **model_has_permissions** - Polymorphic many-to-many
- **model_has_roles** - Polymorphic many-to-many
- **role_has_permissions** - Role-permission pivot table

### Tenant Databases

Each tenant has its own database with the following tables:

#### `users` Table (Tenant)
Same structure as central users table, synchronized via `Syncable` interface.

#### Permission Tables (Spatie Permission - Tenant)
Each tenant database includes its own set of permission tables:
- **permissions**
- **roles**
- **model_has_permissions**
- **model_has_roles**
- **role_has_permissions**

#### Standard Laravel Tables
- **cache** - Cache entries
- **cache_locks** - Cache locking
- **jobs** - Queue jobs
- **job_batches** - Batch job tracking
- **failed_jobs** - Failed queue jobs

---

## 4. Models and Relationships

### Central Models

#### `App\Models\CentralUser`
**Extends:** `Illuminate\Foundation\Auth\User`
**Implements:** `Stancl\Tenancy\Contracts\SyncMaster`
**Traits:**
- `CentralConnection` - Forces central database connection
- `CrudTrait` - Backpack CRUD integration
- `ResourceSyncing` - Syncs data to tenant databases
- `HasRoles` - Spatie Permission roles

**Table:** `users` (central database)

**Fillable Attributes:**
- `name`, `email`, `password`, `is_global_superadmin`

**Relationships:**
```php
public function tenants(): BelongsToMany
// Returns: Tenant instances this user belongs to
// Pivot: TenantPivot (tenant_users table)
```

**Syncing Configuration:**
- **Global Identifier:** `email`
- **Synced Attributes:** `name`, `email`, `password`, `is_global_superadmin`
- **Tenant Model:** `App\Models\User`

**Special Attributes:**
```php
protected function isGlobalSuperadmin(): Attribute
// Computed based on 'superadmin' role
```

#### `App\Models\Tenant`
**Extends:** `Stancl\Tenancy\Database\Models\Tenant`
**Implements:** `Stancl\Tenancy\Contracts\TenantWithDatabase`
**Traits:**
- `HasDatabase` - Database creation/deletion
- `HasDomains` - Domain management
- `CrudTrait` - Backpack CRUD integration

**Relationships:**
```php
public function users(): BelongsToMany
// Returns: CentralUser instances assigned to this tenant
// Pivot: TenantPivot (tenant_users table)

public function domains(): HasMany (inherited)
// Returns: Domain instances for this tenant
```

#### `App\Models\Domain`
**Extends:** `Stancl\Tenancy\Database\Models\Domain`
**Traits:** `CrudTrait`

**Relationships:**
```php
public function tenant(): BelongsTo (inherited)
// Returns: Tenant instance this domain belongs to
```

#### `App\Models\TenantPivot`
**Extends:** `Illuminate\Database\Eloquent\Relations\Pivot`
**Table:** `tenant_users`

Purpose: Custom pivot model for tenant-user relationships.

### Tenant Models

#### `App\Models\User`
**Extends:** `Illuminate\Foundation\Auth\User`
**Implements:** `Stancl\Tenancy\Contracts\Syncable`
**Traits:**
- `HasFactory`, `Notifiable` - Laravel defaults
- `HasRoles` - Spatie Permission roles
- `CrudTrait` - Backpack CRUD integration

**Table:** `users` (tenant database)

**Fillable Attributes:**
- `name`, `email`, `password`, `is_global_superadmin`

**Casts:**
- `email_verified_at` → `datetime`
- `password` → `hashed`
- `is_global_superadmin` → `boolean`

**Syncing Configuration:**
- **Global Identifier:** `email`
- **Synced Attributes:** `name`, `email`, `password`, `is_global_superadmin`
- **Central Model:** `App\Models\CentralUser`
- **Sync Direction:** Central → Tenant only (triggerSyncEvent is empty)

---

## 5. Backpack Configuration and CRUD Panels

### Backpack Configuration

#### Base Configuration (config/backpack/base.php)
- **Route Prefix:** `admin`
- **Web Middleware:** `web`
- **Admin Middleware:** Defined in `middleware_class`
- **Guard:** `backpack` (custom auth guard)
- **Password Reset:** `backpack` (custom password broker)
- **Registration:** Open only on `local` environment (configurable via `BACKPACK_REGISTRATION_OPEN`)
- **User Model:** Dynamic via `config('auth.providers.users.model')`

#### Middleware Stack
```php
[
    App\Http\Middleware\CheckIfAdmin::class,
    \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    \Backpack\CRUD\app\Http\Middleware\AuthenticateSession::class,
]
```

**CheckIfAdmin Middleware:** Currently configured to allow all authenticated users (returns `true`).
⚠️ **Note:** This should be customized to check for actual admin status.

#### Authentication
- **Column:** `email`
- **Avatar Type:** Gravatar with `blank` fallback

### CRUD Panels

#### 1. Tenant CRUD (`/admin/tenant`)
**Controller:** `App\Http\Controllers\Admin\TenantCrudController`
**Route:** `routes/backpack/custom.php`

**Operations:**
- List
- Create
- Update
- Delete
- Show

**List Columns:**
- `id` - Tenant ID
- `users` - Relationship count (number of assigned users)

**Create/Update Fields:**
- `id` (text) - Tenant ID / Subdomain identifier
- `users` (relationship) - Multi-select of CentralUsers (displays email)
- `domains` (relationship/repeatable) - Inline domain creation
  - Subfield: `domain` (text) - Domain name
  - Min rows: 1
  - Initial rows: 1

#### 2. Permission Manager CRUDs
**Route File:** `routes/backpack/permissionmanager.php`
**Namespace:** `App\Http\Controllers\Admin\PermissionManager`

##### User CRUD (`/admin/user`)
**Controller:** `UserCrudController`
**Model:** Configured by Permission Manager package

##### Role CRUD (`/admin/role`)
**Controller:** `RoleCrudController`
**Model:** `Spatie\Permission\Models\Role`

##### Permission CRUD (`/admin/permission`)
**Controller:** `PermissionCrudController`
**Model:** `Spatie\Permission\Models\Permission`

### Permission Configuration

**Package:** Spatie Permission (via Backpack PermissionManager)
**Config:** `config/permission.php`

**Models:**
- Permission: `Spatie\Permission\Models\Permission`
- Role: `Spatie\Permission\Models\Role`

**Tables:**
- `permissions`
- `roles`
- `model_has_permissions`
- `model_has_roles`
- `role_has_permissions`

**Features:**
- **Teams:** Disabled
- **Wildcard Permissions:** Disabled
- **Cache Expiration:** 24 hours
- **Events:** Disabled

---

## 6. Routes Structure

### Central Routes (routes/web.php)
```php
Route::get('/', function () {
    return view('welcome');
});
```

### Tenant Routes (routes/tenant.php)
```php
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/', function () {
        return 'This is your multi-tenant application.
                The id of the current tenant is ' . tenant('id');
    });
});
```

### Backpack Admin Routes

#### Custom Routes (routes/backpack/custom.php)
- `Route::crud('tenant', 'TenantCrudController')` → Tenant management

#### Permission Manager Routes (routes/backpack/permissionmanager.php)
- `Route::crud('permission', 'PermissionCrudController')` → Permission management
- `Route::crud('role', 'RoleCrudController')` → Role management
- `Route::crud('user', 'UserCrudController')` → User management

**Middleware:** `['web', backpack_middleware()]`
**Prefix:** `admin` (from config)

---

## 7. Migrations Summary

### Central Migrations
1. `0001_01_01_000000_create_users_table.php` - Users, password resets, sessions
2. `0001_01_01_000001_create_cache_table.php` - Cache and cache locks
3. `0001_01_01_000002_create_jobs_table.php` - Jobs, batches, failed jobs
4. `2019_09_15_000010_create_tenants_table.php` - Tenants table
5. `2019_09_15_000020_create_domains_table.php` - Domains table
6. `2025_12_08_135939_create_permission_tables.php` - Spatie Permission tables
7. `2020_03_31_114745_remove_backpackuser_model.php` - Backpack cleanup
8. `2025_12_08_202627_create_tenant_users_table.php` - Tenant-user pivot
9. `2025_12_11_193603_add_is_global_superadmin_to_central_users_table.php` - Superadmin flag

### Tenant Migrations (database/migrations/tenant/)
1. `0001_01_01_000000_create_users_table.php` - Tenant users
2. `0001_01_01_000001_create_cache_table.php` - Tenant cache
3. `0001_01_01_000002_create_jobs_table.php` - Tenant jobs
4. `2025_12_08_135939_create_permission_tables.php` - Tenant permissions
5. `2025_12_11_192334_add_is_global_superadmin_to_users_table.php` - Superadmin flag

---

## 8. Key Architecture Decisions

### Multi-Tenancy Strategy
- **Database-per-tenant** approach (not schema-based)
- **Domain/subdomain identification** for tenant resolution
- **Resource syncing** from central → tenant for users
- **Separate permission systems** per tenant (roles/permissions don't sync)

### User Management
- **CentralUser** (central DB) - Global user accounts
- **User** (tenant DB) - Tenant-specific user instances (synced)
- **Many-to-many** relationship via `tenant_users` pivot
- **Synced attributes:** name, email, password, is_global_superadmin
- **One-way sync:** Central → Tenant only

### Authentication & Authorization
- **Backpack custom guard:** Named `backpack`
- **Admin check:** Currently allows all authenticated users (needs customization)
- **Spatie Permission:** Integrated via Backpack PermissionManager
- **Separate roles per tenant:** Each tenant has independent role/permission tables

### Admin Panel
- **Backpack CRUD** for all admin interfaces
- **Tabler theme** for UI
- **Permission Manager** for RBAC management
- **Custom CRUD** for tenant management

---

## 9. Current State Assessment

### ✅ Completed Setup
- Laravel 12 installation
- Multi-tenancy package configured
- Backpack admin panel installed
- Permission management system
- Tenant CRUD interface
- User syncing between central and tenant databases
- Domain-based tenant identification

### ⚠️ Needs Attention

1. **CheckIfAdmin Middleware** (app/Http/Middleware/CheckIfAdmin.php:31)
   - Currently returns `true` for all users
   - Should implement proper admin role checking

2. **Database Seeder** (database/seeders/DatabaseSeeder.php)
   - Only creates a test user
   - Should include roles, permissions, and sample tenants

3. **Environment Configuration**
   - Need to configure `CENTRAL_DOMAINS` for production
   - Database connections need setup
   - Backpack license token may be needed

4. **Missing Documentation**
   - No tenant creation workflow documented
   - No user assignment process documented

### 🔧 Recommended Next Steps

1. Implement proper admin authorization in `CheckIfAdmin` middleware
2. Create seeders for roles and permissions
3. Add tenant creation workflow/documentation
4. Configure production environment variables
5. Set up testing for multi-tenant functionality
6. Add tenant-specific Backpack routes and CRUDs
7. Implement tenant user management interface
8. Configure email verification if needed

---

## 10. Development Commands

### Composer Scripts (from composer.json)
```bash
# Full setup
composer setup

# Development mode (runs server, queue, logs, vite)
composer dev

# Run tests
composer test
```

### Tenancy Commands
```bash
# Create tenant databases
php artisan tenants:migrate

# Seed tenant databases
php artisan tenants:seed

# Run tenant-specific commands
php artisan tenants:artisan "command" --tenants=tenant1,tenant2
```

### Backpack Commands
```bash
# Generate CRUD
php artisan backpack:crud {model-name}

# Publish Backpack assets
php artisan vendor:publish --provider="Backpack\CRUD\BackpackServiceProvider"
```

---

**End of Analysis**
