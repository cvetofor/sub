<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\Subscription;
use App\Support\Days;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сообщения команде в Telegram: новая оплата, изменения клиента, проблемы со списанием.
 * Нужны TELEGRAM_BOT_TOKEN и TELEGRAM_CHAT_ID; без них сообщение только в журнале.
 */
class TeamNotifier {
    public static function configured(): bool {
        return (bool) config('services.telegram.token') && (bool) config('services.telegram.chat_id');
    }

    public static function send(string $text): void {
        if (!self::configured()) {
            Log::channel('shop')->info('Telegram не настроен, сообщение команде: ' . $text);

            return;
        }
        try {
            Http::timeout(10)->post('https://api.telegram.org/bot' . config('services.telegram.token') . '/sendMessage', [
                'chat_id' => config('services.telegram.chat_id'),
                'text' => $text,
                'disable_web_page_preview' => true,
            ])->throw();
        } catch (\Throwable $e) {
            Log::channel('shop')->error('Не удалось отправить сообщение в Telegram.', ['message' => $e->getMessage()]);
        }
    }

    private static function card(Subscription $sub): string {
        return '№' . $sub->id . ' · ' . $sub->plan->name . ' · ' . (Pricing::LABELS[$sub->frequency] ?? $sub->frequency)
            . "\n" . $sub->sender_name . ' ' . $sub->sender_phone
            . "\n" . $sub->address
            . "\n" . url('/hub/subscriptions/' . $sub->id . '/edit');
    }

    public static function newSubscription(Subscription $sub, float $amount): void {
        $sub->loadMissing('plan');
        $first = $sub->deliveries()->planned()->orderBy('planned_date')->first();
        self::send('🌸 Новая подписка, оплачено ' . number_format($amount, 0, '', ' ') . ' ₽'
            . ($first ? "\nПервая доставка " . $first->planned_date->format('d.m') . ' (' . Days::short($first->planned_date->dayOfWeekIso) . ')' . ($first->windowLabel() ? ' ' . $first->windowLabel() : '') : '')
            . "\n" . self::card($sub));
    }

    public static function deliveryChanged(Subscription $sub): void {
        $sub->loadMissing('plan');
        self::send('✏️ Клиент изменил доставку: ' . (Days::NOMINATIVE[$sub->delivery_day] ?? '') . "\n" . self::card($sub));
    }

    public static function deliverySkipped(Subscription $sub, Delivery $delivery): void {
        $sub->loadMissing('plan');
        self::send('⏭ Клиент пропустил доставку ' . $delivery->planned_date->format('d.m') . "\n" . self::card($sub));
    }

    public static function paused(Subscription $sub): void {
        $sub->loadMissing('plan');
        self::send('⏸ Клиент поставил подписку на паузу' . "\n" . self::card($sub));
    }

    public static function paymentFailed(Subscription $sub, float $amount): void {
        $sub->loadMissing('plan');
        self::send('⚠️ Не прошло списание ' . number_format($amount, 0, '', ' ') . ' ₽, подписка на паузе' . "\n" . self::card($sub));
    }
}
