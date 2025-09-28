<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MqttDevice;
use App\Models\Asset;
use App\Services\MqttService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;

class MqttDeviceController extends Controller
{
    private $mqttService;

    public function __construct(MqttService $mqttService)
    {
        $this->mqttService = $mqttService;
    }

    /**
     * Get all MQTT devices with pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = MqttDevice::with('asset:id,name,site_id');

        // Filter by asset
        if ($request->has('asset_id')) {
            $query->where('asset_id', $request->asset_id);
        }

        // Filter by device type
        if ($request->has('device_type')) {
            $query->where('device_type', $request->device_type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $devices = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $devices->items(),
            'pagination' => [
                'current_page' => $devices->currentPage(),
                'total_pages' => $devices->lastPage(),
                'total_items' => $devices->total(),
                'per_page' => $devices->perPage(),
            ],
        ]);
    }

    /**
     * Get specific MQTT device
     */
    public function show(string $deviceId): JsonResponse
    {
        $device = MqttDevice::with('asset:id,name,site_id')
            ->where('device_id', $deviceId)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $device,
        ]);
    }

    /**
     * Get device status summary
     */
    public function deviceStatus(): JsonResponse
    {
        $summary = [
            'total_devices' => MqttDevice::count(),
            'online_devices' => MqttDevice::online()->count(),
            'active_devices' => MqttDevice::active()->count(),
            'recently_active' => MqttDevice::recentlyActive()->count(),
            'by_type' => MqttDevice::selectRaw('device_type, COUNT(*) as count')
                ->groupBy('device_type')
                ->pluck('count', 'device_type'),
            'by_status' => MqttDevice::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get real-time asset data
     */
    public function realtimeData(int $assetId): JsonResponse
    {
        $performanceKey = "realtime:asset:{$assetId}:performance";
        $healthKey = "realtime:asset:{$assetId}:health";

        $performance = Redis::get($performanceKey);
        $health = Redis::get($healthKey);

        return response()->json([
            'success' => true,
            'data' => [
                'asset_id' => $assetId,
                'performance' => $performance ? json_decode($performance, true) : null,
                'health' => $health ? json_decode($health, true) : null,
                'last_updated' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get recent updates for dashboard
     */
    public function recentUpdates(Request $request): JsonResponse
    {
        $limit = min($request->get('limit', 10), 50);
        $updates = Redis::lrange('realtime:updates', 0, $limit - 1);

        $data = array_map(function ($update) {
            return json_decode($update, true);
        }, $updates);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Send command to IoT device
     */
    public function sendCommand(Request $request, int $assetId): JsonResponse
    {
        $request->validate([
            'command' => 'required|string',
            'parameters' => 'sometimes|array',
        ]);

        $asset = Asset::find($assetId);
        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found',
            ], 404);
        }

        $success = $this->mqttService->sendCommand(
            $assetId,
            $request->command,
            $request->get('parameters', [])
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Command sent successfully' : 'Failed to send command',
        ]);
    }

    /**
     * Request asset data update
     */
    public function requestUpdate(int $assetId): JsonResponse
    {
        $asset = Asset::find($assetId);
        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found',
            ], 404);
        }

        $success = $this->mqttService->requestAssetData($assetId);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Data request sent' : 'Failed to send data request',
        ]);
    }

    /**
     * Update device configuration
     */
    public function updateDevice(Request $request, string $deviceId): JsonResponse
    {
        $device = MqttDevice::where('device_id', $deviceId)->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found',
            ], 404);
        }

        $request->validate([
            'is_active' => 'sometimes|boolean',
            'notes' => 'sometimes|string|max:1000',
            'configuration' => 'sometimes|array',
        ]);

        $device->update($request->only(['is_active', 'notes', 'configuration']));

        return response()->json([
            'success' => true,
            'message' => 'Device updated successfully',
            'data' => $device->fresh(),
        ]);
    }

    /**
     * Get device health metrics
     */
    public function deviceHealth(string $deviceId): JsonResponse
    {
        $device = MqttDevice::where('device_id', $deviceId)->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found',
            ], 404);
        }

        $metrics = [
            'device_id' => $device->device_id,
            'is_online' => $device->is_online,
            'battery_status' => $device->battery_status,
            'battery_level' => $device->battery_level,
            'signal_status' => $device->signal_status,
            'signal_strength' => $device->signal_strength,
            'last_seen' => $device->last_seen,
            'last_seen_human' => $device->getLastSeenHuman(),
            'uptime_percentage' => $this->calculateUptime($device),
        ];

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Calculate device uptime percentage (last 24 hours)
     */
    private function calculateUptime(MqttDevice $device): float
    {
        if (!$device->last_seen) {
            return 0.0;
        }

        $hoursAgo24 = now()->subHours(24);
        $lastSeen = $device->last_seen;

        if ($lastSeen < $hoursAgo24) {
            return 0.0;
        }

        $totalMinutes = 24 * 60; // 24 hours in minutes
        $offlineMinutes = max(0, now()->diffInMinutes($lastSeen));

        return max(0, min(100, (($totalMinutes - $offlineMinutes) / $totalMinutes) * 100));
    }
}