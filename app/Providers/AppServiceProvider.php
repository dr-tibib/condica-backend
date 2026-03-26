<?php

namespace App\Providers;

use App\Channels\WhatsAppChannel;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Twilio\Rest\Client as TwilioClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TwilioClient::class, function () {
            return new TwilioClient(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );
        });

        $this->app->singleton(WhatsAppChannel::class, function ($app) {
            return new WhatsAppChannel($app->make(TwilioClient::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user = null, $capability = null) {
            $user = $user ?? backpack_user();
            if ($user) {
                return $user->is_global_superadmin || $user->hasRole('superadmin') ? true : null;
            }
            return null;
        });

        // \App\Models\CentralUser::observe(\App\Listeners\SyncNewSuperAdminToAllTenants::class);
        \App\Models\LeaveRequest::observe(\App\Observers\LeaveRequestObserver::class);
    }
}
