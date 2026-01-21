# Getting Started

This guide will help you set up the Condica multi-tenant application on your local machine.

## Prerequisites

Ensure you have the following installed on your system:

-   **PHP** >= 8.2
-   **Composer**
-   **Node.js** & **NPM**
-   **MySQL** (or compatible database)

## Installation

1.  **Clone the repository:**
    ```bash
    git clone <repository-url>
    cd condica
    ```

2.  **Install dependencies and setup:**
    We have a convenience script that handles most of the setup process.
    ```bash
    composer setup
    ```
    This command will:
    -   Install PHP dependencies via Composer.
    -   Create the `.env` file from `.env.example`.
    -   Generate the application key.
    -   Run database migrations (both central and tenant).
    -   Install Node.js dependencies.
    -   Build frontend assets.

3.  **Configure Environment:**
    Open the `.env` file and configure your database and other settings.

    **Database Configuration:**
    ```ini
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=condica      # The name of your central database
    DB_USERNAME=root
    DB_PASSWORD=
    ```

    **Tenancy Configuration:**
    Configure the domains that should be treated as "central" (landing page, admin panel).
    ```ini
    CENTRAL_DOMAINS=localhost,127.0.0.1,your-local-domain.test
    ```

4.  **Google Places API (Optional):**
    If you want to use location features, add your Google Places API key:
    ```ini
    GOOGLE_PLACES_KEY=your-api-key
    ```

## Running the Application

To start the development server, queue worker, and Vite hot-reload server, run:

```bash
composer dev
```

This will start:
-   Laravel development server (`php artisan serve`)
-   Queue listener (`php artisan queue:listen`)
-   Log tailing (`php artisan pail`)
-   Vite development server (`npm run dev`)

You can access the application at `http://localhost:8000` (or your configured URL).

## Post-Installation Steps

1.  **Create a Super Admin:**
    Currently, you may need to seed the database or manually create a user in the `users` table of the central database and set `is_global_superadmin` to `true`.

2.  **Create a Tenant:**
    You can create tenants using the `tinker` command or (once implemented) via the Admin Panel.
    ```bash
    php artisan tinker
    ```
    ```php
    $tenant = \App\Models\Tenant::create(['id' => 'foo']);
    $tenant->domains()->create(['domain' => 'foo.localhost']);
    ```
    Now you can access the tenant at `http://foo.localhost:8000`.

## Troubleshooting

-   **Vite Manifest Error:** If you see an error about the Vite manifest, run `npm run build` or ensure `npm run dev` is running.
-   **Database Errors:** Ensure your database credentials are correct and that you have run migrations (`php artisan migrate`).
