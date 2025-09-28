<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssetResource\Pages;
use App\Filament\Resources\AssetResource\RelationManagers;
use App\Models\Asset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Energy Management';

    protected static ?int $navigationSort = 2;

    // Policy-based authorization
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', Asset::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', Asset::class) ?? false;
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
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0'); // No access for unauthenticated users
        }

        // VEO Admin can see all assets
        if ($user->hasRole('veo_admin')) {
            return $query;
        }

        // Other roles can only see assets in their assigned sites
        if ($user->hasAnyRole(['site_manager', 'maintenance_staff', 'customer'])) {
            $siteIds = $user->sites()->pluck('sites.id');
            return $query->whereIn('site_id', $siteIds);
        }

        return $query->whereRaw('1 = 0'); // No access for other users
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Asset Information')
                    ->schema([
                        Forms\Components\Select::make('site_id')
                            ->relationship('site', 'name')
                            ->required()
                            ->searchable()
                            ->placeholder('Select site location'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Wind Turbine 1'),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->placeholder('Detailed description of the asset')
                            ->rows(3),
                        Forms\Components\Select::make('asset_type')
                            ->required()
                            ->options([
                                'turbine' => 'Wind Turbine',
                                'transformer' => 'Transformer',
                                'generator' => 'Generator',
                                'inverter' => 'Inverter',
                                'battery' => 'Battery System',
                                'solar_panel' => 'Solar Panel Array',
                            ])
                            ->placeholder('Select asset type'),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Active assets can be assigned maintenance tasks'),
                    ])->columns(2),

                Forms\Components\Section::make('Technical Details')
                    ->schema([
                        Forms\Components\TextInput::make('manufacturer')
                            ->maxLength(255)
                            ->placeholder('e.g., Siemens, GE, Vestas'),
                        Forms\Components\TextInput::make('model')
                            ->maxLength(255)
                            ->placeholder('Model number or designation'),
                        Forms\Components\TextInput::make('serial_number')
                            ->maxLength(255)
                            ->placeholder('Unique serial number'),
                    ])->columns(3),

                Forms\Components\Section::make('Status & Operation')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'operational' => 'Operational',
                                'maintenance' => 'Under Maintenance',
                                'offline' => 'Offline',
                                'decommissioned' => 'Decommissioned',
                            ])
                            ->default('operational'),
                        Forms\Components\Select::make('mode')
                            ->required()
                            ->options([
                                'auto' => 'Automatic',
                                'manual' => 'Manual',
                                'standby' => 'Standby',
                            ])
                            ->default('auto'),
                    ])->columns(2),

                Forms\Components\Section::make('Maintenance Schedule')
                    ->schema([
                        Forms\Components\DatePicker::make('installation_date')
                            ->hint('When the asset was first installed'),
                        Forms\Components\DatePicker::make('last_maintenance_date')
                            ->hint('Last completed maintenance'),
                        Forms\Components\DatePicker::make('next_maintenance_date')
                            ->hint('Next scheduled maintenance')
                            ->afterOrEqual('today'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('site.name')
                    ->label('Site')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-map-pin'),
                Tables\Columns\TextColumn::make('asset_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'turbine' => 'Wind Turbine',
                        'transformer' => 'Transformer',
                        'generator' => 'Generator',
                        'inverter' => 'Inverter',
                        'battery' => 'Battery',
                        'solar_panel' => 'Solar Panel',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'turbine' => 'info',
                        'transformer' => 'warning',
                        'generator' => 'success',
                        'inverter' => 'primary',
                        'battery' => 'danger',
                        'solar_panel' => 'yellow',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'operational' => 'success',
                        'maintenance' => 'warning',
                        'offline' => 'danger',
                        'decommissioned' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('manufacturer')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('model')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('next_maintenance_date')
                    ->label('Next Maintenance')
                    ->date()
                    ->sortable()
                    ->color(fn ($state): string => match (true) {
                        !$state => 'gray',
                        \Carbon\Carbon::parse($state)->isPast() => 'danger',
                        \Carbon\Carbon::parse($state)->diffInDays() <= 7 => 'warning',
                        default => 'success'
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('site')
                    ->relationship('site', 'name')
                    ->searchable()
                    ->placeholder('All sites'),
                Tables\Filters\SelectFilter::make('asset_type')
                    ->options([
                        'turbine' => 'Wind Turbine',
                        'transformer' => 'Transformer',
                        'generator' => 'Generator',
                        'inverter' => 'Inverter',
                        'battery' => 'Battery System',
                        'solar_panel' => 'Solar Panel Array',
                    ])
                    ->placeholder('All types'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'operational' => 'Operational',
                        'maintenance' => 'Under Maintenance',
                        'offline' => 'Offline',
                        'decommissioned' => 'Decommissioned',
                    ])
                    ->placeholder('All statuses'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All assets')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                Tables\Filters\Filter::make('needs_maintenance')
                    ->query(fn (Builder $query): Builder => $query->where('next_maintenance_date', '<=', now()))
                    ->label('Needs Maintenance'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListAssets::route('/'),
            'create' => Pages\CreateAsset::route('/create'),
            'view' => Pages\ViewAsset::route('/{record}'),
            'edit' => Pages\EditAsset::route('/{record}/edit'),
        ];
    }
}
