<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\AssetHistory;
use Illuminate\Console\Command;

class SimulateSensorData extends Command
{
    protected $signature = 'sensor:simulate {asset_id?} {--performance} {--diagnostic} {--all}';
    protected $description = 'Simulate sensor data collection for assets';

    public function handle()
    {
        $assetId = $this->argument('asset_id');
        $assets = $assetId ? [Asset::find($assetId)] : Asset::active()->get();

        if (!$assets || ($assetId && !$assets[0])) {
            $this->error('Asset not found');
            return;
        }

        foreach ($assets as $asset) {
            if ($this->option('performance') || $this->option('all')) {
                $this->simulatePerformanceReading($asset);
            }

            if ($this->option('diagnostic') || $this->option('all')) {
                $this->simulateDiagnosticScan($asset);
            }

            if (!$this->option('performance') && !$this->option('diagnostic') && !$this->option('all')) {
                $this->simulatePerformanceReading($asset);
            }
        }

        $this->info('Sensor data simulation completed');
    }

    private function simulatePerformanceReading(Asset $asset)
    {
        $performanceData = [
            'efficiency' => rand(75, 98) + (rand(0, 99) / 100),
            'power_output' => rand(800, 1200) + (rand(0, 99) / 100),
            'runtime_hours' => rand(8, 16) + (rand(0, 59) / 60),
        ];

        $environmentalData = [
            'temperature' => rand(15, 45) + (rand(0, 99) / 100),
            'humidity' => rand(30, 80) + (rand(0, 99) / 100),
            'vibration_level' => rand(1, 8) + (rand(0, 999) / 1000),
        ];

        AssetHistory::recordPerformanceReading($asset, $performanceData, $environmentalData);

        $this->info("Performance data recorded for {$asset->name}");
    }

    private function simulateDiagnosticScan(Asset $asset)
    {
        $hasAnomalies = rand(1, 10) <= 2; // 20% chance of anomalies

        $diagnosticData = [
            'error_count' => $hasAnomalies ? rand(1, 5) : 0,
            'system_integrity' => rand(85, 100),
            'component_status' => [
                'motor' => rand(90, 100),
                'sensors' => rand(85, 100),
                'controller' => rand(88, 100),
            ],
        ];

        if ($hasAnomalies) {
            $diagnosticData['anomalies'] = [
                'High vibration detected',
                'Temperature spike observed',
            ];
            $diagnosticData['severity'] = rand(1, 3) == 3 ? 'critical' : 'medium';
        }

        AssetHistory::recordDiagnosticScan($asset, $diagnosticData);

        $this->info("Diagnostic scan recorded for {$asset->name}");
    }
}