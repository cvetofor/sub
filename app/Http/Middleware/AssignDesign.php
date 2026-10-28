<?php

namespace App\Http\Middleware;

use App\Models\AbEvent;
use App\Services\Settings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Какой дизайн сайта показать посетителю и под какой версией считать его в статистике.
 *
 * Дизайн: classic (главная с мастером) или quiz. Режим задаётся в админке:
 * показывать только один или чередовать 50 на 50. Выбор хранится в cookie 30 дней,
 * чтобы один человек всегда видел одно и то же. Внутри классического дизайна
 * остаётся старый тест первого экрана (версии a и b), у квиза версия q.
 * Там же выдаётся анонимный идентификатор посетителя для подсчёта воронки.
 * Для проверки руками: ?design=quiz или ?design=classic, ?hero=a или ?hero=b.
 */
class AssignDesign {
    public const COOKIE = 'ab_hero';
    public const DESIGN_COOKIE = 'ab_design';
    public const VISITOR_COOKIE = 'ab_visitor';
    public const VARIANTS = ['a', 'b'];

    public function handle(Request $request, Closure $next, ?string $force = null): Response {
        $design = self::pickDesign($request, $force);
        $hero = self::pickHero($request);
        $visitor = $request->cookie(self::VISITOR_COOKIE);
        if (!is_string($visitor) || !preg_match('/^[a-f0-9]{32}$/', $visitor)) {
            $visitor = bin2hex(random_bytes(16));
        }

        $request->attributes->set('design', $design);
        $request->attributes->set('hero_variant', $hero);
        $request->attributes->set('ab_visitor', $visitor);

        if ($request->isMethod('GET') && ($request->routeIs('home') || $request->routeIs('quiz')) && !self::looksLikeBot($request)) {
            AbEvent::log($design === Settings::DESIGN_QUIZ ? 'q' : $hero, 'visit', $visitor, ['design' => $design]);
        }

        $response = $next($request);
        $minutes = 60 * 24 * 30;
        $response->headers->setCookie(cookie(self::DESIGN_COOKIE, $design, $minutes, '/', null, null, false, false, 'lax'));
        $response->headers->setCookie(cookie(self::COOKIE, $hero, $minutes, '/', null, null, false, false, 'lax'));
        $response->headers->setCookie(cookie(self::VISITOR_COOKIE, $visitor, $minutes, '/', null, null, true, false, 'lax'));

        return $response;
    }

    private static function pickDesign(Request $request, ?string $force): string {
        $forced = $force ?: $request->query('design');
        if (in_array($forced, Settings::DESIGNS, true)) {
            return $forced;
        }
        $mode = Settings::designMode();
        if ($mode !== Settings::DESIGN_SPLIT) {
            return $mode;
        }
        $cookie = $request->cookie(self::DESIGN_COOKIE);

        return in_array($cookie, Settings::DESIGNS, true) ? $cookie : Settings::DESIGNS[random_int(0, 1)];
    }

    private static function pickHero(Request $request): string {
        $forced = $request->query('hero');
        if (in_array($forced, self::VARIANTS, true)) {
            return $forced;
        }
        $mode = Settings::heroMode();
        if ($mode !== 'split') {
            return $mode;
        }
        $cookie = $request->cookie(self::COOKIE);

        return in_array($cookie, self::VARIANTS, true) ? $cookie : self::VARIANTS[random_int(0, 1)];
    }

    /**
     * Версия, посетитель и дизайн из запроса или cookie, для контроллеров вне middleware.
     * Версия: a | b для классического дизайна, q для квиза.
     */
    public static function current(Request $request): array {
        $design = $request->attributes->get('design') ?? $request->cookie(self::DESIGN_COOKIE);
        $design = in_array($design, Settings::DESIGNS, true) ? $design : null;
        $hero = $request->attributes->get('hero_variant') ?? $request->cookie(self::COOKIE);
        $hero = in_array($hero, self::VARIANTS, true) ? $hero : null;
        $visitor = $request->attributes->get('ab_visitor') ?? $request->cookie(self::VISITOR_COOKIE);

        return [
            $design === Settings::DESIGN_QUIZ ? 'q' : $hero,
            is_string($visitor) && preg_match('/^[a-f0-9]{32}$/', $visitor) ? $visitor : null,
            $design,
        ];
    }

    public static function looksLikeBot(Request $request): bool {
        $ua = strtolower((string) $request->userAgent());

        return $ua === '' || Str::contains($ua, ['bot', 'crawl', 'spider', 'slurp', 'yandex.com/bots', 'lighthouse', 'headless']);
    }
}
