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
use App\Models\Workplace;
use App\Models\Employee;
use App\Models\Department;

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

    /**
     * Get workplace enter code for this user through the employee.
     */
    public function getWorkplaceEnterCodeAttribute(): ?string
    {
        return $this->employee?->workplace_enter_code;
    }

    /**
     * Get personal numeric code for this user through the employee.
     */
    public function getPersonalNumericCodeAttribute(): ?string
    {
        return $this->employee?->personal_numeric_code;
    }

    /**
     * Get employee id for this user through the employee.
     */
    public function getEmployeeIdAttribute(): ?int
    {
        return $this->employee?->id;
    }

    /**
     * Get job role for this user through the employee.
     */
    public function getJobRoleAttribute(): ?string
    {
        return $this->employee?->role;
    }

    /**
     * Get address for this user through the employee.
     */
    public function getAddressAttribute(): ?string
    {
        return $this->employee?->address;
    }

    /**
     * Get ID document type for this user through the employee.
     */
    public function getIdDocumentTypeAttribute(): ?string
    {
        return $this->employee?->id_document_type;
    }

    /**
     * Get ID document number for this user through the employee.
     */
    public function getIdDocumentNumberAttribute(): ?string
    {
        return $this->employee?->id_document_number;
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

    /**
     * Get the default workplace for this user through the employee.
     */
    public function defaultWorkplace(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            Workplace::class,
            Employee::class,
            'user_id', // Foreign key on employees table...
            'id',      // Foreign key on workplaces table...
            'id',      // Local key on users table...
            'workplace_id' // Local key on employees table...
        );
    }

    /**
     * Get the department for this user through the employee.
     */
    public function department(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            Department::class,
            Employee::class,
            'user_id',
            'id',
            'id',
            'department_id'
        );
    }
}
