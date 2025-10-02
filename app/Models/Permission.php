<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name_ar',
        'display_name_en',
        'description',
        'module',
        'action',
        'resource'
    ];

    /**
     * Get the roles that have this permission.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /**
     * Get the display name based on locale.
     */
    public function getDisplayNameAttribute()
    {
        return app()->getLocale() === 'ar' ? $this->display_name_ar : $this->display_name_en;
    }

    /**
     * Scope to filter by module.
     */
    public function scopeOfModule($query, $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope to filter by action.
     */
    public function scopeOfAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by resource.
     */
    public function scopeOfResource($query, $resource)
    {
        return $query->where('resource', $resource);
    }

    /**
     * Get default permissions data.
     */
    public static function getDefaultPermissions()
    {
        return [
            // Valuations permissions
            [
                'name' => 'valuations.create',
                'display_name_ar' => 'إنشاء تقييم',
                'display_name_en' => 'Create Valuation',
                'description' => 'إنشاء تقييم جديد',
                'module' => 'valuations',
                'action' => 'create',
                'resource' => 'valuations'
            ],
            [
                'name' => 'valuations.read',
                'display_name_ar' => 'عرض التقييمات',
                'display_name_en' => 'View Valuations',
                'description' => 'عرض قائمة التقييمات',
                'module' => 'valuations',
                'action' => 'read',
                'resource' => 'valuations'
            ],
            [
                'name' => 'valuations.update',
                'display_name_ar' => 'تعديل التقييم',
                'display_name_en' => 'Update Valuation',
                'description' => 'تعديل بيانات التقييم',
                'module' => 'valuations',
                'action' => 'update',
                'resource' => 'valuations'
            ],
            [
                'name' => 'valuations.delete',
                'display_name_ar' => 'حذف التقييم',
                'display_name_en' => 'Delete Valuation',
                'description' => 'حذف التقييم',
                'module' => 'valuations',
                'action' => 'delete',
                'resource' => 'valuations'
            ],
            [
                'name' => 'valuations.approve',
                'display_name_ar' => 'اعتماد التقييم',
                'display_name_en' => 'Approve Valuation',
                'description' => 'اعتماد التقييم النهائي',
                'module' => 'valuations',
                'action' => 'approve',
                'resource' => 'valuations'
            ],
            [
                'name' => 'valuations.reject',
                'display_name_ar' => 'رفض التقييم',
                'display_name_en' => 'Reject Valuation',
                'description' => 'رفض التقييم وإرجاعه للتعديل',
                'module' => 'valuations',
                'action' => 'reject',
                'resource' => 'valuations'
            ],
            [
                'name' => 'valuations.transfer',
                'display_name_ar' => 'تحويل التقييم',
                'display_name_en' => 'Transfer Valuation',
                'description' => 'تحويل التقييم لموظف آخر',
                'module' => 'valuations',
                'action' => 'transfer',
                'resource' => 'valuations'
            ],
            [
                'name' => 'valuations.export_pdf',
                'display_name_ar' => 'تصدير PDF',
                'display_name_en' => 'Export PDF',
                'description' => 'تصدير التقييم كملف PDF',
                'module' => 'valuations',
                'action' => 'export',
                'resource' => 'valuations'
            ],

            // Clients permissions
            [
                'name' => 'clients.create',
                'display_name_ar' => 'إضافة عميل',
                'display_name_en' => 'Create Client',
                'description' => 'إضافة عميل جديد',
                'module' => 'clients',
                'action' => 'create',
                'resource' => 'clients'
            ],
            [
                'name' => 'clients.read',
                'display_name_ar' => 'عرض العملاء',
                'display_name_en' => 'View Clients',
                'description' => 'عرض قائمة العملاء',
                'module' => 'clients',
                'action' => 'read',
                'resource' => 'clients'
            ],
            [
                'name' => 'clients.update',
                'display_name_ar' => 'تعديل العميل',
                'display_name_en' => 'Update Client',
                'description' => 'تعديل بيانات العميل',
                'module' => 'clients',
                'action' => 'update',
                'resource' => 'clients'
            ],
            [
                'name' => 'clients.delete',
                'display_name_ar' => 'حذف العميل',
                'display_name_en' => 'Delete Client',
                'description' => 'حذف العميل',
                'module' => 'clients',
                'action' => 'delete',
                'resource' => 'clients'
            ],

            // Users permissions
            [
                'name' => 'users.create',
                'display_name_ar' => 'إضافة مستخدم',
                'display_name_en' => 'Create User',
                'description' => 'إضافة مستخدم جديد',
                'module' => 'users',
                'action' => 'create',
                'resource' => 'users'
            ],
            [
                'name' => 'users.read',
                'display_name_ar' => 'عرض المستخدمين',
                'display_name_en' => 'View Users',
                'description' => 'عرض قائمة المستخدمين',
                'module' => 'users',
                'action' => 'read',
                'resource' => 'users'
            ],
            [
                'name' => 'users.update',
                'display_name_ar' => 'تعديل المستخدم',
                'display_name_en' => 'Update User',
                'description' => 'تعديل بيانات المستخدم',
                'module' => 'users',
                'action' => 'update',
                'resource' => 'users'
            ],
            [
                'name' => 'users.delete',
                'display_name_ar' => 'حذف المستخدم',
                'display_name_en' => 'Delete User',
                'description' => 'حذف المستخدم',
                'module' => 'users',
                'action' => 'delete',
                'resource' => 'users'
            ],
            [
                'name' => 'users.assign_roles',
                'display_name_ar' => 'تعيين الأدوار',
                'display_name_en' => 'Assign Roles',
                'description' => 'تعيين الأدوار للمستخدمين',
                'module' => 'users',
                'action' => 'assign',
                'resource' => 'roles'
            ],

            // Reports permissions
            [
                'name' => 'reports.view',
                'display_name_ar' => 'عرض التقارير',
                'display_name_en' => 'View Reports',
                'description' => 'عرض التقارير والإحصائيات',
                'module' => 'reports',
                'action' => 'read',
                'resource' => 'reports'
            ],
            [
                'name' => 'reports.export',
                'display_name_ar' => 'تصدير التقارير',
                'display_name_en' => 'Export Reports',
                'description' => 'تصدير التقارير',
                'module' => 'reports',
                'action' => 'export',
                'resource' => 'reports'
            ],

            // Settings permissions
            [
                'name' => 'settings.view',
                'display_name_ar' => 'عرض الإعدادات',
                'display_name_en' => 'View Settings',
                'description' => 'عرض إعدادات النظام',
                'module' => 'settings',
                'action' => 'read',
                'resource' => 'settings'
            ],
            [
                'name' => 'settings.update',
                'display_name_ar' => 'تعديل الإعدادات',
                'display_name_en' => 'Update Settings',
                'description' => 'تعديل إعدادات النظام',
                'module' => 'settings',
                'action' => 'update',
                'resource' => 'settings'
            ],

            // Templates permissions
            [
                'name' => 'templates.create',
                'display_name_ar' => 'إنشاء قالب',
                'display_name_en' => 'Create Template',
                'description' => 'إنشاء قالب تقرير جديد',
                'module' => 'templates',
                'action' => 'create',
                'resource' => 'templates'
            ],
            [
                'name' => 'templates.read',
                'display_name_ar' => 'عرض القوالب',
                'display_name_en' => 'View Templates',
                'description' => 'عرض قوالب التقارير',
                'module' => 'templates',
                'action' => 'read',
                'resource' => 'templates'
            ],
            [
                'name' => 'templates.update',
                'display_name_ar' => 'تعديل القالب',
                'display_name_en' => 'Update Template',
                'description' => 'تعديل قالب التقرير',
                'module' => 'templates',
                'action' => 'update',
                'resource' => 'templates'
            ],
            [
                'name' => 'templates.delete',
                'display_name_ar' => 'حذف القالب',
                'display_name_en' => 'Delete Template',
                'description' => 'حذف قالب التقرير',
                'module' => 'templates',
                'action' => 'delete',
                'resource' => 'templates'
            ]
        ];
    }

    /**
     * Get permissions grouped by module.
     */
    public static function getGroupedPermissions()
    {
        return static::all()->groupBy('module');
    }
}

