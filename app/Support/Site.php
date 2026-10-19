<?php

namespace App\Support;

/** Адрес сайта для людей: в СМС и документах, без «https://» и punycode. */
final class Site {
    public static function host(): string {
        $host = config('app.site_host') ?: (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        if (str_contains($host, 'xn--') && function_exists('idn_to_utf8')) {
            $host = idn_to_utf8($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $host;
        }

        return $host ?: 'подписка.цветофор.рф';
    }
}
