<?php

namespace App\Filament\Admin\Resources;

use App\Actions\ApprovalActions;
use App\Filament\Admin\Resources\WorkFromHomeResource\Pages;
use App\Filament\Admin\Resources\WorkFromHomeResource\RelationManagers;
use App\Models\WorkFromHome;
use App\Settings\SettingOptions;
use App\Settings\SettingWorkingHours;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Closure;
use EightyNine\Approvals\Tables\Columns\ApprovalStatusColumn;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;
use RingleSoft\LaravelProcessApproval\Events\ProcessDiscardedEvent;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval;

class WorkFromHomeResource extends Resource
{
    protected static ?string $model = WorkFromHome::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return __('model.work_from_home');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.work_from_homes');
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
                        Forms\Components\DatePicker::make('from_date')
                            ->label(__('field.from_date'))
                            ->required()
                            ->live()
                            ->native(false)
                            ->afterStateUpdated(function($state, Set $set) {
                                $toDate = $state;
                                $set('to_date', $toDate);
                                
                                $set("requestDates", []);
                                $user = Auth::user();
                                                    
                                // add date to request dates list
                                foreach(getDateRangeBetweenTwoDates($state, $toDate) as $key => $date){                                          
                                    $workDay = $user->workDays->where('day_name.value', $date->dayOfWeek())->first();
                                    if($workDay){
                                        if(!publicHoliday($date)){
                                            $set("requestDates.{$key}.date", $date->toDateString()); 
                                            $set("requestDates.{$key}.start_time", $workDay->start_time);
                                            $set("requestDates.{$key}.end_time", $workDay->end_time);
                                            $set("requestDates.{$key}.hours", getHoursBetweenTwoTimes($workDay->start_time, $workDay->end_time, $workDay->break_time));
                                        }
                                    }                                     
                                }
                            })
                            ->rules([
                                function (Get $get) {
                                    return function (string $attribute, $value, Closure $fail) use($get) {
                                        if($get('requestDates')){
                                            $user = Auth::user();
                                            // get leave request days                                    
                                            $requestDays = getRequestDays($get('requestDates'));

                                            // request days in advance
                                            $inAdvance = round(now()->diffInDays($get('from_date')), 0);
                                            
                                            if(app(SettingOptions::class)->work_from_home_rules){
                                                foreach(app(SettingOptions::class)->work_from_home_rules as $rule){                                                    
                                                    if(empty($rule['to_amount']) && $requestDays > $rule['from_amount'] && !empty($rule['day_in_advance']) && $inAdvance < $rule['day_in_advance']){
                                                        $fail(trans_choice('msg.body.in_advance', $rule['day_in_advance'], ['days' => $rule['day_in_advance']]));
                                                    }else if($requestDays >= $rule['from_amount'] && $requestDays <= $rule['to_amount'] && !empty($rule['day_in_advance']) && $inAdvance < $rule['day_in_advance']){
                                                        $fail(trans_choice('msg.body.in_advance', $rule['day_in_advance'], ['days' => $rule['day_in_advance']]));
                                                    }
                                                }
                                            }
                                        }                                                                               
                                    };
                                },
                            ]),
                        Forms\Components\DatePicker::make('to_date')
                            ->label(__('field.to_date'))
                            ->required()
                            ->live()
                            ->native(false)
                            ->afterStateUpdated(function($state, Get $get, Set $set) {
                                if($get('from_date')){
                                    $set("requestDates", []);
                                    $user = Auth::user();
                                                        
                                    // add date to request dates list
                                    foreach(getDateRangeBetweenTwoDates($get('from_date'), $state) as $key => $date){                                          
                                        $workDay = $user->workDays->where('day_name.value', $date->dayOfWeek())->first();
                                        if($workDay){
                                            if(!publicHoliday($date)){
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
                                        $set('hours', round(getHoursBetweenTwoTimes($get('start_time'), $state, app(SettingWorkingHours::class)->break_time), 1));
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
                            ->label(__('field.label.total', ['label' => __('model.work_from_home')]))
                            ->inlineLabel()
                            ->columnSpanFull()
                            ->content(function(Get $get, Set $set): string {
                                // variable to hold the total price
                                $total = getRequestDays($get('requestDates'));                           
                                return strtolower(trans_choice('field.days_with_count', $total, ['count' => $total]));
                            }),
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
                Tables\Columns\TextColumn::make('from_date')
                    ->label(__('field.from_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('to_date')
                    ->label(__('field.to_date'))
                    ->date()
                    ->sortable(),
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
                    ->relationship('user', 'name'),
                Tables\Filters\TrashedFilter::make()
                    ->visible(fn() => Auth::user()->can('restore_work::from::home')),
            ])
            ->actions(
                ApprovalActions::make(
                    [                                               
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
            'index' => Pages\ListWorkFromHomes::route('/'),
            'create' => Pages\CreateWorkFromHome::route('/create'),
            'view' => Pages\ViewWorkFromHome::route('/{record}'),
            'edit' => Pages\EditWorkFromHome::route('/{record}/edit'),
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
