<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget {
    protected function getStats(): array {
        $subscriptions = Subscription::all();

        return [
            Stat::make('Подписок всего', $subscriptions->count()),
            Stat::make('Активных подписок', Subscription::active()->count()),
            Stat::make('Выручка за все время', Payment::totalPaid() . ' ₽'),
        ];
    }
}
