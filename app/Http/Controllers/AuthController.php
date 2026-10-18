<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Subscription;
use App\Support\Phone;
use Illuminate\Http\Request;

/** Личный кабинет клиента: список подписок и управление ими. */
class AuthController extends Controller {
    public function index(Request $request) {
        $user = $request->user();

        // Новые подписки привязаны по user_id, старые только по номеру телефона в разных форматах
        $phones = array_values(array_unique(array_filter([
            $user->phone,
            $user->phone_e164,
            $user->phone_e164 ? Phone::legacy($user->phone_e164) : null,
        ])));

        $subscriptions = Subscription::with(['plan.options', 'payments', 'timeDelivery'])
            ->where(function ($q) use ($user, $phones) {
                $q->where('user_id', $user->id);
                if ($phones) {
                    $q->orWhereIn('sender_phone', $phones);
                }
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Subscription $sub) {
                $sub->is_paid = $sub->payments->contains(fn ($p) => (int) $p->payment_status_id === Payment::PAYED);

                return $sub;
            });

        return view('profile.index', ['subscriptions' => $subscriptions, 'user' => $user]);
    }
}
