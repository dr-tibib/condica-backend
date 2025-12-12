<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Listeners\PermissionManagerTenancy;
use App\Models\Tenant;
use Stancl\Tenancy\Events\TenancyInitialized;

// Check Central Context
$centralModel = config('backpack.permissionmanager.models.user');
echo 'Central Context Model: '.$centralModel."\n";

if ($centralModel === 'App\Models\CentralUser') {
    echo "PASS: Central config is correct.\n";
} else {
    echo "FAIL: Central config is incorrect. Expected App\Models\CentralUser\n";
}

// Manually trigger listener logic
$listener = new PermissionManagerTenancy;
// Event object doesn't matter for the logic as currently written
$dummyEvent = new TenancyInitialized(new Tenant);

$listener->handle($dummyEvent);

$tenantModel = config('backpack.permissionmanager.models.user');
echo 'After Listener - Config Model: '.$tenantModel."\n";

if ($tenantModel === 'App\Models\User') {
    echo "PASS: Listener updated config correctly.\n";
} else {
    echo "FAIL: Listener failed to update config. Expected App\Models\User\n";
}
