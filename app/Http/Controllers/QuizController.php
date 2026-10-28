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
use App\Services\DeliverySchedule;
use App\Services\Pricing;
use App\Services\Settings;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Квиз, второй дизайн сайта: главная и пять вопросов на одной странице.
 * Оформление без входа по коду: после оплаты клиент получает ссылку в кабинет в СМС.
 */
class QuizController extends Controller {
    private const MESSAGES = [
        'required' => 'Заполните это поле.',
        'min' => 'Слишком коротко.',
        'max' => 'Слишком длинно.',
        'integer' => 'Нужно число.',
        'in' => 'Недопустимое значение.',
        'exists' => 'Такого варианта нет.',
        'address.min' => 'Укажите улицу и дом.',
        'sender_name.min' => 'Как к вам обращаться?',
        'budget.min' => 'Минимальный бюджет 990 ₽.',
        'budget.max' => 'Максимальный бюджет 50 000 ₽.',
        'frequency.in' => 'Выберите, как часто привозить.',
        'who.in' => 'Выберите, кому привозить.',
        'plan_id.required_without' => 'Выберите букет или назовите бюджет.',
        'budget.required_without' => 'Выберите букет или назовите бюджет.',
    ];

    public function index(Request $request) {
        $user = $request->user();
        // В квизе тарифы идут по цене: от «Мини» к «Букету XL», метки «наш выбор» и «самый большой» привязаны к позиции
        $plans = Plan::showcase()->get()->sortBy([['price', 'asc'], ['id', 'asc']])->values()->map(fn (Plan $plan) => [
            'id' => $plan->id,
            'name' => $plan->name,
            'nameGift' => $plan->nameFor(Plan::SCENARIO_GIFT),
            'price' => (float) $plan->price,
            'about' => $plan->tag ?: $plan->description,
            'image' => $plan->imageUrl(),
        ])->values();

        $additions = PlanOption::additions()->get()->map(fn (PlanOption $o) => [
            'id' => $o->id,
            'name' => $o->name,
            'price' => (float) $o->price,
            'every' => (bool) $o->is_every_delivery,
            'about' => $o->description,
            'image' => $o->imageUrl(),
        ])->values();

        $windows = TimeDelivery::query()->orderBy('from')->get()
            ->map(fn ($w) => ['id' => $w->id, 'label' => substr($w->from, 0, 5) . '–' . substr($w->to, 0, 5), 'from' => substr($w->from, 0, 5), 'to' => substr($w->to, 0, 5)])
            ->values();

        $data = [
            'pricing' => Pricing::table(),
            'plans' => $plans,
            'additions' => $additions,
            'windows' => $windows,
            'minLeadDays' => DeliverySchedule::MIN_LEAD_DAYS,
            'city' => City::active()->value('name'),
            'user' => $user ? ['name' => $user->name, 'phone' => Phone::pretty($user->phone_e164 ?: $user->phone)] : null,
            'urls' => ['store' => route('quiz.store'), 'profile' => route('profile'), 'agreement' => route('user_agreement')],
        ];

        return view('quiz.index', [
            'design' => $request->attributes->get('design', Settings::DESIGN_QUIZ),
            'plans' => $plans,
            'additions' => $additions,
            'windows' => $windows,
            'city' => $data['city'],
            'user' => $user,
            'quizJson' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
        ]);
    }

    /** Шаг 5 квиза: создать подписку без входа и вернуть ссылку на оплату. */
    public function store(Request $request) {
        $data = $request->validate([
            'who' => ['required', Rule::in([Plan::SCENARIO_HOME, Plan::SCENARIO_GIFT])],
            'frequency' => ['required', Rule::in(Pricing::frequencies())],
            'plan_id' => 'required_without:budget|nullable|integer',
            'budget' => 'required_without:plan_id|nullable|integer|min:990|max:50000',
            'option_ids' => 'nullable|array|max:10',
            'option_ids.*' => ['integer', Rule::exists('plan_options', 'id')],
            'card_text' => 'nullable|string|max:300',
            'wishes' => 'nullable|string|max:500',
            'receiving_name' => 'nullable|string|max:120',
            'receiving_phone' => 'nullable|string|max:32',
            'hide_sender' => 'nullable|boolean',
            'agree_with_recipient' => 'nullable|boolean',
            'address' => 'required|string|min:5|max:255',
            'address_fias' => 'nullable|string|max:40',
            'apartment' => 'nullable|string|max:20',
            'entrance' => 'nullable|string|max:20',
            'intercom' => 'nullable|string|max:20',
            'comment' => 'nullable|string|max:500',
            'delivery_day' => 'required|integer|min:1|max:7',
            'time_delivery_id' => ['nullable', 'integer', Rule::exists('time_deliveries', 'id')],
            'sender_name' => 'required|string|min:2|max:120',
            'sender_phone' => 'required|string|max:32',
        ], self::MESSAGES);

        $senderPhone = Phone::normalize($data['sender_phone']);
        if (!$senderPhone) {
            return response()->json(['ok' => false, 'errors' => ['sender_phone' => ['В номере не хватает цифр. Пример: +7 924 000-00-00']], 'message' => 'Проверьте ваш телефон.'], 422);
        }
        // Адрес в городе доставки и с номером дома: проверка по DaData, если ключ задан
        $check = \App\Services\AddressCheck::verify($data['address'], $data['address_fias'] ?? null);
        if ($problem = \App\Services\AddressCheck::problem($check)) {
            return response()->json(['ok' => false, 'errors' => ['address' => [$problem]], 'message' => $problem], 422);
        }
        if ($check['status'] === \App\Services\AddressCheck::OK && $check['address']) {
            $data['address'] = $check['address'];
        }

        $isGift = $data['who'] === Plan::SCENARIO_GIFT;
        $receivingPhone = $isGift && !empty($data['receiving_phone']) ? Phone::normalize($data['receiving_phone']) : null;
        if ($isGift && !empty($data['receiving_phone']) && !$receivingPhone) {
            return response()->json(['ok' => false, 'errors' => ['receiving_phone' => ['Телефон в формате +7 924 000-00-00.']], 'message' => 'Проверьте телефон получателя.'], 422);
        }

        // Клиент по номеру телефона: существующий или новый. Номера сотрудников для заказов закрыты.
        $user = AuthCodeController::findOrCreateClient($senderPhone, $data['sender_name']);
        if (!$user || !$user->is_active) {
            return response()->json(['ok' => false, 'message' => 'С этого номера оформить подписку нельзя. Напишите нам, поможем.'], 422);
        }

        if (!empty($data['plan_id'])) {
            $plan = Plan::showcase()->whereKey($data['plan_id'])->first();
            if (!$plan) {
                return response()->json(['ok' => false, 'message' => 'Этот букет больше недоступен. Выберите другой.'], 422);
            }
        } else {
            $plan = Plan::create([
                'name' => 'Свой бюджет',
                'price' => $data['budget'],
                'description' => 'Собрана в квизе',
                'is_active' => true,
                'is_custom' => true,
                'scenario' => $data['who'],
                'city_id' => City::active()->value('id'),
            ]);
        }

        $additions = !empty($data['option_ids']) ? PlanOption::additions()->whereIn('id', $data['option_ids'])->pluck('id')->all() : [];
        if ($additions) {
            if ($plan->is_custom) {
                $plan->options()->sync($additions);
            } else {
                $copy = $plan->replicate(['sort']);
                $copy->is_custom = true;
                $copy->is_active = true;
                $copy->save();
                $copy->options()->sync(array_unique(array_merge($plan->options()->pluck('plan_options.id')->all(), $additions)));
                $plan = $copy;
            }
        }

        $addressParts = array_filter([
            trim($data['address']),
            !empty($data['apartment']) ? 'кв. ' . trim($data['apartment']) : null,
            !empty($data['entrance']) ? 'подъезд ' . trim($data['entrance']) : null,
            !empty($data['intercom']) ? 'домофон ' . trim($data['intercom']) : null,
        ]);

        [$variant, $visitor, $design] = AssignDesign::current($request);

        $subscription = Subscription::create([
            'is_active' => false,
            'plan_id' => $plan->id,
            'user_id' => $user->id,
            'time_delivery_id' => $data['time_delivery_id'] ?? TimeDelivery::query()->orderBy('from')->value('id'),
            'delivery_day' => $data['delivery_day'],
            'sender_name' => $data['sender_name'],
            'sender_phone' => $senderPhone,
            'receiving_name' => $isGift && !empty($data['receiving_name']) ? $data['receiving_name'] : $data['sender_name'],
            'receiving_phone' => $receivingPhone ?: $senderPhone,
            'address' => implode(', ', $addressParts),
            'frequency' => $data['frequency'],
            'comment' => $data['comment'] ?? null,
            'preferences' => [
                'for' => $data['who'],
                'card_text' => $data['card_text'] ?? null,
                'wishes' => $data['wishes'] ?? null,
                'hide_sender' => $isGift && !empty($data['hide_sender']),
                'agree_with_recipient' => $isGift && !empty($data['agree_with_recipient']),
            ],
            'using_promo' => true,
            'ab_variant' => $variant ?: 'q',
            'ab_visitor' => $visitor,
            'ab_design' => $design ?: Settings::DESIGN_QUIZ,
            'source' => Subscription::SOURCE_QUIZ,
        ]);

        Log::channel('shop')->info('Создана подписка из квиза.', [
            'subscription_id' => $subscription->id,
            'plan_id' => $plan->id,
            'frequency' => $subscription->frequency,
            'first_payment' => $subscription->firstPaymentAmount(),
        ]);

        if ($visitor) {
            AbEvent::log($variant ?: 'q', 'payment_redirect', $visitor, ['plan_id' => $plan->id, 'frequency' => $subscription->frequency, 'design' => $design ?: Settings::DESIGN_QUIZ]);
        }
        SyncAmoLead::dispatch($subscription->id, SyncAmoLead::CREATED)->afterResponse();

        $paymentLink = PaymentController::create($subscription);
        if (!filter_var($paymentLink, FILTER_VALIDATE_URL)) {
            return response()->json(['ok' => false, 'message' => 'Не удалось создать оплату, попробуйте ещё раз.'], 400);
        }

        return response()->json(['ok' => true, 'subscription_id' => $subscription->id, 'payment_url' => $paymentLink, 'amount' => $subscription->firstPaymentAmount()], 201);
    }
}
