# Optional features

"Features" are classes that provide additional functionality that's not needed for the core tenancy logic. Out of the box, the package comes with these Features:
- [UserImpersonation](https://tenancyforlaravel.com/docs/v3/features/user-impersonation) for generating impersonation tokens for users of a tenant's DB from other contexts
- [TelescopeTags](https://tenancyforlaravel.com/docs/v3/features/telescope-tags) for adding tags with the current tenant id to Telescope entries
- [TenantConfig](https://tenancyforlaravel.com/docs/v3/features/tenant-config) for mapping keys from the tenant storage into the application config
- [CrossDomainRedirect](https://tenancyforlaravel.com/docs/v3/features/cross-domain-redirect) for adding a domain() macro on RedirectResponse letting you change the intended hostname of the generated route
- [UniversalRoutes](https://tenancyforlaravel.com/docs/v3/features/universal-routes) for route actions that work in both the central & tenant context
- [ViteBundler](https://tenancyforlaravel.com/docs/v3/features/vite-bundler) for making Vite generate the correct asset paths

All of the package's Features are in the Stancl\Tenancy\Features namespace.
You may register features by adding their class names to the tenancy.features config.
