<?php

namespace App\Filament\Widgets;

use App\Models\AbEvent;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

/** Воронка по версиям A и B: уникальные посетители на каждом шаге и доля от визитов. */
class AbFunnelWidget extends Widget {
    protected string $view = 'filament.widgets.ab-funnel';

    protected int|string|array $columnSpan = 'full';

    public string $period = '7d';

    public const PERIODS = [
        'today' => 'Сегодня',
        '7d' => '7 дней',
        '30d' => '30 дней',
        'all' => 'Всё время',
    ];

    public const STEPS = [
        'visit' => 'Открыли главную',
        'hero_mode_select' => 'Выбрали «домой» или «в подарок»',
        'plans_view' => 'Долистали до планов',
        'plan_cta_click' => 'Нажали «Оформить» на плане',
        'wizard_start' => 'Открыли мастер',
        'wizard_step_2' => 'Шаг 2: стиль',
        'wizard_step_3' => 'Шаг 3: доставка',
        'wizard_step_4' => 'Шаг 4: оплата',
        'payment_redirect' => 'Ушли в оплату',
        'payment_success' => 'Оплатили',
    ];

    public static function from(string $period): ?Carbon {
        return match ($period) {
            'today' => Carbon::today(),
            '7d' => Carbon::today()->subDays(6),
            '30d' => Carbon::today()->subDays(29),
            default => null,
        };
    }

    protected function getViewData(): array {
        $funnel = AbEvent::funnel(self::from($this->period));
        $rows = [];
        foreach (self::STEPS as $key => $label) {
            $a = $funnel['a'][$key] ?? 0;
            $b = $funnel['b'][$key] ?? 0;
            $va = $funnel['a']['visit'] ?? 0;
            $vb = $funnel['b']['visit'] ?? 0;
            $pa = $va ? $a / $va * 100 : null;
            $pb = $vb ? $b / $vb * 100 : null;
            $rows[] = [
                'key' => $key,
                'label' => $label,
                'a' => $a, 'b' => $b,
                'pa' => $pa, 'pb' => $pb,
                'leader' => ($pa !== null && $pb !== null && $key !== 'visit' && abs($pa - $pb) >= 0.5) ? ($pa > $pb ? 'a' : 'b') : null,
            ];
        }

        $primaryA = $funnel['a']['wizard_step_3'] ?? 0;
        $primaryB = $funnel['b']['wizard_step_3'] ?? 0;
        $enough = ($funnel['a']['wizard_start'] ?? 0) >= 300 && ($funnel['b']['wizard_start'] ?? 0) >= 300;

        return [
            'rows' => $rows,
            'periods' => self::PERIODS,
            'visitsA' => $funnel['a']['visit'] ?? 0,
            'visitsB' => $funnel['b']['visit'] ?? 0,
            'primaryA' => $primaryA,
            'primaryB' => $primaryB,
            'enough' => $enough,
            'updatedAt' => Carbon::now()->format('H:i:s'),
        ];
    }
}
