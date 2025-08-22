<?php

namespace App\Http\Api;

use App\Models\Plan;
use YooKassa\Client;
use Illuminate\Http\Request;

class Yookassa {
    private static $client;

    public function __construct() {
        self::$client = new Client();
        self::$client->setAuth(config('yookassa.shopId'), config('yookassa.secretKey'));
    }

    public function getPaymentLink($planId) {
        try {
            $plan = Plan::find($planId);

            $idempotenceKey = uniqid('', true);
            $response = self::$client->createPayment(
                [
                    'amount' => [
                        'value' => $plan->totalAmount,
                        'currency' => 'RUB',
                    ],
                    'confirmation' => [
                        'type' => 'redirect',
                        'locale' => 'ru_RU',
                        'return_url' => 'https://merchant-site.ru/return_url',
                    ],
                    'capture' => true,
                    'description' => 'Заказ №72',
                    'metadata' => [
                        'orderNumber' => 1001
                    ],
                    'receipt' => [
                        'customer' => [
                            'full_name' => 'Ivanov Ivan Ivanovich',
                            'email' => 'email@email.ru',
                            'phone' => '79211234567',
                            'inn' => '6321341814'
                        ],
                        'items' => [
                            [
                                'description' => 'Переносное зарядное устройство Хувей',
                                'quantity' => '1.00',
                                'amount' => [
                                    'value' => 1000,
                                    'currency' => 'RUB'
                                ],
                                'vat_code' => '2',
                                'payment_mode' => 'full_payment',
                                'payment_subject' => 'commodity',
                                'country_of_origin_code' => 'CN',
                                'product_code' => '44 4D 01 00 21 FA 41 00 23 05 41 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 12 00 AB 00',
                                'customs_declaration_number' => '10714040/140917/0090376',
                                'excise' => '20.00',
                                'supplier' => [
                                    'name' => 'string',
                                    'phone' => 'string',
                                    'inn' => 'string'
                                ]
                            ],
                        ]
                    ]
                ],
                $idempotenceKey
            );

            //получаем confirmationUrl для дальнейшего редиректа
            $confirmationUrl = $response->getConfirmation()->getConfirmationUrl();
        } catch (\Exception $e) {
            $response = $e;
        }
    }

    public function callback(Request $request) {
    }
}
