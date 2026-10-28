<?php

namespace App\Http\Controllers;

use App\Http\Api\Yookassa;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller {
    /**
     * Ссылка на оплату первого месяца. Возвращает URL или строку с ошибкой.
     * Без ключей ЮKassa вне production создаётся тестовый платёж с имитацией оплаты.
     */
    public static function create($subscription) {
        $payment = $subscription->payments()
            ->orderBy('created_at', 'desc')
            ->first();

        if ($payment && (int) $payment->payment_status_id === Payment::IN_PROGRESS) {
            if (str_starts_with($payment->payment_gateway_transaction, 'test-')) {
                return self::testUrl($subscription);
            }

            return 'https://yoomoney.ru/checkout/payments/v2/contract?orderId=' . $payment->payment_gateway_transaction;
        }

        if ($payment && (int) $payment->payment_status_id === Payment::PAYED) {
            return 'Уже есть оплаченная транзакция. По ней будут рекуррентные списания.';
        }

        if (!Yookassa::isConfigured()) {
            if (app()->isProduction()) {
                Log::channel('shop')->error('Нет ключей ЮKassa, оплата невозможна.', ['subscription_id' => $subscription->id]);

                return 'Оплата временно недоступна.';
            }

            $paymentModel = Payment::create([
                'payment_status_id' => Payment::IN_PROGRESS,
                'amount' => $subscription->firstPaymentAmount(),
                'payment_gateway_transaction' => 'test-' . bin2hex(random_bytes(6)),
            ]);
            $subscription->payments()->attach($paymentModel->id);

            return self::testUrl($subscription);
        }

        $yookassa = new Yookassa();
        $paymentYookassa = $yookassa->createPayment($subscription->id);

        if (!$paymentYookassa) {
            Log::channel('shop')->error('Ошибка при создании платежа через Yookassa', [
                'subscription_id' => $subscription->id,
            ]);

            return 'Ошибка создания операции в платежном сервисе.';
        }

        $paymentModel = Payment::create([
            'payment_status_id' => Payment::IN_PROGRESS,
            'amount' => $paymentYookassa->amount->value,
            'payment_gateway_transaction' => $paymentYookassa->id,
        ]);

        $subscription->payments()->attach($paymentModel->id);

        Log::channel('shop')->info('Создана новая оплата через Yookassa.', [
            'subscription_id' => $subscription->id,
            'amount' => $paymentYookassa->amount->value,
            'payment_gateway_transaction' => $paymentYookassa->id,
        ]);

        return $paymentYookassa->getConfirmation()->getConfirmationUrl();
    }

    /** Адрес имитации оплаты: подписанный, чтобы клиент из квиза открыл его без входа. */
    private static function testUrl(Subscription $subscription): string {
        return \Illuminate\Support\Facades\URL::temporarySignedRoute('payment.test', now()->addDay(), ['subscription' => $subscription->id]);
    }

    /** Возврат с оплаты. Статус подтверждает вебхук, поэтому страница может прийти раньше него и перепроверяет себя. */
    public function redirect(Request $request) {
        $subscription = Subscription::with('plan')->find($request->integer('subscription_id'));
        if (!$subscription) {
            return redirect()->route('home');
        }

        // Страница показывает адрес и суммы, поэтому только владельцу. Сессия могла истечь, пока
        // клиент был на стороне банка: тогда просим войти и возвращаем сюда же.
        if (!$request->user()) {
            // Из квиза клиент возвращается без входа: подписанный адрес из ЮKassa впускает владельца в кабинет
            if ($request->hasValidSignatureWhileIgnoring(['attempt']) && $subscription->user && $subscription->user->isClient() && $subscription->user->is_active) {
                \Illuminate\Support\Facades\Auth::login($subscription->user, true);
                $request->session()->regenerate();
            } else {
                return redirect()->guest(route('auth.login'));
            }
        }
        abort_unless($subscription->belongsToUser($request->user()), 403);

        $payment = $subscription->payments()->orderBy('created_at', 'desc')->first();
        $paid = $payment && (int) $payment->payment_status_id === Payment::PAYED;
        $attempt = $request->integer('attempt');

        if (!$paid && $attempt < 5) {
            return view('payment.pending', ['subscription' => $subscription, 'attempt' => $attempt + 1]);
        }

        $subscription->load('plan.options', 'timeDelivery');
        $view = $subscription->source === Subscription::SOURCE_QUIZ ? 'quiz.' : 'payment.';

        return $paid
            ? view($view . 'success', ['subscription' => $subscription, 'payment' => $payment])
            : view($view . 'fail', ['subscription' => $subscription]);
    }

    /** Имитация оплаты для тестового стенда: только когда ключей ЮKassa нет и это не production. */
    public function test(Request $request, Subscription $subscription) {
        abort_if(app()->isProduction() || Yookassa::isConfigured(), 404);
        if (!$request->user()) {
            if ($request->hasValidSignature() && $subscription->user && $subscription->user->isClient()) {
                \Illuminate\Support\Facades\Auth::login($subscription->user, true);
                $request->session()->regenerate();
            } else {
                return redirect()->guest(route('auth.login'));
            }
        }
        abort_unless($subscription->belongsToUser($request->user()), 403);

        $payment = $subscription->payments()->orderBy('created_at', 'desc')->first();
        abort_unless($payment && str_starts_with($payment->payment_gateway_transaction, 'test-'), 404);

        if ($request->isMethod('POST')) {
            $payment->update(['payment_status_id' => Payment::PAYED, 'recurrent_token' => 'test-token']);
            Yookassa::activate($subscription);

            return redirect()->to(\Illuminate\Support\Facades\URL::temporarySignedRoute('payment.yookassa.redirect', now()->addDay(), ['subscription_id' => $subscription->id]));
        }

        return view('payment.test', ['subscription' => $subscription, 'payment' => $payment]);
    }
}
