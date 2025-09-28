<?php

namespace App\Filament\Resources\MqttConfigurationResource\Pages;

use App\Filament\Resources\MqttConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListMqttConfigurations extends ListRecords
{
    protected static string $resource = MqttConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}