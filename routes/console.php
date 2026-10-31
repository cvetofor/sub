<?php

use App\Http\Api\Yookassa;
use App\Models\Delivery;
use App\Services\DeliverySchedule;
use App\Services\Notifier;
use App\Services\TeamNotifier;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

/*
 * Ежемесячные списания. Берём активные подписки, у которых подошла дата,
 * списываем сумму обычного месяца по сохранённому способу оплаты.
 */
Schedule::call(function () {
    if (!Yookassa::isConfigured()) {
        Log::channel('shop')->warning('Повторные списания пропущены: нет ключей ЮKassa.');

        return;
    }

    $now = Carbon::now();
    $subscriptions = Subscription::active()
        ->with('plan.options')
        ->where('next_date_payment', '<=', $now)
        ->get();

    foreach ($subscriptions as $subscription) {
        // Одна незавершённая попытка уже есть: повторно деньги не запрашиваем.
        $attempt = $subscription->payments()
            ->where('payment_status_id', Payment::IN_PROGRESS)
            ->orderByDesc('payments.created_at')
            ->first();
        if ($attempt && !Yookassa::isUnsentRecurringAttempt($attempt)) {
            Log::channel('shop')->info('Повторное списание уже ожидает ответ ЮKassa.', ['subscription_id' => $subscription->id, 'payment' => $attempt->payment_gateway_transaction]);
            continue;
        }

        // Токен берём с последнего успешного платежа, у которого он сохранён
        $payment = $subscription->payments()
            ->where('payment_status_id', Payment::PAYED)
            ->whereNotNull('recurrent_token')
            ->where('recurrent_token', '!=', '')
            ->orderByDesc('payments.created_at')
            ->first();

        if (!$payment || str_starts_with((string) $payment->recurrent_token, 'test-')) {
            Log::channel('shop')->warning('Нет сохранённого способа оплаты, подписка остановлена.', ['subscription_id' => $subscription->id]);
            $subscription->update(['is_active' => false, 'pause_reason' => \App\Models\Subscription::PAUSE_PAYMENT]);
            Notifier::paymentFailed($subscription, $subscription->monthlyAmount());
            continue;
        }

        try {
            $attempt ??= Yookassa::startRecurringPaymentAttempt($subscription, $payment->recurrent_token);
            $yookassa = new Yookassa();
            $paymentYookassa = $yookassa->recurrentPayment($payment->recurrent_token, $subscription, $attempt->idempotence_key);
        } catch (\Throwable $e) {
            Log::channel('shop')->error('Ошибка повторного списания.', ['subscription_id' => $subscription->id, 'message' => $e->getMessage()]);
            continue;
        }

        if ($paymentYookassa && $paymentYookassa->status === 'pending') {
            // Банк ещё думает: итог придёт уведомлением payment.succeeded или payment.canceled
            Yookassa::saveRecurringPaymentInProgress($subscription, $paymentYookassa, $payment->recurrent_token);
            Log::channel('shop')->info('Повторное списание в обработке, ждём уведомление.', ['subscription_id' => $subscription->id, 'payment' => $paymentYookassa->id]);
            continue;
        }

        if ($paymentYookassa && $paymentYookassa->status === 'succeeded' && $paymentYookassa->paid === true) {
            $newPayment = Yookassa::completeRecurringPayment(
                $subscription,
                $paymentYookassa->id,
                $paymentYookassa->amount->value,
                $paymentYookassa->payment_method->id ?? $payment->recurrent_token,
            );
            if ($newPayment) {
                $subscription->refresh()->load('plan.options');
                // Доставки следующего месяца и СМС клиенту
                DeliverySchedule::planNextMonth($subscription);
                Notifier::renewed($subscription, (float) $newPayment->amount);

                Log::channel('shop')->info('Подписка продлена.', ['subscription_id' => $subscription->id, 'amount' => $newPayment->amount, 'payment' => $paymentYookassa->id]);
            }
        } else {
            if (Yookassa::cancelRecurringPayment($subscription, $paymentYookassa?->id ?? $attempt->payment_gateway_transaction)) {
                $subscription->refresh()->load('plan.options');
                Notifier::paymentFailed($subscription, $subscription->monthlyAmount());
                TeamNotifier::paymentFailed($subscription, $subscription->monthlyAmount());

                Log::channel('shop')->info('Не удалось продлить подписку, поставлена на паузу.', ['subscription_id' => $subscription->id, 'status' => $paymentYookassa?->status]);
            }
        }
    }
})->everyTwoHours()->name('subscriptions:charge-recurrent')->withoutOverlapping();

/* Напоминание о завтрашней доставке: один раз, только по активным подпискам. */
Schedule::call(function () {
    $tomorrow = Carbon::tomorrow()->toDateString();
    $deliveries = Delivery::planned()->onDate($tomorrow)->whereNull('reminded_at')
        ->whereHas('subscription', fn ($q) => $q->where('is_active', true))
        ->with('subscription', 'timeDelivery')
        ->get();
    foreach ($deliveries as $delivery) {
        Notifier::deliveryTomorrow($delivery);
    }
    if ($deliveries->isNotEmpty()) {
        Log::channel('shop')->info('Разосланы напоминания о доставках.', ['date' => $tomorrow, 'count' => $deliveries->count()]);
    }
})->dailyAt('18:00')->name('deliveries:remind')->withoutOverlapping();

/* Заявка без оплаты: через заданное в настройках время одна СМС со ссылкой на оплату. */
Schedule::call(function () {
    \App\Services\UnpaidReminder::run();
})->everyTenMinutes()->name('subscriptions:remind-unpaid')->withoutOverlapping();

/* Ночью закрываем вчерашние доставки, которые флорист не отметил вручную. */
Schedule::call(function () {
    $closed = Delivery::planned()->where('planned_date', '<', Carbon::today()->toDateString())
        ->whereHas('subscription', fn ($q) => $q->where('is_active', true))
        ->update(['status' => Delivery::DELIVERED, 'delivered_at' => Carbon::now(), 'note' => 'закрыта автоматически']);
    if ($closed) {
        Log::channel('shop')->info('Прошедшие доставки закрыты автоматически.', ['count' => $closed]);
    }
})->dailyAt('02:00')->name('deliveries:close');
