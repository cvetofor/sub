<?php

use App\Http\Controllers\AuthLinkController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WizardController;
use App\Http\Middleware\AssignDesign;
use Illuminate\Support\Facades\Route;

// Главная: дизайн выбирает middleware (классическая главная или квиз), там же A/B-тест
Route::get('/', [HomeController::class, 'index'])->middleware(AssignDesign::class)->name('home');

// Квиз: оформление за пять вопросов без кода из СМС. Прямой адрес всегда показывает квиз.
Route::get('/quiz', [QuizController::class, 'index'])->middleware(AssignDesign::class . ':quiz')->name('quiz');
Route::post('/quiz/subscribe', [QuizController::class, 'store'])->middleware(['throttle:10,1'])->name('quiz.store');

// Вход по ссылке из СМС после оплаты
Route::get('/k/{token}', AuthLinkController::class)->middleware('throttle:30,1')->name('auth.link');

// Мастер подписки: страница и создание подписки (после входа по коду)
Route::get('/subscribe', [WizardController::class, 'show'])->middleware(AssignDesign::class . ':classic')->name('wizard');
Route::post('/subscribe', [SubscriptionController::class, 'store'])->middleware(['auth', 'throttle:10,1'])->name('subscribe.store');

// Управление подпиской: только владелец
Route::middleware('auth')->group(function () {
    Route::post('/subscription/{subscription}/pause', [SubscriptionController::class, 'pause'])->name('subscription.disable');
    Route::post('/subscription/{subscription}/resume', [SubscriptionController::class, 'resume'])->name('subscription.active');
    Route::get('/subscription/{subscription}/pay', [SubscriptionController::class, 'pay'])->name('subscription.pay');
    Route::post('/subscription/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    Route::post('/subscription/{subscription}/delivery', [SubscriptionController::class, 'updateDelivery'])->name('subscription.delivery');
    Route::post('/subscription/{subscription}/deliveries/{delivery}/skip', [SubscriptionController::class, 'skipDelivery'])->name('subscription.skip');
});

Route::get('/sitemap.xml', [HomeController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [HomeController::class, 'robots'])->name('robots');

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
