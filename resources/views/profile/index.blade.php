@extends('layouts.site')

@section('title', 'Мои подписки — Цветофор')

@section('body')
    @include('partials.header')
    <main class="cab">
        <div class="cab-head">
            <div>
                <h1 class="serif">Мои подписки</h1>
                <p class="lead">{{ $user->name }} · {{ \App\Support\Phone::pretty($user->phone_e164 ?: $user->phone) }}</p>
            </div>
            <a class="linkBtn" href="{{ route('logout') }}">Выйти</a>
        </div>

        @if (session('success'))
            <div class="notice ok" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert" role="alert" style="margin:0">{{ session('error') }}</div>
        @endif

        @if ($subscriptions->isEmpty())
            <div class="summary empty">
                <p>Подписок пока нет. Выберите план, и первый букет приедет уже в ближайшую субботу.</p>
                <a class="btn" href="{{ route('home') }}#plans">Выбрать план</a>
            </div>
        @else
            <div class="cab-list">
                @foreach ($subscriptions as $sub)
                    <article class="summary sub" aria-labelledby="sub-{{ $sub->id }}">
                        <div class="sub-top">
                            <div>
                                <div class="k">Подписка №{{ $sub->id }}</div>
                                <h2 id="sub-{{ $sub->id }}">{{ $sub->plan->name }}</h2>
                            </div>
                            @if (!$sub->is_paid)
                                <span class="badge warn">Не оплачена</span>
                            @elseif ($sub->is_active)
                                <span class="badge">Активна</span>
                            @else
                                <span class="badge muted">На паузе</span>
                            @endif
                        </div>
                        <div class="row"><span>Частота</span><span>{{ \App\Services\Pricing::LABELS[$sub->frequency] ?? $sub->frequency }}</span></div>
                        @if ($sub->delivery_day)
                            <div class="row"><span>День</span><span>{{ \App\Support\Days::NOMINATIVE[$sub->delivery_day] ?? '' }}@if ($sub->timeDelivery), {{ substr($sub->timeDelivery->from, 0, 5) }}–{{ substr($sub->timeDelivery->to, 0, 5) }}@endif</span></div>
                        @endif
                        <div class="row"><span>Адрес</span><span>{{ $sub->address }}</span></div>
                        @if ($sub->receiving_name && $sub->receiving_name !== $sub->sender_name)
                            <div class="row"><span>Получатель</span><span>{{ $sub->receiving_name }}</span></div>
                        @endif
                        @if ($sub->plan->options->isNotEmpty())
                            <div class="row"><span>Дополнения</span><span>{{ $sub->plan->options->pluck('name')->join(', ') }}</span></div>
                        @endif
                        <hr>
                        @if ($sub->is_paid)
                            <div class="row"><span>Списание раз в месяц</span><span class="total" style="font-size:20px">{{ number_format($sub->monthlyAmount(), 0, '', ' ') }} ₽</span></div>
                            @if ($sub->is_active && $sub->next_date_payment)
                                <div class="row"><span>Следующее</span><span>{{ $sub->next_date_payment->translatedFormat('j F Y') }}</span></div>
                            @endif
                            <div class="sub-actions">
                                @if ($sub->is_active)
                                    <form method="POST" action="{{ route('subscription.disable', $sub) }}">@csrf<button class="btn ghost" type="submit">Поставить на паузу</button></form>
                                @else
                                    <form method="POST" action="{{ route('subscription.active', $sub) }}">@csrf<button class="btn" type="submit">Возобновить</button></form>
                                @endif
                            </div>
                            <p class="toast" style="margin:0">Пауза останавливает списания и доставки. Возобновить можно в любой момент.</p>
                        @else
                            <div class="row"><span>К оплате за первый месяц</span><span class="total" style="font-size:20px">{{ number_format($sub->firstPaymentAmount(), 0, '', ' ') }} ₽</span></div>
                            <div class="sub-actions"><a class="btn" href="{{ route('subscription.pay', $sub) }}">Оплатить</a></div>
                        @endif
                    </article>
                @endforeach
            </div>
            <p class="toast">Поменять адрес, день или состав: напишите нам, и флорист всё перенастроит. Самостоятельное редактирование скоро появится.</p>
        @endif
    </main>
    @include('partials.footer')
@endsection
