<?php

namespace App\Http\Middleware;

use App\Models\AbEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * A/B-тест первого экрана главной.
 * Версия назначается при первом визите, 50 на 50, и хранится в cookie 30 дней,
 * чтобы один человек всегда видел одну и ту же версию.
 * Там же выдаётся анонимный идентификатор посетителя для подсчёта воронки.
 * Для проверки руками: ?hero=a или ?hero=b переназначает версию.
 */
class AssignHeroVariant {
    public const COOKIE = 'ab_hero';
    public const VISITOR_COOKIE = 'ab_visitor';
    public const VARIANTS = ['a', 'b'];

    public function handle(Request $request, Closure $next): Response {
        $forced = $request->query('hero');
        $variant = $request->cookie(self::COOKIE);
        $visitor = $request->cookie(self::VISITOR_COOKIE);

        if (in_array($forced, self::VARIANTS, true)) {
            $variant = $forced;
        } elseif (!in_array($variant, self::VARIANTS, true)) {
            $variant = self::VARIANTS[random_int(0, 1)];
        }
        if (!is_string($visitor) || !preg_match('/^[a-f0-9]{32}$/', $visitor)) {
            $visitor = bin2hex(random_bytes(16));
        }

        $request->attributes->set('hero_variant', $variant);
        $request->attributes->set('ab_visitor', $visitor);

        if ($request->isMethod('GET') && $request->routeIs('home') && !self::looksLikeBot($request)) {
            AbEvent::log($variant, 'visit', $visitor);
        }

        $response = $next($request);
        $minutes = 60 * 24 * 30;
        $response->headers->setCookie(cookie(self::COOKIE, $variant, $minutes, '/', null, null, false, false, 'lax'));
        $response->headers->setCookie(cookie(self::VISITOR_COOKIE, $visitor, $minutes, '/', null, null, true, false, 'lax'));

        return $response;
    }

    /** Текущие версия и посетитель из запроса или cookie, для контроллеров вне middleware. */
    public static function current(Request $request): array {
        $variant = $request->attributes->get('hero_variant') ?? $request->cookie(self::COOKIE);
        $visitor = $request->attributes->get('ab_visitor') ?? $request->cookie(self::VISITOR_COOKIE);

        return [
            in_array($variant, self::VARIANTS, true) ? $variant : null,
            is_string($visitor) && preg_match('/^[a-f0-9]{32}$/', $visitor) ? $visitor : null,
        ];
    }

    public static function looksLikeBot(Request $request): bool {
        $ua = strtolower((string) $request->userAgent());

        return $ua === '' || Str::contains($ua, ['bot', 'crawl', 'spider', 'slurp', 'yandex.com/bots', 'lighthouse', 'headless']);
    }
}
