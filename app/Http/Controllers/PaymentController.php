<?php

namespace App\Http\Controllers;

use App\Http\Api\Yookassa;
use App\Models\Payment;

class PaymentController extends Controller {
    public static function create($subscription) {
        $yookassa = new Yookassa();
        $paymentLink = $yookassa->getPaymentLink($subscription->id);
        $paymentRecord = Payment::create([
            'payment_status_id' => 1,
        ]);

        $subscription->payments()->attach($paymentRecord->id);

        return $paymentLink;
    }
}
