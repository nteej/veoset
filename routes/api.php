<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('overview', [DashboardController::class, 'overview']);
        Route::get('statistics', [DashboardController::class, 'statistics']);
    });

    // Assets
    Route::prefix('assets')->group(function () {
        Route::get('/', [AssetController::class, 'index']);
        Route::get('{id}', [AssetController::class, 'show']);
        Route::put('{id}/status', [AssetController::class, 'updateStatus']);
        Route::get('{id}/health-history', [AssetController::class, 'healthHistory']);
    });

    // Maintenance
    Route::prefix('maintenance')->group(function () {
        Route::get('tasks', [MaintenanceController::class, 'getTasks']);
        Route::get('tasks/{id}', [MaintenanceController::class, 'getTask']);
        Route::put('tasks/{id}/status', [MaintenanceController::class, 'updateTaskStatus']);
        Route::post('tasks', [MaintenanceController::class, 'createTask']);
        Route::get('schedule', [MaintenanceController::class, 'getSchedule']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('summary', [NotificationController::class, 'summary']);
        Route::put('{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('{id}', [NotificationController::class, 'delete']);
    });

    // MQTT Device Management Routes
    Route::prefix('mqtt')->name('mqtt.')->group(function () {
        Route::get('devices', [App\Http\Controllers\Api\MqttDeviceController::class, 'index']);
        Route::get('devices/{deviceId}', [App\Http\Controllers\Api\MqttDeviceController::class, 'show']);
        Route::put('devices/{deviceId}', [App\Http\Controllers\Api\MqttDeviceController::class, 'updateDevice']);
        Route::get('devices/{deviceId}/health', [App\Http\Controllers\Api\MqttDeviceController::class, 'deviceHealth']);
        Route::get('device-status', [App\Http\Controllers\Api\MqttDeviceController::class, 'deviceStatus']);

        // Real-time data endpoints
        Route::get('realtime/{assetId}', [App\Http\Controllers\Api\MqttDeviceController::class, 'realtimeData']);
        Route::get('recent-updates', [App\Http\Controllers\Api\MqttDeviceController::class, 'recentUpdates']);

        // Device commands
        Route::post('assets/{assetId}/command', [App\Http\Controllers\Api\MqttDeviceController::class, 'sendCommand']);
        Route::post('assets/{assetId}/request-update', [App\Http\Controllers\Api\MqttDeviceController::class, 'requestUpdate']);
    });

    // Health check endpoint
    Route::get('health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0',
        ]);
    });
});