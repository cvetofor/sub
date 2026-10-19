<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\Subscription;
use App\Support\Days;
use Carbon\Carbon;

/** Тексты СМС клиентам. Коротко: одна СМС = 70 кириллических знаков, стараемся уложиться в две. */
class Notifier {
    public static function subscriptionActivated(Subscription $sub): void {
        $first = $sub->deliveries()->planned()->orderBy('planned_date')->first();
        $when = $first ? ' Первый букет ' . Days::inDay($first->planned_date->dayOfWeekIso) . ' ' . $first->planned_date->translatedFormat('j F') . '.' : '';
        Sms::send($sub->sender_phone, 'Цветофор: подписка №' . $sub->id . ' оплачена.' . $when . ' Кабинет: ' . route('profile'), 'activated');
    }

    public static function deliveryTomorrow(Delivery $delivery): void {
        $sub = $delivery->subscription;
        $window = $delivery->windowLabel();
        $text = 'Цветофор: завтра' . ($window ? ' ' . $window : '') . ' привезём букет: ' . $sub->address . '. Перенести или уточнить: ' . route('profile');
        if (Sms::send($sub->sender_phone, $text, 'reminder')) {
            $delivery->update(['reminded_at' => Carbon::now()]);
        }
    }

    public static function paymentFailed(Subscription $sub, float $amount): void {
        Sms::send($sub->sender_phone, 'Цветофор: не удалось списать ' . number_format($amount, 0, '', ' ') . ' ₽ за подписку №' . $sub->id . '. Доставки на паузе. Проверьте карту и возобновите в кабинете: ' . route('profile'), 'payment_failed');
    }

    public static function renewed(Subscription $sub, float $amount): void {
        Sms::send($sub->sender_phone, 'Цветофор: подписка №' . $sub->id . ' продлена, списано ' . number_format($amount, 0, '', ' ') . ' ₽. Спасибо, что с нами!', 'renewed');
    }
}
