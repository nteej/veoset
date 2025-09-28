<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'name',
        'description',
        'asset_type',
        'model',
        'manufacturer',
        'serial_number',
        'installation_date',
        'last_maintenance_date',
        'next_maintenance_date',
        'status',
        'mode',
        'is_active',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function metadata(): HasOne
    {
        return $this->hasOne(AssetMetadata::class);
    }

    public function serviceTasks(): HasMany
    {
        return $this->hasMany(ServiceTask::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(AssetHistory::class);
    }

    public function latestHistory(): HasOne
    {
        return $this->hasOne(AssetHistory::class)->latest();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOperational($query)
    {
        return $query->where('status', 'operational');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('asset_type', $type);
    }

    public function scopeNeedsMaintenance($query)
    {
        return $query->where('next_maintenance_date', '<=', now());
    }

    public function isOperational(): bool
    {
        return $this->status === 'operational';
    }

    public function needsMaintenance(): bool
    {
        return $this->next_maintenance_date && $this->next_maintenance_date <= now();
    }

    public function changeStatus(string $newStatus, $changedBy = null): bool
    {
        $previousStatus = $this->status;

        if ($previousStatus === $newStatus) {
            return false; // No change needed
        }

        $this->status = $newStatus;
        $this->save();

        // Record status change in history
        \App\Models\AssetHistory::recordStatusChange($this, $previousStatus, $newStatus, $changedBy);

        // Broadcast the status change (only if broadcasting is enabled)
        if (config('broadcasting.default') === 'pusher') {
            \App\Events\AssetStatusChanged::dispatch($this, $previousStatus, $newStatus, $changedBy);
        } else {
            // Just log the change when broadcasting is disabled
            \Illuminate\Support\Facades\Log::info('Asset status changed', [
                'asset_id' => $this->id,
                'asset_name' => $this->name,
                'site_id' => $this->site_id,
                'site_name' => $this->site->name,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'changed_by' => $changedBy,
            ]);
        }

        return true;
    }

    public function setMaintenanceMode($changedBy = null): bool
    {
        return $this->changeStatus('maintenance', $changedBy);
    }

    public function setOperational($changedBy = null): bool
    {
        return $this->changeStatus('operational', $changedBy);
    }

    public function setOffline($changedBy = null): bool
    {
        return $this->changeStatus('offline', $changedBy);
    }

    public function setEmergency($changedBy = null): bool
    {
        return $this->changeStatus('emergency', $changedBy);
    }

    public function simulateStatusChange(string $newStatus, $changedBy = 'sensor'): bool
    {
        // This method can be used for sensor-based status changes
        return $this->changeStatus($newStatus, $changedBy);
    }
}
