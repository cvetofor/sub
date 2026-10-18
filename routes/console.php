<?php

use App\Http\Api\Yookassa;
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
            $subscription->update(['is_active' => false]);
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

            Log::channel('shop')->info('Подписка продлена.', ['subscription_id' => $subscription->id, 'amount' => $newPayment->amount, 'payment' => $paymentYookassa->id]);
        } else {
            $subscription->is_active = false;
            $subscription->save();

            Log::channel('shop')->info('Не удалось продлить подписку, поставлена на паузу.', ['subscription_id' => $subscription->id, 'status' => $paymentYookassa?->status]);
        }
    }
})->everyTwoHours();
