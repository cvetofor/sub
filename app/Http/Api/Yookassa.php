<?php

namespace App\Http\Api;

use App\Http\Controllers\AuthController;
use App\Models\Payment;
use App\Models\Subscription;
use Exception;
use YooKassa\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Yookassa {
    private static $client;

    public function __construct() {
        self::$client = new Client();
        self::$client->setAuth(config('yookassa.shopId'), config('yookassa.secretKey'));
    }

    public function createPayment(?int $subId = null) {
        try {
            $sub = Subscription::find($subId);

            if (!isset($sub)) {
                throw new Exception("Подписка не найдена. \$subId=$subId");
            }

            $amount = $sub->is_custom ? $sub->totalAmount() : $sub->plan->price * \App\Enums\Frequency::getFrequencyElem($sub->frequency)['count'];

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
                    "save_payment_method" => true,
                    'description' => 'Оплата подписки #' . $sub->id,
                    'metadata' => [
                        'sub_id' => $sub->id,
                        'is_reccurent' => '0'
                    ],
                    'receipt' => [
                        'customer' => [
                            'full_name' => $sub->sender_name,
                            'phone' => preg_replace('/\D+/', '', $sub->sender_phone),
                        ],
                        'items' => [
                            [
                                'description' => 'Подписка "' . $sub->plan->name . '"',
                                'quantity' => '1.00',
                                'amount' => [
                                    'value' => $amount,
                                    'currency' => 'RUB'
                                ],
                                'vat_code' => '7',
                                'payment_mode' => 'full_payment',
                                'payment_subject' => 'commodity',
                            ],
                        ]
                    ]
                ],
                $idempotenceKey
            );

            return $response;
        } catch (\Exception $e) {
            Log::error('Ошибка при создании платежа', [
                'subscription_id' => $subId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    public function recurrentPayment($token, $sub) {
        $idempotenceKey = uniqid('', true);
        $response = self::$client->createPayment(
            array(
                'amount' => array(
                    'value' => $sub->totalAmount(),
                    'currency' => 'RUB',
                ),
                'capture' => true,
                'payment_method_id' => $token,
                'description' => 'Рекурентное списание за подписку #"' . $sub->id . '"',
                'metadata' => [
                    'is_reccurent' => '1'
                ],
            ),
            $idempotenceKey
        );

        return $response;
    }

    public function callback(Request $request) {
        try {
            $data = $request->json()->all();
            $factory = new \YooKassa\Model\Notification\NotificationFactory();
            $notificationObj = $factory->factory($data);
            $responseObj = $notificationObj->getObject();

            if (!self::$client->isNotificationIPTrusted($request->ip())) {
                Log::error('Уведомление с недоверенного IP.', ['IP' => $request->ip()]);
                return response()->noContent(403);
            }

            if ($responseObj->getMetadata()->is_reccurent !== "1") {
                if ($notificationObj->getEvent() === \YooKassa\Model\Notification\NotificationEventType::PAYMENT_SUCCEEDED) {
                    $subId = $responseObj->getMetadata()->sub_id;
                    $transactionId = $responseObj->id;
                    $recurrentToken = $responseObj->getPaymentMethod()->saved ? $responseObj->getPaymentMethod()->id : '';
                    Payment::where('payment_gateway_transaction', $transactionId)
                        ->update([
                            'payment_status_id' => Payment::PAYED,
                            'recurrent_token' => $recurrentToken
                        ]);

                    $subscription =  Subscription::find($subId);
                    if ($subscription) {
                        $subscription->is_active = true;
                        $subscription->next_date_payment = \Carbon\Carbon::now()->addMonth();
                        $subscription->save();

                        Log::channel('shop')->info('Оплачена подписка.', [$responseObj]);
                    }

                    $authController = new AuthController();
                    $_ = $authController->registerUser($subscription->sender_phone, $subscription->sender_name);

                    return response()->noContent(200);
                }
            }
        } catch (\Exception $e) {
            Log::error('Ошибка во время обработки уведомления ЮКассы.', [
                'message' => $e->getMessage()
            ]);

            return response()->noContent(400);
        }
    }
}
