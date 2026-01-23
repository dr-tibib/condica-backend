<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Services\Dashboard\Alerts\AlertProvider;
use App\Services\Dashboard\Alerts\Providers\MissingClockOutProvider;
use App\Services\Dashboard\Alerts\Providers\RejectedLeaveProvider;
use Illuminate\Support\Collection;

class AlertService
{
    /** @var AlertProvider[] */
    protected array $providers = [];

    public function __construct()
    {
        $this->providers = [
            new MissingClockOutProvider(),
            new RejectedLeaveProvider(),
        ];
    }

    public function getAlerts(User $user): Collection
    {
        $alerts = collect();

        foreach ($this->providers as $provider) {
            $alerts = $alerts->merge($provider->getAlerts($user));
        }

        return $alerts;
    }
}
