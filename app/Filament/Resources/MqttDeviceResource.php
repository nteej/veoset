<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MqttDeviceResource\Pages;
use App\Filament\Resources\MqttDeviceResource\RelationManagers;
use App\Models\MqttDevice;
use App\Services\MqttService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\BadgeEntry;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MqttDeviceResource extends Resource
{
    protected static ?string $model = MqttDevice::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'MQTT Devices';

    protected static ?string $modelLabel = 'MQTT Device';

    protected static ?string $pluralModelLabel = 'MQTT Devices';

    protected static ?string $navigationGroup = 'IoT Management';

    protected static ?int $navigationSort = 1;

    // Policy-based authorization
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', MqttDevice::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', MqttDevice::class) ?? false;
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

        // VEO Admin can see all MQTT devices
        if ($user->hasRole('veo_admin')) {
            return $query;
        }

        // Site Managers can only see devices for assets in their sites
        if ($user->hasRole('site_manager')) {
            $siteIds = $user->sites()->pluck('sites.id');
            return $query->whereHas('asset', function ($assetQuery) use ($siteIds) {
                $assetQuery->whereIn('site_id', $siteIds);
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Device Information')
                    ->schema([
                        Forms\Components\TextInput::make('device_id')
                            ->required()
                            ->unique(ignoringRecord: true)
                            ->maxLength(255),
                        Forms\Components\Select::make('asset_id')
                            ->relationship('asset', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('device_type')
                            ->options(MqttDevice::getDeviceTypes())
                            ->required(),
                        Forms\Components\TextInput::make('manufacturer')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('model')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('firmware_version')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Device Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options(MqttDevice::getStatusOptions())
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\TextInput::make('battery_level')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%'),
                        Forms\Components\TextInput::make('signal_strength')
                            ->numeric()
                            ->suffix('dBm'),
                    ])->columns(2),

                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\Repeater::make('capabilities')
                            ->simple(
                                Forms\Components\TextInput::make('capability')
                                    ->required()
                            )
                            ->defaultItems(1),
                        Forms\Components\KeyValue::make('configuration')
                            ->keyLabel('Setting')
                            ->valueLabel('Value'),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull()
                            ->rows(3),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('device_id')
                    ->label('Device ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('asset.name')
                    ->label('Asset')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('device_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state)))
                    ->colors([
                        'primary' => 'sensor',
                        'success' => 'controller',
                        'warning' => 'gateway',
                        'danger' => 'actuator',
                        'info' => 'monitor',
                        'secondary' => 'analyzer',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'online',
                        'danger' => 'offline',
                        'warning' => 'error',
                        'secondary' => 'maintenance',
                    ]),
                Tables\Columns\IconColumn::make('is_online')
                    ->label('Online')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('battery_level')
                    ->label('Battery')
                    ->suffix('%')
                    ->color(fn ($state) => $state > 75 ? 'success' : ($state > 25 ? 'warning' : 'danger'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('signal_strength')
                    ->label('Signal')
                    ->suffix(' dBm')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_seen')
                    ->label('Last Seen')
                    ->dateTime()
                    ->sortable()
                    ->since(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('device_type')
                    ->options(MqttDevice::getDeviceTypes()),
                Tables\Filters\SelectFilter::make('status')
                    ->options(MqttDevice::getStatusOptions()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\Filter::make('online_only')
                    ->label('Online Only')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'online')),
                Tables\Filters\Filter::make('low_battery')
                    ->label('Low Battery')
                    ->query(fn (Builder $query): Builder => $query->where('battery_level', '<', 25)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('ping')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function (MqttDevice $record) {
                        $mqttService = app(MqttService::class);
                        $success = $mqttService->sendCommand($record->asset_id, 'ping');

                        if ($success) {
                            Filament\Notifications\Notification::make()
                                ->title('Ping sent successfully')
                                ->success()
                                ->send();
                        } else {
                            Filament\Notifications\Notification::make()
                                ->title('Failed to send ping')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each->update(['is_active' => true]);
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each->update(['is_active' => false]);
                        }),
                ]),
            ])
            ->defaultSort('last_seen', 'desc');
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
            'index' => Pages\ListMqttDevices::route('/'),
            'create' => Pages\CreateMqttDevice::route('/create'),
            'edit' => Pages\EditMqttDevice::route('/{record}/edit'),
        ];
    }
}
