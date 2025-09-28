<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shift Report - {{ $asset->name }} - {{ ucfirst($shift_type ?? 'All') }} Shift</title>
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
        .shift-summary {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
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
        .event-list {
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .event-item {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .event-item:last-child {
            border-bottom: none;
        }
        .event-info {
            flex-grow: 1;
        }
        .event-time {
            font-size: 12px;
            color: #666;
            min-width: 120px;
            text-align: right;
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
        .performance-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .performance-table th,
        .performance-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .performance-table th {
            background-color: #f8f9fa;
            font-weight: bold;
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
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <h1>VEO Asset Management System</h1>
            <h2>{{ ucfirst($shift_type ?? 'Daily') }} Shift Report</h2>
            <p>{{ $asset->name }} - {{ $date->format('F j, Y') }}</p>
        </div>
    </div>

    <div class="shift-summary">
        <h3>Shift Summary</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Asset:</span>
                <span class="info-value">{{ $asset->name }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Site:</span>
                <span class="info-value">{{ $site->name }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Shift Date:</span>
                <span class="info-value">{{ $date->format('Y-m-d') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Shift Type:</span>
                <span class="info-value">{{ ucfirst($shift_type ?? 'All') }}</span>
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-value">{{ number_format($avg_health_score, 1) }}%</div>
                <div class="summary-label">Average Health Score</div>
            </div>
            <div class="summary-card">
                <div class="summary-value">{{ $performance_readings->count() }}</div>
                <div class="summary-label">Performance Readings</div>
            </div>
            <div class="summary-card">
                <div class="summary-value">{{ $status_changes->count() }}</div>
                <div class="summary-label">Status Changes</div>
            </div>
            <div class="summary-card">
                <div class="summary-value">{{ $total_anomalies }}</div>
                <div class="summary-label">Anomalies Detected</div>
            </div>
        </div>

        @if($shift_report)
        <div class="info-grid">
            @if($shift_report->shift_start)
            <div class="info-item">
                <span class="info-label">Shift Start:</span>
                <span class="info-value">{{ $shift_report->shift_start->format('H:i:s') }}</span>
            </div>
            @endif
            @if($shift_report->shift_end)
            <div class="info-item">
                <span class="info-label">Shift End:</span>
                <span class="info-value">{{ $shift_report->shift_end->format('H:i:s') }}</span>
            </div>
            @endif
            @if($shift_report->recordedBy)
            <div class="info-item">
                <span class="info-label">Technician:</span>
                <span class="info-value">{{ $shift_report->recordedBy->name }}</span>
            </div>
            @endif
        </div>

        @if($shift_report->notes)
        <div class="info-item">
            <span class="info-label">Shift Notes:</span>
            <span class="info-value">{{ $shift_report->notes }}</span>
        </div>
        @endif
        @endif
    </div>

    @if($status_changes->count() > 0)
    <div class="section">
        <div class="section-title">Status Changes</div>
        <div class="event-list">
            @foreach($status_changes as $change)
            <div class="event-item">
                <div class="event-info">
                    <span class="badge badge-info">Status Change</span>
                    From {{ ucfirst($change->previous_status) }} to {{ ucfirst($change->current_status) }}
                    @if($change->recordedBy)
                        <br><small>By: {{ $change->recordedBy->name }}</small>
                    @endif
                </div>
                <div class="event-time">{{ $change->created_at->format('H:i:s') }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($anomalies->count() > 0)
    <div class="section">
        <div class="section-title">Anomalies & Alerts</div>
        <div class="event-list">
            @foreach($anomalies as $anomaly)
            <div class="event-item">
                <div class="event-info">
                    <span class="badge badge-{{ $anomaly->severity_level == 'critical' ? 'danger' : 'warning' }}">
                        {{ ucfirst($anomaly->severity_level) }}
                    </span>
                    {{ $anomaly->anomaly_description ?? $anomaly->event_description }}
                </div>
                <div class="event-time">{{ $anomaly->created_at->format('H:i:s') }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($performance_readings->count() > 0)
    <div class="section">
        <div class="section-title">Performance Readings</div>
        <table class="performance-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Health Score</th>
                    <th>Temperature</th>
                    <th>Efficiency</th>
                    <th>Power Output</th>
                    <th>Vibration</th>
                </tr>
            </thead>
            <tbody>
                @foreach($performance_readings->take(10) as $reading)
                <tr>
                    <td>{{ $reading->created_at->format('H:i:s') }}</td>
                    <td>{{ $reading->health_score ? number_format($reading->health_score, 1) . '%' : '-' }}</td>
                    <td>{{ $reading->temperature ? $reading->temperature . '°C' : '-' }}</td>
                    <td>{{ $reading->efficiency_percentage ? $reading->efficiency_percentage . '%' : '-' }}</td>
                    <td>{{ $reading->power_output ? $reading->power_output . ' kW' : '-' }}</td>
                    <td>{{ $reading->vibration_level ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($performance_readings->count() > 10)
        <p><em>Showing latest 10 of {{ $performance_readings->count() }} performance readings</em></p>
        @endif
    </div>
    @else
    <div class="section">
        <div class="section-title">Performance Readings</div>
        <div class="no-data">No performance readings recorded for this shift</div>
    </div>
    @endif

    <div class="section">
        <div class="section-title">All Events Timeline</div>
        @if($histories->count() > 0)
        <div class="event-list">
            @foreach($histories->take(20) as $event)
            <div class="event-item">
                <div class="event-info">
                    <span class="badge badge-{{ $event->event_type == 'status_change' ? 'info' : ($event->event_type == 'performance_reading' ? 'secondary' : 'warning') }}">
                        {{ str_replace('_', ' ', ucfirst($event->event_type)) }}
                    </span>
                    {{ $event->event_description }}
                    @if($event->health_score)
                        <br><small>Health Score: {{ number_format($event->health_score, 1) }}%</small>
                    @endif
                </div>
                <div class="event-time">{{ $event->created_at->format('H:i:s') }}</div>
            </div>
            @endforeach
        </div>
        @if($histories->count() > 20)
        <p><em>Showing latest 20 of {{ $histories->count() }} events</em></p>
        @endif
        @else
        <div class="no-data">No events recorded for this shift</div>
        @endif
    </div>

    <div class="footer">
        <p>Generated on {{ $generated_at->format('Y-m-d H:i:s') }}</p>
        <p>&copy; {{ date('Y') }} VEO Asset Management System. All rights reserved.</p>
    </div>
</body>
</html>