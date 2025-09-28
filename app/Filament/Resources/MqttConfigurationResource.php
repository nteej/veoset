<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MqttConfigurationResource\Pages;
use App\Models\MqttConfiguration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class MqttConfigurationResource extends Resource
{
    protected static ?string $model = MqttConfiguration::class;

    protected static ?string $navigationIcon = 'heroicon-o-signal';
    
    protected static ?string $navigationLabel = 'MQTT Settings';
    
    protected static ?string $modelLabel = 'MQTT Server Configuration';
    
    protected static ?string $pluralModelLabel = 'MQTT Server Configurations';
    
    protected static ?string $navigationGroup = 'System Settings';
    
    protected static ?int $navigationSort = 90;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('MQTT Configuration')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Server Settings')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Configuration Name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('host')
                                    ->label('MQTT Broker Host')
                                    ->required()
                                    ->default('mqtt.smartforce.fi')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('port')
                                    ->label('MQTT Broker Port')
                                    ->required()
                                    ->default(8883)
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(65535),
                                Forms\Components\Toggle::make('use_tls')
                                    ->label('Use TLS/SSL')
                                    ->default(true)
                                    ->helperText('Use TLS/SSL for secure connection'),
                                Forms\Components\Toggle::make('tls_self_signed_allowed')
                                    ->label('Allow Self-Signed Certificates')
                                    ->default(false)
                                    ->helperText('Enable this only for testing or private networks'),
                                Forms\Components\TextInput::make('client_id_prefix')
                                    ->label('Client ID Prefix')
                                    ->default('veoset-')
                                    ->required()
                                    ->helperText('Prefix for automatically generated client IDs')
                                    ->maxLength(30),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Is Active')
                                    ->default(true)
                                    ->helperText('Only one configuration can be active at a time'),
                            ]),
                        
                        Forms\Components\Tabs\Tab::make('Authentication')
                            ->schema([
                                Forms\Components\TextInput::make('username')
                                    ->label('Username')
                                    ->default('veosetuser')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->dehydrateStateUsing(fn ($state) => $state)
                                    ->placeholder('Leave empty to keep current password')
                                    ->maxLength(255),
                            ]),
                        
                        Forms\Components\Tabs\Tab::make('Topic Configuration')
                            ->schema([
                                Forms\Components\TextInput::make('topic_prefix')
                                    ->label('Topic Prefix')
                                    ->default('veo')
                                    ->required()
                                    ->helperText('Prefix for all MQTT topics (e.g. "veo")')
                                    ->maxLength(30),
                                Forms\Components\KeyValue::make('publish_topics')
                                    ->label('Publish Topics')
                                    ->helperText('Define topics for publishing messages (key: topic pattern, value: description)')
                                    ->addActionLabel('Add Topic')
                                    ->keyLabel('Topic Pattern')
                                    ->keyPlaceholder('assets/{asset_id}/data')
                                    ->valueLabel('Description')
                                    ->valuePlaceholder('Asset performance data')
                                    ->default(MqttConfiguration::getDefaultPublishTopics())
                                    ->columnSpan('full'),
                                Forms\Components\KeyValue::make('subscribe_topics')
                                    ->label('Subscribe Topics')
                                    ->helperText('Define topics to subscribe to (key: topic pattern, value: description)')
                                    ->addActionLabel('Add Topic')
                                    ->keyLabel('Topic Pattern')
                                    ->keyPlaceholder('assets/+/data')
                                    ->valueLabel('Description')
                                    ->valuePlaceholder('All asset data')
                                    ->default(MqttConfiguration::getDefaultSubscribeTopics())
                                    ->columnSpan('full'),
                            ]),
                        
                        Forms\Components\Tabs\Tab::make('Advanced Settings')
                            ->schema([
                                Forms\Components\TextInput::make('keep_alive_interval')
                                    ->label('Keep Alive Interval (seconds)')
                                    ->required()
                                    ->numeric()
                                    ->default(60),
                                Forms\Components\TextInput::make('connect_timeout')
                                    ->label('Connection Timeout (seconds)')
                                    ->required()
                                    ->numeric()
                                    ->default(60),
                                Forms\Components\TextInput::make('socket_timeout')
                                    ->label('Socket Timeout (seconds)')
                                    ->required()
                                    ->numeric()
                                    ->default(5),
                                Forms\Components\TextInput::make('resend_timeout')
                                    ->label('Resend Timeout (seconds)')
                                    ->required()
                                    ->numeric()
                                    ->default(10),
                                Forms\Components\Select::make('quality_of_service')
                                    ->label('Quality of Service (QoS)')
                                    ->options([
                                        0 => 'QoS 0 - At most once delivery',
                                        1 => 'QoS 1 - At least once delivery',
                                        2 => 'QoS 2 - Exactly once delivery',
                                    ])
                                    ->default(0)
                                    ->required(),
                                Forms\Components\Toggle::make('clean_session')
                                    ->label('Clean Session')
                                    ->default(true)
                                    ->helperText('Start with a clean session each time'),
                                Forms\Components\Toggle::make('retain_messages')
                                    ->label('Retain Messages')
                                    ->default(false)
                                    ->helperText('Broker will store the last message for each topic'),
                                Forms\Components\TextInput::make('max_reconnect_attempts')
                                    ->label('Max Reconnect Attempts')
                                    ->required()
                                    ->numeric()
                                    ->default(5),
                            ]),
                    ])
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('host')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('port')
                    ->sortable(),
                Tables\Columns\IconColumn::make('use_tls')
                    ->label('TLS')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('username')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('test_connection')
                    ->label('Test Connection')
                    ->icon('heroicon-o-signal')
                    ->color('success')
                    ->action(function (MqttConfiguration $record): void {
                        $result = $record->testConnection();
                        
                        if ($result['success']) {
                            Notification::make()
                                ->success()
                                ->title('Connection Successful')
                                ->body("Connected to {$result['broker']} with {$result['latency']} ms latency")
                                ->send();
                            
                            Log::info('MQTT Connection Test Successful', [
                                'host' => $record->host,
                                'port' => $record->port,
                                'latency' => $result['latency'],
                                'user' => Auth::id(),
                            ]);
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Connection Failed')
                                ->body($result['message'])
                                ->send();
                            
                            Log::error('MQTT Connection Test Failed', [
                                'host' => $record->host,
                                'port' => $record->port,
                                'error' => $result['error'],
                                'user' => Auth::id(),
                            ]);
                        }
                    }),
                Tables\Actions\Action::make('set_active')
                    ->label('Set As Active')
                    ->icon('heroicon-o-check-circle')
                    ->hidden(fn (MqttConfiguration $record) => $record->is_active)
                    ->action(function (MqttConfiguration $record): void {
                        // Deactivate all other configurations
                        MqttConfiguration::where('id', '!=', $record->id)
                            ->where('is_active', true)
                            ->update(['is_active' => false]);
                            
                        // Activate this configuration
                        $record->update(['is_active' => true]);
                        
                        Notification::make()
                            ->success()
                            ->title('Configuration Activated')
                            ->body("The MQTT configuration '{$record->name}' is now active")
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMqttConfigurations::route('/'),
            'create' => Pages\CreateMqttConfiguration::route('/create'),
            'edit' => Pages\EditMqttConfiguration::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count() > 0 ? null : 'New';
    }
    
    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->hasRole('veo_admin');
    }
}