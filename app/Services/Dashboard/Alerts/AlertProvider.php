<?php

namespace App\Services\Dashboard\Alerts;

use App\Models\Employee;
use Illuminate\Support\Collection;

interface AlertProvider
{
    /**
     * @return Collection<int, Alert>
     */
    public function getAlerts(Employee $employee): Collection;
}
