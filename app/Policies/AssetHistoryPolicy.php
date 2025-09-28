<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AssetHistory;
use Illuminate\Auth\Access\Response;

class AssetHistoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view asset history
        return $user->hasAnyRole(['veo_admin', 'site_manager', 'maintenance_staff', 'customer']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AssetHistory $assetHistory): bool
    {
        // VEO Admin can view all asset history
        if ($user->hasRole('veo_admin')) {
            return true;
        }

        // Check if user has access to the associated asset
        $asset = $assetHistory->asset;
        if (!$asset) {
            return false;
        }

        // Site Managers can view history for assets in their sites
        if ($user->hasRole('site_manager')) {
            return $user->sites()->where('sites.id', $asset->site_id)->exists();
        }

        // Maintenance Staff can view history for assets in their assigned sites
        if ($user->hasRole('maintenance_staff')) {
            return $user->sites()->where('sites.id', $asset->site_id)->exists();
        }

        // Customers can view history for assets in their sites
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
        // VEO Admin, Site Managers, and Maintenance Staff can create asset history records
        return $user->hasAnyRole(['veo_admin', 'site_manager', 'maintenance_staff']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AssetHistory $assetHistory): bool
    {
        // Asset history records should generally not be editable
        // Only VEO Admin can update asset history for data correction purposes
        if ($user->hasRole('veo_admin')) {
            return true;
        }

        // Allow updating only if the record was created by the same user and within last 30 minutes
        if ($assetHistory->recorded_by === $user->id &&
            $assetHistory->created_at->diffInMinutes(now()) <= 30) {

            $asset = $assetHistory->asset;
            if ($asset) {
                // Site Managers can update their own recent records in their sites
                if ($user->hasRole('site_manager')) {
                    return $user->sites()->where('sites.id', $asset->site_id)->exists();
                }

                // Maintenance Staff can update their own recent records in their assigned sites
                if ($user->hasRole('maintenance_staff')) {
                    return $user->sites()->where('sites.id', $asset->site_id)->exists();
                }
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AssetHistory $assetHistory): bool
    {
        // Only VEO Admin can delete asset history records
        return $user->hasRole('veo_admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AssetHistory $assetHistory): bool
    {
        // Only VEO Admin can restore asset history records
        return $user->hasRole('veo_admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AssetHistory $assetHistory): bool
    {
        // Only VEO Admin can force delete asset history records
        return $user->hasRole('veo_admin');
    }

    /**
     * Determine whether the user can generate reports from asset history.
     */
    public function generateReports(User $user, AssetHistory $assetHistory): bool
    {
        // VEO Admin, Site Managers, and Maintenance Staff can generate reports
        if ($user->hasAnyRole(['veo_admin', 'site_manager', 'maintenance_staff'])) {
            return $this->view($user, $assetHistory);
        }

        return false;
    }

    /**
     * Determine whether the user can record performance readings.
     */
    public function recordPerformance(User $user): bool
    {
        // VEO Admin, Site Managers, and Maintenance Staff can record performance
        return $user->hasAnyRole(['veo_admin', 'site_manager', 'maintenance_staff']);
    }

    /**
     * Determine whether the user can record status changes.
     */
    public function recordStatusChange(User $user): bool
    {
        // VEO Admin, Site Managers, and Maintenance Staff can record status changes
        return $user->hasAnyRole(['veo_admin', 'site_manager', 'maintenance_staff']);
    }

    /**
     * Determine whether the user can record diagnostic scans.
     */
    public function recordDiagnostic(User $user): bool
    {
        // VEO Admin, Site Managers, and Maintenance Staff can record diagnostics
        return $user->hasAnyRole(['veo_admin', 'site_manager', 'maintenance_staff']);
    }

    /**
     * Determine whether the user can end shift and generate health status.
     */
    public function endShift(User $user): bool
    {
        // Only Maintenance Staff and Site Managers can end shift
        return $user->hasAnyRole(['site_manager', 'maintenance_staff']);
    }

    /**
     * Determine whether the user can view detailed diagnostic data.
     */
    public function viewDiagnosticData(User $user, AssetHistory $assetHistory): bool
    {
        // VEO Admin and Site Managers can view detailed diagnostic data
        if ($user->hasAnyRole(['veo_admin', 'site_manager'])) {
            return $this->view($user, $assetHistory);
        }

        // Maintenance Staff can view diagnostic data for assets they maintain
        if ($user->hasRole('maintenance_staff')) {
            return $this->view($user, $assetHistory);
        }

        return false;
    }
}