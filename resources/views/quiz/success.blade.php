@extends('layouts.site')

@section('title', 'Подписка оплачена — Цветофор')

@section('robots', 'noindex, nofollow')

@section('bodyClass', 'qz-body')

@php
    $first = $subscription->deliveries()->planned()->orderBy('planned_date')->get();
    $firstDate = $first->first()?->planned_date;
    $second = $first->skip(1)->first()?->planned_date;
    $window = $subscription->timeDelivery ? 'с ' . substr($subscription->timeDelivery->from, 0, 5) . ' до ' . substr($subscription->timeDelivery->to, 0, 5) : '';
    $isGift = ($subscription->preferences['for'] ?? null) === 'gift';
    $recipient = $isGift ? $subscription->receiving_name : 'вас';
    $agree = !empty($subscription->preferences['agree_with_recipient']);
    $monthly = number_format($subscription->monthlyAmount(), 0, '', ' ');
    $perMonth = \App\Services\Pricing::PER_MONTH[$subscription->frequency] ?? 4;
    $extras = $subscription->plan->options->pluck('name')->join(', ');
@endphp

@section('body')
    <main class="qz qz-done" id="paymentResult" data-hero-variant="q" data-design="quiz">
        <header class="qz-top">
            <div class="qz-top-l"><a class="qz-logo" href="{{ route('home') }}" aria-label="Цветофор, на главную"><img src="{{ asset('images/logo-mark.svg') }}" alt="" width="50" height="50"><span class="qz-word">Цветофор</span></a></div>
            <div class="qz-top-r"><span class="qz-note">Подписка оформлена</span></div>
        </header>
        <section class="qz-screen qz-step">
            <div class="qz-main">
                <span class="qz-mark ok" aria-hidden="true"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7"/></svg></span>
                <div>
                    <h1 class="qz-h1">Готово, {{ $subscription->sender_name }}. Первый букет уже в работе</h1>
                    <p class="qz-lead">Ссылку на кабинет и чек отправили на {{ \App\Support\Phone::pretty($subscription->sender_phone) }}. Код вводить не нужно, просто откройте ссылку из СМС.</p>
                </div>
                <ol class="qz-tl" aria-label="Что будет дальше">
                    <li class="green rise d1"><b>Сегодня.</b> Оплачено {{ number_format((float) $payment->amount, 0, '', ' ') }} ₽. Флорист прочитает ваши пожелания и соберёт первый букет.</li>
                    @if ($agree)
                        <li class="rise d2"><b>В ближайшие дни.</b> Позвоним {{ $subscription->receiving_name }}, договоримся о дне и времени первой доставки.</li>
                    @elseif ($firstDate)
                        <li class="rise d2"><b>{{ $firstDate->copy()->subDay()->translatedFormat('j F') }}.</b> Напомним СМС, что завтра доставка.</li>
                        <li class="pink rise d3"><b>{{ $firstDate->translatedFormat('j F') }}{{ $window ? ', ' . $window : '' }}.</b> Курьер позвонит за час и привезёт букет «{{ $subscription->planName() }}»{{ $extras ? ' с дополнениями: ' . mb_strtolower($extras) : '' }}.</li>
                    @endif
                    @if ($second && !$agree)
                        <li class="gift rise d4"><b>{{ $second->translatedFormat('j F') }}.</b> Второй букет, бесплатно. Дальше {{ mb_strtolower(\App\Services\Pricing::LABELS[$subscription->frequency] ?? '') }}, {{ \App\Support\Days::EACH[$subscription->delivery_day] ?? '' }}.</li>
                    @endif
                    <li class="rise d4"><b>{{ $subscription->next_date_payment?->translatedFormat('j F') }}.</b> Списание {{ $monthly }} ₽ за следующие {{ $perMonth }} {{ $perMonth === 1 ? 'букет' : 'букета' }}, напомним за 3 дня.</li>
                </ol>
                <div class="qz-cta-row">
                    <a class="qz-btn qz-btn-lg" href="{{ route('profile') }}">Открыть кабинет</a>
                    <span class="qz-note">В кабинете можно поменять день и время, перенести доставку, поставить подписку на паузу или отменить</span>
                </div>
            </div>
            <aside class="qz-side">
                <div class="qz-side-photo"><img src="{{ $subscription->plan->imageUrl() }}" alt="" width="900" height="900"></div>
                <div class="qz-eyebrow">Ваша подписка</div>
                <div class="qz-kv">
                    <div><span>Букет</span><span class="v"><b>{{ $subscription->planName() }}</b>, {{ number_format((float) $subscription->plan->price, 0, '', ' ') }} ₽ · {{ mb_strtolower(\App\Services\Pricing::LABELS[$subscription->frequency] ?? $subscription->frequency) }}</span></div>
                    <div><span>Кому</span><span class="v">{{ $isGift ? $subscription->receiving_name . ', ' : '' }}{{ $subscription->address }}</span></div>
                    <div><span>Когда</span><span class="v">{{ $agree ? 'Договоримся с получателем' : (\App\Support\Days::NOMINATIVE[$subscription->delivery_day] ?? '') . ($window ? ', ' . $window : '') }}</span></div>
                    @if (!empty($subscription->preferences['card_text']) || $extras)
                        <div><span>Открытка</span><span class="v">{{ !empty($subscription->preferences['card_text']) ? '«' . $subscription->preferences['card_text'] . '»' : 'без текста' }}{{ $extras ? ' · ' . mb_strtolower($extras) : '' }}</span></div>
                    @endif
                </div>
                <div class="qz-total"><span>Оплачено сегодня</span><b>{{ number_format((float) $payment->amount, 0, '', ' ') }} ₽</b></div>
                <p class="qz-side-sub">Дальше {{ $monthly }} ₽ раз в месяц за следующие {{ $perMonth }} {{ $perMonth === 1 ? 'букет' : 'букета' }}.</p>
                <div class="qz-pink small"><b>Вопрос по заказу?</b><span>Напишите нам в <a href="https://wa.me/79676202220" rel="noopener" target="_blank">WhatsApp</a> или <a href="https://t.me/cvetofor_03" rel="noopener" target="_blank">Telegram</a>, ответим с 9:00 до 21:00.</span></div>
            </aside>
        </section>
    </main>
    <script type="application/json" id="purchase-data">{!! json_encode([
        'id' => (string) $payment->payment_gateway_transaction,
        'subscription_id' => $subscription->id,
        'revenue' => (float) $payment->amount,
        'products' => [[
            'id' => (string) $subscription->plan_id,
            'name' => $subscription->planName(),
            'category' => 'Подписка на цветы',
            'price' => (float) $subscription->plan->price,
            'quantity' => \App\Services\Pricing::FIRST_MONTH_PAID[$subscription->frequency] ?? 1,
            'variant' => \App\Services\Pricing::LABELS[$subscription->frequency] ?? $subscription->frequency,
        ]],
        'frequency' => $subscription->frequency,
        'plan_id' => $subscription->plan_id,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection
