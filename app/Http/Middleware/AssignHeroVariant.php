<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A/B-тест первого экрана главной.
 * Версия назначается при первом визите, 50 на 50, и хранится в cookie 30 дней,
 * чтобы один человек всегда видел одну и ту же версию.
 * Для проверки руками: ?hero=a или ?hero=b переназначает версию.
 */
class AssignHeroVariant {
    public const COOKIE = 'ab_hero';
    public const VARIANTS = ['a', 'b'];

    public function handle(Request $request, Closure $next): Response {
        $forced = $request->query('hero');
        $variant = $request->cookie(self::COOKIE);

        if (in_array($forced, self::VARIANTS, true)) {
            $variant = $forced;
        } elseif (!in_array($variant, self::VARIANTS, true)) {
            $variant = self::VARIANTS[random_int(0, 1)];
        }

        $request->attributes->set('hero_variant', $variant);

        $response = $next($request);
        $response->headers->setCookie(cookie(self::COOKIE, $variant, 60 * 24 * 30, '/', null, null, false, false, 'lax'));

        return $response;
    }
}
