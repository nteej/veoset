<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Notifications",
 *     description="API Endpoints for notification management"
 * )
 */
class NotificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     summary="Get user notifications",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="unread_only",
     *         in="query",
     *         description="Show only unread notifications",
     *         @OA\Schema(type="boolean", default=false)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Limit number of notifications",
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notifications list"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = $user->notifications();

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $limit = min($request->get('limit', 50), 100);
        $notifications = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'notifications' => $notifications->map(function($notification) {
                return $this->formatNotification($notification);
            }),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/notifications/{id}/read",
     *     summary="Mark notification as read",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification marked as read"
     *     )
     * )
     */
    public function markAsRead($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $this->formatNotification($notification)
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/notifications/read-all",
     *     summary="Mark all notifications as read",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="All notifications marked as read"
     *     )
     * )
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        $count = $user->unreadNotifications()->count();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read',
            'marked_count' => $count
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/summary",
     *     summary="Get notifications summary",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Notifications summary"
     *     )
     * )
     */
    public function summary()
    {
        $user = Auth::user();

        $summary = [
            'total_unread' => $user->unreadNotifications()->count(),
            'critical_alerts' => $user->unreadNotifications()
                ->where('type', 'LIKE', '%CriticalHealth%')
                ->count(),
            'maintenance_updates' => $user->unreadNotifications()
                ->where('type', 'LIKE', '%Maintenance%')
                ->count(),
            'recent_activity' => $user->notifications()
                ->where('created_at', '>=', now()->subHours(24))
                ->count(),
        ];

        // Get latest critical notification
        $latestCritical = $user->unreadNotifications()
            ->where('type', 'LIKE', '%CriticalHealth%')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestCritical) {
            $summary['latest_critical'] = $this->formatNotification($latestCritical);
        }

        return response()->json($summary);
    }

    /**
     * @OA\Delete(
     *     path="/api/notifications/{id}",
     *     summary="Delete notification",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification deleted"
     *     )
     * )
     */
    public function delete($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully'
        ]);
    }

    private function formatNotification($notification): array
    {
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'type' => $this->getNotificationType($notification->type),
            'title' => $data['title'] ?? 'Notification',
            'body' => $data['body'] ?? $data['message'] ?? '',
            'data' => $data,
            'is_read' => $notification->read_at !== null,
            'created_at' => $notification->created_at->toISOString(),
            'read_at' => $notification->read_at?->toISOString(),
            'priority' => $this->getNotificationPriority($notification->type, $data),
        ];
    }

    private function getNotificationType($className): string
    {
        $typeMap = [
            'App\\Notifications\\CriticalHealthAlert' => 'critical_health',
            'App\\Notifications\\MaintenanceNotification' => 'maintenance',
            'App\\Notifications\\AssetStatusChanged' => 'asset_status',
            'App\\Notifications\\TaskAssigned' => 'task_assignment',
            'App\\Notifications\\ScheduleReminder' => 'schedule_reminder',
        ];

        return $typeMap[$className] ?? 'general';
    }

    private function getNotificationPriority($type, $data): string
    {
        // Determine priority based on notification type and content
        if (str_contains($type, 'CriticalHealth')) {
            return 'high';
        }

        if (str_contains($type, 'Emergency')) {
            return 'high';
        }

        if (isset($data['priority'])) {
            return $data['priority'];
        }

        if (str_contains($type, 'Maintenance')) {
            return 'medium';
        }

        return 'low';
    }
}