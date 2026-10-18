<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\Pricing;
use Illuminate\Http\Request;

/**
 * Мастер подписки из четырёх шагов.
 * Сейчас: шаги 1 (бюджет и частота) и 2 (стиль и предпочтения) на одной странице.
 * Шаги 3 (доставка) и 4 (контакты и оплата) добавляются следующим этапом.
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
            : ($plan?->scenario !== Plan::SCENARIO_ANY ? $plan?->scenario : null);

        $pricing = Pricing::table();
        $budget = $plan ? (int) $plan->price : 3000;

        return view('wizard.index', [
            'plan' => $plan,
            'frequency' => $frequency,
            'for' => $for,
            'pricing' => $pricing,
            'budget' => $budget,
            'firstBouquetDate' => HomeController::nextSaturday(),
            'wizardJson' => json_encode(
                ['pricing' => $pricing, 'frequency' => $frequency, 'budget' => $budget, 'planId' => $plan?->id, 'for' => $for],
                JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            ),
        ]);
    }
}
