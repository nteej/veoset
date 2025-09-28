<?php

namespace App\Filament\Widgets;

use App\Models\Asset;
use App\Models\ServiceTask;
use App\Models\Site;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Sites', Site::count())
                ->description('Active energy sites')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('success'),

            Stat::make('Total Assets', Asset::count())
                ->description('Managed energy assets')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('info'),

            Stat::make('Active Assets', Asset::where('is_active', true)->count())
                ->description('Currently operational')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Pending Tasks', ServiceTask::where('status', 'pending')->count())
                ->description('Awaiting maintenance')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Overdue Tasks', ServiceTask::where('scheduled_date', '<', now())
                ->whereIn('status', ['pending', 'in_progress'])
                ->count())
                ->description('Past due date')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Critical Tasks', ServiceTask::where('priority', 'critical')
                ->whereIn('status', ['pending', 'in_progress'])
                ->count())
                ->description('High priority maintenance')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('danger'),
        ];
    }
}