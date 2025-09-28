<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\ServiceTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Dashboard",
 *     description="API Endpoints for dashboard data"
 * )
 */
class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/dashboard/overview",
     *     summary="Get dashboard overview",
     *     tags={"Dashboard"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard overview data"
     *     )
     * )
     */
    public function overview()
    {
        $user = Auth::user();

        $data = [
            'assets' => $this->getAssetSummary($user),
            'maintenance' => $this->getMaintenanceSummary($user),
            'health' => $this->getHealthSummary($user),
            'recent_activity' => $this->getRecentActivity($user),
        ];

        // Role-specific data
        switch ($user->role) {
            case 'maintenance_staff':
                $data['my_tasks'] = $this->getMyTasks($user);
                $data['today_schedule'] = $this->getTodaySchedule($user);
                break;
            case 'site_manager':
                $data['site_performance'] = $this->getSitePerformance($user);
                break;
            case 'veo_admin':
                $data['system_alerts'] = $this->getSystemAlerts();
                $data['performance_metrics'] = $this->getPerformanceMetrics();
                break;
        }

        return response()->json($data);
    }

    /**
     * @OA\Get(
     *     path="/api/dashboard/statistics",
     *     summary="Get dashboard statistics",
     *     tags={"Dashboard"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period",
     *         @OA\Schema(type="string", enum={"today", "week", "month"}, default="week")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard statistics"
     *     )
     * )
     */
    public function statistics(Request $request)
    {
        $user = Auth::user();
        $period = $request->get('period', 'week');

        $dateRange = $this->getDateRange($period);

        return response()->json([
            'period' => $period,
            'date_range' => $dateRange,
            'asset_status_distribution' => $this->getAssetStatusDistribution($user),
            'health_trend' => $this->getHealthTrend($user, $dateRange),
            'maintenance_completion' => $this->getMaintenanceCompletion($user, $dateRange),
            'performance_trends' => $this->getPerformanceTrends($user, $dateRange),
        ]);
    }

    private function getAssetSummary($user): array
    {
        $query = Asset::query();

        // Apply role-based filtering
        $this->applyRoleFilter($query, $user);

        $total = $query->count();
        $operational = (clone $query)->where('status', 'operational')->count();
        $maintenance = (clone $query)->where('status', 'maintenance')->count();
        $emergency = (clone $query)->where('status', 'emergency')->count();
        $offline = (clone $query)->where('status', 'offline')->count();

        return [
            'total' => $total,
            'operational' => $operational,
            'maintenance' => $maintenance,
            'emergency' => $emergency,
            'offline' => $offline,
            'operational_percentage' => $total > 0 ? round(($operational / $total) * 100, 1) : 0,
        ];
    }

    private function getMaintenanceSummary($user): array
    {
        $query = ServiceTask::query();

        // Apply role-based filtering
        switch ($user->role) {
            case 'maintenance_staff':
                $query->where('assigned_to', $user->id);
                break;
            case 'site_manager':
                if ($user->site_id) {
                    $query->whereHas('asset', function($q) use ($user) {
                        $q->where('site_id', $user->site_id);
                    });
                }
                break;
        }

        $total = $query->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $inProgress = (clone $query)->where('status', 'in_progress')->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $overdue = (clone $query)->where('due_date', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled'])->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'overdue' => $overdue,
        ];
    }

    private function getHealthSummary($user): array
    {
        $query = AssetHistory::query()
            ->select('asset_id', DB::raw('AVG(health_score) as avg_health'))
            ->where('created_at', '>=', now()->subHours(24))
            ->whereNotNull('health_score')
            ->groupBy('asset_id');

        // Apply role-based filtering
        switch ($user->role) {
            case 'site_manager':
                if ($user->site_id) {
                    $query->whereHas('asset', function($q) use ($user) {
                        $q->where('site_id', $user->site_id);
                    });
                }
                break;
            case 'maintenance_staff':
                $query->whereHas('asset', function($q) use ($user) {
                    $q->where('site_id', $user->site_id ?? 0)
                      ->orWhereHas('serviceTasks', function($subQ) use ($user) {
                          $subQ->where('assigned_to', $user->id);
                      });
                });
                break;
        }

        $healthData = $query->get();

        $excellent = $healthData->where('avg_health', '>=', 85)->count();
        $good = $healthData->whereBetween('avg_health', [70, 84])->count();
        $fair = $healthData->whereBetween('avg_health', [50, 69])->count();
        $poor = $healthData->where('avg_health', '<', 50)->count();

        $avgHealth = $healthData->avg('avg_health') ?? 0;

        return [
            'average_health' => round($avgHealth, 1),
            'excellent' => $excellent,
            'good' => $good,
            'fair' => $fair,
            'poor' => $poor,
            'total_assets_monitored' => $healthData->count(),
        ];
    }

    private function getRecentActivity($user): array
    {
        $activities = collect();

        // Recent status changes
        $statusChanges = AssetHistory::with('asset')
            ->where('event_type', 'status_change')
            ->where('created_at', '>=', now()->subDays(3))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        foreach ($statusChanges as $change) {
            if ($this->canUserAccessAsset($user, $change->asset)) {
                $activities->push([
                    'type' => 'status_change',
                    'title' => "Asset status changed",
                    'description' => "{$change->asset->name} changed to {$change->new_status}",
                    'timestamp' => $change->created_at->toISOString(),
                    'asset_id' => $change->asset_id,
                ]);
            }
        }

        // Recent maintenance completions
        $completedTasks = ServiceTask::with('asset')
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subDays(3))
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($completedTasks as $task) {
            if ($this->canUserAccessAsset($user, $task->asset)) {
                $activities->push([
                    'type' => 'maintenance_completed',
                    'title' => "Maintenance completed",
                    'description' => "Task '{$task->title}' completed for {$task->asset->name}",
                    'timestamp' => $task->completed_at->toISOString(),
                    'asset_id' => $task->asset_id,
                ]);
            }
        }

        return $activities->sortByDesc('timestamp')->take(10)->values()->toArray();
    }

    private function getMyTasks($user): array
    {
        $tasks = ServiceTask::with('asset')
            ->where('assigned_to', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        return $tasks->map(function($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'asset_name' => $task->asset->name,
                'priority' => $task->priority,
                'due_date' => $task->due_date?->toDateString(),
                'status' => $task->status,
            ];
        })->toArray();
    }

    private function getTodaySchedule($user): array
    {
        $tasks = ServiceTask::with('asset')
            ->where('assigned_to', $user->id)
            ->whereDate('due_date', now())
            ->orderBy('due_date')
            ->get();

        return $tasks->map(function($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'asset_name' => $task->asset->name,
                'priority' => $task->priority,
                'status' => $task->status,
                'due_time' => $task->due_date?->format('H:i'),
            ];
        })->toArray();
    }

    private function getSitePerformance($user): array
    {
        if (!$user->site_id) {
            return [];
        }

        $assets = Asset::where('site_id', $user->site_id)->pluck('id');

        $avgHealth = AssetHistory::whereIn('asset_id', $assets)
            ->where('created_at', '>=', now()->subDay())
            ->whereNotNull('health_score')
            ->avg('health_score') ?? 0;

        $criticalAssets = AssetHistory::whereIn('asset_id', $assets)
            ->where('created_at', '>=', now()->subHours(2))
            ->whereNotNull('health_score')
            ->groupBy('asset_id')
            ->havingRaw('AVG(health_score) < 30')
            ->count();

        return [
            'average_health' => round($avgHealth, 1),
            'critical_assets' => $criticalAssets,
            'total_assets' => $assets->count(),
        ];
    }

    private function getSystemAlerts(): array
    {
        // System-wide alerts for VEO admins
        return [
            'critical_health_count' => AssetHistory::where('created_at', '>=', now()->subHours(2))
                ->whereNotNull('health_score')
                ->groupBy('asset_id')
                ->havingRaw('AVG(health_score) < 20')
                ->count(),
            'overdue_tasks' => ServiceTask::where('due_date', '<', now())
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count(),
            'offline_assets' => Asset::where('status', 'offline')->count(),
        ];
    }

    private function getPerformanceMetrics(): array
    {
        $today = now()->startOfDay();

        return [
            'readings_today' => AssetHistory::where('created_at', '>=', $today)->count(),
            'tasks_completed_today' => ServiceTask::where('completed_at', '>=', $today)->count(),
            'avg_response_time' => 15.2, // Mock data - could be calculated from actual metrics
            'system_uptime' => 99.8, // Mock data
        ];
    }

    private function applyRoleFilter($query, $user)
    {
        switch ($user->role) {
            case 'site_manager':
                if ($user->site_id) {
                    $query->where('site_id', $user->site_id);
                }
                break;
            case 'maintenance_staff':
                $query->where(function($q) use ($user) {
                    $q->where('site_id', $user->site_id ?? 0)
                      ->orWhereHas('serviceTasks', function($subQ) use ($user) {
                          $subQ->where('assigned_to', $user->id);
                      });
                });
                break;
            case 'customer':
                $query->where('customer_id', $user->id);
                break;
        }
    }

    private function canUserAccessAsset($user, $asset): bool
    {
        if (!$asset) return false;

        switch ($user->role) {
            case 'veo_admin':
                return true;
            case 'site_manager':
                return $asset->site_id === $user->site_id;
            case 'maintenance_staff':
                return $asset->site_id === $user->site_id ||
                       $asset->serviceTasks()->where('assigned_to', $user->id)->exists();
            case 'customer':
                return $asset->customer_id === $user->id;
            default:
                return false;
        }
    }

    private function getDateRange($period): array
    {
        switch ($period) {
            case 'today':
                return [
                    'start' => now()->startOfDay()->toISOString(),
                    'end' => now()->endOfDay()->toISOString(),
                ];
            case 'month':
                return [
                    'start' => now()->startOfMonth()->toISOString(),
                    'end' => now()->endOfMonth()->toISOString(),
                ];
            default: // week
                return [
                    'start' => now()->startOfWeek()->toISOString(),
                    'end' => now()->endOfWeek()->toISOString(),
                ];
        }
    }

    private function getAssetStatusDistribution($user): array
    {
        $query = Asset::query();
        $this->applyRoleFilter($query, $user);

        return $query->groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status')
            ->toArray();
    }

    private function getHealthTrend($user, $dateRange): array
    {
        $query = AssetHistory::whereBetween('created_at', [
            $dateRange['start'], $dateRange['end']
        ])->whereNotNull('health_score');

        // Apply role filtering if needed
        switch ($user->role) {
            case 'site_manager':
                if ($user->site_id) {
                    $query->whereHas('asset', function($q) use ($user) {
                        $q->where('site_id', $user->site_id);
                    });
                }
                break;
        }

        return $query->selectRaw('DATE(created_at) as date, AVG(health_score) as avg_health')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                return [
                    'date' => $item->date,
                    'avg_health' => round($item->avg_health, 1),
                ];
            })->toArray();
    }

    private function getMaintenanceCompletion($user, $dateRange): array
    {
        $query = ServiceTask::whereBetween('created_at', [
            $dateRange['start'], $dateRange['end']
        ]);

        switch ($user->role) {
            case 'maintenance_staff':
                $query->where('assigned_to', $user->id);
                break;
            case 'site_manager':
                if ($user->site_id) {
                    $query->whereHas('asset', function($q) use ($user) {
                        $q->where('site_id', $user->site_id);
                    });
                }
                break;
        }

        $total = $query->count();
        $completed = (clone $query)->where('status', 'completed')->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];
    }

    private function getPerformanceTrends($user, $dateRange): array
    {
        // This could be expanded with more specific performance metrics
        return [
            'avg_response_time' => 12.5,
            'task_completion_time' => 4.2,
            'uptime_percentage' => 99.5,
        ];
    }
}