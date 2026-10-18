<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WizardController;
use App\Http\Middleware\AssignHeroVariant;
use Illuminate\Support\Facades\Route;

// Главная: новая версия с A/B-тестом первого экрана
Route::get('/', [HomeController::class, 'index'])->middleware(AssignHeroVariant::class)->name('home');

// Мастер подписки
Route::get('/subscribe', [WizardController::class, 'show'])->name('wizard');

Route::get('/user_agreement', function () {
    return view('information.user_agreement');
})->name('user_agreement');

Route::group(['prefix' => '/payment', 'as' => 'payment.'], function () {
    Route::group(['prefix' => '/yookassa', 'as' => 'yookassa.'], function () {
        Route::get('/redirect', [PaymentController::class, 'redirect'])->name('redirect');
    });
});

require __DIR__ . '/auth.php';
