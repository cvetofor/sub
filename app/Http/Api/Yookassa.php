<?php

namespace App\Http\Api;

use App\Http\Controllers\AuthCodeController;
use App\Models\AbEvent;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\DeliverySchedule;
use App\Services\Notifier;
use App\Support\Phone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use YooKassa\Client;

class Yookassa {
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
                        'return_url' => route('payment.yookassa.redirect', ['subscription_id' => $subId]),
                    ],
                    'capture' => true,
                    'save_payment_method' => true,
                    'description' => 'Оплата подписки #' . $sub->id,
                    'metadata' => [
                        'sub_id' => $sub->id,
                        'is_reccurent' => '0',
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

    /** Повторное списание: обычный месяц без подарка. */
    public function recurrentPayment($token, $sub) {
        $idempotenceKey = uniqid('', true);
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
                ],
            ],
            $idempotenceKey
        );

        return $response;
    }

    public function callback(Request $request) {
        // Сначала проверяем отправителя, потом разбираем тело
        if (!self::$client->isNotificationIPTrusted($request->ip())) {
            Log::error('Уведомление с недоверенного IP.', ['IP' => $request->ip()]);

            return response()->noContent(403);
        }

        try {
            $data = $request->json()->all();
            $factory = new \YooKassa\Model\Notification\NotificationFactory();
            $notificationObj = $factory->factory($data);
            $responseObj = $notificationObj->getObject();

            if ($responseObj->getMetadata()->is_reccurent !== '1'
                && $notificationObj->getEvent() === \YooKassa\Model\Notification\NotificationEventType::PAYMENT_SUCCEEDED) {
                $subId = $responseObj->getMetadata()->sub_id;
                $transactionId = $responseObj->id;
                $recurrentToken = $responseObj->getPaymentMethod()->saved ? $responseObj->getPaymentMethod()->id : '';
                Payment::where('payment_gateway_transaction', $transactionId)
                    ->update([
                        'payment_status_id' => Payment::PAYED,
                        'recurrent_token' => $recurrentToken,
                    ]);

                $subscription = Subscription::find($subId);
                if ($subscription) {
                    self::activate($subscription);
                    Log::channel('shop')->info('Оплачена подписка.', ['subscription_id' => $subscription->id, 'payment' => $transactionId]);
                }
            }

            return response()->noContent(200);
        } catch (\Exception $e) {
            Log::error('Ошибка во время обработки уведомления ЮКассы.', [
                'message' => $e->getMessage(),
            ]);

            return response()->noContent(400);
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
        }

        if (!empty($subscription->ab_variant) && !empty($subscription->ab_visitor)) {
            AbEvent::log($subscription->ab_variant, 'payment_success', $subscription->ab_visitor, [
                'plan_id' => $subscription->plan_id,
                'frequency' => $subscription->frequency,
            ]);
        }
    }
}
