# Laravel Horizon integration

To use Horizon with multi-tenant applications, you need to make sure your Redis queues are separated.
If you're using the RedisTenancyBootstrapper, this is handled automatically for you.

## Tags
Horizon tags are a great way to filter jobs.
If you want to filter jobs by tenant, you can add a tag to all jobs dispatched from the tenant context.
To do this, add the following to your AppServiceProvider:

```php
use Laravel\Horizon\Horizon; Horizon::auth(function ($request) { // ... });
```
