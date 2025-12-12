# Livewire integration

Livewire works with tenancy out of the box, with one caveat:
Livewire's update requests are sent to a central route (/livewire/message/...).
If you're using domain identification, this central route will be accessed on the tenant domain, so tenancy will be initialized correctly.
However, if you're using path identification, you need to make sure that the Livewire routes are prefixed with the tenant id.

To do this, you can customize the livewire route configuration in config/livewire.php:

```php
'middleware_group' => ['web', 'universal'],
```

And ensuring 'universal' middleware group includes tenancy initialization logic that works for both central and tenant contexts (or just tenant context if that's what you need).
