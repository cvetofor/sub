<?php

namespace App\Http\Controllers;

use App\Http\Api\Yookassa;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller {
    public static function create($subscription) {
        $yookassa = new Yookassa();
        $paymentYookassa = $yookassa->createPayment($subscription->id);

        $paymentModel = Payment::create([
            'payment_status_id' => Payment::IN_PROGRESS,
            'amount' => $paymentYookassa->amount->value,
            'payment_gateway_transaction' =>  $paymentYookassa->id
        ]);

        $subscription->payments()->attach($paymentModel->id);

        return $paymentYookassa->getConfirmation()->getConfirmationUrl();
    }
}
