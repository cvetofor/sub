<?php

namespace App\Http\Controllers;

use App\Models\Subscription;

/** Карточка заявки по защищённой ссылке из amoCRM: без входа, только для просмотра. */
class AmoSubscriptionViewController extends Controller
{
    public function __invoke(string $token)
    {
        $subscription = Subscription::query()
            ->where('amo_view_token', $token)
            ->with(['plan.options', 'timeDelivery', 'deliveries.timeDelivery', 'payments.status'])
            ->firstOrFail();

        return view('amo.subscription', ['subscription' => $subscription]);
    }
}
