<?php

namespace App\Filament\Resources\ServiceTaskResource\Pages;

use App\Filament\Resources\ServiceTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewServiceTask extends ViewRecord
{
    protected static string $resource = ServiceTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}