<?php

namespace App\Filament\Admin\Pages;

use App\Models\LeaveType;
use App\Models\User;
use App\Settings\SettingOptions;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use EightyNine\Approvals\Services\ModelScannerService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class Options extends SettingsPage
{
    use HasPageShield;
    
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = SettingOptions::class;

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('model.page.option');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.page.options');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.settings');
    }

    public function form(Form $form): Form
    {
        $models = (new ModelScannerService())->getApprovableModels();
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('field.create_user'))
                            ->schema([
                                Forms\Components\Toggle::make('allow_add_user')
                                    ->label(__('field.options.allow_add_user')),
                            ]),
                        Forms\Components\Tabs\Tab::make(__('model.work_from_home'))
                            ->schema([
                                Forms\Components\Toggle::make('allow_work_from_home')
                                    ->label(__('field.options.allow_work_from_home')),
                                Forms\Components\Repeater::make('work_from_home_rules')
                                    ->label(__('field.rules'))
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->collapsed(false)
                                    ->addActionLabel(__('btn.label.add', ['label' => __('field.rule')]))
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                    ->reorderable(false)
                                    ->schema([                                                                                    
                                        Forms\Components\TextInput::make('name')
                                            ->label(__('field.name'))
                                            ->required()
                                            ->unique(ignoreRecord:true)
                                            ->live(onBlur: true)
                                            ->maxLength(255),
                                        Forms\Components\Select::make('roles')
                                            ->label(__('field.approval_roles'))                                                    
                                            ->required()
                                            ->multiple()
                                            ->options(fn () => Role::whereNot('id', 1)->orderBy('id', 'asc')->get()->pluck('name', 'id')->map(fn ($item) => ucwords(Str::of($item)->replace('_', ' ')))->toArray()),                       
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('from_amount')
                                                    ->label(__('field.from_amount'))
                                                    ->required()
                                                    ->numeric()
                                                    ->suffix(__('field.day')),
                                                Forms\Components\TextInput::make('to_amount')
                                                    ->label(__('field.to_amount'))
                                                    ->required()
                                                    ->numeric()
                                                    ->suffix(__('field.day')),
                                                Forms\Components\TextInput::make('day_in_advance')
                                                    ->label(__('field.day_in_advance'))
                                                    ->required()
                                                    ->numeric()
                                                    ->suffix(__('field.day')),
                                            ]),                                         
                                        Forms\Components\Textarea::make('description')
                                            ->label(__('field.desc'))
                                            ->columnSpanFull(),                                                                                                                               
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make(__('model.switch_work_day'))
                            ->schema([
                                Forms\Components\Toggle::make('allow_switch_day_work')
                                    ->label(__('field.options.allow_switch_day_work')),
                                // Forms\Components\Repeater::make('switch_day_work_rules')
                                //     ->label(__('field.rules'))
                                //     ->columns(2)
                                //     ->columnSpanFull()
                                //     ->collapsed(false)
                                //     ->addActionLabel(__('btn.label.add', ['label' => __('field.rule')]))
                                //     ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                //     ->reorderable(false)
                                //     ->schema([                                                                                    
                                //         Forms\Components\TextInput::make('name')
                                //             ->label(__('field.name'))
                                //             ->required()
                                //             ->unique(ignoreRecord:true)
                                //             ->live(onBlur: true)
                                //             ->maxLength(255),
                                //         Forms\Components\Select::make('roles')
                                //             ->label(__('field.approval_roles'))                                                    
                                //             ->required()
                                //             ->options(fn () => Role::whereNot('id', 1)->orderBy('id', 'asc')->get()->pluck('name', 'id')->map(fn ($item) => ucwords(Str::of($item)->replace('_', ' ')))->toArray()),                       
                                //         Forms\Components\Grid::make(3)
                                //             ->schema([
                                //                 Forms\Components\TextInput::make('from_amount')
                                //                     ->label(__('field.from_amount'))
                                //                     ->required()
                                //                     ->numeric()
                                //                     ->suffix(__('field.day')),
                                //                 Forms\Components\TextInput::make('to_amount')
                                //                     ->label(__('field.to_amount'))
                                //                     ->required()
                                //                     ->numeric()
                                //                     ->suffix(__('field.day')),
                                //                 Forms\Components\TextInput::make('day_in_advance')
                                //                     ->label(__('field.day_in_advance'))
                                //                     ->required()
                                //                     ->numeric()
                                //                     ->suffix(__('field.day')),
                                //             ]),                                         
                                //         Forms\Components\Textarea::make('description')
                                //             ->label(__('field.desc'))
                                //             ->columnSpanFull(),                                                                                                                               
                                //     ]),
                            ]),
                        Forms\Components\Tabs\Tab::make(__('model.overtime'))
                            ->columns(3)
                            ->schema([
                                Forms\Components\Toggle::make('allow_overtime')
                                    ->label(__('field.options.allow_overtime'))
                                    ->inline(false),
                                Forms\Components\TextInput::make('overtime_expiry')
                                    ->label(__('field.options.overtime_expiry'))
                                    ->required()
                                    ->numeric()
                                    ->suffix(__('field.day')),
                                Forms\Components\Select::make('overtime_link')
                                    ->label(__('field.options.overtime_link'))
                                    ->options(LeaveType::pluck('name', 'id'))
                            ]),
                        Forms\Components\Tabs\Tab::make(__('field.options.cc_email'))
                            ->schema([
                                TableRepeater::make('cc_emails')
                                    ->label(__('field.options.cc_email'))  
                                    ->hiddenLabel()                          
                                    ->addActionLabel(__('btn.add'))                            
                                    ->defaultItems(1)
                                    ->headers([
                                        Header::make(__('field.feature'))->width('40%'),
                                        Header::make(__('field.options.accounts')),
                                    ]) 
                                    ->schema([
                                        Forms\Components\Select::make('model_type')
                                            ->label(__('field.feature'))
                                            ->options(function() use ($models) {
                                                // remove 'App\Models\' from the value of models
                                                $models = array_map(function($model) {
                                                    return str_replace('App\Models\\', '', $model);
                                                }, $models);
                                                return $models;
                                            })
                                            ->required(),
                                        Forms\Components\Select::make('accounts')
                                            ->label(__('field.options.accounts'))
                                            ->multiple()
                                            ->options(function() {
                                                return User::whereHas('employee', fn(Builder $q) => $q->whereNull('resign_date')->orWhereDate('resign_date', '>', now()))->get()->pluck('full_name', 'id');
                                            })
                                            ->required(),
                                    ])
                            ]),
                        ]),
                
            ]);
    }
}
