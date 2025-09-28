<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="VEO Asset Management API",
 *     version="1.0.0",
 *     description="REST API for VEO Asset Management System mobile application",
 *     @OA\Contact(
 *         email="support@veoset.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="VEO Asset Management API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum authentication token"
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="role", type="string", enum={"veo_admin", "site_manager", "maintenance_staff", "customer"}, example="maintenance_staff"),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
 * )
 *
 * @OA\Schema(
 *     schema="Asset",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Generator Unit 01"),
 *     @OA\Property(property="asset_type", type="string", example="generator"),
 *     @OA\Property(property="status", type="string", enum={"operational", "maintenance", "emergency", "offline"}, example="operational"),
 *     @OA\Property(property="location", type="string", example="Building A - Floor 2"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="health_score", type="number", format="float", example=85.5),
 *     @OA\Property(property="health_status", type="string", enum={"excellent", "good", "fair", "poor", "critical"}, example="good"),
 *     @OA\Property(property="last_updated", type="string", format="date-time"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="site",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Main Facility")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="AssetDetail",
 *     type="object",
 *     @OA\Property(property="asset", ref="#/components/schemas/Asset"),
 *     @OA\Property(
 *         property="recent_history",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/AssetHistory")
 *     ),
 *     @OA\Property(
 *         property="health_trend",
 *         type="object",
 *         @OA\Property(property="trend", type="string", enum={"improving", "stable", "declining"}),
 *         @OA\Property(property="change", type="number")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="AssetHistory",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="event_type", type="string", enum={"performance_reading", "diagnostic_scan", "status_change"}, example="performance_reading"),
 *     @OA\Property(property="health_score", type="number", format="float", example=85.5),
 *     @OA\Property(property="performance_score", type="number", format="float", example=92.1),
 *     @OA\Property(property="temperature", type="number", format="float", example=45.2),
 *     @OA\Property(property="pressure", type="number", format="float", example=15.8),
 *     @OA\Property(property="vibration", type="number", format="float", example=2.1),
 *     @OA\Property(property="anomaly_detected", type="boolean", example=false),
 *     @OA\Property(property="anomaly_description", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */
abstract class Controller
{
    //
}
