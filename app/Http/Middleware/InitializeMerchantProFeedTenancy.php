<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeMerchantProFeedTenancy
{
    /**
     * When the request host is a central domain, domain-based tenancy is not initialized.
     * For the MerchantPro CSV feed, allow an explicit tenant from config in that case.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (tenant() !== null) {
            return $next($request);
        }

        $tenantId = config('merchantpro_export.tenant_id');

        if ($tenantId === null || $tenantId === '') {
            abort(404);
        }

        $tenant = Tenant::query()->find($tenantId);

        if ($tenant === null) {
            abort(404);
        }

        $request->attributes->set('merchantpro_feed_manual_tenancy', true);
        tenancy()->initialize($tenant);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($request->attributes->get('merchantpro_feed_manual_tenancy') === true) {
            if (tenant() !== null) {
                tenancy()->end();
            }
        }
    }
}
