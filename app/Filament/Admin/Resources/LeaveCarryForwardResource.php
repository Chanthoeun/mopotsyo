<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LeaveCarryForwardResource\Pages;
use App\Filament\Admin\Resources\LeaveCarryForwardResource\RelationManagers;
use App\Models\LeaveCarryForward;
use App\Models\LeaveEntitlement;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeaveCarryForwardResource extends Resource
{
    protected static ?string $model = LeaveCarryForward::class;

    protected static ?string $navigationIcon = 'fas-share';

    protected static ?int $navigationSort = 6;

    public static function getModelLabel(): string
    {
        return __('model.carry_forward');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.carry_forwards');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.employee');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label(__('model.employee'))
                            ->relationship('user', 'name', fn(Builder $query) => $query->whereNot('id', 1)->whereHas('employee', fn($query) => $query->whereNull('resign_date')))
                            ->required()
                            ->preload()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function($state, Set $set){
                                $set('leave_entitlement_id', null);
                                $set('start_date', null);
                                $set('end_date', null);
                                $set('balance', null);                                                               
                            }),
                        Forms\Components\Select::make('leave_entitlement_id')
                            ->label(__('model.entitlement'))
                            ->relationship('leaveEntitlement', 'id', fn(Get $get, Builder $query) => $query->where('user_id', $get('user_id'))->where('is_active', true)->whereHas('leaveType', fn($query) => $query->where('option->allow_carry_forward', true)))
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn(Model $record) => "{$record->leaveType->name} - {$record->start_date->toFormattedDateString()} - {$record->end_date->toFormattedDateString()} ({$record->remaining})")
                            ->live()
                            ->afterStateUpdated(function($state, Set $set){
                                $entitlement = LeaveEntitlement::find($state);
                                $endDate = Carbon::parse($entitlement->end_date)->add($entitlement->leaveType->option['carry_forward_duration'])->subDay()->toFormattedDateString();
                                $set('start_date', $entitlement->end_date->toFormattedDateString());                                
                                $set('end_date', $endDate);
                                $set('balance', $entitlement->remaining);
                            }),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label(__('field.start_date'))
                                    ->required()
                                    ->native(false),
                                Forms\Components\DatePicker::make('end_date')
                                    ->label(__('field.end_date'))
                                    ->required()
                                    ->native(false),
                                Forms\Components\TextInput::make('balance')
                                    ->label(__('field.balance'))
                                    ->required()
                                    ->numeric()
                                    ->default(0.00),
                            ])
                    ])
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('model.employee'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('leaveEntitlement.leaveType.name')
                    ->label(__('model.entitlement'))
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (Model $record): string => "{$record->leaveEntitlement->leaveType->name} ({$record->leaveEntitlement->start_date->toFormattedDateString()} - {$record->leaveEntitlement->end_date->toFormattedDateString()})"),
                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('field.start_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label(__('field.end_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label(__('field.balance'))
                    ->numeric()
                    ->badge()
                    ->color('info')
                    ->alignCenter()
                    ->sortable(),                
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('field.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('field.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label(__('field.deleted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListLeaveCarryForwards::route('/'),
            'create' => Pages\CreateLeaveCarryForward::route('/create'),
            'edit' => Pages\EditLeaveCarryForward::route('/{record}/edit'),
        ];
    }

    // public static function getEloquentQuery(): Builder
    // {
    //     return parent::getEloquentQuery()
    //         ->withoutGlobalScopes([
    //             SoftDeletingScope::class,
    //         ]);
    // }
}
