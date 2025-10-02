<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Valuation;
use App\Models\User;
use App\Models\Role;
use App\Models\ValuationTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * جلب إحصائيات الداشبورد
     */
    public function getStats()
    {
        try {
            $stats = [
                'totalValuations' => Valuation::count(),
                'totalUsers' => User::where('is_active', true)->count(),
                'pendingValuations' => Valuation::where('status', 'pending')->count(),
                'pendingTransfers' => ValuationTransfer::where('status', 'pending')->count(),
                'completedValuations' => Valuation::where('status', 'completed')->count(),
                'rejectedValuations' => Valuation::where('status', 'rejected')->count(),
            ];

            // إحصائيات إضافية
            $stats['monthlyValuations'] = $this->getMonthlyValuations();
            $stats['propertyTypesDistribution'] = $this->getPropertyTypesDistribution();
            $stats['usersByRole'] = $this->getUsersByRole();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في جلب إحصائيات الداشبورد',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب الأدوار مع عدد المستخدمين
     */
    public function getRoles()
    {
        try {
            $roles = Role::withCount('users')
                ->orderBy('level', 'desc')
                ->get()
                ->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name_ar' => $role->display_name_ar,
                        'display_name_en' => $role->display_name_en,
                        'level' => $role->level,
                        'users_count' => $role->users_count,
                        'description' => $role->description,
                        'is_active' => $role->is_active
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $roles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في جلب الأدوار',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب التحويلات الأخيرة
     */
    public function getRecentTransfers()
    {
        try {
            $transfers = ValuationTransfer::with([
                'valuation:id,reference_number,property_address',
                'fromUser:id,name',
                'toUser:id,name'
            ])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($transfer) {
                return [
                    'id' => $transfer->id,
                    'valuation_title' => $transfer->valuation->reference_number . ' - ' . $transfer->valuation->property_address,
                    'from_user_name' => $transfer->fromUser->name,
                    'to_user_name' => $transfer->toUser->name,
                    'status' => $transfer->status,
                    'reason' => $transfer->reason,
                    'created_at' => $transfer->created_at->format('Y-m-d H:i'),
                    'updated_at' => $transfer->updated_at->format('Y-m-d H:i')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transfers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في جلب التحويلات الأخيرة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب إحصائيات التقييمات الشهرية
     */
    private function getMonthlyValuations()
    {
        return Valuation::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('YEAR(created_at) as year'),
            DB::raw('COUNT(*) as count')
        )
        ->where('created_at', '>=', now()->subMonths(12))
        ->groupBy('year', 'month')
        ->orderBy('year', 'desc')
        ->orderBy('month', 'desc')
        ->get()
        ->map(function ($item) {
            return [
                'month' => $item->month,
                'year' => $item->year,
                'count' => $item->count,
                'label' => $this->getMonthName($item->month) . ' ' . $item->year
            ];
        });
    }

    /**
     * جلب توزيع أنواع العقارات
     */
    private function getPropertyTypesDistribution()
    {
        return Valuation::select('property_type', DB::raw('COUNT(*) as count'))
            ->groupBy('property_type')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->property_type,
                    'count' => $item->count,
                    'label' => $this->getPropertyTypeLabel($item->property_type)
                ];
            });
    }

    /**
     * جلب المستخدمين حسب الأدوار
     */
    private function getUsersByRole()
    {
        return Role::withCount('users')
            ->get()
            ->map(function ($role) {
                return [
                    'role' => $role->display_name_ar,
                    'count' => $role->users_count,
                    'level' => $role->level
                ];
            });
    }

    /**
     * الحصول على اسم الشهر بالعربية
     */
    private function getMonthName($month)
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
        ];
        
        return $months[$month] ?? 'غير محدد';
    }

    /**
     * الحصول على تسمية نوع العقار
     */
    private function getPropertyTypeLabel($type)
    {
        $types = [
            'residential_land' => 'أرض سكنية',
            'commercial_land' => 'أرض تجارية',
            'industrial_land' => 'أرض صناعية',
            'villa' => 'فيلا',
            'apartment' => 'شقة',
            'commercial_building' => 'مبنى تجاري',
            'warehouse' => 'مستودع',
            'office' => 'مكتب',
            'shop' => 'محل تجاري',
            'farm' => 'مزرعة',
            'rest_house' => 'استراحة',
            'other' => 'أخرى'
        ];

        return $types[$type] ?? $type;
    }

    /**
     * جلب التقييمات حسب الحالة
     */
    public function getValuationsByStatus()
    {
        try {
            $statusCounts = Valuation::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status');

            $statuses = [
                'draft' => 'مسودة',
                'pending' => 'معلق',
                'in_progress' => 'قيد التنفيذ',
                'completed' => 'مكتمل',
                'rejected' => 'مرفوض'
            ];

            $result = [];
            foreach ($statuses as $key => $label) {
                $result[] = [
                    'status' => $key,
                    'label' => $label,
                    'count' => $statusCounts[$key] ?? 0
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في جلب التقييمات حسب الحالة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب أحدث النشاطات
     */
    public function getRecentActivities()
    {
        try {
            $activities = [];

            // أحدث التقييمات
            $recentValuations = Valuation::with('user:id,name')
                ->latest()
                ->limit(5)
                ->get();

            foreach ($recentValuations as $valuation) {
                $activities[] = [
                    'type' => 'valuation_created',
                    'title' => 'تقييم جديد',
                    'description' => "تم إنشاء تقييم جديد: {$valuation->reference_number}",
                    'user' => $valuation->user->name,
                    'created_at' => $valuation->created_at->format('Y-m-d H:i')
                ];
            }

            // أحدث التحويلات
            $recentTransfers = ValuationTransfer::with(['fromUser:id,name', 'toUser:id,name'])
                ->latest()
                ->limit(5)
                ->get();

            foreach ($recentTransfers as $transfer) {
                $activities[] = [
                    'type' => 'transfer_created',
                    'title' => 'تحويل تقييم',
                    'description' => "تم تحويل تقييم من {$transfer->fromUser->name} إلى {$transfer->toUser->name}",
                    'user' => $transfer->fromUser->name,
                    'created_at' => $transfer->created_at->format('Y-m-d H:i')
                ];
            }

            // ترتيب النشاطات حسب التاريخ
            usort($activities, function ($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            return response()->json([
                'success' => true,
                'data' => array_slice($activities, 0, 10)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في جلب النشاطات الأخيرة',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

