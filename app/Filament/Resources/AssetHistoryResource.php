<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssetHistoryResource\Pages;
use App\Models\AssetHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class AssetHistoryResource extends Resource
{
    protected static ?string $model = AssetHistory::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Asset History';
    protected static ?string $navigationGroup = 'Asset Management';
    protected static ?int $navigationSort = 3;

    // Policy-based authorization
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', AssetHistory::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', AssetHistory::class) ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view', $record) ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('update', $record) ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete', $record) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasRole('veo_admin') ?? false;
    }

    // Apply policy-based filtering for query
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('asset');
        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // VEO Admin can see all asset history
        if ($user->hasRole('veo_admin')) {
            return $query;
        }

        // Other roles can only see history for assets in their assigned sites
        if ($user->hasAnyRole(['site_manager', 'maintenance_staff', 'customer'])) {
            $siteIds = $user->sites()->pluck('sites.id');
            return $query->whereHas('asset', function ($assetQuery) use ($siteIds) {
                $assetQuery->whereIn('site_id', $siteIds);
            });
        }

        return $query->whereRaw('1 = 0');
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('asset.name')
                    ->label('Asset')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('asset.site.name')
                    ->label('Site')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('event_type')
                    ->label('Event Type')
                    ->colors([
                        'success' => 'status_change',
                        'warning' => 'maintenance_start',
                        'success' => 'maintenance_complete',
                        'info' => 'performance_reading',
                        'danger' => 'alert_triggered',
                        'secondary' => 'diagnostic_scan',
                        'primary' => 'shift_report',
                    ]),
                Tables\Columns\BadgeColumn::make('current_status')
                    ->label('Status')
                    ->colors([
                        'success' => 'operational',
                        'warning' => 'maintenance',
                        'danger' => 'offline',
                        'danger' => 'emergency',
                    ]),
                Tables\Columns\BadgeColumn::make('health_status')
                    ->label('Health')
                    ->colors([
                        'success' => 'excellent',
                        'success' => 'good',
                        'warning' => 'fair',
                        'warning' => 'poor',
                        'danger' => 'critical',
                    ]),
                Tables\Columns\TextColumn::make('health_score')
                    ->label('Score')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\IconColumn::make('anomaly_detected')
                    ->label('Anomaly')
                    ->boolean(),
                Tables\Columns\BadgeColumn::make('severity_level')
                    ->label('Severity')
                    ->colors([
                        'secondary' => 'low',
                        'warning' => 'medium',
                        'warning' => 'high',
                        'danger' => 'critical',
                    ]),
                Tables\Columns\TextColumn::make('data_source')
                    ->label('Source')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recorded At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('asset_id')
                    ->label('Asset')
                    ->relationship('asset', 'name'),
                Tables\Filters\SelectFilter::make('event_type')
                    ->options([
                        'status_change' => 'Status Change',
                        'maintenance_start' => 'Maintenance Start',
                        'maintenance_complete' => 'Maintenance Complete',
                        'performance_reading' => 'Performance Reading',
                        'alert_triggered' => 'Alert Triggered',
                        'diagnostic_scan' => 'Diagnostic Scan',
                        'shift_report' => 'Shift Report',
                    ]),
                Tables\Filters\SelectFilter::make('health_status')
                    ->options([
                        'excellent' => 'Excellent',
                        'good' => 'Good',
                        'fair' => 'Fair',
                        'poor' => 'Poor',
                        'critical' => 'Critical',
                    ]),
                Tables\Filters\Filter::make('anomaly_detected')
                    ->label('With Anomalies')
                    ->query(fn (Builder $query): Builder => $query->where('anomaly_detected', true)),
                Tables\Filters\SelectFilter::make('data_source')
                    ->options([
                        'sensor' => 'Sensor',
                        'manual' => 'Manual',
                        'system' => 'System',
                        'maintenance' => 'Maintenance',
                        'inspection' => 'Inspection',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Event Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('asset.name')
                            ->label('Asset'),
                        Infolists\Components\TextEntry::make('asset.site.name')
                            ->label('Site'),
                        Infolists\Components\TextEntry::make('event_type')
                            ->label('Event Type')
                            ->badge(),
                        Infolists\Components\TextEntry::make('event_description')
                            ->label('Description'),
                        Infolists\Components\TextEntry::make('data_source')
                            ->label('Data Source')
                            ->badge(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Recorded At')
                            ->dateTime(),
                    ])->columns(2),

                Infolists\Components\Section::make('Status Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('previous_status')
                            ->label('Previous Status')
                            ->badge(),
                        Infolists\Components\TextEntry::make('current_status')
                            ->label('Current Status')
                            ->badge(),
                        Infolists\Components\TextEntry::make('health_status')
                            ->label('Health Status')
                            ->badge(),
                        Infolists\Components\TextEntry::make('health_score')
                            ->label('Health Score')
                            ->suffix('%'),
                    ])->columns(2),

                Infolists\Components\Section::make('Environmental Data')
                    ->schema([
                        Infolists\Components\TextEntry::make('temperature')
                            ->label('Temperature')
                            ->suffix('°C'),
                        Infolists\Components\TextEntry::make('humidity')
                            ->label('Humidity')
                            ->suffix('%'),
                        Infolists\Components\TextEntry::make('vibration_level')
                            ->label('Vibration Level'),
                        Infolists\Components\TextEntry::make('power_output')
                            ->label('Power Output')
                            ->suffix('kW'),
                        Infolists\Components\TextEntry::make('efficiency_percentage')
                            ->label('Efficiency')
                            ->suffix('%'),
                    ])->columns(3),

                Infolists\Components\Section::make('Anomaly Information')
                    ->schema([
                        Infolists\Components\IconEntry::make('anomaly_detected')
                            ->label('Anomaly Detected')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('anomaly_description')
                            ->label('Anomaly Description'),
                        Infolists\Components\TextEntry::make('severity_level')
                            ->label('Severity Level')
                            ->badge(),
                    ])->columns(2)
                    ->visible(fn ($record) => $record->anomaly_detected),

                Infolists\Components\Section::make('Shift Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('shift_type')
                            ->label('Shift Type')
                            ->badge(),
                        Infolists\Components\TextEntry::make('shift_start')
                            ->label('Shift Start')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('shift_end')
                            ->label('Shift End')
                            ->dateTime(),
                    ])->columns(2)
                    ->visible(fn ($record) => $record->shift_type),

                Infolists\Components\Section::make('Performance Data')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('performance_data')
                            ->label('Performance Metrics'),
                    ])
                    ->visible(fn ($record) => $record->performance_data),

                Infolists\Components\Section::make('Diagnostic Data')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('diagnostic_data')
                            ->label('Diagnostic Results'),
                    ])
                    ->visible(fn ($record) => $record->diagnostic_data),

                Infolists\Components\Section::make('Additional Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('recordedBy.name')
                            ->label('Recorded By'),
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notes'),
                        Infolists\Components\KeyValueEntry::make('metadata')
                            ->label('Additional Metadata'),
                    ])->columns(2)
                    ->visible(fn ($record) => $record->recorded_by || $record->notes || $record->metadata),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssetHistories::route('/'),
            'view' => Pages\ViewAssetHistory::route('/{record}'),
        ];
    }

}