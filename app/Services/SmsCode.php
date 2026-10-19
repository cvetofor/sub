<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Вход и подтверждение телефона по коду из СМС.
 * Код живёт 10 минут, не больше одной отправки в минуту на номер и пяти попыток ввода.
 */
class SmsCode {
    public const TTL_MINUTES = 10;
    public const MAX_ATTEMPTS = 5;
    public const MAX_FAILURES_PER_HOUR = 10;

    /**
     * Отправляет код. Возвращает ['ok' => bool, 'retry_after' => сек, 'debug_code' => только вне production].
     */
    public function send(string $phoneE164, string $ip): array {
        $limitKey = 'sms-code:send:' . $phoneE164;
        if (RateLimiter::tooManyAttempts($limitKey, 1)) {
            return ['ok' => false, 'retry_after' => RateLimiter::availableIn($limitKey)];
        }
        $ipKey = 'sms-code:ip:' . $ip;
        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            return ['ok' => false, 'retry_after' => RateLimiter::availableIn($ipKey)];
        }
        RateLimiter::hit($limitKey, 60);
        RateLimiter::hit($ipKey, 3600);

        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        Cache::put($this->cacheKey($phoneE164), ['hash' => Hash::make($code), 'attempts' => 0], now()->addMinutes(self::TTL_MINUTES));

        $sent = $this->deliver($phoneE164, $code);
        $result = ['ok' => $sent, 'retry_after' => 60];
        if (!app()->isProduction()) {
            $result['debug_code'] = $code;
        }

        return $result;
    }

    /** Проверяет код. При успехе код сгорает. После 10 неверных вводов подряд номер блокируется на час. */
    public function verify(string $phoneE164, string $code): bool {
        $failKey = 'sms-code:fail:' . $phoneE164;
        if (RateLimiter::tooManyAttempts($failKey, self::MAX_FAILURES_PER_HOUR)) {
            return false;
        }

        $key = $this->cacheKey($phoneE164);
        $entry = Cache::get($key);
        if (!$entry) {
            RateLimiter::hit($failKey, 3600);

            return false;
        }
        if ($entry['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget($key);

            return false;
        }
        if (!Hash::check(trim($code), $entry['hash'])) {
            $entry['attempts']++;
            Cache::put($key, $entry, now()->addMinutes(self::TTL_MINUTES));
            RateLimiter::hit($failKey, 3600);

            return false;
        }
        Cache::forget($key);
        RateLimiter::clear($failKey);

        return true;
    }

    private function deliver(string $phoneE164, string $code): bool {
        return Sms::send($phoneE164, 'Код для входа на ' . \App\Support\Site::host() . ': ' . $code, 'code');
    }

    private function cacheKey(string $phoneE164): string {
        return 'sms-code:' . $phoneE164;
    }
}
