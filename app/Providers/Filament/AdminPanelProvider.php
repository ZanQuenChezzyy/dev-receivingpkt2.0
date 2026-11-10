<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\EditProfile;
use App\Filament\Pages\Login;
use App\Http\Middleware\EnsureNpkIsFilled;
use Caresome\FilamentAuthDesigner\AuthDesignerPlugin;
use Caresome\FilamentAuthDesigner\Enums\MediaPosition;
use Caresome\FilamentAuthDesigner\View\AuthDesignerRenderHook;
use Caresome\FilamentNeobrutalism\NeobrutalismeTheme;
use Devonab\FilamentEasyFooter\EasyFooterPlugin;
use Filament\Enums\ThemeMode;
use Filament\Enums\UserMenuPosition;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// use WatheqAlshowaiter\FilamentStickyTableHeader\StickyTableHeaderPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $themePlugin = $this->themePlugin();

        return $panel
            ->default()
            ->spa()
            ->spaUrlExceptions(fn (): array => [
                url('/'),
                route('home'),
            ])
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login(Login::class)
            ->profile(EditProfile::class)
            ->brandLogo('/images/logo/receiving_dark.png')
            ->darkModeBrandLogo('/images/logo/receiving_white.png')
            ->brandLogoHeight('2.5rem')
            ->defaultThemeMode(ThemeMode::System)
            ->colors([
                'danger' => Color::Red,
                'gray' => Color::Slate,
                'info' => Color::Sky,
                'primary' => Color::hex('#F47920'),
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->databaseNotifications()
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->renderHook(
                'panels::global-search.after',
                fn (): string => Blade::render('@livewire(\'ai-system-toggle\')')
            )
            ->renderHook(
                'panels::sidebar.footer',
                fn (): string => Blade::render('@include(\'filament.components.sidebar-pattern\')')
            )
            ->renderHook(
                'panels::simple-page.start',
                fn (): string => Blade::render('@include(\'filament.components.login-pattern\')')
            )
            ->renderHook(
                'panels::auth.login.form.after',
                fn (): string => Blade::render('<div class="mt-2"><x-filament::button tag="a" :href="route(\'home\')" color="gray" outlined icon="heroicon-o-home-modern" class="w-full">Kembali ke Beranda</x-filament::button></div>')
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                Dashboard::class,
            ])
            ->navigationItems([
                NavigationItem::make('Beranda')
                    ->url(fn (): string => route('home'))
                    ->icon(Heroicon::OutlinedHomeModern)
                    ->activeIcon(Heroicon::HomeModern)
                    ->sort(-1),
            ])
            ->navigationGroups([
                'Halaman Utama',
                'Penerimaan Receiving',
                'Penerimaan Jasa / Invoice',
                'Transaksi',
                'Pengeluaran Material',
                'Data Master',
                'Kelola Pengguna',
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // AccountWidget::class,
                // FilamentInfoWidget::class,
            ])
            ->when(
                $themePlugin,
                fn (Panel $panel) => $panel->plugins([$themePlugin])
            )
            // FOOTER PLUGIN
            ->plugins([
                EasyFooterPlugin::make()
                    ->withBorder()
                    ->withSentence('Receiving PKT v2.0 By Andereyan Muhammat. All rights reserved.')
                    ->hiddenFromPages(['admin/login', 'admin/register', 'admin/forgot-password'])
                    ->hiddenFromPagesEnabled(),
                AuthDesignerPlugin::make()
                    ->defaults(
                        fn ($config) => $config
                            ->media(asset('images/auth/background-auth-3.png'))
                            ->mediaPosition(MediaPosition::Left)
                            ->blur(2)
                            ->mediasize('70%')
                            ->renderHook(AuthDesignerRenderHook::MediaOverlay, fn () => view('auth.motivator-quotes'))
                    )
                    ->themeToggle(),
                // StickyTableHeaderPlugin::make()
                //     ->shouldScrollToTopOnPageChanged(enabled: true, behavior: "smooth"),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureNpkIsFilled::class,
            ]);
    }

    private function themePlugin(): ?object
    {
        return match (config('chezzy.active_theme')) {
            'neobrutalism' => NeobrutalismeTheme::make()
                ->customize([
                    'border-width' => '2px',
                    'shadow-offset-md' => '3px',
                    'radius-md' => '0.5rem',
                ]),

            default => null,
        };
    }
}
