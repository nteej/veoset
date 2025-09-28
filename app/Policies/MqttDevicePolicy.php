<?php

namespace App\Policies;

use App\Models\User;
use App\Models\MqttDevice;

class MqttDevicePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // VEO Admin and Site Managers can view MQTT devices
        return $user->hasAnyRole(['veo_admin', 'site_manager']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MqttDevice $mqttDevice): bool
    {
        // VEO Admin can view all MQTT devices
        if ($user->hasRole('veo_admin')) {
            return true;
        }

        // Site Managers can view MQTT devices for assets in their sites
        if ($user->hasRole('site_manager')) {
            $asset = $mqttDevice->asset;
            if ($asset) {
                return $user->sites()->where('sites.id', $asset->site_id)->exists();
            }
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only VEO Admin and Site Managers can create MQTT devices
        return $user->hasAnyRole(['veo_admin', 'site_manager']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MqttDevice $mqttDevice): bool
    {
        // VEO Admin can update all MQTT devices
        if ($user->hasRole('veo_admin')) {
            return true;
        }

        // Site Managers can update MQTT devices for assets in their sites
        if ($user->hasRole('site_manager')) {
            $asset = $mqttDevice->asset;
            if ($asset) {
                return $user->sites()->where('sites.id', $asset->site_id)->exists();
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MqttDevice $mqttDevice): bool
    {
        // Only VEO Admin can delete MQTT devices
        return $user->hasRole('veo_admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MqttDevice $mqttDevice): bool
    {
        // Only VEO Admin can restore MQTT devices
        return $user->hasRole('veo_admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MqttDevice $mqttDevice): bool
    {
        // Only VEO Admin can force delete MQTT devices
        return $user->hasRole('veo_admin');
    }

    /**
     * Determine whether the user can send commands to the device.
     */
    public function sendCommand(User $user, MqttDevice $mqttDevice): bool
    {
        // VEO Admin can send commands to all devices
        if ($user->hasRole('veo_admin')) {
            return true;
        }

        // Site Managers can send commands to devices in their sites
        if ($user->hasRole('site_manager')) {
            $asset = $mqttDevice->asset;
            if ($asset) {
                return $user->sites()->where('sites.id', $asset->site_id)->exists();
            }
        }

        return false;
    }

    /**
     * Determine whether the user can configure the device.
     */
    public function configure(User $user, MqttDevice $mqttDevice): bool
    {
        // VEO Admin can configure all devices
        if ($user->hasRole('veo_admin')) {
            return true;
        }

        // Site Managers can configure devices in their sites
        if ($user->hasRole('site_manager')) {
            $asset = $mqttDevice->asset;
            if ($asset) {
                return $user->sites()->where('sites.id', $asset->site_id)->exists();
            }
        }

        return false;
    }

    /**
     * Determine whether the user can view device health metrics.
     */
    public function viewHealth(User $user, MqttDevice $mqttDevice): bool
    {
        // Same as view permission
        return $this->view($user, $mqttDevice);
    }

    /**
     * Determine whether the user can activate/deactivate the device.
     */
    public function toggleActive(User $user, MqttDevice $mqttDevice): bool
    {
        // VEO Admin can activate/deactivate all devices
        if ($user->hasRole('veo_admin')) {
            return true;
        }

        // Site Managers can activate/deactivate devices in their sites
        if ($user->hasRole('site_manager')) {
            $asset = $mqttDevice->asset;
            if ($asset) {
                return $user->sites()->where('sites.id', $asset->site_id)->exists();
            }
        }

        return false;
    }

    /**
     * Determine whether the user can view real-time data from the device.
     */
    public function viewRealTimeData(User $user, MqttDevice $mqttDevice): bool
    {
        // VEO Admin can view all real-time data
        if ($user->hasRole('veo_admin')) {
            return true;
        }

        // Site Managers and Maintenance Staff can view real-time data for devices in their sites
        if ($user->hasAnyRole(['site_manager', 'maintenance_staff'])) {
            $asset = $mqttDevice->asset;
            if ($asset) {
                return $user->sites()->where('sites.id', $asset->site_id)->exists();
            }
        }

        return false;
    }

    /**
     * Determine whether the user can request data updates from the device.
     */
    public function requestUpdate(User $user, MqttDevice $mqttDevice): bool
    {
        // VEO Admin can request updates from all devices
        if ($user->hasRole('veo_admin')) {
            return true;
        }

        // Site Managers and Maintenance Staff can request updates for devices in their sites
        if ($user->hasAnyRole(['site_manager', 'maintenance_staff'])) {
            $asset = $mqttDevice->asset;
            if ($asset) {
                return $user->sites()->where('sites.id', $asset->site_id)->exists();
            }
        }

        return false;
    }
}