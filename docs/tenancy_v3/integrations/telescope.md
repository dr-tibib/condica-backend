# Laravel Telescope integration

To use Telescope with multi-tenant applications, you have two options:
1. Run Telescope in the central application only and tag entries with the tenant id.
2. Run Telescope in both central and tenant applications.

For option 1, use the [TelescopeTags](https://tenancyforlaravel.com/docs/v3/features/telescope-tags) feature.

For option 2, you need to install Telescope in your tenant application.
And you need to make sure Telescope uses the tenant database connection.
You can do this by setting the TELESCOPE_DB_CONNECTION environment variable to your tenant connection (e.g. tenant).
