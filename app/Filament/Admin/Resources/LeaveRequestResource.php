<?php

namespace App\Filament\Admin\Resources;

use App\Actions\ApprovalActions;
use App\Filament\Admin\Resources\LeaveRequestResource\Pages;
use App\Filament\Admin\Resources\LeaveRequestResource\RelationManagers;
use App\Models\LeaveCarryForward;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Settings\SettingOptions;
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
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
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
                                    ->options(function (string $operation, ?Model $record) {
                                        if ($operation == 'view') {
                                            $user = $record->user;
                                        } else {
                                            $user = Auth::user();
                                        }

                                        return LeaveType::whereIn('id', $user->contract->contractType->leave_types)
                                            ->where($user->employee->gender->value, true)
                                            ->whereHas('entitlements', function ($query) use ($user) {
                                                $query->where('user_id', $user->id)
                                                    ->where('is_active', true)
                                                    ->whereDate('end_date', '>=', now())
                                                    ->orderBy('created_at', 'desc');
                                            })
                                            ->orderBy('id', 'asc')
                                            ->pluck('abbr', 'id');
                                    })
                                    ->required()
                                    ->inline()
                                    ->grouped()
                                    ->hint(function ($state) {
                                        if ($state) {
                                            return LeaveType::find($state)->name;
                                        }
                                    })
                                    ->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('from_date', null);
                                        $set('to_date', null);
                                        $set('requestDates', []);
                                        $set('overTimes', []);
                                    })
                                    ->rules([
                                        function (Get $get, string $operation, ?Model $record) {
                                            return function (string $attribute, $value, Closure $fail) use ($get, $operation, $record) {
                                                if ($operation == 'view') {
                                                    $user = $record->user;
                                                } else {
                                                    $user = Auth::user();
                                                }

                                                // check request date and time
                                                $requestDateTime = now();


                                                // get leave request days                                    
                                                $requestDays = getRequestDays($get('requestDates'));
                                                if ($requestDays == 0) {
                                                    $fail(__('msg.body.request_days_is_zero'));
                                                }

                                                // request days in advance
                                                $inAdvance = round(now()->diffInDays($get('from_date')), 0);
                                                // allow accruing leave days if no rule
                                                $leaveType = LeaveType::where('id', $value)->first();

                                                // check if request back date
                                                if (isRequestBackDate($get('to_date')) == true) {
                                                    // check balance
                                                    $entitlement = $user->entitlements()->where('leave_type_id', $leaveType->id)->where('is_active', true)->whereDate('end_date', '>=', now())->orderBy('created_at', 'desc')->first();
                                                    if ($entitlement && ($entitlement->remaining == 0 || $requestDays > $entitlement->remaining)) {
                                                        $fail(__('msg.balance_is_not_enough'));
                                                    } else if ($entitlement && (array_key_exists('allow_accrual', $leaveType->option) && $leaveType->option['allow_accrual'] == true && $requestDays > $entitlement->accrued)) {
                                                        $fail(__('msg.body.request_over_accrued_amount', ['amount' => $entitlement->accrued]));
                                                    } else {
                                                        // use overtime
                                                        if (app(SettingOptions::class)->allow_overtime == true && app(SettingOptions::class)->overtime_link == $leaveType->id && getOvertimeDays($user, $get('overTimes')) < $requestDays) {
                                                            $fail(__('msg.balance_is_not_enough'));
                                                        }
                                                    }
                                                } else if ($requestDays) {
                                                    if ($leaveType) {
                                                        // check rule
                                                        if ($leaveType->rules) {
                                                            $applicableRule = null;
                                                            // Find the applicable rule relative to the request days
                                                            foreach ($leaveType->rules as $rule) {
                                                                if ($requestDays >= $rule['from_amount'] && ($rule['to_amount'] == 0 || $requestDays <= $rule['to_amount'])) {
                                                                    $applicableRule = $rule;
                                                                    break;
                                                                }
                                                            }

                                                            if ($applicableRule && !empty($applicableRule['day_in_advance'])) {
                                                                $minDate = now()->addDays((int) $applicableRule['day_in_advance'])->startOfDay();
                                                                $fromDate = Carbon::parse($get('from_date'))->startOfDay();

                                                                if ($fromDate->lt($minDate)) {
                                                                    $fail(trans_choice('msg.body.in_advance', (int) $applicableRule['day_in_advance'], ['days' => $applicableRule['day_in_advance']]));
                                                                }
                                                            }
                                                        }

                                                        // check balance
                                                        $entitlement = $user->entitlements()->where('leave_type_id', $leaveType->id)->where('is_active', true)->whereDate('end_date', '>=', now())->orderBy('created_at', 'desc')->first();

                                                        // Check if Carry Forward covers this request
                                                        $carryForwardBalance = 0;
                                                        // Generic check for any valid Carry Forward
                                                        $cf = LeaveCarryForward::where('user_id', $user->id)
                                                            ->whereHas('leaveEntitlement', fn($q) => $q->where('leave_type_id', $leaveType->id))
                                                            ->whereDate('start_date', '<=', $get('from_date'))
                                                            ->whereDate('end_date', '>=', $get('from_date'))
                                                            ->first();

                                                        if ($cf && $cf->remaining > 0) {
                                                            $carryForwardBalance = $cf->remaining;
                                                        }

                                                        // Adjust request days by what CF can cover
                                                        $effectiveRequestDays = max(0, $requestDays - $carryForwardBalance);

                                                        if ($entitlement && ($entitlement->remaining == 0 || $effectiveRequestDays > $entitlement->remaining)) {
                                                            // Only fail if we still need days and have no entitlement, or not enough entitlement
                                                            if ($effectiveRequestDays > 0) {
                                                                $fail(__('msg.balance_is_not_enough'));
                                                            }
                                                        } else if ($entitlement && (array_key_exists('allow_accrual', $leaveType->option) && $leaveType->option['allow_accrual'] == true && $requestDays > $entitlement->accrued)) {
                                                            $fail(__('msg.body.request_over_accrued_amount', ['amount' => $entitlement->accrued]));
                                                        } else {
                                                            // use overtime
                                                            if (app(SettingOptions::class)->allow_overtime == true && app(SettingOptions::class)->overtime_link == $leaveType->id && getOvertimeDays($user, $get('overTimes')) < $requestDays) {
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
                                        if ($operation == 'create') {
                                            return $query->where('user_id', Auth::id())->whereDate('expiry_date', '>=', now())->where('unused', true)->orderBy('created_at', 'desc');
                                        } else {
                                            return $query->where('user_id', $record->user_id)->whereDate('expiry_date', '>=', now())->where('unused', true)->orderBy('created_at', 'desc');
                                        }
                                    })
                                    ->getOptionLabelFromRecordUsing(function (Model $record) {
                                        $dates = array();
                                        foreach ($record->requestDates as $item) {
                                            $dates[] = $item->date;
                                        }
                                        return strtolower(implode(',', $dates) . ' - ' . trans_choice('field.hours_with_count', $record->hours, ['count' => floatval($record->hours)]) . ' - expire on: ' . $record->expiry_date->toDateString());
                                    })
                                    ->live()
                                    ->visible(function (Get $get) {
                                        if (app(SettingOptions::class)->allow_overtime == true && app(SettingOptions::class)->overtime_link == $get('leave_type_id')) {
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
                                    ->afterStateUpdated(function ($state, Get $get, Set $set, string $operation, ?Model $record) {
                                        $set("to_date", $state);
                                        $set("requestDates", []);
                                        if ($get('leave_type_id') && $get('to_date')) {
                                            if ($operation == 'view') {
                                                $user = $record->user;
                                            } else {
                                                $user = Auth::user();
                                            }
                                            // add date to request dates list
                                            foreach (getDateRangeBetweenTwoDates($state, $get('to_date')) as $key => $date) {
                                                $workDay = $user->workDays->where('day_name.value', $date->dayOfWeek())->first();
                                                if ($workDay) {
                                                    if (dateIsNotDuplicated($user, $date) && !publicHoliday($date)) {
                                                        $set("requestDates.{$key}.date", $date->toDateString());
                                                        $set("requestDates.{$key}.start_time", $workDay->start_time);
                                                        $set("requestDates.{$key}.end_time", $workDay->end_time);
                                                        $set("requestDates.{$key}.hours", getHoursBetweenTwoTimes($workDay->start_time, $workDay->end_time, $workDay->break_time, $date));
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
                                    ->afterStateUpdated(function ($state, Get $get, Set $set, string $operation, ?Model $record) {
                                        $set("requestDates", []);
                                        if ($get('leave_type_id') && $get('from_date')) {
                                            if ($operation == 'view') {
                                                $user = $record->user;
                                            } else {
                                                $user = Auth::user();
                                            }

                                            // add date to request dates list
                                            foreach (getDateRangeBetweenTwoDates($get('from_date'), $state) as $key => $date) {
                                                $workDay = $user->workDays->where('day_name.value', $date->dayOfWeek())->first();
                                                if ($workDay) {
                                                    if (dateIsNotDuplicated($user, $date) && !publicHoliday($date)) {
                                                        $set("requestDates.{$key}.date", $date->toDateString());
                                                        $set("requestDates.{$key}.start_time", $workDay->start_time);
                                                        $set("requestDates.{$key}.end_time", $workDay->end_time);
                                                        $set("requestDates.{$key}.hours", getHoursBetweenTwoTimes($workDay->start_time, $workDay->end_time, $workDay->break_time, $date));
                                                    }
                                                }
                                            }
                                        }
                                    }),
                                Forms\Components\Textarea::make('reason')
                                    ->label(__('field.reason'))
                                    ->required()
                                    ->visible(function (Get $get, string $operation, ?Model $record) {
                                        if ($operation === 'view' && empty($record->reason)) {
                                            return false;
                                        }

                                        if (isRequestBackDate($get('to_date'))) {
                                            return true;
                                        } else if ($get('requestDates') && $get('leave_type_id')) {
                                            // get leave request days                                    
                                            $requestDays = getRequestDays($get('requestDates'));
                                            $leaveType = LeaveType::find($get('leave_type_id'));
                                            if ($leaveType->rules) {
                                                foreach ($leaveType->rules as $rule) {
                                                    if ($requestDays >= $rule['from_amount'] && ($rule['to_amount'] == 0 || $requestDays <= $rule['to_amount'])) {
                                                        if ($rule['reason'] == true) {
                                                            return true;
                                                        }
                                                        break;
                                                    }
                                                }
                                            }
                                            return false;
                                        }
                                        return false; // Default hidden if no condition met
                                    })
                                    ->columnSpanFull(),
                                Forms\Components\FileUpload::make('attachment')
                                    ->label(__('field.attachment'))
                                    ->required()
                                    ->directory('leave-attachments')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                    ->visible(function (Get $get, string $operation, ?Model $record) {
                                        if ($operation === 'view' && empty($record->attachment)) {
                                            return false;
                                        }

                                        if (isRequestBackDate($get('to_date'))) {
                                            return true;
                                        } elseif ($get('requestDates') && $get('leave_type_id')) {
                                            // get leave request days                                    
                                            $requestDays = getRequestDays($get('requestDates'));
                                            $leaveType = LeaveType::find($get('leave_type_id'));
                                            if ($leaveType->rules) {
                                                foreach ($leaveType->rules as $rule) {
                                                    if ($requestDays >= $rule['from_amount'] && ($rule['to_amount'] == 0 || $requestDays <= $rule['to_amount'])) {
                                                        if ($rule['attachment'] == true) {
                                                            return true;
                                                        }
                                                        break;
                                                    }
                                                }
                                            }
                                            return false;
                                        }
                                        return false;
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
                                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                $set('hours', getHoursBetweenTwoTimes($state, $get('end_time'), app(SettingWorkingHours::class)->break_time, $get('date')));
                                            })
                                            ->default('08:00:00'),
                                        Forms\Components\TimePicker::make('end_time')
                                            ->hiddenLabel()
                                            ->required()
                                            ->seconds(false)
                                            ->live()
                                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                $set('hours', getHoursBetweenTwoTimes($get('start_time'), $state, app(SettingWorkingHours::class)->break_time, $get('date')));
                                            })
                                            ->default('17:00:00'),
                                        Forms\Components\TextInput::make('hours')
                                            ->hiddenLabel()
                                            ->required()
                                            ->readOnly()
                                            ->default(0)
                                            ->formatStateUsing(fn($state) => $state == floor($state) ? (int) $state : $state),
                                    ]),
                                Forms\Components\Placeholder::make('total')
                                    ->label(__('field.label.total', ['label' => __('model.leave_request')]))
                                    ->inlineLabel()
                                    ->columnSpanFull()
                                    ->content(function (Get $get, Set $set): string {
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
                            ->visible(function (Get $get, string $operation, ?Model $record): bool {
                                if ($operation == 'view') {
                                    $user = $record->user;
                                    // Hide if not owner and not supervisor
                                    if ($user->id !== Auth::id() && $user->contract?->supervisor_id !== Auth::id()) {
                                        return false;
                                    }
                                } else {
                                    $user = Auth::user();
                                }
                                $entitlement = $user->entitlements()->where('leave_type_id', $get('leave_type_id'))->where('is_active', true)->whereDate('end_date', '>=', now())->first();
                                return empty($entitlement) ? false : true;
                            })
                            ->schema([
                                Forms\Components\Placeholder::make('balance')
                                    ->label(__('field.balance'))
                                    ->content(function (Get $get, string $operation, ?Model $record) {
                                        if ($operation == 'view') {
                                            $user = $record->user;
                                        } else {
                                            $user = Auth::user();
                                        }
                                        if (!empty($get('leave_type_id'))) {
                                            return $user->entitlements->where('is_active', true)->where('leave_type_id', $get('leave_type_id'))->first()->balance ?? 0;
                                        }
                                    }),
                                Forms\Components\Placeholder::make('taken')
                                    ->label(__('field.taken'))
                                    ->content(function (Get $get, string $operation, ?Model $record) {
                                        if ($operation == 'view') {
                                            $user = $record->user;
                                        } else {
                                            $user = Auth::user();
                                        }
                                        if (!empty($get('leave_type_id'))) {
                                            return $user->entitlements->where('is_active', true)->where('leave_type_id', $get('leave_type_id'))->first()->all_taken ?? 0;
                                        }
                                    }),
                                Forms\Components\Placeholder::make('remaining')
                                    ->label(__('field.remaining'))
                                    ->content(function (Get $get, string $operation, ?Model $record) {
                                        if ($operation == 'view') {
                                            $user = $record->user;
                                        } else {
                                            $user = Auth::user();
                                        }

                                        $remaining = 0;
                                        if (!empty($get('leave_type_id'))) {
                                            // Get entitlement
                                            $entitlement = $user->entitlements()
                                                ->where('is_active', true)
                                                ->where('leave_type_id', $get('leave_type_id'))
                                                ->whereDate('end_date', '>=', now())
                                                ->orderBy('created_at', 'desc')
                                                ->first();

                                            if ($entitlement) {
                                                // Simple calculation: balance - taken (don't use the accessor which subtracts CF)
                                                $remaining = $entitlement->balance - $entitlement->all_taken;
                                            }
                                        }
                                        return $remaining;
                                    }),
                                Forms\Components\Placeholder::make('accrued')
                                    ->label(__('field.accrued'))
                                    ->visible(function (Get $get) {
                                        if (!empty($get('leave_type_id'))) {
                                            $leaveType = LeaveType::find($get('leave_type_id'));
                                            return $leaveType->allow_accrual;
                                        }
                                        return false;
                                    })
                                    ->content(function (Get $get, string $operation, ?Model $record) {
                                        if ($operation == 'view') {
                                            $user = $record->user;
                                        } else {
                                            $user = Auth::user();
                                        }
                                        if (!empty($get('leave_type_id'))) {
                                            return $user->entitlements()->where('is_active', true)->where('leave_type_id', $get('leave_type_id'))->whereDate('end_date', '>=', now())->first()->accrued ?? 0;
                                        }
                                    }),
                            ]),
                        Forms\Components\Section::make(__('model.public_holidays'))
                            ->columnSpanFull()
                            ->visible(function (Get $get) {
                                if ($get('from_date') && $get('to_date')) {
                                    foreach (getDateRangeBetweenTwoDates($get('from_date'), $get('to_date')) as $date) {
                                        if (publicHoliday($date)) {
                                            return true;
                                        }
                                    }
                                }
                                return false;
                            })
                            ->schema([
                                Forms\Components\Placeholder::make('dupliatedDate')
                                    ->hiddenLabel()
                                    ->content(function (Get $get, string $operation, ?Model $record) {
                                        if ($operation == 'view') {
                                            $user = $record->user;
                                        } else {
                                            $user = Auth::user();
                                        }
                                        $str = '<div class="container mx-auto px-1 py-1"><ul class="list-decimal">';
                                        foreach (getDateRangeBetweenTwoDates($get('from_date'), $get('to_date')) as $key => $date) {
                                            $publicHoliday = publicHoliday($date);
                                            if ($publicHoliday) {
                                                $str = $str . '<li>' . \Carbon\Carbon::parse($publicHoliday->date)->toDateString() . ': ' . e($publicHoliday->name) . '</li>';
                                            }
                                        }
                                        $str = $str . '</ul></div>';
                                        return new HtmlString($str);
                                    }),
                            ]),
                        Forms\Components\Section::make(__('model.leave_request_rules'))
                            ->columnSpanFull()
                            ->collapsed()
                            ->visible(function (Get $get, string $operation): bool {
                                if ($operation === 'view') {
                                    return false;
                                }
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
                                        foreach ($rules as $key => $rule) {
                                            $key += 1;
                                            $str = $str . '<li><h4 class="font-bold">' . __('field.rule') . ' ' . $key . ': ' . e($rule['name']) . '</h4><p class="text-green-700">' . e($rule['description']) . '</p></li>';
                                        }
                                        $str = $str . '</ol></div>';
                                        return new HtmlString($str);
                                    }),
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
                Tables\Filters\SelectFilter::make('leave_type_id')
                    ->label(__('model.leave_type'))
                    ->relationship('leaveType', 'name')
                    ->preload()
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('field.status'))
                    ->options(\App\Enums\Status::class),
                Tables\Filters\TrashedFilter::make()->visible(fn() => Auth::user()->hasRole('super_admin'))
            ])
            ->filtersFormColumns(3)
            ->actions(
                array_merge(
                    [
                        Tables\Actions\Action::make('submit')
                            ->label(__('btn.submit'))
                            ->icon('heroicon-o-paper-airplane')
                            ->color('primary')
                            ->requiresConfirmation()
                            ->action(function (LeaveRequest $record) {
                                $record->submitToApproval();
                                Notification::make()
                                    ->title(__('msg.body.submitted', ['label' => __('model.leave_request')]))
                                    ->success()
                                    ->send();
                            })
                            ->visible(fn(LeaveRequest $record) => $record->status === \App\Enums\Status::CREATED),
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
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'view' => Pages\ViewLeaveRequest::route('/{record}'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['user.employee.contracts.supervisor', 'leaveType', 'requestDates', 'approvalSteps.approver']);

        if (!Auth::user()->hasRole(['super_admin', 'human_resource'])) {
            $query->where(function (Builder $query) {
                $query->where('user_id', Auth::id())
                    ->orWhereHas('approvalSteps', fn($q) => $q->where('approver_id', Auth::id()));
            });
        }

        return $query;
    }
}
