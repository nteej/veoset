<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetMetadata;
use App\Models\ServiceTask;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class VEOsetSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'VEO Admin',
            'email' => 'admin@veoset.com',
            'password' => Hash::make('password'),
            'role' => 'veo_admin',
        ]);

        // Create test users
        $siteManager = User::factory()->siteManager()->create([
            'name' => 'John Site Manager',
            'email' => 'manager@veoset.com',
        ]);

        $technician1 = User::factory()->maintenanceStaff()->create([
            'name' => 'Mike Technician',
            'email' => 'mike@veoset.com',
        ]);

        $technician2 = User::factory()->maintenanceStaff()->create([
            'name' => 'Sarah Technician',
            'email' => 'sarah@veoset.com',
        ]);

        $customer = User::factory()->customer()->create([
            'name' => 'Energy Customer',
            'email' => 'customer@energycorp.com',
        ]);

        // Create sites
        $windFarm = Site::create([
            'name' => 'Wind Farm Alpha',
            'description' => 'Primary wind energy generation facility',
            'location' => 'Texas Panhandle',
            'address' => '1234 Wind Road, Amarillo, TX 79101',
            'latitude' => 35.2211,
            'longitude' => -101.8313,
            'contact_person' => 'John Site Manager',
            'contact_email' => 'manager@veoset.com',
            'contact_phone' => '+1-806-555-0123',
            'is_active' => true,
        ]);

        $solarFarm = Site::create([
            'name' => 'Solar Farm Beta',
            'description' => 'Large-scale solar photovoltaic installation',
            'location' => 'Arizona Desert',
            'address' => '5678 Solar Way, Phoenix, AZ 85001',
            'latitude' => 33.4484,
            'longitude' => -112.0740,
            'contact_person' => 'Jane Solar Manager',
            'contact_email' => 'solar@veoset.com',
            'contact_phone' => '+1-602-555-0456',
            'is_active' => true,
        ]);

        // Create wind turbines
        $turbines = [];
        for ($i = 1; $i <= 5; $i++) {
            $turbines[] = Asset::create([
                'site_id' => $windFarm->id,
                'name' => "Wind Turbine {$i}",
                'description' => "High-efficiency wind turbine unit {$i}",
                'asset_type' => 'turbine',
                'model' => 'WT-2500X',
                'manufacturer' => 'WindTech Industries',
                'serial_number' => "WT{$i}SN" . str_pad($i, 6, '0', STR_PAD_LEFT),
                'installation_date' => now()->subMonths(rand(12, 36)),
                'last_maintenance_date' => now()->subDays(rand(30, 120)),
                'next_maintenance_date' => now()->addDays(rand(30, 90)),
                'status' => 'operational',
                'mode' => 'auto',
                'is_active' => true,
            ]);
        }

        // Create solar panels
        $solarPanels = [];
        for ($i = 1; $i <= 3; $i++) {
            $solarPanels[] = Asset::create([
                'site_id' => $solarFarm->id,
                'name' => "Solar Array {$i}",
                'description' => "Photovoltaic panel array section {$i}",
                'asset_type' => 'solar_panel',
                'model' => 'SP-500X',
                'manufacturer' => 'SolarMax Corp',
                'serial_number' => "SP{$i}SN" . str_pad($i, 6, '0', STR_PAD_LEFT),
                'installation_date' => now()->subMonths(rand(6, 24)),
                'last_maintenance_date' => now()->subDays(rand(15, 60)),
                'next_maintenance_date' => now()->addDays(rand(15, 60)),
                'status' => 'operational',
                'mode' => 'auto',
                'is_active' => true,
            ]);
        }

        // Create transformers and generators
        $transformer = Asset::create([
            'site_id' => $windFarm->id,
            'name' => 'Main Transformer',
            'description' => 'Primary step-up transformer for wind farm',
            'asset_type' => 'transformer',
            'model' => 'T-150MVA',
            'manufacturer' => 'PowerGrid Solutions',
            'serial_number' => 'TF001SN000001',
            'installation_date' => now()->subYears(3),
            'last_maintenance_date' => now()->subMonths(6),
            'next_maintenance_date' => now()->addMonths(6),
            'status' => 'operational',
            'mode' => 'auto',
            'is_active' => true,
        ]);

        // Create asset metadata for some assets
        AssetMetadata::create([
            'asset_id' => $turbines[0]->id,
            'specifications' => [
                'power_rating' => '2.5 MW',
                'hub_height' => '80 meters',
                'rotor_diameter' => '100 meters',
                'cut_in_speed' => '3 m/s',
                'cut_out_speed' => '25 m/s',
            ],
            'maintenance_schedule' => [
                'interval_days' => 90,
                'maintenance_type' => 'preventive',
                'required_tools' => ['crane', 'multimeter', 'torque_wrench'],
                'estimated_duration_hours' => 8,
            ],
            'performance_metrics' => [
                'efficiency_threshold' => 92.0,
                'availability_target' => 98.5,
                'capacity_factor' => 35.0,
            ],
            'safety_requirements' => [
                'ppe_required' => ['hard_hat', 'safety_harness', 'steel_boots'],
                'lockout_procedures' => true,
                'working_at_height' => true,
            ],
            'environmental_data' => [
                'operating_temp_range' => '-40 to 60°C',
                'wind_class' => 'IEC Class IIIA',
                'seismic_design' => 'Zone 2',
            ],
            'power_rating' => 2500.00,
            'voltage_level' => '690V',
            'expected_lifespan_years' => 25,
            'efficiency_rating' => 94.5,
        ]);

        // Create service tasks
        $tasks = [
            [
                'asset_id' => $turbines[0]->id,
                'assigned_to' => $technician1->id,
                'title' => 'Quarterly Turbine Inspection',
                'description' => 'Comprehensive inspection of blade condition, gearbox oil, and electrical systems',
                'type' => 'preventive',
                'priority' => 'medium',
                'status' => 'pending',
                'scheduled_date' => now()->addDays(7),
                'estimated_duration_hours' => 8,
                'required_tools' => ['crane', 'multimeter', 'vibration_analyzer'],
                'safety_requirements' => [
                    'ppe_required' => ['hard_hat', 'safety_harness'],
                    'lockout_procedures' => true,
                ],
                'requires_shutdown' => true,
            ],
            [
                'asset_id' => $turbines[1]->id,
                'assigned_to' => $technician2->id,
                'title' => 'Gearbox Oil Change',
                'description' => 'Replace gearbox oil and filter, check for contamination',
                'type' => 'preventive',
                'priority' => 'medium',
                'status' => 'in_progress',
                'scheduled_date' => now()->subDays(1),
                'started_at' => now()->subHours(4),
                'estimated_duration_hours' => 6,
                'required_tools' => ['oil_pump', 'filter_wrench', 'sampling_kit'],
                'required_materials' => ['gearbox_oil', 'oil_filter'],
                'requires_shutdown' => true,
            ],
            [
                'asset_id' => $solarPanels[0]->id,
                'assigned_to' => $technician1->id,
                'title' => 'Panel Cleaning and Inspection',
                'description' => 'Clean solar panels and inspect for damage or degradation',
                'type' => 'preventive',
                'priority' => 'low',
                'status' => 'completed',
                'scheduled_date' => now()->subDays(5),
                'started_at' => now()->subDays(3),
                'completed_at' => now()->subDays(3)->addHours(4),
                'estimated_duration_hours' => 4,
                'actual_duration_hours' => 4,
                'completion_notes' => 'All panels cleaned successfully. Minor soiling removed. No damage found.',
                'requires_shutdown' => false,
            ],
            [
                'asset_id' => $transformer->id,
                'assigned_to' => $technician2->id,
                'title' => 'Emergency Oil Leak Repair',
                'description' => 'Investigate and repair oil leak from main transformer',
                'type' => 'emergency',
                'priority' => 'critical',
                'status' => 'pending',
                'scheduled_date' => now()->addHours(2),
                'estimated_duration_hours' => 12,
                'required_tools' => ['leak_detection_kit', 'welding_equipment'],
                'required_materials' => ['transformer_oil', 'gaskets'],
                'safety_requirements' => [
                    'ppe_required' => ['arc_flash_suit', 'insulated_gloves'],
                    'lockout_procedures' => true,
                    'high_voltage_safety' => true,
                ],
                'requires_shutdown' => true,
            ],
        ];

        foreach ($tasks as $taskData) {
            ServiceTask::create($taskData);
        }

        // Create some overdue tasks
        ServiceTask::create([
            'asset_id' => $turbines[2]->id,
            'assigned_to' => $technician1->id,
            'title' => 'Overdue Blade Inspection',
            'description' => 'Visual inspection of turbine blades for cracks or damage',
            'type' => 'preventive',
            'priority' => 'high',
            'status' => 'pending',
            'scheduled_date' => now()->subDays(5),
            'estimated_duration_hours' => 4,
            'requires_shutdown' => true,
        ]);
    }
}