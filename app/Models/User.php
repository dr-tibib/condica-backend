<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Traits\LogsActivity;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Stancl\Tenancy\Contracts\Syncable;
use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable implements Syncable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use CrudTrait, HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_global_superadmin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_global_superadmin' => 'boolean',
        ];
    }

    public function getGlobalIdentifierKey(): string
    {
        return $this->getAttribute($this->getGlobalIdentifierKeyName());
    }

    public function getGlobalIdentifierKeyName(): string
    {
        return 'email';
    }

    public function getCentralModelName(): string
    {
        return CentralUser::class;
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

    public function triggerSyncEvent()
    {
        // This method is required by the Syncable interface,
        // but we don't want to sync from Tenant to Central.
        // So we leave this empty.
    }

    /**
     * Get the employee profile for this user.
     */
    public function employee(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Employee::class);
    }
}
