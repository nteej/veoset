<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Site;
use App\Models\Asset;
use App\Models\AssetMetadata;
use App\Models\AssetHistory;
use App\Models\MqttDevice;
use App\Models\ServiceTask;
use Spatie\Permission\Models\Role;

class VEOsetSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Starting VEO realistic system seeding...');

        // Create Users with proper roles
        $this->seedUsers();

        // Create Sites based on VEO's actual projects
        $sites = $this->seedSites();

        // Create Assets with VEO products and technologies
        $assets = $this->seedAssets($sites);

        // Create MQTT Devices for IoT integration
        $this->seedMqttDevices($assets);

        // Create Service Tasks
        $this->seedServiceTasks($assets);

        // Assign users to sites for role-based access
        $this->assignUsersToSites($sites);

        $this->command->info('✅ VEO realistic system seeding completed successfully!');
    }

    private function seedUsers(): void
    {
        $this->command->info('👥 Creating users...');

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
        $this->command->info('Roles and permissions created successfully!');

        $this->command->info('Created roles: veo_admin, site_manager, maintenance_staff, customer');

    }

    private function seedSites(): array
    {
        $this->command->info('🏭 Creating VEO project sites...');

        $sitesData = [
            [
                'name' => 'VEO Vaasa HQ & Factory',
                'description' => 'VEO headquarters and manufacturing facility with R&D labs',
                'location' => 'Vaasa, Finland',
                'address' => 'Voimakatu 7, 65380 Vaasa, Finland',
                'latitude' => 63.0951,
                'longitude' => 21.6165,
                'contact_person' => 'Operations Manager',
                'contact_email' => 'operations@veo.fi',
                'contact_phone' => '+358 6 781 4400',
                'is_active' => true,
            ],
            [
                'name' => 'Neste Singapore Renewable Fuels',
                'description' => 'Large-scale renewable fuels refinery electrification project',
                'location' => 'Singapore',
                'address' => 'Jurong Island, Singapore',
                'latitude' => 1.2644,
                'longitude' => 103.6967,
                'contact_person' => 'Project Manager',
                'contact_email' => 'singapore.project@veo.fi',
                'contact_phone' => '+65 6123 4567',
                'is_active' => true,
            ],
            [
                'name' => 'Guleslettene Wind Park',
                'description' => '197.4 MW wind park with advanced grid integration',
                'location' => 'Bremanger, Norway',
                'address' => 'Guleslettene, 6727 Rugsund, Norway',
                'latitude' => 61.8753,
                'longitude' => 5.1644,
                'contact_person' => 'Wind Park Manager',
                'contact_email' => 'guleslettene@veo.fi',
                'contact_phone' => '+47 456 78 901',
                'is_active' => true,
            ],
            [
                'name' => 'Martinlaakso Bioenergy Plant',
                'description' => 'Advanced bioenergy heating plant with smart grid integration',
                'location' => 'Vantaa, Finland',
                'address' => 'Martinlaakso, 01620 Vantaa, Finland',
                'latitude' => 60.2741,
                'longitude' => 24.7667,
                'contact_person' => 'Plant Operations Manager',
                'contact_email' => 'martinlaakso@veo.fi',
                'contact_phone' => '+358 9 123 4567',
                'is_active' => true,
            ],
            [
                'name' => 'Rengård Hydropower G2 Extension',
                'description' => 'Hydropower plant modernization with VECOS automation',
                'location' => 'Skellefteå, Sweden',
                'address' => 'Rengård, 931 91 Skellefteå, Sweden',
                'latitude' => 64.7506,
                'longitude' => 20.9517,
                'contact_person' => 'Hydro Project Manager',
                'contact_email' => 'rengard@veo.fi',
                'contact_phone' => '+46 910 123 456',
                'is_active' => true,
            ],
        ];

        $sites = [];
        foreach ($sitesData as $siteData) {
            $site = Site::firstOrCreate(
                ['name' => $siteData['name']],
                $siteData
            );
            $sites[] = $site;
        }

        return $sites;
    }

    private function seedAssets(array $sites): array
    {
        $this->command->info('⚡ Creating VEO technology assets...');

        $assetsData = [
            // VEO Vaasa HQ & Factory
            [
                'site_id' => $sites[0]->id,
                'name' => 'VEDA 5000 LV Switchgear Line A',
                'description' => 'Production test line for VEDA low-voltage switchgear',
                'asset_type' => 'LV Switchgear',
                'model' => 'VEDA 5000',
                'manufacturer' => 'VEO',
                'serial_number' => 'VEDA-VAASA-A-001',
                'installation_date' => '2022-06-01',
                'last_maintenance_date' => '2025-06-15',
                'next_maintenance_date' => '2026-06-15',
                'status' => 'operational',
                'mode' => 'auto',
                'is_active' => true,
            ],
            [
                'site_id' => $sites[0]->id,
                'name' => 'VECTOR MV Switchgear R&D Bay',
                'description' => 'Medium-voltage switchgear research and development test bay',
                'asset_type' => 'MV Switchgear',
                'model' => 'VECTOR',
                'manufacturer' => 'VEO',
                'serial_number' => 'VECTOR-RD-001',
                'installation_date' => '2023-03-10',
                'last_maintenance_date' => '2025-03-15',
                'next_maintenance_date' => '2026-03-15',
                'status' => 'operational',
                'mode' => 'auto',
                'is_active' => true,
            ],
            [
                'site_id' => $sites[0]->id,
                'name' => 'VEBA Bus Duct System',
                'description' => 'Bus duct compatibility testing with VEDA/VECTOR switchgear',
                'asset_type' => 'Bus Duct',
                'model' => 'VEBA',
                'manufacturer' => 'VEO',
                'serial_number' => 'VEBA-TB-001',
                'installation_date' => '2021-11-12',
                'last_maintenance_date' => '2025-05-20',
                'next_maintenance_date' => '2026-05-20',
                'status' => 'maintenance',
                'mode' => 'manual',
                'is_active' => true,
            ],
            [
                'site_id' => $sites[0]->id,
                'name' => 'VECOS Control System',
                'description' => 'Central automation and protection control system',
                'asset_type' => 'Control System',
                'model' => 'VECOS',
                'manufacturer' => 'VEO',
                'serial_number' => 'VECOS-VAASA-001',
                'installation_date' => '2023-01-15',
                'last_maintenance_date' => '2025-01-15',
                'next_maintenance_date' => '2026-01-15',
                'status' => 'operational',
                'mode' => 'auto',
                'is_active' => true,
            ],

            // Neste Singapore Renewable Fuels
            [
                'site_id' => $sites[1]->id,
                'name' => 'E-House Unit 1 - Process Area',
                'description' => 'Modular substation housing for renewable fuel processing',
                'asset_type' => 'E-House',
                'model' => 'Modular Substation',
                'manufacturer' => 'VEO',
                'serial_number' => 'EH-NES-001',
                'installation_date' => '2023-06-15',
                'last_maintenance_date' => '2025-06-15',
                'next_maintenance_date' => '2026-06-15',
                'status' => 'operational',
                'mode' => 'auto',
                'is_active' => true,
            ],
            [
                'site_id' => $sites[1]->id,
                'name' => 'VEDA 5000 Process Distribution',
                'description' => 'Low-voltage power distribution for refinery processes',
                'asset_type' => 'LV Switchgear',
                'model' => 'VEDA 5000',
                'manufacturer' => 'VEO',
                'serial_number' => 'VEDA-NES-A-1001',
                'installation_date' => '2023-07-01',
                'last_maintenance_date' => '2025-07-01',
                'next_maintenance_date' => '2026-07-01',
                'status' => 'operational',
                'mode' => 'auto',
                'is_active' => true,
            ],

            // Guleslettene Wind Park
            [
                'site_id' => $sites[2]->id,
                'name' => 'VECTOR Collector Substation',
                'description' => 'Primary MV distribution for 197.4 MW wind park',
                'asset_type' => 'MV Switchgear',
                'model' => 'VECTOR',
                'manufacturer' => 'VEO',
                'serial_number' => 'VECTOR-GUL-001',
                'installation_date' => '2020-09-01',
                'last_maintenance_date' => '2025-09-01',
                'next_maintenance_date' => '2026-09-01',
                'status' => 'operational',
                'mode' => 'auto',
                'is_active' => true,
            ],
            [
                'site_id' => $sites[2]->id,
                'name' => 'VEBA Main Feeder Bus',
                'description' => 'Main feeder bus duct to step-up transformer',
                'asset_type' => 'Bus Duct',
                'model' => 'VEBA',
                'manufacturer' => 'VEO',
                'serial_number' => 'VEBA-GUL-001',
                'installation_date' => '2020-09-15',
                'last_maintenance_date' => '2025-09-10',
                'next_maintenance_date' => '2026-09-10',
                'status' => 'operational',
                'mode' => 'auto',
                'is_active' => true,
            ],

            // Martinlaakso Bioenergy Plant
            [
                'site_id' => $sites[3]->id,
                'name' => 'VEDA Boiler House Distribution',
                'description' => 'Low-voltage distribution for bioenergy auxiliaries',
                'asset_type' => 'LV Switchgear',
                'model' => 'VEDA 5000',
                'manufacturer' => 'VEO',
                'serial_number' => 'VEDA-MAR-001',
                'installation_date' => '2019-12-01',
                'last_maintenance_date' => '2025-12-01',
                'next_maintenance_date' => '2026-12-01',
                'status' => 'operational',
                'mode' => 'auto',
                'is_active' => true,
            ],
            [
                'site_id' => $sites[3]->id,
                'name' => 'VECOS Protection Cabinet',
                'description' => 'Advanced protection and automation for bioenergy plant',
                'asset_type' => 'Protection Cabinet',
                'model' => 'VECOS',
                'manufacturer' => 'VEO',
                'serial_number' => 'VECOS-MAR-001',
                'installation_date' => '2019-12-01',
                'last_maintenance_date' => '2025-11-15',
                'next_maintenance_date' => '2026-11-15',
                'status' => 'operational',
                'mode' => 'auto',
                'is_active' => true,
            ],

            // Rengård Hydropower G2 Extension
            [
                'site_id' => $sites[4]->id,
                'name' => 'VECTOR G2 Generator Feeder',
                'description' => 'Generator feeder bay for hydropower unit G2',
                'asset_type' => 'MV Switchgear',
                'model' => 'VECTOR',
                'manufacturer' => 'VEO',
                'serial_number' => 'VECTOR-REN-001',
                'installation_date' => '2024-08-01',
                'last_maintenance_date' => '2025-08-01',
                'next_maintenance_date' => '2026-08-01',
                'status' => 'operational',
                'mode' => 'auto',
                'is_active' => true,
            ],
            [
                'site_id' => $sites[4]->id,
                'name' => 'HPG Turbine Governor',
                'description' => 'Hydraulic turbine governor for G2 unit',
                'asset_type' => 'Turbine Governor',
                'model' => 'HPG',
                'manufacturer' => 'VEO',
                'serial_number' => 'HPG-REN-G2-001',
                'installation_date' => '2024-08-01',
                'last_maintenance_date' => '2025-08-01',
                'next_maintenance_date' => '2026-08-01',
                'status' => 'operational',
                'mode' => 'auto',
                'is_active' => true,
            ],
        ];

        $assets = [];
        foreach ($assetsData as $assetData) {
            $asset = Asset::firstOrCreate(
                ['serial_number' => $assetData['serial_number']],
                $assetData
            );
            $assets[] = $asset;

            // Create comprehensive metadata for each asset
            $this->createAssetMetadata($asset);

            // Create initial history entry
            $this->createInitialHistory($asset);
        }

        return $assets;
    }

    private function createAssetMetadata(Asset $asset): void
    {
        $metadataConfig = [
            'LV Switchgear' => [
                'specifications' => [
                    'configuration' => 'withdrawable',
                    'ip_class' => 'up to IP54',
                    'arc_classification' => 'IAC AFL',
                    'busbar_system' => 'copper',
                ],
                'performance_metrics' => [
                    'rated_current_A' => rand(1600, 4000),
                    'rated_voltage_V' => '400/690',
                    'short_circuit_strength_kA' => rand(50, 100),
                ],
                'power_rating' => rand(1600, 4000),
                'voltage_level' => 'LV 400/690 V',
                'expected_lifespan_years' => 25,
                'efficiency_rating' => 99.0,
            ],
            'MV Switchgear' => [
                'specifications' => [
                    'standard' => 'IEC 62271-200',
                    'insulation' => 'SF6 or vacuum',
                    'arc_classification' => 'A-FLR',
                ],
                'performance_metrics' => [
                    'rated_voltage_kV' => rand(12, 36),
                    'rated_current_A' => rand(1250, 3150),
                    'short_circuit_current_kA' => rand(25, 40),
                ],
                'power_rating' => 0,
                'voltage_level' => 'MV 12-36 kV',
                'expected_lifespan_years' => 30,
                'efficiency_rating' => null,
            ],
            'Bus Duct' => [
                'specifications' => [
                    'system_type' => 'low/medium voltage busbar',
                    'conductor_material' => 'copper/aluminum',
                    'enclosure' => 'IP55',
                ],
                'performance_metrics' => [
                    'bus_rating_A' => rand(2000, 5000),
                    'temperature_rise_K' => 65,
                ],
                'power_rating' => 0,
                'voltage_level' => 'LV/MV',
                'expected_lifespan_years' => 25,
                'efficiency_rating' => null,
            ],
            'E-House' => [
                'specifications' => [
                    'module_area_m2' => 'up to 400+',
                    'climate_control' => 'HVAC integrated',
                    'protection_class' => 'IP44/IP54',
                ],
                'performance_metrics' => [
                    'contains' => ['LV switchgear', 'MV switchgear', 'auxiliary systems'],
                ],
                'power_rating' => 0,
                'voltage_level' => 'LV/MV',
                'expected_lifespan_years' => 25,
                'efficiency_rating' => null,
            ],
            'Control System' => [
                'specifications' => [
                    'protocol' => 'IEC 61850',
                    'hmi' => 'web-based interface',
                    'redundancy' => 'hot standby',
                ],
                'performance_metrics' => [
                    'response_time_ms' => rand(10, 50),
                    'availability_percent' => 99.9,
                ],
                'power_rating' => 0,
                'voltage_level' => 'Control',
                'expected_lifespan_years' => 15,
                'efficiency_rating' => null,
            ],
            'Protection Cabinet' => [
                'specifications' => [
                    'relays' => 'IEC 61850 capable',
                    'communication' => 'fiber optic',
                    'redundancy' => 'dual protection',
                ],
                'performance_metrics' => [
                    'operating_time_ms' => rand(10, 100),
                    'accuracy_class' => '0.2S',
                ],
                'power_rating' => 0,
                'voltage_level' => 'LV/MV',
                'expected_lifespan_years' => 20,
                'efficiency_rating' => null,
            ],
            'Turbine Governor' => [
                'specifications' => [
                    'type' => 'electrohydraulic',
                    'control_mode' => 'PID with advanced algorithms',
                    'response_time' => 'less than 0.2s',
                ],
                'performance_metrics' => [
                    'regulation_accuracy_percent' => 0.25,
                    'load_rejection_capability' => '100%',
                ],
                'power_rating' => 0,
                'voltage_level' => 'Control',
                'expected_lifespan_years' => 25,
                'efficiency_rating' => 98.5,
            ],
        ];

        $config = $metadataConfig[$asset->asset_type] ?? $metadataConfig['LV Switchgear'];

        AssetMetadata::firstOrCreate(
            ['asset_id' => $asset->id],
            [
                'specifications' => $config['specifications'],
                'maintenance_schedule' => ['interval_months' => 12],
                'performance_metrics' => $config['performance_metrics'],
                'safety_requirements' => ['standards' => ['IEC 61439', 'IEC 62271']],
                'environmental_data' => ['placement' => 'indoor', 'operating_temp_C' => '-25 to +40'],
                'power_rating' => $config['power_rating'],
                'voltage_level' => $config['voltage_level'],
                'expected_lifespan_years' => $config['expected_lifespan_years'],
                'efficiency_rating' => $config['efficiency_rating'],
                'operational_notes' => "VEO {$asset->model} - {$asset->description}",
            ]
        );
    }

    private function createInitialHistory(Asset $asset): void
    {
        AssetHistory::firstOrCreate(
            [
                'asset_id' => $asset->id,
                'event_type' => 'status_change',
                'event_description' => 'Asset commissioned and operational',
            ],
            [
                'recorded_by' => null,
                'previous_status' => null,
                'current_status' => $asset->status,
                'performance_data' => [
                    'commissioned' => true,
                    'initial_tests' => 'passed',
                    'factory_acceptance_test' => 'completed',
                ],
                'diagnostic_data' => [
                    'insulation_resistance' => 'within specifications',
                    'contact_resistance' => 'acceptable',
                    'operational_tests' => 'successful',
                ],
                'health_score' => rand(90, 100),
                'health_status' => 'excellent',
                'temperature' => rand(20, 35),
                'humidity' => rand(40, 60),
                'vibration_level' => rand(1, 5),
                'power_output' => $asset->metadata?->power_rating > 0 ? rand(80, 100) : null,
                'efficiency_percentage' => $asset->metadata?->efficiency_rating ?? rand(95, 99),
                'anomaly_detected' => false,
                'anomaly_description' => null,
                'severity_level' => 'low',
                'shift_type' => 'day',
                'shift_start' => now()->startOfDay()->addHours(8),
                'shift_end' => now()->startOfDay()->addHours(16),
                'data_source' => 'system',
                'notes' => 'Initial commissioning completed successfully',
            ]
        );
    }

    private function seedMqttDevices(array $assets): void
    {
        $this->command->info('📡 Creating MQTT IoT devices...');

        foreach ($assets as $index => $asset) {
            // Create 1-2 MQTT devices per asset for monitoring
            $deviceTypes = ['temperature_sensor', 'current_monitor', 'voltage_monitor', 'vibration_sensor', 'humidity_sensor'];
            $numDevices = rand(1, 2);

            for ($i = 0; $i < $numDevices; $i++) {
                $deviceType = $deviceTypes[array_rand($deviceTypes)];

                MqttDevice::firstOrCreate(
                    [
                        'asset_id' => $asset->id,
                        'device_id' => "VEO_{$asset->serial_number}_{$deviceType}_{$i}",
                    ],
                    [
                        'name' => ucfirst(str_replace('_', ' ', $deviceType)) . " - {$asset->name}",
                        'device_type' => $deviceType,
                        'topic_prefix' => "veo/assets/{$asset->id}/sensors",
                        'status' => 'online',
                        'last_seen' => now(),
                        'firmware_version' => '2.1.' . rand(0, 5),
                        'config' => [
                            'sampling_rate_ms' => rand(1000, 5000),
                            'threshold_alerts' => true,
                            'data_retention_days' => 30,
                        ],
                        'location' => $asset->site->name,
                        'installation_date' => $asset->installation_date,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    private function seedServiceTasks(array $assets): void
    {
        $this->command->info('🔧 Creating service tasks...');

        $users = User::all();
        $maintenanceStaff = $users->filter(fn($user) => $user->hasRole('maintenance_staff'));

        if ($maintenanceStaff->isEmpty()) {
            return;
        }

        $taskTemplates = [
            [
                'title' => 'Annual Switchgear Inspection',
                'description' => 'Comprehensive inspection of switchgear components, connections, and protective devices',
                'type' => 'preventive',
                'priority' => 'medium',
                'estimated_duration_hours' => 6,
                'asset_types' => ['LV Switchgear', 'MV Switchgear'],
            ],
            [
                'title' => 'Bus Duct Thermal Inspection',
                'description' => 'Thermal imaging inspection of bus duct connections and joints',
                'type' => 'preventive',
                'priority' => 'medium',
                'estimated_duration_hours' => 4,
                'asset_types' => ['Bus Duct'],
            ],
            [
                'title' => 'VECOS System Update',
                'description' => 'Update VECOS control system firmware and configuration backup',
                'type' => 'preventive',
                'priority' => 'low',
                'estimated_duration_hours' => 3,
                'asset_types' => ['Control System', 'Protection Cabinet'],
            ],
            [
                'title' => 'E-House HVAC Maintenance',
                'description' => 'Service HVAC system in E-House module including filter replacement',
                'type' => 'preventive',
                'priority' => 'medium',
                'estimated_duration_hours' => 4,
                'asset_types' => ['E-House'],
            ],
        ];

        foreach ($assets as $asset) {
            $applicableTasks = array_filter($taskTemplates, function($task) use ($asset) {
                return in_array($asset->asset_type, $task['asset_types']);
            });

            if (!empty($applicableTasks)) {
                $task = $applicableTasks[array_rand($applicableTasks)];
                $assignedUser = $maintenanceStaff->random();

                ServiceTask::firstOrCreate(
                    [
                        'asset_id' => $asset->id,
                        'title' => $task['title'],
                    ],
                    [
                        'assigned_to' => $assignedUser->id,
                        'description' => $task['description'],
                        'type' => $task['type'],
                        'priority' => $task['priority'],
                        'status' => 'pending',
                        'scheduled_date' => now()->addDays(rand(1, 30)),
                        'estimated_duration_hours' => $task['estimated_duration_hours'],
                        'required_tools' => ['multimeter', 'thermal_camera', 'safety_equipment'],
                        'safety_requirements' => [
                            'ppe_required' => ['hard_hat', 'safety_glasses', 'insulated_gloves'],
                            'lockout_procedures' => true,
                        ],
                        'requires_shutdown' => rand(0, 1) === 1,
                    ]
                );
            }
        }
    }

    private function assignUsersToSites(array $sites): void
    {
        $this->command->info('🔗 Assigning users to sites...');

        $manager = User::where('email', 'manager@veoset.com')->first();
        $maintenance = User::where('email', 'mike@veoset.com')->first();
        $customer = User::where('email', 'customer@energycorp.com')->first();

        if ($manager) {
            // Site manager manages VEO HQ and Martinlaakso
            $manager->sites()->sync([$sites[0]->id, $sites[3]->id]);
        }

        if ($maintenance) {
            // Maintenance staff works on all sites except customer sites
            $maintenance->sites()->sync([
                $sites[0]->id, // VEO HQ
                $sites[1]->id, // Singapore
                $sites[2]->id, // Norway
            ]);
        }

        if ($customer) {
            // Customer has access to their specific sites
            $customer->sites()->sync([
                $sites[1]->id, // Singapore (their refinery)
                $sites[3]->id, // Martinlaakso (their plant)
            ]);
        }
    }
}