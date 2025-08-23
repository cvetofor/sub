<?php

namespace App\Http\Api;

use App\Models\Plan;
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

            $idempotenceKey = uniqid('', true);
            $response = self::$client->createPayment(
                [
                    'amount' => [
                        'value' => $sub->totalAmount(),
                        'currency' => 'RUB',
                    ],
                    'confirmation' => [
                        'type' => 'redirect',
                        'locale' => 'ru_RU',
                        'return_url' => url('/'),
                    ],
                    'capture' => true,
                    'description' => 'Оплата подписки #' . $sub->id,
                    'metadata' => [
                        'sub_id' => $sub->id
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
                                    'value' => $sub->totalAmount(),
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
            // Log::info();
        }
    }

    public function callback(Request $request) {
        $data = $request->json()->all();

        $factory = new \YooKassa\Model\Notification\NotificationFactory();
        $notificationObj = $factory->factory($data);
        $responseObj = $notificationObj->getObject();
        Log::info('DEBUG', [$responseObj]);
        Log::info('DEBUG', [$responseObj->getMetadata()]);

        if (!self::$client->isNotificationIPTrusted($request->ip())) {
            return response('IP not trusted', 403);
        }

        if ($notificationObj->getEvent() === \YooKassa\Model\Notification\NotificationEventType::PAYMENT_SUCCEEDED) {
            $subId = $responseObj->getMetadata();
        }
    }
}
