<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\AssetHistory;
use Illuminate\Console\Command;

class CollectSensorData extends Command
{
    protected $signature = 'sensor:collect {--all} {--asset=} {--site=}';
    protected $description = 'Collect sensor data from assets automatically';

    public function handle()
    {
        $this->info('Starting automated sensor data collection...');

        $assets = $this->getAssetsForCollection();

        if ($assets->isEmpty()) {
            $this->warn('No assets found for data collection');
            return;
        }

        $collected = 0;
        $errors = 0;

        foreach ($assets as $asset) {
            try {
                $this->collectAssetData($asset);
                $collected++;

                if ($this->option('verbose')) {
                    $this->info("✓ Collected data for {$asset->name}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Failed to collect data for {$asset->name}: {$e->getMessage()}");
            }
        }

        $this->info("Data collection completed: {$collected} successful, {$errors} errors");
    }

    private function getAssetsForCollection()
    {
        $query = Asset::active();

        if ($this->option('asset')) {
            return $query->where('id', $this->option('asset'))->get();
        }

        if ($this->option('site')) {
            return $query->where('site_id', $this->option('site'))->get();
        }

        if ($this->option('all')) {
            return $query->get();
        }

        // Default: collect from operational assets only
        return $query->where('status', 'operational')->get();
    }

    private function collectAssetData(Asset $asset)
    {
        // Simulate sensor data collection
        $performanceData = $this->generatePerformanceData($asset);
        $environmentalData = $this->generateEnvironmentalData($asset);

        // Record performance reading
        AssetHistory::recordPerformanceReading($asset, $performanceData, $environmentalData);

        // Occasionally run diagnostic scans (20% chance)
        if (rand(1, 100) <= 20) {
            $diagnosticData = $this->generateDiagnosticData($asset);
            AssetHistory::recordDiagnosticScan($asset, $diagnosticData);
        }

        // Check for status changes based on health score
        $healthScore = AssetHistory::calculateHealthScore($performanceData, [], $environmentalData);
        $this->checkForStatusChanges($asset, $healthScore);
    }

    private function generatePerformanceData(Asset $asset): array
    {
        $baseEfficiency = match($asset->asset_type) {
            'turbine' => rand(85, 95),
            'solar' => rand(80, 92),
            'transformer' => rand(95, 99),
            default => rand(80, 95)
        };

        $basePower = match($asset->asset_type) {
            'turbine' => rand(2000, 2500),
            'solar' => rand(500, 800),
            'transformer' => rand(5000, 10000),
            default => rand(500, 2000)
        };

        return [
            'efficiency' => $baseEfficiency + (rand(-5, 5)),
            'power_output' => $basePower + (rand(-200, 200)),
            'runtime_hours' => rand(20, 24),
            'energy_produced' => $basePower * rand(18, 24),
        ];
    }

    private function generateEnvironmentalData(Asset $asset): array
    {
        return [
            'temperature' => rand(15, 35) + (rand(0, 99) / 100),
            'humidity' => rand(40, 70) + (rand(0, 99) / 100),
            'vibration_level' => rand(1, 5) + (rand(0, 999) / 1000),
            'wind_speed' => $asset->asset_type === 'turbine' ? rand(5, 15) : null,
            'solar_irradiance' => $asset->asset_type === 'solar' ? rand(800, 1200) : null,
        ];
    }

    private function generateDiagnosticData(Asset $asset): array
    {
        $hasIssues = rand(1, 100) <= 15; // 15% chance of issues

        $data = [
            'error_count' => $hasIssues ? rand(1, 3) : 0,
            'system_integrity' => rand(85, 100),
            'component_status' => [
                'motor' => rand(90, 100),
                'sensors' => rand(85, 100),
                'controller' => rand(88, 100),
                'power_electronics' => rand(92, 100),
            ],
            'maintenance_alerts' => [],
        ];

        if ($hasIssues) {
            $issues = [
                'Elevated vibration detected',
                'Temperature sensor reading anomaly',
                'Power output fluctuation',
                'Communication timeout detected',
            ];

            $data['anomalies'] = [array_rand(array_flip($issues))];
            $data['severity'] = rand(1, 10) > 8 ? 'critical' : 'medium';
        }

        return $data;
    }

    private function checkForStatusChanges(Asset $asset, float $healthScore)
    {
        $currentStatus = $asset->status;

        // Auto-status change logic based on health score
        if ($healthScore < 40 && $currentStatus === 'operational') {
            $asset->changeStatus('maintenance', 'system');
            $this->warn("Asset {$asset->name} automatically switched to maintenance (health: {$healthScore}%)");
        } elseif ($healthScore >= 75 && $currentStatus === 'maintenance') {
            $asset->changeStatus('operational', 'system');
            $this->info("Asset {$asset->name} automatically restored to operational (health: {$healthScore}%)");
        }
    }
}