# Console commands

The package comes with some useful artisan commands.
Tenant-aware commands run for all tenants by default. The commands also have the --tenants option which lets you specify IDs of the tenants for which the command will run.

## Migrate (tenant-aware)
The tenants:migrate command migrates databases of your tenants.

```bash
php artisan tenants:migrate --tenants=8075a580-1cb8-11e9-8822-49c5d8f8ff23
```

Note: By default, the migrations should be in database/migrations/tenant. If you wish to use a different path, you may use the --path option.

## Rollback & seed (tenant-aware)
- Rollback: tenants:rollback
- Seed: tenants:seed

## Migrate fresh (tenant-aware)
This package also offers a simplified, tenant-aware version of migrate:fresh. It runs db:wipe and tenants:migrate on the tenant's database.

## Run (tenant-aware)
You can use the tenants:run command to run your own commands for tenants.

```bash
php artisan tenants:run email:send --tenants=8075a580-1cb8-11e9-8822-49c5d8f8ff23 --option="queue=1" --option="subject=New Feature" --argument="body=We have launched a new feature. ..."
```

## List
The tenants:list command lists all existing tenants.

## Selectively clearing tenant cache
You can delete specific tenants' cache by using the --tags option on cache:clear:

```bash
php artisan cache:clear --tags=tenantdbe0b330-1a6e-11e9-b4c3-354da4b4f339
```

The tag is derived from config('tenancy.cache.tag_base') . $id.
