<?php

use App\Http\Api\Yookassa;
use Illuminate\Support\Facades\Route;

// Вебхук ЮKassa. Создание подписки и управление ею перенесены в web-маршруты под авторизацию.
Route::group(['prefix' => '/payment', 'as' => 'payment.'], function () {
    Route::group(['prefix' => '/yookassa', 'as' => 'yookassa.'], function () {
        Route::post('/callback', [Yookassa::class, 'callback']);
    });
});
