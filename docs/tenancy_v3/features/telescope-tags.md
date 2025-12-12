# Telescope tags

[Laravel Telescope](https://github.com/laravel/telescope) provides insight into the requests coming into your application. You can filter those requests by tag. You can automatically tag all requests by the active tenant by enabling the Telescope tag feature.

## Enabling the feature
Uncomment the following line in your tenancy.features config:

```php
// Stancl\Tenancy\Features\TelescopeTags::class,
```
