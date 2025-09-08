<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\SubscriptionsChart;
use App\Models\Role;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider {
    public function boot(): void {
        Filament::serving(function () {
            $user = Auth::user();

            if ($user && $user->role_id === Role::CLIENT) {
                Auth::logout();
                request()->session()->invalidate();
                request()->session()->regenerateToken();

                abort(403, 'Доступ запрещён');
            }
        });
    }

    public function panel(Panel $panel): Panel {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'danger'   => '#f43f5e',
                'info'     => '#6366f1',
                'primary'  => '#ca4592',
                'success'  => '#71be38',
                'warning'  => '#f59e0b',
            ])
            ->darkMode(false)
            ->brandName("Цветофор.рф")
            ->brandLogo(asset('images/logo.svg'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('images/favicon.ico'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                StatsOverview::class,
                SubscriptionsChart::class
            ])
            ->navigationGroups([
                'Подписки пользователей',
                'Пользователь',
                'Административное управлние элементами'
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
                Authenticate::class,
            ]);
    }
}
