<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetMetadata;
use App\Models\ServiceTask;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetManagementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_asset_lifecycle_workflow()
    {
        // 1. Create a site with VEO Admin
        $admin = User::factory()->veoAdmin()->create();
        $site = Site::factory()->create([
            'name' => 'Main Energy Site',
            'location' => 'Houston, TX',
        ]);

        // 2. Add assets to the site
        $turbine = Asset::factory()->create([
            'site_id' => $site->id,
            'name' => 'Wind Turbine 1',
            'asset_type' => 'turbine',
            'status' => 'operational',
            'installation_date' => now()->subYears(2),
            'next_maintenance_date' => now()->addDays(30),
        ]);

        $generator = Asset::factory()->create([
            'site_id' => $site->id,
            'name' => 'Backup Generator',
            'asset_type' => 'generator',
            'status' => 'operational',
        ]);

        // 3. Add detailed metadata for critical assets
        $turbineMetadata = AssetMetadata::factory()->create([
            'asset_id' => $turbine->id,
            'power_rating' => 2500.00,
            'voltage_level' => '11kV',
            'expected_lifespan_years' => 25,
            'maintenance_schedule' => [
                'interval_days' => 90,
                'maintenance_type' => 'preventive',
                'required_tools' => ['crane', 'multimeter', 'safety_harness'],
                'estimated_duration_hours' => 8,
            ],
            'safety_requirements' => [
                'ppe_required' => ['hard_hat', 'safety_glasses', 'gloves', 'harness'],
                'lockout_procedures' => true,
                'working_at_height' => true,
            ],
        ]);

        // 4. Verify site has multiple assets
        $this->assertCount(2, $site->assets);
        $this->assertTrue($site->assets->contains($turbine));
        $this->assertTrue($site->assets->contains($generator));

        // 5. Verify asset has metadata
        $this->assertInstanceOf(AssetMetadata::class, $turbine->metadata);
        $this->assertEquals(2500.00, $turbine->metadata->power_rating);
        $this->assertTrue($turbine->metadata->isHighVoltage());

        // 6. Verify business rules
        $this->assertTrue($turbine->isOperational());
        $this->assertFalse($turbine->needsMaintenance()); // 30 days in future
        $this->assertEquals(90, $turbine->metadata->getMaintenanceInterval());

        $this->assertTrue($admin->canManageAssets());
        $this->assertTrue($admin->canViewAssets());
    }

    public function test_maintenance_scheduling_workflow()
    {
        // Setup: Site Manager creates maintenance schedule
        $siteManager = User::factory()->siteManager()->create();
        $maintenanceStaff = User::factory()->maintenanceStaff()->create();

        $site = Site::factory()->create();
        $asset = Asset::factory()->create([
            'site_id' => $site->id,
            'name' => 'Solar Panel Array',
            'asset_type' => 'solar_panel',
            'next_maintenance_date' => now()->subDays(5), // Overdue
        ]);

        // 1. Site Manager can manage assets and create maintenance tasks
        $this->assertTrue($siteManager->canManageAssets());
        $this->assertTrue($siteManager->canExecuteTasks());

        // 2. Create overdue maintenance task
        $maintenanceTask = ServiceTask::factory()->create([
            'asset_id' => $asset->id,
            'assigned_to' => $maintenanceStaff->id,
            'title' => 'Solar Panel Cleaning and Inspection',
            'type' => 'preventive',
            'priority' => 'high',
            'status' => 'pending',
            'scheduled_date' => now()->subDays(3),
            'estimated_duration_hours' => 4,
            'required_tools' => ['cleaning_equipment', 'multimeter', 'safety_harness'],
            'requires_shutdown' => false,
        ]);

        // 3. Verify asset needs maintenance
        $this->assertTrue($asset->needsMaintenance());
        $this->assertTrue($maintenanceTask->isOverdue());
        $this->assertTrue($maintenanceTask->isPending());

        // 4. Maintenance staff starts the task
        $this->assertTrue($maintenanceStaff->canExecuteTasks());
        $result = $maintenanceTask->start();

        $this->assertTrue($result);
        $this->assertTrue($maintenanceTask->fresh()->isInProgress());
        $this->assertNotNull($maintenanceTask->fresh()->started_at);

        // 5. Complete the maintenance
        $completionResult = $maintenanceTask->complete('Panels cleaned, all systems operational');

        $this->assertTrue($completionResult);
        $this->assertTrue($maintenanceTask->fresh()->isCompleted());
        $this->assertNotNull($maintenanceTask->fresh()->completed_at);
        $this->assertEquals('Panels cleaned, all systems operational',
                          $maintenanceTask->fresh()->completion_notes);

        // 6. Update asset maintenance dates
        $asset->update([
            'last_maintenance_date' => now(),
            'next_maintenance_date' => now()->addDays(90),
        ]);

        $this->assertFalse($asset->fresh()->needsMaintenance());
    }

    public function test_asset_performance_monitoring_workflow()
    {
        $site = Site::factory()->create(['name' => 'Wind Farm Alpha']);

        // Create multiple turbines with different statuses
        $operationalTurbines = Asset::factory()->count(3)->create([
            'site_id' => $site->id,
            'asset_type' => 'turbine',
            'status' => 'operational',
        ]);

        $maintenanceTurbine = Asset::factory()->create([
            'site_id' => $site->id,
            'asset_type' => 'turbine',
            'status' => 'maintenance',
        ]);

        $offlineTurbine = Asset::factory()->create([
            'site_id' => $site->id,
            'asset_type' => 'turbine',
            'status' => 'offline',
        ]);

        // Add performance metadata
        foreach ($operationalTurbines as $turbine) {
            AssetMetadata::factory()->create([
                'asset_id' => $turbine->id,
                'performance_metrics' => [
                    'efficiency_threshold' => 92.0,
                    'current_efficiency' => fake()->randomFloat(2, 85, 98),
                    'power_output' => fake()->numberBetween(2000, 2500),
                    'availability' => fake()->randomFloat(2, 95, 99.9),
                ],
            ]);
        }

        // Business Intelligence Queries
        $totalAssets = $site->assets()->count();
        $operationalAssets = $site->assets()->operational()->count();
        $turbines = $site->assets()->byType('turbine')->count();
        $activeAssets = $site->activeAssets()->count();

        $this->assertEquals(5, $totalAssets);
        $this->assertEquals(3, $operationalAssets);
        $this->assertEquals(5, $turbines);
        $this->assertEquals(5, $activeAssets); // All are active by default

        // Performance analysis
        $operationalTurbinesCollection = $site->assets()
            ->operational()
            ->byType('turbine')
            ->with('metadata')
            ->get();

        $this->assertCount(3, $operationalTurbinesCollection);

        foreach ($operationalTurbinesCollection as $turbine) {
            $this->assertTrue($turbine->isOperational());
            $this->assertNotNull($turbine->metadata);
            $this->assertArrayHasKey('efficiency_threshold',
                                   $turbine->metadata->performance_metrics);
        }
    }

    public function test_multi_site_asset_management()
    {
        $admin = User::factory()->veoAdmin()->create();

        // Create multiple sites
        $sites = Site::factory()->count(3)->create();

        foreach ($sites as $index => $site) {
            // Each site has different types and quantities of assets
            Asset::factory()->count($index + 2)->create([
                'site_id' => $site->id,
                'asset_type' => fake()->randomElement(['turbine', 'transformer', 'generator']),
            ]);
        }

        // Query across all sites
        $totalAssetsAcrossAllSites = Asset::count();
        $activeSites = Site::active()->count();
        $allSiteAssets = Site::with(['assets'])->get();

        $this->assertEquals(9, $totalAssetsAcrossAllSites); // 2+3+4 = 9
        $this->assertEquals(3, $activeSites);
        $this->assertEquals(3, $allSiteAssets->count());

        // Verify admin can manage all sites
        $this->assertTrue($admin->canManageAssets());
        $this->assertTrue($admin->canViewAssets());

        // Test site-specific queries
        foreach ($allSiteAssets as $site) {
            $this->assertGreaterThan(0, $site->assets->count());

            foreach ($site->assets as $asset) {
                $this->assertEquals($site->id, $asset->site_id);
                $this->assertInstanceOf(Site::class, $asset->site);
            }
        }
    }

    public function test_role_based_access_workflow()
    {
        $site = Site::factory()->create();
        $asset = Asset::factory()->create(['site_id' => $site->id]);

        $admin = User::factory()->veoAdmin()->create();
        $manager = User::factory()->siteManager()->create();
        $staff = User::factory()->maintenanceStaff()->create();
        $customer = User::factory()->customer()->create();

        // All users can view assets
        $this->assertTrue($admin->canViewAssets());
        $this->assertTrue($manager->canViewAssets());
        $this->assertTrue($staff->canViewAssets());
        $this->assertTrue($customer->canViewAssets());

        // Only admin and managers can manage assets
        $this->assertTrue($admin->canManageAssets());
        $this->assertTrue($manager->canManageAssets());
        $this->assertFalse($staff->canManageAssets());
        $this->assertFalse($customer->canManageAssets());

        // Admin, managers, and staff can execute tasks
        $this->assertTrue($admin->canExecuteTasks());
        $this->assertTrue($manager->canExecuteTasks());
        $this->assertTrue($staff->canExecuteTasks());
        $this->assertFalse($customer->canExecuteTasks());

        // Create tasks for different roles
        $emergencyTask = ServiceTask::factory()->create([
            'asset_id' => $asset->id,
            'assigned_to' => $staff->id,
            'type' => 'emergency',
            'priority' => 'critical',
        ]);

        $this->assertTrue($emergencyTask->isCritical());
        $this->assertEquals($staff->id, $emergencyTask->assigned_to);
        $this->assertInstanceOf(User::class, $emergencyTask->assignedUser);
    }

    public function test_asset_metadata_complex_queries()
    {
        $site = Site::factory()->create();

        // Create high-voltage assets
        $highVoltageAssets = Asset::factory()->count(2)->create([
            'site_id' => $site->id,
        ]);

        foreach ($highVoltageAssets as $asset) {
            AssetMetadata::factory()->create([
                'asset_id' => $asset->id,
                'voltage_level' => '33kV',
                'power_rating' => 5000.00,
            ]);
        }

        // Create low-voltage assets
        $lowVoltageAssets = Asset::factory()->count(3)->create([
            'site_id' => $site->id,
        ]);

        foreach ($lowVoltageAssets as $asset) {
            AssetMetadata::factory()->create([
                'asset_id' => $asset->id,
                'voltage_level' => '400V',
                'power_rating' => 100.00,
            ]);
        }

        // Query high-voltage assets
        $highVoltageCount = Asset::whereHas('metadata', function ($query) {
            $query->where('voltage_level', 'like', '%kV');
        })->count();

        $this->assertEquals(2, $highVoltageCount);

        // Verify voltage classification
        $allAssetsWithMetadata = Asset::with('metadata')->get();

        $highVoltageAssetsVerified = $allAssetsWithMetadata->filter(function ($asset) {
            return $asset->metadata && $asset->metadata->isHighVoltage();
        });

        $lowVoltageAssetsVerified = $allAssetsWithMetadata->filter(function ($asset) {
            return $asset->metadata && !$asset->metadata->isHighVoltage();
        });

        $this->assertCount(2, $highVoltageAssetsVerified);
        $this->assertCount(3, $lowVoltageAssetsVerified);
    }
}