<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Plan;
use App\Models\PlanOption;
use App\Models\TimeDelivery;
use App\Services\Pricing;
use App\Support\Phone;
use Illuminate\Http\Request;

/**
 * Мастер подписки из четырёх шагов на одной странице:
 * 1 бюджет и частота, 2 стиль и предпочтения, 3 доставка, 4 контакты и оплата.
 */
class WizardController extends Controller {
    public function show(Request $request) {
        $plan = null;
        if ($request->filled('plan')) {
            $plan = Plan::showcase()->whereKey($request->integer('plan'))->first();
        }

        $frequency = $request->query('freq');
        if (!Pricing::isValid($frequency)) {
            $frequency = Pricing::BIWEEKLY;
        }

        $for = in_array($request->query('for'), [Plan::SCENARIO_HOME, Plan::SCENARIO_GIFT], true)
            ? $request->query('for')
            : ($plan && $plan->scenario !== Plan::SCENARIO_ANY ? $plan->scenario : null);

        $pricing = Pricing::table();
        $budget = $plan ? (int) $plan->price : 3000;
        $user = $request->user();

        $additions = PlanOption::active()->where('type', 'addition')->orderBy('price')->get()
            ->map(fn ($o) => ['id' => $o->id, 'name' => $o->name, 'price' => (float) $o->price, 'every' => (bool) $o->is_every_delivery])
            ->values();

        $windows = TimeDelivery::query()->orderBy('from')->get()
            ->map(fn ($w) => ['id' => $w->id, 'label' => substr($w->from, 0, 5) . '–' . substr($w->to, 0, 5)])
            ->values();

        return view('wizard.index', [
            'plan' => $plan,
            'frequency' => $frequency,
            'for' => $for,
            'pricing' => $pricing,
            'budget' => $budget,
            'additions' => $additions,
            'windows' => $windows,
            'cityName' => City::active()->value('name'),
            'firstBouquetDate' => HomeController::nextSaturday(),
            'user' => $user,
            'wizardJson' => json_encode([
                'pricing' => $pricing,
                'frequency' => $frequency,
                'budget' => $budget,
                'planId' => $plan?->id,
                'planName' => $plan?->name,
                'for' => $for,
                'additions' => $additions,
                'user' => $user ? ['name' => $user->name, 'phone' => Phone::pretty($user->phone_e164 ?: $user->phone)] : null,
                'urls' => [
                    'code' => route('auth.code'),
                    'verify' => route('auth.verify'),
                    'store' => route('subscribe.store'),
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
        ]);
    }
}
