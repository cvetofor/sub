<?php

use App\Http\Api\Yookassa;
use App\Models\Delivery;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\DeliverySchedule;
use App\Services\Notifier;
use App\Services\TeamNotifier;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

/*
 * Ежемесячные списания. Берём активные подписки, у которых подошла дата,
 * списываем сумму обычного месяца по сохранённому способу оплаты.
 */
Schedule::call(function () {
    if (! Yookassa::isConfigured()) {
        Log::channel('shop')->warning('Повторные списания пропущены: нет ключей ЮKassa.');

        return;
    }

    $now = Carbon::now();
    $subscriptions = Subscription::active()
        ->with('plan.options')
        ->where('next_date_payment', '<=', $now)
        ->get();

    foreach ($subscriptions as $subscription) {
        // Одна незавершённая попытка уже есть: повторно деньги не запрашиваем.
        $attempt = $subscription->payments()
            ->where('payment_status_id', Payment::IN_PROGRESS)
            ->orderByDesc('payments.created_at')
            ->first();
        if ($attempt && ! Yookassa::isUnsentRecurringAttempt($attempt)) {
            Log::channel('shop')->info('Повторное списание уже ожидает ответ ЮKassa.', ['subscription_id' => $subscription->id, 'payment' => $attempt->payment_gateway_transaction]);

            continue;
        }

        // Токен берём с последнего успешного платежа, у которого он сохранён
        $payment = $subscription->payments()
            ->where('payment_status_id', Payment::PAYED)
            ->whereNotNull('recurrent_token')
            ->where('recurrent_token', '!=', '')
            ->orderByDesc('payments.created_at')
            ->first();

        if (! $payment || str_starts_with((string) $payment->recurrent_token, 'test-')) {
            Log::channel('shop')->warning('Нет сохранённого способа оплаты, подписка остановлена.', ['subscription_id' => $subscription->id]);
            $subscription->update(['is_active' => false, 'pause_reason' => \App\Models\Subscription::PAUSE_PAYMENT]);
            Notifier::paymentFailed($subscription, $subscription->monthlyAmount());

            continue;
        }

        try {
            $attempt ??= Yookassa::startRecurringPaymentAttempt($subscription, $payment->recurrent_token);
            $yookassa = new Yookassa;
            $paymentYookassa = $yookassa->recurrentPayment($payment->recurrent_token, $subscription, $attempt->idempotence_key);
        } catch (\Throwable $e) {
            Log::channel('shop')->error('Ошибка повторного списания.', ['subscription_id' => $subscription->id, 'message' => $e->getMessage()]);

            continue;
        }

        if ($paymentYookassa && $paymentYookassa->status === 'pending') {
            // Банк ещё думает: итог придёт уведомлением payment.succeeded или payment.canceled
            Yookassa::saveRecurringPaymentInProgress($subscription, $paymentYookassa, $payment->recurrent_token);
            Log::channel('shop')->info('Повторное списание в обработке, ждём уведомление.', ['subscription_id' => $subscription->id, 'payment' => $paymentYookassa->id]);

            continue;
        }

        if ($paymentYookassa && $paymentYookassa->status === 'succeeded' && $paymentYookassa->paid === true) {
            $newPayment = Yookassa::completeRecurringPayment(
                $subscription,
                $paymentYookassa->id,
                $paymentYookassa->amount->value,
                $paymentYookassa->payment_method->id ?? $payment->recurrent_token,
            );
            if ($newPayment) {
                $subscription->refresh()->load('plan.options');
                // Доставки следующего месяца и СМС клиенту
                DeliverySchedule::planNextMonth($subscription);
                Notifier::renewed($subscription, (float) $newPayment->amount);

                Log::channel('shop')->info('Подписка продлена.', ['subscription_id' => $subscription->id, 'amount' => $newPayment->amount, 'payment' => $paymentYookassa->id]);
            }
        } else {
            if (Yookassa::cancelRecurringPayment($subscription, $paymentYookassa?->id ?? $attempt->payment_gateway_transaction)) {
                $subscription->refresh()->load('plan.options');
                Notifier::paymentFailed($subscription, $subscription->monthlyAmount());
                TeamNotifier::paymentFailed($subscription, $subscription->monthlyAmount());

                Log::channel('shop')->info('Не удалось продлить подписку, поставлена на паузу.', ['subscription_id' => $subscription->id, 'status' => $paymentYookassa?->status]);
            }
        }
    }
})->everyTwoHours()->name('subscriptions:charge-recurrent')->withoutOverlapping();

/*
 * Контролируемая проверка рекуррентного платежа: только одна конкретная
 * подписка. Пока --confirm не повторяет её ID, команда лишь показывает
 * готовность и не обращается в ЮKassa.
 */
Artisan::command('subscriptions:charge-one-recurrent
    {subscription : Номер подписки для единственного списания}
    {--due= : Ожидаемая дата следующей оплаты из проверки (ГГГГ-ММ-ДД)}
    {--confirm= : Повторите номер подписки, чтобы действительно создать платёж}', function () {
    $subscription = Subscription::with('plan.options')->find((int) $this->argument('subscription'));
    if (! $subscription) {
        $this->error('Подписка не найдена. Платёж не создавался.');

        return 1;
    }

    $savedPayment = $subscription->payments()
        ->where('payment_status_id', Payment::PAYED)
        ->whereNotNull('recurrent_token')
        ->where('recurrent_token', '!=', '')
        ->orderByDesc('payments.created_at')
        ->first();

    $this->line('subscription_id='.$subscription->id);
    $this->line('monthly_amount='.number_format($subscription->monthlyAmount(), 2, '.', '').' RUB');
    $this->line('active='.($subscription->is_active ? 'yes' : 'no'));
    $this->line('saved_payment_method='.($savedPayment && ! str_starts_with((string) $savedPayment->recurrent_token, 'test-') ? 'yes' : 'no'));
    $this->line('next_payment_date='.($subscription->next_date_payment?->toDateString() ?? 'not_set'));

    if ((string) $this->option('confirm') !== (string) $subscription->id) {
        $this->warn('Платёж не создан. Для адресного списания повторите номер подписки: --confirm='.$subscription->id);

        return 0;
    }

    $dueDate = $subscription->next_date_payment?->toDateString();
    if (! $dueDate || (string) $this->option('due') !== $dueDate) {
        $this->warn('Платёж не создан. Для адресного списания подтвердите дату из проверки: --due='.($dueDate ?? 'not_set'));

        return 0;
    }

    if (! Yookassa::isConfigured()) {
        $this->error('ЮKassa не настроена. Платёж не создавался.');

        return 1;
    }

    if (! $subscription->is_active) {
        $this->error('Подписка не активна. Платёж не создавался.');

        return 1;
    }

    // Незавершённый запрос уже зафиксирован: повторно деньги не списываем.
    $attempt = $subscription->payments()
        ->where('payment_status_id', Payment::IN_PROGRESS)
        ->orderByDesc('payments.created_at')
        ->first();
    if ($attempt && ! Yookassa::isUnsentRecurringAttempt($attempt)) {
        $this->warn('Повторное списание уже ожидает ответ ЮKassa. Новый платёж не создавался.');

        return 0;
    }

    if (! $savedPayment || str_starts_with((string) $savedPayment->recurrent_token, 'test-')) {
        $this->error('Нет сохранённого боевого способа оплаты. Платёж не создавался.');

        return 1;
    }

    try {
        $attempt ??= Yookassa::startRecurringPaymentAttempt($subscription, $savedPayment->recurrent_token);
        $paymentYookassa = (new Yookassa)->recurrentPayment($savedPayment->recurrent_token, $subscription, $attempt->idempotence_key);
    } catch (\Throwable $e) {
        Log::channel('shop')->error('Ошибка адресного теста повторного списания.', ['subscription_id' => $subscription->id, 'message' => $e->getMessage()]);
        $this->error('ЮKassa вернула ошибку. Смотрите журнал магазина; повторный платёж не подтверждён.');

        return 1;
    }

    if ($paymentYookassa && $paymentYookassa->status === 'pending') {
        Yookassa::saveRecurringPaymentInProgress($subscription, $paymentYookassa, $savedPayment->recurrent_token);
        $this->info('Платёж создан и ожидает ответ банка. Итог обработает уведомление ЮKassa.');

        return 0;
    }

    if ($paymentYookassa && $paymentYookassa->status === 'succeeded' && $paymentYookassa->paid === true) {
        $newPayment = Yookassa::completeRecurringPayment(
            $subscription,
            $paymentYookassa->id,
            $paymentYookassa->amount->value,
            $paymentYookassa->payment_method->id ?? $savedPayment->recurrent_token,
        );
        if ($newPayment) {
            $subscription->refresh()->load('plan.options');
            DeliverySchedule::planNextMonth($subscription);
            Notifier::renewed($subscription, (float) $newPayment->amount);
            $this->info('Платёж успешно списан, подписка продлена.');

            return 0;
        }

        $this->warn('Платёж уже был обработан ранее. Повторное списание не создано.');

        return 0;
    }

    $transactionId = $paymentYookassa?->id ?? $attempt->payment_gateway_transaction;
    if (Yookassa::cancelRecurringPayment($subscription, $transactionId)) {
        $subscription->refresh()->load('plan.options');
        Notifier::paymentFailed($subscription, $subscription->monthlyAmount());
        TeamNotifier::paymentFailed($subscription, $subscription->monthlyAmount());
    }
    $this->error('Банк не подтвердил повторное списание. Подписка поставлена на паузу.');

    return 1;
})->purpose('Создать одно подтверждённое рекуррентное списание для указанной подписки');

/* Напоминание о завтрашней доставке: один раз, только по активным подпискам. */
Schedule::call(function () {
    $tomorrow = Carbon::tomorrow()->toDateString();
    $deliveries = Delivery::planned()->onDate($tomorrow)->whereNull('reminded_at')
        ->whereHas('subscription', fn ($q) => $q->where('is_active', true))
        ->with('subscription', 'timeDelivery')
        ->get();
    foreach ($deliveries as $delivery) {
        Notifier::deliveryTomorrow($delivery);
    }
    if ($deliveries->isNotEmpty()) {
        Log::channel('shop')->info('Разосланы напоминания о доставках.', ['date' => $tomorrow, 'count' => $deliveries->count()]);
    }
})->dailyAt('18:00')->name('deliveries:remind')->withoutOverlapping();

/* Заявка без оплаты: через заданное в настройках время одна СМС со ссылкой на оплату. */
Schedule::call(function () {
    \App\Services\UnpaidReminder::run();
})->everyTenMinutes()->name('subscriptions:remind-unpaid')->withoutOverlapping();

/* Ночью закрываем вчерашние доставки, которые флорист не отметил вручную. */
Schedule::call(function () {
    $closed = Delivery::planned()->where('planned_date', '<', Carbon::today()->toDateString())
        ->whereHas('subscription', fn ($q) => $q->where('is_active', true))
        ->update(['status' => Delivery::DELIVERED, 'delivered_at' => Carbon::now(), 'note' => 'закрыта автоматически']);
    if ($closed) {
        Log::channel('shop')->info('Прошедшие доставки закрыты автоматически.', ['count' => $closed]);
    }
})->dailyAt('02:00')->name('deliveries:close');
