# Laravel Passport

Tip: If you just want to write an SPA application but don't need an API for some other use (e.g., a mobile app), you can avoid a lot of the complexity of writing SPAs by using [Inertia.js](https://inertiajs.com/).
Another tip: Using Passport only in the central application doesn't require any additional configuration. You can just install it following [the official Laravel Passport documentation](https://laravel.com/docs/9.x/passport).

## Using Passport in the tenant application only
Note: Don't use the passport:install command. The command creates the encryption keys & two clients in the central application. Instead of that, we'll generate the keys and create the clients manually later.

To use Passport inside the tenant part of your application, you may do the following.
1. Publish the Passport migrations by running php artisan vendor:publish --tag=passport-migrations and move them to your tenant migration directory (database/migrations/tenant/).
2. Publish the Passport config by running php artisan vendor:publish --tag=passport-config. If you're using Passport 10.x, make Passport use the default database connection by setting the storage database connection to null. The passport:keys command puts the keys in the storage/ directory by default – you can change that by setting the key path in the config.
3. Prevent Passport migrations from running in the central application by adding Passport::ignoreMigrations() to the register() method in your AuthServiceProvider.
4. Data-filling/Passport routes setup in AuthServiceProvider boot().

## Using Passport in both the tenant and the central application
To use Passport in both the tenant and the central application:
1. Follow [the steps for using Passport in the tenant appliction](https://tenancyforlaravel.com/docs/v3/integrations/passport#using-passport-in-the-tenant-application-only).
2. Copy the Passport migrations to the central application, so that the Passport migrations are in both the central and the tenant application.
3. Remove Passport::ignoreMigrations() from the register() method in your AuthServiceProvider (if it is there).
4. In your AuthServiceProvider's boot() method (where you registered the Passport routes), add the 'universal' middleware to the Passport routes, and remove the PreventAccessFromCentralDomains::class middleware.
5. Enable [universal routes](https://tenancyforlaravel.com/docs/v3/features/universal-routes) to make Passport routes accessible to both apps.

## Passport encryption keys
### Shared keys
To generate a single Passport key pair for the whole app, create Passport clients for your tenants by adding code to your tenant database seeder.
You can set your tenant database seeder class in config/tenancy.php file at seeder_parameters key.
Then, seed the database and generate the key pair by running php artisan passport:keys.

### Tenant-specific keys
Note: The security benefit of doing this is negligible since you're likely already using the same APP_KEY for all tenants. This is a relatively complex approach, so before implementing it, make sure you really want it. Using shared keys instead is strongly recommended.
If you want to use a unique Passport key pair for each tenant, there are multiple ways to store and load tenant Passport keys. The most straightforward way is to store them in the Tenant model and load them into the Passport configuration using the [Tenant Config](https://tenancyforlaravel.com/docs/v3/features/tenant-config) feature.
