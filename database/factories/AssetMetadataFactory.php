<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssetMetadata>
 */
class AssetMetadataFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => \App\Models\Asset::factory(),
            'specifications' => [
                'weight_kg' => fake()->numberBetween(100, 50000),
                'dimensions' => fake()->randomElement(['2x2x3m', '5x3x4m', '10x5x6m']),
                'material' => fake()->randomElement(['steel', 'aluminum', 'composite']),
                'certification' => fake()->randomElement(['ISO9001', 'IEC61400', 'UL1741']),
            ],
            'maintenance_schedule' => [
                'interval_days' => fake()->randomElement([30, 60, 90, 180, 365]),
                'maintenance_type' => fake()->randomElement(['preventive', 'predictive', 'condition-based']),
                'required_tools' => fake()->randomElements(['wrench', 'multimeter', 'crane', 'safety_harness'], 2),
                'estimated_duration_hours' => fake()->numberBetween(1, 24),
            ],
            'performance_metrics' => [
                'efficiency_threshold' => fake()->randomFloat(2, 80, 98),
                'max_power_output' => fake()->numberBetween(100, 10000),
                'operating_temperature_range' => '-20 to 60°C',
                'vibration_limit' => fake()->randomFloat(2, 1, 10),
            ],
            'safety_requirements' => [
                'lockout_procedures' => ['electrical_isolation', 'mechanical_lockout'],
                'ppe_required' => fake()->randomElements(['hard_hat', 'safety_glasses', 'gloves', 'harness'], 3),
                'emergency_procedures' => ['evacuation_route', 'emergency_contacts', 'shutdown_sequence'],
                'hazard_classification' => fake()->randomElement(['low', 'medium', 'high']),
            ],
            'environmental_data' => [
                'max_temperature' => fake()->numberBetween(60, 120),
                'min_temperature' => fake()->numberBetween(-40, 0),
                'humidity_range' => '10-90%',
                'altitude_limit' => fake()->numberBetween(0, 3000),
                'ip_rating' => fake()->randomElement(['IP54', 'IP65', 'IP67']),
            ],
            'power_rating' => fake()->randomFloat(2, 10, 10000),
            'voltage_level' => fake()->randomElement(['400V', '690V', '3.3kV', '11kV', '33kV']),
            'expected_lifespan_years' => fake()->numberBetween(10, 30),
            'efficiency_rating' => fake()->randomFloat(2, 85, 98),
            'operational_notes' => fake()->optional(0.7)->text(200),
        ];
    }
}
