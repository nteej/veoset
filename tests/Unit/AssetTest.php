<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_can_be_created_with_required_fields()
    {
        $site = Site::factory()->create();

        $asset = Asset::create([
            'site_id' => $site->id,
            'name' => 'Test Turbine',
            'asset_type' => 'turbine',
        ]);

        $asset = $asset->fresh(); // Refresh from database to get defaults
        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertEquals('Test Turbine', $asset->name);
        $this->assertEquals('turbine', $asset->asset_type);
        $this->assertEquals('operational', $asset->status); // Default
        $this->assertEquals('auto', $asset->mode); // Default
        $this->assertTrue($asset->is_active); // Default
    }

    public function test_asset_belongs_to_site()
    {
        $site = Site::factory()->create(['name' => 'Energy Site']);
        $asset = Asset::factory()->create(['site_id' => $site->id]);

        $this->assertEquals('Energy Site', $asset->site->name);
    }

    public function test_asset_can_have_complete_information()
    {
        $site = Site::factory()->create();

        $asset = Asset::create([
            'site_id' => $site->id,
            'name' => 'Main Generator',
            'description' => 'Primary power generation unit',
            'asset_type' => 'generator',
            'model' => 'GEN-5000X',
            'manufacturer' => 'Energy Corp',
            'serial_number' => 'SN123456789',
            'installation_date' => '2020-01-15',
            'last_maintenance_date' => '2024-06-01',
            'next_maintenance_date' => '2024-12-01',
            'status' => 'operational',
            'mode' => 'auto',
            'is_active' => true,
        ]);

        $this->assertEquals('Main Generator', $asset->name);
        $this->assertEquals('Primary power generation unit', $asset->description);
        $this->assertEquals('generator', $asset->asset_type);
        $this->assertEquals('GEN-5000X', $asset->model);
        $this->assertEquals('Energy Corp', $asset->manufacturer);
        $this->assertEquals('SN123456789', $asset->serial_number);
        $this->assertEquals('2020-01-15', $asset->installation_date->format('Y-m-d'));
        $this->assertEquals('2024-06-01', $asset->last_maintenance_date->format('Y-m-d'));
        $this->assertEquals('2024-12-01', $asset->next_maintenance_date->format('Y-m-d'));
    }

    public function test_asset_operational_scope()
    {
        $site = Site::factory()->create();
        Asset::factory()->create(['site_id' => $site->id, 'status' => 'operational']);
        Asset::factory()->create(['site_id' => $site->id, 'status' => 'maintenance']);
        Asset::factory()->create(['site_id' => $site->id, 'status' => 'offline']);

        $operationalAssets = Asset::operational()->get();

        $this->assertCount(1, $operationalAssets);
        $this->assertEquals('operational', $operationalAssets->first()->status);
    }

    public function test_asset_active_scope()
    {
        $site = Site::factory()->create();
        Asset::factory()->create(['site_id' => $site->id, 'is_active' => true]);
        Asset::factory()->create(['site_id' => $site->id, 'is_active' => false]);

        $activeAssets = Asset::active()->get();

        $this->assertCount(1, $activeAssets);
        $this->assertTrue($activeAssets->first()->is_active);
    }

    public function test_asset_by_type_scope()
    {
        $site = Site::factory()->create();
        Asset::factory()->create(['site_id' => $site->id, 'asset_type' => 'turbine']);
        Asset::factory()->create(['site_id' => $site->id, 'asset_type' => 'generator']);
        Asset::factory()->create(['site_id' => $site->id, 'asset_type' => 'turbine']);

        $turbines = Asset::byType('turbine')->get();

        $this->assertCount(2, $turbines);
        $turbines->each(function ($asset) {
            $this->assertEquals('turbine', $asset->asset_type);
        });
    }

    public function test_asset_needs_maintenance_scope()
    {
        $site = Site::factory()->create();

        // Asset that needs maintenance (past due)
        $pastDue = Asset::factory()->create([
            'site_id' => $site->id,
            'next_maintenance_date' => now()->subDays(5),
        ]);

        // Asset that needs maintenance today
        $dueToday = Asset::factory()->create([
            'site_id' => $site->id,
            'next_maintenance_date' => now(),
        ]);

        // Asset with future maintenance
        Asset::factory()->create([
            'site_id' => $site->id,
            'next_maintenance_date' => now()->addDays(30),
        ]);

        $needsMaintenance = Asset::needsMaintenance()->get();

        $this->assertCount(2, $needsMaintenance);
        $this->assertTrue($needsMaintenance->contains($pastDue));
        $this->assertTrue($needsMaintenance->contains($dueToday));
    }

    public function test_asset_is_operational_method()
    {
        $operationalAsset = Asset::factory()->make(['status' => 'operational']);
        $maintenanceAsset = Asset::factory()->make(['status' => 'maintenance']);

        $this->assertTrue($operationalAsset->isOperational());
        $this->assertFalse($maintenanceAsset->isOperational());
    }

    public function test_asset_needs_maintenance_method()
    {
        $needsMaintenanceAsset = Asset::factory()->make([
            'next_maintenance_date' => now()->subDays(1),
        ]);

        $futureMaintenanceAsset = Asset::factory()->make([
            'next_maintenance_date' => now()->addDays(30),
        ]);

        $noMaintenanceDateAsset = Asset::factory()->make([
            'next_maintenance_date' => null,
        ]);

        $this->assertTrue($needsMaintenanceAsset->needsMaintenance());
        $this->assertFalse($futureMaintenanceAsset->needsMaintenance());
        $this->assertFalse($noMaintenanceDateAsset->needsMaintenance());
    }

    public function test_asset_factory_creates_valid_asset()
    {
        $asset = Asset::factory()->create();

        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertNotEmpty($asset->name);
        $this->assertNotEmpty($asset->asset_type);
        $this->assertInstanceOf(Site::class, $asset->site);
        $this->assertContains($asset->status, ['operational', 'maintenance', 'offline', 'decommissioned']);
        $this->assertContains($asset->mode, ['auto', 'manual', 'standby']);
    }

    public function test_asset_dates_are_cast_properly()
    {
        $asset = Asset::factory()->create([
            'installation_date' => '2020-01-15',
            'last_maintenance_date' => '2024-06-01',
            'next_maintenance_date' => '2024-12-01',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $asset->installation_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $asset->last_maintenance_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $asset->next_maintenance_date);
    }

    public function test_deleting_site_cascades_to_assets()
    {
        $site = Site::factory()->create();
        $asset = Asset::factory()->create(['site_id' => $site->id]);

        $this->assertDatabaseHas('assets', ['id' => $asset->id]);

        $site->delete();

        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
    }
}