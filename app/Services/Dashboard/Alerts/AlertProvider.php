<?php

namespace App\Services\Dashboard\Alerts;

use App\Models\User;
use Illuminate\Support\Collection;

interface AlertProvider
{
    /**
     * @return Collection<int, Alert>
     */
    public function getAlerts(User $user): Collection;
}
