# Admin Guide

This guide explains how to use the Central Administration Panel to manage tenants and users.

## Accessing the Admin Panel

The Admin Panel is located at the `/admin` path of your central domain.
-   **Local:** `http://localhost:8000/admin`
-   **Production:** `https://your-domain.com/admin`

You must log in with a user that has the `is_global_superadmin` flag set to `true` (or appropriate permissions).

## Managing Tenants

Navigate to **Admin -> Tenants** in the sidebar.

### Creating a New Tenant
1.  Click **"Add Tenant"**.
2.  **ID:** Enter a unique identifier. This is often used as the subdomain (e.g., `acme`).
3.  **Domains:** Click "Add Domain" and enter the full domain (e.g., `acme.localhost` or `acme.com`).
    -   *Note:* You must ensure your DNS or `/etc/hosts` points this domain to your server IP.
4.  **Users:** You can select existing users to assign to this tenant immediately.
5.  Click **"Save"**.

The system will automatically:
-   Create a new database for the tenant.
-   Run all migrations in the new database.
-   Sync the selected users to the new database.

### Managing Tenant Users
To add a user to an existing tenant:
1.  Edit the Tenant.
2.  In the **Users** field, select the users you want to add.
3.  Click **"Save"**.

Alternatively, you can manage this via the "Users" menu if a custom relationship manager is implemented.

## Managing Global Users

Navigate to **Admin -> Users**.

This panel manages the *Central User* accounts.
-   **Add User:** Creates a new global identity.
-   **Edit User:** Updates name, email, or password. These changes will be synced to all tenants the user belongs to.
-   **Assign Super Admin:** Toggle the "Global Super Admin" checkbox to grant full system access.

## Roles & Permissions (Global)

Navigate to **Admin -> Authentication**.

Here you can define roles and permissions for the *Central Admin Panel*.
-   **Roles:** e.g., "Support Staff", "Billing Manager".
-   **Permissions:** e.g., "manage tenants", "view logs".

> **Important:** These roles apply ONLY to the central application. They do not grant permissions inside a tenant's specific application context.
