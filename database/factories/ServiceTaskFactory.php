<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceTask>
 */
class ServiceTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['preventive', 'corrective', 'predictive', 'emergency'];
        $priorities = ['low', 'medium', 'high', 'critical'];
        $statuses = ['pending', 'in_progress', 'completed', 'cancelled', 'on_hold'];

        return [
            'asset_id' => \App\Models\Asset::factory(),
            'assigned_to' => \App\Models\User::factory(),
            'title' => fake()->randomElement([
                'Routine Maintenance Check',
                'Oil Change and Filter Replacement',
                'Electrical System Inspection',
                'Performance Optimization',
                'Safety System Verification',
                'Vibration Analysis',
                'Temperature Sensor Calibration',
                'Emergency Shutdown Test',
            ]),
            'description' => fake()->paragraph(3),
            'type' => fake()->randomElement($types),
            'priority' => fake()->randomElement($priorities),
            'status' => fake()->randomElement($statuses),
            'scheduled_date' => fake()->dateTimeBetween('-30 days', '+60 days'),
            'started_at' => fake()->optional(0.4)->dateTimeBetween('-15 days', 'now'),
            'completed_at' => fake()->optional(0.2)->dateTimeBetween('-10 days', 'now'),
            'estimated_duration_hours' => fake()->numberBetween(1, 24),
            'actual_duration_hours' => fake()->optional(0.3)->numberBetween(1, 30),
            'required_tools' => fake()->randomElements([
                'wrench_set', 'multimeter', 'crane', 'safety_harness',
                'thermal_camera', 'vibration_analyzer', 'torque_wrench'
            ], fake()->numberBetween(1, 4)),
            'required_materials' => fake()->randomElements([
                'oil_filter', 'hydraulic_fluid', 'grease', 'gaskets',
                'electrical_tape', 'cable_ties', 'spare_fuses'
            ], fake()->numberBetween(0, 3)),
            'safety_requirements' => [
                'ppe_required' => fake()->randomElements(['hard_hat', 'safety_glasses', 'gloves', 'steel_boots'], 3),
                'lockout_procedures' => fake()->boolean(70),
                'confined_space' => fake()->boolean(20),
                'working_at_height' => fake()->boolean(30),
            ],
            'notes' => fake()->optional(0.6)->text(150),
            'completion_notes' => fake()->optional(0.2)->text(100),
            'requires_shutdown' => fake()->boolean(25), // 25% chance requires shutdown
        ];
    }
}
