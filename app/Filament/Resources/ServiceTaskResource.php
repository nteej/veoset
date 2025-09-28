<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceTaskResource\Pages;
use App\Filament\Resources\ServiceTaskResource\RelationManagers;
use App\Models\ServiceTask;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceTaskResource extends Resource
{
    protected static ?string $model = ServiceTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Maintenance';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Service Tasks';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Task Information')
                    ->schema([
                        Forms\Components\Select::make('asset_id')
                            ->relationship('asset', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Select asset for maintenance'),
                        Forms\Components\Select::make('assigned_to')
                            ->relationship('assignedUser', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Assign to technician'),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Quarterly Turbine Maintenance'),
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->columnSpanFull()
                            ->placeholder('Detailed description of maintenance activities')
                            ->rows(4),
                    ])->columns(2),

                Forms\Components\Section::make('Task Classification')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'preventive' => 'Preventive',
                                'corrective' => 'Corrective',
                                'predictive' => 'Predictive',
                                'emergency' => 'Emergency',
                            ])
                            ->default('preventive'),
                        Forms\Components\Select::make('priority')
                            ->required()
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'critical' => 'Critical',
                            ])
                            ->default('medium'),
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'on_hold' => 'On Hold',
                            ])
                            ->default('pending'),
                        Forms\Components\Toggle::make('requires_shutdown')
                            ->default(false)
                            ->helperText('Requires asset shutdown for maintenance'),
                    ])->columns(4),

                Forms\Components\Section::make('Scheduling')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_date')
                            ->required()
                            ->native(false)
                            ->minDate(now())
                            ->hint('When this maintenance should be performed'),
                        Forms\Components\TextInput::make('estimated_duration_hours')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(168)
                            ->suffix('hours')
                            ->placeholder('8'),
                        Forms\Components\DateTimePicker::make('started_at')
                            ->native(false)
                            ->hint('Automatically set when task is started'),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->native(false)
                            ->hint('Automatically set when task is completed'),
                        Forms\Components\TextInput::make('actual_duration_hours')
                            ->numeric()
                            ->suffix('hours')
                            ->readOnly()
                            ->hint('Calculated automatically'),
                    ])->columns(3),

                Forms\Components\Section::make('Technical Details')
                    ->schema([
                        Forms\Components\TagsInput::make('required_tools')
                            ->placeholder('Add tools: crane, multimeter, safety harness')
                            ->hint('Press Enter after each tool'),
                        Forms\Components\TagsInput::make('required_materials')
                            ->placeholder('Add materials: oil filter, hydraulic fluid')
                            ->hint('Press Enter after each material'),
                        Forms\Components\KeyValue::make('safety_requirements')
                            ->label('Safety Requirements')
                            ->keyLabel('Requirement')
                            ->valueLabel('Details')
                            ->hint('Safety protocols and PPE requirements'),
                    ])->columns(1),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull()
                            ->placeholder('Additional notes, special instructions, or considerations')
                            ->rows(3),
                        Forms\Components\Textarea::make('completion_notes')
                            ->columnSpanFull()
                            ->placeholder('Notes added upon completion of the task')
                            ->rows(3)
                            ->hint('Filled out when marking task as complete'),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),
                Tables\Columns\TextColumn::make('asset.name')
                    ->label('Asset')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-cog-6-tooth'),
                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Assigned To')
                    ->searchable()
                    ->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'preventive' => 'success',
                        'corrective' => 'warning',
                        'predictive' => 'info',
                        'emergency' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'low' => 'gray',
                        'medium' => 'info',
                        'high' => 'warning',
                        'critical' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'on_hold' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Scheduled')
                    ->dateTime()
                    ->sortable()
                    ->color(fn($state): string => match (true) {
                        !$state => 'gray',
                        \Carbon\Carbon::parse($state)->isPast() => 'danger',
                        \Carbon\Carbon::parse($state)->diffInDays() <= 3 => 'warning',
                        default => 'success'
                    }),
                Tables\Columns\IconColumn::make('requires_shutdown')
                    ->label('Shutdown')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('asset')
                    ->relationship('asset', 'name')
                    ->searchable()
                    ->placeholder('All assets'),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->relationship('assignedUser', 'name')
                    ->searchable()
                    ->placeholder('All technicians'),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'preventive' => 'Preventive',
                        'corrective' => 'Corrective',
                        'predictive' => 'Predictive',
                        'emergency' => 'Emergency',
                    ])
                    ->placeholder('All types'),
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'critical' => 'Critical',
                    ])
                    ->placeholder('All priorities'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'on_hold' => 'On Hold',
                    ])
                    ->placeholder('All statuses'),
                Tables\Filters\TernaryFilter::make('requires_shutdown')
                    ->label('Requires Shutdown')
                    ->placeholder('All tasks')
                    ->trueLabel('Shutdown required')
                    ->falseLabel('No shutdown needed'),
                Tables\Filters\Filter::make('overdue')
                    ->query(fn(Builder $query): Builder => $query->where('scheduled_date', '<', now())->whereIn('status', ['pending', 'in_progress']))
                    ->label('Overdue Tasks'),
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
            ->defaultSort('scheduled_date', 'asc');
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
            'index' => Pages\ListServiceTasks::route('/'),
            'create' => Pages\CreateServiceTask::route('/create'),
            'view' => Pages\ViewServiceTask::route('/{record}'),
            'edit' => Pages\EditServiceTask::route('/{record}/edit'),
        ];
    }
}
