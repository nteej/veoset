<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Assets",
 *     description="API Endpoints for asset management"
 * )
 */
class AssetController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/assets",
     *     summary="Get assets list",
     *     tags={"Assets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="site_id",
     *         in="query",
     *         description="Filter by site ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"operational", "maintenance", "emergency", "offline"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assets list",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Asset")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Asset::with(['site', 'latestHistory']);

        // Role-based filtering
        switch ($user->role) {
            case 'site_manager':
                if ($user->site_id) {
                    $query->where('site_id', $user->site_id);
                }
                break;
            case 'maintenance_staff':
                // Show assets assigned to this technician or in their site
                $query->where(function($q) use ($user) {
                    $q->whereHas('serviceTasks', function($subQ) use ($user) {
                        $subQ->where('assigned_to', $user->id)
                             ->where('status', '!=', 'completed');
                    })->orWhere('site_id', $user->site_id ?? 0);
                });
                break;
            case 'customer':
                // Customers only see assets they own
                $query->where('customer_id', $user->id);
                break;
        }

        // Apply filters
        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $assets = $query->get()->map(function ($asset) {
            return $this->formatAsset($asset);
        });

        return response()->json($assets);
    }

    /**
     * @OA\Get(
     *     path="/api/assets/{id}",
     *     summary="Get asset details",
     *     tags={"Assets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Asset details",
     *         @OA\JsonContent(ref="#/components/schemas/AssetDetail")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Asset not found"
     *     )
     * )
     */
    public function show($id)
    {
        $user = Auth::user();
        $asset = Asset::with(['site', 'latestHistory'])->find($id);

        if (!$asset) {
            return response()->json(['error' => 'Asset not found'], 404);
        }

        // Check permissions
        if (!$this->canAccessAsset($user, $asset)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $recentHistory = AssetHistory::where('asset_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'asset' => $this->formatAsset($asset),
            'recent_history' => $recentHistory->map(function ($history) {
                return $this->formatHistory($history);
            }),
            'health_trend' => $this->getHealthTrend($id),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/assets/{id}/status",
     *     summary="Update asset status",
     *     tags={"Assets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"operational", "maintenance", "emergency", "offline"}),
     *             @OA\Property(property="notes", type="string", example="Routine maintenance completed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status updated successfully"
     *     )
     * )
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        $asset = Asset::find($id);

        if (!$asset) {
            return response()->json(['error' => 'Asset not found'], 404);
        }

        // Only maintenance staff and admins can update status
        if (!in_array($user->role, ['veo_admin', 'maintenance_staff'])) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $request->validate([
            'status' => 'required|in:operational,maintenance,emergency,offline',
            'notes' => 'nullable|string|max:1000',
        ]);

        $previousStatus = $asset->status;
        $asset->changeStatus($request->status, $user->id, $request->notes);

        return response()->json([
            'message' => 'Asset status updated successfully',
            'asset' => $this->formatAsset($asset->fresh())
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/assets/{id}/health-history",
     *     summary="Get asset health history",
     *     tags={"Assets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="Number of days to look back",
     *         @OA\Schema(type="integer", default=7)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Health history data"
     *     )
     * )
     */
    public function healthHistory(Request $request, $id)
    {
        $user = Auth::user();
        $asset = Asset::find($id);

        if (!$asset || !$this->canAccessAsset($user, $asset)) {
            return response()->json(['error' => 'Asset not found or access denied'], 404);
        }

        $days = $request->get('days', 7);

        $history = AssetHistory::where('asset_id', $id)
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('health_score')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($record) {
                return [
                    'timestamp' => $record->created_at->toISOString(),
                    'health_score' => $record->health_score,
                    'performance_score' => $record->performance_score,
                    'temperature' => $record->temperature,
                    'pressure' => $record->pressure,
                    'vibration' => $record->vibration,
                ];
            });

        return response()->json($history);
    }

    private function canAccessAsset($user, $asset): bool
    {
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

    private function formatAsset($asset): array
    {
        return [
            'id' => $asset->id,
            'name' => $asset->name,
            'asset_type' => $asset->asset_type,
            'status' => $asset->status,
            'location' => $asset->location,
            'is_active' => $asset->is_active,
            'site' => [
                'id' => $asset->site->id,
                'name' => $asset->site->name,
            ],
            'health_score' => $asset->latestHistory?->health_score,
            'health_status' => $asset->latestHistory ?
                AssetHistory::getHealthStatusFromScore($asset->latestHistory->health_score) : 'unknown',
            'last_updated' => $asset->latestHistory?->created_at?->toISOString(),
            'created_at' => $asset->created_at->toISOString(),
        ];
    }

    private function formatHistory($history): array
    {
        return [
            'id' => $history->id,
            'event_type' => $history->event_type,
            'health_score' => $history->health_score,
            'performance_score' => $history->performance_score,
            'temperature' => $history->temperature,
            'pressure' => $history->pressure,
            'vibration' => $history->vibration,
            'anomaly_detected' => $history->anomaly_detected,
            'anomaly_description' => $history->anomaly_description,
            'created_at' => $history->created_at->toISOString(),
        ];
    }

    private function getHealthTrend($assetId): array
    {
        $data = AssetHistory::where('asset_id', $assetId)
            ->whereNotNull('health_score')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->pluck('health_score', 'created_at')
            ->reverse();

        if ($data->count() < 2) {
            return ['trend' => 'stable', 'change' => 0];
        }

        $recent = $data->take(-5)->avg();
        $previous = $data->take(-10)->skip(-10)->take(5)->avg();

        $change = $recent - $previous;

        if (abs($change) < 2) {
            $trend = 'stable';
        } elseif ($change > 0) {
            $trend = 'improving';
        } else {
            $trend = 'declining';
        }

        return [
            'trend' => $trend,
            'change' => round($change, 1),
        ];
    }
}