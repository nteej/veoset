<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\AssetMetadata;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_metadata_can_be_created()
    {
        $site = Site::factory()->create();
        $asset = Asset::factory()->create(['site_id' => $site->id]);

        $metadata = AssetMetadata::create([
            'asset_id' => $asset->id,
            'power_rating' => 1500.50,
            'voltage_level' => '11kV',
            'expected_lifespan_years' => 25,
            'efficiency_rating' => 92.5,
        ]);

        $this->assertInstanceOf(AssetMetadata::class, $metadata);
        $this->assertEquals($asset->id, $metadata->asset_id);
        $this->assertEquals(1500.50, $metadata->power_rating);
        $this->assertEquals('11kV', $metadata->voltage_level);
        $this->assertEquals(25, $metadata->expected_lifespan_years);
        $this->assertEquals(92.5, $metadata->efficiency_rating);
    }

    public function test_asset_metadata_belongs_to_asset()
    {
        $site = Site::factory()->create();
        $asset = Asset::factory()->create(['site_id' => $site->id, 'name' => 'Test Turbine']);
        $metadata = AssetMetadata::factory()->create(['asset_id' => $asset->id]);

        $this->assertEquals('Test Turbine', $metadata->asset->name);
    }

    public function test_asset_metadata_json_fields_are_cast_to_arrays()
    {
        $metadata = AssetMetadata::factory()->create([
            'specifications' => ['weight_kg' => 5000, 'material' => 'steel'],
            'maintenance_schedule' => ['interval_days' => 90, 'type' => 'preventive'],
            'performance_metrics' => ['efficiency_threshold' => 95.0],
            'safety_requirements' => ['ppe_required' => ['hard_hat', 'gloves']],
            'environmental_data' => ['max_temperature' => 80, 'ip_rating' => 'IP65'],
        ]);

        $this->assertIsArray($metadata->specifications);
        $this->assertIsArray($metadata->maintenance_schedule);
        $this->assertIsArray($metadata->performance_metrics);
        $this->assertIsArray($metadata->safety_requirements);
        $this->assertIsArray($metadata->environmental_data);

        $this->assertEquals(5000, $metadata->specifications['weight_kg']);
        $this->assertEquals(90, $metadata->maintenance_schedule['interval_days']);
        $this->assertEquals(95.0, $metadata->performance_metrics['efficiency_threshold']);
        $this->assertContains('hard_hat', $metadata->safety_requirements['ppe_required']);
        $this->assertEquals(80, $metadata->environmental_data['max_temperature']);
    }

    public function test_get_maintenance_interval_method()
    {
        $metadata = AssetMetadata::factory()->create([
            'maintenance_schedule' => ['interval_days' => 180, 'type' => 'predictive'],
        ]);

        $this->assertEquals(180, $metadata->getMaintenanceInterval());

        $metadataWithoutInterval = AssetMetadata::factory()->create([
            'maintenance_schedule' => ['type' => 'condition-based'],
        ]);

        $this->assertNull($metadataWithoutInterval->getMaintenanceInterval());
    }

    public function test_get_max_operating_temperature_method()
    {
        $metadata = AssetMetadata::factory()->create([
            'environmental_data' => ['max_temperature' => 85, 'min_temperature' => -10],
        ]);

        $this->assertEquals(85, $metadata->getMaxOperatingTemperature());

        $metadataWithoutTemp = AssetMetadata::factory()->create([
            'environmental_data' => ['humidity_range' => '10-90%'],
        ]);

        $this->assertNull($metadataWithoutTemp->getMaxOperatingTemperature());
    }

    public function test_get_performance_threshold_method()
    {
        $metadata = AssetMetadata::factory()->create([
            'performance_metrics' => [
                'efficiency_threshold' => 92.5,
                'vibration_limit' => 5.0,
                'max_power_output' => 1500,
            ],
        ]);

        $this->assertEquals(92.5, $metadata->getPerformanceThreshold('efficiency_threshold'));
        $this->assertEquals(5.0, $metadata->getPerformanceThreshold('vibration_limit'));
        $this->assertNull($metadata->getPerformanceThreshold('non_existent_metric'));
    }

    public function test_is_high_voltage_method()
    {
        $highVoltageMetadata = AssetMetadata::factory()->create(['voltage_level' => '11kV']);
        $mediumVoltageMetadata = AssetMetadata::factory()->create(['voltage_level' => '690V']);
        $lowVoltageMetadata = AssetMetadata::factory()->create(['voltage_level' => '400V']);
        $noVoltageMetadata = AssetMetadata::factory()->create(['voltage_level' => null]);

        $this->assertTrue($highVoltageMetadata->isHighVoltage());
        $this->assertFalse($mediumVoltageMetadata->isHighVoltage());
        $this->assertFalse($lowVoltageMetadata->isHighVoltage());
        $this->assertFalse($noVoltageMetadata->isHighVoltage());
    }

    public function test_voltage_level_parsing_handles_different_formats()
    {
        $metadata1 = AssetMetadata::factory()->create(['voltage_level' => '3.3kV']);
        $metadata2 = AssetMetadata::factory()->create(['voltage_level' => '33000V']);
        $metadata3 = AssetMetadata::factory()->create(['voltage_level' => '500V AC']);

        $this->assertTrue($metadata1->isHighVoltage()); // 3300V > 1000V
        $this->assertTrue($metadata2->isHighVoltage()); // 33000V > 1000V
        $this->assertFalse($metadata3->isHighVoltage()); // 500V < 1000V
    }

    public function test_decimal_fields_are_cast_properly()
    {
        $metadata = AssetMetadata::factory()->create([
            'power_rating' => '1500.75',
            'efficiency_rating' => '92.33',
        ]);

        $this->assertIsFloat($metadata->power_rating);
        $this->assertIsFloat($metadata->efficiency_rating);
        $this->assertEquals(1500.75, $metadata->power_rating);
        $this->assertEquals(92.33, $metadata->efficiency_rating);
    }

    public function test_asset_metadata_factory_creates_valid_metadata()
    {
        $metadata = AssetMetadata::factory()->create();

        $this->assertInstanceOf(AssetMetadata::class, $metadata);
        $this->assertInstanceOf(Asset::class, $metadata->asset);
        $this->assertIsArray($metadata->specifications);
        $this->assertIsArray($metadata->maintenance_schedule);
        $this->assertIsArray($metadata->performance_metrics);
        $this->assertIsArray($metadata->safety_requirements);
        $this->assertIsArray($metadata->environmental_data);
        $this->assertIsFloat($metadata->power_rating);
        $this->assertIsFloat($metadata->efficiency_rating);
        $this->assertIsInt($metadata->expected_lifespan_years);
    }

    public function test_complex_maintenance_schedule_data()
    {
        $metadata = AssetMetadata::factory()->create([
            'maintenance_schedule' => [
                'interval_days' => 90,
                'maintenance_type' => 'predictive',
                'required_tools' => ['multimeter', 'crane', 'safety_harness'],
                'estimated_duration_hours' => 8,
                'checklist' => [
                    'visual_inspection',
                    'electrical_testing',
                    'mechanical_checks',
                    'performance_validation',
                ],
            ],
        ]);

        $schedule = $metadata->maintenance_schedule;
        $this->assertEquals(90, $schedule['interval_days']);
        $this->assertEquals('predictive', $schedule['maintenance_type']);
        $this->assertContains('multimeter', $schedule['required_tools']);
        $this->assertEquals(8, $schedule['estimated_duration_hours']);
        $this->assertContains('visual_inspection', $schedule['checklist']);
    }

    public function test_deleting_asset_cascades_to_metadata()
    {
        $site = Site::factory()->create();
        $asset = Asset::factory()->create(['site_id' => $site->id]);
        $metadata = AssetMetadata::factory()->create(['asset_id' => $asset->id]);

        $this->assertDatabaseHas('asset_metadata', ['id' => $metadata->id]);

        $asset->delete();

        $this->assertDatabaseMissing('asset_metadata', ['id' => $metadata->id]);
    }
}