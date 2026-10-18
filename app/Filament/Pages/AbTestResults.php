<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AbDailyChart;
use App\Filament\Widgets\AbFunnelWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Результаты A/B-теста первого экрана главной, обновляются сами каждые 30 секунд. */
class AbTestResults extends Page {
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'A/B-тест главной';

    protected static ?string $title = 'A/B-тест первого экрана';

    protected static string|UnitEnum|null $navigationGroup = 'Подписки пользователей';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.ab-test-results';

    protected function getHeaderWidgets(): array {
        return [
            AbFunnelWidget::class,
            AbDailyChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array {
        return 1;
    }
}
