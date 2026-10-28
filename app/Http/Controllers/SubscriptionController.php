<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AssignDesign;
use App\Jobs\SyncAmoLead;
use App\Models\AbEvent;
use App\Models\City;
use App\Models\Plan;
use App\Models\PlanOption;
use App\Models\Subscription;
use App\Models\TimeDelivery;
use App\Models\Delivery;
use App\Services\DeliverySchedule;
use App\Services\Pricing;
use App\Services\TeamNotifier;
use App\Support\Phone;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller {
    /** Тексты ошибок валидации для мастера */
    private const MESSAGES = [
        'required' => 'Заполните это поле.',
        'required_without' => 'Укажите бюджет букета.',
        'min' => 'Слишком коротко.',
        'max' => 'Слишком длинно.',
        'integer' => 'Нужно число.',
        'in' => 'Недопустимое значение.',
        'exists' => 'Такого варианта нет.',
        'address.min' => 'Укажите адрес: улица, дом, квартира.',
        'sender_name.min' => 'Как к вам обращаться?',
        'budget.min' => 'Минимальный бюджет 1 000 ₽.',
        'budget.max' => 'Максимальный бюджет 50 000 ₽.',
        'frequency.in' => 'Выберите частоту доставки.',
    ];

    /**
     * Шаг 4 мастера: создать подписку и вернуть ссылку на оплату.
     * Пользователь уже вошёл по коду из СМС (middleware auth).
     */
    public function store(Request $request) {
        $data = $request->validate([
            'plan_id' => 'nullable|integer',
            'budget' => 'required_without:plan_id|nullable|integer|min:1000|max:50000',
            'frequency' => ['required', Rule::in(Pricing::frequencies())],
            'for' => ['nullable', Rule::in([Plan::SCENARIO_HOME, Plan::SCENARIO_GIFT])],
            'styles' => 'nullable|array|max:4',
            'styles.*' => 'string|max:40',
            'tags' => 'nullable|array|max:8',
            'tags.*' => 'string|max:40',
            'address' => 'required|string|min:5|max:255',
            'delivery_day' => 'required|integer|min:1|max:7',
            'time_delivery_id' => ['nullable', 'integer', Rule::exists('time_deliveries', 'id')],
            'option_ids' => 'nullable|array|max:10',
            'option_ids.*' => ['integer', Rule::exists('plan_options', 'id')],
            'sender_name' => 'required|string|min:2|max:120',
            'receiving_name' => 'nullable|string|min:2|max:120',
            'receiving_phone' => 'nullable|string|max:32',
            'card_text' => 'nullable|string|max:300',
            'comment' => 'nullable|string|max:500',
        ], self::MESSAGES);

        $user = $request->user();
        $senderPhone = $user->phone_e164 ?: Phone::normalize($user->phone);
        if (!$senderPhone) {
            return response()->json(['ok' => false, 'message' => 'У аккаунта нет подтверждённого телефона.'], 422);
        }

        $receivingPhone = isset($data['receiving_phone']) ? Phone::normalize($data['receiving_phone']) : null;
        if (!empty($data['receiving_phone']) && !$receivingPhone) {
            return response()->json(['ok' => false, 'message' => 'Проверьте телефон получателя.'], 422);
        }

        if (!empty($data['plan_id'])) {
            // Только витринные планы: активные и не пользовательские копии
            $plan = Plan::showcase()->whereKey($data['plan_id'])->first();
            if (!$plan) {
                return response()->json(['ok' => false, 'message' => 'Этот план больше недоступен. Выберите другой на главной.'], 422);
            }
        } else {
            $city = City::active()->first();
            $plan = Plan::create([
                'name' => 'Своя подписка',
                'price' => $data['budget'],
                'description' => 'Собрана в мастере',
                'is_active' => true,
                'is_custom' => true,
                'scenario' => $data['for'] ?? Plan::SCENARIO_ANY,
                'city_id' => $city?->id,
            ]);
        }

        // Дополнения (ваза, конфеты и т. п.) привязываются к плану подписки. У готового плана они дополняют состав.
        if (!empty($data['option_ids'])) {
            $additions = PlanOption::active()->whereIn('id', $data['option_ids'])->pluck('id')->all();
            if ($plan->is_custom) {
                $plan->options()->sync($additions);
            } else {
                $plan = $this->cloneWithOptions($plan, $additions);
            }
        }

        [$variant, $visitor, $design] = AssignDesign::current($request);

        $subscription = Subscription::create([
            'is_active' => false,
            'plan_id' => $plan->id,
            'user_id' => $user->id,
            'time_delivery_id' => $data['time_delivery_id'] ?? TimeDelivery::query()->value('id'),
            'delivery_day' => $data['delivery_day'],
            'sender_name' => $data['sender_name'],
            'sender_phone' => $senderPhone,
            'receiving_name' => $data['receiving_name'] ?? $data['sender_name'],
            'receiving_phone' => $receivingPhone ?: $senderPhone,
            'address' => $data['address'],
            'frequency' => $data['frequency'],
            'comment' => $data['comment'] ?? null,
            'preferences' => [
                'for' => $data['for'] ?? null,
                'styles' => array_values($data['styles'] ?? []),
                'tags' => array_values($data['tags'] ?? []),
                'card_text' => $data['card_text'] ?? null,
            ],
            'using_promo' => true,
            'ab_variant' => $variant,
            'ab_visitor' => $visitor,
            'ab_design' => $design ?? 'classic',
            'source' => Subscription::SOURCE_WIZARD,
        ]);

        if ($user->name === 'Клиент' && $data['sender_name']) {
            $user->forceFill(['name' => $data['sender_name']])->save();
        }

        Log::channel('shop')->info('Создана новая подписка.', [
            'subscription_id' => $subscription->id,
            'plan_id' => $plan->id,
            'plan' => $plan->name,
            'frequency' => $subscription->frequency,
            'first_payment' => $subscription->firstPaymentAmount(),
        ]);

        if ($variant && $visitor) {
            AbEvent::log($variant, 'payment_redirect', $visitor, ['plan_id' => $plan->id, 'frequency' => $subscription->frequency, 'design' => $design]);
        }

        // Заявка в amoCRM сразу, не дожидаясь оплаты: оператор видит, кому позвонить
        SyncAmoLead::dispatch($subscription->id, SyncAmoLead::CREATED)->afterResponse();

        $paymentLink = PaymentController::create($subscription);
        if (!filter_var($paymentLink, FILTER_VALIDATE_URL)) {
            return response()->json(['ok' => false, 'message' => 'Не удалось создать оплату, попробуйте ещё раз.'], 400);
        }

        return response()->json(['ok' => true, 'subscription_id' => $subscription->id, 'payment_url' => $paymentLink, 'amount' => $subscription->firstPaymentAmount()], 201);
    }

    /** Кабинет: изменить адрес, день, окно, получателя, открытку и комментарий. Только владелец. */
    public function updateDelivery(Request $request, Subscription $subscription) {
        abort_unless($subscription->belongsToUser($request->user()), 403);

        // Ошибки в отдельном пакете, чтобы на странице с несколькими подписками подсветилась нужная форма
        $data = Validator::make($request->all(), [
            'address' => 'required|string|min:5|max:255',
            'delivery_day' => 'required|integer|min:1|max:7',
            'time_delivery_id' => ['nullable', 'integer', Rule::exists('time_deliveries', 'id')],
            'receiving_name' => 'nullable|string|min:2|max:120',
            'receiving_phone' => 'nullable|string|max:32',
            'card_text' => 'nullable|string|max:300',
            'comment' => 'nullable|string|max:500',
        ], self::MESSAGES)->validateWithBag('sub' . $subscription->id);

        $receivingPhone = !empty($data['receiving_phone']) ? Phone::normalize($data['receiving_phone']) : null;
        if (!empty($data['receiving_phone']) && !$receivingPhone) {
            return back()->withErrors(['receiving_phone' => 'Телефон в формате +7 912 345-67-89.'], 'sub' . $subscription->id)->withInput();
        }

        $preferences = $subscription->preferences ?? [];
        $preferences['card_text'] = $data['card_text'] ?? null;

        $subscription->update([
            'address' => $data['address'],
            'delivery_day' => $data['delivery_day'],
            'time_delivery_id' => $data['time_delivery_id'] ?? $subscription->time_delivery_id,
            'receiving_name' => ($data['receiving_name'] ?? null) ?: $subscription->sender_name,
            'receiving_phone' => $receivingPhone ?: $subscription->sender_phone,
            'comment' => $data['comment'] ?? null,
            'preferences' => $preferences,
        ]);

        DeliverySchedule::moveToWeekday($subscription);
        TeamNotifier::deliveryChanged($subscription);
        Log::channel('shop')->info('Клиент изменил доставку.', ['subscription_id' => $subscription->id, 'delivery_day' => $data['delivery_day']]);

        return redirect()->route('profile')->with('success', 'Доставка обновлена. Флорист увидит изменения сразу.');
    }

    /** Кабинет: пропустить ближайшую доставку. Букет не сгорает, а переносится в конец. */
    public function skipDelivery(Request $request, Subscription $subscription, Delivery $delivery) {
        abort_unless($subscription->belongsToUser($request->user()), 403);
        abort_unless((int) $delivery->subscription_id === (int) $subscription->id && $delivery->status === Delivery::PLANNED, 404);
        if ($delivery->planned_date->lt(Carbon::today()->addDays(DeliverySchedule::MIN_LEAD_DAYS))) {
            return redirect()->route('profile')->with('error', 'Эту доставку уже собирают, пропустить можно доставку не раньше чем за два дня. Позвоните нам, поможем.');
        }
        DeliverySchedule::skip($delivery);
        TeamNotifier::deliverySkipped($subscription, $delivery);
        Log::channel('shop')->info('Клиент пропустил доставку.', ['subscription_id' => $subscription->id, 'delivery_id' => $delivery->id]);

        return redirect()->route('profile')->with('success', 'Доставка пропущена, букет перенесён в конец расписания.');
    }

    /** Повторная оплата неоплаченной подписки из кабинета: только владелец. */
    public function pay(Request $request, Subscription $subscription) {
        abort_unless($subscription->belongsToUser($request->user()), 403);
        $subscription->load('plan.options');
        $link = PaymentController::create($subscription);
        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            return redirect()->route('profile')->with('error', $link);
        }

        return redirect()->away($link);
    }

    /** Пауза подписки: только владелец. */
    public function pause(Request $request, Subscription $subscription) {
        abort_unless($subscription->belongsToUser($request->user()), 403);
        $subscription->update(['is_active' => false, 'pause_reason' => Subscription::PAUSE_CLIENT]);
        TeamNotifier::paused($subscription);
        Log::channel('shop')->info('Подписка поставлена на паузу клиентом.', ['subscription_id' => $subscription->id]);

        return redirect()->back()->with('success', 'Подписка на паузе. Списаний не будет.');
    }

    /** Возобновление: только владелец и только если подписка когда-то была оплачена. */
    public function resume(Request $request, Subscription $subscription) {
        abort_unless($subscription->belongsToUser($request->user()), 403);
        abort_unless($subscription->payments()->where('payment_status_id', \App\Models\Payment::PAYED)->exists(), 422);
        $subscription->update(['is_active' => true, 'pause_reason' => null]);
        DeliverySchedule::shiftOverdue($subscription);
        Log::channel('shop')->info('Подписка возобновлена клиентом.', ['subscription_id' => $subscription->id]);

        return redirect()->back()->with('success', 'Подписка снова активна.');
    }

    /** Готовый план с выбранными дополнениями: отдельная пользовательская копия, чтобы не менять витринный план. */
    private function cloneWithOptions(Plan $plan, array $optionIds): Plan {
        $copy = $plan->replicate(['sort']);
        $copy->is_custom = true;
        $copy->is_active = true;
        $copy->save();
        $copy->options()->sync(array_unique(array_merge($plan->options()->pluck('plan_options.id')->all(), $optionIds)));

        return $copy;
    }
}
