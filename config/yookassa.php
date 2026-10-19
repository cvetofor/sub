<?php

// Ключи только из окружения. Боевые и тестовые значения в коде не хранятся.
return [
    'shopId' => env('YOOKASSA_SHOP_ID'),
    'secretKey' => env('YOOKASSA_SECRET_KEY'),

    // Адрес вебхука старого сайта, пока он работает на соседнем поддомене. Пусто — чужие уведомления просто подтверждаем.
    'legacy_callback' => env('YOOKASSA_LEGACY_CALLBACK'),
];
