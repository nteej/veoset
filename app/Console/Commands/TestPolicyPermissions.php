<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\MqttDevice;
use Illuminate\Console\Command;

class TestPolicyPermissions extends Command
{
    protected $signature = 'test:policies {user_email}';

    protected $description = 'Test policy permissions for a specific user';

    public function handle(): int
    {
        $userEmail = $this->argument('user_email');
        $user = User::where('email', $userEmail)->first();

        if (!$user) {
            $this->error("User with email '{$userEmail}' not found.");
            return Command::FAILURE;
        }

        $this->info("Testing policies for user: {$user->name} ({$user->email})");
        $this->info("Roles: " . $user->getRoleNames()->implode(', '));
        $this->newLine();

        // Test Asset policies
        $this->testAssetPolicies($user);

        // Test AssetHistory policies
        $this->testAssetHistoryPolicies($user);

        // Test MqttDevice policies
        $this->testMqttDevicePolicies($user);

        // Test Gates
        $this->testGates($user);

        return Command::SUCCESS;
    }

    private function testAssetPolicies(User $user): void
    {
        $this->info('Asset Policy Tests:');
        $this->line('==================');

        $this->testPermission($user, 'viewAny', Asset::class);
        $this->testPermission($user, 'create', Asset::class);

        $asset = Asset::first();
        if ($asset) {
            $this->testPermission($user, 'view', $asset);
            $this->testPermission($user, 'update', $asset);
            $this->testPermission($user, 'delete', $asset);
            $this->testPermission($user, 'changeStatus', $asset);
            $this->testPermission($user, 'manageMqttDevices', $asset);
        }

        $this->newLine();
    }

    private function testAssetHistoryPolicies(User $user): void
    {
        $this->info('Asset History Policy Tests:');
        $this->line('===========================');

        $this->testPermission($user, 'viewAny', AssetHistory::class);
        $this->testPermission($user, 'create', AssetHistory::class);

        $history = AssetHistory::first();
        if ($history) {
            $this->testPermission($user, 'view', $history);
            $this->testPermission($user, 'update', $history);
            $this->testPermission($user, 'delete', $history);
            $this->testPermission($user, 'generateReports', $history);
        }

        $this->newLine();
    }

    private function testMqttDevicePolicies(User $user): void
    {
        $this->info('MQTT Device Policy Tests:');
        $this->line('=========================');

        $this->testPermission($user, 'viewAny', MqttDevice::class);
        $this->testPermission($user, 'create', MqttDevice::class);

        $device = MqttDevice::first();
        if ($device) {
            $this->testPermission($user, 'view', $device);
            $this->testPermission($user, 'update', $device);
            $this->testPermission($user, 'delete', $device);
            $this->testPermission($user, 'sendCommand', $device);
            $this->testPermission($user, 'configure', $device);
        }

        $this->newLine();
    }

    private function testGates(User $user): void
    {
        $this->info('Gate Tests:');
        $this->line('===========');

        $gates = [
            'manage-assets',
            'view-all-assets',
            'record-performance',
            'end-shift',
            'generate-reports',
            'manage-mqtt-devices',
            'veo-admin-access',
            'site-manager-access',
            'maintenance-staff-access',
            'customer-access',
            'view-asset-management',
            'view-iot-management',
            'view-system-admin',
        ];

        foreach ($gates as $gate) {
            $canAccess = $user->can($gate);
            $status = $canAccess ? '✅ ALLOWED' : '❌ DENIED';
            $this->line("  {$gate}: {$status}");
        }

        $this->newLine();
    }

    private function testPermission(User $user, string $ability, $model): void
    {
        $canAccess = $user->can($ability, $model);
        $status = $canAccess ? '✅ ALLOWED' : '❌ DENIED';

        $modelName = is_string($model) ? class_basename($model) : class_basename($model::class);
        $this->line("  {$ability} {$modelName}: {$status}");
    }
}