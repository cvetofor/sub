<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DesignDailyChart;
use App\Filament\Widgets\DesignFunnelWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Сравнение двух дизайнов сайта: сколько зашло, начало оформлять, оформило заявку и оплатило. */
class DesignStats extends Page {
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static ?string $navigationLabel = 'Статистика дизайнов';

    protected static ?string $title = 'Статистика дизайнов: классический против квиза';

    protected static string|UnitEnum|null $navigationGroup = 'Подписки пользователей';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.design-stats';

    protected function getHeaderWidgets(): array {
        return [
            DesignFunnelWidget::class,
            DesignDailyChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array {
        return 1;
    }
}
