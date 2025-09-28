<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Asset;
use Illuminate\Auth\Access\Response;

class AssetPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // VEO Admin and Site Managers can view all assets
        return $user->hasAnyRole(['veo_admin', 'site_manager', 'maintenance_staff', 'customer']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Asset $asset): bool
    {
        // VEO Admin can view all assets
        if ($user->hasRole('veo_admin')) {
            return true;
        }

        // Site Managers can view assets in their sites
        if ($user->hasRole('site_manager')) {
            return $user->sites()->where('sites.id', $asset->site_id)->exists();
        }

        // Maintenance Staff can view assets in their assigned sites
        if ($user->hasRole('maintenance_staff')) {
            return $user->sites()->where('sites.id', $asset->site_id)->exists();
        }

        // Customers can view assets in their sites
        if ($user->hasRole('customer')) {
            return $user->sites()->where('sites.id', $asset->site_id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only VEO Admin and Site Managers can create assets
        return $user->hasAnyRole(['veo_admin', 'site_manager']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Asset $asset): bool
    {
        // VEO Admin can update all assets
        if ($user->hasRole('veo_admin')) {
            return true;
        }

        // Site Managers can update assets in their sites
        if ($user->hasRole('site_manager')) {
            return $user->sites()->where('sites.id', $asset->site_id)->exists();
        }

        // Maintenance Staff can update basic asset information in their assigned sites
        if ($user->hasRole('maintenance_staff')) {
            return $user->sites()->where('sites.id', $asset->site_id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Asset $asset): bool
    {
        // Only VEO Admin can delete assets
        return $user->hasRole('veo_admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Asset $asset): bool
    {
        // Only VEO Admin can restore assets
        return $user->hasRole('veo_admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Asset $asset): bool
    {
        // Only VEO Admin can force delete assets
        return $user->hasRole('veo_admin');
    }

    /**
     * Determine whether the user can change asset status.
     */
    public function changeStatus(User $user, Asset $asset): bool
    {
        // VEO Admin can change any asset status
        if ($user->hasRole('veo_admin')) {
            return true;
        }

        // Site Managers can change asset status in their sites
        if ($user->hasRole('site_manager')) {
            return $user->sites()->where('sites.id', $asset->site_id)->exists();
        }

        // Maintenance Staff can change asset status in their assigned sites
        if ($user->hasRole('maintenance_staff')) {
            return $user->sites()->where('sites.id', $asset->site_id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can view asset history.
     */
    public function viewHistory(User $user, Asset $asset): bool
    {
        // All authenticated users can view history of assets they have access to
        return $this->view($user, $asset);
    }

    /**
     * Determine whether the user can generate reports.
     */
    public function generateReports(User $user, Asset $asset): bool
    {
        // VEO Admin, Site Managers, and Maintenance Staff can generate reports
        if ($user->hasAnyRole(['veo_admin', 'site_manager', 'maintenance_staff'])) {
            return $this->view($user, $asset);
        }

        return false;
    }

    /**
     * Determine whether the user can manage MQTT devices for this asset.
     */
    public function manageMqttDevices(User $user, Asset $asset): bool
    {
        // VEO Admin can manage all MQTT devices
        if ($user->hasRole('veo_admin')) {
            return true;
        }

        // Site Managers can manage MQTT devices in their sites
        if ($user->hasRole('site_manager')) {
            return $user->sites()->where('sites.id', $asset->site_id)->exists();
        }

        return false;
    }
}