<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class AssetHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'recorded_by',
        'event_type',
        'event_description',
        'previous_status',
        'current_status',
        'performance_data',
        'diagnostic_data',
        'health_score',
        'health_status',
        'temperature',
        'humidity',
        'vibration_level',
        'power_output',
        'efficiency_percentage',
        'shift_type',
        'shift_start',
        'shift_end',
        'anomaly_detected',
        'anomaly_description',
        'severity_level',
        'data_source',
        'is_automated',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'performance_data' => 'array',
        'diagnostic_data' => 'array',
        'metadata' => 'array',
        'health_score' => 'decimal:2',
        'temperature' => 'decimal:2',
        'humidity' => 'decimal:2',
        'vibration_level' => 'decimal:3',
        'power_output' => 'decimal:2',
        'efficiency_percentage' => 'decimal:2',
        'anomaly_detected' => 'boolean',
        'is_automated' => 'boolean',
        'shift_start' => 'datetime',
        'shift_end' => 'datetime',
    ];

    // Relationships
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // Scopes for queries
    public function scopeForAsset($query, $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    public function scopeByEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeByShift($query, $shiftType = null, $date = null)
    {
        $query = $query->whereNotNull('shift_type');

        if ($shiftType) {
            $query = $query->where('shift_type', $shiftType);
        }

        if ($date) {
            $date = Carbon::parse($date);
            $query = $query->whereDate('created_at', $date);
        }

        return $query;
    }

    public function scopeWithAnomalies($query)
    {
        return $query->where('anomaly_detected', true);
    }

    public function scopeCriticalEvents($query)
    {
        return $query->where('severity_level', 'critical');
    }

    public function scopeByHealthStatus($query, $status)
    {
        return $query->where('health_status', $status);
    }

    public function scopeRecentEvents($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // Health calculation methods
    public static function calculateHealthScore($performanceData, $diagnosticData, $environmentalData = [])
    {
        $score = 100;

        // Performance degradation factors
        if (isset($performanceData['efficiency'])) {
            $efficiency = $performanceData['efficiency'];
            if ($efficiency < 90) $score -= (90 - $efficiency) * 2;
        }

        // Environmental factors
        if (isset($environmentalData['temperature'])) {
            $temp = $environmentalData['temperature'];
            if ($temp > 60 || $temp < -10) $score -= 15;
            elseif ($temp > 45 || $temp < 0) $score -= 5;
        }

        if (isset($environmentalData['vibration_level'])) {
            $vibration = $environmentalData['vibration_level'];
            if ($vibration > 10) $score -= 20;
            elseif ($vibration > 5) $score -= 10;
        }

        // Diagnostic issues
        if (isset($diagnosticData['error_count'])) {
            $errorCount = $diagnosticData['error_count'];
            $score -= min($errorCount * 5, 30);
        }

        return max(0, min(100, $score));
    }

    public static function getHealthStatusFromScore($score)
    {
        if ($score >= 90) return 'excellent';
        if ($score >= 75) return 'good';
        if ($score >= 60) return 'fair';
        if ($score >= 40) return 'poor';
        return 'critical';
    }

    // Automated data recording methods
    public static function recordStatusChange($asset, $previousStatus, $newStatus, $recordedBy = null)
    {
        return static::create([
            'asset_id' => $asset->id,
            'recorded_by' => is_numeric($recordedBy) ? $recordedBy : null,
            'event_type' => 'status_change',
            'event_description' => "Status changed from {$previousStatus} to {$newStatus}",
            'previous_status' => $previousStatus,
            'current_status' => $newStatus,
            'data_source' => 'system',
            'is_automated' => true,
        ]);
    }

    public static function recordPerformanceReading($asset, $performanceData, $environmentalData = [])
    {
        $healthScore = static::calculateHealthScore($performanceData, [], $environmentalData);
        $healthStatus = static::getHealthStatusFromScore($healthScore);

        return static::create([
            'asset_id' => $asset->id,
            'event_type' => 'performance_reading',
            'event_description' => 'Automated performance data collection',
            'current_status' => $asset->status,
            'performance_data' => $performanceData,
            'health_score' => $healthScore,
            'health_status' => $healthStatus,
            'temperature' => $environmentalData['temperature'] ?? null,
            'humidity' => $environmentalData['humidity'] ?? null,
            'vibration_level' => $environmentalData['vibration_level'] ?? null,
            'power_output' => $performanceData['power_output'] ?? null,
            'efficiency_percentage' => $performanceData['efficiency'] ?? null,
            'data_source' => 'sensor',
            'is_automated' => true,
        ]);
    }

    public static function recordDiagnosticScan($asset, $diagnosticData, $recordedBy = null)
    {
        $anomalyDetected = isset($diagnosticData['anomalies']) && count($diagnosticData['anomalies']) > 0;
        $severity = $anomalyDetected ? ($diagnosticData['severity'] ?? 'medium') : 'low';

        return static::create([
            'asset_id' => $asset->id,
            'recorded_by' => $recordedBy,
            'event_type' => 'diagnostic_scan',
            'event_description' => 'Diagnostic scan completed',
            'current_status' => $asset->status,
            'diagnostic_data' => $diagnosticData,
            'anomaly_detected' => $anomalyDetected,
            'anomaly_description' => $anomalyDetected ? implode(', ', $diagnosticData['anomalies']) : null,
            'severity_level' => $severity,
            'data_source' => $recordedBy ? 'manual' : 'system',
            'is_automated' => !$recordedBy,
        ]);
    }

    public static function recordShiftReport($asset, $shiftType, $shiftStart, $shiftEnd, $recordedBy, $notes = null)
    {
        // Calculate average health score for this shift
        $shiftData = static::where('asset_id', $asset->id)
            ->whereBetween('created_at', [$shiftStart, $shiftEnd])
            ->whereNotNull('health_score')
            ->get();

        $avgHealthScore = $shiftData->avg('health_score') ?? 0;
        $healthStatus = static::getHealthStatusFromScore($avgHealthScore);

        return static::create([
            'asset_id' => $asset->id,
            'recorded_by' => $recordedBy,
            'event_type' => 'shift_report',
            'event_description' => "End of {$shiftType} shift report",
            'current_status' => $asset->status,
            'health_score' => $avgHealthScore,
            'health_status' => $healthStatus,
            'shift_type' => $shiftType,
            'shift_start' => $shiftStart,
            'shift_end' => $shiftEnd,
            'data_source' => 'manual',
            'is_automated' => false,
            'notes' => $notes,
        ]);
    }
}
