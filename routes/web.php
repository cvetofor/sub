<?php

use App\Http\Controllers\MainController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// Главная страница
Route::get('/', [MainController::class, 'index'])->name('home');

Route::get('/policy', function () {
    return view('information.policy');
})->name('policy');

Route::group(['prefix' => '/payment', 'as' => 'payment.'], function () {
    Route::group(['prefix' => '/yookassa', 'as' => 'yookassa.'], function () {
        Route::get('/redirect', [PaymentController::class, 'redirect'])->name('redirect');
    });
});
