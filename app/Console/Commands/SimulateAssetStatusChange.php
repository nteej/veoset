<?php

namespace App\Console\Commands;

use App\Models\Asset;
use Illuminate\Console\Command;

class SimulateAssetStatusChange extends Command
{
    protected $signature = 'simulate:asset-status {asset_id?} {status?} {--random} {--all}';

    protected $description = 'Simulate asset status changes for testing real-time updates';

    public function handle()
    {
        if ($this->option('all')) {
            $this->simulateRandomStatusChanges();
            return;
        }

        if ($this->option('random')) {
            $this->simulateRandomStatusChange();
            return;
        }

        $assetId = $this->argument('asset_id');
        $status = $this->argument('status');

        if (!$assetId || !$status) {
            $this->error('Please provide asset_id and status, or use --random or --all option');
            $this->line('Usage: php artisan simulate:asset-status <asset_id> <status>');
            $this->line('       php artisan simulate:asset-status --random');
            $this->line('       php artisan simulate:asset-status --all');
            $this->line('');
            $this->line('Available statuses: operational, maintenance, offline, emergency');
            return;
        }

        $asset = Asset::find($assetId);
        if (!$asset) {
            $this->error("Asset with ID {$assetId} not found");
            return;
        }

        $validStatuses = ['operational', 'maintenance', 'offline', 'emergency'];
        if (!in_array($status, $validStatuses)) {
            $this->error("Invalid status. Valid statuses: " . implode(', ', $validStatuses));
            return;
        }

        $previousStatus = $asset->status;
        $changed = $asset->changeStatus($status, 'simulation');

        if ($changed) {
            $this->info("✅ Asset '{$asset->name}' status changed from '{$previousStatus}' to '{$status}'");
            $this->line("   Site: {$asset->site->name}");
            $this->line("   Real-time update broadcasted to Pusher channels");
        } else {
            $this->warn("No change needed - asset was already in '{$status}' status");
        }
    }

    private function simulateRandomStatusChange()
    {
        $assets = Asset::with('site')->get();

        if ($assets->isEmpty()) {
            $this->error('No assets found in database');
            return;
        }

        $asset = $assets->random();
        $statuses = ['operational', 'maintenance', 'offline', 'emergency'];
        $currentStatus = $asset->status;

        // Remove current status to ensure we always change to something different
        $availableStatuses = array_filter($statuses, fn($status) => $status !== $currentStatus);
        $newStatus = collect($availableStatuses)->random();

        $changed = $asset->changeStatus($newStatus, 'sensor_automation');

        if ($changed) {
            $this->info("🎲 Random status change simulated:");
            $this->line("   Asset: {$asset->name}");
            $this->line("   Site: {$asset->site->name}");
            $this->line("   Status: {$currentStatus} → {$newStatus}");
            $this->line("   Real-time update broadcasted");
        }
    }

    private function simulateRandomStatusChanges()
    {
        $assets = Asset::with('site')->get();

        if ($assets->isEmpty()) {
            $this->error('No assets found in database');
            return;
        }

        $this->info('🔄 Simulating multiple random status changes...');
        $this->line('');

        $changes = rand(2, 5); // Random number of changes
        for ($i = 0; $i < $changes; $i++) {
            $this->simulateRandomStatusChange();

            if ($i < $changes - 1) {
                $this->line('');
                sleep(2); // Wait 2 seconds between changes
            }
        }

        $this->line('');
        $this->info("✨ Completed {$changes} status change simulations");
    }
}
