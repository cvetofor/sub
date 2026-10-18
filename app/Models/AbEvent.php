<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class AbEvent extends Model {
    public $timestamps = false;

    protected $fillable = ['variant', 'event', 'visitor', 'step', 'plan_id', 'mode', 'frequency', 'source', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public static function log(string $variant, string $event, string $visitor, array $params = []): ?self {
        if (!in_array($event, config('analytics.events'), true) || !in_array($variant, ['a', 'b'], true)) {
            return null;
        }

        return self::create([
            'variant' => $variant,
            'event' => $event,
            'visitor' => substr($visitor, 0, 64),
            'step' => isset($params['step']) ? (int) $params['step'] : null,
            'plan_id' => isset($params['plan_id']) ? (int) $params['plan_id'] : null,
            'mode' => isset($params['mode']) ? substr((string) $params['mode'], 0, 8) : null,
            'frequency' => isset($params['frequency']) ? substr((string) $params['frequency'], 0, 12) : null,
            'source' => isset($params['source']) ? substr((string) $params['source'], 0, 32) : null,
            'created_at' => Carbon::now(),
        ]);
    }

    /**
     * Воронка по версиям: уникальные посетители на каждом шаге за период.
     * Возвращает ['a' => ['visit' => n, ...], 'b' => [...]].
     */
    public static function funnel(?Carbon $from): array {
        $query = self::query()->selectRaw('variant, event, step, COUNT(DISTINCT visitor) AS visitors')
            ->groupBy('variant', 'event', 'step');
        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        $out = ['a' => [], 'b' => []];
        foreach ($query->get() as $row) {
            $key = $row->event === 'wizard_step' ? 'wizard_step_' . $row->step : $row->event;
            $out[$row->variant][$key] = ($out[$row->variant][$key] ?? 0) + (int) $row->visitors;
        }

        return $out;
    }
}
