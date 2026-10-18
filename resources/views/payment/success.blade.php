@extends('layouts.site')

@section('title', 'Подписка оплачена — Цветофор')

@section('body')
    @include('partials.header')
    <main class="result" id="paymentResult" data-hero-variant="{{ $subscription->ab_variant }}">
        <span class="mark" aria-hidden="true"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg></span>
        <h1 class="serif">Готово, ждите цветы</h1>
        <p>Подписка №{{ $subscription->id }} оплачена. Первый букет приедет {{ \App\Support\Days::inDay((int) $subscription->delivery_day) }}, за день напишем в СМС.</p>
        <div class="summary">
            <div class="k">Ваша подписка</div>
            <div class="row"><span>План</span><span>{{ $subscription->plan->name }}</span></div>
            <div class="row"><span>Частота</span><span>{{ \App\Services\Pricing::LABELS[$subscription->frequency] ?? $subscription->frequency }}</span></div>
            <div class="row"><span>Адрес</span><span>{{ $subscription->address }}</span></div>
            <div class="row"><span>Оплачено</span><span class="total">{{ number_format((float) $payment->amount, 0, '', ' ') }} ₽</span></div>
            <div class="row"><span>Следующее списание</span><span>{{ $subscription->next_date_payment?->translatedFormat('j F') }} · {{ number_format($subscription->monthlyAmount(), 0, '', ' ') }} ₽</span></div>
        </div>
        <div class="row-actions">
            <a class="btn" href="{{ route('profile') }}">Мои подписки</a>
            <a class="btn ghost" style="margin-top:0" href="{{ route('home') }}">На главную</a>
        </div>
    </main>
    @include('partials.footer')
    <script type="application/json" id="purchase-data">{!! json_encode([
        'id' => (string) $payment->payment_gateway_transaction,
        'subscription_id' => $subscription->id,
        'revenue' => (float) $payment->amount,
        'products' => [[
            'id' => (string) $subscription->plan_id,
            'name' => $subscription->plan->name,
            'category' => 'Подписка на цветы',
            'price' => (float) $subscription->plan->price,
            'quantity' => \App\Services\Pricing::FIRST_MONTH_PAID[$subscription->frequency] ?? 1,
            'variant' => \App\Services\Pricing::LABELS[$subscription->frequency] ?? $subscription->frequency,
        ]],
        'frequency' => $subscription->frequency,
        'plan_id' => $subscription->plan_id,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection
