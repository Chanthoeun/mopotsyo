<?php

namespace App\Filament\Admin\Resources;

use App\Enums\ApprovalStatuEnum;
use App\Filament\Admin\Resources\LeaveRequestResource\Pages;
use App\Filament\Admin\Resources\LeaveRequestResource\RelationManagers;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestRule;
use App\Models\LeaveType;
use App\Models\PublicHoliday;
use App\Models\User;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
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
use Illuminate\Support\HtmlString;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('model.leave_request');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.leave_requests');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.employee');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(2)
            ->schema([
                Forms\Components\Section::make(__('field.leave_request_form'))
                    ->columnSpan(['lg' => 1])
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label(__('model.employee'))
                            ->relationship('user', 'name', function(Builder $query) {
                                $query->whereHas('employee', function(Builder $query) {
                                    $query->whereNull('resign_date');
                                    $query->whereHas('contracts', function(Builder $query) {
                                        $query->where('is_active', true);
                                        $query->whereHas('contractType', function(Builder $query) {
                                            $query->where('allow_leave_request', true);
                                        });
                                    });
                                });
                            })
                            ->required()
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\ToggleButtons::make('leave_type_id')
                            ->label(__('model.leave_type'))
                            ->options(function(Get $get) {
                                if($get('user_id')){
                                    $user = User::with('employee.contracts.contractType')->find($get('user_id'));
                                    $contract = $user->employee->contracts->where('is_active', true)->first();                                            
                                    return LeaveType::whereIn('id', $contract->contractType->leave_types)->where($user->employee->gender->value, true)->get()->pluck('abbr', 'id');
                                }
                                return LeaveType::all()->pluck('abbr', 'id');
                            })
                            ->required()                                    
                            ->inline()
                            ->grouped()                            
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('from_date')
                            ->label(__('field.from_date'))
                            ->placeholder(__('field.select_date'))
                            ->required()
                            ->native(false)
                            ->suffixIcon('fas-calendar')
                            ->live()
                            ->afterStateUpdated(fn($state, Set $set) => $set('to_date', $state)),
                        Forms\Components\DatePicker::make('to_date')
                            ->label(__('field.to_date'))
                            ->placeholder(__('field.select_date'))
                            ->required()
                            ->native(false)
                            ->suffixIcon('fas-calendar')
                            ->live()
                            ->afterStateUpdated(function($state, Get $get, Set $set, string $operation, ?Model $record){
                                $set("requestDates", []);
                                if($get('user_id') && $get('leave_type_id') && $get('from_date')){
                                    if($operation == 'edit'){
                                        $user = User::with(['employee.contracts', 'leaveRequests' => function($query) use($record){
                                            return $query->whereNot('id', $record->id)->whereIn('status', [ApprovalStatuEnum::SUBMITTED, ApprovalStatuEnum::APPROVED]);
                                        }])->find($get('user_id'));
                                    }else{
                                        $user = User::with(['leaveRequests' => function($query){
                                            return $query->whereIn('status', [ApprovalStatuEnum::SUBMITTED, ApprovalStatuEnum::APPROVED]);
                                        }])->find($get('user_id'));
                                    }
                                    
                                    $contract = $user->employee->contracts->where('is_active', true)->first();
                                    $dates = getDateRangeBetweenTwoDates($get('from_date'), $state);
                                    $key = 0;
                                    foreach($dates as $date){
                                        if(isWeekend($date) == false && isPublicHoliday($date) == false){
                                            $requestDate = checkDuplicatedLeaveRequest($user, $date);                                                    
                                            if($requestDate == false){
                                                $set("requestDates.{$key}.date", $date->toDateString());    
                                                $set("requestDates.{$key}.start_time", '08:00');
                                                $set("requestDates.{$key}.end_time", '17:00');
                                                $set("requestDates.{$key}.hours", round(getHoursBetweenTwoTimes($get('start_time'), $state), 1));
                                                $key++;  
                                            }else if($requestDate->hours < $contract->shift->work_hours){
                                                if($requestDate->end_time == '12:00:00'){
                                                    $startTime = '13:00:00';
                                                }else{
                                                    $startTime = $requestDate->end_time;
                                                }
                                                $set("requestDates.{$key}.date", $date->toDateString());   
                                                $set("requestDates.{$key}.start_time", $startTime);
                                                $set("requestDates.{$key}.end_time", '17:00');                                                     
                                                $set("requestDates.{$key}.hours", round(getHoursBetweenTwoTimes($get('start_time'), $state), 1));
                                                $key++;  
                                            }                                    
                                        }                                                
                                    }

                                    // adjust from and to date base requestDates
                                    $repeaters = $get('requestDates');
                                    if(count($repeaters) > 0){
                                        $set('from_date', $repeaters[0]['date']);
                                        $set('to_date', $repeaters[count($repeaters) - 1]['date']);
                                    }
                                }
                            }),
                        Forms\Components\Textarea::make('reason')
                            ->columnSpanFull(),
                        TableRepeater::make('requestDates')
                            ->label(__('field.request_dates'))
                            ->relationship()
                            ->required()                                                                        
                            ->addable(false)  
                            ->deletable(false)
                            ->defaultItems(0)                              
                            ->columnSpanFull()
                            ->headers([
                                Header::make(__('field.date')),
                                Header::make(__('field.start_time'))->width('150px'),
                                Header::make(__('field.end_time'))->width('150px'),
                                Header::make(__('field.hour'))->width('80px'),
                            ])
                            ->schema([
                                Forms\Components\DatePicker::make('date')
                                    ->hiddenLabel()
                                    ->placeholder(__('field.select_date'))
                                    ->required()
                                    ->native(false),                              
                                Forms\Components\TimePicker::make('start_time')
                                    ->hiddenLabel()                                            
                                    ->required()
                                    ->seconds(false)
                                    ->default('08:00:00'),
                                Forms\Components\TimePicker::make('end_time')
                                    ->hiddenLabel()                                            
                                    ->required()
                                    ->seconds(false)
                                    ->live()
                                    ->afterStateUpdated(function($state, Get $get, Set $set){                                                
                                        $set('hours', round(getHoursBetweenTwoTimes($get('start_time'), $state), 1));
                                    })
                                    ->default('17:00:00'),
                                Forms\Components\TextInput::make('hours')
                                    ->hiddenLabel()                                            
                                    ->required()
                                    ->readOnly()
                                    ->default(0),
                            ]),                                 
                        // Forms\Components\Placeholder::make('total')
                        //     ->label(__('field.total', ['name' => __('model.request_dates')]))
                        //     ->inlineLabel()
                        //     ->columnSpanFull()
                        //     ->content(function(Get $get, Set $set): string {
                        //         // variable to hold the total price
                        //         $total = 0;
                        //         $user = User::with(['profile.shift', 'entitlements'])->find(Auth::id());

                        //         // If there are no items in the repeater, return $total as 0
                        //         if (! $repeaters = $get('requestDates')) {
                        //             return trans_choice('field.day_with_num', $total, ['number' => $total]);
                        //         }

                        //         // If there are items in the repeater, loop through and add the sub_totals to $total
                        //         foreach ($repeaters as $repeater) {
                        //             if(!empty($repeater['hours'])){
                        //                 $total += $repeater['hours'];
                        //             }
                        //         }

                        //         $total = floatval($total / $user->profile->shift->work_hours);

                        //         return trans_choice('field.day_with_num', $total, ['number' => $total]);
                        //     }),
                    ]),
                Forms\Components\Section::make(__('field.leave_request_info'))
                    ->columnSpan(['lg' => 1])
                    ->visible(fn(Get $get): bool => $get('user_id') && $get('leave_type_id') ? true : false)
                    ->schema([
                        Forms\Components\Section::make(fn(Get $get): string => !empty($get('leave_type_id')) ? LeaveType::find($get('leave_type_id'))->name : __('model.entitlement'))                                                        
                            ->columns(4)
                            ->schema([
                                Forms\Components\Placeholder::make('balance')
                                    ->label(__('field.balance'))
                                    ->content(function (Get $get) {
                                        if(!empty($get('leave_type_id'))){                                            
                                            $user = User::find($get('user_id'));
                                            return $user->entitlements->where('is_active', true)->where('leave_type_id', $get('leave_type_id'))->first()->balance ?? 0;                                            
                                        }
                                    } ),
                                Forms\Components\Placeholder::make('accrued')
                                    ->label(__('field.accrued'))
                                    ->visible(function(Get $get){
                                        if(!empty($get('leave_type_id'))){
                                            $leaveType = LeaveType::find($get('leave_type_id'));
                                            return $leaveType->allow_accrual;
                                        }
                                        return false;
                                    })                                 
                                    ->content(function (Get $get) {
                                        if(!empty($get('leave_type_id'))){  
                                            $user = User::find($get('user_id'));                                          
                                            return  $user->entitlements->where('is_active', true)->where('leave_type_id', $get('leave_type_id'))->first()->accrued ?? 0;                                                                                                                                                         
                                        }
                                    } ),
                                Forms\Components\Placeholder::make('taken')
                                    ->label(__('field.taken'))
                                    ->content(function (Get $get) {
                                        if(!empty($get('leave_type_id'))){
                                            $user = User::find($get('user_id'));
                                            return $user->entitlements->where('is_active', true)->where('leave_type_id', $get('leave_type_id'))->first()->taken ?? 0;                                            
                                        }
                                    } ),
                                Forms\Components\Placeholder::make('remaining')
                                    ->label(__('field.remaining'))
                                    ->content(function (Get $get) {
                                        if(!empty($get('leave_type_id'))){
                                            $user = User::find($get('user_id'));
                                            return $user->entitlements->where('is_active', true)->where('leave_type_id', $get('leave_type_id'))->first()->remaining ?? 0;                                            
                                        }
                                    } ),
                                ]),
                            Forms\Components\Section::make(__('model.leave_request_rules'))
                                ->columnSpanFull()
                                ->visible(fn(Get $get): bool => LeaveRequestRule::where('leave_type_id', $get('leave_type_id'))->count() ? true : false)
                                ->schema([
                                    Forms\Components\Placeholder::make('rule')
                                        ->label(__('field.rules'))
                                        ->hiddenLabel()
                                        ->content(function (Get $get) {                                            
                                            $rules = LeaveRequestRule::where('leave_type_id', $get('leave_type_id'))->get();
                                            $str = '<div class="container mx-auto px-1 py-1"><ol class="list-decimal">';
                                            foreach($rules as $key => $rule){
                                                $key += 1;
                                                $str = $str . '<li><h4 class="font-bold">'.__('field.rule').' '.$key. ': ' .$rule->name.'</h4><p class="text-green-700">'.$rule->description.'</p></li>';
                                            }
                                            $str = $str . '</ol></div>';
                                            return new HtmlString($str);                                            
                                        } ),
                                ])
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('leaveType.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('from_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('to_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('leaverequestable_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('leaverequestable_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
