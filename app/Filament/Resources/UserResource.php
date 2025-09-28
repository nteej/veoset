<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Models\Site;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(User::class, 'email', ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(fn ($livewire) => $livewire instanceof Pages\CreateUser)
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn ($state) => $state ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->hint('Leave blank to keep current password when editing'),
                    ])->columns(2),

                Forms\Components\Section::make('Role & Permissions')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('Role')
                            ->relationship('roles', 'name')
                            ->options(Role::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Additional Permissions')
                            ->relationship('permissions', 'name')
                            ->options(Permission::all()->pluck('name', 'id'))
                            ->searchable()
                            ->columns(3)
                            ->hint('Select additional permissions beyond the role'),
                    ]),

                Forms\Components\Section::make('Site Access')
                    ->schema([
                        Forms\Components\Select::make('sites')
                            ->label('Assigned Sites')
                            ->relationship('sites', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->hint('Leave empty for VEO admins to access all sites'),
                    ])
                    ->visible(fn () => auth()->user()->hasRole('veo_admin')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'veo_admin' => 'danger',
                        'site_manager' => 'warning',
                        'maintenance_staff' => 'info',
                        'customer' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('sites.name')
                    ->label('Sites')
                    ->badge()
                    ->separator(', ')
                    ->limit(2)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (is_array($state) && count($state) > 2) {
                            return 'Sites: ' . implode(', ', $state);
                        }
                        return null;
                    }),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('sites')
                    ->relationship('sites', 'name')
                    ->multiple()
                    ->preload(),
                Tables\Filters\Filter::make('verified')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at'))
                    ->label('Verified users only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['veo_admin', 'site_manager']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['veo_admin', 'site_manager']);
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        if ($user->hasRole('veo_admin')) {
            return true;
        }

        if ($user->hasRole('site_manager')) {
            return !$record->hasRole('veo_admin');
        }

        return false;
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();

        if ($user->hasRole('veo_admin')) {
            return $record->id !== $user->id;
        }

        if ($user->hasRole('site_manager')) {
            return !$record->hasRole('veo_admin') && $record->id !== $user->id;
        }

        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery();

        if ($user->hasRole('veo_admin')) {
            return $query;
        }

        if ($user->hasRole('site_manager')) {
            $userSiteIds = $user->sites()->pluck('sites.id');
            return $query->whereHas('sites', function ($q) use ($userSiteIds) {
                $q->whereIn('sites.id', $userSiteIds);
            })->orWhere('id', $user->id);
        }

        return $query->where('id', -1);
    }
}
