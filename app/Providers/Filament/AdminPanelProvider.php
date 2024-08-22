<?php

namespace App\Providers\Filament;

use App\Filament\Auth\CustomLogin;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\SpatieLaravelTranslatablePlugin;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Kenepa\TranslationManager\TranslationManagerPlugin;
use Mchev\Banhammer\Middleware\AuthBanned;
use Mchev\Banhammer\Middleware\LogoutBanned;
use Njxqlus\FilamentProgressbar\FilamentProgressbarPlugin;
use Tapp\FilamentAuthenticationLog\FilamentAuthenticationLogPlugin;
use Yebor974\Filament\RenewPassword\RenewPasswordPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(CustomLogin::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->resources([
                config('filament-logger.activity_resource')
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([ 
                LogoutBanned::class, 
                AuthBanned::class,
                Authenticate::class,
            ])
            ->maxContentWidth(MaxWidth::Full)
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->viteTheme('resources/css/filament/admin/theme.css')            
            ->plugins([
                FilamentProgressbarPlugin::make()->color('#29b'),
                FilamentAuthenticationLogPlugin::make(),
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                SpatieLaravelTranslatablePlugin::make()->defaultLocales(['en', 'km']),
                TranslationManagerPlugin::make(),
                RenewPasswordPlugin::make()->forceRenewPassword(),
                \EightyNine\Approvals\ApprovalPlugin::make()
            ])
            ->unsavedChangesAlerts()
            ->navigationGroups([
                NavigationGroup::make()
                     ->label(fn() => __('nav.employee'))
                     ->icon('fas-user'),
                NavigationGroup::make()
                     ->label(fn() => __('nav.hr'))
                     ->icon('fas-users'),
                NavigationGroup::make()
                     ->label(fn() => __('nav.rdf'))
                     ->icon('heroicon-o-shopping-cart'),
                NavigationGroup::make()
                    ->label(fn() => __('nav.admin'))
                    ->icon('fas-gears'),
                NavigationGroup::make()
                    ->label(fn (): string => __('nav.log'))
                    ->icon('fas-file-lines')
                    ->collapsed(),
            ])
            ->font(
                'Battambang', 
                url: 'https://fonts.googleapis.com/css2?family=Battambang:wght@100;300;400;700;900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap',
                provider: GoogleFontProvider::class);
    }
}
