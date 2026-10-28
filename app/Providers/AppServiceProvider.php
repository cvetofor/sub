<?php

namespace App\Providers;

use App\Filament\Widgets\AbDailyChart;
use App\Filament\Widgets\AbFunnelWidget;
use App\Filament\Widgets\DesignDailyChart;
use App\Filament\Widgets\DesignFunnelWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        // Даты на сайте по-русски: «18 ноября», «сб»
        Carbon::setLocale('ru');

        // Виджеты страницы «A/B-тест главной» живут только на ней, а не на дашборде,
        // поэтому регистрируем их как компоненты Livewire вручную.
        Livewire::component('app.filament.widgets.ab-funnel-widget', AbFunnelWidget::class);
        Livewire::component('app.filament.widgets.ab-daily-chart', AbDailyChart::class);
        Livewire::component('app.filament.widgets.design-funnel-widget', DesignFunnelWidget::class);
        Livewire::component('app.filament.widgets.design-daily-chart', DesignDailyChart::class);
    }
}
