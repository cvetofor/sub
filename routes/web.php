<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WizardController;
use App\Http\Middleware\AssignHeroVariant;
use Illuminate\Support\Facades\Route;

// Главная: новая версия с A/B-тестом первого экрана
Route::get('/', [HomeController::class, 'index'])->middleware(AssignHeroVariant::class)->name('home');

// Мастер подписки: страница и создание подписки (после входа по коду)
Route::get('/subscribe', [WizardController::class, 'show'])->name('wizard');
Route::post('/subscribe', [SubscriptionController::class, 'store'])->middleware(['auth', 'throttle:10,1'])->name('subscribe.store');

// Управление подпиской: только владелец
Route::middleware('auth')->group(function () {
    Route::post('/subscription/{subscription}/pause', [SubscriptionController::class, 'pause'])->name('subscription.disable');
    Route::post('/subscription/{subscription}/resume', [SubscriptionController::class, 'resume'])->name('subscription.active');
    Route::get('/subscription/{subscription}/pay', [SubscriptionController::class, 'pay'])->name('subscription.pay');
    Route::post('/subscription/{subscription}/delivery', [SubscriptionController::class, 'updateDelivery'])->name('subscription.delivery');
});

Route::get('/sitemap.xml', [HomeController::class, 'sitemap'])->name('sitemap');

Route::get('/user_agreement', function () {
    return view('information.user_agreement');
})->name('user_agreement');

Route::group(['prefix' => '/payment', 'as' => 'payment.'], function () {
    Route::group(['prefix' => '/yookassa', 'as' => 'yookassa.'], function () {
        Route::get('/redirect', [PaymentController::class, 'redirect'])->name('redirect');
    });
    // Имитация оплаты на тестовом стенде, в production отвечает 404
    Route::match(['get', 'post'], '/test/{subscription}', [PaymentController::class, 'test'])->name('test');
});

require __DIR__ . '/auth.php';
