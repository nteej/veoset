<?php

namespace App\Filament\Resources\AssetHistoryResource\Pages;

use App\Filament\Resources\AssetHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;

class ViewAssetHistory extends ViewRecord
{
    protected static string $resource = AssetHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPDF')
                ->label('Download PDF Report')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn () => route('asset-history.pdf', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}