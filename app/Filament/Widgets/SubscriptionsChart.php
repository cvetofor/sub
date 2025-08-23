<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class SubscriptionsChart extends ChartWidget {
    protected ?string $heading = 'График оформленных подписок по месяцам';

    protected ?string $pollingInterval = null;

    protected static bool $isLazy = true;

    protected function getData(): array {
        $data = Trend::query(Subscription::active())
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Активные подписки',
                    'data' => $data->map(fn(TrendValue $value) => $value->aggregate),
                    'backgroundColor' => '#ca4592',
                    'borderColor' => '#ca4592',
                ],
            ],
            'labels' => ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июнь', 'Июль', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
        ];
    }

    protected function getType(): string {
        return 'line';
    }
}
