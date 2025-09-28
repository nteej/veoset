<?php

namespace App\Filament\Resources\MqttConfigurationResource\Pages;

use App\Filament\Resources\MqttConfigurationResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Models\MqttConfiguration;

class CreateMqttConfiguration extends CreateRecord
{
    protected static string $resource = MqttConfigurationResource::class;
    
    protected function afterCreate(): void
    {
        $record = $this->record;
        
        // If this is the first configuration or marked as active
        if (MqttConfiguration::count() == 1 || $record->is_active) {
            // Ensure all other configurations are inactive
            MqttConfiguration::where('id', '!=', $record->id)
                ->update(['is_active' => false]);
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}