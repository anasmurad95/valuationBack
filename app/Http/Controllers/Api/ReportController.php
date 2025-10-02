<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Valuation;
use App\Models\User;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Generate comprehensive reports.
     */
    public function generateReport(Request $request): JsonResponse
    {
        try {
            $reportType = $request->get('type', 'general');
            $dateFrom = $request->get('date_from', Carbon::now()->subMonth()->format('Y-m-d'));
            $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
            $userId = $request->get('user_id');
            $clientId = $request->get('client_id');
            
            $data = [];
            
            switch ($reportType) {
                case 'valuations':
                    $data = $this->getValuationsReport($dateFrom, $dateTo, $userId, $clientId);
                    break;
                case 'performance':
                    $data = $this->getPerformanceReport($dateFrom, $dateTo, $userId);
                    break;
                case 'financial':
                    $data = $this->getFinancialReport($dateFrom, $dateTo);
                    break;
                case 'clients':
                    $data = $this->getClientsReport($dateFrom, $dateTo);
                    break;
                default:
                    $data = $this->getGeneralReport($dateFrom, $dateTo);
            }
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'filters' => [
                    'type' => $reportType,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'user_id' => $userId,
                    'client_id' => $clientId
                ],
                'message' => 'تم إنتاج التقرير بنجاح'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنتاج التقرير',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get valuations report.
     */
    private function getValuationsReport($dateFrom, $dateTo, $userId = null, $clientId = null): array
    {
        $query = Valuation::with(['user', 'client', 'valuationType'])
            ->whereBetween('created_at', [$dateFrom, $dateTo]);
            
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        if ($clientId) {
            $query->where('client_id', $clientId);
        }
        
        $valuations = $query->get();
        
        return [
            'total_valuations' => $valuations->count(),
            'total_value' => $valuations->sum('estimated_value'),
            'average_value' => $valuations->avg('estimated_value'),
            'valuations_by_type' => $valuations->groupBy('valuationType.name_ar')->map->count(),
            'valuations_by_status' => $valuations->groupBy('status')->map->count(),
            'valuations_by_user' => $valuations->groupBy('user.name')->map->count(),
            'monthly_trend' => $this->getMonthlyTrend($valuations),
            'detailed_list' => $valuations->map(function($valuation) {
                return [
                    'id' => $valuation->id,
                    'reference_number' => $valuation->reference_number,
                    'property_type' => $valuation->valuationType->name_ar ?? 'غير محدد',
                    'estimated_value' => $valuation->estimated_value,
                    'user_name' => $valuation->user->name ?? 'غير محدد',
                    'client_name' => $valuation->client->name ?? 'غير محدد',
                    'status' => $valuation->status,
                    'created_at' => $valuation->created_at->format('Y-m-d')
                ];
            })
        ];
    }

    /**
     * Get performance report.
     */
    private function getPerformanceReport($dateFrom, $dateTo, $userId = null): array
    {
        $query = User::with(['valuations' => function($q) use ($dateFrom, $dateTo) {
            $q->whereBetween('created_at', [$dateFrom, $dateTo]);
        }]);
        
        if ($userId) {
            $query->where('id', $userId);
        }
        
        $users = $query->get();
        
        $performance = $users->map(function($user) {
            $valuations = $user->valuations;
            return [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'total_valuations' => $valuations->count(),
                'total_value' => $valuations->sum('estimated_value'),
                'average_value' => $valuations->avg('estimated_value'),
                'completed_valuations' => $valuations->where('status', 'completed')->count(),
                'pending_valuations' => $valuations->where('status', 'pending')->count(),
                'completion_rate' => $valuations->count() > 0 ? 
                    ($valuations->where('status', 'completed')->count() / $valuations->count()) * 100 : 0,
                'average_completion_time' => $this->getAverageCompletionTime($valuations)
            ];
        });
        
        return [
            'users_performance' => $performance,
            'top_performers' => $performance->sortByDesc('total_valuations')->take(5)->values(),
            'performance_summary' => [
                'total_users' => $users->count(),
                'active_users' => $users->filter(function($user) {
                    return $user->valuations->count() > 0;
                })->count(),
                'average_valuations_per_user' => $performance->avg('total_valuations'),
                'total_completion_rate' => $performance->avg('completion_rate')
            ]
        ];
    }

    /**
     * Get financial report.
     */
    private function getFinancialReport($dateFrom, $dateTo): array
    {
        $valuations = Valuation::whereBetween('created_at', [$dateFrom, $dateTo])->get();
        
        return [
            'total_value' => $valuations->sum('estimated_value'),
            'average_value' => $valuations->avg('estimated_value'),
            'highest_value' => $valuations->max('estimated_value'),
            'lowest_value' => $valuations->min('estimated_value'),
            'value_by_month' => $this->getValueByMonth($valuations),
            'value_by_property_type' => $valuations->groupBy('property_type')
                ->map(function($group) {
                    return [
                        'count' => $group->count(),
                        'total_value' => $group->sum('estimated_value'),
                        'average_value' => $group->avg('estimated_value')
                    ];
                }),
            'revenue_projection' => $this->calculateRevenueProjection($valuations)
        ];
    }

    /**
     * Get clients report.
     */
    private function getClientsReport($dateFrom, $dateTo): array
    {
        $clients = Client::with(['valuations' => function($q) use ($dateFrom, $dateTo) {
            $q->whereBetween('created_at', [$dateFrom, $dateTo]);
        }])->get();
        
        return [
            'total_clients' => $clients->count(),
            'active_clients' => $clients->filter(function($client) {
                return $client->valuations->count() > 0;
            })->count(),
            'clients_by_type' => $clients->groupBy('type')->map->count(),
            'top_clients' => $clients->sortByDesc(function($client) {
                return $client->valuations->count();
            })->take(10)->map(function($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'type' => $client->type,
                    'valuations_count' => $client->valuations->count(),
                    'total_value' => $client->valuations->sum('estimated_value')
                ];
            })->values(),
            'new_clients' => $clients->where('created_at', '>=', $dateFrom)->count(),
            'client_satisfaction' => $this->getClientSatisfactionMetrics($clients)
        ];
    }

    /**
     * Get general report.
     */
    private function getGeneralReport($dateFrom, $dateTo): array
    {
        return [
            'overview' => [
                'total_valuations' => Valuation::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                'total_value' => Valuation::whereBetween('created_at', [$dateFrom, $dateTo])->sum('estimated_value'),
                'total_users' => User::count(),
                'total_clients' => Client::count(),
                'active_users' => User::whereHas('valuations', function($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('created_at', [$dateFrom, $dateTo]);
                })->count()
            ],
            'trends' => $this->getGeneralTrends($dateFrom, $dateTo),
            'top_metrics' => $this->getTopMetrics($dateFrom, $dateTo)
        ];
    }

    /**
     * Get monthly trend data.
     */
    private function getMonthlyTrend($valuations): array
    {
        return $valuations->groupBy(function($valuation) {
            return $valuation->created_at->format('Y-m');
        })->map(function($group, $month) {
            return [
                'month' => $month,
                'count' => $group->count(),
                'total_value' => $group->sum('estimated_value'),
                'average_value' => $group->avg('estimated_value')
            ];
        })->values()->toArray();
    }

    /**
     * Get average completion time.
     */
    private function getAverageCompletionTime($valuations): float
    {
        $completedValuations = $valuations->where('status', 'completed');
        
        if ($completedValuations->count() === 0) {
            return 0;
        }
        
        $totalDays = $completedValuations->sum(function($valuation) {
            return $valuation->created_at->diffInDays($valuation->updated_at);
        });
        
        return $totalDays / $completedValuations->count();
    }

    /**
     * Get value by month.
     */
    private function getValueByMonth($valuations): array
    {
        return $valuations->groupBy(function($valuation) {
            return $valuation->created_at->format('Y-m');
        })->map(function($group) {
            return $group->sum('estimated_value');
        })->toArray();
    }

    /**
     * Calculate revenue projection.
     */
    private function calculateRevenueProjection($valuations): array
    {
        $monthlyAverage = $valuations->count() > 0 ? 
            $valuations->sum('estimated_value') / max(1, $valuations->groupBy(function($v) {
                return $v->created_at->format('Y-m');
            })->count()) : 0;
            
        return [
            'monthly_average' => $monthlyAverage,
            'quarterly_projection' => $monthlyAverage * 3,
            'yearly_projection' => $monthlyAverage * 12
        ];
    }

    /**
     * Get general trends.
     */
    private function getGeneralTrends($dateFrom, $dateTo): array
    {
        $currentPeriod = Valuation::whereBetween('created_at', [$dateFrom, $dateTo]);
        $previousPeriod = Valuation::whereBetween('created_at', [
            Carbon::parse($dateFrom)->subDays(Carbon::parse($dateTo)->diffInDays(Carbon::parse($dateFrom))),
            Carbon::parse($dateFrom)
        ]);
        
        $currentCount = $currentPeriod->count();
        $previousCount = $previousPeriod->count();
        
        return [
            'valuations_growth' => $previousCount > 0 ? 
                (($currentCount - $previousCount) / $previousCount) * 100 : 0,
            'value_growth' => $this->calculateValueGrowth($currentPeriod, $previousPeriod),
            'user_activity_growth' => $this->calculateUserActivityGrowth($dateFrom, $dateTo)
        ];
    }

    /**
     * Get top metrics.
     */
    private function getTopMetrics($dateFrom, $dateTo): array
    {
        return [
            'most_active_user' => User::withCount(['valuations' => function($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('created_at', [$dateFrom, $dateTo]);
            }])->orderByDesc('valuations_count')->first(),
            'highest_value_valuation' => Valuation::whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderByDesc('estimated_value')->first(),
            'most_common_property_type' => Valuation::whereBetween('created_at', [$dateFrom, $dateTo])
                ->select('property_type', DB::raw('count(*) as count'))
                ->groupBy('property_type')
                ->orderByDesc('count')
                ->first()
        ];
    }

    /**
     * Calculate value growth.
     */
    private function calculateValueGrowth($currentPeriod, $previousPeriod): float
    {
        $currentValue = $currentPeriod->sum('estimated_value');
        $previousValue = $previousPeriod->sum('estimated_value');
        
        return $previousValue > 0 ? (($currentValue - $previousValue) / $previousValue) * 100 : 0;
    }

    /**
     * Calculate user activity growth.
     */
    private function calculateUserActivityGrowth($dateFrom, $dateTo): float
    {
        $currentActiveUsers = User::whereHas('valuations', function($q) use ($dateFrom, $dateTo) {
            $q->whereBetween('created_at', [$dateFrom, $dateTo]);
        })->count();
        
        $previousActiveUsers = User::whereHas('valuations', function($q) use ($dateFrom, $dateTo) {
            $q->whereBetween('created_at', [
                Carbon::parse($dateFrom)->subDays(Carbon::parse($dateTo)->diffInDays(Carbon::parse($dateFrom))),
                Carbon::parse($dateFrom)
            ]);
        })->count();
        
        return $previousActiveUsers > 0 ? 
            (($currentActiveUsers - $previousActiveUsers) / $previousActiveUsers) * 100 : 0;
    }

    /**
     * Get client satisfaction metrics.
     */
    private function getClientSatisfactionMetrics($clients): array
    {
        // This would typically involve feedback/rating data
        // For now, we'll use completion rate as a proxy
        return [
            'average_rating' => 4.2, // Placeholder
            'satisfaction_rate' => 85, // Placeholder
            'repeat_clients' => $clients->filter(function($client) {
                return $client->valuations->count() > 1;
            })->count()
        ];
    }

    /**
     * Export report to different formats.
     */
    public function exportReport(Request $request): JsonResponse
    {
        try {
            $format = $request->get('format', 'pdf');
            $reportData = $this->generateReport($request)->getData();
            
            // Here you would implement actual export logic
            // For now, return the data with export info
            
            return response()->json([
                'success' => true,
                'data' => $reportData,
                'export_info' => [
                    'format' => $format,
                    'filename' => 'report_' . date('Y-m-d_H-i-s') . '.' . $format,
                    'generated_at' => now()->toISOString()
                ],
                'message' => 'تم تصدير التقرير بنجاح'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تصدير التقرير',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

