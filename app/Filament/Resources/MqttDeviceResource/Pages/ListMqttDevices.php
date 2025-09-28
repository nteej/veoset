<?php

namespace App\Filament\Resources\MqttDeviceResource\Pages;

use App\Filament\Resources\MqttDeviceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMqttDevices extends ListRecords
{
    protected static string $resource = MqttDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
