<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'description',
        'level',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Get the permissions for this role.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    /**
     * Get the users with this role.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles')
                    ->withPivot(['assigned_by', 'assigned_at', 'expires_at', 'is_active'])
                    ->withTimestamps();
    }

    /**
     * Scope to get only active roles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by level.
     */
    public function scopeByLevel($query, $direction = 'asc')
    {
        return $query->orderBy('level', $direction);
    }

    /**
     * Get the display name based on locale.
     */
    public function getDisplayNameAttribute()
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    /**
     * Check if role has a specific permission.
     */
    public function hasPermission($permission)
    {
        if (is_string($permission)) {
            return $this->permissions()->where('name', $permission)->exists();
        }

        return $this->permissions()->where('id', $permission->id)->exists();
    }

    /**
     * Give permission to role.
     */
    public function givePermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
        }

        if ($permission && !$this->hasPermission($permission)) {
            $this->permissions()->attach($permission->id);
        }

        return $this;
    }

    /**
     * Revoke permission from role.
     */
    public function revokePermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
        }

        if ($permission && $this->hasPermission($permission)) {
            $this->permissions()->detach($permission->id);
        }

        return $this;
    }

    /**
     * Sync permissions for role.
     */
    public function syncPermissions($permissions)
    {
        $permissionIds = collect($permissions)->map(function ($permission) {
            if (is_string($permission)) {
                return Permission::where('name', $permission)->first()?->id;
            }
            return is_object($permission) ? $permission->id : $permission;
        })->filter()->toArray();

        $this->permissions()->sync($permissionIds);

        return $this;
    }

    /**
     * Get role level label.
     */
    public function getLevelLabelAttribute()
    {
        $levels = [
            1 => 'مدير عام',
            2 => 'مدير إدارة',
            3 => 'مشرف',
            4 => 'مقيم أول',
            5 => 'مقيم',
            6 => 'مساعد مقيم',
            7 => 'إدخال بيانات'
        ];

        return $levels[$this->level] ?? 'غير محدد';
    }

    /**
     * Check if this role is higher than another role.
     */
    public function isHigherThan(Role $role)
    {
        return $this->level < $role->level;
    }

    /**
     * Check if this role is lower than another role.
     */
    public function isLowerThan(Role $role)
    {
        return $this->level > $role->level;
    }

    /**
     * Get default roles data.
     */
    public static function getDefaultRoles()
    {
        return [
            [
                'name_ar' => 'مدير عام',
                'name_en' => 'General Manager',
                'slug' => 'general-manager',
                'description' => 'صلاحيات كاملة على النظام',
                'level' => 1,
                'is_active' => true
            ],
            [
                'name_ar' => 'مدير التقييم',
                'name_en' => 'Valuation Manager',
                'slug' => 'valuation-manager',
                'description' => 'إدارة عمليات التقييم والموظفين',
                'level' => 2,
                'is_active' => true
            ],
            [
                'name_ar' => 'مشرف التقييم',
                'name_en' => 'Valuation Supervisor',
                'slug' => 'valuation-supervisor',
                'description' => 'الإشراف على التقييمات ومراجعتها',
                'level' => 3,
                'is_active' => true
            ],
            [
                'name_ar' => 'مقيم أول',
                'name_en' => 'Senior Valuator',
                'slug' => 'senior-valuator',
                'description' => 'تقييم العقارات المعقدة والمراجعة',
                'level' => 4,
                'is_active' => true
            ],
            [
                'name_ar' => 'مقيم معتمد',
                'name_en' => 'Certified Valuator',
                'slug' => 'certified-valuator',
                'description' => 'تقييم العقارات وإعداد التقارير',
                'level' => 5,
                'is_active' => true
            ],
            [
                'name_ar' => 'مساعد مقيم',
                'name_en' => 'Assistant Valuator',
                'slug' => 'assistant-valuator',
                'description' => 'مساعدة في عمليات التقييم',
                'level' => 6,
                'is_active' => true
            ],
            [
                'name_ar' => 'إدخال بيانات',
                'name_en' => 'Data Entry',
                'slug' => 'data-entry',
                'description' => 'إدخال وتحديث البيانات',
                'level' => 7,
                'is_active' => true
            ]
        ];
    }
}

