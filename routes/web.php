<?php

use App\Http\Controllers\MainController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// Главная страница
Route::get('/', [MainController::class, 'index'])->name('home');

Route::get('/user_agreement', function () {
    return view('information.user_agreement');
})->name('user_agreement');

Route::group(['prefix' => '/payment', 'as' => 'payment.'], function () {
    Route::group(['prefix' => '/yookassa', 'as' => 'yookassa.'], function () {
        Route::get('/redirect', [PaymentController::class, 'redirect'])->name('redirect');
    });
});
