<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Health Report - {{ $asset->name }}</title>
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
        .health-overview {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        .health-score {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .health-score.excellent { color: #28a745; }
        .health-score.good { color: #28a745; }
        .health-score.fair { color: #ffc107; }
        .health-score.poor { color: #fd7e14; }
        .health-score.critical { color: #dc3545; }
        .health-status {
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .health-trend {
            font-size: 18px;
            margin-top: 10px;
        }
        .trend-improving { color: #28a745; }
        .trend-stable { color: #007bff; }
        .trend-declining { color: #dc3545; }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .summary-card {
            text-align: center;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
        }
        .summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .summary-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 15px;
            border-bottom: 2px solid #eee;
            padding-bottom: 5px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-item {
            display: flex;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            min-width: 140px;
        }
        .info-value {
            color: #666;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-right: 10px;
        }
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        .badge-info { background-color: #d1ecf1; color: #0c5460; }
        .badge-secondary { background-color: #e2e3e5; color: #383d41; }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .data-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .anomaly-list {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .anomaly-item {
            margin-bottom: 10px;
            padding: 8px;
            background-color: white;
            border-radius: 3px;
            border-left: 4px solid #ffc107;
        }
        .anomaly-critical {
            border-left-color: #dc3545;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
        .asset-info {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <h1>VEO Asset Management System</h1>
            <h2>Real-time Health Report</h2>
            <p>{{ $asset->name }} - {{ $generated_at->format('F j, Y') }}</p>
        </div>
    </div>

    <div class="asset-info">
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
                <span class="info-label">Current Status:</span>
                <span class="info-value">
                    <span class="badge badge-{{ $asset->status == 'operational' ? 'success' : ($asset->status == 'maintenance' ? 'warning' : 'danger') }}">
                        {{ ucfirst($asset->status) }}
                    </span>
                </span>
            </div>
        </div>
    </div>

    <div class="health-overview">
        @php
            $healthStatusClass = $latest_performance && $latest_performance->health_status ? $latest_performance->health_status : 'unknown';
        @endphp
        <div class="health-score {{ $healthStatusClass }}">
            {{ $current_health_score ? number_format($current_health_score, 1) . '%' : 'N/A' }}
        </div>
        <div class="health-status {{ $healthStatusClass }}">
            {{ $latest_performance && $latest_performance->health_status ? ucfirst($latest_performance->health_status) : 'Unknown' }}
        </div>
        <div class="health-trend trend-{{ $health_trend }}">
            Trend: {{ ucfirst($health_trend) }}
            @if($health_trend == 'improving')
                ↗️
            @elseif($health_trend == 'declining')
                ↘️
            @else
                →
            @endif
        </div>
        <p style="margin-top: 15px; color: #666;">
            7-Day Average: {{ $avg_health_score ? number_format($avg_health_score, 1) . '%' : 'N/A' }}
        </p>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-value">{{ $performance_readings->count() }}</div>
            <div class="summary-label">Performance Readings (7 days)</div>
        </div>
        <div class="summary-card">
            <div class="summary-value">{{ $diagnostic_scans->count() }}</div>
            <div class="summary-label">Diagnostic Scans (7 days)</div>
        </div>
        <div class="summary-card">
            <div class="summary-value">{{ $total_anomalies }}</div>
            <div class="summary-label">Anomalies Detected (7 days)</div>
        </div>
        <div class="summary-card">
            <div class="summary-value">
                @if($latest_performance && $latest_performance->efficiency_percentage)
                    {{ number_format($latest_performance->efficiency_percentage, 1) }}%
                @else
                    N/A
                @endif
            </div>
            <div class="summary-label">Current Efficiency</div>
        </div>
    </div>

    @if($latest_performance)
    <div class="section">
        <div class="section-title">Latest Performance Data</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Reading Time:</span>
                <span class="info-value">{{ $latest_performance->created_at->format('Y-m-d H:i:s') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Temperature:</span>
                <span class="info-value">{{ $latest_performance->temperature ? $latest_performance->temperature . '°C' : 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Humidity:</span>
                <span class="info-value">{{ $latest_performance->humidity ? $latest_performance->humidity . '%' : 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Vibration Level:</span>
                <span class="info-value">{{ $latest_performance->vibration_level ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Power Output:</span>
                <span class="info-value">{{ $latest_performance->power_output ? $latest_performance->power_output . ' kW' : 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Efficiency:</span>
                <span class="info-value">{{ $latest_performance->efficiency_percentage ? $latest_performance->efficiency_percentage . '%' : 'N/A' }}</span>
            </div>
        </div>
    </div>
    @endif

    @if($latest_diagnostic)
    <div class="section">
        <div class="section-title">Latest Diagnostic Results</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Scan Time:</span>
                <span class="info-value">{{ $latest_diagnostic->created_at->format('Y-m-d H:i:s') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Anomaly Detected:</span>
                <span class="info-value">
                    <span class="badge badge-{{ $latest_diagnostic->anomaly_detected ? 'danger' : 'success' }}">
                        {{ $latest_diagnostic->anomaly_detected ? 'Yes' : 'No' }}
                    </span>
                </span>
            </div>
            @if($latest_diagnostic->anomaly_detected)
            <div class="info-item">
                <span class="info-label">Severity:</span>
                <span class="info-value">
                    <span class="badge badge-{{ $latest_diagnostic->severity_level == 'critical' ? 'danger' : 'warning' }}">
                        {{ ucfirst($latest_diagnostic->severity_level) }}
                    </span>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Description:</span>
                <span class="info-value">{{ $latest_diagnostic->anomaly_description }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif

    @if($recent_anomalies->count() > 0)
    <div class="section">
        <div class="section-title">Recent Anomalies (Last 7 Days)</div>
        <div class="anomaly-list">
            @foreach($recent_anomalies as $anomaly)
            <div class="anomaly-item {{ $anomaly->severity_level == 'critical' ? 'anomaly-critical' : '' }}">
                <strong>{{ $anomaly->created_at->format('M j, H:i') }}</strong> -
                <span class="badge badge-{{ $anomaly->severity_level == 'critical' ? 'danger' : 'warning' }}">
                    {{ ucfirst($anomaly->severity_level) }}
                </span>
                {{ $anomaly->anomaly_description ?? $anomaly->event_description }}
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($performance_readings->count() > 0)
    <div class="section">
        <div class="section-title">Recent Performance History</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Health Score</th>
                    <th>Health Status</th>
                    <th>Temperature</th>
                    <th>Efficiency</th>
                    <th>Power Output</th>
                </tr>
            </thead>
            <tbody>
                @foreach($performance_readings as $reading)
                <tr>
                    <td>{{ $reading->created_at->format('M j, H:i') }}</td>
                    <td>{{ $reading->health_score ? number_format($reading->health_score, 1) . '%' : '-' }}</td>
                    <td>
                        @if($reading->health_status)
                        <span class="badge badge-{{ $reading->health_status == 'excellent' || $reading->health_status == 'good' ? 'success' : ($reading->health_status == 'fair' ? 'warning' : 'danger') }}">
                            {{ ucfirst($reading->health_status) }}
                        </span>
                        @else
                        -
                        @endif
                    </td>
                    <td>{{ $reading->temperature ? $reading->temperature . '°C' : '-' }}</td>
                    <td>{{ $reading->efficiency_percentage ? $reading->efficiency_percentage . '%' : '-' }}</td>
                    <td>{{ $reading->power_output ? $reading->power_output . ' kW' : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="section">
        <div class="section-title">Recent Performance History</div>
        <div class="no-data">No performance data available for the last 7 days</div>
    </div>
    @endif

    @if($diagnostic_scans->count() > 0)
    <div class="section">
        <div class="section-title">Recent Diagnostic Scans</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Anomaly</th>
                    <th>Severity</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach($diagnostic_scans as $scan)
                <tr>
                    <td>{{ $scan->created_at->format('M j, H:i') }}</td>
                    <td>
                        <span class="badge badge-{{ $scan->anomaly_detected ? 'danger' : 'success' }}">
                            {{ $scan->anomaly_detected ? 'Yes' : 'No' }}
                        </span>
                    </td>
                    <td>
                        @if($scan->anomaly_detected)
                        <span class="badge badge-{{ $scan->severity_level == 'critical' ? 'danger' : 'warning' }}">
                            {{ ucfirst($scan->severity_level) }}
                        </span>
                        @else
                        -
                        @endif
                    </td>
                    <td>{{ $scan->anomaly_description ?: 'Normal operation' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="section">
        <div class="section-title">Recent Diagnostic Scans</div>
        <div class="no-data">No diagnostic scans available for the last 7 days</div>
    </div>
    @endif

    <div class="section">
        <div class="section-title">Health Assessment Summary</div>
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px;">
            @php
                $healthAdvice = '';
                if ($current_health_score >= 90) {
                    $healthAdvice = 'Asset is performing excellently. Continue regular monitoring and preventive maintenance.';
                } elseif ($current_health_score >= 75) {
                    $healthAdvice = 'Asset is in good condition. Monitor performance trends and schedule routine maintenance.';
                } elseif ($current_health_score >= 60) {
                    $healthAdvice = 'Asset performance is fair. Consider increased monitoring and check for maintenance needs.';
                } elseif ($current_health_score >= 40) {
                    $healthAdvice = 'Asset performance is poor. Immediate inspection and maintenance recommended.';
                } else {
                    $healthAdvice = 'Critical: Asset requires immediate attention. Schedule emergency maintenance.';
                }
            @endphp
            <p><strong>Current Assessment:</strong> {{ $healthAdvice }}</p>

            @if($total_anomalies > 0)
            <p><strong>Anomaly Alert:</strong> {{ $total_anomalies }} anomalies detected in the last 7 days. Review diagnostic reports and consider preventive action.</p>
            @endif

            @if($health_trend == 'declining')
            <p><strong>Trend Warning:</strong> Health scores are declining. Investigate potential causes and increase monitoring frequency.</p>
            @elseif($health_trend == 'improving')
            <p><strong>Positive Trend:</strong> Health scores are improving. Continue current maintenance practices.</p>
            @endif
        </div>
    </div>

    <div class="footer">
        <p>Generated on {{ $generated_at->format('Y-m-d H:i:s') }}</p>
        <p>&copy; {{ date('Y') }} VEO Asset Management System. All rights reserved.</p>
        <p><em>This report is based on data collected over the last 7 days</em></p>
    </div>
</body>
</html>