<?php

namespace App\Support;

/** Телефоны России в одном виде: +7XXXXXXXXXX. */
final class Phone {
    /** Возвращает +7XXXXXXXXXX или null, если это не российский мобильный номер. */
    public static function normalize(?string $raw): ?string {
        $digits = preg_replace('/\D+/', '', (string) $raw);
        if (strlen($digits) === 11 && in_array($digits[0], ['7', '8'], true)) {
            $digits = '7' . substr($digits, 1);
        } elseif (strlen($digits) === 10 && $digits[0] === '9') {
            $digits = '7' . $digits;
        } else {
            return null;
        }
        if ($digits[1] !== '9') {
            return null;
        }

        return '+' . $digits;
    }

    /** +7 (999) 123-45-67 для показа. */
    public static function pretty(?string $e164): string {
        $d = preg_replace('/\D+/', '', (string) $e164);
        if (strlen($d) !== 11) {
            return (string) $e164;
        }

        return sprintf('+7 (%s) %s-%s-%s', substr($d, 1, 3), substr($d, 4, 3), substr($d, 7, 2), substr($d, 9, 2));
    }

    /** Вид, в котором номера хранил старый сайт: +7 (999) 999-9999. Нужен для совместимости со старыми подписками. */
    public static function legacy(?string $e164): string {
        $d = preg_replace('/\D+/', '', (string) $e164);
        if (strlen($d) !== 11) {
            return (string) $e164;
        }

        return sprintf('+7 (%s) %s-%s', substr($d, 1, 3), substr($d, 4, 3), substr($d, 7, 4));
    }
}
