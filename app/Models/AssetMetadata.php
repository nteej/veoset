<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMetadata extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'specifications',
        'maintenance_schedule',
        'performance_metrics',
        'safety_requirements',
        'environmental_data',
        'power_rating',
        'voltage_level',
        'expected_lifespan_years',
        'efficiency_rating',
        'operational_notes',
    ];

    protected $casts = [
        'specifications' => 'array',
        'maintenance_schedule' => 'array',
        'performance_metrics' => 'array',
        'safety_requirements' => 'array',
        'environmental_data' => 'array',
        'power_rating' => 'decimal:2',
        'efficiency_rating' => 'decimal:2',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function getMaintenanceInterval(): ?int
    {
        return $this->maintenance_schedule['interval_days'] ?? null;
    }

    public function getMaxOperatingTemperature(): ?float
    {
        return $this->environmental_data['max_temperature'] ?? null;
    }

    public function getPerformanceThreshold(string $metric): ?float
    {
        return $this->performance_metrics[$metric] ?? null;
    }

    public function isHighVoltage(): bool
    {
        if (!$this->voltage_level) {
            return false;
        }

        // Extract numeric value from voltage string
        preg_match('/(\d+)/', $this->voltage_level, $matches);
        $voltage = isset($matches[1]) ? (int) $matches[1] : 0;

        return $voltage > 1000; // High voltage is typically >1kV
    }
}
