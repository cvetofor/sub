<?php

use App\Http\Api\Yookassa;
use App\Models\Delivery;
use App\Services\DeliverySchedule;
use App\Services\Notifier;
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
            $yookassa = new Yookassa();
            $paymentYookassa = $yookassa->recurrentPayment($payment->recurrent_token, $subscription);
        } catch (\Throwable $e) {
            Log::channel('shop')->error('Ошибка повторного списания.', ['subscription_id' => $subscription->id, 'message' => $e->getMessage()]);
            continue;
        }

        if ($paymentYookassa && $paymentYookassa->status === 'succeeded' && $paymentYookassa->paid === true) {
            $subscription->is_active = true;
            $subscription->next_date_payment = Carbon::parse($subscription->next_date_payment)->addMonth();
            $subscription->save();

            $newPayment = new Payment;
            $newPayment->payment_status_id = Payment::PAYED;
            $newPayment->amount = $paymentYookassa->amount->value;
            $newPayment->payment_gateway_transaction = $paymentYookassa->id;
            $newPayment->recurrent_token = $paymentYookassa->payment_method->id ?? $payment->recurrent_token;
            $newPayment->save();

            $subscription->payments()->attach($newPayment->id);

            // Доставки следующего месяца и СМС клиенту
            DeliverySchedule::planNextMonth($subscription);
            Notifier::renewed($subscription, (float) $newPayment->amount);

            Log::channel('shop')->info('Подписка продлена.', ['subscription_id' => $subscription->id, 'amount' => $newPayment->amount, 'payment' => $paymentYookassa->id]);
        } else {
            $subscription->is_active = false;
            $subscription->pause_reason = \App\Models\Subscription::PAUSE_PAYMENT;
            $subscription->save();
            Notifier::paymentFailed($subscription, $subscription->monthlyAmount());

            Log::channel('shop')->info('Не удалось продлить подписку, поставлена на паузу.', ['subscription_id' => $subscription->id, 'status' => $paymentYookassa?->status]);
        }
    }
})->everyTwoHours();

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

/* Ночью закрываем вчерашние доставки, которые флорист не отметил вручную. */
Schedule::call(function () {
    $closed = Delivery::planned()->where('planned_date', '<', Carbon::today()->toDateString())
        ->whereHas('subscription', fn ($q) => $q->where('is_active', true))
        ->update(['status' => Delivery::DELIVERED, 'delivered_at' => Carbon::now(), 'note' => 'закрыта автоматически']);
    if ($closed) {
        Log::channel('shop')->info('Прошедшие доставки закрыты автоматически.', ['count' => $closed]);
    }
})->dailyAt('02:00')->name('deliveries:close');
