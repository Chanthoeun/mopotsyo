<?php

namespace App\Filament\Admin\Pages;

use App\Settings\SettingGeneral;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class General extends SettingsPage
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = SettingGeneral::class;

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('model.page.general');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.page.generals');
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
                        Forms\Components\TextInput::make('organization')
                            ->label(__('field.organization'))
                            ->required()
                            ->prefixIcon('fas-building'),
                        Forms\Components\TextInput::make('abbr')
                            ->label(__('field.abbr'))
                            ->required()
                            ->prefixIcon('fas-building'),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                PhoneInput::make('telephone')
                                    ->label(__('field.telephone'))
                                    ->required()
                                    ->prefixIcon('fas-phone')
                                    ->defaultCountry('kh'),
                                Forms\Components\TextInput::make('email')
                                    ->label(__('field.email'))
                                    ->required()
                                    ->prefixIcon('fas-envelope'),
                                Forms\Components\TextInput::make('website')
                                    ->label(__('field.website'))
                                    ->required()
                                    ->prefixIcon('fas-globe'),
                            ]),
                        Forms\Components\TextInput::make('address')
                            ->label(__('field.address'))
                            ->required()
                            ->prefixIcon('fas-map-marker')
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('logo')
                            ->label(__('field.logo'))
                            ->directory('settings')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,                                                                
                                '1:1',
                            ])
                            ->columnSpanFull(),
                    ])
            ]);
    }
}
