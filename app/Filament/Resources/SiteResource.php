<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteResource\Pages;
use App\Filament\Resources\SiteResource\RelationManagers;
use App\Models\Site;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Energy Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Site Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Main Wind Farm Alpha'),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->placeholder('Detailed description of the energy site and its purpose')
                            ->rows(3),
                        Forms\Components\TextInput::make('location')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Houston, TX')
                            ->hint('City or region where the site is located'),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Active sites can have assets and maintenance activities'),
                    ])->columns(2),

                Forms\Components\Section::make('Address & Coordinates')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->maxLength(255)
                            ->placeholder('Full street address')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('latitude')
                            ->numeric()
                            ->step(0.00000001)
                            ->placeholder('40.7128')
                            ->hint('Decimal degrees'),
                        Forms\Components\TextInput::make('longitude')
                            ->numeric()
                            ->step(0.00000001)
                            ->placeholder('-74.0060')
                            ->hint('Decimal degrees'),
                    ])->columns(2),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('contact_person')
                            ->maxLength(255)
                            ->placeholder('Site Manager Name'),
                        Forms\Components\TextInput::make('contact_email')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('manager@energysite.com'),
                        Forms\Components\TextInput::make('contact_phone')
                            ->tel()
                            ->maxLength(255)
                            ->placeholder('+1-555-0123'),
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
                Tables\Columns\TextColumn::make('location')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-map-pin'),
                Tables\Columns\TextColumn::make('assets_count')
                    ->counts('assets')
                    ->label('Assets')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('contact_person')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('contact_email')
                    ->searchable()
                    ->toggleable()
                    ->copyable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Status')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Site Status')
                    ->placeholder('All sites')
                    ->trueLabel('Active sites only')
                    ->falseLabel('Inactive sites only'),
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
            'index' => Pages\ListSites::route('/'),
            'create' => Pages\CreateSite::route('/create'),
            'view' => Pages\ViewSite::route('/{record}'),
            'edit' => Pages\EditSite::route('/{record}/edit'),
        ];
    }
}
