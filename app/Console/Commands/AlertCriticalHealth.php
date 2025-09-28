<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;

class AlertCriticalHealth extends Command
{
    protected $signature = 'alert:critical-health {--threshold=30} {--hours=2} {--test}';
    protected $description = 'Send alerts for assets with critical health scores';

    public function handle()
    {
        $threshold = (float) $this->option('threshold');
        $hours = (int) $this->option('hours');
        $isTest = $this->option('test');

        $this->info("🚨 Checking for critical asset health (threshold: {$threshold}%, window: {$hours}h)");

        $criticalAssets = $this->getCriticalAssets($threshold, $hours);

        if ($criticalAssets->isEmpty()) {
            $this->info('✅ No assets with critical health scores found');
            return;
        }

        $this->warn("⚠️  Found {$criticalAssets->count()} assets with critical health scores!");

        foreach ($criticalAssets as $assetData) {
            $asset = Asset::find($assetData->asset_id);
            if (!$asset) continue;

            $this->processAlertForAsset($asset, $assetData->avg_health, $threshold, $isTest);
        }

        $this->sendSummaryAlert($criticalAssets, $threshold, $isTest);
    }

    private function getCriticalAssets($threshold, $hours)
    {
        return DB::table('asset_histories as ah')
            ->select([
                'ah.asset_id',
                DB::raw('AVG(ah.health_score) as avg_health'),
                DB::raw('MIN(ah.health_score) as min_health'),
                DB::raw('MAX(ah.health_score) as max_health'),
                DB::raw('COUNT(ah.id) as reading_count'),
                'a.name as asset_name',
                'a.asset_type',
                'a.status',
                's.name as site_name'
            ])
            ->join('assets as a', 'ah.asset_id', '=', 'a.id')
            ->join('sites as s', 'a.site_id', '=', 's.id')
            ->where('ah.created_at', '>=', now()->subHours($hours))
            ->whereNotNull('ah.health_score')
            ->where('a.is_active', true)
            ->groupBy('ah.asset_id', 'a.name', 'a.asset_type', 'a.status', 's.name')
            ->havingRaw('AVG(ah.health_score) < ?', [$threshold])
            ->orderBy('avg_health', 'asc')
            ->get();
    }

    private function processAlertForAsset($asset, $avgHealth, $threshold, $isTest)
    {
        $alertLevel = $this->determineAlertLevel($avgHealth);
        $message = $this->buildAlertMessage($asset, $avgHealth, $alertLevel);

        $this->error("   🔴 {$asset->name}: {$avgHealth}% health");

        // Log the alert
        Log::critical('Critical asset health detected', [
            'asset_id' => $asset->id,
            'asset_name' => $asset->name,
            'site_name' => $asset->site->name,
            'health_score' => round($avgHealth, 2),
            'alert_level' => $alertLevel,
            'threshold' => $threshold,
            'is_test' => $isTest,
        ]);

        if (!$isTest) {
            // Send notifications to relevant users
            $this->sendUserNotifications($asset, $message, $alertLevel);

            // Create maintenance task if health is extremely low
            if ($avgHealth < 20) {
                $this->createEmergencyMaintenanceTask($asset, $avgHealth);
            }

            // Automatically change status if needed
            if ($asset->status === 'operational' && $avgHealth < 25) {
                $asset->changeStatus('maintenance', 'system');
                $this->warn("   📝 Automatically changed {$asset->name} to maintenance mode");
            }
        } else {
            $this->info("   🧪 TEST MODE: Would send alerts for {$asset->name}");
        }
    }

    private function determineAlertLevel($healthScore)
    {
        if ($healthScore < 15) return 'emergency';
        if ($healthScore < 25) return 'critical';
        if ($healthScore < 35) return 'warning';
        return 'info';
    }

    private function buildAlertMessage($asset, $avgHealth, $alertLevel)
    {
        $urgency = match($alertLevel) {
            'emergency' => '🚨 EMERGENCY',
            'critical' => '🔴 CRITICAL',
            'warning' => '⚠️ WARNING',
            default => '💡 INFO'
        };

        return "{$urgency}: Asset '{$asset->name}' at {$asset->site->name} has critical health score of " .
               round($avgHealth, 1) . "%. Immediate attention required.";
    }

    private function sendUserNotifications($asset, $message, $alertLevel)
    {
        // Get users who should receive alerts for this asset/site
        $users = $this->getRelevantUsers($asset);

        foreach ($users as $user) {
            try {
                // Send Filament notification
                Notification::make()
                    ->title('Critical Asset Health Alert')
                    ->body($message)
                    ->danger()
                    ->persistent()
                    ->sendToDatabase($user);

                // For critical alerts, also send email
                if (in_array($alertLevel, ['emergency', 'critical'])) {
                    $this->sendEmailAlert($user, $asset, $message);
                }

            } catch (\Exception $e) {
                Log::error('Failed to send alert notification', [
                    'user_id' => $user->id,
                    'asset_id' => $asset->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function getRelevantUsers($asset)
    {
        // Get users who should be alerted for this asset
        return User::where(function ($query) use ($asset) {
            $query->whereHas('roles', function ($roleQuery) {
                $roleQuery->whereIn('name', ['VEO Admin', 'Site Manager', 'Maintenance Staff']);
            })
            ->orWhere('site_id', $asset->site_id); // Users assigned to this site
        })->get();
    }

    private function sendEmailAlert($user, $asset, $message)
    {
        // In production, implement actual email sending
        Log::info('Email alert would be sent', [
            'to' => $user->email,
            'subject' => 'Critical Asset Health Alert',
            'asset' => $asset->name,
            'message' => $message,
        ]);
    }

    private function createEmergencyMaintenanceTask($asset, $healthScore)
    {
        try {
            DB::table('service_tasks')->insert([
                'asset_id' => $asset->id,
                'title' => 'Emergency Maintenance - Critical Health Score',
                'description' => "Asset {$asset->name} has extremely low health score of " .
                               round($healthScore, 1) . "%. Immediate maintenance required.",
                'priority' => 'emergency',
                'status' => 'pending',
                'assigned_to' => null, // Will be assigned by maintenance supervisor
                'due_date' => now()->addHours(4), // 4-hour emergency response
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->warn("   📋 Created emergency maintenance task for {$asset->name}");

        } catch (\Exception $e) {
            Log::error('Failed to create emergency maintenance task', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendSummaryAlert($criticalAssets, $threshold, $isTest)
    {
        $summary = [
            'total_critical_assets' => $criticalAssets->count(),
            'emergency_level' => $criticalAssets->where('avg_health', '<', 15)->count(),
            'critical_level' => $criticalAssets->whereBetween('avg_health', [15, 25])->count(),
            'warning_level' => $criticalAssets->whereBetween('avg_health', [25, $threshold])->count(),
            'threshold' => $threshold,
            'check_time' => now()->toISOString(),
            'is_test' => $isTest,
        ];

        Log::warning('Critical health summary', $summary);

        // Send summary to VEO admins
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'VEO Admin');
        })->get();

        foreach ($admins as $admin) {
            Notification::make()
                ->title('Critical Health Summary')
                ->body("Found {$criticalAssets->count()} assets with health below {$threshold}%")
                ->warning()
                ->sendToDatabase($admin);
        }

        if (!$isTest) {
            $this->info("📊 Summary alert sent to " . $admins->count() . " administrators");
        }
    }
}