<?php

namespace App\Filament\Admin\Resources;

use App\Actions\ApprovalActions;
use App\Filament\Admin\Resources\OverTimeResource\Pages;
use App\Filament\Admin\Resources\OverTimeResource\RelationManagers;
use App\Models\OverTime;
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
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;
use RingleSoft\LaravelProcessApproval\Events\ProcessDiscardedEvent;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval;

class OverTimeResource extends Resource
{
    protected static ?string $model = OverTime::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('model.overtime');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.overtimes');
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
                    ->schema([
                        TableRepeater::make('requestDates')
                            ->label(__('field.working_dates'))
                            ->relationship()
                            ->required()       
                            ->addActionLabel(__('btn.label.add', ['label' => __('field.date')]))                                                                 
                            ->defaultItems(1)   
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
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->hint(new HtmlString(Blade::render('<x-filament::loading-indicator class="h-5 w-5" wire:loading wire:target="data.date" />')))
                                    ->live()
                                    ->rules([
                                        function (Get $get) {
                                            return function (string $attribute, $value, Closure $fail) use($get) {
                                                if($get('start_time') && $get('end_time')){
                                                    if(isWorkHour(Auth::user(), $value, $get('start_time')) == true){
                                                        $fail(__('msg.body.is_working_hour'));   
                                                    }
                                                }                                                                               
                                            };
                                        },
                                    ]),                              
                                Forms\Components\TimePicker::make('start_time')
                                    ->hiddenLabel()                                            
                                    ->required()
                                    ->seconds(false)
                                    ->live()
                                    ->afterStateUpdated(function($state, Get $get, Set $set){   
                                        $set('hours', round(getHoursBetweenTwoTimes($state, $get('end_time'), app(SettingWorkingHours::class)->break_time), 1));                                            
                                    }),
                                Forms\Components\TimePicker::make('end_time')
                                    ->hiddenLabel()                                            
                                    ->required()
                                    ->seconds(false)
                                    ->live()
                                    ->afterStateUpdated(function($state, Get $get, Set $set){    
                                        $set('hours', round(getHoursBetweenTwoTimes($get('start_time'), $state, app(SettingWorkingHours::class)->break_time), 1));                                        
                                    }),
                                Forms\Components\TextInput::make('hours')
                                    ->hiddenLabel()                                            
                                    ->required()
                                    ->readOnly()
                                    ->numeric()
                                    ->default(0.00)
                                    ->rules([
                                        function (Get $get) {
                                            return function (string $attribute, $value, Closure $fail) use($get) {
                                                if($value <= 0){
                                                    $fail(__('msg.body.is_not_correct'));
                                                }                                                                               
                                            };
                                        },
                                    ]),
                            ]),
                        Forms\Components\Textarea::make('reason')
                            ->label(__('field.reason'))
                            ->required()
                            ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('requestDates.date')
                    ->label(__('field.date'))
                    ->date()
                    ->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('requestDates.start_time')
                    ->label(__('field.start_time'))
                    ->time('h:i A')
                    ->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('requestDates.end_time')
                    ->label(__('field.end_time'))
                    ->time('h:i A')
                    ->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('hours')
                    ->label(__('field.hours'))
                    ->numeric()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label(__('field.expiry_date'))
                    ->date()
                    ->badge()
                    ->color(fn($state) => $state < now() ? 'danger' : 'success')
                    ->alignCenter(),
                Tables\Columns\IconColumn::make('unused')
                    ->label(__('field.unused'))
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
                    ->relationship('user', 'name'),
                Tables\Filters\TrashedFilter::make()
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
                                    ->title(__('msg.label.discarded', ['label' => __('model.overtime')]))
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
            'index' => Pages\ListOverTimes::route('/'),
            'create' => Pages\CreateOverTime::route('/create'),
            'view' => Pages\ViewOverTime::route('/{record}'),
            'edit' => Pages\EditOverTime::route('/{record}/edit'),
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
