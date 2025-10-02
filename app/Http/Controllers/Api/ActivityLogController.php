<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ActivityLog::with(['user']);
            
            // Filter by activity type
            if ($request->has('type') && !empty($request->type)) {
                $query->where('activity_type', $request->type);
            }
            
            // Filter by user
            if ($request->has('user_id') && !empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }
            
            // Filter by date range
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            
            // Search in description
            if ($request->has('search') && !empty($request->search)) {
                $query->where('description', 'like', '%' . $request->search . '%');
            }
            
            // Order by latest first
            $query->orderBy('created_at', 'desc');
            
            // Pagination
            $perPage = $request->get('per_page', 20);
            $activities = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $activities,
                'message' => 'تم جلب سجل النشاطات بنجاح'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب سجل النشاطات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created activity log.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $activity = ActivityLog::create([
                'user_id' => Auth::id(),
                'activity_type' => $request->activity_type,
                'description' => $request->description,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'details' => $request->details
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $activity->load('user'),
                'message' => 'تم تسجيل النشاط بنجاح'
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل النشاط',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified activity log.
     */
    public function show($id): JsonResponse
    {
        try {
            $activity = ActivityLog::with(['user'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $activity,
                'message' => 'تم جلب تفاصيل النشاط بنجاح'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على النشاط',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get activity statistics.
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->get('date_to', now()->format('Y-m-d'));
            
            $stats = [
                'total_activities' => ActivityLog::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                'activities_by_type' => ActivityLog::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->selectRaw('activity_type, count(*) as count')
                    ->groupBy('activity_type')
                    ->pluck('count', 'activity_type'),
                'activities_by_user' => ActivityLog::with('user')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->get()
                    ->groupBy('user.name')
                    ->map->count(),
                'daily_activities' => ActivityLog::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->selectRaw('DATE(created_at) as date, count(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->pluck('count', 'date'),
                'most_active_users' => User::withCount(['activityLogs' => function($q) use ($dateFrom, $dateTo) {
                        $q->whereBetween('created_at', [$dateFrom, $dateTo]);
                    }])
                    ->orderByDesc('activity_logs_count')
                    ->take(5)
                    ->get()
                    ->map(function($user) {
                        return [
                            'name' => $user->name,
                            'activities_count' => $user->activity_logs_count
                        ];
                    }),
                'recent_activities' => ActivityLog::with('user')
                    ->latest()
                    ->take(10)
                    ->get()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'date_range' => [
                    'from' => $dateFrom,
                    'to' => $dateTo
                ],
                'message' => 'تم جلب إحصائيات النشاطات بنجاح'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب إحصائيات النشاطات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get activity types.
     */
    public function getActivityTypes(): JsonResponse
    {
        try {
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
            
            return response()->json([
                'success' => true,
                'data' => $types,
                'message' => 'تم جلب أنواع النشاطات بنجاح'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب أنواع النشاطات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear old activity logs.
     */
    public function clearOldLogs(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 90); // Default: keep logs for 90 days
            $cutoffDate = now()->subDays($days);
            
            $deletedCount = ActivityLog::where('created_at', '<', $cutoffDate)->delete();
            
            // Log this action
            ActivityLog::create([
                'user_id' => Auth::id(),
                'activity_type' => 'system_maintenance',
                'description' => "تم حذف {$deletedCount} سجل نشاط أقدم من {$days} يوم",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'details' => [
                    'deleted_count' => $deletedCount,
                    'cutoff_date' => $cutoffDate->toDateString(),
                    'retention_days' => $days
                ]
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'deleted_count' => $deletedCount,
                    'cutoff_date' => $cutoffDate->toDateString()
                ],
                'message' => "تم حذف {$deletedCount} سجل نشاط قديم بنجاح"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف السجلات القديمة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export activity logs.
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $format = $request->get('format', 'csv');
            $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->get('date_to', now()->format('Y-m-d'));
            
            $activities = ActivityLog::with('user')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Log the export action
            ActivityLog::create([
                'user_id' => Auth::id(),
                'activity_type' => 'data_exported',
                'description' => "تم تصدير {$activities->count()} سجل نشاط بصيغة {$format}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'details' => [
                    'export_format' => $format,
                    'records_count' => $activities->count(),
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo
                ]
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'activities' => $activities,
                    'export_info' => [
                        'format' => $format,
                        'filename' => 'activity_logs_' . date('Y-m-d_H-i-s') . '.' . $format,
                        'records_count' => $activities->count(),
                        'date_range' => [
                            'from' => $dateFrom,
                            'to' => $dateTo
                        ]
                    ]
                ],
                'message' => 'تم تصدير سجل النشاطات بنجاح'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تصدير سجل النشاطات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Log user activity (helper method).
     */
    public static function logActivity($activityType, $description, $details = null, $userId = null)
    {
        try {
            ActivityLog::create([
                'user_id' => $userId ?? Auth::id(),
                'activity_type' => $activityType,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details' => $details
            ]);
        } catch (\Exception $e) {
            // Log the error but don't throw exception to avoid breaking the main flow
            \Log::error('Failed to log activity: ' . $e->getMessage());
        }
    }
}

