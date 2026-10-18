<?php

namespace App\Filament\Widgets;

use App\Models\AbEvent;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/** Старты мастера по дням, версии A и B. */
class AbDailyChart extends ChartWidget {
    protected ?string $heading = 'Старты мастера по дням';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected function getData(): array {
        $from = Carbon::today()->subDays(13);
        $rows = AbEvent::query()
            ->selectRaw("variant, DATE(created_at) AS day, COUNT(DISTINCT visitor) AS visitors")
            ->where('event', 'wizard_start')
            ->where('created_at', '>=', $from)
            ->groupBy('variant', 'day')
            ->get();

        $labels = [];
        $a = [];
        $b = [];
        for ($d = $from->copy(); $d <= Carbon::today(); $d->addDay()) {
            $key = $d->toDateString();
            $labels[] = $d->format('d.m');
            $a[] = (int) ($rows->first(fn ($r) => $r->variant === 'a' && $r->day === $key)?->visitors ?? 0);
            $b[] = (int) ($rows->first(fn ($r) => $r->variant === 'b' && $r->day === $key)?->visitors ?? 0);
        }

        return [
            'datasets' => [
                ['label' => 'A · сегмент', 'data' => $a, 'borderColor' => '#CA4592', 'backgroundColor' => '#CA4592', 'tension' => 0.3],
                ['label' => 'B · карточки', 'data' => $b, 'borderColor' => '#3F7D2A', 'backgroundColor' => '#3F7D2A', 'tension' => 0.3],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string {
        return 'line';
    }
}
