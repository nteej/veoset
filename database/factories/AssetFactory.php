<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $assetTypes = ['turbine', 'transformer', 'generator', 'inverter', 'battery', 'solar_panel'];
        $statuses = ['operational', 'maintenance', 'offline', 'decommissioned'];
        $modes = ['auto', 'manual', 'standby'];

        return [
            'site_id' => \App\Models\Site::factory(),
            'name' => fake()->randomElement($assetTypes) . ' ' . fake()->numberBetween(1, 999),
            'description' => fake()->text(150),
            'asset_type' => fake()->randomElement($assetTypes),
            'model' => fake()->word() . '-' . fake()->numberBetween(100, 9999),
            'manufacturer' => fake()->company(),
            'serial_number' => fake()->bothify('SN###??###'),
            'installation_date' => fake()->dateTimeBetween('-10 years', '-1 year'),
            'last_maintenance_date' => fake()->optional(0.7)->dateTimeBetween('-1 year', 'now'),
            'next_maintenance_date' => fake()->optional(0.8)->dateTimeBetween('now', '+1 year'),
            'status' => fake()->randomElement($statuses),
            'mode' => fake()->randomElement($modes),
            'is_active' => fake()->boolean(90), // 90% chance of being active
        ];
    }
}
