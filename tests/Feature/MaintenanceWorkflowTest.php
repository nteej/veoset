<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetMetadata;
use App\Models\ServiceTask;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_preventive_maintenance_scheduling_workflow()
    {
        // Setup: Energy site with critical equipment
        $site = Site::factory()->create(['name' => 'Critical Power Station']);
        $manager = User::factory()->siteManager()->create();
        $technician = User::factory()->maintenanceStaff()->create();

        $turbine = Asset::factory()->create([
            'site_id' => $site->id,
            'name' => 'Main Wind Turbine',
            'asset_type' => 'turbine',
            'status' => 'operational',
        ]);

        $metadata = AssetMetadata::factory()->create([
            'asset_id' => $turbine->id,
            'maintenance_schedule' => [
                'interval_days' => 90,
                'maintenance_type' => 'preventive',
                'required_tools' => ['crane', 'torque_wrench', 'multimeter'],
                'estimated_duration_hours' => 12,
                'checklist' => [
                    'inspect_blades',
                    'check_bearings',
                    'test_electrical_systems',
                    'verify_safety_systems',
                ],
            ],
        ]);

        // 1. Schedule preventive maintenance
        $preventiveTask = ServiceTask::create([
            'asset_id' => $turbine->id,
            'assigned_to' => $technician->id,
            'title' => 'Quarterly Turbine Maintenance',
            'description' => 'Complete preventive maintenance as per schedule',
            'type' => 'preventive',
            'priority' => 'medium',
            'scheduled_date' => now()->addDays(7),
            'estimated_duration_hours' => 12,
            'required_tools' => ['crane', 'torque_wrench', 'multimeter'],
            'safety_requirements' => [
                'ppe_required' => ['hard_hat', 'safety_harness', 'steel_boots'],
                'lockout_procedures' => true,
                'working_at_height' => true,
                'weather_restrictions' => 'no_high_winds',
            ],
            'requires_shutdown' => true,
        ]);

        // 2. Verify task scheduling
        $this->assertEquals('preventive', $preventiveTask->type);
        $this->assertTrue($preventiveTask->requires_shutdown);
        $this->assertTrue($preventiveTask->isPending());
        $this->assertFalse($preventiveTask->isOverdue());

        // 3. Execute maintenance workflow
        $startResult = $preventiveTask->start();
        $this->assertTrue($startResult);
        $this->assertTrue($preventiveTask->fresh()->isInProgress());

        // 4. Complete maintenance with detailed notes
        $completionNotes = "Maintenance completed successfully:\n" .
                          "- Blade inspection: No damage found\n" .
                          "- Bearing lubrication: Completed\n" .
                          "- Electrical tests: All within specifications\n" .
                          "- Safety systems: Operational";

        $completeResult = $preventiveTask->complete($completionNotes);
        $this->assertTrue($completeResult);

        $completedTask = $preventiveTask->fresh();
        $this->assertTrue($completedTask->isCompleted());
        $this->assertStringContains('Maintenance completed successfully', $completedTask->completion_notes);

        // 5. Update asset maintenance dates
        $turbine->update([
            'last_maintenance_date' => now(),
            'next_maintenance_date' => now()->addDays($metadata->getMaintenanceInterval()),
        ]);

        $this->assertFalse($turbine->fresh()->needsMaintenance());
    }

    public function test_emergency_maintenance_workflow()
    {
        $site = Site::factory()->create();
        $emergencyTechnician = User::factory()->maintenanceStaff()->create();
        $supervisor = User::factory()->siteManager()->create();

        $generator = Asset::factory()->create([
            'site_id' => $site->id,
            'name' => 'Emergency Backup Generator',
            'asset_type' => 'generator',
            'status' => 'offline', // Emergency condition
        ]);

        // 1. Create emergency maintenance task
        $emergencyTask = ServiceTask::create([
            'asset_id' => $generator->id,
            'assigned_to' => $emergencyTechnician->id,
            'title' => 'Generator Failure - Emergency Repair',
            'description' => 'Generator failed to start during power outage. Investigate and repair immediately.',
            'type' => 'emergency',
            'priority' => 'critical',
            'scheduled_date' => now(), // Immediate
            'estimated_duration_hours' => 4,
            'required_tools' => ['multimeter', 'wrench_set', 'spare_parts'],
            'safety_requirements' => [
                'ppe_required' => ['hard_hat', 'safety_glasses', 'gloves'],
                'lockout_procedures' => true,
                'fuel_safety' => true,
            ],
            'requires_shutdown' => false, // Already offline
        ]);

        // 2. Verify emergency task properties
        $this->assertTrue($emergencyTask->isCritical());
        $this->assertEquals('emergency', $emergencyTask->type);
        $this->assertEquals('critical', $emergencyTask->priority);

        // 3. Start emergency repair
        $this->assertTrue($emergencyTechnician->canExecuteTasks());
        $emergencyTask->start();

        $this->assertTrue($emergencyTask->fresh()->isInProgress());

        // 4. Complete emergency repair
        $emergencyTask->complete('Faulty fuel pump replaced. Generator tested and operational.');

        // 5. Update asset status
        $generator->update(['status' => 'operational']);

        $this->assertTrue($generator->fresh()->isOperational());
        $this->assertTrue($emergencyTask->fresh()->isCompleted());
    }

    public function test_predictive_maintenance_workflow()
    {
        $site = Site::factory()->create();
        $analyst = User::factory()->siteManager()->create();
        $technician = User::factory()->maintenanceStaff()->create();

        $transformer = Asset::factory()->create([
            'site_id' => $site->id,
            'name' => 'Main Transformer',
            'asset_type' => 'transformer',
            'status' => 'operational',
        ]);

        $metadata = AssetMetadata::factory()->create([
            'asset_id' => $transformer->id,
            'performance_metrics' => [
                'temperature_threshold' => 80,
                'current_temperature' => 85, // Above threshold
                'vibration_limit' => 5.0,
                'current_vibration' => 6.2, // Above limit
                'oil_quality_threshold' => 0.8,
                'current_oil_quality' => 0.6, // Below threshold
            ],
        ]);

        // 1. Create predictive maintenance based on sensor data
        $predictiveTask = ServiceTask::create([
            'asset_id' => $transformer->id,
            'assigned_to' => $technician->id,
            'title' => 'Transformer Predictive Maintenance',
            'description' => 'Sensor data indicates potential issues: high temperature (85°C), excessive vibration (6.2), and degraded oil quality (0.6)',
            'type' => 'predictive',
            'priority' => 'high',
            'scheduled_date' => now()->addDays(2),
            'estimated_duration_hours' => 8,
            'required_tools' => ['thermal_camera', 'vibration_analyzer', 'oil_test_kit'],
            'safety_requirements' => [
                'ppe_required' => ['arc_flash_suit', 'insulated_gloves', 'face_shield'],
                'lockout_procedures' => true,
                'high_voltage_safety' => true,
            ],
            'requires_shutdown' => true,
        ]);

        // 2. Verify predictive maintenance scheduling
        $this->assertEquals('predictive', $predictiveTask->type);
        $this->assertEquals('high', $predictiveTask->priority);

        // 3. Verify performance metrics analysis
        $performanceMetrics = $metadata->performance_metrics;
        $this->assertGreaterThan($performanceMetrics['temperature_threshold'],
                                $performanceMetrics['current_temperature']);
        $this->assertGreaterThan($performanceMetrics['vibration_limit'],
                                $performanceMetrics['current_vibration']);
        $this->assertLessThan($performanceMetrics['oil_quality_threshold'],
                             $performanceMetrics['current_oil_quality']);

        // 4. Execute predictive maintenance
        $predictiveTask->start();
        $predictiveTask->complete('Temperature issue resolved by cleaning cooling fins. Vibration reduced after bearing adjustment. Oil replaced with fresh dielectric fluid.');

        // 5. Update performance metrics after maintenance
        $metadata->update([
            'performance_metrics' => array_merge($performanceMetrics, [
                'current_temperature' => 75,
                'current_vibration' => 3.5,
                'current_oil_quality' => 0.95,
            ]),
        ]);

        $updatedMetrics = $metadata->fresh()->performance_metrics;
        $this->assertLessThan($updatedMetrics['temperature_threshold'],
                             $updatedMetrics['current_temperature']);
        $this->assertLessThan($updatedMetrics['vibration_limit'],
                             $updatedMetrics['current_vibration']);
        $this->assertGreaterThan($updatedMetrics['oil_quality_threshold'],
                                $updatedMetrics['current_oil_quality']);
    }

    public function test_maintenance_scheduling_conflicts()
    {
        $site = Site::factory()->create();
        $technician = User::factory()->maintenanceStaff()->create();

        $asset1 = Asset::factory()->create(['site_id' => $site->id, 'name' => 'Turbine 1']);
        $asset2 = Asset::factory()->create(['site_id' => $site->id, 'name' => 'Turbine 2']);

        $scheduledDate = now()->addDays(5)->setHour(10)->setMinute(0);

        // 1. Schedule first maintenance task
        $task1 = ServiceTask::create([
            'asset_id' => $asset1->id,
            'assigned_to' => $technician->id,
            'title' => 'Turbine 1 Maintenance',
            'description' => 'Regular maintenance',
            'scheduled_date' => $scheduledDate,
            'estimated_duration_hours' => 6,
        ]);

        // 2. Try to schedule overlapping task for same technician
        $task2 = ServiceTask::create([
            'asset_id' => $asset2->id,
            'assigned_to' => $technician->id,
            'title' => 'Turbine 2 Maintenance',
            'description' => 'Regular maintenance',
            'scheduled_date' => $scheduledDate->copy()->addHours(3), // Overlaps with task1
            'estimated_duration_hours' => 4,
        ]);

        // 3. Check for scheduling conflicts (business logic)
        $technicianTasks = ServiceTask::where('assigned_to', $technician->id)
            ->whereBetween('scheduled_date', [
                $scheduledDate->copy()->subHours(6),
                $scheduledDate->copy()->addHours(6)
            ])
            ->get();

        $this->assertCount(2, $technicianTasks);

        // 4. Verify both tasks exist but would need conflict resolution
        $this->assertTrue($technicianTasks->contains($task1));
        $this->assertTrue($technicianTasks->contains($task2));
    }

    public function test_maintenance_requiring_shutdown()
    {
        $site = Site::factory()->create();
        $manager = User::factory()->siteManager()->create();
        $technician = User::factory()->maintenanceStaff()->create();

        // Create multiple interconnected assets
        $mainTransformer = Asset::factory()->create([
            'site_id' => $site->id,
            'name' => 'Main Transformer',
            'asset_type' => 'transformer',
            'status' => 'operational',
        ]);

        $generator1 = Asset::factory()->create([
            'site_id' => $site->id,
            'name' => 'Generator Unit 1',
            'asset_type' => 'generator',
            'status' => 'operational',
        ]);

        $generator2 = Asset::factory()->create([
            'site_id' => $site->id,
            'name' => 'Generator Unit 2',
            'asset_type' => 'generator',
            'status' => 'operational',
        ]);

        // 1. Schedule maintenance requiring shutdown
        $shutdownTask = ServiceTask::create([
            'asset_id' => $mainTransformer->id,
            'assigned_to' => $technician->id,
            'title' => 'Main Transformer Overhaul',
            'description' => 'Complete transformer maintenance requiring full site shutdown',
            'type' => 'preventive',
            'priority' => 'high',
            'scheduled_date' => now()->addDays(14), // Plan ahead
            'estimated_duration_hours' => 24,
            'required_tools' => ['crane', 'oil_processing_unit', 'testing_equipment'],
            'safety_requirements' => [
                'site_isolation' => true,
                'ppe_required' => ['arc_flash_suit', 'insulated_tools'],
                'lockout_procedures' => true,
            ],
            'requires_shutdown' => true,
        ]);

        // 2. Verify shutdown requirements
        $this->assertTrue($shutdownTask->requires_shutdown);

        // 3. Query tasks requiring shutdown
        $shutdownTasks = ServiceTask::requiresShutdown()->get();
        $this->assertCount(1, $shutdownTasks);
        $this->assertTrue($shutdownTasks->contains($shutdownTask));

        // 4. Simulate coordinated shutdown
        $shutdownTask->start();

        // In real scenario, this would trigger shutdown of dependent assets
        $affectedAssets = Asset::where('site_id', $site->id)
            ->where('id', '!=', $mainTransformer->id)
            ->get();

        foreach ($affectedAssets as $asset) {
            // Simulate putting assets in standby mode
            $this->assertEquals('operational', $asset->status);
            // In practice: $asset->update(['mode' => 'standby']);
        }

        // 5. Complete maintenance and restore operations
        $shutdownTask->complete('Transformer maintenance completed. All systems restored to operational status.');

        $this->assertTrue($shutdownTask->fresh()->isCompleted());
    }

    public function test_maintenance_task_lifecycle_with_status_transitions()
    {
        $technician = User::factory()->maintenanceStaff()->create();
        $asset = Asset::factory()->create();

        $task = ServiceTask::factory()->create([
            'asset_id' => $asset->id,
            'assigned_to' => $technician->id,
            'status' => 'pending',
        ]);

        // 1. Initial state
        $this->assertTrue($task->isPending());
        $this->assertFalse($task->isInProgress());
        $this->assertFalse($task->isCompleted());

        // 2. Start task
        $this->assertTrue($task->start());
        $task = $task->fresh();
        $this->assertTrue($task->isInProgress());
        $this->assertNotNull($task->started_at);

        // 3. Cannot start again
        $this->assertFalse($task->start());

        // 4. Complete task
        $this->assertTrue($task->complete('Task completed successfully'));
        $task = $task->fresh();
        $this->assertTrue($task->isCompleted());
        $this->assertNotNull($task->completed_at);
        $this->assertNotNull($task->actual_duration_hours);

        // 5. Cannot complete again
        $this->assertFalse($task->complete('Should not work'));

        // 6. Verify duration calculation
        $this->assertIsInt($task->duration);
        $this->assertGreaterThanOrEqual(0, $task->duration);
    }
}