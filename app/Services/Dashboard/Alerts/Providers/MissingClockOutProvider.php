<?php

namespace App\Services\Dashboard\Alerts\Providers;

use App\Models\User;
use App\Services\Dashboard\Alerts\Alert;
use App\Services\Dashboard\Alerts\AlertProvider;
use Illuminate\Support\Collection;

class MissingClockOutProvider implements AlertProvider
{
    public function getAlerts(User $user): Collection
    {
        $latest = $user->latestPresenceEvent;

        // If the user is currently checked in, but the check-in was before today
        // (meaning they forgot to check out yesterday or earlier)
        if ($latest && $latest->isCheckIn() && !$latest->event_time->isToday()) {
            return collect([
                new Alert(
                    'Missing Log',
                    'Missing clock-out on ' . $latest->event_time->format('M jS'),
                    backpack_url('presence-event'), // Direct them to presence list to fix
                    'Fix Now',
                    'danger'
                )
            ]);
        }

        return collect();
    }
}
