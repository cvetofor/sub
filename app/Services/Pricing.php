<?php

namespace App\Services;

/**
 * Единая арифметика подписки.
 *
 * Правило акции: второй букет в подписке бесплатно при любой частоте.
 *  - каждую неделю: 4 букета в месяц, в первый месяц платим за 3;
 *  - раз в 2 недели: 2 букета в месяц, в первый месяц платим за 1;
 *  - раз в месяц: 1 букет в месяц, в первый месяц привозим 2 и платим за 1.
 */
class Pricing {
    public const WEEKLY = 'weekly';
    public const BIWEEKLY = 'biweekly';
    public const MONTHLY = 'monthly';

    /** Сколько букетов привозим в обычный месяц. */
    public const PER_MONTH = [
        self::WEEKLY => 4,
        self::BIWEEKLY => 2,
        self::MONTHLY => 1,
    ];

    /** Сколько букетов привозим в первый месяц. */
    public const FIRST_MONTH_DELIVERIES = [
        self::WEEKLY => 4,
        self::BIWEEKLY => 2,
        self::MONTHLY => 2,
    ];

    /** За сколько букетов платим в первый месяц. */
    public const FIRST_MONTH_PAID = [
        self::WEEKLY => 3,
        self::BIWEEKLY => 1,
        self::MONTHLY => 1,
    ];

    public const LABELS = [
        self::WEEKLY => 'Каждую неделю',
        self::BIWEEKLY => 'Раз в 2 недели',
        self::MONTHLY => 'Раз в месяц',
    ];

    public const NOTES = [
        self::WEEKLY => '4 букета в месяц',
        self::BIWEEKLY => '2 букета в месяц',
        self::MONTHLY => '1 букет в месяц',
    ];

    public const PROMO = [
        self::WEEKLY => 'Первый месяц: 4 букета по цене 3',
        self::BIWEEKLY => 'Первый месяц: 2 букета по цене 1',
        self::MONTHLY => 'Первый месяц: 2 букета по цене 1, второй привезём через две недели',
    ];

    public static function frequencies(): array {
        return array_keys(self::PER_MONTH);
    }

    public static function isValid(?string $frequency): bool {
        return $frequency !== null && array_key_exists($frequency, self::PER_MONTH);
    }

    /** Стоимость первого месяца (с подарочным букетом). */
    public static function firstMonth(float $pricePerBouquet, string $frequency, float $oneTimeExtras = 0, float $perDeliveryExtras = 0): float {
        $paid = self::FIRST_MONTH_PAID[$frequency];

        return round(($pricePerBouquet + $perDeliveryExtras) * $paid + $oneTimeExtras, 2);
    }

    /** Стоимость обычного месяца, со второго. */
    public static function monthly(float $pricePerBouquet, string $frequency, float $perDeliveryExtras = 0): float {
        return round(($pricePerBouquet + $perDeliveryExtras) * self::PER_MONTH[$frequency], 2);
    }

    /** Полная цена первого месяца без акции, для зачёркнутой суммы. */
    public static function firstMonthWithoutPromo(float $pricePerBouquet, string $frequency, float $perDeliveryExtras = 0): float {
        return round(($pricePerBouquet + $perDeliveryExtras) * self::FIRST_MONTH_DELIVERIES[$frequency], 2);
    }

    /** Данные для фронтенда одним массивом. */
    public static function table(): array {
        $out = [];
        foreach (self::frequencies() as $f) {
            $out[$f] = [
                'perMonth' => self::PER_MONTH[$f],
                'firstDeliveries' => self::FIRST_MONTH_DELIVERIES[$f],
                'firstPaid' => self::FIRST_MONTH_PAID[$f],
                'label' => self::LABELS[$f],
                'note' => self::NOTES[$f],
                'promo' => self::PROMO[$f],
            ];
        }

        return $out;
    }
}
