<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Stancl\Tenancy\Contracts\SyncMaster;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Stancl\Tenancy\Database\Concerns\ResourceSyncing;

class CentralUser extends Authenticatable implements SyncMaster
{
    use CentralConnection, CrudTrait, HasRoles, LogsActivity, ResourceSyncing;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_global_superadmin',
    ];

    protected $guarded = [];

    protected $guard_name = 'web';

    public function getGlobalIdentifierKeyName(): string
    {
        return 'email';
    }

    public function getGlobalIdentifierKey(): string
    {
        return $this->getAttribute($this->getGlobalIdentifierKeyName());
    }

    public function getCentralModelName(): string
    {
        return static::class;
    }

    public function getTenantModelName(): string
    {
        return User::class;
    }

    public function getSyncedAttributeNames(): array
    {
        return [
            'name',
            'email',
            'password',
            'is_global_superadmin',
        ];
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users', 'global_user_id', 'tenant_id')
            ->using(TenantPivot::class);
    }

    protected function isGlobalSuperadmin(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->hasRole('superadmin'),
            set: fn ($value) => $this->hasRole('superadmin')
        );
    }
}
