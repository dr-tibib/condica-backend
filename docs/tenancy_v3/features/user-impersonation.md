# User impersonation

This package comes with a feature that lets you impersonate users inside tenant databases. This feature works with any identification method and any stateful auth guard — even if you use multiple.
Note: If you're currently using a non-stateful auth guard (e.g., Laravel Sanctum's guard), you can still utilize user impersonation by passing a stateful guard to tenancy()->impersonate() (e.g. the 'web' guard).

## How it works
You generate an impersonation token and store it in the central database, in the tenant_user_impersonation_tokens table.
Each record in the table holds the following data:
- The token value (128 character string)
- The tenant's id
- The user's id
- The name of the auth guard
- The URL to redirect to after the impersonation takes place
You visit an impersonation route that you create — though little work is needed on your side, your route will mostly just call a method provided by the feature. This route is a tenant route, meaning it's on the tenant domain if you use domain identification, or prefixed with the tenant id if you use path identification.
This route tries to find a record in that table based on the token, and if it's valid, it authenticates you with the stored user id against the auth guard and redirects you to the stored URL.
If the impersonation succeeds, the token is deleted from the database.
All tokens expire after 60 seconds by default, and this TTL can be customized.

## Enabling the feature
To enable this feature, go to your config/tenancy.php file and make sure the following class is in your features part of the config:

```php
Stancl\Tenancy\Features\UserImpersonation::class,
```

Next, publish the migration for creating the table with impersonation tokens:

```
php artisan vendor:publish --tag=impersonation-migrations
```

And finally, run the migration:

```
php artisan migrate
```

## Usage
First, you need to create a tenant route that looks like this:

```php
use Stancl\Tenancy\Features\UserImpersonation; // We're in your tenant routes! Route::get('/impersonate/{token}', function ($token) { return UserImpersonation::makeResponse($token); });
```

Then, to use impersonation in your app, generate a token like this:

```php
$redirectUrl = '/dashboard'; $token = tenancy()->impersonate($tenant, $user->id, $redirectUrl);
```

### Domain identification
```php
$domain = $tenant->primary_domain; return redirect("https://$domain/impersonate/{$token->token}");
```

### Path identification
```php
return redirect("{$tenant->id}/impersonate/{$token->token}");
```

### Custom auth guards
Note: The auth guard used by user impersonation has to be stateful.
If you're using multiple auth guards, you may want to specify what auth guard the impersonation logic should use.
To do this, simply pass the auth guard name as the fourth argument to the impersonate() method.

```php
tenancy()->impersonate($tenant, $user->id, $redirectUrl, 'jwt');
```

## Customization
You may customize the TTL of impersonation tokens by setting the following static property to the amount of seconds you want to use:

```php
Stancl\Tenancy\Features\UserImpersonation::$ttl = 120; // 2 minutes
```
