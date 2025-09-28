<?php

namespace App\Filament\Resources\MqttConfigurationResource\Pages;

use App\Filament\Resources\MqttConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use App\Models\MqttConfiguration;

class EditMqttConfiguration extends EditRecord
{
    protected static string $resource = MqttConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('test_connection')
                ->label('Test Connection')
                ->icon('heroicon-o-signal')
                ->color('success')
                ->action(function (): void {
                    $result = $this->record->testConnection();
                    
                    if ($result['success']) {
                        Notification::make()
                            ->success()
                            ->title('Connection Successful')
                            ->body("Connected to {$result['broker']} with {$result['latency']} ms latency")
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Connection Failed')
                            ->body($result['message'])
                            ->send();
                    }
                }),
        ];
    }
    
    protected function afterSave(): void
    {
        // If this configuration is set as active
        if ($this->record->is_active) {
            // Ensure all other configurations are inactive
            MqttConfiguration::where('id', '!=', $this->record->id)
                ->update(['is_active' => false]);
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}