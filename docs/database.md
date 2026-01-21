# Database Schema

The application uses a **Multi-Database** architecture. There is one "Central" (Landlord) database and many "Tenant" databases.

## Central Database (Landlord)

The central database manages the tenants, domains, and global user identities.

### Key Tables

#### `tenants`
Stores the registered tenants.
-   `id` (PK, String): The unique identifier (usually UUID or subdomain).
-   `data` (JSON): Stores extra tenant attributes (e.g., plan, subscription status).

#### `domains`
Stores the domains associated with tenants.
-   `domain` (String): The full domain name (e.g., `foo.localhost`).
-   `tenant_id` (FK): Links to the `tenants` table.

#### `users` (Central Users)
Stores the global user accounts.
-   `id` (PK, BigInt)
-   `name` (String)
-   `email` (String, Unique)
-   `password` (String, Hashed)
-   `is_global_superadmin` (Boolean): Grants full access to the central admin panel.

#### `tenant_users` (Pivot)
Connects Global Users to Tenants.
-   `tenant_id` (FK -> tenants.id)
-   `global_user_id` (FK -> users.id)

#### Permission Tables (Central)
Standard Spatie Permission tables (`roles`, `permissions`, etc.) for managing access to the *Central* application (e.g., who can create tenants).

## Tenant Databases

Each tenant has a completely independent database containing their business data.

### Key Tables

#### `users` (Tenant Users)
A local copy of the user data for this tenant.
-   Synced from Central Database (Name, Email, Password).
-   Used for local authentication within the tenant context.

#### Permission Tables (Tenant)
Standard Spatie Permission tables.
-   Roles and permissions here are *specific to this tenant*.
-   Example: A user might be `Admin` in Tenant A, but have no role in Tenant B.

#### Application Specific Tables
Tables related to the core business logic (e.g., Attendance, Projects, Tasks).
-   `presence_events` (example): Stores check-in/check-out data.
-   `workplaces`: Locations where users can check in.
-   `devices`: Registered user devices for notifications.

## Data Syncing

We implement the `Stancl\Tenancy\Contracts\SyncMaster` interface on the Central User and `Syncable` on the Tenant User.

-   **Trigger:** When a user is attached to a tenant or their central profile is updated.
-   **Direction:** Central -> Tenant.
-   **Attributes:** `name`, `email`, `password`, `is_global_superadmin`.

> **Note:** We do not sync roles/permissions. Authorization is handled separately in each context.
