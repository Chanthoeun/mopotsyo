<?php

namespace App\Filament\Admin\Resources;

use App\Actions\ApprovalActions;
use App\Filament\Admin\Resources\SwitchWorkDayResource\Pages;
use App\Filament\Admin\Resources\SwitchWorkDayResource\RelationManagers;
use App\Models\SwitchWorkDay;
use App\Settings\SettingOptions;
use Carbon\Carbon;
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

class SwitchWorkDayResource extends Resource
{
    protected static ?string $model = SwitchWorkDay::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('model.switch_work_day');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.switch_work_days');
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
                            ->label(__('field.work_date'))
                            ->required()
                            ->native(false)
                            ->closeOnDateSelection()
                            ->hint(new HtmlString(Blade::render('<x-filament::loading-indicator class="h-5 w-5" wire:loading wire:target="data.from_date" />')))
                            ->suffixIcon('fas-calendar')
                            ->live()
                            ->afterStateUpdated(fn($state, Set $set) => $set('to_date', $state))
                            ->rules([
                                function (Get $get) {
                                    return function (string $attribute, $value, Closure $fail) use ($get) {
                                        // Not allow to select past date                                        
                                        if ($value < now()->toDateString()) {
                                            $fail(__('msg.body.not_allow_past_date'));
                                        }

                                        // Not allow to select work day after to_date
                                        // if($get('to_date') && $value > $get('to_date')){
                                        //     $fail(__('msg.body.select_before', ['option1' => __('field.work_date'), 'option2' => __('field.to_date')]));
                                        // }
                        
                                        // Not allow to select work day that is not  your work day                                         
                                        if (empty(Auth::user()->workDays->where('day_name.value', Carbon::parse($value)->dayOfWeek())->first())) {
                                            $fail(__('msg.body.working_day'));
                                        }

                                        // Not allow to select work day that is public holiday
                                        if (publicHoliday($value)) {
                                            $fail(__('msg.body.is_public_holiday'));
                                        }
                                    };
                                },
                            ]),
                        Forms\Components\DatePicker::make('to_date')
                            ->label(__('field.to_date'))
                            ->required()
                            ->native(false)
                            ->closeOnDateSelection()
                            ->hint(new HtmlString(Blade::render('<x-filament::loading-indicator class="h-5 w-5" wire:loading wire:target="data.to_date" />')))
                            ->suffixIcon('fas-calendar')
                            ->rules([
                                function (Get $get) {
                                    return function (string $attribute, $value, Closure $fail) use ($get) {
                                        // Not allow to select past date                                        
                                        if ($value < now()->subDay()) {
                                            $fail(__('msg.body.not_allow_past_date'));
                                        }

                                        // Not allow to select work day after to_date
                                        // if($value < $get('from_date')){
                                        //     $fail(__('msg.body.select_after', ['option1' => __('field.to_date'), 'option2' => __('field.work_date')]));
                                        // }
                        
                                        // Not allow to select work day that is not  your work day                                         
                                        if (Auth::user()->workDays->where('day_name.value', Carbon::parse($value)->dayOfWeek())->first()) {
                                            $fail(__('msg.body.not_working_day'));
                                        }


                                        // Not allow to select work day that is public holiday
                                        if (publicHoliday($value)) {
                                            $fail(__('msg.body.is_public_holiday'));
                                        }
                                    };
                                },
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
                Tables\Columns\TextColumn::make('from_date')
                    ->label(__('field.work_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('to_date')
                    ->label(__('field.to_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_approver_name')
                    ->label(__('field.current_approver'))
                    ->color('primary')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('field.status'))
                    ->badge(),
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
            ->actions(
                array_merge(
                    [
                        Tables\Actions\Action::make('submit')
                            ->label(__('btn.submit'))
                            ->icon('heroicon-o-paper-airplane')
                            ->color('primary')
                            ->requiresConfirmation()
                            ->action(function (SwitchWorkDay $record) {
                                $record->submitToApproval();
                                Notification::make()
                                    ->title(__('msg.body.submitted', ['label' => __('model.switch_work_day')]))
                                    ->success()
                                    ->send();
                            })
                            ->visible(fn(SwitchWorkDay $record) => $record->status === \App\Enums\Status::CREATED),
                    ],
                    ApprovalActions::make(
                        [
                            Tables\Actions\ActionGroup::make([
                                Tables\Actions\ViewAction::make(),
                                Tables\Actions\EditAction::make(),
                            ])
                        ]
                    )
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
            'index' => Pages\ListSwitchWorkDays::route('/'),
            'create' => Pages\CreateSwitchWorkDay::route('/create'),
            'view' => Pages\ViewSwitchWorkDay::route('/{record}'),
            'edit' => Pages\EditSwitchWorkDay::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['user.employee.contracts.supervisor', 'approvalSteps.approver']);

        if (!Auth::user()->hasRole(['super_admin', 'human_resource'])) {
            $query->where(function (Builder $query) {
                $query->where('user_id', Auth::id())
                    ->orWhereHas('approvalSteps', fn($q) => $q->where('approver_id', Auth::id()));
            });
        }

        return $query;
    }
}
