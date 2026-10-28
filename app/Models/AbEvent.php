<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Журнал событий воронки. Одна строка — одно событие посетителя.
 * design: classic | quiz, по нему сравниваются два дизайна сайта.
 * variant: a | b — версия первого экрана классической главной, q — квиз.
 */
class AbEvent extends Model {
    public $timestamps = false;

    protected $fillable = ['variant', 'design', 'event', 'visitor', 'step', 'plan_id', 'mode', 'frequency', 'source', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public const DESIGNS = ['classic', 'quiz'];

    public static function log(string $variant, string $event, string $visitor, array $params = []): ?self {
        if (!in_array($event, config('analytics.events'), true) || !in_array($variant, ['a', 'b', 'q'], true)) {
            return null;
        }

        return self::create([
            'variant' => $variant,
            'design' => in_array($params['design'] ?? null, self::DESIGNS, true) ? $params['design'] : ($variant === 'q' ? 'quiz' : 'classic'),
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
     * Воронка первого экрана классической главной по версиям A и B:
     * уникальные посетители на каждом шаге за период. ['a' => ['visit' => n, ...], 'b' => [...]].
     */
    public static function funnel(?Carbon $from): array {
        $query = self::query()->selectRaw('variant, event, step, COUNT(DISTINCT visitor) AS visitors')
            ->where('design', 'classic')
            ->groupBy('variant', 'event', 'step');
        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        $out = ['a' => [], 'b' => []];
        foreach ($query->get() as $row) {
            if (!isset($out[$row->variant])) {
                continue;
            }
            $key = $row->event === 'wizard_step' ? 'wizard_step_' . $row->step : $row->event;
            $out[$row->variant][$key] = ($out[$row->variant][$key] ?? 0) + (int) $row->visitors;
        }

        return $out;
    }

    /**
     * Воронка по дизайнам: уникальные посетители на каждом шаге за период.
     * Шаги с «start» объединяют мастер и квиз: ['classic' => ['visit' => n, 'start' => n, 'step_2' => n, ...], 'quiz' => [...]].
     */
    public static function designFunnel(?Carbon $from): array {
        $query = self::query()->selectRaw('design, event, step, COUNT(DISTINCT visitor) AS visitors')
            ->groupBy('design', 'event', 'step');
        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        $out = ['classic' => [], 'quiz' => []];
        foreach ($query->get() as $row) {
            if (!isset($out[$row->design])) {
                continue;
            }
            $key = match ($row->event) {
                'wizard_start', 'quiz_start' => 'start',
                'wizard_step', 'quiz_step' => 'step_' . $row->step,
                default => $row->event,
            };
            $out[$row->design][$key] = ($out[$row->design][$key] ?? 0) + (int) $row->visitors;
        }

        return $out;
    }
}
