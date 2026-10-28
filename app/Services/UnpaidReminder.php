<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Заявка оформлена, но не оплачена: через заданное время (по умолчанию час) клиенту уходит
 * одна СМС со ссылкой на оплату. Ссылка ведёт в кабинет без кода и сразу открывает оплату.
 * Старше недели не напоминаем: такие заявки уже брошены.
 */
class UnpaidReminder {
    public const KIND = 'unpaid';
    public const MAX_AGE_DAYS = 7;

    public static function delayMinutes(): int {
        $minutes = (int) Settings::get('unpaid_reminder_minutes', 60);

        return max(5, min(60 * 24 * 3, $minutes ?: 60));
    }

    /** Кандидаты: с сайта, без подтверждённой оплаты, без ранее отправленной СМС, оформлены от delay назад до недели. */
    public static function due(?Carbon $now = null): \Illuminate\Database\Eloquent\Collection {
        $now = $now ?? Carbon::now();

        return Subscription::query()
            ->whereIn('source', [Subscription::SOURCE_WIZARD, Subscription::SOURCE_QUIZ])
            ->where('is_active', false)
            ->whereNull('unpaid_reminded_at')
            ->whereNull('cancelled_at')
            ->whereNotNull('user_id')
            ->where('created_at', '<=', $now->copy()->subMinutes(self::delayMinutes()))
            ->where('created_at', '>=', $now->copy()->subDays(self::MAX_AGE_DAYS))
            ->whereDoesntHave('payments', fn ($q) => $q->where('payment_status_id', Payment::PAYED))
            ->with('user', 'plan')
            ->orderBy('id')
            ->get();
    }

    /** Разослать напоминания. Возвращает число отправленных. */
    public static function run(?Carbon $now = null): int {
        if (!Settings::smsEnabled(self::KIND)) {
            return 0;
        }
        $sent = 0;
        foreach (self::due($now) as $sub) {
            // Отметка ставится до отправки: при сбое СМС повторять не будем, чтобы не спамить
            $sub->forceFill(['unpaid_reminded_at' => Carbon::now()])->save();
            if (!$sub->user || !$sub->user->isClient() || !$sub->user->is_active) {
                continue;
            }
            $link = LoginLink::for($sub->user, ['pay' => $sub->id]);
            $amount = number_format($sub->firstPaymentAmount(), 0, '', ' ');
            $text = 'Цветофор: заказ №' . $sub->id . ' на ' . $amount . ' ₽ не оплачен. Оплатите по ссылке, и мы примем его в работу: ' . $link;
            if (Sms::send($sub->sender_phone, $text, self::KIND)) {
                $sent++;
            }
        }
        if ($sent) {
            Log::channel('shop')->info('Разосланы напоминания о неоплаченных заявках.', ['count' => $sent]);
        }

        return $sent;
    }
}
