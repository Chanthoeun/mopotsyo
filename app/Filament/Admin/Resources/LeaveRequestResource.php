<?php

namespace App\Filament\Admin\Resources;

use App\Actions\ApprovalActions;
use App\Filament\Admin\Resources\LeaveRequestResource\Pages;
use App\Filament\Admin\Resources\LeaveRequestResource\RelationManagers;
use App\Models\LeaveEntitlement;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestRule;
use App\Models\LeaveType;
use App\Models\OverTime;
use App\Models\User;
use App\Notifications\SendLeaveRequestNotification;
use App\Settings\SettingOptions;
use App\Settings\SettingWorkingHours;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Closure;
use EightyNine\Approvals\Tables\Actions\RejectAction;
use EightyNine\Approvals\Tables\Columns\ApprovalStatusColumn;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Actions\Action as ActionsAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;
use RingleSoft\LaravelProcessApproval\Events\ProcessDiscardedEvent;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval;

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
            ->columns(12)
            ->schema([
                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 7])                    
                    ->schema([                        
                        Forms\Components\Section::make()
                            ->columns(2)
                            ->schema([
                                Forms\Components\ToggleButtons::make('leave_type_id')
                                    ->label(__('model.leave_type'))
                                    ->options(function(string $operation, ?Model $record) {
                                        if($operation == 'view'){
                                            $user = $record->user;
                                        }else{
                                            $user = Auth::user(); 
                                        }
                                              
                                        return LeaveType::whereIn('id', $user->contract->contractType->leave_types)->where($user->employee->gender->value, true)->orderBy('id', 'asc')->pluck('abbr', 'id');
                                    })
                                    ->required()                                    
                                    ->inline()
                                    ->grouped()
                                    ->hint(function($state) {
                                        if($state){
                                            return LeaveType::find($state)->name;
                                        }
                                    })                            
                                    ->live()
                                    ->afterStateUpdated(function(Set $set){
                                        $set('from_date', null);
                                        $set('to_date', null);
                                        $set('requestDates', []);
                                        $set('overTimes', []);
                                    })
                                    ->rules([
                                        function (Get $get, string $operation, ?Model $record) {
                                            return function (string $attribute, $value, Closure $fail) use($get, $operation, $record) {
                                                if($operation == 'view'){
                                                    $user = $record->user;
                                                }else{
                                                    $user = Auth::user();
                                                }
                                                
                                                // check request date and time
                                                $requestDateTime = now();
                                               
                                                // get leave request days                                    
                                                $requestDays = getRequestDays($get('requestDates'));
                                                if($requestDays == 0){
                                                    $fail(__('msg.body.request_days_is_zero'));
                                                }

                                                // request days in advance
                                                $inAdvance = round(now()->diffInDays($get('from_date')), 0);
                                                // allow accruing leave days if no rule
                                                $leaveType = LeaveType::where('id', $value)->first();
                                                
                                                // check if request back date
                                                if(isRequestBackDate($get('to_date')) == true){
                                                    // check balance
                                                    $entitlement = $user->entitlements()->where('leave_type_id', $leaveType->id)->where('is_active', true)->whereDate('end_date', '>=', now())->first();
                                                    if($entitlement && ($entitlement->remaining == 0 || $requestDays > $entitlement->remaining)){
                                                        $fail(__('msg.balance_is_not_enough'));
                                                    }else if($entitlement && (array_key_exists('allow_accrual', $leaveType->option) && $leaveType->option['allow_accrual'] == true && $requestDays > $entitlement->accrued)){
                                                        $fail(__('msg.body.request_over_accrued_amount', ['amount' => $entitlement->accrued]));
                                                    }else{                                                            
                                                        // use overtime
                                                        if(app(SettingOptions::class)->allow_overtime == true && app(SettingOptions::class)->overtime_link == $leaveType->id && getOvertimeDays($user, $get('overTimes')) < $requestDays){                                                                
                                                            $fail(__('msg.balance_is_not_enough'));
                                                        }
                                                    }
                                                }else if($requestDays){
                                                    if($leaveType){
                                                        // TODO: check rule
                                                        if($leaveType->rules){
                                                            foreach($leaveType->rules as $rule){   
                                                                // empty($rule['to_amount']) && $requestDays > $rule['from_amount'] && !empty($rule['day_in_advance']) && $inAdvance < floatval($rule['day_in_advance'] - 0.9)      
                                                                if($requestDays <= 1 && $requestDateTime->isBefore($get('from_date')) && $requestDateTime->isAfter(date('Y-m-d').' 15:00:00')) {
                                                                    $fail(trans_choice('msg.body.in_advance', $rule['day_in_advance'], ['days' => $rule['day_in_advance']]));
                                                                }else if($requestDays >= $rule['from_amount'] && $requestDays <= $rule['to_amount'] && !empty($rule['day_in_advance']) && $inAdvance < floatval($rule['day_in_advance'] - 0.9)){
                                                                    $fail(trans_choice('msg.body.in_advance', $rule['day_in_advance'], ['days' => $rule['day_in_advance']]));
                                                                }
                                                            }
                                                        }
                                                                                                                
                                                        // check balance
                                                        $entitlement = $user->entitlements()->where('leave_type_id', $leaveType->id)->where('is_active', true)->whereDate('end_date', '>=', now())->first();
                                                        if($entitlement && ($entitlement->remaining == 0 || $requestDays > $entitlement->remaining)){
                                                            $fail(__('msg.balance_is_not_enough'));
                                                        }else if($entitlement && (array_key_exists('allow_accrual', $leaveType->option) && $leaveType->option['allow_accrual'] == true && $requestDays > $entitlement->accrued)){
                                                            $fail(__('msg.body.request_over_accrued_amount', ['amount' => $entitlement->accrued]));
                                                        }else{                                                            
                                                            // use overtime
                                                            if(app(SettingOptions::class)->allow_overtime == true && app(SettingOptions::class)->overtime_link == $leaveType->id && getOvertimeDays($user, $get('overTimes')) < $requestDays){                                                                
                                                                $fail(__('msg.balance_is_not_enough'));
                                                            }
                                                        }
                                                    }
                                                }                                                                               
                                            };
                                        },
                                    ])
                                    ->columnSpanFull(),
                                Forms\Components\CheckboxList::make('overTimes')
                                    ->label(__('model.overtimes'))
                                    ->relationship(titleAttribute: 'id', modifyQueryUsing: function (Builder $query, $operation, ?Model $record) {
                                        if($operation == 'create'){
                                            return $query->where('user_id', Auth::id())->whereDate('expiry_date', '>=', now())->where('unused', true);
                                        }else{
                                            return $query->where('user_id', $record->user_id)->whereDate('expiry_date', '>=', now())->where('unused', true);
                                        }
                                    })
                                    ->getOptionLabelFromRecordUsing(function (Model $record) {
                                        $dates = array();
                                        foreach($record->requestDates as $item){
                                            $dates[] = $item->date;
                                        }
                                        return strtolower(implode(',', $dates). ' - ' . trans_choice('field.hours_with_count', $record->hours, ['count' => floatval($record->hours)]). ' - expire on: '. $record->expiry_date->toDateString());
                                    })
                                    ->live()
                                    ->visible(function(Get $get) {
                                        if(app(SettingOptions::class)->allow_overtime == true && app(SettingOptions::class)->overtime_link == $get('leave_type_id')){
                                            return true;
                                        }
                                        return false;
                                    })                                    
                                    ->columnSpanFull(),
                                Forms\Components\DatePicker::make('from_date')
                                    ->label(__('field.from_date'))
                                    ->placeholder(__('field.select_date'))
                                    ->required()
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->hint(new HtmlString(Blade::render('<x-filament::loading-indicator class="h-5 w-5" wire:loading wire:target="data.from_date" />')))
                                    ->suffixIcon('fas-calendar')
                                    ->live()
                                    ->afterStateUpdated(function($state, Get $get, Set $set, string $operation, ?Model $record){
                                        $set("to_date", $state);
                                        $set("requestDates", []);
                                        if($get('leave_type_id') && $get('to_date')){
                                            if($operation == 'view'){
                                                $user = $record->user;
                                            }else{
                                                $user = Auth::user();
                                            }
                                            // add date to request dates list
                                            foreach(getDateRangeBetweenTwoDates($state, $get('to_date')) as $key => $date){                                          
                                                $workDay = $user->workDays->where('day_name.value', $date->dayOfWeek())->first();
                                                if($workDay){
                                                    if(dateIsNotDuplicated($user, $date) && !publicHoliday($date)){
                                                        $set("requestDates.{$key}.date", $date->toDateString()); 
                                                        $set("requestDates.{$key}.start_time", $workDay->start_time);
                                                        $set("requestDates.{$key}.end_time", $workDay->end_time);
                                                        $set("requestDates.{$key}.hours", getHoursBetweenTwoTimes($workDay->start_time, $workDay->end_time, $workDay->break_time));
                                                    }
                                                }                                     
                                            }
                                        }
                                    }),                            
                                Forms\Components\DatePicker::make('to_date')
                                    ->label(__('field.to_date'))
                                    ->placeholder(__('field.select_date'))
                                    ->required()
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->hint(new HtmlString(Blade::render('<x-filament::loading-indicator class="h-5 w-5" wire:loading wire:target="data.to_date" />')))
                                    ->suffixIcon('fas-calendar')
                                    ->live()
                                    ->afterStateUpdated(function($state, Get $get, Set $set, string $operation, ?Model $record){
                                        $set("requestDates", []);
                                        if($get('leave_type_id') && $get('from_date')){
                                            if($operation == 'view'){
                                                $user = $record->user;
                                            }else{
                                                $user = Auth::user();
                                            }
                                            
                                            // add date to request dates list
                                            foreach(getDateRangeBetweenTwoDates($get('from_date'), $state) as $key => $date){                                          
                                                $workDay = $user->workDays->where('day_name.value', $date->dayOfWeek())->first();
                                                if($workDay){
                                                    if(dateIsNotDuplicated($user, $date) && !publicHoliday($date)){
                                                        $set("requestDates.{$key}.date", $date->toDateString()); 
                                                        $set("requestDates.{$key}.start_time", $workDay->start_time);
                                                        $set("requestDates.{$key}.end_time", $workDay->end_time);
                                                        $set("requestDates.{$key}.hours", getHoursBetweenTwoTimes($workDay->start_time, $workDay->end_time, $workDay->break_time));
                                                    }
                                                }                                     
                                            }
                                        }
                                    }),
                                Forms\Components\Textarea::make('reason')
                                    ->label(__('field.reason'))
                                    ->required()
                                    ->visible(function(Get $get){
                                        if(isRequestBackDate($get('to_date'))){
                                            return true;
                                        }else if($get('requestDates') && $get('leave_type_id')){
                                            // get leave request days                                    
                                            $requestDays = getRequestDays($get('requestDates'));
                                            $leaveType = LeaveType::find($get('leave_type_id'));

                                            // check rule     
                                            if($leaveType->rules){
                                                foreach($leaveType->rules as $rule){
                                                    if($requestDays >= $rule['from_amount'] && $rule['reason'] == true){
                                                        return true;
                                                    }
                                                }
                                            }                                       
                                            
                                            return false;
                                        }
                                    })
                                    ->columnSpanFull(),
                                Forms\Components\FileUpload::make('attachment')
                                    ->label(__('field.attachment'))
                                    ->required()
                                    ->directory('leave-attachments')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg' ,'application/pdf'])
                                    ->visible(function(Get $get){
                                        if(isRequestBackDate($get('to_date'))){
                                            return true;
                                        }elseif($get('requestDates') && $get('leave_type_id')){
                                            // get leave request days                                    
                                            $requestDays = getRequestDays($get('requestDates'));
                                            $leaveType = LeaveType::find($get('leave_type_id'));

                                            // check rule  
                                            if($leaveType->rules){
                                                foreach($leaveType->rules as $rule){
                                                    if($requestDays >= $rule['from_amount'] && $rule['attachment'] == true){
                                                        return true;
                                                    }
                                                }
                                            }                                          
                                            
                                            return false;
                                        }
                                    })
                                    ->columnSpanFull(),
                                TableRepeater::make('requestDates')
                                    ->label(__('field.request_dates'))
                                    ->relationship()
                                    ->required()                                                                        
                                    ->addable(false)  
                                    ->deletable(false)
                                    ->defaultItems(0)   
                                    ->live()                           
                                    ->columnSpanFull()
                                    ->headers([
                                        Header::make(__('field.date'))->width('150px'),
                                        Header::make(__('field.start_time'))->width('140px'),
                                        Header::make(__('field.end_time'))->width('140px'),
                                        Header::make(__('field.hours'))->width('50px'),
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
                                            ->live()
                                            ->afterStateUpdated(function($state, Get $get, Set $set){                                                
                                                $set('hours', round(getHoursBetweenTwoTimes($state, $get('end_time'), app(SettingWorkingHours::class)->break_time), 1));
                                            })
                                            ->default('08:00:00'),
                                        Forms\Components\TimePicker::make('end_time')
                                            ->hiddenLabel()                                            
                                            ->required()
                                            ->seconds(false)
                                            ->live()
                                            ->afterStateUpdated(function($state, Get $get, Set $set){                                                
                                                $set('hours', round(getHoursBetweenTwoTimes($get('start_time'), $state, app(SettingWorkingHours::class)->break_time), 1));
                                            })
                                            ->default('17:00:00'),
                                        Forms\Components\TextInput::make('hours')
                                            ->hiddenLabel()                                            
                                            ->required()
                                            ->readOnly()
                                            ->default(0),
                                    ]),                                 
                                Forms\Components\Placeholder::make('total')
                                    ->label(__('field.label.total', ['label' => __('model.leave_request')]))
                                    ->inlineLabel()
                                    ->columnSpanFull()
                                    ->content(function(Get $get, Set $set): string {
                                        // variable to hold the total price
                                        $total = getRequestDays($get('requestDates'));                           
                                        return strtolower(trans_choice('field.days_with_count', $total, ['count' => $total]));
                                    }),
                            ])
                    ]),
                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 5])
                    ->schema([
                        Forms\Components\Section::make(fn(Get $get): string => !empty($get('leave_type_id')) ? LeaveType::find($get('leave_type_id'))->name : __('field.balance'))                                                        
                            ->columns(4)
                            ->visible(function(Get $get, string $operation, ?Model $record): bool {
                                if($operation == 'view'){
                                    $user = $record->user;
                                }else{
                                    $user = Auth::user();
                                }
                                $entitlement = $user->entitlements()->where('leave_type_id', $get('leave_type_id'))->where('is_active', true)->whereDate('end_date', '>=', now())->first();
                                return empty($entitlement) ? false : true;
                            })
                            ->schema([
                                Forms\Components\Placeholder::make('balance')
                                    ->label(__('field.balance'))
                                    ->content(function (Get $get, string $operation, ?Model $record) {
                                        if($operation == 'view'){
                                            $user = $record->user;
                                        }else{  
                                            $user = Auth::user();
                                        }
                                        if(!empty($get('leave_type_id'))){                                            
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
                                    ->content(function (Get $get, string $operation, ?Model $record) {
                                        if($operation == 'view'){
                                            $user = $record->user;
                                        }else{
                                            $user = Auth::user();
                                        }
                                        if(!empty($get('leave_type_id'))){                                       
                                            return  $user->entitlements()->where('is_active', true)->where('leave_type_id', $get('leave_type_id'))->whereDate('end_date', '>=', now())->first()->accrued ?? 0;                                                                                                                                                         
                                        }
                                    } ),
                                Forms\Components\Placeholder::make('taken')
                                    ->label(__('field.taken'))
                                    ->content(function (Get $get, string $operation, ?Model $record) {
                                        if($operation == 'view'){
                                            $user = $record->user;
                                        }else{
                                            $user = Auth::user();
                                        }
                                        if(!empty($get('leave_type_id'))){
                                            return $user->entitlements->where('is_active', true)->where('leave_type_id', $get('leave_type_id'))->first()->taken ?? 0;                                            
                                        }
                                    } ),
                                Forms\Components\Placeholder::make('remaining')
                                    ->label(__('field.remaining'))
                                    ->content(function (Get $get, string $operation, ?Model $record) {
                                        if($operation == 'view'){
                                            $user = $record->user;
                                        }else{
                                            $user = Auth::user();
                                        }
                                        if(!empty($get('leave_type_id'))){
                                            return $user->entitlements->where('is_active', true)->where('leave_type_id', $get('leave_type_id'))->first()->remaining ?? 0;                                            
                                        }
                                    } ),
                                ]),
                        Forms\Components\Section::make(__('field.holiday_duplicated_date'))
                            ->columnSpanFull()
                            ->visible(function(Get $get, string $operation, ?Model $record) {
                                if($get('from_date') && $get('to_date')){
                                    if($operation == 'view'){
                                        $user = $record->user;
                                    }else{
                                        $user = Auth::user();
                                    }
                                    
                                    foreach(getDateRangeBetweenTwoDates($get('from_date'), $get('to_date')) as $key => $date){                                          
                                        $workDay = $user->workDays->where('day_name.value', $date->dayOfWeek())->first();
                                        if($workDay){
                                            if(!dateIsNotDuplicated($user, $date) || publicHoliday($date)){
                                                return true;
                                            }
                                        }                                     
                                    }
                                }
                                
                                return false;
                            })
                            ->schema([
                                Forms\Components\Placeholder::make('dupliatedDate')
                                    ->hiddenLabel()
                                    ->content(function (Get $get, string $operation, ?Model $record) {
                                        if($operation == 'view'){
                                            $user = $record->user;
                                        }else{
                                            $user = Auth::user();
                                        }   
                                        $str = '<div class="container mx-auto px-1 py-1"><ul class="list-decimal">';
                                        foreach(getDateRangeBetweenTwoDates($get('from_date'), $get('to_date')) as $key => $date){                                          
                                            $workDay = $user->workDays->where('day_name.value', $date->dayOfWeek())->first();
                                            $publicHoliday = publicHoliday($date);
                                            $duplicatedLeave = getLeaveDuplicatedDate($user, $date);
                                            if($workDay){
                                                if($duplicatedLeave){
                                                    $str = $str . '<li>'.$duplicatedLeave->date->toDateString() .': '. $duplicatedLeave->requestdateable->leaveType->name .'</li>';
                                                }else if($publicHoliday){
                                                    $str = $str . '<li>'.$publicHoliday->date->toDateString() .': '. $publicHoliday->name .'</li>';
                                                }
                                                
                                            }                                     
                                        }
                                        $str = $str . '</ul></div>';
                                        return new HtmlString($str);                                            
                                    } ),
                            ]),
                        Forms\Components\Section::make(__('model.leave_request_rules'))
                            ->columnSpanFull()
                            ->collapsed()
                            ->visible(function(Get $get): bool {
                                $leaveType = LeaveType::find($get('leave_type_id'));
                                return empty($leaveType->rules) ? false : true;
                            })
                            ->schema([
                                Forms\Components\Placeholder::make('rule')
                                    ->label(__('field.rules'))
                                    ->hiddenLabel()
                                    ->content(function (Get $get) {                                            
                                        $rules = LeaveType::find($get('leave_type_id'))->rules;
                                        $str = '<div class="container mx-auto px-1 py-1"><ol class="list-decimal">';
                                        foreach($rules as $key => $rule){
                                            $key += 1;
                                            $str = $str . '<li><h4 class="font-bold">'.__('field.rule').' '.$key. ': ' .$rule['name'].'</h4><p class="text-green-700">'.$rule['description'].'</p></li>';
                                        }
                                        $str = $str . '</ol></div>';
                                        return new HtmlString($str);                                            
                                    } ),
                            ])
                    ])
            
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('requested')
                    ->label(__('field.requested_by'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('leaveType.name')
                    ->label(__('model.leave_type'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('from_date')
                    ->label(__('field.from_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('to_date')
                    ->label(__('field.to_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('days')
                    ->label(__('field.requested_days'))                     
                    ->formatStateUsing(fn($state) => trans_choice('field.days_with_count', $state, ['count' => $state]))
                    ->alignCenter(),
                Tables\Columns\IconColumn::make('back_date')
                    ->label(__('field.is_back_date'))
                    ->boolean()
                    ->alignCenter(),
                ApprovalStatusColumn::make("approvalStatus.status")
                    ->label(__('field.status')),                
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
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('requested_by')
                    ->label(__('field.requested_by'))
                    ->relationship('user', 'name')
                    ->preload()
                    ->searchable()
                    ->default(Auth::Id()),
                Tables\Filters\SelectFilter::make('leave_type_id')
                    ->label(__('model.leave_type'))
                    ->relationship('leaveType', 'name')
                    ->preload()
                    ->searchable(),
                Tables\Filters\TrashedFilter::make()->visible(fn() => Auth::user()->hasRole('super_admin'))
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions(
                ApprovalActions::make(
                    [                                               
                        Tables\Actions\Action::make('discard') 
                            ->label(__('filament-approvals::approvals.actions.discard'))                           
                            ->visible(fn (Model $record) => (Auth::id() == $record->approvalStatus->creator->id && $record->isApprovalCompleted() && $record->isApproved()))                                                      
                            ->hidden(fn(Model $record) => (Auth::id() != $record->approvalStatus->creator->id || $record->isDiscarded() || $record->isRejected() || $record->to_date < now()))                            
                            ->form([
                                Textarea::make('reason')
                                    ->label(__('field.reason'))
                                    ->required()
                            ])
                            ->icon('heroicon-m-archive-box-x-mark')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->modalIcon('heroicon-m-archive-box-x-mark')
                            ->action(function (array $data, Model $record) {
                                // update status  
                                $record->approvalStatus()->update(['status' => ApprovalStatusEnum::DISCARDED->value]);

                                // update approval status
                                $approval = ProcessApproval::query()->create([
                                    'approvable_type' => $record::getApprovableType(),
                                    'approvable_id' => $record->id,
                                    'process_approval_flow_step_id' => null,
                                    'approval_action' => ApprovalStatusEnum::DISCARDED,
                                    'comment' => $data['reason'],
                                    'user_id' => Auth::id(),
                                    'approver_name' => Auth::user()->full_name1
                                ]);

                                ProcessDiscardedEvent::dispatch($approval);

                                // notification
                                Notification::make()
                                    ->success()
                                    ->icon('fas-user-clock')
                                    ->iconColor('success')
                                    ->title(__('msg.label.discarded', ['label' => __('model.leave_request')]))
                                    ->send();
                            }),
                    ],
                    [             
                        Tables\Actions\ActionGroup::make([
                            Tables\Actions\EditAction::make(),
                            Tables\Actions\DeleteAction::make(),
                            Tables\Actions\RestoreAction::make(),
                        ])                                       
                    ]
                )
            );
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
            'view' => Pages\ViewLeaveRequest::route('/{record}'),
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
