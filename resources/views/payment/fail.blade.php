@extends('layouts.site')

@section('title', 'Оплата не прошла — Цветофор')

@section('body')
    @include('partials.header')
    <main class="result">
        <span class="mark bad" aria-hidden="true"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg></span>
        <h1 class="serif">Оплата не прошла</h1>
        <p>Деньги не списаны. Подписка №{{ $subscription->id }} сохранена, можно попробовать ещё раз или написать нам.</p>
        <div class="row-actions">
            <a class="btn" href="{{ route('profile') }}">Повторить оплату</a>
            <a class="btn ghost" style="margin-top:0" href="{{ route('home') }}">На главную</a>
        </div>
    </main>
    @include('partials.footer')
@endsection
