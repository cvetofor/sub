<?php

namespace App\Http\Api;

use App\Http\Controllers\AuthCodeController;
use App\Models\AbEvent;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\DeliverySchedule;
use App\Services\Notifier;
use App\Services\TeamNotifier;
use App\Support\Phone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use YooKassa\Client;
use YooKassa\Model\Notification\NotificationEventType;

class Yookassa {
    /** Метка платежей нового сайта в metadata. Уведомления без неё принадлежат старому сайту. */
    public const SITE_TAG = 'v2';

    /** Временная метка в payment_gateway_transaction до ответа ЮKassa. */
    private const RECURRING_ATTEMPT_PREFIX = 'recurrent-attempt:';

    private static $client;

    public function __construct() {
        self::$client = new Client();
        if (self::isConfigured()) {
            self::$client->setAuth(config('yookassa.shopId'), config('yookassa.secretKey'));
        }
    }

    /** Ключи заданы в окружении. Без них платежи не создаются, на тестовом стенде работает имитация. */
    public static function isConfigured(): bool {
        return (bool) config('yookassa.shopId') && (bool) config('yookassa.secretKey');
    }

    /** Первый платёж по подписке: сумма первого месяца с подарочным букетом и разовыми опциями. */
    public function createPayment(?int $subId = null) {
        try {
            $sub = Subscription::with('plan.options')->find($subId);

            if (!isset($sub)) {
                throw new Exception("Подписка не найдена. \$subId=$subId");
            }

            $amount = number_format($sub->firstPaymentAmount(), 2, '.', '');
            if ((float) $amount <= 0) {
                throw new Exception("Нулевая сумма платежа по подписке #{$sub->id}");
            }

            $idempotenceKey = uniqid('', true);
            $response = self::$client->createPayment(
                [
                    'amount' => [
                        'value' => $amount,
                        'currency' => 'RUB',
                    ],
                    'confirmation' => [
                        'type' => 'redirect',
                        'locale' => 'ru_RU',
                        // Подписанный адрес: клиент из квиза ещё не входил в кабинет, подпись даёт право увидеть итог
                        'return_url' => \Illuminate\Support\Facades\URL::temporarySignedRoute('payment.yookassa.redirect', now()->addDays(3), ['subscription_id' => $subId]),
                    ],
                    'capture' => true,
                    'save_payment_method' => true,
                    'description' => 'Оплата подписки #' . $sub->id,
                    'metadata' => [
                        'sub_id' => $sub->id,
                        'is_reccurent' => '0',
                        'site' => self::SITE_TAG,
                    ],
                    'receipt' => [
                        'customer' => [
                            'full_name' => $sub->sender_name,
                            'phone' => preg_replace('/\D+/', '', $sub->sender_phone),
                        ],
                        'items' => [
                            [
                                'description' => 'Подписка «' . $sub->plan->name . '», первый месяц',
                                'quantity' => '1.00',
                                'amount' => [
                                    'value' => $amount,
                                    'currency' => 'RUB',
                                ],
                                'vat_code' => '7',
                                'payment_mode' => 'full_payment',
                                'payment_subject' => 'commodity',
                            ],
                        ],
                    ],
                ],
                $idempotenceKey
            );

            return $response;
        } catch (\Exception $e) {
            Log::error('Ошибка при создании платежа', [
                'subscription_id' => $subId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Постоянный ключ одной попытки списания. Повтор того же запроса с этим
     * ключом вернёт в ЮKassa ту же операцию, а не создаст новое списание.
     */
    public static function recurringIdempotenceKey(Subscription $subscription): string {
        $dueAt = $subscription->next_date_payment?->copy()->utc()->format('Y-m-d\\TH:i:s\\Z') ?? 'unknown';

        return hash('sha256', "recurring:{$subscription->id}:{$dueAt}");
    }

    /** Создаёт запись о попытке до обращения в ЮKassa. */
    public static function startRecurringPaymentAttempt(Subscription $subscription, string $recurrentToken): Payment {
        $idempotenceKey = self::recurringIdempotenceKey($subscription);

        return DB::transaction(function () use ($subscription, $recurrentToken, $idempotenceKey) {
            $lockedSubscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);
            $existing = $lockedSubscription->payments()
                ->where('payment_status_id', Payment::IN_PROGRESS)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $attempt = Payment::create([
                'payment_status_id' => Payment::IN_PROGRESS,
                'amount' => $lockedSubscription->monthlyAmount(),
                'payment_gateway_transaction' => self::RECURRING_ATTEMPT_PREFIX . $idempotenceKey,
                'idempotence_key' => $idempotenceKey,
                'recurrent_token' => $recurrentToken,
            ]);
            $lockedSubscription->payments()->attach($attempt->id);

            return $attempt;
        });
    }

    /** Ответ ЮKassa ещё не получен: такой запрос можно безопасно повторить с тем же ключом. */
    public static function isUnsentRecurringAttempt(Payment $payment): bool {
        return str_starts_with((string) $payment->payment_gateway_transaction, self::RECURRING_ATTEMPT_PREFIX);
    }

    /** Повторное списание: обычный месяц без подарка. */
    public function recurrentPayment(string $token, Subscription $sub, string $idempotenceKey) {
        $response = self::$client->createPayment(
            [
                'amount' => [
                    'value' => number_format($sub->monthlyAmount(), 2, '.', ''),
                    'currency' => 'RUB',
                ],
                'capture' => true,
                'payment_method_id' => $token,
                'description' => 'Ежемесячное списание за подписку #' . $sub->id,
                'metadata' => [
                    'sub_id' => $sub->id,
                    'is_reccurent' => '1',
                    'site' => self::SITE_TAG,
                ],
            ],
            $idempotenceKey
        );

        return $response;
    }

    /** Сохраняет номер операции ЮKassa, пока банк подтверждает списание. */
    public static function saveRecurringPaymentInProgress(Subscription $subscription, object $paymentYookassa, string $fallbackToken): void {
        DB::transaction(function () use ($subscription, $paymentYookassa, $fallbackToken) {
            $lockedSubscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);
            $payment = Payment::query()
                ->where('idempotence_key', self::recurringIdempotenceKey($lockedSubscription))
                ->lockForUpdate()
                ->firstOrFail();

            $payment->update([
                'payment_gateway_transaction' => $paymentYookassa->id,
                'amount' => $paymentYookassa->amount->value,
                'recurrent_token' => $paymentYookassa->payment_method->id ?? $fallbackToken,
            ]);
            $lockedSubscription->payments()->syncWithoutDetaching([$payment->id]);
        });
    }

    /**
     * Подтверждает одно повторное списание ровно один раз. Возвращает запись
     * платежа только тому вызову, который действительно продлил подписку.
     */
    public static function completeRecurringPayment(Subscription $subscription, string $transactionId, mixed $amount, ?string $recurrentToken): ?Payment {
        return DB::transaction(function () use ($subscription, $transactionId, $amount, $recurrentToken) {
            $lockedSubscription = Subscription::query()->lockForUpdate()->find($subscription->id);
            if (!$lockedSubscription) {
                return null;
            }

            $payment = Payment::query()->where('payment_gateway_transaction', $transactionId)->lockForUpdate()->first()
                ?? $lockedSubscription->payments()->where('payment_status_id', Payment::IN_PROGRESS)->lockForUpdate()->first();

            if ($payment && (int) $payment->payment_status_id === Payment::PAYED) {
                return null;
            }

            if (!$payment) {
                $payment = Payment::create([
                    'payment_status_id' => Payment::PAYED,
                    'amount' => $amount,
                    'payment_gateway_transaction' => $transactionId,
                    'recurrent_token' => $recurrentToken,
                ]);
            } else {
                $payment->update([
                    'payment_status_id' => Payment::PAYED,
                    'amount' => $amount,
                    'payment_gateway_transaction' => $transactionId,
                    'recurrent_token' => $recurrentToken ?: $payment->recurrent_token,
                ]);
            }

            $lockedSubscription->payments()->syncWithoutDetaching([$payment->id]);
            $lockedSubscription->is_active = true;
            $lockedSubscription->pause_reason = null;
            $lockedSubscription->next_date_payment = \Carbon\Carbon::parse($lockedSubscription->next_date_payment ?? now())->addMonth();
            $lockedSubscription->save();

            return $payment->fresh();
        });
    }

    /** Отменяет сохранённую попытку и ставит активную подписку на паузу. */
    public static function cancelRecurringPayment(Subscription $subscription, string $transactionId): bool {
        return DB::transaction(function () use ($subscription, $transactionId) {
            $lockedSubscription = Subscription::query()->lockForUpdate()->find($subscription->id);
            if (!$lockedSubscription) {
                return false;
            }

            $payment = Payment::query()->where('payment_gateway_transaction', $transactionId)->lockForUpdate()->first()
                ?? $lockedSubscription->payments()->where('payment_status_id', Payment::IN_PROGRESS)->lockForUpdate()->first();
            if ($payment) {
                $payment->update(['payment_status_id' => Payment::CANCELED, 'payment_gateway_transaction' => $transactionId]);
            }

            if (!$lockedSubscription->is_active) {
                return false;
            }

            $lockedSubscription->update(['is_active' => false, 'pause_reason' => Subscription::PAUSE_PAYMENT]);

            return true;
        });
    }

    public function callback(Request $request) {
        // Сначала проверяем отправителя, потом разбираем тело
        if (!self::$client->isNotificationIPTrusted($request->ip())) {
            Log::error('Уведомление с недоверенного IP.', ['IP' => $request->ip()]);

            return response()->noContent(403);
        }

        try {
            $data = $request->json()->all();

            // У магазина ЮKassa один адрес уведомлений. Пока старый сайт работает рядом,
            // его платежи (без метки site в metadata) пересылаем ему как есть.
            if (($data['object']['metadata']['site'] ?? null) !== self::SITE_TAG) {
                return self::forwardToLegacy($request);
            }

            // События, которых SDK не знает (например payment_method.active), подтверждаем без разбора
            $rawEvent = (string) ($data['event'] ?? '');
            if (!NotificationEventType::valueExists($rawEvent)) {
                Log::channel('shop')->info('Уведомление ЮKassa без действий.', ['event' => $rawEvent, 'object' => $data['object']['id'] ?? null]);

                return response()->noContent(200);
            }

            $factory = new \YooKassa\Model\Notification\NotificationFactory();
            $notificationObj = $factory->factory($data);
            $responseObj = $notificationObj->getObject();

            $event = $notificationObj->getEvent();
            $isRecurrent = ($responseObj->getMetadata()->is_reccurent ?? '0') === '1';
            $subId = (int) ($responseObj->getMetadata()->sub_id ?? 0);
            $transactionId = $responseObj->id;
            $subscription = Subscription::with('plan.options')->find($subId);

            if ($event === NotificationEventType::PAYMENT_SUCCEEDED && !$isRecurrent) {
                // Первая оплата: подтверждаем платёж, активируем подписку
                $recurrentToken = $responseObj->getPaymentMethod()?->saved ? $responseObj->getPaymentMethod()->id : '';
                Payment::where('payment_gateway_transaction', $transactionId)
                    ->update(['payment_status_id' => Payment::PAYED, 'recurrent_token' => $recurrentToken]);
                if ($subscription) {
                    self::activate($subscription);
                    Log::channel('shop')->info('Оплачена подписка.', ['subscription_id' => $subscription->id, 'payment' => $transactionId]);
                }
            } elseif ($event === NotificationEventType::PAYMENT_SUCCEEDED && $isRecurrent) {
                // Повторное списание подтверждаем идемпотентно: уведомление можно прислать повторно.
                if ($subscription && ($payment = self::completeRecurringPayment(
                    $subscription,
                    $transactionId,
                    $responseObj->getAmount()->getValue(),
                    $responseObj->getPaymentMethod()?->id,
                ))) {
                    $subscription->refresh()->load('plan.options');
                    DeliverySchedule::planNextMonth($subscription);
                    Notifier::renewed($subscription, (float) $payment->amount);
                    Log::channel('shop')->info('Подписка продлена по уведомлению.', ['subscription_id' => $subscription->id, 'payment' => $transactionId]);
                }
            } elseif ($event === NotificationEventType::PAYMENT_CANCELED) {
                Payment::where('payment_gateway_transaction', $transactionId)->update(['payment_status_id' => Payment::CANCELED]);
                if ($isRecurrent && $subscription && self::cancelRecurringPayment($subscription, $transactionId)) {
                    // Повторное списание не прошло: пауза и сообщения клиенту и команде
                    $subscription->refresh()->load('plan.options');
                    Notifier::paymentFailed($subscription, $subscription->monthlyAmount());
                    TeamNotifier::paymentFailed($subscription, $subscription->monthlyAmount());
                }
                Log::channel('shop')->info('Платёж отменён.', ['subscription_id' => $subId, 'payment' => $transactionId, 'recurrent' => $isRecurrent, 'reason' => $responseObj->getCancellationDetails()?->getReason()]);
            } else {
                // payment_method.active и прочие события: только журнал
                Log::channel('shop')->info('Уведомление ЮKassa без действий.', ['event' => $event, 'payment' => $transactionId, 'subscription_id' => $subId]);
            }

            return response()->noContent(200);
        } catch (\Exception $e) {
            Log::error('Ошибка во время обработки уведомления ЮКассы.', [
                'message' => $e->getMessage(),
            ]);

            return response()->noContent(400);
        }
    }

    /** Пересылка уведомления старому сайту. Без настроенного адреса отвечаем 200, чтобы ЮKassa не повторяла. */
    private static function forwardToLegacy(Request $request) {
        $url = config('yookassa.legacy_callback');
        if (!$url) {
            Log::channel('shop')->info('Уведомление ЮKassa не для этого сайта, адрес пересылки не задан.', ['payment' => $request->json('object.id')]);

            return response()->noContent(200);
        }
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)
                ->withBody($request->getContent(), 'application/json')
                ->post($url);
            Log::channel('shop')->info('Уведомление ЮKassa переслано старому сайту.', ['payment' => $request->json('object.id'), 'status' => $response->status()]);

            return response()->noContent($response->successful() ? 200 : 502);
        } catch (\Throwable $e) {
            Log::channel('shop')->error('Не удалось переслать уведомление ЮKassa старому сайту.', ['message' => $e->getMessage()]);

            return response()->noContent(502);
        }
    }

    /** Общее для вебхука и тестовой оплаты: активировать подписку, привязать пользователя, записать в воронку. */
    public static function activate(Subscription $subscription): void {
        $wasActive = (bool) $subscription->is_active;
        $subscription->is_active = true;
        $subscription->pause_reason = null;
        $subscription->next_date_payment = \Carbon\Carbon::now()->addMonth();

        if (!$subscription->user_id) {
            $phone = Phone::normalize($subscription->sender_phone);
            if ($phone) {
                $subscription->user_id = AuthCodeController::findOrCreateClient($phone, $subscription->sender_name)?->id;
            }
        }
        $subscription->save();

        // Первая оплата: расписание доставок первого месяца и СМС клиенту
        if (!$wasActive && !$subscription->deliveries()->exists()) {
            DeliverySchedule::planFirstMonth($subscription);
            Notifier::subscriptionActivated($subscription);
            TeamNotifier::newSubscription($subscription, $subscription->firstPaymentAmount());
            \App\Jobs\SyncAmoLead::dispatch($subscription->id, \App\Jobs\SyncAmoLead::PAID, $subscription->firstPaymentAmount())->afterResponse();
        }

        if (!empty($subscription->ab_variant) && !empty($subscription->ab_visitor)) {
            AbEvent::log($subscription->ab_variant, 'payment_success', $subscription->ab_visitor, [
                'plan_id' => $subscription->plan_id,
                'frequency' => $subscription->frequency,
                'design' => $subscription->ab_design,
            ]);
        }
    }
}
