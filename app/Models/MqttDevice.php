<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class MqttDevice extends Model
{
    protected $fillable = [
        'device_id',
        'asset_id',
        'device_type',
        'manufacturer',
        'model',
        'firmware_version',
        'capabilities',
        'status',
        'is_active',
        'last_seen',
        'battery_level',
        'signal_strength',
        'configuration',
        'registration_data',
        'notes',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'configuration' => 'array',
        'registration_data' => 'array',
        'is_active' => 'boolean',
        'last_seen' => 'datetime',
    ];

    // Relationships
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeRecentlyActive($query, $minutes = 5)
    {
        return $query->where('last_seen', '>=', now()->subMinutes($minutes));
    }

    // Accessors
    public function getIsOnlineAttribute(): bool
    {
        return $this->status === 'online' &&
               $this->last_seen &&
               $this->last_seen->greaterThan(now()->subMinutes(5));
    }

    public function getBatteryStatusAttribute(): string
    {
        if (!$this->battery_level) {
            return 'unknown';
        }

        if ($this->battery_level > 75) {
            return 'good';
        } elseif ($this->battery_level > 50) {
            return 'fair';
        } elseif ($this->battery_level > 25) {
            return 'low';
        } else {
            return 'critical';
        }
    }

    public function getSignalStatusAttribute(): string
    {
        if (!$this->signal_strength) {
            return 'unknown';
        }

        // Assuming signal_strength is in dBm
        if ($this->signal_strength > -50) {
            return 'excellent';
        } elseif ($this->signal_strength > -70) {
            return 'good';
        } elseif ($this->signal_strength > -80) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    // Methods
    public function updateHeartbeat(array $data = []): void
    {
        $updateData = [
            'last_seen' => now(),
            'status' => 'online',
        ];

        if (isset($data['battery_level'])) {
            $updateData['battery_level'] = $data['battery_level'];
        }

        if (isset($data['signal_strength'])) {
            $updateData['signal_strength'] = $data['signal_strength'];
        }

        $this->update($updateData);
    }

    public function markOffline(): void
    {
        $this->update(['status' => 'offline']);
    }

    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? []);
    }

    public function getLastSeenHuman(): string
    {
        if (!$this->last_seen) {
            return 'Never';
        }

        return $this->last_seen->diffForHumans();
    }

    public static function getDeviceTypes(): array
    {
        return [
            'sensor' => 'Sensor Device',
            'controller' => 'Controller',
            'gateway' => 'Gateway',
            'actuator' => 'Actuator',
            'monitor' => 'Monitor',
            'analyzer' => 'Analyzer',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'online' => 'Online',
            'offline' => 'Offline',
            'error' => 'Error',
            'maintenance' => 'Maintenance',
        ];
    }
}
