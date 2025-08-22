<?php

use App\Http\Api\Yookassa;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/subscription', 'as' => 'subscription.'], function () {
    Route::post('/create', [SubscriptionController::class, 'create'])->name('create');
});

Route::group(['prefix' => '/payment', 'as' => 'payment.'], function () {
    Route::group(['prefix' => '/yookassa', 'as' => 'yookassa.'], function () {
        Route::post('/callback', [Yookassa::class, 'callback']);
    });
});
