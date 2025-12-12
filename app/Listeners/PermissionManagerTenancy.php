<?php

namespace App\Listeners;

use App\Models\CentralUser;
use App\Models\User;
use Stancl\Tenancy\Events\TenancyInitialized;

class PermissionManagerTenancy
{
    /**
     * Handle the event.
     */
    public function handle(TenancyInitialized $event): void
    {
        $isCentralContext = $event->tenancy->tenant === null;
        $modelClass = $isCentralContext ? CentralUser::class : User::class;
        config(['backpack.permissionmanager.models.user' => $modelClass]);
    }
}
