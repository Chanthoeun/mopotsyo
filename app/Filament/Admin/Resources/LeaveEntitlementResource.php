<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LeaveEntitlementResource\Pages;
use App\Filament\Admin\Resources\LeaveEntitlementResource\RelationManagers;
use App\Models\LeaveEntitlement;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class LeaveEntitlementResource extends Resource
{
    protected static ?string $model = LeaveEntitlement::class;

    protected static ?string $navigationIcon = 'fas-user-clock';

    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return __('model.entitlement');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.entitlements');
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
                            ->relationship('user', 'name', function(Builder $query) {
                                $query->whereHas('employee', function(Builder $query) {
                                    $query->whereNull('resign_date');
                                    $query->whereHas('contracts', function(Builder $query) {
                                        $query->where('is_active', true);
                                        // $query->whereHas('contractType', function(Builder $query) {
                                        //     $query->where('allow_leave_request', true);
                                        // });
                                    });
                                });
                            })
                            ->required()
                            ->preload()
                            ->searchable()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function($state, Set $set){
                                $set('leave_type_id', null);
                                $set('balance', null);
                                if(!empty($state)){
                                    $user = User::with('employee')->find($state);           
                                    if(empty($user->entitlements)){
                                        $startDate = Carbon::createFromDate(now()->year, $user->employee->join_date->month, $user->employee->join_date->day);
                                    }else{
                                        $startDate = Carbon::createFromDate(date('Y'), '01', '01');
                                    }                         

                                    $set('start_date', $startDate->toFormattedDateString());
                                    $set('end_date', Carbon::createFromDate(date('Y'), '12', '31')->toFormattedDateString());
                                }                                
                            }),
                        Forms\Components\Select::make('leave_type_id')
                            ->label(__('model.leave_type'))
                            ->relationship('leaveType', 'name', function(Get $get, Builder $query) {
                                if($get('user_id')){
                                    $user = User::find($get('user_id'));
                                    if($user->contract->contractType->leave_types){
                                        return $query->whereIn('id', $user->contract->contractType->leave_types)->where('balance', '>', 0)->where($user->employee->gender->value, true)->orderBy('id', 'asc');
                                    }else{
                                        return $query->where('balance', '>', 0)->where($user->employee->gender->value, true)->orderBy('id', 'asc');
                                    }
                                }
                            })
                            ->live()
                            ->afterStateUpdated(function($state, Get $get, Set $set){
                                if($get('user_id') && $state){
                                    $user = User::with('employee')->find($get('user_id'));                                    
                                    $leaveType = LeaveType::find($state);

                                    if(!empty($leaveType->option['balance_increment_amount']) && !empty($leaveType->option['balance_increment_period'])){
                                        $set('balance', getEntitlementBalance($user->employee->join_date, $leaveType));
                                    }else{
                                        $set('balance', $leaveType->balance);
                                    }                                                                        
                                }else{
                                    $set('balance', null);
                                }
                            }),
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label(__('field.start_date'))
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->suffixIcon('fas-calendar')
                                    ->afterStateUpdated(function($state, Set $set){                                
                                        $set('end_date', Carbon::parse($state)->addYear()->subDay()->toFormattedDateString());
                                    }),
                                Forms\Components\DatePicker::make('end_date')
                                    ->label(__('field.end_date'))
                                    ->required()
                                    ->native(false)
                                    ->suffixIcon('fas-calendar'),
                                Forms\Components\TextInput::make('balance')
                                    ->label(__('field.balance'))
                                    ->numeric()
                                    ->default(0), 
                                Forms\Components\TextInput::make('taken')
                                    ->label(__('field.taken'))
                                    ->numeric()
                                    ->default(0), 
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
                Tables\Columns\TextColumn::make('leaveType.name')
                    ->label(__('model.leave_type'))
                    ->numeric()
                    ->sortable(),
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
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('all_taken')
                    ->label(__('field.taken'))
                    ->numeric()
                    ->alignCenter()
                    ->badge()
                    ->color('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining')
                    ->label(__('field.remaining'))
                    ->numeric()
                    ->alignCenter()
                    ->badge()
                    ->color('success')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label(__('field.is_active')),            
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
                Tables\Filters\SelectFilter::make('user_id')
                    ->label(__('model.employee'))
                    ->relationship('user', 'name', fn(Builder $query) => $query->whereHas('entitlements')),
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
            'index' => Pages\ListLeaveEntitlements::route('/'),
            'create' => Pages\CreateLeaveEntitlement::route('/create'),
            'edit' => Pages\EditLeaveEntitlement::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if(Auth::user()->hasRole(['super_admin', 'human_resource'])) {
            return $query; 
        }

        return parent::getEloquentQuery()
                ->where('user_id', Auth::id());
    }
}
