<?php

namespace App\Filament\Widgets;

use App\Models\AbEvent;
use App\Models\Subscription;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/** Визиты и заявки по дням за две недели, по дизайнам. */
class DesignDailyChart extends ChartWidget {
    protected ?string $heading = 'Визиты и заявки по дням';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected function getData(): array {
        $from = Carbon::today()->subDays(13);
        $visits = AbEvent::query()
            ->selectRaw('design, DATE(created_at) AS day, COUNT(DISTINCT visitor) AS visitors')
            ->where('event', 'visit')
            ->where('created_at', '>=', $from)
            ->groupBy('design', 'day')
            ->get();
        $orders = Subscription::query()
            ->selectRaw('ab_design AS design, DATE(created_at) AS day, COUNT(*) AS orders')
            ->where('created_at', '>=', $from)
            ->groupBy('design', 'day')
            ->get();

        $labels = [];
        $series = ['vc' => [], 'vq' => [], 'oc' => [], 'oq' => []];
        for ($d = $from->copy(); $d <= Carbon::today(); $d->addDay()) {
            $key = $d->toDateString();
            $labels[] = $d->format('d.m');
            $series['vc'][] = (int) ($visits->first(fn ($r) => $r->design === 'classic' && $r->day === $key)?->visitors ?? 0);
            $series['vq'][] = (int) ($visits->first(fn ($r) => $r->design === 'quiz' && $r->day === $key)?->visitors ?? 0);
            $series['oc'][] = (int) ($orders->first(fn ($r) => $r->design === 'classic' && $r->day === $key)?->orders ?? 0);
            $series['oq'][] = (int) ($orders->first(fn ($r) => $r->design === 'quiz' && $r->day === $key)?->orders ?? 0);
        }

        return [
            'datasets' => [
                ['label' => 'Визиты · дизайн 1', 'data' => $series['vc'], 'borderColor' => '#3F7D2A', 'backgroundColor' => '#3F7D2A', 'tension' => 0.3],
                ['label' => 'Визиты · квиз', 'data' => $series['vq'], 'borderColor' => '#CA4592', 'backgroundColor' => '#CA4592', 'tension' => 0.3],
                ['label' => 'Заявки · дизайн 1', 'data' => $series['oc'], 'borderColor' => '#3F7D2A', 'backgroundColor' => '#3F7D2A', 'borderDash' => [6, 4], 'tension' => 0.3],
                ['label' => 'Заявки · квиз', 'data' => $series['oq'], 'borderColor' => '#CA4592', 'backgroundColor' => '#CA4592', 'borderDash' => [6, 4], 'tension' => 0.3],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string {
        return 'line';
    }
}
