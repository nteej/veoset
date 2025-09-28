<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\MqttDevice;
use App\Policies\AssetPolicy;
use App\Policies\AssetHistoryPolicy;
use App\Policies\MqttDevicePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Asset::class => AssetPolicy::class,
        AssetHistory::class => AssetHistoryPolicy::class,
        MqttDevice::class => MqttDevicePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define additional gates for granular permissions

        // Asset-related gates
        Gate::define('manage-assets', function ($user) {
            return $user->hasAnyRole(['veo_admin', 'site_manager']);
        });

        Gate::define('view-all-assets', function ($user) {
            return $user->hasRole('veo_admin');
        });

        Gate::define('change-asset-status', function ($user, $asset) {
            return $user->can('changeStatus', $asset);
        });

        // Asset History gates
        Gate::define('record-performance', function ($user) {
            return $user->hasAnyRole(['veo_admin', 'site_manager', 'maintenance_staff']);
        });

        Gate::define('end-shift', function ($user) {
            return $user->hasAnyRole(['site_manager', 'maintenance_staff']);
        });

        Gate::define('generate-reports', function ($user) {
            return $user->hasAnyRole(['veo_admin', 'site_manager', 'maintenance_staff']);
        });

        // MQTT Device gates
        Gate::define('manage-mqtt-devices', function ($user) {
            return $user->hasAnyRole(['veo_admin', 'site_manager']);
        });

        Gate::define('send-device-commands', function ($user, $mqttDevice) {
            return $user->can('sendCommand', $mqttDevice);
        });

        Gate::define('configure-devices', function ($user, $mqttDevice) {
            return $user->can('configure', $mqttDevice);
        });

        Gate::define('view-real-time-data', function ($user, $mqttDevice) {
            return $user->can('viewRealTimeData', $mqttDevice);
        });

        // Role-based gates
        Gate::define('veo-admin-access', function ($user) {
            return $user->hasRole('veo_admin');
        });

        Gate::define('site-manager-access', function ($user) {
            return $user->hasRole('site_manager');
        });

        Gate::define('maintenance-staff-access', function ($user) {
            return $user->hasRole('maintenance_staff');
        });

        Gate::define('customer-access', function ($user) {
            return $user->hasRole('customer');
        });

        // Site-specific access
        Gate::define('access-site', function ($user, $siteId) {
            if ($user->hasRole('veo_admin')) {
                return true;
            }
            return $user->sites()->where('sites.id', $siteId)->exists();
        });

        // Navigation-related gates for Filament
        Gate::define('view-asset-management', function ($user) {
            return $user->hasAnyRole(['veo_admin', 'site_manager', 'maintenance_staff', 'customer']);
        });

        Gate::define('view-iot-management', function ($user) {
            return $user->hasAnyRole(['veo_admin', 'site_manager']);
        });

        Gate::define('view-system-admin', function ($user) {
            return $user->hasRole('veo_admin');
        });
    }
}