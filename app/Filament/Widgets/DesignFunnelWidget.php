<?php

namespace App\Filament\Widgets;

use App\Models\AbEvent;
use App\Models\Payment;
use App\Models\Subscription;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

/** Воронка по дизайнам: визиты, начали оформлять, заявки, оплаты и конверсия на каждом шаге. */
class DesignFunnelWidget extends Widget {
    protected string $view = 'filament.widgets.design-funnel';

    protected int|string|array $columnSpan = 'full';

    public string $period = '30d';

    public const PERIODS = [
        'today' => 'Сегодня',
        '7d' => '7 дней',
        '30d' => '30 дней',
        'all' => 'Всё время',
    ];

    public static function from(string $period): ?Carbon {
        return match ($period) {
            'today' => Carbon::today(),
            '7d' => Carbon::today()->subDays(6),
            '30d' => Carbon::today()->subDays(29),
            default => null,
        };
    }

    /** Цифры по дизайну: ['visits' => n, 'starts' => n, 'orders' => n, 'paid' => n, 'revenue' => сумма]. */
    public static function numbers(?Carbon $from): array {
        $funnel = AbEvent::designFunnel($from);
        $out = [];
        foreach (AbEvent::DESIGNS as $design) {
            $subs = Subscription::query()->where('ab_design', $design);
            if ($from) {
                $subs->where('created_at', '>=', $from);
            }
            $orders = (clone $subs)->count();
            $paidQuery = (clone $subs)->whereHas('payments', fn ($q) => $q->where('payment_status_id', Payment::PAYED));
            $paid = (clone $paidQuery)->count();
            $revenue = (float) Payment::query()->where('payment_status_id', Payment::PAYED)
                ->whereIn('id', fn ($q) => $q->select('payment_id')->from('subscription_payments')->whereIn('subscription_id', (clone $paidQuery)->select('id')))
                ->sum('amount');
            $out[$design] = [
                'visits' => (int) ($funnel[$design]['visit'] ?? 0),
                'starts' => (int) ($funnel[$design]['start'] ?? 0),
                'orders' => $orders,
                'paid' => $paid,
                'revenue' => $revenue,
            ];
        }

        return $out;
    }

    protected function getViewData(): array {
        $n = self::numbers(self::from($this->period));
        $pct = fn (int $a, int $b) => $b > 0 ? $a / $b * 100 : null;
        $steps = [
            ['key' => 'visits', 'label' => 'Зашли на сайт', 'base' => null],
            ['key' => 'starts', 'label' => 'Начали оформлять', 'base' => 'visits'],
            ['key' => 'orders', 'label' => 'Оформили заявку (ушли в оплату)', 'base' => 'visits'],
            ['key' => 'paid', 'label' => 'Оплатили', 'base' => 'visits'],
        ];
        $rows = [];
        foreach ($steps as $s) {
            $c = $n['classic'][$s['key']];
            $q = $n['quiz'][$s['key']];
            $pc = $s['base'] ? $pct($c, $n['classic'][$s['base']]) : null;
            $pq = $s['base'] ? $pct($q, $n['quiz'][$s['base']]) : null;
            $rows[] = [
                'label' => $s['label'], 'classic' => $c, 'quiz' => $q, 'pc' => $pc, 'pq' => $pq,
                'leader' => ($pc !== null && $pq !== null && abs($pc - $pq) >= 0.5) ? ($pc > $pq ? 'classic' : 'quiz') : null,
            ];
        }
        $rows[] = [
            'label' => 'Оплатили из оформивших', 'classic' => $n['classic']['paid'], 'quiz' => $n['quiz']['paid'],
            'pc' => $pct($n['classic']['paid'], $n['classic']['orders']), 'pq' => $pct($n['quiz']['paid'], $n['quiz']['orders']), 'leader' => null,
        ];
        $enough = $n['classic']['visits'] >= 500 && $n['quiz']['visits'] >= 500 && ($n['classic']['paid'] + $n['quiz']['paid']) >= 20;

        return [
            'rows' => $rows,
            'n' => $n,
            'periods' => self::PERIODS,
            'convC' => $pct($n['classic']['paid'], $n['classic']['visits']),
            'convQ' => $pct($n['quiz']['paid'], $n['quiz']['visits']),
            'enough' => $enough,
            'updatedAt' => Carbon::now()->format('H:i:s'),
        ];
    }
}
