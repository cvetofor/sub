<?php

namespace App\Http\Controllers;

use App\Http\Api\Yookassa;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller {
    public static function create($subscription) {
        $payment = $subscription->payments()
            ->orderBy('created_at', 'desc')
            ->first();

        if ($payment && $payment->payment_status_id === Payment::IN_PROGRESS) {
            return 'https://yoomoney.ru/checkout/payments/v2/contract?orderId=' . $payment->payment_gateway_transaction;
        }

        if ($payment && $payment->payment_status_id === Payment::PAYED) {
            return 'Уже есть оплаченная транзакция. По ней будут рекурентные списания.';
        }

        if (!$payment) {
            $yookassa = new Yookassa();
            $paymentYookassa = $yookassa->createPayment($subscription->id);

            if (!$paymentYookassa) {
                Log::channel('shop')->error('Ошибка при создании платежа через Yookassa', [
                    'subscription_id' => $subscription->id,
                ]);

                return 'Ошибка создания операции в платежном сервисе.';
            }

            $paymentModel = Payment::create([
                'payment_status_id' => Payment::IN_PROGRESS,
                'amount' => $paymentYookassa->amount->value,
                'payment_gateway_transaction' =>  $paymentYookassa->id
            ]);

            $subscription->payments()->attach($paymentModel->id);

            Log::channel('shop')->info('Создана новая оплата через Yookassa.', [
                'subscription_id' => $subscription->id,
                'amount' => $paymentYookassa->amount->value,
                'payment_gateway_transaction' =>  $paymentYookassa->id
            ]);

            return $paymentYookassa->getConfirmation()->getConfirmationUrl();
        }
    }

    public function redirect(Request $request) {
        if ($request->has('subscription_id')) {
            $subscriptionId = $request->subscription_id;
            $subscription = Subscription::find($subscriptionId);

            // sleep(1);
            $payment = $subscription->payments()
                ->orderBy('created_at', 'desc')
                ->first();

            if ($payment && $payment->payment_status_id === Payment::PAYED) {
                return view('payment.success', compact('subscription'));
            }

            return view('payment.fail');
        }

        return url('/');
    }
}
