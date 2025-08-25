<?php

use App\Http\Api\Yookassa;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    $now = Carbon::now();
    $subscriptions = Subscription::active()->where('next_date_payment', '<=', $now)->get();

    foreach ($subscriptions as $subscription) {
        $payment = $subscription->payments()->first();

        $yookassa = new Yookassa();
        $paymentYookassa = $yookassa->recurrentPayment($payment->recurrent_token, $subscription);

        if ($paymentYookassa->status === 'succeeded' && $paymentYookassa->paid === true) {
            $subscription->is_active = true;
            $subscription->next_date_payment = Carbon::parse($subscription->next_date_payment)->addMonth();
            $subscription->save();

            $newPayment = new Payment;
            $newPayment->payment_status_id = Payment::PAYED;
            $newPayment->amount = $subscription->totalAmount();
            $newPayment->payment_gateway_transaction = $paymentYookassa->id;
            $newPayment->recurrent_token = $paymentYookassa->payment_method->id;
            $newPayment->save();

            $subscription->payments()->attach($newPayment->id);

            Log::channel('shop')->info('Подписка успешно продлена.', [$subscription, $paymentYookassa]);
        } else {
            $subscription->is_active = false;
            $subscription->save();

            Log::channel('shop')->info('Не удалось продлить подписку.', [$subscription, $paymentYookassa]);
        }
    }
})->everySecond();
