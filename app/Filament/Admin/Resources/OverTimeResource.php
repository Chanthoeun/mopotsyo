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
                                            return function (string $attribute, $value, Closure $fail) use ($get) {
                                                if ($get('start_time') && $get('end_time')) {
                                                    if (isWorkHour(Auth::user(), $value, $get('start_time')) == true) {
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
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $set('hours', getHoursBetweenTwoTimes($state, $get('end_time'), app(SettingWorkingHours::class)->break_time));
                                    }),
                                Forms\Components\TimePicker::make('end_time')
                                    ->hiddenLabel()
                                    ->required()
                                    ->seconds(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $set('hours', getHoursBetweenTwoTimes($get('start_time'), $state, app(SettingWorkingHours::class)->break_time));
                                    }),
                                Forms\Components\TextInput::make('hours')
                                    ->hiddenLabel()
                                    ->required()
                                    ->readOnly()
                                    ->numeric()
                                    ->default(0.00)
                                    ->rules([
                                        function (Get $get) {
                                            return function (string $attribute, $value, Closure $fail) use ($get) {
                                                if ($value <= 0) {
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
                Tables\Columns\TextColumn::make('current_approver_name')
                    ->label(__('field.current_approver'))
                    ->color('primary')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('field.status'))
                    ->badge()
                    ->action(
                        Tables\Actions\Action::make('view_history')
                            ->label('View History')
                            ->modalHeading('Approval History')
                            ->modalContent(fn(OverTime $record) => view('filament.admin.partials.approval-history', ['record' => $record]))
                            ->modalSubmitAction(false)
                            ->modalCancelAction(fn($action) => $action->label('Close'))
                    ),
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
                Tables\Filters\TrashedFilter::make()->visible(fn() => Auth::user()->hasRole('super_admin')),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('field.status'))
                    ->options(\App\Enums\Status::class),
            ])
            ->actions([
                Tables\Actions\Action::make('submit')
                    ->label(__('btn.submit'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function (OverTime $record) {
                        if ($record->submitToApproval()) {
                            Notification::make()
                                ->title(__('msg.body.submitted', ['label' => __('model.overtime')]))
                                ->success()
                                ->send();
                        }
                    })
                    ->visible(fn(OverTime $record) => $record->status === \App\Enums\Status::CREATED),
                ApprovalActions::discard(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn(OverTime $record) => $record->user_id == Auth::id() && $record->status === \App\Enums\Status::CREATED),
                ...ApprovalActions::approverActions(),
                Tables\Actions\Action::make('force_approve')
                    ->label('Force Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to FORCE approve this request? This will mark all steps as approved and send the completion email immediately.')
                    ->action(function (OverTime $record) {
                        $record->update(['status' => \App\Enums\Status::APPROVED]);
                        $record->approvalSteps()->update(['status' => \App\Enums\Status::APPROVED]);

                        // Dispatch event to send email
                        \App\Events\ApprovalProcessed::dispatch($record, 'approved', 'Force Approved by ' . Auth::user()->name, Auth::user());

                        Notification::make()
                            ->title('Request Force Approved')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(OverTime $record) => Auth::user()->hasRole(['acting_director']) && empty($record->currentApprovalStep()?->approver_id) && $record->status !== \App\Enums\Status::APPROVED),
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
            'index' => Pages\ListOverTimes::route('/'),
            'create' => Pages\CreateOverTime::route('/create'),
            'view' => Pages\ViewOverTime::route('/{record}'),
            'edit' => Pages\EditOverTime::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['user.employee.contracts.supervisor', 'requestDates', 'approvalSteps.approver']);

        if (!Auth::user()->hasRole(['super_admin', 'human_resource'])) {
            $query->where(function (Builder $query) {
                $query->where('user_id', Auth::id())
                    ->orWhereHas('approvalSteps', fn($q) => $q->where('approver_id', Auth::id()));
            });
        }

        return $query;
    }
}
