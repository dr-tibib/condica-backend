<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;

// Check Central Context
$centralModel = config('backpack.permissionmanager.models.user');
echo 'Central Context Model: '.$centralModel."\n";

if ($centralModel === 'App\Models\CentralUser') {
    echo "PASS: Central config is correct.\n";
} else {
    echo "FAIL: Central config is incorrect. Expected App\Models\CentralUser\n";
}

// Check Tenant Context
$tenantId = 'verify_test_tenant';
// Reuse existing tenant if available or create
if (! Tenant::find($tenantId)) {
    $tenant = Tenant::create(['id' => $tenantId]);
    $tenant->domains()->create(['domain' => $tenantId.'.test']);
} else {
    $tenant = Tenant::find($tenantId);
}

echo "\nInitializing Tenancy for $tenantId...\n";
tenancy()->initialize($tenant);

$tenantModel = config('backpack.permissionmanager.models.user');
echo 'Tenant Context Model: '.$tenantModel."\n";

if ($tenantModel === 'App\Models\User') {
    echo "PASS: Tenant config is correct.\n";
} else {
    echo "FAIL: Tenant config is incorrect. Expected App\Models\User\n";
}

tenancy()->end();
