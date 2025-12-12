<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TenantPivot extends Pivot
{
    protected $table = 'tenant_users';
}
