<?php

namespace App\Filament\Admin\Pages;

use App\Settings\SettingOptions;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class Options extends SettingsPage
{
    use HasPageShield;
    
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = SettingOptions::class;

    protected static ?int $navigationSort = 2;

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
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Toggle::make('allow_add_user')
                            ->label(__('field.options.allow_add_user')),
                        Forms\Components\Toggle::make('allow_work_from_home')
                            ->label(__('field.options.allow_work_from_home')),
                        Forms\Components\Toggle::make('allow_switch_day_work')
                            ->label(__('field.options.allow_switch_day_work')),
                    ])
            ]);
    }
}
