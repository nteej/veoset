<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\{Site, Asset, AssetMetadata, AssetHistory};

class VeoSetSeeder extends Seeder
{
    public function run(): void
    {
        // --- Sites ---
        $sites = [
            ['name' => 'VEO Vaasa HQ & Factory', 'description' => 'Headquarters and manufacturing site', 'location' => 'Vaasa, Finland', 'address' => 'Hyvinkääntie / Vaasa (exact address omitted)', 'latitude' => null, 'longitude' => null, 'contact_person' => 'Operations Desk', 'contact_email' => 'info@veo.fi', 'contact_phone' => '+358', 'is_active' => true],
            ['name' => 'Neste Singapore Refinery (Expansion)', 'description' => 'Renewable fuels refinery electrification project', 'location' => 'Singapore', 'address' => null, 'latitude' => null, 'longitude' => null, 'contact_person' => 'Project Liaison', 'contact_email' => null, 'contact_phone' => null, 'is_active' => true],
            ['name' => 'Guleslettene Wind Park', 'description' => '197.4 MW wind park electrification', 'location' => 'Bremanger, Norway', 'address' => null, 'latitude' => null, 'longitude' => null, 'contact_person' => 'Site Manager', 'contact_email' => null, 'contact_phone' => null, 'is_active' => true],
            ['name' => 'Martinlaakso Bioenergy Plant', 'description' => 'Bioenergy heating plant electrification', 'location' => 'Vantaa, Finland', 'address' => null, 'latitude' => null, 'longitude' => null, 'contact_person' => 'Plant Operations', 'contact_email' => null, 'contact_phone' => null, 'is_active' => true],
            ['name' => 'Rengård Hydropower (G2 Extension)', 'description' => 'Hydropower plant electrification & automation', 'location' => 'Skellefteå, Sweden', 'address' => null, 'latitude' => null, 'longitude' => null, 'contact_person' => 'Project Manager', 'contact_email' => null, 'contact_phone' => null, 'is_active' => true],
        ];

        $siteIds = [];
        foreach ($sites as $s) {
            $site = Site::query()->create($s);
            $siteIds[] = $site->id;
        }

        // --- Assets (tie to sites by index) ---
        $assets = [
            // Site 1
            ['site_index' => 0, 'name' => 'VEDA Low-Voltage Switchgear Line A', 'description' => 'Production test line switchgear', 'asset_type' => 'LV Switchgear', 'model' => 'VEDA 5000', 'manufacturer' => 'VEO', 'serial_number' => 'VEDA-VAASA-A-001', 'installation_date' => '2022-06-01', 'last_maintenance_date' => '2025-06-15', 'next_maintenance_date' => '2026-06-15', 'status' => 'operational', 'mode' => 'auto', 'is_active' => true],
            ['site_index' => 0, 'name' => 'VECTOR Medium-Voltage Switchgear Bay 1', 'description' => 'MV switchgear R&D test bay', 'asset_type' => 'MV Switchgear', 'model' => 'VECTOR', 'manufacturer' => 'VEO', 'serial_number' => 'VECTOR-RD-001', 'installation_date' => '2023-03-10', 'last_maintenance_date' => '2025-03-15', 'next_maintenance_date' => '2026-03-15', 'status' => 'operational', 'mode' => 'auto', 'is_active' => true],
            ['site_index' => 0, 'name' => 'Bus Duct Testbed', 'description' => 'Bus duct compatibility test with switchgear', 'asset_type' => 'Bus Duct', 'model' => 'VEBA', 'manufacturer' => 'VEO', 'serial_number' => 'VEBA-TB-001', 'installation_date' => '2021-11-12', 'last_maintenance_date' => '2025-05-20', 'next_maintenance_date' => '2026-05-20', 'status' => 'maintenance', 'mode' => 'manual', 'is_active' => true],

            // Site 2
            ['site_index' => 1, 'name' => 'LV Switchgear – Process Area A', 'description' => 'Power distribution for process area', 'asset_type' => 'LV Switchgear', 'model' => 'VEDA 5000', 'manufacturer' => 'VEO', 'serial_number' => 'VEDA-NES-A-1001', 'installation_date' => '2023-07-01', 'last_maintenance_date' => '2025-07-01', 'next_maintenance_date' => '2026-07-01', 'status' => 'operational', 'mode' => 'auto', 'is_active' => true],
            ['site_index' => 1, 'name' => 'E-House Unit 1', 'description' => 'Modular substation housing LV/MV equipment', 'asset_type' => 'E-House', 'model' => 'Modular Substation', 'manufacturer' => 'VEO', 'serial_number' => 'EH-NES-001', 'installation_date' => '2023-06-15', 'last_maintenance_date' => '2025-06-15', 'next_maintenance_date' => '2026-06-15', 'status' => 'operational', 'mode' => 'auto', 'is_active' => true],

            // Site 3
            ['site_index' => 2, 'name' => 'MV Switchgear – Collector Substation', 'description' => 'Primary MV distribution for wind park', 'asset_type' => 'MV Switchgear', 'model' => 'VECTOR', 'manufacturer' => 'VEO', 'serial_number' => 'VECTOR-GUL-001', 'installation_date' => '2020-09-01', 'last_maintenance_date' => '2025-09-01', 'next_maintenance_date' => '2026-09-01', 'status' => 'operational', 'mode' => 'auto', 'is_active' => true],
            ['site_index' => 2, 'name' => 'Bus Duct – Main Feeder', 'description' => 'Main feeder bus duct to step-up transformer', 'asset_type' => 'Bus Duct', 'model' => 'VEBA', 'manufacturer' => 'VEO', 'serial_number' => 'VEBA-GUL-001', 'installation_date' => '2020-09-15', 'last_maintenance_date' => '2025-09-10', 'next_maintenance_date' => '2026-09-10', 'status' => 'operational', 'mode' => 'auto', 'is_active' => true],

            // Site 4
            ['site_index' => 3, 'name' => 'LV Switchgear – Boiler House', 'description' => 'Low-voltage distribution for auxiliaries', 'asset_type' => 'LV Switchgear', 'model' => 'VEDA 5000', 'manufacturer' => 'VEO', 'serial_number' => 'VEDA-MAR-001', 'installation_date' => '2019-12-01', 'last_maintenance_date' => '2025-12-01', 'next_maintenance_date' => '2026-12-01', 'status' => 'operational', 'mode' => 'auto', 'is_active' => true],
            ['site_index' => 3, 'name' => 'Protection & Automation Panel', 'description' => 'Vecos protection and automation cabinet', 'asset_type' => 'Protection Cabinet', 'model' => 'VECOS', 'manufacturer' => 'VEO', 'serial_number' => 'VECOS-MAR-001', 'installation_date' => '2019-12-01', 'last_maintenance_date' => '2025-11-15', 'next_maintenance_date' => '2026-11-15', 'status' => 'operational', 'mode' => 'auto', 'is_active' => true],

            // Site 5
            ['site_index' => 4, 'name' => 'MV Switchgear – G2 Generator Feeder', 'description' => 'Generator feeder bay', 'asset_type' => 'MV Switchgear', 'model' => 'VECTOR', 'manufacturer' => 'VEO', 'serial_number' => 'VECTOR-REN-001', 'installation_date' => '2024-08-01', 'last_maintenance_date' => '2025-08-01', 'next_maintenance_date' => '2026-08-01', 'status' => 'operational', 'mode' => 'auto', 'is_active' => true],
        ];

        $assetsCreated = [];
        foreach ($assets as $a) {
            $siteId = $siteIds[$a['site_index']];
            unset($a['site_index']);
            $a['site_id'] = $siteId;
            $assetsCreated[] = Asset::query()->create($a);
        }

        // --- Asset metadata (index-based to match above order) ---
        $metadata = [
            1 => ['specifications' => ['configuration' => 'withdrawable','ip_class' => 'up to IP54'], 'maintenance_schedule' => ['interval_months' => 12], 'performance_metrics' => ['rated_current_A' => 4000], 'safety_requirements' => ['standards' => ['EN 61439']], 'environmental_data' => ['placement' => 'indoor'], 'power_rating' => 4000.00, 'voltage_level' => 'LV 400/690 V', 'expected_lifespan_years' => 25, 'efficiency_rating' => 99.0, 'operational_notes' => 'Production test line'],
            2 => ['specifications' => ['standard' => 'IEC 62271-200'], 'maintenance_schedule' => ['interval_months' => 12], 'performance_metrics' => ['rated_voltage_kV' => 24], 'safety_requirements' => ['arc_class' => 'A-FLR'], 'environmental_data' => ['placement' => 'indoor'], 'power_rating' => 0.00, 'voltage_level' => 'MV 24 kV', 'expected_lifespan_years' => 30, 'efficiency_rating' => null, 'operational_notes' => 'R&D test bay'],
            3 => ['specifications' => ['system' => 'low-voltage busbar'], 'maintenance_schedule' => ['interval_months' => 12], 'performance_metrics' => ['bus_rating_A' => 3200], 'safety_requirements' => [], 'environmental_data' => ['placement' => 'indoor'], 'power_rating' => 0.00, 'voltage_level' => 'LV 400/690 V', 'expected_lifespan_years' => 25, 'efficiency_rating' => null, 'operational_notes' => 'Testbed'],
            4 => ['specifications' => ['configuration' => 'fixed'], 'maintenance_schedule' => ['interval_months' => 12], 'performance_metrics' => ['rated_current_A' => 3200], 'safety_requirements' => [], 'environmental_data' => ['placement' => 'indoor'], 'power_rating' => 3200.00, 'voltage_level' => 'LV 400/690 V', 'expected_lifespan_years' => 25, 'efficiency_rating' => null, 'operational_notes' => 'Process area A distribution'],
            5 => ['specifications' => ['module_area_m2' => 'up to 400+'], 'maintenance_schedule' => ['interval_months' => 12], 'performance_metrics' => ['contains' => ['LV switchgear','MV switchgear','aux systems']], 'safety_requirements' => [], 'environmental_data' => ['placement' => 'outdoor'], 'power_rating' => 0.00, 'voltage_level' => 'LV/MV', 'expected_lifespan_years' => 25, 'efficiency_rating' => null, 'operational_notes' => 'Modular substation'],
            6 => ['specifications' => ['type' => 'collector substation'], 'maintenance_schedule' => ['interval_months' => 12], 'performance_metrics' => ['rated_voltage_kV' => 24], 'safety_requirements' => [], 'environmental_data' => ['placement' => 'indoor'], 'power_rating' => 0.00, 'voltage_level' => 'MV 24 kV', 'expected_lifespan_years' => 30, 'efficiency_rating' => null, 'operational_notes' => 'Wind park collector'],
            7 => ['specifications' => ['application' => 'main feeder'], 'maintenance_schedule' => ['interval_months' => 12], 'performance_metrics' => ['bus_rating_A' => 4000], 'safety_requirements' => [], 'environmental_data' => ['placement' => 'indoor'], 'power_rating' => 0.00, 'voltage_level' => 'LV', 'expected_lifespan_years' => 25, 'efficiency_rating' => null, 'operational_notes' => 'Feeder to transformer'],
            8 => ['specifications' => ['location' => 'boiler house'], 'maintenance_schedule' => ['interval_months' => 12], 'performance_metrics' => ['rated_current_A' => 2500], 'safety_requirements' => [], 'environmental_data' => ['placement' => 'indoor'], 'power_rating' => 2500.00, 'voltage_level' => 'LV 400/690 V', 'expected_lifespan_years' => 25, 'efficiency_rating' => null, 'operational_notes' => 'Auxiliaries'],
            9 => ['specifications' => ['function' => 'protection & automation'], 'maintenance_schedule' => ['interval_months' => 12], 'performance_metrics' => ['relays' => 'IEC 61850 capable'], 'safety_requirements' => [], 'environmental_data' => ['placement' => 'indoor'], 'power_rating' => 0.00, 'voltage_level' => 'LV/MV', 'expected_lifespan_years' => 20, 'efficiency_rating' => null, 'operational_notes' => 'Protection cabinet'],
            10 => ['specifications' => ['feeder' => 'generator'], 'maintenance_schedule' => ['interval_months' => 12], 'performance_metrics' => ['rated_voltage_kV' => 24], 'safety_requirements' => [], 'environmental_data' => ['placement' => 'indoor'], 'power_rating' => 0.00, 'voltage_level' => 'MV 24 kV', 'expected_lifespan_years' => 30, 'efficiency_rating' => null, 'operational_notes' => 'Hydro G2'],
        ];

        foreach ($assetsCreated as $index => $asset) {
            $md = $metadata[$index + 1] ?? null;
            if ($md) {
                AssetMetadata::query()->create(array_merge(
                    ['asset_id' => $asset->id],
                    $md
                ));
            }

            // Initial asset history event
            AssetHistory::query()->create([
                'asset_id' => $asset->id,
                'recorded_by' => null,
                'event_type' => 'status_change',
                'event_description' => 'Commissioned and set to operational',
                'previous_status' => null,
                'current_status' => $asset->status,
                'performance_data' => ['commissioned' => true],
                'diagnostic_data' => null,
                'health_score' => 95.0,
                'health_status' => 'good',
                'temperature' => null,
                'humidity' => null,
                'vibration_level' => null,
                'power_output' => null,
                'efficiency_percentage' => null,
                'shift_type' => 'day',
                'shift_start' => now()->startOfDay()->addHours(8),
                'shift_end' => now()->startOfDay()->addHours(16),
            ]);
        }
    }
}
