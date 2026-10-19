<?php

namespace App\Filament\Widgets;

use App\Models\Delivery;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget {
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array {
        $activeSubs = fn ($q) => $q->whereHas('subscription', fn ($s) => $s->where('is_active', true));
        $today = Delivery::planned()->onDate(today())->tap($activeSubs)->count();
        $tomorrow = Delivery::planned()->onDate(Carbon::tomorrow())->tap($activeSubs)->count();
        $monthRevenue = Payment::where('payment_status_id', Payment::PAYED)->where('created_at', '>=', now()->startOfMonth())->sum('amount');
        $paymentPaused = Subscription::where('is_active', false)->where('pause_reason', Subscription::PAUSE_PAYMENT)->count();

        return [
            Stat::make('Доставок сегодня', $today)
                ->description($tomorrow . ' завтра')
                ->url('/hub/deliveries')
                ->color($today > 0 ? 'primary' : 'gray'),
            Stat::make('Активных подписок', Subscription::active()->count())
                ->description(Subscription::count() . ' всего' . ($paymentPaused ? ' · ' . $paymentPaused . ' с неудачным списанием' : ''))
                ->color($paymentPaused ? 'warning' : 'success'),
            Stat::make('Выручка за месяц', number_format((float) $monthRevenue, 0, '', ' ') . ' ₽')
                ->description('за всё время ' . number_format(Payment::totalPaid(), 0, '', ' ') . ' ₽'),
        ];
    }
}
