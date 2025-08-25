<?php

namespace App\Http\Controllers;

use App\Http\Api\Yookassa;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller {
    public static function create($subscription): string {
        $yookassa = new Yookassa();
        $paymentYookassa = $yookassa->createPayment($subscription->id);

        if (isset($paymentYookassa)) {
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

        return '';
    }
}
