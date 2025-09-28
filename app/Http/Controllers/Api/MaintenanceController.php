<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Maintenance",
 *     description="API Endpoints for maintenance management"
 * )
 */
class MaintenanceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/maintenance/tasks",
     *     summary="Get maintenance tasks",
     *     tags={"Maintenance"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"pending", "in_progress", "completed", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="priority",
     *         in="query",
     *         description="Filter by priority",
     *         @OA\Schema(type="string", enum={"low", "medium", "high", "emergency"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Maintenance tasks list"
     *     )
     * )
     */
    public function getTasks(Request $request)
    {
        $user = Auth::user();
        $query = ServiceTask::with(['asset.site', 'assignedUser']);

        // Role-based filtering
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
            case 'veo_admin':
                // Can see all tasks
                break;
            default:
                return response()->json(['error' => 'Access denied'], 403);
        }

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        $tasks = $query->orderBy('created_at', 'desc')->get();

        return response()->json($tasks->map(function($task) {
            return $this->formatTask($task);
        }));
    }

    /**
     * @OA\Get(
     *     path="/api/maintenance/tasks/{id}",
     *     summary="Get maintenance task details",
     *     tags={"Maintenance"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task details"
     *     )
     * )
     */
    public function getTask($id)
    {
        $user = Auth::user();
        $task = ServiceTask::with(['asset.site', 'assignedUser'])->find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        if (!$this->canAccessTask($user, $task)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        return response()->json($this->formatTask($task, true));
    }

    /**
     * @OA\Put(
     *     path="/api/maintenance/tasks/{id}/status",
     *     summary="Update task status",
     *     tags={"Maintenance"},
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
     *             @OA\Property(property="status", type="string", enum={"pending", "in_progress", "completed", "cancelled"}),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="completion_notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task status updated"
     *     )
     * )
     */
    public function updateTaskStatus(Request $request, $id)
    {
        $user = Auth::user();
        $task = ServiceTask::find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        if (!$this->canModifyTask($user, $task)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
            'completion_notes' => 'nullable|string|max:1000',
        ]);

        $task->status = $request->status;

        if ($request->has('notes')) {
            $task->notes = $request->notes;
        }

        if ($request->status === 'completed') {
            $task->completed_at = now();
            $task->completion_notes = $request->completion_notes;

            // Auto-change asset status back to operational if it was in maintenance
            if ($task->asset && $task->asset->status === 'maintenance') {
                $task->asset->changeStatus('operational', $user->id, 'Maintenance completed');
            }
        }

        if ($request->status === 'in_progress' && !$task->started_at) {
            $task->started_at = now();
        }

        $task->save();

        return response()->json([
            'message' => 'Task status updated successfully',
            'task' => $this->formatTask($task->fresh())
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/maintenance/tasks",
     *     summary="Create maintenance task",
     *     tags={"Maintenance"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"asset_id", "title", "priority"},
     *             @OA\Property(property="asset_id", type="integer"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="priority", type="string", enum={"low", "medium", "high", "emergency"}),
     *             @OA\Property(property="due_date", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Task created successfully"
     *     )
     * )
     */
    public function createTask(Request $request)
    {
        $user = Auth::user();

        // Only admins and site managers can create tasks
        if (!in_array($user->role, ['veo_admin', 'site_manager'])) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'priority' => 'required|in:low,medium,high,emergency',
            'due_date' => 'nullable|date|after:today',
        ]);

        $task = ServiceTask::create([
            'asset_id' => $request->asset_id,
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => 'pending',
            'due_date' => $request->due_date,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Task created successfully',
            'task' => $this->formatTask($task->fresh())
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/maintenance/schedule",
     *     summary="Get maintenance schedule",
     *     tags={"Maintenance"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Specific date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Maintenance schedule"
     *     )
     * )
     */
    public function getSchedule(Request $request)
    {
        $user = Auth::user();
        $date = $request->get('date', now()->format('Y-m-d'));

        $query = ServiceTask::with(['asset.site', 'assignedUser'])
            ->whereDate('due_date', $date)
            ->where('status', '!=', 'completed');

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

        $tasks = $query->orderBy('due_date')->get();

        return response()->json([
            'date' => $date,
            'tasks' => $tasks->map(function($task) {
                return $this->formatTask($task);
            })
        ]);
    }

    private function canAccessTask($user, $task): bool
    {
        switch ($user->role) {
            case 'veo_admin':
                return true;
            case 'site_manager':
                return $task->asset && $task->asset->site_id === $user->site_id;
            case 'maintenance_staff':
                return $task->assigned_to === $user->id;
            default:
                return false;
        }
    }

    private function canModifyTask($user, $task): bool
    {
        switch ($user->role) {
            case 'veo_admin':
                return true;
            case 'maintenance_staff':
                return $task->assigned_to === $user->id;
            default:
                return false;
        }
    }

    private function formatTask($task, $detailed = false): array
    {
        $data = [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'priority' => $task->priority,
            'status' => $task->status,
            'due_date' => $task->due_date?->toDateString(),
            'created_at' => $task->created_at->toISOString(),
            'started_at' => $task->started_at?->toISOString(),
            'completed_at' => $task->completed_at?->toISOString(),
            'asset' => [
                'id' => $task->asset->id,
                'name' => $task->asset->name,
                'location' => $task->asset->location,
                'site' => [
                    'id' => $task->asset->site->id,
                    'name' => $task->asset->site->name,
                ],
            ],
            'assigned_user' => $task->assignedUser ? [
                'id' => $task->assignedUser->id,
                'name' => $task->assignedUser->name,
            ] : null,
        ];

        if ($detailed) {
            $data['notes'] = $task->notes;
            $data['completion_notes'] = $task->completion_notes;
        }

        return $data;
    }
}