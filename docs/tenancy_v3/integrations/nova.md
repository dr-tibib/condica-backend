# Laravel Nova integration

## In the central app
To use Nova in the central app, you just need to make sure that the Nova routes are not accessible on tenant domains.
To do this, use a path-based identification middleware as the tenancy middleware in your Nova config:

```php
'middleware' => [ // ... 'web', 'auth:web', \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class, \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class, ],
```

## In the tenant app
To use Nova in the tenant app, you need to make sure that Nova uses the tenant database connection.
You can do this by setting the connection in your Nova Resource classes:

```php
public static $connection = 'tenant'; // or whatever your tenant connection is named
```

## Tenant file thumbnails and previews
If you upload images to the tenant's storage, Nova will try to display them using the storage_path() helper.
The issue is that the storage_path() helper returns an absolute path, which Nova doesn't know how to serve.
The solution is to use the tenant_asset() helper in your Nova resources:

```php
Text::make('Avatar')->resolveUsing(function ($value) { return tenant_asset($value); })->asHtml(),
```
