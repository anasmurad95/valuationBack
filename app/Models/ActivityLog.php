<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity_type',
        'description',
        'ip_address',
        'user_agent',
        'details'
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for filtering by activity type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope for filtering by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get activity type in Arabic.
     */
    public function getActivityTypeArabicAttribute()
    {
        $types = [
            'valuation_created' => 'إنشاء تقييم',
            'valuation_updated' => 'تحديث تقييم',
            'valuation_deleted' => 'حذف تقييم',
            'valuation_transferred' => 'تحويل تقييم',
            'user_login' => 'تسجيل دخول',
            'user_logout' => 'تسجيل خروج',
            'user_created' => 'إنشاء مستخدم',
            'user_updated' => 'تحديث مستخدم',
            'user_deleted' => 'حذف مستخدم',
            'role_assigned' => 'تعيين دور',
            'role_removed' => 'إزالة دور',
            'report_generated' => 'إنتاج تقرير',
            'report_exported' => 'تصدير تقرير',
            'profile_updated' => 'تحديث ملف شخصي',
            'password_changed' => 'تغيير كلمة المرور',
            'settings_updated' => 'تحديث إعدادات',
            'data_exported' => 'تصدير بيانات',
            'data_imported' => 'استيراد بيانات',
            'backup_created' => 'إنشاء نسخة احتياطية',
            'system_maintenance' => 'صيانة النظام'
        ];

        return $types[$this->activity_type] ?? $this->activity_type;
    }

    /**
     * Get formatted created at date.
     */
    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }

    /**
     * Get activity icon based on type.
     */
    public function getActivityIconAttribute()
    {
        $icons = [
            'valuation_created' => 'mdi-plus-circle',
            'valuation_updated' => 'mdi-pencil',
            'valuation_deleted' => 'mdi-delete',
            'valuation_transferred' => 'mdi-swap-horizontal',
            'user_login' => 'mdi-login',
            'user_logout' => 'mdi-logout',
            'user_created' => 'mdi-account-plus',
            'user_updated' => 'mdi-account-edit',
            'user_deleted' => 'mdi-account-remove',
            'role_assigned' => 'mdi-shield-account',
            'role_removed' => 'mdi-shield-remove',
            'report_generated' => 'mdi-file-document',
            'report_exported' => 'mdi-download',
            'profile_updated' => 'mdi-account-edit',
            'password_changed' => 'mdi-key-change',
            'settings_updated' => 'mdi-cog',
            'data_exported' => 'mdi-export',
            'data_imported' => 'mdi-import',
            'backup_created' => 'mdi-backup-restore',
            'system_maintenance' => 'mdi-wrench'
        ];

        return $icons[$this->activity_type] ?? 'mdi-information';
    }

    /**
     * Get activity color based on type.
     */
    public function getActivityColorAttribute()
    {
        $colors = [
            'valuation_created' => 'success',
            'valuation_updated' => 'info',
            'valuation_deleted' => 'error',
            'valuation_transferred' => 'primary',
            'user_login' => 'success',
            'user_logout' => 'warning',
            'user_created' => 'success',
            'user_updated' => 'info',
            'user_deleted' => 'error',
            'role_assigned' => 'purple',
            'role_removed' => 'orange',
            'report_generated' => 'secondary',
            'report_exported' => 'teal',
            'profile_updated' => 'info',
            'password_changed' => 'warning',
            'settings_updated' => 'grey',
            'data_exported' => 'teal',
            'data_imported' => 'indigo',
            'backup_created' => 'green',
            'system_maintenance' => 'orange'
        ];

        return $colors[$this->activity_type] ?? 'grey';
    }
}

