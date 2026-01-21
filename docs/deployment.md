# Deployment

## Requirements

-   **Server:** Linux (Ubuntu recommended)
-   **Web Server:** Nginx or Apache
-   **PHP:** 8.2+
-   **Database:** MySQL 8.0+ or PostgreSQL
-   **Redis:** Recommended for Cache and Queues

## Environment Configuration

Ensure these variables are set in your production `.env` file:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
...

# Important: List all domains that are "Central"
CENTRAL_DOMAINS=app.yourdomain.com,admin.yourdomain.com
```

## Deployment Steps

1.  **Code Deployment:**
    ```bash
    git pull origin main
    composer install --no-dev --optimize-autoloader
    npm ci && npm run build
    ```

2.  **Migrations:**
    ```bash
    php artisan migrate --force           # Central DB
    php artisan tenants:migrate --force   # All Tenant DBs
    ```

3.  **Optimization:**
    ```bash
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    ```

4.  **Queue Workers:**
    Configure Supervisor to run queue workers.
    ```bash
    php artisan queue:work --tries=3
    ```

## Web Server Configuration

You must configure your web server to handle wildcard subdomains if you are using them.

**Nginx Example:**
```nginx
server {
    listen 80;
    server_name yourdomain.com *.yourdomain.com;
    root /var/www/condica/public;

    # ... standard Laravel Nginx config ...
}
```
