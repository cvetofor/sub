@extends('layouts.site')

@section('title', 'Тестовая оплата — Цветофор')

@section('robots', 'noindex, nofollow')

@section('body')
    @include('partials.header')
    <main class="result">
        <h1 class="serif">Тестовая оплата</h1>
        <p>Ключи ЮKassa не заданы, поэтому платёж имитируется. На боевом сайте этой страницы нет.</p>
        <div class="summary">
            <div class="k">Платёж</div>
            <div class="row"><span>Подписка</span><span>№{{ $subscription->id }} · {{ $subscription->plan->name }}</span></div>
            <div class="row"><span>Сумма</span><span class="total">{{ number_format((float) $payment->amount, 0, '', ' ') }} ₽</span></div>
        </div>
        <form method="POST" action="{{ route('payment.test', ['subscription' => $subscription->id]) }}" class="row-actions">
            @csrf
            <button class="btn" type="submit" id="payTestBtn">Оплатить (имитация)</button>
            <a class="btn ghost" style="margin-top:0" href="{{ route('payment.yookassa.redirect', ['subscription_id' => $subscription->id, 'attempt' => 9]) }}">Отменить</a>
        </form>
    </main>
    @include('partials.footer')
@endsection
