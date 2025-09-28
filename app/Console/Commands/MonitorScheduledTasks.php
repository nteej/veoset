<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MonitorScheduledTasks extends Command
{
    protected $signature = 'monitor:scheduled-tasks {--report} {--alert-threshold=30}';
    protected $description = 'Monitor the health and execution of scheduled tasks';

    public function handle()
    {
        $this->info('🔍 VEO Scheduled Task Monitor');
        $this->info('===========================');

        $this->checkSchedulerHealth();
        $this->checkSensorDataCollection();
        $this->checkAssetHealthStatus();

        if ($this->option('report')) {
            $this->generateDetailedReport();
        }
    }

    private function checkSchedulerHealth()
    {
        $this->info('📊 Checking Scheduler Health...');

        // Check if scheduler has run recently (within last 5 minutes)
        $lastScheduleRun = $this->getLastScheduleRunTime();

        if ($lastScheduleRun && $lastScheduleRun->diffInMinutes(now()) <= 5) {
            $this->info("✅ Scheduler is healthy (last run: {$lastScheduleRun->format('Y-m-d H:i:s')})");
        } else {
            $this->error("❌ Scheduler appears to be down (last run: " . ($lastScheduleRun ? $lastScheduleRun->format('Y-m-d H:i:s') : 'unknown') . ")");
            $this->alertSchedulerDown();
        }
    }

    private function checkSensorDataCollection()
    {
        $this->info('📡 Checking Sensor Data Collection...');

        // Check recent sensor data collection (last hour)
        $recentCollections = DB::table('asset_histories')
            ->where('event_type', 'performance_reading')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        $activeAssets = DB::table('assets')->where('is_active', true)->count();

        $expectedCollections = $activeAssets * 4; // Every 15 minutes = 4 times per hour

        if ($recentCollections >= ($expectedCollections * 0.8)) {
            $this->info("✅ Sensor data collection is healthy ({$recentCollections}/{$expectedCollections} expected)");
        } else {
            $this->warn("⚠️  Low sensor data collection rate ({$recentCollections}/{$expectedCollections} expected)");
            Log::warning('Low sensor data collection rate', [
                'collected' => $recentCollections,
                'expected' => $expectedCollections,
                'rate' => round(($recentCollections / $expectedCollections) * 100, 2) . '%'
            ]);
        }
    }

    private function checkAssetHealthStatus()
    {
        $this->info('💊 Checking Asset Health Status...');

        $criticalAssets = DB::table('asset_histories')
            ->select('asset_id', DB::raw('AVG(health_score) as avg_health'))
            ->where('created_at', '>=', now()->subHours(2))
            ->whereNotNull('health_score')
            ->groupBy('asset_id')
            ->havingRaw('AVG(health_score) < ?', [$this->option('alert-threshold')])
            ->get();

        if ($criticalAssets->isEmpty()) {
            $this->info('✅ No assets with critical health scores');
        } else {
            $this->error("❌ {$criticalAssets->count()} assets with critical health scores!");

            foreach ($criticalAssets as $asset) {
                $assetName = DB::table('assets')->where('id', $asset->asset_id)->value('name');
                $this->error("   • {$assetName} (Health: " . round($asset->avg_health, 1) . "%)");

                $this->alertCriticalAssetHealth($asset->asset_id, $assetName, $asset->avg_health);
            }
        }
    }

    private function generateDetailedReport()
    {
        $this->info('📈 Generating Detailed Report...');
        $this->newLine();

        // Task execution statistics
        $this->table(
            ['Metric', 'Value', 'Status'],
            [
                ['Total Active Assets', DB::table('assets')->where('is_active', true)->count(), '✅'],
                ['Performance Readings (24h)', $this->getPerformanceReadingsCount(24), '📊'],
                ['Diagnostic Scans (24h)', $this->getDiagnosticScansCount(24), '🔍'],
                ['Status Changes (24h)', $this->getStatusChangesCount(24), '🔄'],
                ['Critical Health Assets', $this->getCriticalHealthAssetsCount(), '⚠️'],
                ['Operational Assets', DB::table('assets')->where('status', 'operational')->count(), '✅'],
                ['Maintenance Assets', DB::table('assets')->where('status', 'maintenance')->count(), '🔧'],
            ]
        );

        // Recent failures or issues
        $this->info('🚨 Recent Issues (Last 24h):');
        $issues = $this->getRecentIssues();

        if ($issues->isEmpty()) {
            $this->info('   No issues detected');
        } else {
            foreach ($issues as $issue) {
                $this->warn("   • {$issue->created_at}: {$issue->issue}");
            }
        }
    }

    private function getLastScheduleRunTime()
    {
        // Check Laravel logs for scheduler execution
        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            return null;
        }

        $lines = array_slice(file($logFile), -100); // Check last 100 lines

        foreach (array_reverse($lines) as $line) {
            if (strpos($line, 'schedule:run') !== false || strpos($line, 'sensor:collect') !== false) {
                preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches);
                if (!empty($matches[1])) {
                    return Carbon::parse($matches[1]);
                }
            }
        }

        return null;
    }

    private function getPerformanceReadingsCount($hours)
    {
        return DB::table('asset_histories')
            ->where('event_type', 'performance_reading')
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();
    }

    private function getDiagnosticScansCount($hours)
    {
        return DB::table('asset_histories')
            ->where('event_type', 'diagnostic_scan')
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();
    }

    private function getStatusChangesCount($hours)
    {
        return DB::table('asset_histories')
            ->where('event_type', 'status_change')
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();
    }

    private function getCriticalHealthAssetsCount()
    {
        return DB::table('asset_histories')
            ->select('asset_id')
            ->where('created_at', '>=', now()->subHours(2))
            ->whereNotNull('health_score')
            ->groupBy('asset_id')
            ->havingRaw('AVG(health_score) < ?', [$this->option('alert-threshold')])
            ->count();
    }

    private function getRecentIssues()
    {
        return DB::table('asset_histories')
            ->select('created_at', 'anomaly_description as issue')
            ->where('anomaly_detected', true)
            ->where('severity_level', 'critical')
            ->where('created_at', '>=', now()->subDay())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    private function alertSchedulerDown()
    {
        Log::critical('VEO Scheduler appears to be down', [
            'timestamp' => now()->toISOString(),
            'check_time' => now()->format('Y-m-d H:i:s'),
        ]);

        // In production, this would integrate with monitoring services
        // like Slack, email, PagerDuty, etc.
    }

    private function alertCriticalAssetHealth($assetId, $assetName, $healthScore)
    {
        Log::critical('Asset health critical', [
            'asset_id' => $assetId,
            'asset_name' => $assetName,
            'health_score' => round($healthScore, 2),
            'threshold' => $this->option('alert-threshold'),
            'timestamp' => now()->toISOString(),
        ]);

        // In production, this would send notifications to relevant stakeholders
    }
}