<?php

// Ключи только из окружения. Боевые и тестовые значения в коде не хранятся.
return [
    'shopId' => env('YOOKASSA_SHOP_ID'),
    'secretKey' => env('YOOKASSA_SECRET_KEY'),
];
