<?php

namespace App\Services;

use App\Http\Api\SMSC;
use Illuminate\Support\Facades\Log;

/** Отправка СМС через SMSC. Без учётных данных текст пишется в журнал (тестовый стенд). */
class Sms {
    public static function configured(): bool {
        return (bool) config('smsc.login') && (bool) config('smsc.password');
    }

    public static function send(string $phoneE164, string $text, string $kind = 'sms'): bool {
        if (!Settings::smsEnabled($kind)) {
            Log::channel('shop')->info('СМС выключена в настройках.', ['kind' => $kind, 'phone' => $phoneE164]);

            return false;
        }
        if (!self::configured()) {
            Log::channel('shop')->info('СМС не отправлена: нет учётных данных SMSC.', ['kind' => $kind, 'phone' => $phoneE164, 'text' => app()->isProduction() ? '***' : $text]);

            return !app()->isProduction();
        }

        try {
            $response = (new SMSC(ltrim($phoneE164, '+')))->sendSMS($text);
        } catch (\Throwable $e) {
            Log::channel('shop')->error('Ошибка отправки СМС.', ['kind' => $kind, 'phone' => $phoneE164, 'message' => $e->getMessage()]);

            return false;
        }
        $ok = is_array($response) && empty($response['error']);
        Log::channel('shop')->info('СМС отправлена.', ['kind' => $kind, 'phone' => $phoneE164, 'ok' => $ok, 'response' => $response]);

        return $ok;
    }
}
