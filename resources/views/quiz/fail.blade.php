@extends('layouts.site')

@section('title', 'Оплата не прошла — Цветофор')

@section('robots', 'noindex, nofollow')

@section('bodyClass', 'qz-body')

@section('body')
    <main class="qz qz-done" data-design="quiz">
        <header class="qz-top">
            <div class="qz-top-l"><a class="qz-logo" href="{{ route('home') }}" aria-label="Цветофор, на главную"><img src="{{ asset('images/logo-mark.svg') }}" alt="" width="50" height="50"><span class="qz-word">Цветофор</span></a></div>
            <div class="qz-top-r"><span class="qz-note">Оплата</span></div>
        </header>
        <section class="qz-screen qz-step single">
            <div class="qz-main narrow">
                <span class="qz-mark bad" aria-hidden="true"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v6M12 17h.01"/><circle cx="12" cy="12" r="9"/></svg></span>
                <div>
                    <h1 class="qz-h1">Оплата не прошла</h1>
                    <p class="qz-lead">Деньги не списались, подписка №{{ $subscription->id }} не оформлена. Ваши ответы сохранены, ничего заполнять заново не нужно. Чаще всего банк отклоняет платёж из-за лимита по карте или подтверждения в приложении банка.</p>
                </div>
                <div class="qz-cta-row">
                    <a class="qz-btn qz-btn-lg" href="{{ route('subscription.pay', $subscription) }}">Попробовать снова, {{ number_format($subscription->firstPaymentAmount(), 0, '', ' ') }} ₽</a>
                    <a class="qz-btn ghost" href="{{ route('profile') }}">Открыть кабинет</a>
                </div>
                <span class="qz-note">Не получается? Напишите нам в <a href="https://wa.me/79676202220" rel="noopener" target="_blank">WhatsApp</a> или <a href="https://t.me/cvetofor_03" rel="noopener" target="_blank">Telegram</a>, поможем оформить.</span>
            </div>
        </section>
    </main>
@endsection
