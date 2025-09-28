<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Asset History Report - {{ $asset->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-info {
            text-align: center;
            margin-bottom: 20px;
        }
        .asset-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .event-details {
            margin-bottom: 20px;
        }
        .data-section {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        .info-item {
            display: flex;
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            min-width: 120px;
        }
        .info-value {
            color: #666;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        .badge-info { background-color: #d1ecf1; color: #0c5460; }
        .badge-secondary { background-color: #e2e3e5; color: #383d41; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .json-data {
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 11px;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <h1>VEO Asset Management System</h1>
            <p>Asset History Report</p>
        </div>
    </div>

    <div class="asset-info">
        <div class="section-title">Asset Information</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Asset Name:</span>
                <span class="info-value">{{ $asset->name }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Asset Type:</span>
                <span class="info-value">{{ $asset->asset_type }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Site:</span>
                <span class="info-value">{{ $site->name }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Location:</span>
                <span class="info-value">{{ $site->location }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Serial Number:</span>
                <span class="info-value">{{ $asset->serial_number ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Current Status:</span>
                <span class="info-value">
                    <span class="badge badge-{{ $asset->status == 'operational' ? 'success' : ($asset->status == 'maintenance' ? 'warning' : 'danger') }}">
                        {{ ucfirst($asset->status) }}
                    </span>
                </span>
            </div>
        </div>
    </div>

    <div class="event-details">
        <div class="section-title">Event Details</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Event Type:</span>
                <span class="info-value">
                    <span class="badge badge-info">{{ str_replace('_', ' ', ucfirst($history->event_type)) }}</span>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Recorded At:</span>
                <span class="info-value">{{ $history->created_at->format('Y-m-d H:i:s') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Data Source:</span>
                <span class="info-value">
                    <span class="badge badge-secondary">{{ ucfirst($history->data_source) }}</span>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Automated:</span>
                <span class="info-value">{{ $history->is_automated ? 'Yes' : 'No' }}</span>
            </div>
        </div>

        <div class="info-item">
            <span class="info-label">Description:</span>
            <span class="info-value">{{ $history->event_description }}</span>
        </div>

        @if($history->recorded_by)
        <div class="info-item">
            <span class="info-label">Recorded By:</span>
            <span class="info-value">{{ $history->recordedBy->name ?? 'System' }}</span>
        </div>
        @endif
    </div>

    @if($history->previous_status || $history->current_status)
    <div class="data-section">
        <div class="section-title">Status Information</div>
        @if($history->previous_status)
        <div class="info-item">
            <span class="info-label">Previous Status:</span>
            <span class="info-value">
                <span class="badge badge-secondary">{{ ucfirst($history->previous_status) }}</span>
            </span>
        </div>
        @endif
        <div class="info-item">
            <span class="info-label">Current Status:</span>
            <span class="info-value">
                <span class="badge badge-{{ $history->current_status == 'operational' ? 'success' : ($history->current_status == 'maintenance' ? 'warning' : 'danger') }}">
                    {{ ucfirst($history->current_status) }}
                </span>
            </span>
        </div>
    </div>
    @endif

    @if($history->health_score || $history->health_status)
    <div class="data-section">
        <div class="section-title">Health Information</div>
        @if($history->health_score)
        <div class="info-item">
            <span class="info-label">Health Score:</span>
            <span class="info-value">{{ number_format($history->health_score, 1) }}%</span>
        </div>
        @endif
        @if($history->health_status)
        <div class="info-item">
            <span class="info-label">Health Status:</span>
            <span class="info-value">
                <span class="badge badge-{{ $history->health_status == 'excellent' || $history->health_status == 'good' ? 'success' : ($history->health_status == 'fair' ? 'warning' : 'danger') }}">
                    {{ ucfirst($history->health_status) }}
                </span>
            </span>
        </div>
        @endif
    </div>
    @endif

    @if($history->temperature || $history->humidity || $history->vibration_level || $history->power_output || $history->efficiency_percentage)
    <div class="data-section">
        <div class="section-title">Environmental & Performance Data</div>
        <div class="info-grid">
            @if($history->temperature)
            <div class="info-item">
                <span class="info-label">Temperature:</span>
                <span class="info-value">{{ $history->temperature }}°C</span>
            </div>
            @endif
            @if($history->humidity)
            <div class="info-item">
                <span class="info-label">Humidity:</span>
                <span class="info-value">{{ $history->humidity }}%</span>
            </div>
            @endif
            @if($history->vibration_level)
            <div class="info-item">
                <span class="info-label">Vibration Level:</span>
                <span class="info-value">{{ $history->vibration_level }}</span>
            </div>
            @endif
            @if($history->power_output)
            <div class="info-item">
                <span class="info-label">Power Output:</span>
                <span class="info-value">{{ $history->power_output }} kW</span>
            </div>
            @endif
            @if($history->efficiency_percentage)
            <div class="info-item">
                <span class="info-label">Efficiency:</span>
                <span class="info-value">{{ $history->efficiency_percentage }}%</span>
            </div>
            @endif
        </div>
    </div>
    @endif

    @if($history->anomaly_detected)
    <div class="data-section">
        <div class="section-title">Anomaly Information</div>
        <div class="info-item">
            <span class="info-label">Anomaly Detected:</span>
            <span class="info-value">
                <span class="badge badge-danger">Yes</span>
            </span>
        </div>
        @if($history->anomaly_description)
        <div class="info-item">
            <span class="info-label">Description:</span>
            <span class="info-value">{{ $history->anomaly_description }}</span>
        </div>
        @endif
        <div class="info-item">
            <span class="info-label">Severity Level:</span>
            <span class="info-value">
                <span class="badge badge-{{ $history->severity_level == 'low' ? 'info' : ($history->severity_level == 'medium' ? 'warning' : 'danger') }}">
                    {{ ucfirst($history->severity_level) }}
                </span>
            </span>
        </div>
    </div>
    @endif

    @if($history->shift_type || $history->shift_start || $history->shift_end)
    <div class="data-section">
        <div class="section-title">Shift Information</div>
        @if($history->shift_type)
        <div class="info-item">
            <span class="info-label">Shift Type:</span>
            <span class="info-value">
                <span class="badge badge-info">{{ ucfirst($history->shift_type) }}</span>
            </span>
        </div>
        @endif
        @if($history->shift_start)
        <div class="info-item">
            <span class="info-label">Shift Start:</span>
            <span class="info-value">{{ $history->shift_start->format('Y-m-d H:i:s') }}</span>
        </div>
        @endif
        @if($history->shift_end)
        <div class="info-item">
            <span class="info-label">Shift End:</span>
            <span class="info-value">{{ $history->shift_end->format('Y-m-d H:i:s') }}</span>
        </div>
        @endif
    </div>
    @endif

    @if($history->performance_data)
    <div class="data-section">
        <div class="section-title">Performance Data</div>
        <div class="json-data">{{ json_encode($history->performance_data, JSON_PRETTY_PRINT) }}</div>
    </div>
    @endif

    @if($history->diagnostic_data)
    <div class="data-section">
        <div class="section-title">Diagnostic Data</div>
        <div class="json-data">{{ json_encode($history->diagnostic_data, JSON_PRETTY_PRINT) }}</div>
    </div>
    @endif

    @if($history->notes)
    <div class="data-section">
        <div class="section-title">Notes</div>
        <p>{{ $history->notes }}</p>
    </div>
    @endif

    <div class="footer">
        <p>Generated on {{ $generated_at->format('Y-m-d H:i:s') }}</p>
        <p>&copy; {{ date('Y') }} VEO Asset Management System. All rights reserved.</p>
    </div>
</body>
</html>