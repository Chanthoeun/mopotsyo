<?php

namespace App\Filament\Admin\Resources;

use App\Actions\ApprovalActions;
use App\Filament\Admin\Resources\LeaveRequestResource\Pages;
use App\Filament\Admin\Resources\LeaveRequestResource\RelationManagers;
use App\Models\LeaveEntitlement;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestRule;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\SendLeaveRequestNotification;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;
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
                Forms\Components\Section::make(__('field.leave_request_form'))
                    ->columnSpan(['lg' => 7])
                    ->columns(2)
                    ->schema([                        
                        Forms\Components\ToggleButtons::make('leave_type_id')
                            ->label(__('model.leave_type'))
                            ->options(function() {
                                $user = Auth::user();
                                $userLeaveTypes = $user->contract->contractType->leave_types;
                                                          
                                $ids = collect();
                                foreach($userLeaveTypes as $leaveTypeId){
                                    $entitlement = $user->entitlements()->where('leave_type_id', $leaveTypeId)->where('is_active', true)->whereDate('end_date', '>=', now())->first();
                                    if($entitlement && $entitlement->remaining > 0){
                                        $ids->push($entitlement->leave_type_id);
                                    }
                                }
                                    
                                return LeaveType::whereIn('id', $userLeaveTypes)->where($user->employee->gender->value, true)->where('visible', true)->whereHas('rules')->orderBy('id', 'asc')->pluck('abbr', 'id');
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
                            })
                            ->rules([
                                function (Get $get) {
                                    return function (string $attribute, $value, Closure $fail) use($get) {
                                        if($get('requestDates')){
                                            // get leave request days                                    
                                            $requestDays = 0;
        
                                            // If there are items in the repeater, loop through and add the sub_totals to $total                                    
                                            foreach ($get('requestDates') as $repeater) {
                                                if(!empty($repeater['hours'])){
                                                    $requestDays += $repeater['hours'];
                                                }
                                            }
                                            // leave day request
                                            $requestDays = floatval($requestDays / app(SettingWorkingHours::class)->day);     
                                            
                                            
                                            // allow accruing leave days if no rule
                                            $leaveType = LeaveType::where('id', $get('leave_type_id'))->first();
                                            
                                            if($leaveType){
                                                $entitlement = Auth::user()->entitlements()->where('leave_type_id', $get('leave_type_id'))->where('is_active', true)->whereDate('end_date', '>=', now())->first();
                                                if($leaveType->balance > 0 && $leaveType->allow_accrual == true){
                                                    if($entitlement && $requestDays > $entitlement->accrued){
                                                        $fail(__('msg.body.request_over_accrued_amount', ['amount' => $entitlement->accrued]));   
                                                    }
                                                }

                                                // check balance
                                                if($leaveType->balance > 0){
                                                    if($requestDays > $entitlement->remaining){
                                                        $fail(__('msg.balance_is_not_enough'));
                                                    }
                                                }
                                            }
                                        }                                                                               
                                    };
                                },
                            ])
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('from_date')
                            ->label(__('field.from_date'))
                            ->placeholder(__('field.select_date'))
                            ->required()
                            ->native(false)
                            ->suffixIcon('fas-calendar')
                            ->live()                            
                            ->rules([
                                function (Get $get) {
                                    return function (string $attribute, $value, Closure $fail) use($get) {
                                        if($get('leave_type_id') && $get('to_date')){  
                                            if($get('requestDates')){
                                                // get leave request days                                    
                                                $requestDays = 0;
            
                                                // If there are items in the repeater, loop through and add the sub_totals to $total                                    
                                                foreach ($get('requestDates') as $repeater) {
                                                    if(!empty($repeater['hours'])){
                                                        $requestDays += $repeater['hours'];
                                                    }
                                                }
                                                // leave day request
                                                $requestDays = floatval($requestDays / app(SettingWorkingHours::class)->day);     

                                                // request days in advance
                                                $inAdvance = round(now()->diffInDays($value), 0);
            
                                                // check rule
                                                $leaveRequestRules = LeaveRequestRule::where('leave_type_id', $get('leave_type_id'))->get();
                                                foreach($leaveRequestRules as $rule){
                                                    if(empty($rule->to_amount)){
                                                        if($requestDays > $rule->from_amount && !empty($rule->day_in_advance) && $inAdvance < $rule->day_in_advance){
                                                            $fail(__('msg.body.in_advance', ['days' => $rule->day_in_advance]));
                                                        }
                                                    }else{
                                                        if($requestDays >= $rule->from_amount && $requestDays <= $rule->to_amount && !empty($rule->day_in_advance) && $inAdvance < $rule->day_in_advance){
                                                            $fail(__('msg.body.in_advance', ['days' => $rule->day_in_advance]));
                                                        }
                                                    }                                                    
                                                }

                                            }
                                        }                                                                                
                                    };
                                },
                            ]),
                        Forms\Components\DatePicker::make('to_date')
                            ->label(__('field.to_date'))
                            ->placeholder(__('field.select_date'))
                            ->required()
                            ->native(false)
                            ->suffixIcon('fas-calendar')
                            ->live()
                            ->afterStateUpdated(function($state, Get $get, Set $set, string $operation, ?Model $record){
                                $set("requestDates", []);
                                if($get('leave_type_id') && $get('from_date')){
                                    $user = Auth::user();
                                    
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
                                if($get('requestDates')){
                                    // get leave request days                                    
                                    $requestDays = 0;

                                    // If there are items in the repeater, loop through and add the sub_totals to $total                                    
                                    foreach ($get('requestDates') as $repeater) {
                                        if(!empty($repeater['hours'])){
                                            $requestDays += $repeater['hours'];
                                        }
                                    }

                                    $requestDays = floatval($requestDays / app(SettingWorkingHours::class)->day);     


                                    // check rule
                                    $leaveRequestRules = LeaveRequestRule::where('leave_type_id', $get('leave_type_id'))->where('reason', true)->get();
                                    foreach($leaveRequestRules as $rule){
                                        if($requestDays >= $rule->from_amount){
                                            return true;
                                        }
                                    }
                                    return false;
                                }
                            })
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('attachement')
                            ->label(__('field.attachment'))
                            ->required()
                            ->directory('leave-attachments')
                            ->acceptedFileTypes(['application/pdf'])
                            ->visible(function(Get $get){
                                if($get('requestDates')){
                                    // get leave request days                                    
                                    $requestDays = 0;

                                    // If there are items in the repeater, loop through and add the sub_totals to $total                                    
                                    foreach ($get('requestDates') as $repeater) {
                                        if(!empty($repeater['hours'])){
                                            $requestDays += $repeater['hours'];
                                        }
                                    }

                                    $requestDays = floatval($requestDays / app(SettingWorkingHours::class)->day);     


                                    // check rule
                                    $leaveRequestRules = LeaveRequestRule::where('leave_type_id', $get('leave_type_id'))->where('attachment', true)->get();
                                    foreach($leaveRequestRules as $rule){
                                        if($requestDays >= $rule->from_amount){
                                            return true;
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
                        Forms\Components\Placeholder::make('total')
                            ->label(__('field.label.total', ['label' => __('model.leave_request')]))
                            ->inlineLabel()
                            ->columnSpanFull()
                            ->content(function(Get $get, Set $set): string {
                                // variable to hold the total price
                                $total = 0;
                                

                                // If there are no items in the repeater, return $total as 0
                                if (! $repeaters = $get('requestDates')) {
                                    return $total;
                                }

                                // If there are items in the repeater, loop through and add the sub_totals to $total
                                foreach ($repeaters as $repeater) {
                                    if(!empty($repeater['hours'])){
                                        $total += $repeater['hours'];
                                    }
                                }

                                $total = floatval($total / app(SettingWorkingHours::class)->day);                               

                                return strtolower(trans_choice('field.days_with_count', $total, ['count' => $total]));
                            }),
                    ]),
                Forms\Components\Section::make(__('field.leave_request_info'))
                    ->columnSpan(['lg' => 5])
                    ->visible(fn(Get $get): bool => $get('leave_type_id') ? true : false)
                    ->schema([
                        Forms\Components\Section::make(fn(Get $get): string => !empty($get('leave_type_id')) ? LeaveType::find($get('leave_type_id'))->name : __('model.entitlement'))                                                        
                            ->columns(4)
                            ->schema([
                                Forms\Components\Placeholder::make('balance')
                                    ->label(__('field.balance'))
                                    ->content(function (Get $get) {
                                        if(!empty($get('leave_type_id'))){                                            
                                            return Auth::user()->entitlements->where('is_active', true)->where('leave_type_id', $get('leave_type_id'))->first()->balance ?? 0;                                            
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
                                            return  Auth::user()->entitlements()->where('is_active', true)->where('leave_type_id', $get('leave_type_id'))->whereDate('end_date', '>=', now())->first()->accrued ?? 0;                                                                                                                                                         
                                        }
                                    } ),
                                Forms\Components\Placeholder::make('taken')
                                    ->label(__('field.taken'))
                                    ->content(function (Get $get) {
                                        if(!empty($get('leave_type_id'))){
                                            return Auth::user()->entitlements->where('is_active', true)->where('leave_type_id', $get('leave_type_id'))->first()->taken ?? 0;                                            
                                        }
                                    } ),
                                Forms\Components\Placeholder::make('remaining')
                                    ->label(__('field.remaining'))
                                    ->content(function (Get $get) {
                                        if(!empty($get('leave_type_id'))){
                                            return Auth::user()->entitlements->where('is_active', true)->where('leave_type_id', $get('leave_type_id'))->first()->remaining ?? 0;                                            
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
                Tables\Columns\TextColumn::make('requested')
                    ->label(__('field.created_by'))
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
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions(
                ApprovalActions::make(
                    [                        
                        Tables\Actions\ViewAction::make(),                        
                        Tables\Actions\Action::make('discard') 
                            ->label(__('filament-approvals::approvals.actions.discard'))                           
                            ->visible(fn (Model $record) => (Auth::id() == $record->approvalStatus->creator->id && $record->isApprovalCompleted() && $record->isApproved()))                                                      
                            ->hidden(fn(Model $record) => (Auth::id() != $record->approvalStatus->creator->id || $record->isDiscarded()))                            
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
                                ProcessApproval::query()->create([
                                    'approvable_type' => $record::getApprovableType(),
                                    'approvable_id' => $record->id,
                                    'process_approval_flow_step_id' => null,
                                    'approval_action' => ApprovalStatusEnum::DISCARDED,
                                    'comment' => $data['reason'],
                                    'user_id' => Auth::id(),
                                    'approver_name' => Auth::user()->full_name1
                                ]);

                                // notification
                                Notification::make()
                                    ->success()
                                    ->icon('fas-user-clock')
                                    ->iconColor('success')
                                    ->title(__('msg.label.discarded', ['label' => __('model.leave_request')]))
                                    ->send();

                                // notification to approver
                                foreach($record->approvers->pluck('email')->unique() as $approver){
                                    $sender = User::where('email', $approver)->first();
                                    $message = collect([
                                        'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.leave_request')])]),
                                        'greeting' => __('mail.greeting', ['name' => $sender->name]),
                                        'body' => __('msg.body.discarded', [
                                            'request'  => strtolower(__('model.leave_request')), 
                                            'days'  => strtolower(trans_choice('field.days_with_count', $record->days, ['count' => $record->days])),
                                            'leave_type' => strtolower($record->leaveType->name),
                                            'from'  => $record->from_date->toDateString(), 
                                            'to' => $record->to_date->toDateString(),
                                            'name'  => Auth::user()->full_name
                                        ]),
                                        'action'    => [
                                            'name'  => __('btn.view'),
                                            'url'   => LeaveRequestResource::getUrl('view', ['record' => $record])
                                        ]
                                    ]);

                                    // send noti
                                    Notification::make()
                                        ->success()
                                        ->icon('fas-user-clock')
                                        ->iconColor('success')
                                        ->title($message['subject'])
                                        ->body($message['body'])
                                        ->actions([               
                                            ActionsAction::make('view')
                                                ->label($message['action']['name'])
                                                ->button()
                                                ->url($message['action']['url']) 
                                                ->icon('fas-eye')                   
                                                ->markAsRead(),
                                        ])
                                        ->sendToDatabase($sender);
                                    
                                    $sender->notify(new SendLeaveRequestNotification($message, $data['reason']));
                                }
                            }),
                    ]
                )
            )
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
