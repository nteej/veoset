<?php

namespace App\Http\Controllers;

use App\Models\AssetHistory;
use App\Models\Asset;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class AssetHistoryController extends Controller
{
    public function downloadPDF(AssetHistory $assetHistory)
    {
        // Check permissions
        if (!auth()->user()->can('download_pdf_reports')) {
            abort(403, 'Unauthorized to download PDF reports');
        }

        // Check if user can view this specific record
        if (!auth()->user()->can('view_asset_history')) {
            if (!auth()->user()->can('view_own_asset_history')) {
                abort(403, 'Unauthorized to view asset history');
            }

            // Check if this is user's own record or their site's asset
            $user = auth()->user();
            $canView = $assetHistory->recorded_by === $user->id ||
                      ($user->site_id && $assetHistory->asset->site_id === $user->site_id);

            if (!$canView) {
                abort(403, 'Unauthorized to view this asset history record');
            }
        }

        $asset = $assetHistory->asset;

        $data = [
            'history' => $assetHistory,
            'asset' => $asset,
            'site' => $asset->site,
            'generated_at' => now(),
        ];

        $pdf = PDF::loadView('pdfs.asset-history-single', $data);

        return $pdf->download("asset-history-{$asset->name}-{$assetHistory->created_at->format('Y-m-d-H-i-s')}.pdf");
    }

    public function generateAssetReport(Asset $asset, Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30));
        $endDate = $request->get('end_date', now());

        if (is_string($startDate)) {
            $startDate = Carbon::parse($startDate);
        }
        if (is_string($endDate)) {
            $endDate = Carbon::parse($endDate);
        }

        $histories = $asset->history()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $performanceReadings = $histories->where('event_type', 'performance_reading');
        $statusChanges = $histories->where('event_type', 'status_change');
        $anomalies = $histories->where('anomaly_detected', true);

        $healthScores = $performanceReadings->pluck('health_score')->filter();
        $avgHealthScore = $healthScores->avg();
        $minHealthScore = $healthScores->min();
        $maxHealthScore = $healthScores->max();

        $data = [
            'asset' => $asset,
            'site' => $asset->site,
            'histories' => $histories,
            'performance_readings' => $performanceReadings,
            'status_changes' => $statusChanges,
            'anomalies' => $anomalies,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'avg_health_score' => $avgHealthScore,
            'min_health_score' => $minHealthScore,
            'max_health_score' => $maxHealthScore,
            'total_anomalies' => $anomalies->count(),
            'generated_at' => now(),
        ];

        $pdf = PDF::loadView('pdfs.asset-comprehensive-report', $data);

        return $pdf->download("asset-report-{$asset->name}-{$startDate->format('Y-m-d')}-to-{$endDate->format('Y-m-d')}.pdf");
    }

    public function generateShiftReport(Asset $asset, Request $request)
    {
        $shiftType = $request->get('shift_type');
        $date = $request->get('date', now()->format('Y-m-d'));

        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        $histories = $asset->history()
            ->whereDate('created_at', $date)
            ->when($shiftType, function ($query) use ($shiftType) {
                return $query->where('shift_type', $shiftType);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        $shiftReport = $histories->where('event_type', 'shift_report')->first();
        $performanceReadings = $histories->where('event_type', 'performance_reading');
        $statusChanges = $histories->where('event_type', 'status_change');
        $anomalies = $histories->where('anomaly_detected', true);

        $healthScores = $performanceReadings->pluck('health_score')->filter();
        $avgHealthScore = $healthScores->avg() ?? 0;

        $data = [
            'asset' => $asset,
            'site' => $asset->site,
            'shift_report' => $shiftReport,
            'histories' => $histories,
            'performance_readings' => $performanceReadings,
            'status_changes' => $statusChanges,
            'anomalies' => $anomalies,
            'shift_type' => $shiftType,
            'date' => $date,
            'avg_health_score' => $avgHealthScore,
            'total_anomalies' => $anomalies->count(),
            'generated_at' => now(),
        ];

        $pdf = PDF::loadView('pdfs.asset-shift-report', $data);

        return $pdf->download("shift-report-{$asset->name}-{$shiftType}-{$date->format('Y-m-d')}.pdf");
    }

    public function generateHealthReport(Asset $asset)
    {
        $recentHistories = $asset->history()
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->get();

        $performanceReadings = $recentHistories->where('event_type', 'performance_reading');
        $diagnosticScans = $recentHistories->where('event_type', 'diagnostic_scan');
        $anomalies = $recentHistories->where('anomaly_detected', true);

        $healthScores = $performanceReadings->pluck('health_score')->filter();
        $currentHealthScore = $healthScores->first() ?? 0;
        $avgHealthScore = $healthScores->avg() ?? 0;
        $healthTrend = $this->calculateHealthTrend($healthScores);

        $latestPerformance = $performanceReadings->first();
        $latestDiagnostic = $diagnosticScans->first();

        $data = [
            'asset' => $asset,
            'site' => $asset->site,
            'current_health_score' => $currentHealthScore,
            'avg_health_score' => $avgHealthScore,
            'health_trend' => $healthTrend,
            'latest_performance' => $latestPerformance,
            'latest_diagnostic' => $latestDiagnostic,
            'recent_anomalies' => $anomalies->take(5),
            'total_anomalies' => $anomalies->count(),
            'performance_readings' => $performanceReadings->take(10),
            'diagnostic_scans' => $diagnosticScans->take(5),
            'generated_at' => now(),
        ];

        $pdf = PDF::loadView('pdfs.asset-health-report', $data);

        return $pdf->download("health-report-{$asset->name}-{now()->format('Y-m-d')}.pdf");
    }

    private function calculateHealthTrend($healthScores)
    {
        if ($healthScores->count() < 2) {
            return 'stable';
        }

        $recent = $healthScores->take(3)->avg();
        $older = $healthScores->skip(3)->take(3)->avg();

        if ($recent > $older + 5) {
            return 'improving';
        } elseif ($recent < $older - 5) {
            return 'declining';
        } else {
            return 'stable';
        }
    }
}