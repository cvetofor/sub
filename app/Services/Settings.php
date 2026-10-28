<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Настройки сайта, которые владелец меняет в админке без выката:
 * какой дизайн показывать, какие СМС слать, работать ли с amoCRM.
 * Значения кэшируются на минуту, чтобы не ходить в базу на каждом запросе.
 */
class Settings {
    public const DESIGN_CLASSIC = 'classic';
    public const DESIGN_QUIZ = 'quiz';
    public const DESIGN_SPLIT = 'split';

    public const DESIGNS = [self::DESIGN_CLASSIC, self::DESIGN_QUIZ];

    public const DESIGN_MODES = [
        self::DESIGN_CLASSIC => 'Дизайн 1: классическая главная с мастером',
        self::DESIGN_QUIZ => 'Дизайн 2: квиз',
        self::DESIGN_SPLIT => 'Чередовать 50 на 50 (A/B-тест)',
    ];

    public const HERO_MODES = [
        'a' => 'A: переключатель над заголовком',
        'b' => 'B: фото-карточки выбора',
        'split' => 'Чередовать 50 на 50',
    ];

    /** Виды СМС клиентам, каждую можно выключить в админке. */
    public const SMS_KINDS = [
        'activated' => 'После оплаты: подписка оформлена, ссылка на кабинет',
        'reminder' => 'За день до доставки: напоминание',
        'renewed' => 'После повторного списания: подписка продлена',
        'payment_failed' => 'Если списание не прошло: подписка на паузе',
        'code' => 'Код входа в кабинет (классический дизайн)',
        'unpaid' => 'Заявка без оплаты: ссылка на оплату через заданное время',
    ];

    public const DEFAULTS = [
        'design_mode' => self::DESIGN_SPLIT,
        'hero_mode' => 'a',
        'sms' => ['activated' => true, 'reminder' => true, 'renewed' => true, 'payment_failed' => true, 'code' => true, 'unpaid' => true],
        'unpaid_reminder_minutes' => 60,
        'amocrm_enabled' => false,
        'amocrm_pipeline_id' => null,
        'amocrm_status_new' => null,
        'amocrm_status_paid' => null,
        'amocrm_responsible_user_id' => null,
    ];

    private const CACHE_KEY = 'site-settings';

    public static function all(): array {
        $stored = Cache::remember(self::CACHE_KEY, 60, function () {
            // На пустой базе (миграции ещё не прошли) настройки по умолчанию
            if (!Schema::hasTable('settings')) {
                return [];
            }

            return Setting::query()->pluck('value', 'key')->all();
        });

        return array_replace(self::DEFAULTS, $stored);
    }

    public static function get(string $key, mixed $default = null): mixed {
        $all = self::all();

        return array_key_exists($key, $all) && $all[$key] !== null ? $all[$key] : ($default ?? (self::DEFAULTS[$key] ?? null));
    }

    public static function set(string $key, mixed $value): void {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_KEY);
    }

    public static function setMany(array $values): void {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget(self::CACHE_KEY);
    }

    public static function forget(): void {
        Cache::forget(self::CACHE_KEY);
    }

    /** Режим показа дизайна: classic, quiz или split. */
    public static function designMode(): string {
        $mode = (string) self::get('design_mode');

        return in_array($mode, [self::DESIGN_CLASSIC, self::DESIGN_QUIZ, self::DESIGN_SPLIT], true) ? $mode : self::DESIGN_SPLIT;
    }

    /** Версия первого экрана классической главной: a, b или split. */
    public static function heroMode(): string {
        $mode = (string) self::get('hero_mode');

        return in_array($mode, ['a', 'b', 'split'], true) ? $mode : 'a';
    }

    /** Разрешена ли СМС этого вида. Коды входа и неизвестные виды не блокируются настройкой. */
    public static function smsEnabled(string $kind): bool {
        $sms = self::get('sms');
        if (!is_array($sms) || !array_key_exists($kind, self::SMS_KINDS)) {
            return true;
        }

        return (bool) ($sms[$kind] ?? true);
    }

    public static function amoEnabled(): bool {
        return (bool) self::get('amocrm_enabled') && (bool) config('services.amocrm.subdomain') && (bool) config('services.amocrm.token');
    }
}
