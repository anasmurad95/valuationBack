<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Valuation;
use App\Models\User;
use App\Models\Client;
use App\Models\ValuationTransfer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatisticsController extends Controller
{
    /**
     * Get overview statistics.
     */
    public function getOverview(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'month'); // month, quarter, year
            $dateRange = $this->getDateRange($period);
            
            $stats = [
                'kpis' => $this->getKPIs($dateRange),
                'trends' => $this->getTrends($dateRange),
                'distributions' => $this->getDistributions($dateRange),
                'performance_cards' => $this->getPerformanceCards($dateRange)
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'period' => $period,
                'date_range' => $dateRange,
                'message' => 'تم جلب الإحصائيات العامة بنجاح'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed statistics.
     */
    public function getDetailed(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'monthly'); // monthly, user_performance, ratings
            $dateRange = $this->getDateRange($request->get('period', 'year'));
            
            $data = [];
            
            switch ($type) {
                case 'monthly':
                    $data = $this->getMonthlyBreakdown($dateRange);
                    break;
                case 'user_performance':
                    $data = $this->getUserPerformance($dateRange);
                    break;
                case 'ratings':
                    $data = $this->getUserRatings($dateRange);
                    break;
                default:
                    $data = $this->getMonthlyBreakdown($dateRange);
            }
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'type' => $type,
                'message' => 'تم جلب الإحصائيات التفصيلية بنجاح'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات التفصيلية',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comparison statistics.
     */
    public function getComparison(Request $request): JsonResponse
    {
        try {
            $currentPeriod = $request->get('current_period', 'this_year');
            $comparisonPeriod = $request->get('comparison_period', 'last_year');
            
            $currentRange = $this->getDateRange($currentPeriod);
            $comparisonRange = $this->getDateRange($comparisonPeriod);
            
            $comparison = [
                'current_period' => [
                    'label' => $this->getPeriodLabel($currentPeriod),
                    'data' => $this->getPeriodStats($currentRange)
                ],
                'comparison_period' => [
                    'label' => $this->getPeriodLabel($comparisonPeriod),
                    'data' => $this->getPeriodStats($comparisonRange)
                ],
                'growth_metrics' => $this->calculateGrowthMetrics($currentRange, $comparisonRange),
                'performance_indicators' => $this->getPerformanceIndicators($currentRange, $comparisonRange)
            ];
            
            return response()->json([
                'success' => true,
                'data' => $comparison,
                'message' => 'تم جلب إحصائيات المقارنة بنجاح'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب إحصائيات المقارنة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Key Performance Indicators.
     */
    private function getKPIs($dateRange): array
    {
        $valuations = Valuation::whereBetween('created_at', $dateRange);
        $users = User::whereHas('valuations', function($q) use ($dateRange) {
            $q->whereBetween('created_at', $dateRange);
        });
        
        return [
            'total_valuations' => $valuations->count(),
            'total_value' => $valuations->sum('estimated_value'),
            'average_value' => $valuations->avg('estimated_value'),
            'active_users' => $users->count(),
            'completion_rate' => $this->getCompletionRate($dateRange),
            'average_completion_time' => $this->getAverageCompletionTime($dateRange),
            'client_satisfaction' => $this->getClientSatisfaction($dateRange),
            'revenue_growth' => $this->getRevenueGrowth($dateRange)
        ];
    }

    /**
     * Get trends data.
     */
    private function getTrends($dateRange): array
    {
        $monthlyData = Valuation::whereBetween('created_at', $dateRange)
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(estimated_value) as total_value'),
                DB::raw('AVG(estimated_value) as avg_value')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();
            
        return [
            'valuations_trend' => $monthlyData->pluck('count', 'month'),
            'value_trend' => $monthlyData->pluck('total_value', 'month'),
            'average_value_trend' => $monthlyData->pluck('avg_value', 'month'),
            'growth_rate' => $this->calculateGrowthRate($monthlyData)
        ];
    }

    /**
     * Get distributions.
     */
    private function getDistributions($dateRange): array
    {
        $valuations = Valuation::whereBetween('created_at', $dateRange);
        
        return [
            'property_types' => $valuations->select('property_type', DB::raw('count(*) as count'))
                ->groupBy('property_type')
                ->pluck('count', 'property_type'),
            'status_distribution' => $valuations->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),
            'value_ranges' => $this->getValueRangeDistribution($valuations),
            'geographical_distribution' => $this->getGeographicalDistribution($valuations)
        ];
    }

    /**
     * Get performance cards.
     */
    private function getPerformanceCards($dateRange): array
    {
        return [
            'best_performer' => $this->getBestPerformer($dateRange),
            'fastest_completion' => $this->getFastestCompletion($dateRange),
            'highest_satisfaction' => $this->getHighestSatisfaction($dateRange),
            'most_valuable_project' => $this->getMostValuableProject($dateRange)
        ];
    }

    /**
     * Get monthly breakdown.
     */
    private function getMonthlyBreakdown($dateRange): array
    {
        $monthlyStats = [];
        
        $start = Carbon::parse($dateRange[0]);
        $end = Carbon::parse($dateRange[1]);
        
        while ($start->lte($end)) {
            $monthStart = $start->copy()->startOfMonth();
            $monthEnd = $start->copy()->endOfMonth();
            
            $monthlyStats[] = [
                'month' => $start->format('Y-m'),
                'month_name' => $start->locale('ar')->monthName,
                'year' => $start->year,
                'valuations' => Valuation::whereBetween('created_at', [$monthStart, $monthEnd])->count(),
                'total_value' => Valuation::whereBetween('created_at', [$monthStart, $monthEnd])->sum('estimated_value'),
                'completed' => Valuation::whereBetween('created_at', [$monthStart, $monthEnd])
                    ->where('status', 'completed')->count(),
                'pending' => Valuation::whereBetween('created_at', [$monthStart, $monthEnd])
                    ->where('status', 'pending')->count(),
                'active_users' => User::whereHas('valuations', function($q) use ($monthStart, $monthEnd) {
                    $q->whereBetween('created_at', [$monthStart, $monthEnd]);
                })->count()
            ];
            
            $start->addMonth();
        }
        
        return $monthlyStats;
    }

    /**
     * Get user performance.
     */
    private function getUserPerformance($dateRange): array
    {
        return User::with(['valuations' => function($q) use ($dateRange) {
            $q->whereBetween('created_at', $dateRange);
        }])->get()->map(function($user) {
            $valuations = $user->valuations;
            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'total_valuations' => $valuations->count(),
                'completed_valuations' => $valuations->where('status', 'completed')->count(),
                'total_value' => $valuations->sum('estimated_value'),
                'average_value' => $valuations->avg('estimated_value'),
                'completion_rate' => $valuations->count() > 0 ? 
                    ($valuations->where('status', 'completed')->count() / $valuations->count()) * 100 : 0,
                'average_completion_time' => $this->getUserAverageCompletionTime($user->id, $dateRange),
                'performance_score' => $this->calculatePerformanceScore($user, $valuations)
            ];
        })->sortByDesc('performance_score')->values()->toArray();
    }

    /**
     * Get user ratings.
     */
    private function getUserRatings($dateRange): array
    {
        // This would typically come from a ratings/feedback system
        // For now, we'll calculate based on performance metrics
        return User::with(['valuations' => function($q) use ($dateRange) {
            $q->whereBetween('created_at', $dateRange);
        }])->get()->map(function($user) {
            $valuations = $user->valuations;
            $completionRate = $valuations->count() > 0 ? 
                ($valuations->where('status', 'completed')->count() / $valuations->count()) * 100 : 0;
                
            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'overall_rating' => min(5, max(1, ($completionRate / 20) + 1)), // Convert to 1-5 scale
                'quality_rating' => $this->calculateQualityRating($user, $valuations),
                'speed_rating' => $this->calculateSpeedRating($user, $valuations),
                'reliability_rating' => $this->calculateReliabilityRating($user, $valuations),
                'total_reviews' => $valuations->count(),
                'recommendation_score' => $this->calculateRecommendationScore($user, $valuations)
            ];
        })->sortByDesc('overall_rating')->values()->toArray();
    }

    /**
     * Get date range based on period.
     */
    private function getDateRange($period): array
    {
        $now = Carbon::now();
        
        switch ($period) {
            case 'week':
                return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()];
            case 'month':
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
            case 'quarter':
                return [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()];
            case 'year':
                return [$now->copy()->startOfYear(), $now->copy()->endOfYear()];
            case 'last_week':
                return [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()];
            case 'last_month':
                return [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()];
            case 'last_quarter':
                return [$now->copy()->subQuarter()->startOfQuarter(), $now->copy()->subQuarter()->endOfQuarter()];
            case 'last_year':
                return [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()];
            case 'this_year':
                return [$now->copy()->startOfYear(), $now->copy()->endOfYear()];
            default:
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
        }
    }

    /**
     * Get period label.
     */
    private function getPeriodLabel($period): string
    {
        $labels = [
            'week' => 'هذا الأسبوع',
            'month' => 'هذا الشهر',
            'quarter' => 'هذا الربع',
            'year' => 'هذا العام',
            'last_week' => 'الأسبوع الماضي',
            'last_month' => 'الشهر الماضي',
            'last_quarter' => 'الربع الماضي',
            'last_year' => 'العام الماضي',
            'this_year' => 'هذا العام'
        ];
        
        return $labels[$period] ?? $period;
    }

    /**
     * Get period statistics.
     */
    private function getPeriodStats($dateRange): array
    {
        $valuations = Valuation::whereBetween('created_at', $dateRange);
        
        return [
            'total_valuations' => $valuations->count(),
            'total_value' => $valuations->sum('estimated_value'),
            'average_value' => $valuations->avg('estimated_value'),
            'completed_valuations' => $valuations->where('status', 'completed')->count(),
            'active_users' => User::whereHas('valuations', function($q) use ($dateRange) {
                $q->whereBetween('created_at', $dateRange);
            })->count()
        ];
    }

    /**
     * Calculate growth metrics.
     */
    private function calculateGrowthMetrics($currentRange, $comparisonRange): array
    {
        $current = $this->getPeriodStats($currentRange);
        $comparison = $this->getPeriodStats($comparisonRange);
        
        return [
            'valuations_growth' => $this->calculatePercentageGrowth(
                $current['total_valuations'], 
                $comparison['total_valuations']
            ),
            'value_growth' => $this->calculatePercentageGrowth(
                $current['total_value'], 
                $comparison['total_value']
            ),
            'users_growth' => $this->calculatePercentageGrowth(
                $current['active_users'], 
                $comparison['active_users']
            ),
            'completion_growth' => $this->calculatePercentageGrowth(
                $current['completed_valuations'], 
                $comparison['completed_valuations']
            )
        ];
    }

    /**
     * Calculate percentage growth.
     */
    private function calculatePercentageGrowth($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return (($current - $previous) / $previous) * 100;
    }

    // Additional helper methods would be implemented here...
    // For brevity, I'm including placeholders for the remaining methods

    private function getCompletionRate($dateRange): float
    {
        $total = Valuation::whereBetween('created_at', $dateRange)->count();
        $completed = Valuation::whereBetween('created_at', $dateRange)
            ->where('status', 'completed')->count();
        
        return $total > 0 ? ($completed / $total) * 100 : 0;
    }

    private function getAverageCompletionTime($dateRange): float
    {
        // Implementation for average completion time
        return 5.2; // Placeholder
    }

    private function getClientSatisfaction($dateRange): float
    {
        // Implementation for client satisfaction
        return 4.3; // Placeholder
    }

    private function getRevenueGrowth($dateRange): float
    {
        // Implementation for revenue growth
        return 12.5; // Placeholder
    }

    private function calculateGrowthRate($monthlyData): array
    {
        // Implementation for growth rate calculation
        return []; // Placeholder
    }

    private function getValueRangeDistribution($valuations): array
    {
        // Implementation for value range distribution
        return []; // Placeholder
    }

    private function getGeographicalDistribution($valuations): array
    {
        // Implementation for geographical distribution
        return []; // Placeholder
    }

    private function getBestPerformer($dateRange): array
    {
        // Implementation for best performer
        return []; // Placeholder
    }

    private function getFastestCompletion($dateRange): array
    {
        // Implementation for fastest completion
        return []; // Placeholder
    }

    private function getHighestSatisfaction($dateRange): array
    {
        // Implementation for highest satisfaction
        return []; // Placeholder
    }

    private function getMostValuableProject($dateRange): array
    {
        // Implementation for most valuable project
        return []; // Placeholder
    }

    private function getUserAverageCompletionTime($userId, $dateRange): float
    {
        // Implementation for user average completion time
        return 0; // Placeholder
    }

    private function calculatePerformanceScore($user, $valuations): float
    {
        // Implementation for performance score calculation
        return 0; // Placeholder
    }

    private function calculateQualityRating($user, $valuations): float
    {
        // Implementation for quality rating
        return 0; // Placeholder
    }

    private function calculateSpeedRating($user, $valuations): float
    {
        // Implementation for speed rating
        return 0; // Placeholder
    }

    private function calculateReliabilityRating($user, $valuations): float
    {
        // Implementation for reliability rating
        return 0; // Placeholder
    }

    private function calculateRecommendationScore($user, $valuations): float
    {
        // Implementation for recommendation score
        return 0; // Placeholder
    }

    private function getPerformanceIndicators($currentRange, $comparisonRange): array
    {
        // Implementation for performance indicators
        return []; // Placeholder
    }
}

