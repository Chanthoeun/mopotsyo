<?php

namespace App\Filament\Admin\Pages;

use App\Enums\DayOfWeekEnum;
use App\Settings\SettingWorkingHours;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class WorkingHours extends SettingsPage
{
    use HasPageShield;
    
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = SettingWorkingHours::class;

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('model.page.working_hour');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.page.working_hours');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.settings');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('day')
                            ->label(__('field.working_hours_per_day'))
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('week')
                            ->label(__('field.working_hours_per_week'))
                            ->required()
                            ->numeric(),
                        TableRepeater::make('work_days')
                            ->label(__('field.working_days_per_week'))                                                   
                            ->reorderable(true)
                            ->required()
                            ->maxItems(7)  
                            ->addActionLabel(__('btn.label.add', ['label' => __('field.working_day')]))       
                            ->defaultItems(0)                                  
                            ->headers([
                                Header::make(__('field.day')),
                                Header::make(__('field.start_date')),
                                Header::make(__('field.end_date')),
                                Header::make(__('field.break_time'))->width('100px'),
                                Header::make(__('field.from')),
                                Header::make(__('field.to')),
                            ])                                             
                            ->columnSpan('full')                                
                            ->schema([                                
                                Forms\Components\Select::make('day_name')
                                    ->hiddenLabel()
                                    ->required()
                                    ->options(DayOfWeekEnum::class)
                                    ->default(DayOfWeekEnum::MONDAY),
                                Forms\Components\TimePicker::make('start_time') 
                                    ->hiddenLabel()                                       
                                    ->required()
                                    ->default('08:00:00'),
                                Forms\Components\TimePicker::make('end_time')
                                    ->hiddenLabel()
                                    ->required()
                                    ->default('17:00:00'),
                                Forms\Components\TextInput::make('break_time')
                                    ->hiddenLabel()
                                    ->required()
                                    ->numeric()
                                    ->live()
                                    ->inputMode('decimal')
                                    ->default(1),
                                Forms\Components\TimePicker::make('break_from')
                                    ->hiddenLabel()
                                    ->required()
                                    ->default('12:00:00'),
                                Forms\Components\TimePicker::make('break_to')
                                    ->hiddenLabel()
                                    ->required()
                                    ->default('13:00:00'),
                            ]),
                    ])
            ]);
    }
}
