<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nameAr',
        'nameEn',
        'email',
        'phone',
        'gender',
        'role',
        'position',
        'is_active',
        'language',
        'last_login_at',
        'email_verified_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays and JSON.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the roles for the user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /**
     * Get all permissions for the user through roles.
     */
    public function permissions()
    {
        return $this->hasManyThrough(
            Permission::class,
            Role::class,
            'id', // Foreign key on roles table
            'id', // Foreign key on permissions table
            'id', // Local key on users table
            'id'  // Local key on roles table
        )->distinct();
    }

    /**
     * Check if user has specific permission.
     */
    public function hasPermission($permission)
    {
        $userRoles = $this->roles()->with('permissions')->get();
        
        foreach ($userRoles as $role) {
            foreach ($role->permissions as $rolePermission) {
                if ($rolePermission->name === $permission) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission($permissions)
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions.
     */
    public function hasAllPermissions($permissions)
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has specific role.
     */
    public function hasRole($role)
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole($roles)
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Assign role to user.
     */
    public function assignRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }

        if ($role && !$this->hasRole($role->name)) {
            $this->roles()->attach($role->id);
        }

        return $this;
    }

    /**
     * Remove role from user.
     */
    public function removeRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }

        if ($role) {
            $this->roles()->detach($role->id);
        }

        return $this;
    }

    /**
     * Sync user roles.
     */
    public function syncRoles($roles)
    {
        $roleIds = [];
        
        foreach ($roles as $role) {
            if (is_string($role)) {
                $roleModel = Role::where('name', $role)->first();
                if ($roleModel) {
                    $roleIds[] = $roleModel->id;
                }
            } elseif (is_numeric($role)) {
                $roleIds[] = $role;
            }
        }

        $this->roles()->sync($roleIds);
        return $this;
    }

    /**
     * Get user's highest role level.
     */
    public function getHighestRoleLevel()
    {
        $roles = $this->roles()->get();
        $highestLevel = 0;

        foreach ($roles as $role) {
            if ($role->level > $highestLevel) {
                $highestLevel = $role->level;
            }
        }

        return $highestLevel;
    }

    /**
     * Check if user can manage another user (based on role levels).
     */
    public function canManageUser(User $user)
    {
        return $this->getHighestRoleLevel() > $user->getHighestRoleLevel();
    }

    /**
     * Get user's primary role (highest level).
     */
    public function getPrimaryRole()
    {
        return $this->roles()->orderBy('level', 'desc')->first();
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Relations
     */
    public function valuationsPrepared()
    {
        return $this->hasMany(Valuation::class, 'prepared_by');
    }

    public function valuationsInspected()
    {
        return $this->hasMany(Valuation::class, 'inspected_by');
    }

    /**
     * Get full name accessor (optional)
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->nameEn} / {$this->nameAr}"
        );
    }

    /**
     * Get the activity logs for the user.
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
