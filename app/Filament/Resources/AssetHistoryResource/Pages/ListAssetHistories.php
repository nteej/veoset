<?php

namespace App\Filament\Resources\AssetHistoryResource\Pages;

use App\Filament\Resources\AssetHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\Asset;
use App\Models\AssetHistory;

class ListAssetHistories extends ListRecords
{
    protected static string $resource = AssetHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateShiftReport')
                ->label('Generate Shift Report')
                ->icon('heroicon-o-document-text')
                ->form([
                    \Filament\Forms\Components\Select::make('asset_id')
                        ->label('Asset')
                        ->options(Asset::all()->pluck('name', 'id'))
                        ->required(),
                    \Filament\Forms\Components\Select::make('shift_type')
                        ->label('Shift Type')
                        ->options([
                            'day' => 'Day Shift',
                            'night' => 'Night Shift',
                            'emergency' => 'Emergency Shift',
                        ])
                        ->required(),
                    \Filament\Forms\Components\DateTimePicker::make('shift_start')
                        ->label('Shift Start')
                        ->required(),
                    \Filament\Forms\Components\DateTimePicker::make('shift_end')
                        ->label('Shift End')
                        ->required(),
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Shift Notes')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $asset = Asset::find($data['asset_id']);

                    AssetHistory::recordShiftReport(
                        $asset,
                        $data['shift_type'],
                        $data['shift_start'],
                        $data['shift_end'],
                        Auth::id(),
                        $data['notes'] ?? null
                    );

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Shift report generated successfully')
                        ->send();
                }),

            Action::make('simulateSensorData')
                ->label('Simulate Sensor Data')
                ->icon('heroicon-o-cpu-chip')
                ->form([
                    \Filament\Forms\Components\Select::make('asset_id')
                        ->label('Asset')
                        ->options(Asset::all()->pluck('name', 'id'))
                        ->required(),
                    \Filament\Forms\Components\CheckboxList::make('data_types')
                        ->label('Data Types to Simulate')
                        ->options([
                            'performance' => 'Performance Reading',
                            'diagnostic' => 'Diagnostic Scan',
                        ])
                        ->default(['performance', 'diagnostic'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $asset = Asset::find($data['asset_id']);

                    if (in_array('performance', $data['data_types'])) {
                        $performanceData = [
                            'efficiency' => rand(75, 98) + (rand(0, 99) / 100),
                            'power_output' => rand(800, 1200) + (rand(0, 99) / 100),
                            'runtime_hours' => rand(8, 16) + (rand(0, 59) / 60),
                        ];

                        $environmentalData = [
                            'temperature' => rand(15, 45) + (rand(0, 99) / 100),
                            'humidity' => rand(30, 80) + (rand(0, 99) / 100),
                            'vibration_level' => rand(1, 8) + (rand(0, 999) / 1000),
                        ];

                        AssetHistory::recordPerformanceReading($asset, $performanceData, $environmentalData);
                    }

                    if (in_array('diagnostic', $data['data_types'])) {
                        $hasAnomalies = rand(1, 10) <= 2;

                        $diagnosticData = [
                            'error_count' => $hasAnomalies ? rand(1, 5) : 0,
                            'system_integrity' => rand(85, 100),
                            'component_status' => [
                                'motor' => rand(90, 100),
                                'sensors' => rand(85, 100),
                                'controller' => rand(88, 100),
                            ],
                        ];

                        if ($hasAnomalies) {
                            $diagnosticData['anomalies'] = [
                                'High vibration detected',
                                'Temperature spike observed',
                            ];
                            $diagnosticData['severity'] = rand(1, 3) == 3 ? 'critical' : 'medium';
                        }

                        AssetHistory::recordDiagnosticScan($asset, $diagnosticData);
                    }

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Sensor data simulated successfully')
                        ->send();
                }),
        ];
    }
}